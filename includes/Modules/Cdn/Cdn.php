<?php
/**
 * CDN URL rewriting.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Modules\Cdn;

use UPO\Modules\Abstract_Module;
use UPO\Support\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrites local static asset URLs to a CDN hostname.
 *
 * Uses targeted filters (enqueued CSS/JS, attachment URLs, srcset) rather than
 * a full-page buffer, so it never mangles inline HTML or admin URLs.
 */
final class Cdn extends Abstract_Module {

	/**
	 * Parsed CDN base URL (no trailing slash).
	 *
	 * @var string
	 */
	private string $cdn_base = '';

	/**
	 * Allowed file extensions.
	 *
	 * @var string[]
	 */
	private array $extensions = array();

	/**
	 * Exclusion fragments.
	 *
	 * @var string[]
	 */
	private array $exclusions = array();

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'cdn';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->enabled( 'cdn_enable' ) ) {
			return;
		}

		$url = esc_url_raw( trim( (string) $this->settings->get( 'cdn_url', '' ) ) );
		if ( '' === $url ) {
			$this->log->warning( 'CDN is enabled but no CDN URL is configured.', 'cdn' );
			return;
		}

		$this->cdn_base   = untrailingslashit( $url );
		$this->extensions = array_map( 'strtolower', array_filter( array_map( 'trim', explode( ',', (string) $this->settings->get( 'cdn_extensions', '' ) ) ) ) );
		$this->exclusions = array_merge( array( 'wp-login', 'wp-admin', '.php' ), $this->settings->get_lines( 'cdn_exclude' ) );

		add_filter( 'style_loader_src', array( $this, 'rewrite' ), 30 );
		add_filter( 'script_loader_src', array( $this, 'rewrite' ), 30 );
		add_filter( 'wp_get_attachment_url', array( $this, 'rewrite' ), 30 );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'rewrite_srcset' ), 30 );
		add_filter( 'stylesheet_directory_uri', array( $this, 'rewrite' ), 30 );
		add_filter( 'template_directory_uri', array( $this, 'rewrite' ), 30 );
	}

	/**
	 * Rewrite a single URL to the CDN host if eligible.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public function rewrite( $url ): string {
		$url = (string) $url;

		if ( '' === $url || is_admin() || ! Helpers::is_frontend_optimizable() ) {
			return $url;
		}
		if ( ! Helpers::is_local_url( $url ) ) {
			return $url;
		}
		if ( Helpers::contains_any( $url, $this->exclusions ) ) {
			return $url;
		}
		if ( array() !== $this->extensions ) {
			$ext = strtolower( (string) pathinfo( (string) wp_parse_url( $url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
			if ( '' !== $ext && ! in_array( $ext, $this->extensions, true ) ) {
				return $url;
			}
		}

		$home = home_url();

		// Absolute URL on our host.
		if ( 0 === strpos( $url, $home ) ) {
			return $this->cdn_base . substr( $url, strlen( $home ) );
		}

		// Root-relative URL.
		if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
			return $this->cdn_base . $url;
		}

		return $url;
	}

	/**
	 * Rewrite each source URL in a srcset array.
	 *
	 * @param array<int, array<string, mixed>> $sources Srcset sources.
	 * @return array<int, array<string, mixed>>
	 */
	public function rewrite_srcset( $sources ): array {
		if ( ! is_array( $sources ) ) {
			return array();
		}
		foreach ( $sources as $key => $source ) {
			if ( isset( $source['url'] ) ) {
				$sources[ $key ]['url'] = $this->rewrite( (string) $source['url'] );
			}
		}
		return $sources;
	}
}
