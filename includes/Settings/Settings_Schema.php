<?php
/**
 * Settings schema — the single source of truth for every option.
 *
 * The admin UI, the sanitizer and the modules all read from this registry,
 * so there is exactly one place to declare a new toggle.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides the full field registry grouped by admin tab.
 */
final class Settings_Schema {

	/**
	 * Field type constants.
	 */
	public const TYPE_TOGGLE   = 'toggle';
	public const TYPE_TEXT     = 'text';
	public const TYPE_TEXTAREA = 'textarea';
	public const TYPE_SELECT   = 'select';
	public const TYPE_NUMBER   = 'number';

	/**
	 * Cached, fully-built schema.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $cache = null;

	/**
	 * Return the entire schema keyed by tab id.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function all(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$schema = array(
			'frontend'    => self::frontend_fields(),
			'backend'     => self::backend_fields(),
			'woocommerce' => self::woocommerce_fields(),
			'elementor'   => self::elementor_fields(),
			'fonts'       => self::fonts_fields(),
			'images'      => self::images_fields(),
			'caching'     => self::caching_fields(),
			'javascript'  => self::javascript_fields(),
			'css'         => self::css_fields(),
			'database'    => self::database_fields(),
			'cdn'         => self::cdn_fields(),
			'advanced'    => self::advanced_fields(),
		);

		/**
		 * Filter the settings schema so add-ons can register fields.
		 *
		 * @param array $schema The schema keyed by tab id.
		 */
		self::$cache = (array) apply_filters( 'upo_settings_schema', $schema );

