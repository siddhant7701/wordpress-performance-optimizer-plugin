<?php
/**
 * Registers and coordinates all optimization modules.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Modules;

use UPO\Settings\Settings;
use UPO\Support\Environment;
use UPO\Support\Logger;
use UPO\Modules\Frontend\Cleanup;
use UPO\Modules\Frontend\Heartbeat;
use UPO\Modules\Frontend\Scripts;
use UPO\Modules\Frontend\Resource_Hints;
use UPO\Modules\Frontend\Fonts;
use UPO\Modules\Frontend\Images;
use UPO\Modules\Frontend\Lcp;
use UPO\Modules\Frontend\Lazy_YouTube;
use UPO\Modules\Frontend\Css;
use UPO\Modules\WooCommerce\WooCommerce;
use UPO\Modules\Elementor\Elementor;
use UPO\Modules\Database\Database;
use UPO\Modules\Cache\Cache;
use UPO\Modules\Cdn\Cdn;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds module instances and registers their hooks, honouring Safe Mode.
 */
final class Module_Manager {

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
	 * Instantiated modules keyed by id.
	 *
	 * @var array<string, Abstract_Module>
	 */
	private array $modules = array();

	/**
	 * Constructor.
	 *
	 * @param Settings    $settings Settings gateway.
	 * @param Environment $env      Environment detector.
	 * @param Logger      $log      Logger.
	 */
	public function __construct( Settings $settings, Environment $env, Logger $log ) {
		$this->settings = $settings;
		$this->env      = $env;
		$this->log      = $log;
	}

	/**
	 * Instantiate the module classes.
	 *
	 * @return void
	 */
	private function build(): void {
		if ( array() !== $this->modules ) {
			return;
		}

		$classes = array(
			Cleanup::class,
			Heartbeat::class,
			Scripts::class,
			Resource_Hints::class,
			Fonts::class,
			Images::class,
			Lcp::class,
			Lazy_YouTube::class,
			Css::class,
			WooCommerce::class,
			Elementor::class,
			Database::class,
			Cache::class,
			Cdn::class,
		);

		/**
		 * Filter the list of module classes before instantiation.
		 *
		 * @param string[] $classes Fully-qualified class names.
		 */
		$classes = (array) apply_filters( 'upo_module_classes', $classes );

		foreach ( $classes as $class ) {
			if ( ! class_exists( $class ) ) {
				continue;
			}
			/** @var Abstract_Module $instance */
			$instance                       = new $class( $this->settings, $this->env, $this->log );
			$this->modules[ $instance->id() ] = $instance;
		}
	}

	/**
	 * Register every applicable module.
	 *
	 * @return void
	 */
	public function register(): void {
		$this->build();

		$safe_mode = $this->settings->is_enabled( 'safe_mode' );

		foreach ( $this->modules as $module ) {
			// Safe Mode suspends frontend-affecting modules on the frontend only.
			if ( $safe_mode && $module->affects_frontend() && ! is_admin() ) {
				continue;
			}
			$module->register();
		}

		if ( $safe_mode ) {
			$this->log->warning( 'Safe Mode is active — frontend optimizations are suspended.', 'safe_mode' );
		}
	}

	/**
	 * Get a module by id.
	 *
	 * @param string $id Module id.
	 * @return Abstract_Module|null
	 */
	public function get( string $id ): ?Abstract_Module {
		$this->build();
		return $this->modules[ $id ] ?? null;
	}

	/**
	 * Get all modules.
	 *
	 * @return array<string, Abstract_Module>
	 */
	public function all(): array {
		$this->build();
		return $this->modules;
	}
}
