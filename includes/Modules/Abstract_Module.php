<?php
/**
 * Base class every optimization module extends.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Modules;

use UPO\Settings\Settings;
use UPO\Support\Environment;
use UPO\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides shared dependencies and a small contract for modules.
 */
abstract class Abstract_Module {

	/**
	 * Settings gateway.
	 *
	 * @var Settings
	 */
	protected Settings $settings;

	/**
	 * Environment detector.
	 *
	 * @var Environment
	 */
	protected Environment $env;

	/**
	 * Logger.
	 *
	 * @var Logger
	 */
	protected Logger $log;

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
	 * Unique module id.
	 *
	 * @return string
	 */
	abstract public function id(): string;

	/**
	 * Attach the module's WordPress hooks.
	 *
	 * @return void
	 */
	abstract public function register(): void;

	/**
	 * Whether this module changes frontend output.
	 *
	 * Modules that do are suspended while Safe Mode is on.
	 *
	 * @return bool
	 */
	public function affects_frontend(): bool {
		return true;
	}

	/**
	 * Shortcut to a boolean setting.
	 *
	 * @param string $key Field id.
	 * @return bool
	 */
	protected function enabled( string $key ): bool {
		return $this->settings->is_enabled( $key );
	}
}
