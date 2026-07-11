<?php
/**
 * Dashboard tab: coverage estimate, CWV tips, conflicts and cache stack.
 *
 * @package UPO
 *
 * @var \UPO\Diagnostics\Diagnostics $diagnostics
 * @var \UPO\Support\Environment     $env
 * @var \UPO\Settings\Settings       $settings
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$upo_cov       = $diagnostics->coverage_estimate();
$upo_tips      = $diagnostics->cwv_recommendations();
$upo_conflicts = $diagnostics->conflict_scan();
$upo_stack     = $diagnostics->cache_stack();

$upo_score = (int) $upo_cov['score'];
$upo_grade = $upo_score >= 80 ? 'good' : ( $upo_score >= 50 ? 'ok' : 'poor' );

$upo_lcp_set = '' !== trim( (string) $settings->get( 'preload_lcp_image', '' ) );
?>
<div class="upo-card upo-card--cta">
	<div class="upo-cta__text">
		<h3><span class="dashicons dashicons-superhero-alt"></span> <?php esc_html_e( 'One-click optimization', 'ultimate-performance-optimizer' ); ?></h3>
		<p><?php esc_html_e( 'Apply the recommended safe, high-impact profile in a single step: defer &amp; delay third-party JavaScript (Analytics, GTM, Pixel, Hotjar, Clarity), auto-preload your LCP image with high priority, force font-display: swap, clean the document head and tune the Heartbeat API.', 'ultimate-performance-optimizer' ); ?></p>
		<p class="upo-muted"><?php esc_html_e( 'Risky, site-specific options (async CSS delivery, WooCommerce/Elementor asset unloading, CDN) are left for you to enable and test individually.', 'ultimate-performance-optimizer' ); ?></p>
	</div>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="upo-cta__action">
		<?php wp_nonce_field( 'upo_auto_optimize' ); ?>
		<input type="hidden" name="action" value="upo_auto_optimize">
		<button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Auto-Optimize now', 'ultimate-performance-optimizer' ); ?></button>
	</form>
</div>

<?php if ( ! $upo_lcp_set ) : ?>
	<div class="upo-callout upo-callout--info">
		<p>
			<strong><?php esc_html_e( 'Best LCP result:', 'ultimate-performance-optimizer' ); ?></strong>
			<?php
			printf(
				/* translators: %s: URL to the Fonts settings tab. */
				wp_kses(
					__( 'If your hero is a page-builder background image (Beaver Builder / Elementor), paste its exact URL under <a href="%s">Fonts → Preload LCP image</a>. That guarantees the browser prioritises the right resource even when it lives in an external CSS file.', 'ultimate-performance-optimizer' ),
					array( 'a' => array( 'href' => array() ) )
				),
				esc_url( add_query_arg( array( 'page' => \UPO\Admin\Admin::SLUG, 'tab' => 'fonts' ), admin_url( 'admin.php' ) ) )
			);
			?>
		</p>
	</div>
<?php endif; ?>

<div class="upo-grid upo-grid--dashboard">

	<div class="upo-card upo-card--score">
		<h3><?php esc_html_e( 'Optimization coverage', 'ultimate-performance-optimizer' ); ?></h3>
		<div class="upo-gauge upo-gauge--<?php echo esc_attr( $upo_grade ); ?>" id="upo-score-gauge" data-score="<?php echo esc_attr( (string) $upo_score ); ?>">
			<span class="upo-gauge__value"><?php echo esc_html( (string) $upo_score ); ?></span>
			<span class="upo-gauge__unit">/100</span>
		</div>
		<p class="upo-muted upo-card--score__note">
			<?php esc_html_e( 'An estimate of how many safe, high-impact optimizations are active here and in your stack. It is NOT a Lighthouse/PageSpeed score — always test with Google PageSpeed Insights.', 'ultimate-performance-optimizer' ); ?>
		</p>
		<ul class="upo-factors">
			<?php foreach ( $upo_cov['factors'] as $upo_factor ) : ?>
				<li class="upo-factor upo-factor--<?php echo ! empty( $upo_factor['on'] ) ? 'on' : 'off'; ?>">
					<span class="dashicons <?php echo ! empty( $upo_factor['on'] ) ? 'dashicons-yes-alt' : 'dashicons-marker'; ?>"></span>
					<?php echo esc_html( (string) $upo_factor['label'] ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<div class="upo-card">
		<h3><?php esc_html_e( 'Core Web Vitals suggestions', 'ultimate-performance-optimizer' ); ?></h3>
		<?php if ( array() === $upo_tips ) : ?>
			<p class="upo-good-msg"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Great — the key safe optimizations are already in place.', 'ultimate-performance-optimizer' ); ?></p>
		<?php else : ?>
			<ul class="upo-tips">
				<?php foreach ( $upo_tips as $upo_tip ) : ?>
					<li>
						<span class="upo-metric-badge"><?php echo esc_html( (string) $upo_tip['metric'] ); ?></span>
						<?php echo esc_html( (string) $upo_tip['text'] ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<div class="upo-card">
		<h3><?php esc_html_e( 'Detected stack', 'ultimate-performance-optimizer' ); ?></h3>
		<div class="upo-status-grid">
			<?php
			$upo_stack_map = array(
				'page_cache'   => __( 'Page cache', 'ultimate-performance-optimizer' ),
				'object_cache' => __( 'Object cache', 'ultimate-performance-optimizer' ),
				'redis'        => __( 'Redis', 'ultimate-performance-optimizer' ),
				'memcached'    => __( 'Memcached', 'ultimate-performance-optimizer' ),
				'cloudflare'   => __( 'Cloudflare', 'ultimate-performance-optimizer' ),
				'litespeed'    => __( 'LiteSpeed', 'ultimate-performance-optimizer' ),
				'gzip'         => __( 'Compression', 'ultimate-performance-optimizer' ),
			);
			foreach ( $upo_stack_map as $upo_k => $upo_label ) :
				?>
				<div class="upo-status">
					<span class="upo-status__dot upo-status__dot--<?php echo ! empty( $upo_stack[ $upo_k ] ) ? 'on' : 'off'; ?>"></span>
					<span class="upo-status__label"><?php echo esc_html( $upo_label ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="upo-card">
		<h3><?php esc_html_e( 'Conflict detector', 'ultimate-performance-optimizer' ); ?></h3>
		<?php if ( array() === $upo_conflicts ) : ?>
			<p class="upo-good-msg"><span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'No overlapping performance plugins detected.', 'ultimate-performance-optimizer' ); ?></p>
		<?php else : ?>
			<div class="upo-callout upo-callout--warn">
				<p><?php esc_html_e( 'These active plugins overlap with features here. Enabling the same optimization in two places can cause double-processing or breakage — pick one owner per feature.', 'ultimate-performance-optimizer' ); ?></p>
			</div>
			<ul class="upo-list">
				<?php foreach ( $upo_conflicts as $upo_conflict ) : ?>
					<li>
						<strong><?php echo esc_html( (string) $upo_conflict['name'] ); ?></strong> —
						<span class="upo-muted"><?php echo esc_html( (string) $upo_conflict['overlap'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

</div>
