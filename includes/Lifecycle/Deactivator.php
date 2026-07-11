<?php
/**
 * Deactivation routine.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Lifecycle;

use UPO\Modules\Database\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs once on plugin deactivation.
 *
 * We intentionally keep settings intact on deactivation (only remove
 * transient/scheduled state). Full removal happens in uninstall.php.
 */
final class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( Database::CRON_HOOK );
		if ( false !== $timestamp ) {
			wp_unschedule_event( $timestamp, Database::CRON_HOOK );
		}
		wp_clear_scheduled_hook( Database::CRON_HOOK );

		// Clear our own cached diagnostics/reports.
		delete_transient( 'upo_diagnostics_report' );
		delete_transient( 'upo_conflict_scan' );

		flush_rewrite_rules( false );
	}
}
