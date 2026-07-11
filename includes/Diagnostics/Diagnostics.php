<?php
/**
 * Diagnostics: environment, conflicts, reports and a coverage estimate.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Diagnostics;

use UPO\Settings\Settings;
use UPO\Support\Environment;
use UPO\Support\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read-only inspection of the site. Nothing here changes site behaviour.
 */
final class Diagnostics {

	/**
	 * Settings gateway.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Environment detector.
	 *
	 * @var Environment
	 */
	private Environment $env;

	/**
	 * Constructor.
	 *
	 * @param Settings    $settings Settings gateway.
	 * @param Environment $env      Environment detector.
	 */
	public function __construct( Settings $settings, Environment $env ) {
		$this->settings = $settings;
		$this->env      = $env;
	}

	/**
	 * Collect server / PHP / WordPress information.
	 *
	 * @return array<string, string>
	 */
	public function server_info(): array {
		global $wp_version, $wpdb;

		return array(
			'server_software'     => $this->env->server_software() ?: __( 'Unknown', 'ultimate-performance-optimizer' ),
			'php_version'         => PHP_VERSION,
			'mysql_version'       => is_object( $wpdb ) ? (string) $wpdb->db_version() : __( 'Unknown', 'ultimate-performance-optimizer' ),
			'wordpress_version'   => (string) ( $wp_version ?? '' ),
			'multisite'           => $this->env->is_multisite() ? __( 'Yes', 'ultimate-performance-optimizer' ) : __( 'No', 'ultimate-performance-optimizer' ),
			'https'               => is_ssl() ? __( 'Yes', 'ultimate-performance-optimizer' ) : __( 'No', 'ultimate-performance-optimizer' ),
			'php_memory_limit'    => (string) ini_get( 'memory_limit' ),
			'wp_memory_limit'     => defined( 'WP_MEMORY_LIMIT' ) ? (string) WP_MEMORY_LIMIT : __( 'Default', 'ultimate-performance-optimizer' ),
			'max_execution_time'  => (string) ini_get( 'max_execution_time' ) . 's',
			'post_max_size'       => (string) ini_get( 'post_max_size' ),
			'upload_max_filesize' => (string) ini_get( 'upload_max_filesize' ),
			'max_input_vars'      => (string) ini_get( 'max_input_vars' ),
			'memory_usage'        => Helpers::format_bytes( memory_get_usage( true ) ),
			'memory_peak'         => Helpers::format_bytes( memory_get_peak_usage( true ) ),
		);
	}

	/**
	 * Relevant PHP extensions and their availability.
	 *
	 * @return array<string, bool>
	 */
	public function php_extensions(): array {
		return array(
			'opcache'  => function_exists( 'opcache_get_status' ) || ( function_exists( 'ini_get' ) && (bool) ini_get( 'opcache.enable' ) ),
			'imagick'  => extension_loaded( 'imagick' ),
			'gd'       => extension_loaded( 'gd' ),
			'curl'     => extension_loaded( 'curl' ),
			'zlib'     => extension_loaded( 'zlib' ),
			'brotli'   => function_exists( 'brotli_compress' ),
			'intl'     => extension_loaded( 'intl' ),
			'mbstring' => extension_loaded( 'mbstring' ),
		);
	}

	/**
	 * Detect the surrounding cache/CDN stack.
	 *
	 * @return array<string, bool>
	 */
	public function cache_stack(): array {
		return array(
			'object_cache' => $this->env->has_object_cache(),
			'redis'        => $this->env->has_redis(),
			'memcached'    => $this->env->has_memcached(),
			'page_cache'   => $this->env->has_page_cache_plugin(),
			'cloudflare'   => $this->env->has_cloudflare(),
			'litespeed'    => $this->env->has_litespeed(),
			'gzip'         => $this->env->gzip_active(),
		);
	}

