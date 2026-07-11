<?php
/**
 * Tools tab: export / import / reset settings and safe/debug shortcuts.
 *
 * @package UPO
 *
 * @var \UPO\Settings\Settings $settings
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$upo_post = esc_url( admin_url( 'admin-post.php' ) );
?>
<div class="upo-grid upo-grid--tools">

	<div class="upo-card">
		<h3><?php esc_html_e( 'Export settings', 'ultimate-performance-optimizer' ); ?></h3>
		<p class="upo-muted"><?php esc_html_e( 'Download all plugin settings as a JSON file for backup or to copy to another site.', 'ultimate-performance-optimizer' ); ?></p>
		<form method="post" action="<?php echo $upo_post; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
			<input type="hidden" name="action" value="upo_tool">
			<input type="hidden" name="upo_action" value="export">
			<?php wp_nonce_field( 'upo_tool_export' ); ?>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Download JSON', 'ultimate-performance-optimizer' ); ?></button>
		</form>
	</div>

	<div class="upo-card">
		<h3><?php esc_html_e( 'Import settings', 'ultimate-performance-optimizer' ); ?></h3>
		<p class="upo-muted"><?php esc_html_e( 'Upload a previously exported JSON file. Existing settings will be replaced.', 'ultimate-performance-optimizer' ); ?></p>
		<form method="post" action="<?php echo $upo_post; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="upo_tool">
			<input type="hidden" name="upo_action" value="import">
			<?php wp_nonce_field( 'upo_tool_import' ); ?>
			<input type="file" name="upo_import_file" accept="application/json,.json" required>
			<button type="submit" class="button button-secondary"><?php esc_html_e( 'Import', 'ultimate-performance-optimizer' ); ?></button>
		</form>
	</div>

	<div class="upo-card">
		<h3><?php esc_html_e( 'Reset settings', 'ultimate-performance-optimizer' ); ?></h3>
		<p class="upo-muted"><?php esc_html_e( 'Restore every option to its safe default. This cannot be undone.', 'ultimate-performance-optimizer' ); ?></p>
		<form method="post" action="<?php echo $upo_post; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" class="upo-confirm">
			<input type="hidden" name="action" value="upo_tool">
			<input type="hidden" name="upo_action" value="reset">
			<?php wp_nonce_field( 'upo_tool_reset' ); ?>
			<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Reset to defaults', 'ultimate-performance-optimizer' ); ?></button>
		</form>
	</div>

	<div class="upo-card">
		<h3><?php esc_html_e( 'Safe & Debug mode', 'ultimate-performance-optimizer' ); ?></h3>
		<p class="upo-muted"><?php esc_html_e( 'Safe Mode suspends all frontend optimizations without losing settings — ideal for troubleshooting. Debug Mode adds inline markers and verbose logging. Both live in the Advanced tab.', 'ultimate-performance-optimizer' ); ?></p>
		<p>
			<strong><?php esc_html_e( 'Safe Mode:', 'ultimate-performance-optimizer' ); ?></strong>
			<?php echo $settings->is_enabled( 'safe_mode' ) ? esc_html__( 'ON', 'ultimate-performance-optimizer' ) : esc_html__( 'OFF', 'ultimate-performance-optimizer' ); ?>
			&nbsp;·&nbsp;
			<strong><?php esc_html_e( 'Debug Mode:', 'ultimate-performance-optimizer' ); ?></strong>
			<?php echo $settings->is_enabled( 'debug_mode' ) ? esc_html__( 'ON', 'ultimate-performance-optimizer' ) : esc_html__( 'OFF', 'ultimate-performance-optimizer' ); ?>
		</p>
		<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => \UPO\Admin\Admin::SLUG, 'tab' => 'advanced' ), admin_url( 'admin.php' ) ) ); ?>">
			<?php esc_html_e( 'Open Advanced tab', 'ultimate-performance-optimizer' ); ?>
		</a>
	</div>

</div>
