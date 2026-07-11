<?php
/**
 * Lightweight PSR-4 autoloader (no Composer dependency).
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps the plugin's root namespace to its includes/ directory.
 *
 * Example: UPO\Modules\Frontend\Cleanup  ->  includes/Modules/Frontend/Cleanup.php
 */
final class Autoloader {

	/**
	 * Root namespace prefix, e.g. "UPO".
	 *
	 * @var string
	 */
	private string $prefix;

	/**
	 * Base directory for the namespace prefix (with trailing slash).
	 *
	 * @var string
	 */
	private string $base_dir;

	/**
	 * Constructor.
	 *
	 * @param string $prefix   Root namespace.
	 * @param string $base_dir Base directory.
	 */
	private function __construct( string $prefix, string $base_dir ) {
		$this->prefix   = trim( $prefix, '\\' ) . '\\';
		$this->base_dir = rtrim( $base_dir, '/\\' ) . '/';
	}

	/**
	 * Register the autoloader with SPL.
	 *
	 * @param string $prefix   Root namespace.
	 * @param string $base_dir Base directory.
	 * @return void
	 */
	public static function register( string $prefix, string $base_dir ): void {
		$loader = new self( $prefix, $base_dir );
		spl_autoload_register( array( $loader, 'load' ) );
	}

	/**
	 * Attempt to load the file for a given class name.
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return void
	 */
	public function load( string $class_name ): void {
		if ( 0 !== strncmp( $this->prefix, $class_name, strlen( $this->prefix ) ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $this->prefix ) );
		$relative = str_replace( '\\', '/', $relative );
		$file     = $this->base_dir . $relative . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