	/**
	 * Detect other performance plugins whose features overlap with ours.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function conflict_scan(): array {
		$signatures = array(
			array(
				'name'    => 'WP Rocket',
				'active'  => defined( 'WP_ROCKET_VERSION' ),
				'overlap' => __( 'Caching, defer/delay JS, resource hints', 'ultimate-performance-optimizer' ),
			),
			array(
				'name'    => 'LiteSpeed Cache',
				'active'  => defined( 'LSCWP_V' ),
				'overlap' => __( 'Caching, minify, defer/delay JS, image optimization', 'ultimate-performance-optimizer' ),
			),
			array(
				'name'    => 'W3 Total Cache',
				'active'  => defined( 'W3TC' ),
				'overlap' => __( 'Caching, minify, CDN', 'ultimate-performance-optimizer' ),
			),
			array(
				'name'    => 'WP Super Cache',
				'active'  => defined( 'WPCACHEHOME' ),
				'overlap' => __( 'Page caching', 'ultimate-performance-optimizer' ),
			),
			array(
				'name'    => 'WP Fastest Cache',
				'active'  => defined( 'WPFC_MAIN_PATH' ),
				'overlap' => __( 'Caching, minify, defer JS', 'ultimate-performance-optimizer' ),
			),
			array(
				'name'    => 'Autoptimize',
				'active'  => defined( 'AUTOPTIMIZE_PLUGIN_VERSION' ) || class_exists( 'autoptimizeBase' ),
				'overlap' => __( 'CSS/JS aggregation & minify, defer JS', 'ultimate-performance-optimizer' ),
			),
			array(
				'name'    => 'Perfmatters',
				'active'  => defined( 'PERFMATTERS_VERSION' ),
				'overlap' => __( 'Script manager, defer/delay JS, cleanup', 'ultimate-performance-optimizer' ),
			),
			array(
				'name'    => 'SG Optimizer',
				'active'  => defined( 'SiteGround_Optimizer\VERSION' ) || defined( 'SG_CACHEPRESS_VERSION' ),
				'overlap' => __( 'Caching, minify, defer JS, image optimization', 'ultimate-performance-optimizer' ),
			),
			array(
				'name'    => 'Async JavaScript',
				'active'  => class_exists( 'Async_JavaScript' ),
				'overlap' => __( 'Defer/async JavaScript', 'ultimate-performance-optimizer' ),
			),
		);

		$found = array();
		foreach ( $signatures as $sig ) {
			if ( $sig['active'] ) {
				$found[] = array(
					'name'    => (string) $sig['name'],
					'overlap' => (string) $sig['overlap'],
				);
			}
		}
		return $found;
	}

	/**
	 * Sizes of active plugin directories (cached for 12 hours).
	 *
	 * @param int $limit Number of largest plugins to return.
	 * @return array<int, array<string, string|int>>
	 */
	public function large_plugins( int $limit = 8 ): array {
		$cached = get_transient( 'upo_plugin_sizes' );
		if ( is_array( $cached ) ) {
			return array_slice( $cached, 0, $limit );
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all    = get_plugins();
		$active = (array) get_option( 'active_plugins', array() );
		$rows   = array();

		foreach ( $all as $file => $data ) {
			if ( ! in_array( $file, $active, true ) ) {
				continue;
			}
			$dir  = WP_PLUGIN_DIR . '/' . dirname( $file );
			$size = ( '.' === dirname( $file ) ) ? ( is_file( WP_PLUGIN_DIR . '/' . $file ) ? (int) filesize( WP_PLUGIN_DIR . '/' . $file ) : 0 ) : $this->dir_size( $dir );
			$rows[] = array(
				'name'  => (string) ( $data['Name'] ?? $file ),
				'bytes' => $size,
				'size'  => Helpers::format_bytes( $size ),
			);
		}

		usort( $rows, static fn( $a, $b ): int => $b['bytes'] <=> $a['bytes'] );

		set_transient( 'upo_plugin_sizes', $rows, 12 * HOUR_IN_SECONDS );

		return array_slice( $rows, 0, $limit );
	}

	/**
	 * Recursively compute a directory size, guarding against huge trees.
	 *
	 * @param string $dir Directory path.
	 * @return int Bytes.
	 */
	private function dir_size( string $dir ): int {
		if ( ! is_dir( $dir ) ) {
			return 0;
		}
		$size  = 0;
		$count = 0;
		try {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS )
			);
			foreach ( $iterator as $file ) {
				if ( $file->isFile() ) {
					$size += (int) $file->getSize();
				}
				// Safety valve for pathological plugin trees.
				if ( ++$count > 20000 ) {
					break;
				}
			}
		} catch ( \Throwable $e ) {
			return $size;
		}
		return $size;
	}

	/**
	 * Scan recent published posts for images missing alt text.
	 *
	 * @param int $limit Max posts to scan.
	 * @return array<int, array<string, mixed>>
	 */
	public function images_missing_alt( int $limit = 50 ): array {
		$query = new \WP_Query(
			array(
				'post_type'      => array( 'post', 'page' ),
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'no_found_rows'  => true,
				'fields'         => 'ids',
			)
		);

		$rows = array();
		foreach ( $query->posts as $post_id ) {
			$content = (string) get_post_field( 'post_content', (int) $post_id );
			if ( false === stripos( $content, '<img' ) ) {
				continue;
			}
			if ( ! preg_match_all( '/<img\b[^>]*>/i', $content, $imgs ) ) {
				continue;
			}
			$missing = 0;
			foreach ( $imgs[0] as $img ) {
				if ( ! preg_match( '/\salt\s*=\s*(["\'])(.*?)\1/i', $img, $m ) || '' === trim( $m[2] ) ) {
					$missing++;
				}
			}
			if ( $missing > 0 ) {
				$rows[] = array(
					'id'      => (int) $post_id,
					'title'   => get_the_title( (int) $post_id ),
					'edit'    => (string) get_edit_post_link( (int) $post_id, 'raw' ),
					'missing' => $missing,
				);
			}
		}
		return $rows;
	}

	/**
	 * Produce an honest optimization-coverage estimate (0-100).
	 *
	 * This is NOT a Lighthouse or PageSpeed score. It reflects how many safe,
	 * high-impact optimizations are active in this plugin and the surrounding
	 * stack. Use Google PageSpeed Insights for real field/lab scores.
	 *
	 * @return array{score:int, max:int, factors:array<int, array<string, mixed>>}
	 */
	public function coverage_estimate(): array {
		$factors = array();

		$add = static function ( string $label, bool $on, int $weight ) use ( &$factors ): void {
			$factors[] = array(
				'label'  => $label,
				'on'     => $on,
				'weight' => $weight,
			);
		};

		$add( __( 'Persistent object cache (Redis/Memcached)', 'ultimate-performance-optimizer' ), $this->env->has_object_cache(), 12 );
		$add( __( 'Full-page cache present', 'ultimate-performance-optimizer' ), $this->env->has_page_cache_plugin(), 12 );
		$add( __( 'Gzip/Brotli compression active', 'ultimate-performance-optimizer' ), $this->env->gzip_active(), 8 );
		$add( __( 'CDN in front (Cloudflare/other)', 'ultimate-performance-optimizer' ), $this->env->has_cloudflare() || $this->settings->is_enabled( 'cdn_enable' ), 8 );
		$add( __( 'Delay JavaScript (interaction)', 'ultimate-performance-optimizer' ), $this->settings->is_enabled( 'js_delay' ) || $this->settings->is_enabled( 'js_delay_third_party' ), 12 );
		$add( __( 'Defer JavaScript', 'ultimate-performance-optimizer' ), $this->settings->is_enabled( 'js_defer' ), 8 );
		$add( __( 'Image lazy loading', 'ultimate-performance-optimizer' ), $this->settings->is_enabled( 'img_lazy_load' ), 6 );
		$add( __( 'Async image decoding', 'ultimate-performance-optimizer' ), $this->settings->is_enabled( 'img_async_decoding' ), 4 );
		$add( __( 'LCP image prioritised (preload / fetchpriority)', 'ultimate-performance-optimizer' ), $this->settings->is_enabled( 'img_fetchpriority_lcp' ) || $this->settings->is_enabled( 'lcp_auto_preload' ) || '' !== (string) $this->settings->get( 'preload_lcp_image', '' ), 8 );
		$add( __( 'Render-blocking CSS reduced', 'ultimate-performance-optimizer' ), $this->settings->is_enabled( 'css_optimize_delivery' ), 4 );
		$add( __( 'Font-display: swap', 'ultimate-performance-optimizer' ), $this->settings->is_enabled( 'fonts_display_swap' ), 4 );
		$add( __( 'Resource hints (preconnect/dns-prefetch)', 'ultimate-performance-optimizer' ), $this->settings->is_enabled( 'fonts_preconnect' ) || array() !== $this->settings->get_lines( 'preconnect_hosts' ), 4 );
		$add( __( 'wp_head cleanup (emojis, meta)', 'ultimate-performance-optimizer' ), $this->settings->is_enabled( 'remove_emojis' ), 4 );
		$add( __( 'Heartbeat optimized', 'ultimate-performance-optimizer' ), 'default' !== (string) $this->settings->get( 'heartbeat_mode', 'optimize' ), 4 );

		$max   = array_sum( array_column( $factors, 'weight' ) );
		$score = 0;
		foreach ( $factors as $factor ) {
			if ( $factor['on'] ) {
				$score += (int) $factor['weight'];
			}
		}

		$normalized = $max > 0 ? (int) round( $score / $max * 100 ) : 0;

		return array(
			'score'   => $normalized,
			'max'     => 100,
			'factors' => $factors,
		);
	}

	/**
	 * Core Web Vitals oriented recommendations based on current state.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function cwv_recommendations(): array {
		$tips = array();

		$tip = static function ( string $metric, string $text ) use ( &$tips ): void {
			$tips[] = array(
				'metric' => $metric,
				'text'   => $text,
			);
		};

		if ( ! $this->settings->is_enabled( 'img_fetchpriority_lcp' ) && ! $this->settings->is_enabled( 'lcp_auto_preload' ) && '' === (string) $this->settings->get( 'preload_lcp_image', '' ) ) {
			$tip( 'LCP', __( 'Prioritise your hero/LCP image. If it is a page-builder background, turn on "Auto-preload the LCP background image" (Images tab) or paste its URL under Fonts.', 'ultimate-performance-optimizer' ) );
		}
		if ( ! $this->settings->is_enabled( 'js_delay' ) && ! $this->settings->is_enabled( 'js_delay_third_party' ) ) {
			$tip( 'INP/TBT', __( 'Delay third-party JavaScript (Analytics, Pixels, chat) to cut main-thread blocking.', 'ultimate-performance-optimizer' ) );
		}
		if ( ! $this->settings->is_enabled( 'css_optimize_delivery' ) ) {
			$tip( 'FCP/LCP', __( 'Render-blocking stylesheets delay first paint. Try "Optimize CSS delivery" (CSS tab), then test and exclude any that flicker.', 'ultimate-performance-optimizer' ) );
		}
		if ( $this->env->has_cloudflare() ) {
			$tip( 'TBT', __( 'Cloudflare Rocket Loader can add significant main-thread time and defer your scripts unpredictably. If TBT is high, disable it in Cloudflare → Speed → Optimization → Content Optimization.', 'ultimate-performance-optimizer' ) );
		}
		if ( ! $this->settings->is_enabled( 'fonts_display_swap' ) ) {
			$tip( 'CLS', __( 'Enable font-display: swap and set explicit image width/height to avoid layout shift.', 'ultimate-performance-optimizer' ) );
		}
		if ( ! $this->env->has_page_cache_plugin() && ! $this->env->has_object_cache() ) {
			$tip( 'TTFB', __( 'No cache detected. Add a page cache and/or a persistent object cache to lower TTFB.', 'ultimate-performance-optimizer' ) );
		}
		if ( ! $this->env->gzip_active() ) {
			$tip( 'FCP', __( 'Text compression (gzip/Brotli) does not appear active. Enable it at the server or CDN.', 'ultimate-performance-optimizer' ) );
		}

		return $tips;
	}
}
