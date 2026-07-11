<?php
/**
 * LCP (Largest Contentful Paint) preloading.
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
 * Preloads the real Largest Contentful Paint resource with fetchpriority="high".
 *
 * Unlike plain <img> fetchpriority (which cannot touch CSS background images),
 * this module can preload:
 *
 *  1. One or more explicit URLs the site owner pastes in (most reliable).
 *  2. Auto-detected CSS background images used by hero rows built with Beaver
 *     Builder, Elementor, Divi, etc. — the common case where the LCP element is
 *     a <div> with a background image, not an <img>, so a normal fetchpriority
 *     hint can never fire.
 */
final class Lcp extends Abstract_Module {

	/**
	 * Image extensions we are willing to preload as the LCP.
	 */
	private const IMAGE_EXT = array( 'jpg', 'jpeg', 'png', 'webp', 'avif', 'gif' );

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'lcp';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function register(): void {
		$manual = $this->manual_urls();

		// Explicit URLs are printed early in <head> — no output buffer required.
		if ( array() !== $manual ) {
			add_action( 'wp_head', array( $this, 'print_manual_preloads' ), 1 );
		}

		// Auto-detection needs the rendered body, so it buffers the page. We only
		// pay that cost when auto mode is on AND no explicit URL was given.
		if ( $this->enabled( 'lcp_auto_preload' ) && array() === $manual ) {
			add_action( 'template_redirect', array( $this, 'start_buffer' ), 1 );
		}
	}

