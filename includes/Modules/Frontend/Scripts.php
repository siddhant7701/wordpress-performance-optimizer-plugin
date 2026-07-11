<?php
/**
 * JavaScript optimization: safe defer and interaction-based delay.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Modules\Frontend;

use UPO\Modules\Abstract_Module;
use UPO\Support\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds defer to enqueued scripts and delays scripts until user interaction.
 *
 * Delay works on a full-page output buffer so it can catch both enqueued and
 * inline third-party scripts. It only runs for logged-out visitors, never
 * touches JSON/LD or template scripts, and loads delayed scripts sequentially
 * to preserve dependency order.
 */
final class Scripts extends Abstract_Module {

	/**
	 * Marker type used for delayed scripts.
	 */
	private const DELAY_TYPE = 'upo-delayed-script';

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'scripts';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function register(): void {
		if ( $this->enabled( 'js_defer' ) ) {
			add_filter( 'script_loader_tag', array( $this, 'add_defer' ), 20, 3 );
		}

		if ( $this->delay_active() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_loader' ), 5 );
			add_action( 'template_redirect', array( $this, 'start_buffer' ), 1 );
		}
	}

	/**
	 * Whether any delay feature should run for this request.
	 *
	 * @return bool
	 */
	private function delay_active(): bool {
		if ( ! $this->enabled( 'js_delay' ) && ! $this->enabled( 'js_delay_third_party' ) && array() === $this->settings->get_lines( 'js_delay_extra' ) ) {
			return false;
		}
		if ( ! Helpers::is_frontend_optimizable() ) {
			return false;
		}
		// Never risk breaking the experience for logged-in users / admin bar.
		return ! is_user_logged_in();
	}

	/**
	 * Add the defer attribute to eligible enqueued scripts.
	 *
	 * @param string $tag    Full script tag.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 * @return string
	 */
	public function add_defer( $tag, $handle, $src ): string {
		$tag = (string) $tag;

		if ( is_admin() || '' === (string) $src ) {
			return $tag;
		}
		if ( Helpers::contains_any( (string) $handle, $this->settings->get_lines( 'js_defer_exclude' ) ) ) {
			return $tag;
		}
		// Do not double up on async/defer or module scripts (already deferred).
		if ( preg_match( '/\b(defer|async)\b/i', $tag ) || false !== strpos( $tag, 'type="module"' ) ) {
			return $tag;
		}
		return preg_replace( '/<script\s/i', '<script defer ', $tag, 1 ) ?? $tag;
	}

	/**
	 * Enqueue the tiny delay loader (auto-excluded from delay by its own URL).
	 *
	 * @return void
	 */
	public function enqueue_loader(): void {
		wp_register_script(
			'upo-delay',
			UPO_ASSETS . 'js/upo-delay.js',
			array(),
			UPO_VERSION,
			true
		);

		$config = array(
			'timeout' => max( 0, (int) $this->settings->get( 'js_delay_timeout', 6 ) ),
		);
		wp_add_inline_script(
			'upo-delay',
			'window.upoDelay = ' . wp_json_encode( $config ) . ';',
			'before'
		);
		wp_enqueue_script( 'upo-delay' );
	}

	/**
	 * Begin buffering the full page output for delay rewriting.
	 *
	 * @return void
	 */
	public function start_buffer(): void {
		ob_start( array( $this, 'process_buffer' ) );
	}

	/**
	 * Rewrite matched script tags in the buffered HTML.
	 *
	 * @param string $html Buffered HTML.
	 * @return string
	 */
	public function process_buffer( $html ): string {
		$html = (string) $html;
		if ( '' === $html || false === stripos( $html, '<script' ) ) {
			return $html;
		}

		$result = preg_replace_callback(
			'#<script\b([^>]*)>(.*?)</script>#is',
			array( $this, 'rewrite_script' ),
			$html
		);

		$html = is_string( $result ) ? $result : $html;

		if ( $this->enabled( 'debug_mode' ) ) {
			$html .= "\n<!-- Organic Kratom USA Performance Optimizer: JS delay active -->\n";
		}

		return $html;
	}

