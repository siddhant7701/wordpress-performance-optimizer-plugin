<?php
/**
 * Environment requirement checker.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Verifies the runtime meets the minimum PHP and WordPress versions.
 *
 * This runs before the autoloader so it deliberately avoids any dependency
 * on the rest of the plugin.
 */
final class Requirements {

	/**
	 * Minimum required PHP version.
	 *
	 * @var string
	 */
	private string $min_php;

	/**
	 * Minimum required WordPress version.
	 *
	 * @var string
	 */
	private string $min_wp;

	/**
	 * A human readable list of unmet requirements.
	 *
	 * @var string[]
	 */
	private array $errors = array();

	/**
	 * Constructor.
	 *
	 * @param string $min_php Minimum PHP version.
	 * @param string $min_wp  Minimum WordPress version.
	 */
	public function __construct( string $min_php, string $min_wp ) {
		$this->min_php = $min_php;
		$this->min_wp  = $min_wp;
	}

	/**
	 * Whether every requirement is satisfied.
	 *
	 * @return bool
	 */
	public function are_met(): bool {
		global $wp_version;

		if ( version_compare( PHP_VERSION, $this->min_php, '<' ) ) {
			$this->errors[] = sprintf(
				/* translators: 1: required PHP version, 2: current PHP version. */
				__( 'Organic Kratom USA Performance Optimizer requires PHP %1$s or higher. You are running %2$s.', 'ultimate-performance-optimizer' ),
				$this->min_php,
				PHP_VERSION
			);
		}

		if ( isset( $wp_version ) && version_compare( $wp_version, $this->min_wp, '<' ) ) {
			$this->errors[] = sprintf(
				/* translators: 1: required WordPress version, 2: current WordPress version. */
				__( 'Organic Kratom USA Performance Optimizer requires WordPress %1$s or higher. You are running %2$s.', 'ultimate-performance-optimizer' ),
				$this->min_wp,
				$wp_version
			);
		}

		return array() === $this->errors;
	}

	/**
	 * Print an admin notice describing the unmet requirements.
	 *
	 * @return void
	 */
	public function render_notice(): void {
		$errors = $this->errors;

		add_action( 'admin_notices', static function () use ( $errors ): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-error"><p><strong>';
			echo esc_html__( 'Organic Kratom USA Performance Optimizer could not start:', 'ultimate-performance-optimizer' );
			echo '</strong></p><ul style="list-style:disc;margin-left:20px;">';
			foreach ( $errors as $error ) {
				echo '<li>' . esc_html( $error ) . '</li>';
			}
			echo '</ul></div>';
		} );
	}
}
