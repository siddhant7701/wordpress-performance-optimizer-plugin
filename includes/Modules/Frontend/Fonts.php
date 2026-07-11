<?php
/**
 * Google Fonts optimization.
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
 * Improves Google Fonts loading: forces font-display:swap and adds preconnect
 * hints. True local self-hosting is intentionally not faked — see the Fonts
 * tab notice for guidance.
 */
final class Fonts extends Abstract_Module {

	/**
	 * Whether any Google Fonts stylesheet was seen this request.
	 *
	 * @var bool
	 */
	private bool $google_fonts_detected = false;

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'fonts';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function register(): void {
		if ( $this->enabled( 'fonts_display_swap' ) ) {
			add_filter( 'style_loader_src', array( $this, 'force_display_swap' ), 20, 2 );
		}
		if ( $this->enabled( 'fonts_preconnect' ) ) {
			add_filter( 'wp_resource_hints', array( $this, 'preconnect_google_fonts' ), 10, 2 );
		}
	}

	/**
	 * Append &display=swap to Google Fonts stylesheet URLs.
	 *
	 * @param string $src    Stylesheet URL.
	 * @param string $handle Style handle.
	 * @return string
	 */
	public function force_display_swap( $src, $handle ): string {
		unset( $handle );
		$src = (string) $src;

		if ( false === strpos( $src, 'fonts.googleapis.com' ) ) {
			return $src;
		}

		$this->google_fonts_detected = true;

		if ( false !== strpos( $src, 'display=' ) ) {
			return $src;
		}

		return add_query_arg( 'display', 'swap', $src );
	}

	/**
	 * Add preconnect hints for the Google Fonts hosts.
	 *
	 * We only add them when a Google Fonts stylesheet is actually enqueued, so
	 * we do not create wasted connections.
	 *
	 * @param array<int, mixed> $urls          Hint URLs.
	 * @param string            $relation_type Relation type.
	 * @return array<int, mixed>
	 */
	public function preconnect_google_fonts( $urls, $relation_type ): array {
		$urls = is_array( $urls ) ? $urls : array();

		if ( 'preconnect' !== $relation_type || ! Helpers::is_frontend_optimizable() ) {
			return $urls;
		}
		if ( ! $this->google_fonts_detected && ! $this->has_google_fonts_enqueued() ) {
			return $urls;
		}

		$urls[] = array(
			'href' => 'https://fonts.googleapis.com',
		);
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);

		return $urls;
	}

	/**
	 * Scan the enqueued styles for a Google Fonts stylesheet.
	 *
	 * @return bool
	 */
	private function has_google_fonts_enqueued(): bool {
		$styles = wp_styles();
		if ( ! $styles instanceof \WP_Styles ) {
			return false;
		}
		foreach ( $styles->registered as $style ) {
			if ( isset( $style->src ) && is_string( $style->src ) && false !== strpos( $style->src, 'fonts.googleapis.com' ) ) {
				return true;
			}
		}
		return false;
	}
}
