<?php
/**
 * Elementor-specific optimizations.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Modules\Elementor;

use UPO\Modules\Abstract_Module;
use UPO\Support\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reduces Elementor's frontend footprint conservatively.
 *
 * Elementor's own experiments (Improved CSS Loading, Optimized DOM Output,
 * Inline Font Icons) are the safest wins and are surfaced as recommendations
 * in the admin rather than forced here.
 */
final class Elementor extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'elementor';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->env->has_elementor() ) {
			return;
		}

		if ( $this->enabled( 'elementor_disable_google_fonts' ) ) {
			// Tell Elementor to stop enqueuing Google Fonts.
			add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
		}

		if ( $this->enabled( 'elementor_disable_fa_eicons_guests' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_defer_eicons' ), 99 );
		}
	}

	/**
	 * Defer Elementor's eicons stylesheet for logged-out visitors on pages that
	 * do not render Elementor content.
	 *
	 * @return void
	 */
	public function maybe_defer_eicons(): void {
		if ( is_user_logged_in() || ! Helpers::is_frontend_optimizable() ) {
			return;
		}
		// Keep icons if the current page was built with Elementor.
		$post_id = get_queried_object_id();
		if ( $post_id && get_post_meta( $post_id, '_elementor_edit_mode', true ) ) {
			return;
		}
		wp_dequeue_style( 'elementor-icons-fa-solid' );
		wp_dequeue_style( 'elementor-icons-fa-regular' );
		wp_dequeue_style( 'elementor-icons-fa-brands' );
	}
}
