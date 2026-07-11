<?php
/**
 * Diagnostics tab: server/PHP info, extensions, large plugins, alt scan.
 *
 * @package UPO
 *
 * @var \UPO\Diagnostics\Diagnostics $diagnostics
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$upo_server = $diagnostics->server_info();
$upo_exts   = $diagnostics->php_extensions();
$upo_big    = $diagnostics->large_plugins( 8 );

$upo_server_labels = array(
	'server_software'     => __( 'Server software', 'ultimate-performance-optimizer' ),
	'php_version'         => __( 'PHP version', 'ultimate-performance-optimizer' ),
	'mysql_version'       => __( 'MySQL version', 'ultimate-performance-optimizer' ),
	'wordpress_version'   => __( 'WordPress version', 'ultimate-performance-optimizer' ),
	'multisite'           => __( 'Multisite', 'ultimate-performance-optimizer' ),
	'https'               => __( 'HTTPS', 'ultimate-performance-optimizer' ),
	'php_memory_limit'    => __( 'PHP memory limit', 'ultimate-performance-optimizer' ),
	'wp_memory_limit'     => __( 'WP memory limit', 'ultimate-performance-optimizer' ),
	'max_execution_time'  => __( 'Max execution time', 'ultimate-performance-optimizer' ),
	'post_max_size'       => __( 'Post max size', 'ultimate-performance-optimizer' ),
	'upload_max_filesize' => __( 'Upload max filesize', 'ultimate-performance-optimizer' ),
	'max_input_vars'      => __( 'Max input vars', 'ultimate-performance-optimizer' ),
	'memory_usage'        => __( 'Current memory usage', 'ultimate-performance-optimizer' ),
	'memory_peak'         => __( 'Peak memory usage', 'ultimate-performance-optimizer' ),
);
?>
<div class="upo-grid upo-grid--diag">

	<div class="upo-card">
		<h3><?php esc_html_e( 'Server & WordPress', 'ultimate-performance-optimizer' ); ?></h3>
		<table class="upo-table upo-table--kv">
			<tbody>
				<?php foreach ( $upo_server_labels as $upo_k => $upo_label ) : ?>
					<tr>
						<th><?php echo esc_html( $upo_label ); ?></th>
						<td><?php echo esc_html( (string) ( $upo_server[ $upo_k ] ?? '' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="upo-card">
		<h3><?php esc_html_e( 'PHP extensions', 'ultimate-performance-optimizer' ); ?></h3>
		<div class="upo-status-grid">
			<?php foreach ( $upo_exts as $upo_ext => $upo_on ) : ?>
				<div class="upo-status">
					<span class="upo-status__dot upo-status__dot--<?php echo $upo_on ? 'on' : 'off'; ?>"></span>
					<span class="upo-status__label"><?php echo esc_html( strtoupper( (string) $upo_ext ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="upo-muted"><?php esc_html_e( 'OPcache and an image library (Imagick/GD) are the most impactful for backend performance and media handling.', 'ultimate-performance-optimizer' ); ?></p>
	</div>

	<div class="upo-card">
		<div class="upo-panel__head">
			<h3><?php esc_html_e( 'Largest active plugins', 'ultimate-performance-optimizer' ); ?></h3>
			<button type="button" class="button" id="upo-refresh-sizes"><?php esc_html_e( 'Recalculate', 'ultimate-performance-optimizer' ); ?></button>
		</div>
		<table class="upo-table" id="upo-plugin-sizes">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Plugin', 'ultimate-performance-optimizer' ); ?></th>
					<th class="upo-num"><?php esc_html_e( 'Size', 'ultimate-performance-optimizer' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $upo_big as $upo_row ) : ?>
					<tr>
						<td><?php echo esc_html( (string) $upo_row['name'] ); ?></td>
						<td class="upo-num"><?php echo esc_html( (string) $upo_row['size'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="upo-muted"><?php esc_html_e( 'Directory size on disk is a rough proxy — a large plugin is not necessarily slow, but it is worth reviewing what loads on every request.', 'ultimate-performance-optimizer' ); ?></p>
	</div>

	<div class="upo-card">
		<div class="upo-panel__head">
			<h3><?php esc_html_e( 'Images missing alt text', 'ultimate-performance-optimizer' ); ?></h3>
			<button type="button" class="button" id="upo-scan-alt"><?php esc_html_e( 'Scan content', 'ultimate-performance-optimizer' ); ?></button>
		</div>
		<div id="upo-alt-results">
			<p class="upo-muted"><?php esc_html_e( 'Scan your latest posts and pages for images without alt attributes (accessibility & SEO).', 'ultimate-performance-optimizer' ); ?></p>
		</div>
	</div>

</div>
