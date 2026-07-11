<?php
/**
 * Database maintenance: scheduled + on-demand cleanup and reporting.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Modules\Database;

use UPO\Modules\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cleans common database bloat and reports on it.
 *
 * All deletions go through core APIs (wp_delete_post_revision, wp_delete_post,
 * wp_delete_comment) so related metadata is removed correctly, and every count
 * query is prepared.
 */
final class Database extends Abstract_Module {

	/**
	 * Cron hook name for the scheduled cleanup.
	 */
	public const CRON_HOOK = 'upo_database_cleanup';

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'database';
	}

	/**
	 * Database work is back-end only.
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
		add_action( self::CRON_HOOK, array( $this, 'run_scheduled_cleanup' ) );
		add_action( 'init', array( $this, 'sync_schedule' ) );
	}

	/**
	 * Keep the cron schedule in sync with the setting.
	 *
	 * @return void
	 */
	public function sync_schedule(): void {
		$enabled   = $this->enabled( 'db_schedule_cleanup' );
		$scheduled = wp_next_scheduled( self::CRON_HOOK );

		if ( $enabled && false === $scheduled ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'weekly', self::CRON_HOOK );
		} elseif ( ! $enabled && false !== $scheduled ) {
			wp_unschedule_event( $scheduled, self::CRON_HOOK );
		}
	}

	/**
	 * Run every enabled cleanup task on schedule.
	 *
	 * @return void
	 */
	public function run_scheduled_cleanup(): void {
		$summary = $this->run_selected_tasks();
		$total   = array_sum( $summary );
		$this->log->info(
			sprintf( 'Scheduled database cleanup removed %d items.', $total ),
			'database'
		);
	}

	/**
	 * Run the tasks that are enabled in settings and return per-task counts.
	 *
	 * @return array<string, int>
	 */
	public function run_selected_tasks(): array {
		$result = array();

		if ( $this->enabled( 'db_clean_expired_transients' ) ) {
			$result['expired_transients'] = $this->clean_expired_transients();
		}
		if ( $this->enabled( 'db_clean_revisions' ) ) {
			$result['revisions'] = $this->clean_revisions();
		}
		if ( $this->enabled( 'db_clean_auto_drafts' ) ) {
			$result['auto_drafts'] = $this->clean_auto_drafts();
		}
		if ( $this->enabled( 'db_clean_trash_posts' ) ) {
			$result['trash_posts'] = $this->clean_trash_posts();
		}
		if ( $this->enabled( 'db_clean_spam_comments' ) ) {
			$result['spam_comments'] = $this->clean_comments( 'spam' );
		}
		if ( $this->enabled( 'db_clean_trash_comments' ) ) {
			$result['trash_comments'] = $this->clean_comments( 'trash' );
		}

		return $result;
	}

	/**
	 * Delete expired transients (both regular and site transients).
	 *
	 * @return int Number of transients removed.
	 */
	public function clean_expired_transients(): int {
		global $wpdb;
		$now     = time();
		$count   = 0;
		$options = $wpdb->options;

		// Regular transients.
		$timeouts = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$options} WHERE option_name LIKE %s AND option_value < %d",
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				$now
			)
		);
		foreach ( (array) $timeouts as $timeout_name ) {
			$key = str_replace( '_transient_timeout_', '', (string) $timeout_name );
			if ( delete_transient( $key ) ) {
				$count++;
			}
		}

		// Site transients (multisite / network).
		$site_timeouts = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$options} WHERE option_name LIKE %s AND option_value < %d",
				$wpdb->esc_like( '_site_transient_timeout_' ) . '%',
				$now
			)
		);
		foreach ( (array) $site_timeouts as $timeout_name ) {
			$key = str_replace( '_site_transient_timeout_', '', (string) $timeout_name );
			if ( delete_site_transient( $key ) ) {
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Delete post revisions beyond the configured keep count.
	 *
	 * @return int Number of revisions removed.
	 */
	public function clean_revisions(): int {
		global $wpdb;
		$keep  = max( 0, (int) $this->settings->get( 'db_revisions_keep', 5 ) );
		$count = 0;

		$parents = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_parent FROM {$wpdb->posts} WHERE post_type = 'revision' GROUP BY post_parent HAVING COUNT(*) > %d",
				$keep
			)
		);

		foreach ( (array) $parents as $parent_id ) {
			$revisions = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_parent = %d ORDER BY ID DESC",
					(int) $parent_id
				)
			);
			$to_delete = array_slice( (array) $revisions, $keep );
			foreach ( $to_delete as $revision_id ) {
				if ( wp_delete_post_revision( (int) $revision_id ) ) {
					$count++;
				}
			}
		}

		return $count;
	}

	/**
	 * Delete auto-draft posts older than a day.
	 *
	 * @return int Number of auto-drafts removed.
	 */
	public function clean_auto_drafts(): int {
		global $wpdb;
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'auto-draft' AND post_date < %s",
				gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS )
			)
		);
		$count = 0;
		foreach ( (array) $ids as $id ) {
			if ( wp_delete_post( (int) $id, true ) ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Permanently delete trashed posts.
	 *
	 * @return int Number of posts removed.
	 */
	public function clean_trash_posts(): int {
		global $wpdb;
		$ids = $wpdb->get_col(
			"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'trash'"
		);
		$count = 0;
		foreach ( (array) $ids as $id ) {
			if ( wp_delete_post( (int) $id, true ) ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Permanently delete comments of a given status.
	 *
	 * @param string $status Either 'spam' or 'trash'.
	 * @return int Number of comments removed.
	 */
	public function clean_comments( string $status ): int {
		global $wpdb;
		$approved = ( 'spam' === $status ) ? 'spam' : 'trash';
		$ids      = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = %s",
				$approved
			)
		);
		$count = 0;
		foreach ( (array) $ids as $id ) {
			if ( wp_delete_comment( (int) $id, true ) ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Optimize the site's database tables.
	 *
	 * @return int Number of tables optimized.
	 */
	public function optimize_tables(): int {
		global $wpdb;
		$tables = $wpdb->get_col(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $wpdb->prefix ) . '%'
			)
		);
		$count = 0;
		foreach ( (array) $tables as $table ) {
			// Table names come from SHOW TABLES; wrap in backticks for safety.
			$safe = '`' . str_replace( '`', '', (string) $table ) . '`';
			if ( false !== $wpdb->query( "OPTIMIZE TABLE {$safe}" ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Build a full database report for the admin UI.
	 *
	 * @return array<string, int>
	 */
	public function get_report(): array {
		global $wpdb;

		$now = time();

		return array(
			'revisions'          => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'" ),
			'auto_drafts'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'" ),
			'trash_posts'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'" ),
			'spam_comments'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'" ),
			'trash_comments'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'" ),
			'expired_transients' => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
					$wpdb->esc_like( '_transient_timeout_' ) . '%',
					$now
				)
			),
			'orphan_postmeta'    => (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.ID IS NULL"
			),
			'orphan_commentmeta' => (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM {$wpdb->commentmeta} cm LEFT JOIN {$wpdb->comments} c ON c.comment_ID = cm.comment_id WHERE c.comment_ID IS NULL"
			),
		);
	}

	/**
	 * Approximate total database size in bytes.
	 *
	 * @return int
	 */
	public function get_database_size(): int {
		global $wpdb;
		$size = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT SUM(data_length + index_length) FROM information_schema.TABLES WHERE table_schema = %s',
				DB_NAME
			)
		);
		return (int) $size;
	}
}
