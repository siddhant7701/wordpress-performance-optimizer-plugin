<?php
/**
 * Caching detection and safe static-asset headers.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Modules\Cache;

use UPO\Modules\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * We do NOT implement full-page caching — that belongs at the server/host or a
 * dedicated cache plugin, and doing it badly breaks sites. Instead this module
 * detects the existing cache stack and optionally sends far-future headers for
 * static files that happen to be served through PHP (never for HTML).
 */
final class Cache extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'cache';
	}

	/**
	 * Detection stays available even in Safe Mode.
	 *
	 * @return bool
	 */
	public function affects_frontend(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function register(): void {
		if ( $this->enabled( 'browser_cache_headers' ) ) {
			add_action( 'send_headers', array( $this, 'send_static_headers' ) );
		}
	}

	/**
	 * Send cache headers only for static file requests reaching PHP.
	 *
	 * HTML documents never match the extension list, so dynamic pages are
	 * never accidentally cached.
	 *
	 * @return void
	 */
	public function send_static_headers(): void {
		if ( is_admin() || headers_sent() ) {
			return;
		}
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		$ext = strtolower( (string) pathinfo( $uri, PATHINFO_EXTENSION ) );

		$static = array( 'css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'otf' );
		if ( ! in_array( $ext, $static, true ) ) {
			return;
		}

		$max_age = YEAR_IN_SECONDS;
		header( 'Cache-Control: public, max-age=' . $max_age . ', immutable' );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $max_age ) . ' GMT' );
	}

	/**
	 * Detected caching layers for reporting.
	 *
	 * @return array<string, bool>
	 */
	public function detect(): array {
		return array(
			'object_cache' => $this->env->has_object_cache(),
			'redis'        => $this->env->has_redis(),
			'memcached'    => $this->env->has_memcached(),
			'cloudflare'   => $this->env->has_cloudflare(),
			'litespeed'    => $this->env->has_litespeed(),
			'page_cache'   => $this->env->has_page_cache_plugin(),
			'wp_rocket'    => $this->env->has_wp_rocket(),
			'gzip'         => $this->env->gzip_active(),
			'php_brotli'   => $this->env->php_brotli_available(),
		);
	}

	/**
	 * Ready-to-use Apache/.htaccess caching rules for the admin to copy.
	 *
	 * @return string
	 */
	public function get_htaccess_rules(): string {
		return <<<'HTACCESS'
# BEGIN Organic Kratom USA Performance Optimizer
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType application/javascript "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType image/avif "access plus 1 year"
  ExpiresByType image/svg+xml "access plus 1 year"
  ExpiresByType font/woff2 "access plus 1 year"
</IfModule>
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/css application/javascript application/json image/svg+xml
</IfModule>
# END Organic Kratom USA Performance Optimizer
HTACCESS;
	}

	/**
	 * Ready-to-use Nginx caching rules for the admin to copy.
	 *
	 * @return string
	 */
	public function get_nginx_rules(): string {
		return <<<'NGINX'
# Organic Kratom USA Performance Optimizer — add inside your server { } block
location ~* \.(css|js|jpg|jpeg|png|gif|webp|avif|svg|ico|woff|woff2|ttf|otf)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    access_log off;
}
gzip on;
gzip_types text/css application/javascript application/json image/svg+xml;
NGINX;
	}
}
