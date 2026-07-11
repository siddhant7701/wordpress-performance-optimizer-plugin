<?php
/**
 * Frontend cleanup: emojis, head meta, embeds, XML-RPC, pingbacks, feeds.
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
 * Removes low-value markup and requests WordPress adds by default.
 *
 * Every action here is reversible and none of them change how content renders.
 */
final class Cleanup extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'cleanup';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function register(): void {
		if ( $this->enabled( 'remove_emojis' ) ) {
			$this->remove_emojis();
		}
		if ( $this->enabled( 'remove_generator' ) ) {
			remove_action( 'wp_head', 'wp_generator' );
			add_filter( 'the_generator', '__return_empty_string' );
		}
		if ( $this->enabled( 'remove_wlwmanifest' ) ) {
			remove_action( 'wp_head', 'wlwmanifest_link' );
		}
		if ( $this->enabled( 'remove_rsd' ) ) {
			remove_action( 'wp_head', 'rsd_link' );
		}
		if ( $this->enabled( 'remove_shortlink' ) ) {
			remove_action( 'wp_head', 'wp_shortlink_wp_head' );
			remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
		}
		if ( $this->enabled( 'remove_rest_links' ) ) {
			remove_action( 'wp_head', 'rest_output_link_wp_head' );
			remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		}
		if ( $this->enabled( 'remove_oembed_discovery' ) ) {
			$this->remove_oembed_discovery();
		}
		if ( $this->enabled( 'disable_wp_embed_js' ) ) {
			add_action( 'wp_footer', array( $this, 'deregister_wp_embed' ) );
		}
		if ( $this->enabled( 'remove_dashicons_frontend' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_remove_dashicons' ), 100 );
		}
		if ( $this->enabled( 'disable_jquery_migrate' ) ) {
			add_action( 'wp_default_scripts', array( $this, 'remove_jquery_migrate' ) );
		}
		if ( $this->enabled( 'remove_query_strings' ) ) {
			add_filter( 'style_loader_src', array( $this, 'strip_version_query' ), 15 );
			add_filter( 'script_loader_src', array( $this, 'strip_version_query' ), 15 );
		}
		if ( $this->enabled( 'disable_xmlrpc' ) ) {
			$this->disable_xmlrpc();
		}
		if ( $this->enabled( 'disable_self_pingbacks' ) ) {
			add_action( 'pre_ping', array( $this, 'block_self_pingbacks' ) );
		}
		if ( $this->enabled( 'disable_feeds' ) ) {
			$this->disable_feeds();
		}
	}

	/**
	 * Strip the emoji detection script, styles and related hooks.
	 *
	 * @return void
	 */
	private function remove_emojis(): void {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

		// Stop the emoji CDN DNS-prefetch hint.
		add_filter( 'emoji_svg_url', '__return_false' );
		add_filter( 'wp_resource_hints', array( $this, 'remove_emoji_dns_prefetch' ), 10, 2 );

		// Remove emoji from TinyMCE.
		add_filter( 'tiny_mce_plugins', static function ( $plugins ) {
			return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
		} );
	}

	/**
	 * Remove the s.w.org emoji DNS prefetch hint.
	 *
	 * @param array<int, mixed> $urls          Resource hint URLs.
	 * @param string            $relation_type Relation type.
	 * @return array<int, mixed>
	 */
	public function remove_emoji_dns_prefetch( $urls, $relation_type ): array {
		$urls = is_array( $urls ) ? $urls : array();
		if ( 'dns-prefetch' !== $relation_type ) {
			return $urls;
		}
		return array_filter( $urls, static function ( $url ): bool {
			$url = is_array( $url ) ? ( $url['href'] ?? '' ) : (string) $url;
			return false === strpos( $url, 's.w.org' );
		} );
	}

	/**
	 * Remove oEmbed discovery links while keeping oEmbed functionality intact.
	 *
	 * @return void
	 */
	private function remove_oembed_discovery(): void {
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
	}

	/**
	 * Dequeue wp-embed.min.js in the footer if nothing depends on it.
	 *
	 * @return void
	 */
	public function deregister_wp_embed(): void {
		if ( ! Helpers::is_frontend_optimizable() ) {
			return;
		}
		wp_deregister_script( 'wp-embed' );
	}

	/**
	 * Remove Dashicons on the frontend when it is safe to do so.
	 *
	 * We keep Dashicons whenever the admin bar is showing (it needs them).
	 *
	 * @return void
	 */
	public function maybe_remove_dashicons(): void {
		if ( is_user_logged_in() || is_admin_bar_showing() ) {
			return;
		}
		if ( ! Helpers::is_frontend_optimizable() ) {
			return;
		}
		wp_dequeue_style( 'dashicons' );
	}

	/**
	 * Remove jquery-migrate from the jQuery dependency chain.
	 *
	 * @param \WP_Scripts $scripts Scripts registry.
	 * @return void
	 */
	public function remove_jquery_migrate( $scripts ): void {
		if ( is_admin() || ! isset( $scripts->registered['jquery'] ) ) {
			return;
		}
		$jquery = $scripts->registered['jquery'];
		if ( ! empty( $jquery->deps ) ) {
			$jquery->deps = array_diff( $jquery->deps, array( 'jquery-migrate' ) );
		}
	}

	/**
	 * Remove the ?ver= query string from an asset URL.
	 *
	 * @param string $src Source URL.
	 * @return string
	 */
	public function strip_version_query( $src ): string {
		$src = (string) $src;
		if ( '' === $src || false === strpos( $src, 'ver=' ) ) {
			return $src;
		}
		// Only touch local assets; leave third-party/CDN cache-busting alone.
		if ( ! Helpers::is_local_url( $src ) ) {
			return $src;
		}
		return remove_query_arg( 'ver', $src );
	}

	/**
	 * Disable XML-RPC endpoints and pingback advertising.
	 *
	 * @return void
	 */
	private function disable_xmlrpc(): void {
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'pings_open', '__return_false', 9 );
		add_filter( 'wp_headers', static function ( $headers ) {
			if ( is_array( $headers ) ) {
				unset( $headers['X-Pingback'] );
			}
			return $headers;
		} );
		add_filter( 'xmlrpc_methods', static function ( $methods ) {
			if ( is_array( $methods ) ) {
				unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
			}
			return $methods;
		} );
	}

	/**
	 * Prevent the site from pinging its own URLs.
	 *
	 * @param string[] $links Links to be pinged (passed by reference by core).
	 * @return void
	 */
	public function block_self_pingbacks( &$links ): void {
		$home = home_url();
		foreach ( $links as $index => $link ) {
			if ( is_string( $link ) && 0 === strpos( $link, $home ) ) {
				unset( $links[ $index ] );
			}
		}
	}

	/**
	 * Redirect every feed endpoint to the homepage.
	 *
	 * @return void
	 */
	private function disable_feeds(): void {
		$handler = static function (): void {
			wp_safe_redirect( home_url(), 301 );
			exit;
		};
		foreach ( array( 'do_feed', 'do_feed_rdf', 'do_feed_rss', 'do_feed_rss2', 'do_feed_atom', 'do_feed_rss2_comments', 'do_feed_atom_comments' ) as $hook ) {
			add_action( $hook, $handler, 1 );
		}
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
	}
}
