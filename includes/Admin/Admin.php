<?php
/**
 * Admin controller: menu, tabs, asset loading and secure form handling.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Admin;

use UPO\Settings\Settings;
use UPO\Settings\Settings_Schema;
use UPO\Support\Environment;
use UPO\Support\Logger;
use UPO\Modules\Module_Manager;
use UPO\Diagnostics\Diagnostics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the entire admin experience.
 */
final class Admin {

	/**
	 * Admin page slug.
	 */
	public const SLUG = 'ultimate-performance-optimizer';

	/**
	 * Capability required to manage the plugin.
	 */
	public const CAP = 'manage_options';

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
	 * Admin page hook suffix.
	 *
	 * @var string
	 */
	private string $hook = '';

	/**
	 * Constructor.
	 *
	 * @param Settings       $settings Settings gateway.
	 * @param Environment    $env      Environment detector.
	 * @param Logger         $log      Logger.
	 * @param Module_Manager $modules  Module manager.
	 */
	public function __construct( Settings $settings, Environment $env, Logger $log, Module_Manager $modules ) {
		$this->settings    = $settings;
		$this->env         = $env;
		$this->log         = $log;
		$this->modules     = $modules;
		$this->diagnostics = new Diagnostics( $settings, $env );
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_upo_save_settings', array( $this, 'handle_save' ) );
		add_action( 'admin_post_upo_tool', array( $this, 'handle_tool' ) );
		add_action( 'admin_post_upo_auto_optimize', array( $this, 'handle_auto_optimize' ) );
		add_filter( 'plugin_action_links_' . UPO_BASENAME, array( $this, 'action_links' ) );

		( new Ajax( $this->settings, $this->env, $this->log, $this->modules, $this->diagnostics ) )->register();
	}