	/**
	 * Explicit LCP image URLs (one per line), sanitised.
	 *
	 * @return string[]
	 */
	private function manual_urls(): array {
		$raw = (string) $this->settings->get( 'preload_lcp_image', '' );
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return array();
		}
		$parts = preg_split( '/[\r\n]+/', $raw ) ?: array();
		$urls  = array();
		foreach ( $parts as $part ) {
			$part = trim( (string) $part );
			if ( '' !== $part ) {
				$urls[] = $part;
			}
		}
		return array_slice( array_values( array_unique( $urls ) ), 0, 3 );
	}

	/**
	 * Print preload tags for the explicitly configured LCP image(s).
	 *
	 * @return void
	 */
	public function print_manual_preloads(): void {
		if ( ! Helpers::is_frontend_optimizable() ) {
			return;
		}
		foreach ( $this->manual_urls() as $url ) {
			$url = esc_url( $url );
			if ( '' === $url ) {
				continue;
			}
			printf(
				'<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n",
				esc_url( $url )
			);
		}
	}

	/**
	 * Begin buffering the page so we can detect and preload the hero background.
	 *
	 * @return void
	 */
	public function start_buffer(): void {
		if ( ! Helpers::is_frontend_optimizable() ) {
			return;
		}
		ob_start( array( $this, 'inject_auto_preload' ) );
	}

	/**
	 * Scan the buffered HTML for the first usable background image and preload it.
	 *
	 * @param string $html Buffered HTML.
	 * @return string
	 */
	public function inject_auto_preload( $html ): string {
		$html = (string) $html;
		if ( '' === $html || false === stripos( $html, 'background' ) ) {
			return $html;
		}

		// A hero background lives near the top of the document; cap the scan so we
		// never walk a huge page on every uncached request.
		$region = substr( $html, 0, 200000 );

		$url = $this->first_background_image( $region );
		if ( '' === $url ) {
			return $html;
		}

		// Do not double-preload if something already prioritised this image.
		if ( $this->already_preloaded( $region, $url ) ) {
			return $html;
		}

		$tag = sprintf(
			'<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n",
			esc_url( $url )
		);

		if ( $this->enabled( 'debug_mode' ) ) {
			$tag .= "<!-- Organic Kratom USA Performance Optimizer: auto LCP preload -->\n";
		}

		// Inject as high in <head> as possible so the browser discovers it early.
		if ( preg_match( '/<head\b[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE ) ) {
			$pos  = (int) $m[0][1] + strlen( $m[0][0] );
			return substr( $html, 0, $pos ) . "\n" . $tag . substr( $html, $pos );
		}

		return $tag . $html;
	}

	/**
	 * Find the first background-image URL that is a local, real image file.
	 *
	 * Element-level inline styles (style="…background-image:url(…)") are a strong
	 * hero signal, so we check those first, then fall back to scanning <style>
	 * blocks in the region. Both element inline styles and CSS routinely encode
	 * the quotes inside url() as &quot; / &#34; / &#039;, so we decode entities
	 * and strip surrounding quotes before validating.
	 *
	 * @param string $html HTML region to scan.
	 * @return string Absolute URL, or '' if none found.
	 */
	private function first_background_image( string $html ): string {
		$sources = array();

		// 1. Element-level inline style attributes, in document order.
		if ( preg_match_all( '/\bstyle\s*=\s*("|\')(.*?)\1/is', $html, $sm ) ) {
			foreach ( $sm[2] as $style_value ) {
				$sources[] = (string) $style_value;
			}
		}

		// 2. Fallback: the whole region (covers <style> blocks and other CSS).
		$sources[] = $html;

		foreach ( $sources as $source ) {
			$url = $this->match_background_url( $source );
			if ( '' !== $url ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * Return the first local image URL declared as a background in a CSS chunk.
	 *
	 * @param string $css A style attribute value or a block of CSS/HTML.
	 * @return string Absolute URL, or '' if none.
	 */
	private function match_background_url( string $css ): string {
		if ( ! preg_match_all( '/background(?:-image)?\s*:\s*[^;{}]*?url\(\s*([^)]+?)\s*\)/i', $css, $matches ) ) {
			return '';
		}

		foreach ( $matches[1] as $raw ) {
			// Decode &quot;/&#34;/&#039; etc., then strip any surrounding quotes.
			$candidate = html_entity_decode( trim( (string) $raw ), ENT_QUOTES );
			$candidate = trim( $candidate, " \t\n\r\"'" );

			if ( '' === $candidate || 0 === stripos( $candidate, 'data:' ) ) {
				continue;
			}

			$absolute = $this->to_absolute_url( $candidate );
			if ( '' === $absolute || ! $this->is_image_url( $absolute ) ) {
				continue;
			}

			// Only preload images we serve — a third-party host would need its own
			// preconnect and rarely holds the LCP.
			if ( ! Helpers::is_local_url( $absolute ) ) {
				continue;
			}

			return $absolute;
		}

		return '';
	}

	/**
	 * Resolve a CSS url() value to an absolute URL we can preload.
	 *
	 * @param string $url Raw url() value.
	 * @return string Absolute URL, or '' if we cannot resolve it safely.
	 */
	private function to_absolute_url( string $url ): string {
		if ( preg_match( '#^https?://#i', $url ) ) {
			return $url;
		}
		if ( 0 === strpos( $url, '//' ) ) {
			return ( is_ssl() ? 'https:' : 'http:' ) . $url;
		}
		if ( 0 === strpos( $url, '/' ) ) {
			return home_url( $url );
		}
		// Relative paths (./ or bare) are ambiguous against the CSS file location.
		return '';
	}

	/**
	 * Whether a URL points at a raster image we would treat as an LCP candidate.
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	private function is_image_url( string $url ): bool {
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		$ext  = strtolower( (string) pathinfo( $path, PATHINFO_EXTENSION ) );
		return in_array( $ext, self::IMAGE_EXT, true );
	}

	/**
	 * Whether an image preload for this URL already exists in the head.
	 *
	 * @param string $html HTML region.
	 * @param string $url  Candidate URL.
	 * @return bool
	 */
	private function already_preloaded( string $html, string $url ): bool {
		if ( false === stripos( $html, 'rel="preload"' ) && false === stripos( $html, "rel='preload'" ) ) {
			return false;
		}
		// The path is enough to catch the same asset regardless of query string.
		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( '' === $path ) {
			return false;
		}
		return (bool) preg_match(
			'/<link\b[^>]*rel=["\']?preload["\']?[^>]*as=["\']?image["\']?[^>]*' . preg_quote( $path, '/' ) . '/i',
			$html
		) || (bool) preg_match(
			'/<link\b[^>]*' . preg_quote( $path, '/' ) . '[^>]*rel=["\']?preload["\']?[^>]*as=["\']?image["\']?/i',
			$html
		);
	}
}
