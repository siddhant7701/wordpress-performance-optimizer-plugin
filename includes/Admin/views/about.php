<?php
/**
 * About tab: philosophy, feature summary and honest limitations.
 *
 * @package UPO
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="upo-grid upo-grid--about">

	<div class="upo-card">
		<h3><?php esc_html_e( 'Our approach', 'ultimate-performance-optimizer' ); ?></h3>
		<p><?php esc_html_e( 'Organic Kratom USA Performance Optimizer favours real, safe optimizations over checkbox features. Every option that can break a site is off by default, clearly flagged, and guarded by compatibility checks for your theme, WooCommerce, Elementor and popular caching plugins.', 'ultimate-performance-optimizer' ); ?></p>
		<p><?php esc_html_e( 'When something cannot be done safely in PHP alone — such as true Critical CSS, reliable Unused CSS/JS removal, server-level Brotli, or WebP/AVIF conversion — we detect and report it instead of pretending to do it.', 'ultimate-performance-optimizer' ); ?></p>
	</div>

	<div class="upo-card">
		<h3><?php esc_html_e( 'What it optimizes', 'ultimate-performance-optimizer' ); ?></h3>
		<ul class="upo-list">
			<li><?php esc_html_e( 'LCP: true background-image / hero preload with fetchpriority, manual & auto detection, font-display swap', 'ultimate-performance-optimizer' ); ?></li>
			<li><?php esc_html_e( 'INP / TBT: defer and interaction-delay JavaScript, one-click third-party delay', 'ultimate-performance-optimizer' ); ?></li>
			<li><?php esc_html_e( 'Render-blocking: optional async CSS delivery with a noscript fallback', 'ultimate-performance-optimizer' ); ?></li>
			<li><?php esc_html_e( 'CLS: async decoding, native lazy loading, swap fonts', 'ultimate-performance-optimizer' ); ?></li>
			<li><?php esc_html_e( 'Fewer requests: head cleanup, conditional WooCommerce/Elementor assets', 'ultimate-performance-optimizer' ); ?></li>
			<li><?php esc_html_e( 'TTFB: heartbeat control, database cleanup, cache detection', 'ultimate-performance-optimizer' ); ?></li>
		</ul>
	</div>

	<div class="upo-card">
		<h3><?php esc_html_e( 'Author', 'ultimate-performance-optimizer' ); ?></h3>
		<p><strong><?php echo esc_html( UPO_AUTHOR ); ?></strong></p>
		<p class="upo-muted"><?php esc_html_e( 'Designed and built as a custom, one-click performance suite.', 'ultimate-performance-optimizer' ); ?></p>
		<p>
			<a class="button" href="<?php echo esc_url( UPO_AUTHOR_URL ); ?>" target="_blank" rel="noopener noreferrer">
				<span class="dashicons dashicons-admin-links" style="vertical-align: middle;"></span>
				<?php esc_html_e( 'Portfolio', 'ultimate-performance-optimizer' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( UPO_GITHUB_URL ); ?>" target="_blank" rel="noopener noreferrer">
				<span class="dashicons dashicons-editor-code" style="vertical-align: middle;"></span>
				<?php esc_html_e( 'GitHub', 'ultimate-performance-optimizer' ); ?>
			</a>
		</p>
	</div>

	<div class="upo-card">
		<h3><?php esc_html_e( 'Honest limitations', 'ultimate-performance-optimizer' ); ?></h3>
		<ul class="upo-list upo-list--muted">
			<li><?php esc_html_e( 'No full-page caching (use your host/server or a dedicated cache plugin).', 'ultimate-performance-optimizer' ); ?></li>
			<li><?php esc_html_e( 'No fake Critical/Unused CSS — reported, not generated.', 'ultimate-performance-optimizer' ); ?></li>
			<li><?php esc_html_e( 'Brotli and WebP/AVIF conversion are server-level; we detect availability only.', 'ultimate-performance-optimizer' ); ?></li>
		</ul>
	</div>

	<div class="upo-card">
		<h3><?php esc_html_e( 'Version', 'ultimate-performance-optimizer' ); ?></h3>
		<p><strong><?php echo esc_html( UPO_NAME . ' ' . UPO_VERSION ); ?></strong></p>
		<p class="upo-muted">
			<?php
			printf(
				/* translators: 1: PHP version, 2: WordPress required version. */
				esc_html__( 'Requires PHP %1$s+ and WordPress %2$s+. Multisite compatible.', 'ultimate-performance-optimizer' ),
				esc_html( UPO_MIN_PHP ),
				esc_html( UPO_MIN_WP )
			);
			?>
		</p>
	</div>

</div>
