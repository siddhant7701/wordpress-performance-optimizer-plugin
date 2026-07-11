<?php
/**
 * Secured AJAX endpoints for the admin UI.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Admin;

use UPO\Settings\Settings;
use UPO\Support\Environment;
use UPO\Support\Logger;
use UPO\Modules\Module_Manager;
use UPO\Modules\Database\Database;
use UPO\Diagnostics\Diagnostics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles asynchronous admin actions. Every handler verifies a nonce and the
 * manage_options capability before doing anything.
 */
final class Ajax {

	/**
	 * Settings gateway.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Environment detector.
	 *
	 * @var Environment
	 */
	private Environment $env;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private Logger $log;

	/**
	 * Module manager.
	 *
	 * @var Module_Manager
	 */
	private Module_Manager $modules;

	/**
	 * Diagnostics service.
	 *
	 * @var Diagnostics
	 */
	private Diagnostics $diagnostics;

	/**
	 * Constructor.
	 *
	 * @param Settings       $settings    Settings gateway.
	 * @param Environment    $env         Environment detector.
	 * @param Logger         $log         Logger.
	 * @param Module_Manager $modules     Module manager.
	 * @param Diagnostics    $diagnostics Diagnostics service.
	 */
	public function __construct( Settings $settings, Environment $env, Logger $log, Module_Manager $modules, Diagnostics $diagnostics ) {
		$this->settings    = $settings;
		$this->env         = $env;
		$this->log         = $log;
		$this->modules     = $modules;
		$this->diagnostics = $diagnostics;
	}

	/**
	 * Register AJAX actions.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_ajax_upo_db_task', array( $this, 'db_task' ) );
		add_action( 'wp_ajax_upo_db_optimize', array( $this, 'db_optimize' ) );
		add_action( 'wp_ajax_upo_db_report', array( $this, 'db_report' ) );
		add_action( 'wp_ajax_upo_score', array( $this, 'score' ) );
		add_action( 'wp_ajax_upo_scan_alt', array( $this, 'scan_alt' ) );
		add_action( 'wp_ajax_upo_refresh_plugin_sizes', array( $this, 'refresh_plugin_sizes' ) );
	}

	/**
	 * Shared guard: verify nonce + capability, or die with an error.
	 *
	 * @return void
	 */
	private function guard(): void {
		if ( ! check_ajax_referer( 'upo_ajax', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'ultimate-performance-optimizer' ) ), 403 );
		}
		if ( ! current_user_can( Admin::CAP ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ultimate-performance-optimizer' ) ), 403 );
		}
	}

	/**
	 * Run a single database cleanup task.
	 *
	 * @return void
	 */
	public function db_task(): void {
		$this->guard();

		$db = $this->modules->get( 'database' );
		if ( ! $db instanceof Database ) {
			wp_send_json_error( array( 'message' => __( 'Database module unavailable.', 'ultimate-performance-optimizer' ) ) );
		}

		$task  = isset( $_POST['task'] ) ? sanitize_key( wp_unslash( $_POST['task'] ) ) : '';
		$count = 0;

		switch ( $task ) {
			case 'expired_transients':
				$count = $db->clean_expired_transients();
				break;
			case 'revisions':
				$count = $db->clean_revisions();
				break;
			case 'auto_drafts':
				$count = $db->clean_auto_drafts();
				break;
			case 'trash_posts':
				$count = $db->clean_trash_posts();
				break;
			case 'spam_comments':
				$count = $db->clean_comments( 'spam' );
				break;
			case 'trash_comments':
				$count = $db->clean_comments( 'trash' );
				break;
			default:
				wp_send_json_error( array( 'message' => __( 'Unknown task.', 'ultimate-performance-optimizer' ) ) );
		}

		$this->log->info( sprintf( 'Manual DB task "%1$s" removed %2$d items.', $task, $count ), 'database' );

		wp_send_json_success(
			array(
				'count'   => $count,
				'message' => sprintf(
					/* translators: %d: number of items removed. */
					_n( 'Removed %d item.', 'Removed %d items.', $count, 'ultimate-performance-optimizer' ),
					$count
				),
				'report'  => $db->get_report(),
			)
		);
	}

	/**
	 * Optimize database tables.
	 *
	 * @return void
	 */
	public function db_optimize(): void {
		$this->guard();

		$db = $this->modules->get( 'database' );
		if ( ! $db instanceof Database ) {
			wp_send_json_error( array( 'message' => __( 'Database module unavailable.', 'ultimate-performance-optimizer' ) ) );
		}

		$count = $db->optimize_tables();
		$this->log->info( sprintf( 'Optimized %d database tables.', $count ), 'database' );

		wp_send_json_success(
			array(
				'count'   => $count,
				'message' => sprintf(
					/* translators: %d: number of tables optimized. */
					_n( 'Optimized %d table.', 'Optimized %d tables.', $count, 'ultimate-performance-optimizer' ),
					$count
				),
			)
		);
	}

	/**
	 * Return a fresh database report.
	 *
	 * @return void
	 */
	public function db_report(): void {
		$this->guard();

		$db = $this->modules->get( 'database' );
		if ( ! $db instanceof Database ) {
			wp_send_json_error( array( 'message' => __( 'Database module unavailable.', 'ultimate-performance-optimizer' ) ) );
		}

		wp_send_json_success(
			array(
				'report' => $db->get_report(),
				'size'   => $db->get_database_size(),
			)
		);
	}

	/**
	 * Return the coverage estimate.
	 *
	 * @return void
	 */
	public function score(): void {
		$this->guard();
		wp_send_json_success( $this->diagnostics->coverage_estimate() );
	}

	/**
	 * Scan for images missing alt text.
	 *
	 * @return void
	 */
	public function scan_alt(): void {
		$this->guard();
		wp_send_json_success( array( 'rows' => $this->diagnostics->images_missing_alt( 50 ) ) );
	}

	/**
	 * Recompute plugin directory sizes.
	 *
	 * @return void
	 */
	public function refresh_plugin_sizes(): void {
		$this->guard();
		delete_transient( 'upo_plugin_sizes' );
		wp_send_json_success( array( 'rows' => $this->diagnostics->large_plugins( 10 ) ) );
	}
}
