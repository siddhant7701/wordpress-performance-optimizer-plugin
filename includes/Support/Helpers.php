<?php
/**
 * Static helper utilities shared across modules.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stateless helpers. Nothing here writes to the database.
 */
final class Helpers {

	/**
	 * Whether the current request is a normal, optimizable frontend page view.
	 *
	 * We deliberately bail on admin, AJAX, REST, cron, feeds, the customizer,
	 * login/register, XML sitemaps, the block/Elementor editors and any
	 * requests that already sent headers.
	 *
	 * @return bool
	 */
	public static function is_frontend_optimizable(): bool {
		if ( is_admin() ) {
			return false;
		}
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return false;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return false;
		}
		if ( function_exists( 'is_feed' ) && is_feed() ) {
			return false;
		}
		if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
			return false;
		}
		if ( function_exists( 'is_embed' ) && is_embed() ) {
			return false;
		}
		if ( isset( $_GET['elementor-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}
		if ( self::is_page_builder_editor() ) {
			return false;
		}

		/**
		 * Allow site owners to force-disable frontend optimization for a request.
		 *
		 * @param bool $optimizable Whether the request is optimizable.
		 */
		return (bool) apply_filters( 'upo_is_frontend_optimizable', true );
	}

	/**
	 * Detect common page-builder editor / preview contexts.
	 *
	 * @return bool
	 */
	public static function is_page_builder_editor(): bool {
		$flags = array(
			'elementor-preview',
			'fl_builder',      // Beaver Builder.
			'et_fb',           // Divi.
			'vc_editable',     // WPBakery.
			'ct_builder',      // Oxygen.
			'brizy-edit',      // Brizy.
			'bricks',          // Bricks (uses ?bricks=run).
		);
		foreach ( $flags as $flag ) {
			if ( isset( $_GET[ $flag ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return true;
			}
		}
		return false;
	}

	/**
	 * Case-insensitive "does haystack contain any needle" check.
	 *
	 * @param string   $haystack Haystack.
	 * @param string[] $needles  Needles.
	 * @return bool
	 */
	public static function contains_any( string $haystack, array $needles ): bool {
		if ( '' === $haystack ) {
			return false;
		}
		$haystack = strtolower( $haystack );
		foreach ( $needles as $needle ) {
			$needle = strtolower( trim( (string) $needle ) );
			if ( '' !== $needle && false !== strpos( $haystack, $needle ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Format a byte count for display.
	 *
	 * @param int|float $bytes Bytes.
	 * @return string
	 */
	public static function format_bytes( $bytes ): string {
		$bytes = (float) $bytes;
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		$i     = 0;
		while ( $bytes >= 1024 && $i < count( $units ) - 1 ) {
			$bytes /= 1024;
			$i++;
		}
		return sprintf( '%s %s', number_format_i18n( $bytes, $i > 0 ? 1 : 0 ), $units[ $i ] );
	}

	/**
	 * Return the site's home host, memoized.
	 *
	 * @return string
	 */
	public static function home_host(): string {
		static $host = null;
		if ( null === $host ) {
			$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		}
		return $host;
	}

	/**
	 * Whether a URL points at the current site (relative or same host).
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	public static function is_local_url( string $url ): bool {
		if ( '' === $url ) {
			return false;
		}
		if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
			return true;
		}
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		return '' !== $host && strtolower( $host ) === strtolower( self::home_host() );
	}

	/**
	 * Minify a CSS string conservatively (safe transformations only).
	 *
	 * @param string $css CSS source.
	 * @return string
	 */
	public static function minify_css( string $css ): string {
		// Remove comments.
		$css = preg_replace( '#/\*(?!!).*?\*/#s', '', $css ) ?? $css;
		// Collapse whitespace.
		$css = preg_replace( '/\s+/', ' ', $css ) ?? $css;
		// Trim space around structural characters.
		$css = preg_replace( '/\s*([{}:;,>~+])\s*/', '$1', $css ) ?? $css;
		// Remove the last semicolon in a block and trailing spaces.
		$css = str_replace( array( ';}', ' {' ), array( '}', '{' ), $css );
		return trim( $css );
	}
}
