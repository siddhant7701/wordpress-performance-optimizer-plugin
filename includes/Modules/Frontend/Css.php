<?php
/**
 * CSS optimization: inline minification and async (non-render-blocking) delivery.
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
 * Two conservative CSS optimizations sharing one output buffer:
 *
 *  1. Minify inline <style> blocks output by the theme/plugins.
 *  2. Optionally load stylesheets asynchronously (preload + onload swap) so they
 *     stop blocking the initial render — the biggest lab win on sites with many
 *     enqueued stylesheets. This is off by default because it can cause a flash
 *     of unstyled content; excludes let you protect above-the-fold styles.
 *
 * External file minification and true unused-CSS removal are NOT performed here —
 * they cannot be done safely in PHP alone and are reported in the CSS tab.
 */
final class Css extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'css';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->enabled( 'css_minify_inline' ) && ! $this->enabled( 'css_optimize_delivery' ) ) {
			return;
		}
		add_action( 'template_redirect', array( $this, 'start_buffer' ), 2 );
	}

	/**
	 * Start buffering when the request is optimizable.
	 *
	 * @return void
	 */
	public function start_buffer(): void {
		if ( ! Helpers::is_frontend_optimizable() ) {
			return;
		}
		ob_start( array( $this, 'process' ) );
	}

	/**
	 * Apply the enabled CSS transformations to the buffered HTML.
	 *
	 * @param string $html Buffered HTML.
	 * @return string
	 */
	public function process( $html ): string {
		$html = (string) $html;

		if ( $this->enabled( 'css_minify_inline' ) ) {
			$html = $this->minify_inline_styles( $html );
		}

		if ( $this->enabled( 'css_optimize_delivery' ) ) {
			$html = $this->async_stylesheets( $html );
		}

		return $html;
	}

	/**
	 * Minify inline <style> blocks in the buffered HTML.
	 *
	 * @param string $html Buffered HTML.
	 * @return string
	 */
	private function minify_inline_styles( string $html ): string {
		if ( false === stripos( $html, '<style' ) ) {
			return $html;
		}

		$result = preg_replace_callback(
			'#<style\b([^>]*)>(.*?)</style>#is',
			static function ( array $m ): string {
				$attrs = $m[1];
				// Only handle standard CSS blocks.
				if ( preg_match( '/\btype\s*=\s*(["\'])(.*?)\1/i', $attrs, $t ) && 'text/css' !== strtolower( trim( $t[2] ) ) ) {
					return $m[0];
				}
				return '<style' . $attrs . '>' . Helpers::minify_css( $m[2] ) . '</style>';
			},
			$html
		);

		return is_string( $result ) ? $result : $html;
	}

	/**
	 * Convert render-blocking stylesheet links into async preloads with a
	 * <noscript> fallback, honouring the user's exclusion list.
	 *
	 * @param string $html Buffered HTML.
	 * @return string
	 */
	private function async_stylesheets( string $html ): string {
		if ( false === stripos( $html, '<link' ) ) {
			return $html;
		}

		$excludes = $this->settings->get_lines( 'css_delivery_exclude' );

		$result = preg_replace_callback(
			'/<link\b[^>]*>/i',
			function ( array $m ) use ( $excludes ): string {
				$tag = $m[0];

				// Must be a stylesheet with an href.
				if ( ! preg_match( '/\brel\s*=\s*(["\'])stylesheet\1/i', $tag ) ) {
					return $tag;
				}
				if ( ! preg_match( '/\bhref\s*=\s*(["\'])(.*?)\1/i', $tag, $h ) || '' === trim( $h[2] ) ) {
					return $tag;
				}
				$href = $h[2];

				// Already async / preloaded, or explicitly non-blocking (print).
				if ( preg_match( '/\brel\s*=\s*(["\'])preload\1/i', $tag ) ) {
					return $tag;
				}
				if ( preg_match( '/\bmedia\s*=\s*(["\'])\s*print\s*\1/i', $tag ) ) {
					return $tag;
				}
				// Respect an explicit opt-out marker or the exclusion list.
				if ( false !== stripos( $tag, 'data-no-optimize' ) ) {
					return $tag;
				}
				if ( Helpers::contains_any( $href, $excludes ) ) {
					return $tag;
				}

				// Build the preload swap.
				$preload = preg_replace( '/\brel\s*=\s*(["\'])stylesheet\1/i', 'rel="preload" as="style"', $tag, 1 );
				$preload = is_string( $preload ) ? $preload : $tag;
				$preload = rtrim( $preload );
				$preload = preg_replace( '#\s*/?>$#', '', $preload );
				$preload = (string) $preload . ' onload="this.onload=null;this.rel=\'stylesheet\'">';

				return $preload . '<noscript>' . $tag . '</noscript>';
			},
			$html
		);

		return is_string( $result ) ? $result : $html;
	}
}