	/**
	 * Decide whether a single script block should be delayed and rewrite it.
	 *
	 * @param array<int, string> $matches preg matches: 0 full, 1 attrs, 2 body.
	 * @return string
	 */
	private function rewrite_script( array $matches ): string {
		$full  = $matches[0];
		$attrs = $matches[1];
		$body  = $matches[2];

		// Never touch our own loader / config or already-delayed scripts.
		if ( false !== stripos( $full, 'upo-delay' ) || false !== stripos( $full, 'upoDelay' ) || false !== stripos( $full, self::DELAY_TYPE ) ) {
			return $full;
		}

		// Only defer executable script types; leave JSON-LD, templates, etc.
		$type = '';
		if ( preg_match( '/\btype\s*=\s*(["\'])(.*?)\1/i', $attrs, $m ) ) {
			$type = strtolower( trim( $m[2] ) );
		}
		$exec_types = array( '', 'text/javascript', 'application/javascript', 'application/ecmascript', 'text/ecmascript', 'module' );
		if ( ! in_array( $type, $exec_types, true ) ) {
			return $full;
		}

		$has_src = (bool) preg_match( '/\ssrc\s*=\s*(["\']).*?\1/i', $attrs );
		$target  = $has_src ? $attrs : $body;

		if ( ! $this->should_delay( $target, $has_src ) ) {
			return $full;
		}

		if ( $has_src ) {
			$new_attrs = preg_replace( '/(\s)src(\s*=\s*)/i', '$1data-upo-src$2', $attrs, 1 ) ?? $attrs;
			$new_attrs = preg_replace( '/\b(async|defer)\b/i', '', $new_attrs ) ?? $new_attrs;
			$new_attrs = preg_replace( '/\btype\s*=\s*(["\']).*?\1/i', '', $new_attrs ) ?? $new_attrs;
			$new_attrs = 'type="' . self::DELAY_TYPE . '" ' . trim( $new_attrs );
			return '<script ' . trim( $new_attrs ) . '></script>';
		}

		$new_attrs = preg_replace( '/\btype\s*=\s*(["\']).*?\1/i', '', $attrs ) ?? $attrs;
		$new_attrs = 'type="' . self::DELAY_TYPE . '" ' . trim( $new_attrs );
		return '<script ' . trim( $new_attrs ) . '>' . $body . '</script>';
	}

	/**
	 * Whether a given target (attrs for external, body for inline) matches the
	 * active delay rules.
	 *
	 * @param string $target  Attribute string or inline body.
	 * @param bool   $has_src Whether the script is external.
	 * @return bool
	 */
	private function should_delay( string $target, bool $has_src ): bool {
		$excludes = array_merge(
			array( 'ultimate-performance-optimizer', 'upo-delay' ),
			$this->settings->get_lines( 'js_defer_exclude' )
		);
		if ( Helpers::contains_any( $target, $excludes ) ) {
			return false;
		}

		// "Delay all" mode: delay every eligible script.
		if ( $this->enabled( 'js_delay' ) ) {
			return true;
		}

		// Third-party / keyword mode.
		$keywords = $this->settings->get_lines( 'js_delay_extra' );
		if ( $this->enabled( 'js_delay_third_party' ) ) {
			$keywords = array_merge( $keywords, $this->third_party_keywords() );
		}

		return array() !== $keywords && Helpers::contains_any( $target, $keywords );
	}

	/**
	 * Known third-party script signatures worth delaying.
	 *
	 * @return string[]
	 */
	private function third_party_keywords(): array {
		return array(
			'google-analytics.com',
			'googletagmanager.com',
			'gtag/js',
			'gtag(',
			'analytics.js',
			'gtm.start',
			'connect.facebook.net',
			'fbevents.js',
			'fbq(',
			'static.hotjar.com',
			'hotjar',
			'clarity.ms',
			'clarity(',
			'tawk.to',
			'crisp.chat',
			'widget.intercom',
			'intercomcdn',
			'js.driftt.com',
			'zdassets.com',
			'livechatinc.com',
			'js.hs-scripts.com',
			'js.hs-analytics.net',
			'platform.twitter.com',
			'ads-twitter.com',
			'doubleclick.net',
			'snap.licdn.com',
			'pinimg.com',
			'tiktok.com/i18n/pixel',
		);
	}
}
