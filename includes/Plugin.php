<?php
/**
 * Main plugin orchestrator (singleton).
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO;

use UPO\Settings\Settings;
use UPO\Support\Environment;
use UPO\Support\Logger;
use UPO\Modules\Module_Manager;
use UPO\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires the plugin together and exposes its services.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

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
	private Environment $environment;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	private Logger $logger;

	/**
	 * Module manager.
	 *
	 * @var Module_Manager
	 */
	private Module_Manager $modules;

	/**
	 * Whether boot() has already run.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Private constructor — builds core services.
	 */
	private function __construct() {
		$this->settings    = new Settings();
		$this->environment = new Environment();
		$this->logger      = new Logger( $this->settings->is_enabled( 'enable_logging' ) );
		$this->modules     = new Module_Manager( $this->settings, $this->environment, $this->logger );
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Boot the plugin. Idempotent.
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Register optimization modules (front + back + cron).
		$this->modules->register();

		// Admin UI.
		if ( is_admin() ) {
			( new Admin( $this->settings, $this->environment, $this->logger, $this->modules ) )->register();
		}

		// Register our custom cron schedule regardless of context.
		add_filter( 'cron_schedules', array( $this, 'register_cron_schedules' ) );

		/**
		 * Fires once the plugin has finished booting.
		 *
		 * @param Plugin $plugin The plugin instance.
		 */
		do_action( 'upo_booted', $this );
	}

	/**
	 * Load the plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'ultimate-performance-optimizer',
			false,
			dirname( UPO_BASENAME ) . '/languages'
		);
	}

	/**
	 * Ensure a "weekly" cron schedule exists.
	 *
	 * @param array<string, array<string, mixed>> $schedules Existing schedules.
	 * @return array<string, array<string, mixed>>
	 */
	public function register_cron_schedules( array $schedules ): array {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = array(
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Once Weekly', 'ultimate-performance-optimizer' ),
			);
		}
		return $schedules;
	}

	/**
	 * Settings accessor.
	 *
	 * @return Settings
	 */
	public function settings(): Settings {
		return $this->settings;
	}

	/**
	 * Environment accessor.
	 *
	 * @return Environment
	 */
	public function environment(): Environment {
		return $this->environment;
	}

	/**
	 * Logger accessor.
	 *
	 * @return Logger
	 */
	public function logger(): Logger {
		return $this->logger;
	}

	/**
	 * Module manager accessor.
	 *
	 * @return Module_Manager
	 */
	public function modules(): Module_Manager {
		return $this->modules;
	}

	/**
	 * Prevent cloning.
	 *
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __METHOD__, esc_html__( 'Cloning is not allowed.', 'ultimate-performance-optimizer' ), '1.0.0' );
	}

	/**
	 * Prevent unserialization.
	 *
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __METHOD__, esc_html__( 'Unserializing is not allowed.', 'ultimate-performance-optimizer' ), '1.0.0' );
	}
}
