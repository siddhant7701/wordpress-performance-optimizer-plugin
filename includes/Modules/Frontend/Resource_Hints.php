<?php
/**
 * Resource hints: preconnect, dns-prefetch and preload.
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
 * Emits browser resource hints to speed up connection setup and preload the
 * most important assets (fonts, LCP image).
 */
final class Resource_Hints extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'resource_hints';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'wp_resource_hints', array( $this, 'add_hints' ), 10, 2 );
		add_action( 'wp_head', array( $this, 'print_preloads' ), 1 );
	}

	/**
	 * Merge user preconnect / dns-prefetch hosts into core's list.
	 *
	 * @param array<int, mixed> $urls          Hint URLs.
	 * @param string            $relation_type Relation type.
	 * @return array<int, mixed>
	 */
	public function add_hints( $urls, $relation_type ): array {
		$urls = is_array( $urls ) ? $urls : array();

		if ( ! Helpers::is_frontend_optimizable() ) {
			return $urls;
		}

		if ( 'preconnect' === $relation_type ) {
			foreach ( $this->settings->get_lines( 'preconnect_hosts' ) as $host ) {
				$host = esc_url_raw( $host );
				if ( '' !== $host ) {
					$urls[] = array(
						'href'        => $host,
						'crossorigin' => 'anonymous',
					);
				}
			}
		}

		if ( 'dns-prefetch' === $relation_type ) {
			foreach ( $this->settings->get_lines( 'dns_prefetch_hosts' ) as $host ) {
				$host = esc_url_raw( $host );
				if ( '' !== $host ) {
					$urls[] = $host;
				}
			}
		}

		return $urls;
	}

	/**
	 * Print preload tags for fonts.
	 *
	 * The LCP image preload is intentionally handled by the dedicated Lcp module,
	 * which also supports CSS background heroes and auto-detection.
	 *
	 * @return void
	 */
	public function print_preloads(): void {
		if ( ! Helpers::is_frontend_optimizable() ) {
			return;
		}

		// Font preloads (crossorigin required for fonts).
		foreach ( $this->settings->get_lines( 'fonts_preload' ) as $url ) {
			$url = esc_url( $url );
			if ( '' === $url ) {
				continue;
			}
			printf(
				'<link rel="preload" href="%s" as="font" type="%s" crossorigin>' . "\n",
				esc_url( $url ),
				esc_attr( $this->font_mime( $url ) )
			);
		}
	}

	/**
	 * Guess a font MIME type from its extension.
	 *
	 * @param string $url Font URL.
	 * @return string
	 */
	private function font_mime( string $url ): string {
		$ext = strtolower( (string) pathinfo( (string) wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
		$map = array(
			'woff2' => 'font/woff2',
			'woff'  => 'font/woff',
			'ttf'   => 'font/ttf',
			'otf'   => 'font/otf',
		);
		return $map[ $ext ] ?? 'font/woff2';
	}
}
