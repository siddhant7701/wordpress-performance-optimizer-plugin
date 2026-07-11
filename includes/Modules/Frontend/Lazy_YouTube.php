<?php
/**
 * YouTube facade: replace heavy iframes with a click-to-load preview.
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
 * Swaps YouTube embeds for a thumbnail + play button. The real player only
 * loads on interaction, removing hundreds of KB from the initial page.
 */
final class Lazy_YouTube extends Abstract_Module {

	/**
	 * Whether at least one embed was replaced this request.
	 *
	 * @var bool
	 */
	private bool $replaced = false;

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'lazy_youtube';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->enabled( 'lazy_youtube' ) ) {
			return;
		}
		add_filter( 'embed_oembed_html', array( $this, 'replace' ), 20 );
		add_filter( 'the_content', array( $this, 'replace' ), 20 );
		add_filter( 'widget_text', array( $this, 'replace' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'wp_footer', array( $this, 'maybe_enqueue_assets' ) );
	}

	/**
	 * Register (but do not yet enqueue) the facade assets.
	 *
	 * @return void
	 */
	public function register_assets(): void {
		wp_register_style( 'upo-frontend', UPO_ASSETS . 'css/upo-frontend.css', array(), UPO_VERSION );
		wp_register_script( 'upo-lazy-youtube', UPO_ASSETS . 'js/upo-lazy-youtube.js', array(), UPO_VERSION, true );
	}

	/**
	 * Enqueue the assets only if we actually replaced an embed.
	 *
	 * @return void
	 */
	public function maybe_enqueue_assets(): void {
		if ( ! $this->replaced ) {
			return;
		}
		wp_enqueue_style( 'upo-frontend' );
		wp_enqueue_script( 'upo-lazy-youtube' );
	}

	/**
	 * Replace YouTube iframes in an HTML string.
	 *
	 * @param string $html Input HTML.
	 * @return string
	 */
	public function replace( $html ): string {
		$html = (string) $html;

		if ( is_admin() || ! Helpers::is_frontend_optimizable() ) {
			return $html;
		}
		if ( false === stripos( $html, '/embed/' ) ) {
			return $html;
		}

		$result = preg_replace_callback(
			'#<iframe\b[^>]*\ssrc=(["\'])(?<src>[^"\']*(?:youtube\.com|youtube-nocookie\.com)/embed/[^"\']+)\1[^>]*>\s*</iframe>#i',
			array( $this, 'build_facade' ),
			$html
		);

		return is_string( $result ) ? $result : $html;
	}

	/**
	 * Build the facade markup for a matched iframe.
	 *
	 * @param array<string, string> $m Regex matches (named group "src").
	 * @return string
	 */
	private function build_facade( array $m ): string {
		$src   = html_entity_decode( $m['src'], ENT_QUOTES );
		$path  = (string) wp_parse_url( $src, PHP_URL_PATH );
		$parts = explode( '/embed/', $path );
		$id    = isset( $parts[1] ) ? preg_replace( '/[^A-Za-z0-9_\-].*$/', '', $parts[1] ) : '';

		if ( '' === $id ) {
			return $m[0];
		}

		$this->replaced = true;

		$nocookie = ( false !== strpos( $src, 'youtube-nocookie' ) ) ? 'youtube-nocookie.com' : 'youtube.com';
		$thumb    = sprintf( 'https://i.ytimg.com/vi/%s/hqdefault.jpg', $id );
		$label    = esc_attr__( 'Play video', 'ultimate-performance-optimizer' );

		return sprintf(
			'<div class="upo-yt" data-id="%1$s" data-host="%2$s" role="button" tabindex="0" aria-label="%4$s" style="background-image:url(%3$s)">' .
			'<button type="button" class="upo-yt__btn" aria-label="%4$s"></button></div>',
			esc_attr( $id ),
			esc_attr( $nocookie ),
			esc_url( $thumb ),
			$label
		);
	}
}