	/**
	 * Add the top-level menu page.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		$this->hook = (string) add_menu_page(
			__( 'Organic Kratom USA Performance Optimizer', 'ultimate-performance-optimizer' ),
			__( 'Performance', 'ultimate-performance-optimizer' ),
			self::CAP,
			self::SLUG,
			array( $this, 'render_page' ),
			'dashicons-performance',
			58
		);
	}

	/**
	 * The ordered tab registry.
	 *
	 * @return array<string, array<string, string>>
	 */
	public function tabs(): array {
		return array(
			'dashboard'   => array( 'title' => __( 'Dashboard', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-dashboard', 'type' => 'custom' ),
			'frontend'    => array( 'title' => __( 'Frontend', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-admin-appearance', 'type' => 'settings' ),
			'backend'     => array( 'title' => __( 'Backend', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-admin-tools', 'type' => 'settings' ),
			'woocommerce' => array( 'title' => __( 'WooCommerce', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-cart', 'type' => 'settings' ),
			'elementor'   => array( 'title' => __( 'Elementor', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-editor-kitchensink', 'type' => 'settings' ),
			'fonts'       => array( 'title' => __( 'Fonts', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-editor-textcolor', 'type' => 'settings' ),
			'images'      => array( 'title' => __( 'Images', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-format-image', 'type' => 'settings' ),
			'caching'     => array( 'title' => __( 'Caching', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-database', 'type' => 'settings' ),
			'javascript'  => array( 'title' => __( 'JavaScript', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-media-code', 'type' => 'settings' ),
			'css'         => array( 'title' => __( 'CSS', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-editor-code', 'type' => 'settings' ),
			'database'    => array( 'title' => __( 'Database', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-database-view', 'type' => 'settings' ),
			'cdn'         => array( 'title' => __( 'CDN', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-cloud', 'type' => 'settings' ),
			'advanced'    => array( 'title' => __( 'Advanced', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-admin-settings', 'type' => 'settings' ),
			'tools'       => array( 'title' => __( 'Tools', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-admin-generic', 'type' => 'custom' ),
			'logs'        => array( 'title' => __( 'Logs', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-media-text', 'type' => 'custom' ),
			'diagnostics' => array( 'title' => __( 'Diagnostics', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-heart', 'type' => 'custom' ),
			'about'       => array( 'title' => __( 'About', 'ultimate-performance-optimizer' ), 'icon' => 'dashicons-info', 'type' => 'custom' ),
		);
	}

	/**
	 * Resolve the current tab from the request.
	 *
	 * @return string
	 */
	private function current_tab(): string {
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tabs = $this->tabs();
		return isset( $tabs[ $tab ] ) ? $tab : 'dashboard';
	}

	/**
	 * Enqueue admin assets only on our page.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( $hook !== $this->hook ) {
			return;
		}

		wp_enqueue_style( 'upo-admin', UPO_ASSETS . 'css/upo-admin.css', array(), UPO_VERSION );
		wp_enqueue_script( 'upo-admin', UPO_ASSETS . 'js/upo-admin.js', array( 'jquery', 'wp-util' ), UPO_VERSION, true );

		wp_localize_script(
			'upo-admin',
			'upoAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'upo_ajax' ),
				'i18n'    => array(
					'working'   => __( 'Working…', 'ultimate-performance-optimizer' ),
					'confirm'   => __( 'Are you sure? This cannot be undone.', 'ultimate-performance-optimizer' ),
					'done'      => __( 'Done.', 'ultimate-performance-optimizer' ),
					'error'     => __( 'Something went wrong. Please try again.', 'ultimate-performance-optimizer' ),
					'items'     => __( 'items', 'ultimate-performance-optimizer' ),
				),
			)
		);
	}

	/**
	 * Add a Settings link on the Plugins screen.
	 *
	 * @param array<int, string> $links Existing links.
	 * @return array<int, string>
	 */
	public function action_links( $links ): array {
		$links   = is_array( $links ) ? $links : array();
		$url     = admin_url( 'admin.php?page=' . self::SLUG );
		$custom  = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings', 'ultimate-performance-optimizer' ) );
		array_unshift( $links, $custom );
		return $links;
	}

	/**
	 * Render the whole admin page (wrapper + nav + tab content).
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'ultimate-performance-optimizer' ) );
		}

		$tabs        = $this->tabs();
		$current     = $this->current_tab();
		$current_def = $tabs[ $current ];

		$this->view( 'header', array( 'tabs' => $tabs, 'current' => $current ) );

		echo '<div class="upo-content">';

		if ( 'settings' === $current_def['type'] ) {
			$this->view( 'settings-tab', array( 'tab' => $current ) );
		} else {
			$this->view( $current, array( 'tab' => $current ) );
		}

		echo '</div>'; // .upo-content

		$this->view( 'footer', array() );
	}

	/**
	 * Include a view file with the given data in scope.
	 *
	 * @param string               $view View base name (no extension).
	 * @param array<string, mixed> $data Data extracted into the view.
	 * @return void
	 */
	private function view( string $view, array $data = array() ): void {
		$file = UPO_INC . 'Admin/views/' . $view . '.php';
		if ( ! is_readable( $file ) ) {
			return;
		}
		// Expose services to the view. $this is available inside the include.
		$settings    = $this->settings;
		$env         = $this->env;
		$log         = $this->log;
		$modules     = $this->modules;
		$diagnostics = $this->diagnostics;
		$admin       = $this;
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $data, EXTR_SKIP );

		require $file;
	}

	/**
	 * Accessor for the diagnostics service (used by views).
	 *
	 * @return Diagnostics
	 */
	public function diagnostics(): Diagnostics {
		return $this->diagnostics;
	}

	/**
	 * Accessor for the module manager (used by views).
	 *
	 * @return Module_Manager
	 */
	public function modules(): Module_Manager {
		return $this->modules;
	}

	/**
	 * Handle a tab settings submission securely.
	 *
	 * @return void
	 */
	public function handle_save(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ultimate-performance-optimizer' ) );
		}

		$tab = isset( $_POST['upo_tab'] ) ? sanitize_key( wp_unslash( $_POST['upo_tab'] ) ) : '';
		check_admin_referer( 'upo_save_' . $tab );

		$ids   = Settings_Schema::field_ids_for_tab( $tab );
		$input = isset( $_POST[ UPO_OPTION ] ) && is_array( $_POST[ UPO_OPTION ] )
			? wp_unslash( $_POST[ UPO_OPTION ] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			: array();

		$this->settings->save_fields( $input, $ids );

		// Keep the DB cron schedule in sync immediately after saving.
		$db = $this->modules->get( 'database' );
		if ( $db instanceof \UPO\Modules\Database\Database ) {
			$db->sync_schedule();
		}

		$this->log->info( sprintf( 'Settings saved on the "%s" tab.', $tab ), 'settings' );

		$this->redirect_back( $tab, 'saved' );
	}

	/**
	 * Handle export / import / reset tool actions.
	 *
	 * @return void
	 */
	public function handle_tool(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ultimate-performance-optimizer' ) );
		}

		$action = isset( $_POST['upo_action'] ) ? sanitize_key( wp_unslash( $_POST['upo_action'] ) ) : '';
		check_admin_referer( 'upo_tool_' . $action );

		switch ( $action ) {
			case 'export':
				$this->export_settings();
				return; // export streams and exits.

			case 'import':
				$this->import_settings();
				$this->redirect_back( 'tools', 'imported' );
				return;

			case 'reset':
				$this->settings->reset();
				$this->log->warning( 'Settings reset to defaults.', 'settings' );
				$this->redirect_back( 'tools', 'reset' );
				return;

			case 'clear_logs':
				$this->log->clear();
				$this->redirect_back( 'logs', 'logs_cleared' );
				return;

			default:
				$this->redirect_back( 'tools', 'error' );
		}
	}

	/**
	 * Apply the recommended one-click optimization profile.
	 *
	 * Enables the safe, high-impact set (defer + delay third-party JS, LCP
	 * preload, font-display swap, head cleanup, heartbeat tuning) in a single
	 * step. Deliberately leaves risky, site-specific options (async CSS delivery,
	 * WooCommerce/Elementor asset unloading, CDN) alone.
	 *
	 * @return void
	 */
	public function handle_auto_optimize(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ultimate-performance-optimizer' ) );
		}
		check_admin_referer( 'upo_auto_optimize' );

		$profile = $this->recommended_profile();
		$this->settings->save_fields( $profile, array_keys( $profile ) );

		// Keep the DB cron schedule in sync in case anything changed.
		$db = $this->modules->get( 'database' );
		if ( $db instanceof \UPO\Modules\Database\Database ) {
			$db->sync_schedule();
		}

		$this->log->info( 'One-click Auto-Optimize applied the recommended profile.', 'settings' );

		$this->redirect_back( 'dashboard', 'optimized' );
	}

	/**
	 * The recommended, safe-by-default optimization profile.
	 *
	 * @return array<string, mixed>
	 */
	private function recommended_profile(): array {
		return array(
			// Head cleanup — no visual impact.
			'remove_emojis'              => true,
			'remove_generator'          => true,
			'remove_wlwmanifest'        => true,
			'remove_rsd'                => true,
			'remove_shortlink'          => true,
			'remove_oembed_discovery'   => true,
			'disable_self_pingbacks'    => true,
			// Backend load.
			'heartbeat_mode'            => 'optimize',
			'heartbeat_interval'        => 60,
			'heartbeat_disable_frontend' => true,
			// Fonts.
			'fonts_display_swap'        => true,
			'fonts_preconnect'          => true,
			// Images & LCP.
			'img_lazy_load'             => true,
			'img_async_decoding'        => true,
			'img_fetchpriority_lcp'     => true,
			'lcp_auto_preload'          => true,
			// JavaScript — the biggest TBT/INP win.
			'js_defer'                  => true,
			'js_delay_third_party'      => true,
			'js_delay_timeout'          => 5,
		);
	}

	/**
	 * Stream the current settings as a JSON download.
	 *
	 * @return void
	 */
	private function export_settings(): void {
		$data = array(
			'plugin'   => 'ultimate-performance-optimizer',
			'version'  => UPO_VERSION,
			'exported' => gmdate( 'c' ),
			'settings' => $this->settings->export(),
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="upo-settings-' . gmdate( 'Ymd' ) . '.json"' );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT );
		exit;
	}

	/**
	 * Import settings from an uploaded JSON file.
	 *
	 * @return void
	 */
	private function import_settings(): void {
		if ( empty( $_FILES['upo_import_file']['tmp_name'] ) ) {
			return;
		}

		$tmp = sanitize_text_field( wp_unslash( $_FILES['upo_import_file']['tmp_name'] ) );
		if ( ! is_uploaded_file( $tmp ) ) {
			return;
		}

		$raw = (string) file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$decoded = json_decode( $raw, true );

		if ( is_array( $decoded ) && isset( $decoded['settings'] ) && is_array( $decoded['settings'] ) ) {
			$this->settings->import( $decoded['settings'] );
			$this->log->info( 'Settings imported from file.', 'settings' );
		}
	}

	/**
	 * Redirect back to a tab with a status flag.
	 *
	 * @param string $tab    Tab id.
	 * @param string $status Status key.
	 * @return void
	 */
	private function redirect_back( string $tab, string $status ): void {
		$url = add_query_arg(
			array(
				'page'       => self::SLUG,
				'tab'        => $tab,
				'upo_status' => $status,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
