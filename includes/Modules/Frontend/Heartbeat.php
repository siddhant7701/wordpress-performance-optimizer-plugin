<?php
/**
 * Heartbeat API control and revision/autosave tuning.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Modules\Frontend;

use UPO\Modules\Abstract_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reduces admin-ajax.php traffic from the Heartbeat API and trims revision
 * churn. These are backend concerns, so they stay active in Safe Mode.
 */
final class Heartbeat extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'heartbeat';
	}

	/**
	 * These changes are admin/back-end oriented, so keep them out of Safe Mode.
	 *
	 * @return bool
	 */
	public function affects_frontend(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function register(): void {
		$mode = (string) $this->settings->get( 'heartbeat_mode', 'optimize' );

		if ( 'disable' === $mode || $this->enabled( 'heartbeat_disable_frontend' ) ) {
			add_action( 'init', array( $this, 'maybe_disable_heartbeat' ), 1 );
		}
		if ( 'optimize' === $mode ) {
			add_filter( 'heartbeat_settings', array( $this, 'set_interval' ) );
		}

		if ( $this->enabled( 'limit_revisions' ) ) {
			add_filter( 'wp_revisions_to_keep', array( $this, 'limit_revisions' ), 10, 2 );
		}

		$autosave = (int) $this->settings->get( 'autosave_interval', 0 );
		if ( $autosave > 0 ) {
			add_filter( 'wp_default_scripts', array( $this, 'set_autosave_interval' ) );
		}
	}

	/**
	 * Disable Heartbeat where it is not needed.
	 *
	 * The post editor keeps Heartbeat because it powers autosave and lock
	 * detection; everywhere else it can go.
	 *
	 * @return void
	 */
	public function maybe_disable_heartbeat(): void {
		$mode   = (string) $this->settings->get( 'heartbeat_mode', 'optimize' );
		$screen = '';

		if ( function_exists( 'get_current_screen' ) ) {
			$current = get_current_screen();
			$screen  = $current->id ?? '';
		}

		// Never touch Heartbeat inside the editor.
		if ( in_array( $screen, array( 'post', 'post-new' ), true ) ) {
			return;
		}

		$disable_frontend = $this->enabled( 'heartbeat_disable_frontend' ) && ! is_admin();
		$disable_all      = 'disable' === $mode;

		if ( $disable_frontend || $disable_all ) {
			wp_deregister_script( 'heartbeat' );
		}
	}

	/**
	 * Slow the Heartbeat interval down.
	 *
	 * @param array<string, mixed> $settings Heartbeat settings.
	 * @return array<string, mixed>
	 */
	public function set_interval( $settings ): array {
		$settings           = is_array( $settings ) ? $settings : array();
		$settings['interval'] = max( 15, min( 300, (int) $this->settings->get( 'heartbeat_interval', 60 ) ) );
		return $settings;
	}

	/**
	 * Cap stored revisions.
	 *
	 * @param int      $num  Current limit.
	 * @param \WP_Post $post Post object.
	 * @return int
	 */
	public function limit_revisions( $num, $post ): int {
		unset( $post );
		return (int) $this->settings->get( 'revisions_to_keep', 5 );
	}

	/**
	 * Increase the autosave interval.
	 *
	 * @param \WP_Scripts $scripts Scripts registry.
	 * @return void
	 */
	public function set_autosave_interval( $scripts ): void {
		if ( ! isset( $scripts->registered['autosave'] ) ) {
			return;
		}
		$interval = max( 15, (int) $this->settings->get( 'autosave_interval', 60 ) );
		$scripts->registered['autosave']->extra['data'] = 'var autosaveL10n = ' . wp_json_encode( array( 'autosaveInterval' => $interval, 'blog_id' => get_current_blog_id() ) ) . ';';
	}
}
