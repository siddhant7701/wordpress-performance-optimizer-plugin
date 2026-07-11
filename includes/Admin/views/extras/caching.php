<?php
/**
 * Caching tab extras: detected stack + copy-ready server rules.
 *
 * @package UPO
 *
 * @var \UPO\Modules\Module_Manager $modules
 */

declare( strict_types=1 );

use UPO\Modules\Cache\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$upo_cache = $modules->get( 'cache' );
if ( ! $upo_cache instanceof Cache ) {
	return;
}

$upo_stack = $upo_cache->detect();
$upo_map   = array(
	'object_cache' => __( 'Persistent object cache', 'ultimate-performance-optimizer' ),
	'redis'        => __( 'Redis', 'ultimate-performance-optimizer' ),
	'memcached'    => __( 'Memcached', 'ultimate-performance-optimizer' ),
	'page_cache'   => __( 'Full-page cache plugin', 'ultimate-performance-optimizer' ),
	'litespeed'    => __( 'LiteSpeed', 'ultimate-performance-optimizer' ),
	'cloudflare'   => __( 'Cloudflare', 'ultimate-performance-optimizer' ),
	'gzip'         => __( 'Gzip compression', 'ultimate-performance-optimizer' ),
	'php_brotli'   => __( 'Brotli (PHP extension)', 'ultimate-performance-optimizer' ),
);
?>
<div class="upo-callout">
	<p><?php esc_html_e( 'This plugin intentionally does not implement full-page caching — that is a server/host responsibility. Below is what we detected, plus ready-to-use rules you can add to your server.', 'ultimate-performance-optimizer' ); ?></p>
</div>

<div class="upo-status-grid">
	<?php foreach ( $upo_map as $upo_key => $upo_label ) : ?>
		<div class="upo-status">
			<span class="upo-status__dot upo-status__dot--<?php echo ! empty( $upo_stack[ $upo_key ] ) ? 'on' : 'off'; ?>"></span>
			<span class="upo-status__label"><?php echo esc_html( $upo_label ); ?></span>
			<span class="upo-status__value">
				<?php echo ! empty( $upo_stack[ $upo_key ] ) ? esc_html__( 'Detected', 'ultimate-performance-optimizer' ) : esc_html__( 'Not detected', 'ultimate-performance-optimizer' ); ?>
			</span>
		</div>
	<?php endforeach; ?>
</div>

<details class="upo-details">
	<summary><?php esc_html_e( 'Apache / .htaccess caching rules', 'ultimate-performance-optimizer' ); ?></summary>
	<textarea class="upo-code" rows="16" readonly onclick="this.select()"><?php echo esc_textarea( $upo_cache->get_htaccess_rules() ); ?></textarea>
</details>

<details class="upo-details">
	<summary><?php esc_html_e( 'Nginx caching rules', 'ultimate-performance-optimizer' ); ?></summary>
	<textarea class="upo-code" rows="10" readonly onclick="this.select()"><?php echo esc_textarea( $upo_cache->get_nginx_rules() ); ?></textarea>
</details>
