<?php
/**
 * Admin page header: title bar, status notices and tab navigation.
 *
 * @package UPO
 *
 * @var \UPO\Admin\Admin              $admin
 * @var array<string, array<string>>  $tabs
 * @var string                        $current
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$upo_statuses = array(
	'saved'        => array( 'success', __( 'Settings saved.', 'ultimate-performance-optimizer' ) ),
	'optimized'    => array( 'success', __( 'Recommended optimizations applied. Clear any page cache, then re-test with PageSpeed Insights.', 'ultimate-performance-optimizer' ) ),
	'imported'     => array( 'success', __( 'Settings imported.', 'ultimate-performance-optimizer' ) ),
	'reset'        => array( 'success', __( 'Settings reset to defaults.', 'ultimate-performance-optimizer' ) ),
	'logs_cleared' => array( 'success', __( 'Logs cleared.', 'ultimate-performance-optimizer' ) ),
	'error'        => array( 'error', __( 'Action could not be completed.', 'ultimate-performance-optimizer' ) ),
);
$upo_status   = isset( $_GET['upo_status'] ) ? sanitize_key( wp_unslash( $_GET['upo_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<div class="wrap upo-wrap">
	<div class="upo-topbar">
		<div class="upo-topbar__brand">
			<span class="dashicons dashicons-performance"></span>
			<div>
				<h1 class="upo-topbar__title"><?php esc_html_e( 'Organic Kratom USA Performance Optimizer', 'ultimate-performance-optimizer' ); ?></h1>
				<p class="upo-topbar__sub"><?php esc_html_e( 'Safe, modular Core Web Vitals optimization — real LCP preloading, defer/delay JS, async CSS.', 'ultimate-performance-optimizer' ); ?></p>
			</div>
		</div>
		<div class="upo-topbar__meta">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="upo-topbar__cta">
				<?php wp_nonce_field( 'upo_auto_optimize' ); ?>
				<input type="hidden" name="action" value="upo_auto_optimize">
				<button type="submit" class="upo-topbar__btn" title="<?php esc_attr_e( 'Apply the recommended safe optimization profile', 'ultimate-performance-optimizer' ); ?>">
					<span class="dashicons dashicons-superhero-alt"></span>
					<?php esc_html_e( 'Auto-Optimize', 'ultimate-performance-optimizer' ); ?>
				</button>
			</form>
			<span class="upo-chip"><?php echo esc_html( 'v' . UPO_VERSION ); ?></span>
			<?php if ( $settings->is_enabled( 'safe_mode' ) ) : ?>
				<span class="upo-chip upo-chip--warn"><?php esc_html_e( 'Safe Mode ON', 'ultimate-performance-optimizer' ); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( '' !== $upo_status && isset( $upo_statuses[ $upo_status ] ) ) : ?>
		<div class="upo-notice upo-notice--<?php echo esc_attr( $upo_statuses[ $upo_status ][0] ); ?>">
			<?php echo esc_html( $upo_statuses[ $upo_status ][1] ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $settings->is_enabled( 'safe_mode' ) ) : ?>
		<div class="upo-notice upo-notice--warn">
			<?php esc_html_e( 'Safe Mode is active — all frontend optimizations are temporarily suspended. Turn it off in the Advanced tab when you have finished testing.', 'ultimate-performance-optimizer' ); ?>
		</div>
	<?php endif; ?>

	<nav class="upo-nav" aria-label="<?php esc_attr_e( 'Performance settings sections', 'ultimate-performance-optimizer' ); ?>">
		<?php foreach ( $tabs as $upo_tab_id => $upo_tab ) : ?>
			<?php
			$upo_url = add_query_arg(
				array(
					'page' => \UPO\Admin\Admin::SLUG,
					'tab'  => $upo_tab_id,
				),
				admin_url( 'admin.php' )
			);
			?>
			<a
				href="<?php echo esc_url( $upo_url ); ?>"
				class="upo-nav__item<?php echo ( $upo_tab_id === $current ) ? ' is-active' : ''; ?>"
			>
				<span class="dashicons <?php echo esc_attr( $upo_tab['icon'] ); ?>"></span>
				<span class="upo-nav__label"><?php echo esc_html( $upo_tab['title'] ); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>
</div>
