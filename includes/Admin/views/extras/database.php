<?php
/**
 * Database tab extras: live report and on-demand cleanup actions.
 *
 * @package UPO
 *
 * @var \UPO\Modules\Module_Manager $modules
 */

declare( strict_types=1 );

use UPO\Modules\Database\Database;
use UPO\Support\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$upo_db = $modules->get( 'database' );
if ( ! $upo_db instanceof Database ) {
	return;
}

$upo_report = $upo_db->get_report();
$upo_size   = $upo_db->get_database_size();

$upo_rows = array(
	'expired_transients' => __( 'Expired transients', 'ultimate-performance-optimizer' ),
	'revisions'          => __( 'Post revisions', 'ultimate-performance-optimizer' ),
	'auto_drafts'        => __( 'Auto-drafts', 'ultimate-performance-optimizer' ),
	'trash_posts'        => __( 'Trashed posts', 'ultimate-performance-optimizer' ),
	'spam_comments'      => __( 'Spam comments', 'ultimate-performance-optimizer' ),
	'trash_comments'     => __( 'Trashed comments', 'ultimate-performance-optimizer' ),
);
?>
<div class="upo-callout">
	<p>
		<strong><?php esc_html_e( 'Database size:', 'ultimate-performance-optimizer' ); ?></strong>
		<?php echo esc_html( Helpers::format_bytes( $upo_size ) ); ?>
		<?php if ( $upo_report['orphan_postmeta'] > 0 || $upo_report['orphan_commentmeta'] > 0 ) : ?>
			&nbsp;·&nbsp;
			<?php
			printf(
				/* translators: 1: orphaned post meta rows, 2: orphaned comment meta rows. */
				esc_html__( 'Orphaned metadata detected: %1$d post-meta, %2$d comment-meta rows.', 'ultimate-performance-optimizer' ),
				(int) $upo_report['orphan_postmeta'],
				(int) $upo_report['orphan_commentmeta']
			);
			?>
		<?php endif; ?>
	</p>
</div>

<table class="upo-table" id="upo-db-report">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Item', 'ultimate-performance-optimizer' ); ?></th>
			<th class="upo-num"><?php esc_html_e( 'Count', 'ultimate-performance-optimizer' ); ?></th>
			<th class="upo-actions"><?php esc_html_e( 'Action', 'ultimate-performance-optimizer' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $upo_rows as $upo_key => $upo_label ) : ?>
			<tr>
				<td><?php echo esc_html( $upo_label ); ?></td>
				<td class="upo-num" data-count="<?php echo esc_attr( $upo_key ); ?>"><?php echo esc_html( number_format_i18n( (int) $upo_report[ $upo_key ] ) ); ?></td>
				<td class="upo-actions">
					<button
						type="button"
						class="button upo-db-clean"
						data-task="<?php echo esc_attr( $upo_key ); ?>"
						<?php disabled( 0, (int) $upo_report[ $upo_key ] ); ?>
					>
						<?php esc_html_e( 'Clean', 'ultimate-performance-optimizer' ); ?>
					</button>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<div class="upo-inline-actions">
	<button type="button" class="button button-secondary" id="upo-db-optimize">
		<?php esc_html_e( 'Optimize database tables', 'ultimate-performance-optimizer' ); ?>
	</button>
	<span class="upo-inline-actions__note">
		<?php esc_html_e( 'Cleanup uses core delete functions so related metadata is removed correctly. Back up your database before large cleanups.', 'ultimate-performance-optimizer' ); ?>
	</span>
</div>

<hr class="upo-hr">
<h3 class="upo-section__title"><?php esc_html_e( 'Scheduled cleanup', 'ultimate-performance-optimizer' ); ?></h3>
<p class="upo-muted"><?php esc_html_e( 'Enable the tasks below and turn on the weekly schedule to keep the database tidy automatically.', 'ultimate-performance-optimizer' ); ?></p>
