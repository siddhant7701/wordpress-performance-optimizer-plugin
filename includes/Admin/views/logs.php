<?php
/**
 * Logs tab: rolling plugin event log.
 *
 * @package UPO
 *
 * @var \UPO\Support\Logger $log
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$upo_entries = array_reverse( $log->all() );
?>
<div class="upo-panel">
	<div class="upo-panel__head">
		<h2><?php esc_html_e( 'Activity log', 'ultimate-performance-optimizer' ); ?></h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="upo-confirm">
			<input type="hidden" name="action" value="upo_tool">
			<input type="hidden" name="upo_action" value="clear_logs">
			<?php wp_nonce_field( 'upo_tool_clear_logs' ); ?>
			<button type="submit" class="button"><?php esc_html_e( 'Clear log', 'ultimate-performance-optimizer' ); ?></button>
		</form>
	</div>

	<?php if ( array() === $upo_entries ) : ?>
		<p class="upo-muted"><?php esc_html_e( 'No log entries yet.', 'ultimate-performance-optimizer' ); ?></p>
	<?php else : ?>
		<table class="upo-table upo-table--logs">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Time', 'ultimate-performance-optimizer' ); ?></th>
					<th><?php esc_html_e( 'Level', 'ultimate-performance-optimizer' ); ?></th>
					<th><?php esc_html_e( 'Context', 'ultimate-performance-optimizer' ); ?></th>
					<th><?php esc_html_e( 'Message', 'ultimate-performance-optimizer' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $upo_entries as $upo_entry ) : ?>
					<tr>
						<td class="upo-nowrap"><?php echo esc_html( wp_date( 'Y-m-d H:i:s', (int) $upo_entry['time'] ) ); ?></td>
						<td><span class="upo-level upo-level--<?php echo esc_attr( (string) $upo_entry['level'] ); ?>"><?php echo esc_html( (string) $upo_entry['level'] ); ?></span></td>
						<td><?php echo esc_html( (string) $upo_entry['context'] ); ?></td>
						<td><?php echo esc_html( (string) $upo_entry['message'] ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