		return self::$cache;
	}

	/**
	 * Flatten the schema to a map of field id => field definition.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function flat(): array {
		$flat = array();
		foreach ( self::all() as $tab ) {
			foreach ( $tab['fields'] as $field ) {
				$flat[ $field['id'] ] = $field;
			}
		}
		return $flat;
	}

	/**
	 * Return the list of field ids belonging to a given tab.
	 *
	 * @param string $tab Tab id.
	 * @return string[]
	 */
	public static function field_ids_for_tab( string $tab ): array {
		$all = self::all();
		if ( ! isset( $all[ $tab ]['fields'] ) ) {
			return array();
		}
		return array_map(
			static fn( array $field ): string => (string) $field['id'],
			$all[ $tab ]['fields']
		);
	}

	/**
	 * Return a map of field id => default value.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		$defaults = array();
		foreach ( self::flat() as $id => $field ) {
			$defaults[ $id ] = $field['default'];
		}
		return $defaults;
	}

	/**
	 * Build a single field definition, applying sensible fallbacks.
	 *
	 * @param array<string, mixed> $field Partial field.
	 * @return array<string, mixed>
	 */
	private static function field( array $field ): array {
		return wp_parse_args(
			$field,
			array(
				'type'        => self::TYPE_TOGGLE,
				'default'     => false,
				'label'       => '',
				'description' => '',
				'section'     => '',
				'safe'        => true,
				'options'     => array(),
				'min'         => 0,
				'max'         => 0,
				'note'        => '',
			)
		);
	}

	/**
	 * Frontend optimization fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function frontend_fields(): array {
		return array(
			'title'  => __( 'Frontend Optimization', 'ultimate-performance-optimizer' ),
			'icon'   => 'dashicons-admin-appearance',
			'fields' => array(
				self::field( array(
					'id'          => 'remove_emojis',
					'label'       => __( 'Disable WordPress emojis', 'ultimate-performance-optimizer' ),
					'description' => __( 'Removes the emoji detection script and inline CSS. Native/system emojis still work.', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'wp_head',
				) ),
				self::field( array(
					'id'          => 'remove_generator',
					'label'       => __( 'Remove generator meta tag', 'ultimate-performance-optimizer' ),
					'description' => __( 'Hides the WordPress version from the page source.', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'wp_head',
				) ),
				self::field( array(
					'id'          => 'remove_wlwmanifest',
					'label'       => __( 'Remove Windows Live Writer manifest', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'wp_head',
				) ),
				self::field( array(
					'id'          => 'remove_rsd',
					'label'       => __( 'Remove RSD (Really Simple Discovery) link', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'wp_head',
				) ),
				self::field( array(
					'id'          => 'remove_shortlink',
					'label'       => __( 'Remove wp-shortlink', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'wp_head',
				) ),
				self::field( array(
					'id'          => 'remove_rest_links',
					'label'       => __( 'Remove REST API links from head', 'ultimate-performance-optimizer' ),
					'description' => __( 'Removes the <link rel="https://api.w.org"> tag. The REST API itself keeps working.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'note'        => __( 'Some editors/blocks discover the API via this tag. Leave off if unsure.', 'ultimate-performance-optimizer' ),
					'section'     => 'wp_head',
				) ),
				self::field( array(
					'id'          => 'remove_oembed_discovery',
					'label'       => __( 'Remove oEmbed discovery links', 'ultimate-performance-optimizer' ),
					'description' => __( 'Removes discovery <link> tags. Embedding your posts elsewhere still works.', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'wp_head',
				) ),
				self::field( array(
					'id'          => 'disable_wp_embed_js',
					'label'       => __( 'Disable wp-embed.min.js', 'ultimate-performance-optimizer' ),
					'description' => __( 'Dequeues the script that renders embeds of other WordPress sites in your content.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'section'     => 'assets',
				) ),
				self::field( array(
					'id'          => 'remove_dashicons_frontend',
					'label'       => __( 'Remove Dashicons for logged-out visitors', 'ultimate-performance-optimizer' ),
					'description' => __( 'Only unloads Dashicons on the frontend when no admin bar is shown and the theme does not depend on it.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'note'        => __( 'Some themes/plugins use Dashicons on the frontend. Verify your icons after enabling.', 'ultimate-performance-optimizer' ),
					'section'     => 'assets',
				) ),
				self::field( array(
					'id'          => 'disable_jquery_migrate',
					'label'       => __( 'Disable jQuery Migrate', 'ultimate-performance-optimizer' ),
					'description' => __( 'Removes the jquery-migrate helper. Modern themes rarely need it.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'note'        => __( 'Older themes/plugins may rely on deprecated jQuery APIs. Test thoroughly.', 'ultimate-performance-optimizer' ),
					'section'     => 'assets',
				) ),
				self::field( array(
					'id'          => 'remove_query_strings',
					'label'       => __( 'Remove version query strings from static assets', 'ultimate-performance-optimizer' ),
					'description' => __( 'Strips ?ver= from CSS/JS URLs. Some proxies cache these better without it.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'note'        => __( 'Removing the version can serve stale files after updates unless your host busts cache another way.', 'ultimate-performance-optimizer' ),
					'section'     => 'assets',
				) ),
				self::field( array(
					'id'          => 'disable_xmlrpc',
					'label'       => __( 'Disable XML-RPC', 'ultimate-performance-optimizer' ),
					'description' => __( 'Blocks xmlrpc.php requests and removes the pingback header.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'note'        => __( 'The Jetpack and WordPress mobile apps use XML-RPC. Disable only if you do not use them.', 'ultimate-performance-optimizer' ),
					'section'     => 'security',
				) ),
				self::field( array(
					'id'          => 'disable_self_pingbacks',
					'label'       => __( 'Disable self pingbacks', 'ultimate-performance-optimizer' ),
					'description' => __( 'Stops the site from pinging itself when you link between your own posts.', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'security',
				) ),
				self::field( array(
					'id'          => 'disable_feeds',
					'label'       => __( 'Disable RSS feeds', 'ultimate-performance-optimizer' ),
					'description' => __( 'Redirects all feed endpoints to the homepage.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'note'        => __( 'Breaks feed readers, podcast feeds and some email/marketing integrations.', 'ultimate-performance-optimizer' ),
					'section'     => 'feeds',
				) ),
			),
		);
	}

	/**
	 * Backend / admin optimization fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function backend_fields(): array {
		return array(
			'title'  => __( 'Backend Optimization', 'ultimate-performance-optimizer' ),
			'icon'   => 'dashicons-admin-tools',
			'fields' => array(
				self::field( array(
					'id'          => 'heartbeat_mode',
					'type'        => self::TYPE_SELECT,
					'label'       => __( 'Heartbeat API behaviour', 'ultimate-performance-optimizer' ),
					'description' => __( 'Controls how often WordPress polls admin-ajax.php.', 'ultimate-performance-optimizer' ),
					'default'     => 'optimize',
					'options'     => array(
						'default'  => __( 'Default (WordPress decides)', 'ultimate-performance-optimizer' ),
						'optimize' => __( 'Optimize (slow down to interval below)', 'ultimate-performance-optimizer' ),
						'disable'  => __( 'Disable everywhere except the post editor', 'ultimate-performance-optimizer' ),
					),
					'section'     => 'heartbeat',
				) ),
				self::field( array(
					'id'          => 'heartbeat_interval',
					'type'        => self::TYPE_NUMBER,
					'label'       => __( 'Heartbeat interval (seconds)', 'ultimate-performance-optimizer' ),
					'default'     => 60,
					'min'         => 15,
					'max'         => 300,
					'section'     => 'heartbeat',
				) ),
				self::field( array(
					'id'          => 'heartbeat_disable_frontend',
					'label'       => __( 'Disable Heartbeat on the frontend', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'heartbeat',
				) ),
				self::field( array(
					'id'          => 'limit_revisions',
					'label'       => __( 'Limit post revisions', 'ultimate-performance-optimizer' ),
					'description' => __( 'Caps the number of stored revisions per post to keep the database lean.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'section'     => 'revisions',
				) ),
				self::field( array(
					'id'          => 'revisions_to_keep',
					'type'        => self::TYPE_NUMBER,
					'label'       => __( 'Revisions to keep', 'ultimate-performance-optimizer' ),
					'default'     => 5,
					'min'         => 0,
					'max'         => 100,
					'section'     => 'revisions',
				) ),
				self::field( array(
					'id'          => 'autosave_interval',
					'type'        => self::TYPE_NUMBER,
					'label'       => __( 'Autosave interval (seconds)', 'ultimate-performance-optimizer' ),
					'description' => __( 'Larger values reduce admin-ajax traffic while editing. 0 keeps the default (60).', 'ultimate-performance-optimizer' ),
					'default'     => 0,
					'min'         => 0,
					'max'         => 600,
					'section'     => 'revisions',
				) ),
			),
		);
	}

	/**
	 * WooCommerce fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function woocommerce_fields(): array {
		return array(
			'title'  => __( 'WooCommerce', 'ultimate-performance-optimizer' ),
			'icon'   => 'dashicons-cart',
			'fields' => array(
				self::field( array(
					'id'          => 'woo_disable_cart_fragments',
					'label'       => __( 'Disable cart fragments on non-cart pages', 'ultimate-performance-optimizer' ),
					'description' => __( 'Stops the AJAX cart counter request on pages without a cart/mini-cart. Skipped automatically when a cart block/widget is detected.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'note'        => __( 'If your header mini-cart stops updating without a reload, turn this off.', 'ultimate-performance-optimizer' ),
					'section'     => 'assets',
				) ),
				self::field( array(
					'id'          => 'woo_unload_assets',
					'label'       => __( 'Unload WooCommerce assets on non-shop pages', 'ultimate-performance-optimizer' ),
					'description' => __( 'Dequeues WooCommerce CSS/JS on pages that are not shop, product, cart, checkout or account.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'note'        => __( 'Disable if you use WooCommerce shortcodes/blocks on regular pages.', 'ultimate-performance-optimizer' ),
					'section'     => 'assets',
				) ),
				self::field( array(
					'id'          => 'woo_disable_block_styles',
					'label'       => __( 'Remove WooCommerce block styles when unused', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'section'     => 'assets',
				) ),
				self::field( array(
					'id'          => 'woo_disable_password_meter',
					'label'       => __( 'Disable password strength meter off account pages', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'section'     => 'assets',
				) ),
				self::field( array(
					'id'          => 'woo_status_report',
					'type'        => self::TYPE_TOGGLE,
					'label'       => __( 'Show WooCommerce optimization tips', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'report',
				) ),
			),
		);
	}

	/**
	 * Elementor fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function elementor_fields(): array {
		return array(
			'title'  => __( 'Elementor', 'ultimate-performance-optimizer' ),
			'icon'   => 'dashicons-editor-kitchensink',
			'fields' => array(
				self::field( array(
					'id'          => 'elementor_disable_google_fonts',
					'label'       => __( 'Disable Elementor Google Fonts requests', 'ultimate-performance-optimizer' ),
					'description' => __( 'Prevents Elementor from calling fonts.googleapis.com. Use theme/system fonts or self-host instead.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'section'     => 'assets',
				) ),
				self::field( array(
					'id'          => 'elementor_disable_fa_eicons_guests',
					'label'       => __( 'Defer Elementor eicons for logged-out users', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'section'     => 'assets',
				) ),
				self::field( array(
					'id'          => 'elementor_recommend_experiments',
					'label'       => __( 'Show Elementor performance recommendations', 'ultimate-performance-optimizer' ),
					'description' => __( 'Reports Elementor experiments (Improved CSS Loading, Optimized DOM, Lazy Load) you should enable in Elementor itself.', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'report',
				) ),
			),
		);
	}

	/**
	 * Font fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function fonts_fields(): array {
		return array(
			'title'  => __( 'Fonts', 'ultimate-performance-optimizer' ),
			'icon'   => 'dashicons-editor-textcolor',
			'fields' => array(
				self::field( array(
					'id'          => 'fonts_display_swap',
					'label'       => __( 'Force font-display: swap on Google Fonts', 'ultimate-performance-optimizer' ),
					'description' => __( 'Adds &display=swap to Google Fonts URLs to eliminate invisible text (FOIT).', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'google_fonts',
				) ),
				self::field( array(
					'id'          => 'fonts_preconnect',
					'label'       => __( 'Preconnect to Google Fonts', 'ultimate-performance-optimizer' ),
					'description' => __( 'Adds preconnect hints for fonts.googleapis.com and fonts.gstatic.com when Google Fonts are used.', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'google_fonts',
				) ),
				self::field( array(
					'id'          => 'fonts_preload',
					'type'        => self::TYPE_TEXTAREA,
					'label'       => __( 'Preload fonts', 'ultimate-performance-optimizer' ),
					'description' => __( 'One font URL per line (woff2 recommended). These are preloaded with crossorigin.', 'ultimate-performance-optimizer' ),
					'default'     => '',
					'section'     => 'preload',
				) ),
				self::field( array(
					'id'          => 'preconnect_hosts',
					'type'        => self::TYPE_TEXTAREA,
					'label'       => __( 'Preconnect hosts', 'ultimate-performance-optimizer' ),
					'description' => __( 'One origin per line (e.g. https://cdn.example.com). Use for hosts that serve render-critical resources.', 'ultimate-performance-optimizer' ),
					'default'     => '',
					'section'     => 'hints',
				) ),
				self::field( array(
					'id'          => 'dns_prefetch_hosts',
					'type'        => self::TYPE_TEXTAREA,
					'label'       => __( 'DNS-prefetch hosts', 'ultimate-performance-optimizer' ),
					'description' => __( 'One origin per line. Cheaper than preconnect; good for non-critical third parties.', 'ultimate-performance-optimizer' ),
					'default'     => '',
					'section'     => 'hints',
				) ),
				self::field( array(
					'id'          => 'preload_lcp_image',
					'type'        => self::TYPE_TEXTAREA,
					'label'       => __( 'Preload LCP image', 'ultimate-performance-optimizer' ),
					'description' => __( 'Full URL of your above-the-fold hero/background image to preload with fetchpriority="high" (one per line, up to 3). This is the most reliable way to fix LCP — it works for CSS background heroes that a normal image hint cannot reach.', 'ultimate-performance-optimizer' ),
					'default'     => '',
					'section'     => 'hints',
				) ),
			),
		);
	}

	/**
	 * Image fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function images_fields(): array {
		return array(
			'title'  => __( 'Images', 'ultimate-performance-optimizer' ),
			'icon'   => 'dashicons-format-image',
			'fields' => array(
				self::field( array(
					'id'          => 'img_lazy_load',
					'label'       => __( 'Native lazy loading', 'ultimate-performance-optimizer' ),
					'description' => __( 'Ensures loading="lazy" on content images (WordPress default, reinforced and made filterable).', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'loading',
				) ),
				self::field( array(
					'id'          => 'img_async_decoding',
					'label'       => __( 'Add decoding="async" to images', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'loading',
				) ),
				self::field( array(
					'id'          => 'img_fetchpriority_lcp',
					'label'       => __( 'Add fetchpriority="high" to the first image', 'ultimate-performance-optimizer' ),
					'description' => __( 'Hints the browser to prioritise the likely LCP image (first large in-content image, excluded from lazy loading). Automatically stands down when an LCP preload below is configured.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'section'     => 'loading',
				) ),
				self::field( array(
					'id'          => 'lcp_auto_preload',
					'label'       => __( 'Auto-preload the LCP background image', 'ultimate-performance-optimizer' ),
					'description' => __( 'Detects the first hero/background image (Beaver Builder, Elementor, Divi rows, etc.) and preloads it with fetchpriority="high". This is what makes prioritisation work when your LCP is a &lt;div&gt; background rather than an &lt;img&gt;.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'note'        => __( 'For a builder background stored in an external CSS file, paste the exact image URL under Fonts → Preload LCP image for the most reliable result.', 'ultimate-performance-optimizer' ),
					'section'     => 'loading',
				) ),
				self::field( array(
					'id'          => 'lazy_youtube',
					'label'       => __( 'Lazy load YouTube (facade)', 'ultimate-performance-optimizer' ),
					'description' => __( 'Replaces YouTube iframes with a lightweight preview image that loads the real player on click.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'section'     => 'loading',
				) ),
				self::field( array(
					'id'          => 'img_missing_alt_report',
					'label'       => __( 'Report images missing alt text', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'report',
				) ),
			),
		);
	}

	/**
	 * Caching fields (mostly detection — real caching is server/host level).
	 *
	 * @return array<string, mixed>
	 */
	private static function caching_fields(): array {
		return array(
			'title'  => __( 'Caching', 'ultimate-performance-optimizer' ),
			'icon'   => 'dashicons-database',
			'fields' => array(
				self::field( array(
					'id'          => 'browser_cache_headers',
					'label'       => __( 'Send far-future cache headers for static assets', 'ultimate-performance-optimizer' ),
					'description' => __( 'Adds Cache-Control and Expires headers to static files served through PHP. Has no effect if your server already sets them.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'note'        => __( 'Only applies to requests that reach PHP. Server-level rules (Nginx/Apache/CDN) take precedence.', 'ultimate-performance-optimizer' ),
					'section'     => 'headers',
				) ),
			),
		);
	}

	/**
	 * JavaScript fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function javascript_fields(): array {
		return array(
			'title'  => __( 'JavaScript', 'ultimate-performance-optimizer' ),
			'icon'   => 'dashicons-media-code',
			'fields' => array(
				self::field( array(
					'id'          => 'js_defer',
					'label'       => __( 'Defer JavaScript (safe)', 'ultimate-performance-optimizer' ),
					'description' => __( 'Adds the defer attribute to enqueued scripts, preserving execution order. Inline and excluded scripts are untouched.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'section'     => 'defer',
				) ),
				self::field( array(
					'id'          => 'js_defer_exclude',
					'type'        => self::TYPE_TEXTAREA,
					'label'       => __( 'Defer exclusions', 'ultimate-performance-optimizer' ),
					'description' => __( 'Script handles or URL fragments to never defer (one per line).', 'ultimate-performance-optimizer' ),
					'default'     => "jquery-core\njquery\njquery-migrate",
					'section'     => 'defer',
				) ),
				self::field( array(
					'id'          => 'js_delay',
					'label'       => __( 'Delay JavaScript until user interaction', 'ultimate-performance-optimizer' ),
					'description' => __( 'Holds selected scripts until the first scroll, click, key press or touch. Dramatically lowers Total Blocking Time.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'section'     => 'delay',
				) ),
				self::field( array(
					'id'          => 'js_delay_third_party',
					'label'       => __( 'Delay known third-party scripts', 'ultimate-performance-optimizer' ),
					'description' => __( 'Automatically delays Google Analytics, Tag Manager, Facebook Pixel, Hotjar, Microsoft Clarity and common chat widgets.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'section'     => 'delay',
				) ),
				self::field( array(
					'id'          => 'js_delay_extra',
					'type'        => self::TYPE_TEXTAREA,
					'label'       => __( 'Additional delay keywords', 'ultimate-performance-optimizer' ),
					'description' => __( 'URL fragments of scripts to delay (one per line), e.g. cookieconsent, recaptcha.', 'ultimate-performance-optimizer' ),
					'default'     => '',
					'section'     => 'delay',
				) ),
				self::field( array(
					'id'          => 'js_delay_timeout',
					'type'        => self::TYPE_NUMBER,
					'label'       => __( 'Fallback timeout (seconds)', 'ultimate-performance-optimizer' ),
					'description' => __( 'Load delayed scripts after this many seconds even without interaction. 0 = only on interaction.', 'ultimate-performance-optimizer' ),
					'default'     => 6,
					'min'         => 0,
					'max'         => 30,
					'section'     => 'delay',
				) ),
			),
		);
	}

	/**
	 * CSS fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function css_fields(): array {
		return array(
			'title'  => __( 'CSS', 'ultimate-performance-optimizer' ),
			'icon'   => 'dashicons-editor-code',
			'fields' => array(
				self::field( array(
					'id'          => 'css_minify_inline',
					'label'       => __( 'Minify inline CSS', 'ultimate-performance-optimizer' ),
					'description' => __( 'Collapses whitespace and comments in <style> blocks output by the theme/plugins. Does not touch external files.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'section'     => 'minify',
				) ),
				self::field( array(
					'id'          => 'css_optimize_delivery',
					'label'       => __( 'Optimize CSS delivery (eliminate render-blocking)', 'ultimate-performance-optimizer' ),
					'description' => __( 'Loads stylesheets asynchronously (preload + onload swap with a &lt;noscript&gt; fallback) so they no longer block the first paint. Directly targets the "render-blocking requests" warning in PageSpeed.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'note'        => __( 'Powerful but can cause a brief flash of unstyled content. Enable it, test your key pages, then add any above-the-fold stylesheet to the exclusions below.', 'ultimate-performance-optimizer' ),
					'section'     => 'delivery',
				) ),
				self::field( array(
					'id'          => 'css_delivery_exclude',
					'type'        => self::TYPE_TEXTAREA,
					'label'       => __( 'CSS delivery exclusions', 'ultimate-performance-optimizer' ),
					'description' => __( 'Stylesheet URL fragments to keep render-blocking (one per line), e.g. your theme or page-builder layout CSS. Add href fragments of anything that flickers with the option on.', 'ultimate-performance-optimizer' ),
					'default'     => '',
					'section'     => 'delivery',
				) ),
			),
		);
	}

	/**
	 * Database fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function database_fields(): array {
		return array(
			'title'  => __( 'Database', 'ultimate-performance-optimizer' ),
			'icon'   => 'dashicons-database-view',
			'fields' => array(
				self::field( array(
					'id'          => 'db_schedule_cleanup',
					'label'       => __( 'Enable scheduled weekly cleanup', 'ultimate-performance-optimizer' ),
					'description' => __( 'Runs the enabled cleanup tasks below once per week via WP-Cron.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'section'     => 'schedule',
				) ),
				self::field( array(
					'id'          => 'db_clean_expired_transients',
					'label'       => __( 'Delete expired transients', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'tasks',
				) ),
				self::field( array(
					'id'          => 'db_clean_revisions',
					'label'       => __( 'Delete old post revisions', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'section'     => 'tasks',
				) ),
				self::field( array(
					'id'          => 'db_revisions_keep',
					'type'        => self::TYPE_NUMBER,
					'label'       => __( 'Revisions to keep per post when cleaning', 'ultimate-performance-optimizer' ),
					'default'     => 5,
					'min'         => 0,
					'max'         => 100,
					'section'     => 'tasks',
				) ),
				self::field( array(
					'id'          => 'db_clean_auto_drafts',
					'label'       => __( 'Delete auto-drafts', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'section'     => 'tasks',
				) ),
				self::field( array(
					'id'          => 'db_clean_trash_posts',
					'label'       => __( 'Delete trashed posts', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'section'     => 'tasks',
				) ),
				self::field( array(
					'id'          => 'db_clean_spam_comments',
					'label'       => __( 'Delete spam comments', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'section'     => 'tasks',
				) ),
				self::field( array(
					'id'          => 'db_clean_trash_comments',
					'label'       => __( 'Delete trashed comments', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'section'     => 'tasks',
				) ),
			),
		);
	}

	/**
	 * CDN fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function cdn_fields(): array {
		return array(
			'title'  => __( 'CDN', 'ultimate-performance-optimizer' ),
			'icon'   => 'dashicons-cloud',
			'fields' => array(
				self::field( array(
					'id'          => 'cdn_enable',
					'label'       => __( 'Enable CDN URL rewriting', 'ultimate-performance-optimizer' ),
					'description' => __( 'Rewrites static asset URLs (CSS, JS, images, fonts) to your CDN hostname.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'safe'        => false,
					'note'        => __( 'Make sure your CDN is already pulling from your origin before enabling.', 'ultimate-performance-optimizer' ),
					'section'     => 'cdn',
				) ),
				self::field( array(
					'id'          => 'cdn_provider',
					'type'        => self::TYPE_SELECT,
					'label'       => __( 'Provider', 'ultimate-performance-optimizer' ),
					'default'     => 'custom',
					'options'     => array(
						'custom'     => __( 'Custom / Other', 'ultimate-performance-optimizer' ),
						'cloudflare' => __( 'Cloudflare', 'ultimate-performance-optimizer' ),
						'bunnycdn'   => __( 'BunnyCDN', 'ultimate-performance-optimizer' ),
						'cloudfront' => __( 'Amazon CloudFront', 'ultimate-performance-optimizer' ),
						'keycdn'     => __( 'KeyCDN', 'ultimate-performance-optimizer' ),
					),
					'section'     => 'cdn',
				) ),
				self::field( array(
					'id'          => 'cdn_url',
					'type'        => self::TYPE_TEXT,
					'label'       => __( 'CDN hostname / URL', 'ultimate-performance-optimizer' ),
					'description' => __( 'e.g. https://cdn.example.com', 'ultimate-performance-optimizer' ),
					'default'     => '',
					'section'     => 'cdn',
				) ),
				self::field( array(
					'id'          => 'cdn_extensions',
					'type'        => self::TYPE_TEXT,
					'label'       => __( 'File extensions to rewrite', 'ultimate-performance-optimizer' ),
					'default'     => 'css,js,jpg,jpeg,png,gif,webp,avif,svg,woff,woff2,ttf,otf',
					'section'     => 'cdn',
				) ),
				self::field( array(
					'id'          => 'cdn_exclude',
					'type'        => self::TYPE_TEXTAREA,
					'label'       => __( 'Exclude URLs containing', 'ultimate-performance-optimizer' ),
					'description' => __( 'One fragment per line. Matching URLs are left on the origin.', 'ultimate-performance-optimizer' ),
					'default'     => 'wp-admin',
					'section'     => 'cdn',
				) ),
			),
		);
	}

	/**
	 * Advanced / global fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function advanced_fields(): array {
		return array(
			'title'  => __( 'Advanced', 'ultimate-performance-optimizer' ),
			'icon'   => 'dashicons-admin-settings',
			'fields' => array(
				self::field( array(
					'id'          => 'safe_mode',
					'label'       => __( 'Safe Mode', 'ultimate-performance-optimizer' ),
					'description' => __( 'Temporarily suspends every frontend optimization while keeping your settings. Use this to rule the plugin out when debugging.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'section'     => 'global',
				) ),
				self::field( array(
					'id'          => 'debug_mode',
					'label'       => __( 'Debug Mode', 'ultimate-performance-optimizer' ),
					'description' => __( 'Adds HTML comments marking optimizations applied on each page, and enables verbose logging.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'section'     => 'global',
				) ),
				self::field( array(
					'id'          => 'enable_logging',
					'label'       => __( 'Enable logging', 'ultimate-performance-optimizer' ),
					'default'     => true,
					'section'     => 'global',
				) ),
				self::field( array(
					'id'          => 'remove_data_on_uninstall',
					'label'       => __( 'Delete all plugin data on uninstall', 'ultimate-performance-optimizer' ),
					'description' => __( 'When enabled, removing the plugin also erases its settings and logs.', 'ultimate-performance-optimizer' ),
					'default'     => false,
					'section'     => 'global',
				) ),
			),
		);
	}
}
