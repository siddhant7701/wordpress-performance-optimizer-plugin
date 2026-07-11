<?php
/**
 * Activation routine.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Lifecycle;

use UPO\Settings\Settings;
use UPO\Modules\Database\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs once on plugin activation.
 */
final class Activator {

	/**
	 * Activate the plugin.
	 *
	 * - Seeds default settings without overwriting existing ones.
	 * - Stores the installed version and activation timestamp.
	 * - Schedules the recurring database cleanup event.
	 *
	 * @return void
	 */
	public static function activate(): void {
		$settings = new Settings();
		$settings->maybe_seed_defaults();

		if ( false === get_option( 'upo_installed_at', false ) ) {
			add_option( 'upo_installed_at', time() );
		}

		update_option( 'upo_version', UPO_VERSION );

		// Schedule the weekly database cleanup if enabled.
		if ( $settings->is_enabled( 'db_schedule_cleanup' ) && ! wp_next_scheduled( Database::CRON_HOOK ) ) {
			wp_schedule_event( time() + DAY_IN_SECONDS, 'weekly', Database::CRON_HOOK );
		}

		// Ensure custom cron schedules are registered for this request.
		flush_rewrite_rules( false );
	}
}
