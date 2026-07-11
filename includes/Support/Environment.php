<?php
/**
 * Environment / compatibility detector.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects the surrounding stack: themes, page builders, SEO plugins, caching
 * plugins and object caches. Results are memoized per request.
 *
 * Modules consult this before applying an optimization so we never fight with
 * a plugin that already does the same job.
 */
final class Environment {

	/**
	 * Memoized boolean checks.
	 *
	 * @var array<string, bool>
	 */
	private array $memo = array();

	/**
	 * Is WooCommerce active?
	 *
	 * @return bool
	 */
	public function has_woocommerce(): bool {
		return $this->once( 'woocommerce', static fn(): bool => class_exists( 'WooCommerce' ) );
	}

	/**
	 * Is Elementor active?
	 *
	 * @return bool
	 */
	public function has_elementor(): bool {
		return $this->once( 'elementor', static fn(): bool => did_action( 'elementor/loaded' ) > 0 || defined( 'ELEMENTOR_VERSION' ) );
	}

	/**
	 * Is Yoast SEO active?
	 *
	 * @return bool
	 */
	public function has_yoast(): bool {
		return $this->once( 'yoast', static fn(): bool => defined( 'WPSEO_VERSION' ) );
	}

	/**
	 * Is Rank Math active?
	 *
	 * @return bool
	 */
	public function has_rankmath(): bool {
		return $this->once( 'rankmath', static fn(): bool => class_exists( 'RankMath' ) || defined( 'RANK_MATH_VERSION' ) );
	}

	/**
	 * Is WP Rocket active?
	 *
	 * @return bool
	 */
	public function has_wp_rocket(): bool {
		return $this->once( 'wp_rocket', static fn(): bool => defined( 'WP_ROCKET_VERSION' ) );
	}

	/**
	 * Is LiteSpeed Cache active?
	 *
	 * @return bool
	 */
	public function has_litespeed(): bool {
		return $this->once( 'litespeed', static function (): bool {
			return defined( 'LSCWP_V' ) || ( isset( $_SERVER['SERVER_SOFTWARE'] ) && false !== stripos( sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ), 'litespeed' ) );
		} );
	}

	/**
	 * Is any well-known full-page caching plugin active?
	 *
	 * @return bool
	 */
	public function has_page_cache_plugin(): bool {
		return $this->once( 'page_cache', function (): bool {
			return $this->has_wp_rocket()
				|| $this->has_litespeed()
				|| defined( 'WPCACHEHOME' )                       // WP Super Cache.
				|| defined( 'W3TC' )                              // W3 Total Cache.
				|| defined( 'WPFC_MAIN_PATH' )                    // WP Fastest Cache.
				|| class_exists( '\WpeCommon' )                   // WP Engine.
				|| defined( 'SG_CACHEPRESS_VERSION' )             // SiteGround.
				|| class_exists( '\Breeze_Admin' );               // Breeze.
		} );
	}

	/**
	 * Is the site likely behind Cloudflare?
	 *
	 * @return bool
	 */
	public function has_cloudflare(): bool {
		return $this->once( 'cloudflare', static function (): bool {
			if ( isset( $_SERVER['HTTP_CF_RAY'] ) || isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
				return true;
			}
			if ( isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
				return true;
			}
			$server = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
			return false !== stripos( $server, 'cloudflare' );
		} );
	}

	/**
	 * Is a persistent object cache (Redis/Memcached) in use?
	 *
	 * @return bool
	 */
	public function has_object_cache(): bool {
		return $this->once( 'object_cache', static fn(): bool => wp_using_ext_object_cache() );
	}

	/**
	 * Is Redis available/loaded?
	 *
	 * @return bool
	 */
	public function has_redis(): bool {
		return $this->once( 'redis', static function (): bool {
			return class_exists( 'Redis' )
				|| defined( 'WP_REDIS_HOST' )
				|| class_exists( '\Redis_Object_Cache' );
		} );
	}

	/**
	 * Is Memcached available/loaded?
	 *
	 * @return bool
	 */
	public function has_memcached(): bool {
		return $this->once( 'memcached', static fn(): bool => class_exists( 'Memcached' ) || class_exists( 'Memcache' ) );
	}

	/**
	 * Get the active theme's template (parent) slug in lowercase.
	 *
	 * @return string
	 */
	public function theme_slug(): string {
		$theme = wp_get_theme();
		return strtolower( (string) $theme->get_template() );
	}

	/**
	 * Return a friendly, known theme key if recognised, else 'other'.
	 *
	 * @return string
	 */
	public function known_theme(): string {
		$slug  = $this->theme_slug();
		$known = array(
			'generatepress'  => 'generatepress',
			'astra'          => 'astra',
			'kadence'        => 'kadence',
			'blocksy'        => 'blocksy',
			'hello-elementor' => 'hello-elementor',
			'twentytwentyone' => 'default',
			'twentytwentytwo' => 'default',
			'twentytwentythree' => 'default',
			'twentytwentyfour' => 'default',
			'twentytwentyfive' => 'default',
		);
		return $known[ $slug ] ?? 'other';
	}

	/**
	 * Is this a multisite install?
	 *
	 * @return bool
	 */
	public function is_multisite(): bool {
		return is_multisite();
	}

	/**
	 * Detected server software string.
	 *
	 * @return string
	 */
	public function server_software(): string {
		return isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
	}

	/**
	 * Whether Brotli output compression appears available at PHP level.
	 *
	 * We only report this; we never try to enable it ourselves because that is
	 * a server-level concern.
	 *
	 * @return bool
	 */
	public function php_brotli_available(): bool {
		return function_exists( 'brotli_compress' );
	}

	/**
	 * Whether gzip output compression is active for this request.
	 *
	 * @return bool
	 */
	public function gzip_active(): bool {
		return in_array( 'ob_gzhandler', array_map( 'strval', ob_list_handlers() ), true )
			|| ( function_exists( 'ini_get' ) && (bool) ini_get( 'zlib.output_compression' ) );
	}

	/**
	 * Run a callback once and memoize the boolean result.
	 *
	 * @param string   $key      Memo key.
	 * @param callable $callback Producer returning bool.
	 * @return bool
	 */
	private function once( string $key, callable $callback ): bool {
		if ( ! array_key_exists( $key, $this->memo ) ) {
			$this->memo[ $key ] = (bool) $callback();
		}
		return $this->memo[ $key ];
	}
}
