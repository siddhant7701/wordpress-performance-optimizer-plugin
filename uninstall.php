<?php
/**
 * Uninstall handler.
 *
 * Only removes data when the user opted in via the "Delete all plugin data on
 * uninstall" setting. Runs for both single-site and multisite.
 *
 * @package UPO
 */

declare( strict_types=1 );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove all plugin data for the current site.
 *
 * @return void
 */
function upo_uninstall_cleanup(): void {
	$settings = get_option( 'upo_settings', array() );
	$remove   = is_array( $settings ) && ! empty( $settings['remove_data_on_uninstall'] );

	if ( ! $remove ) {
		return;
	}

	delete_option( 'upo_settings' );
	delete_option( 'upo_version' );
	delete_option( 'upo_installed_at' );
	delete_option( 'upo_log' );

	delete_transient( 'upo_plugin_sizes' );
	delete_transient( 'upo_diagnostics_report' );
	delete_transient( 'upo_conflict_scan' );

	wp_clear_scheduled_hook( 'upo_database_cleanup' );
}

if ( is_multisite() ) {
	$upo_sites = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $upo_sites as $upo_site_id ) {
		switch_to_blog( (int) $upo_site_id );
		upo_uninstall_cleanup();
		restore_current_blog();
	}
} else {
	upo_uninstall_cleanup();
}
