<?php
/**
 * WooCommerce-specific optimizations.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Modules\WooCommerce;

use UPO\Modules\Abstract_Module;
use UPO\Support\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trims WooCommerce assets and requests on pages that do not need them.
 *
 * Each optimization is guarded so it only ever runs when WooCommerce is active
 * and only on pages where the affected feature is genuinely unused.
 */
final class WooCommerce extends Abstract_Module {

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'woocommerce';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->env->has_woocommerce() ) {
			return;
		}

		if ( $this->enabled( 'woo_disable_cart_fragments' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_disable_cart_fragments' ), 99 );
		}
		if ( $this->enabled( 'woo_unload_assets' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_unload_assets' ), 99 );
		}
		if ( $this->enabled( 'woo_disable_block_styles' ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'maybe_disable_block_styles' ), 99 );
		}
		if ( $this->enabled( 'woo_disable_password_meter' ) ) {
			add_action( 'wp_print_scripts', array( $this, 'maybe_disable_password_meter' ), 100 );
		}
	}

	/**
	 * Whether the current request is a WooCommerce-critical page.
	 *
	 * @return bool
	 */
	private function is_woo_page(): bool {
		if ( is_admin() ) {
			return true;
		}
		$is = ( function_exists( 'is_woocommerce' ) && is_woocommerce() )
			|| ( function_exists( 'is_cart' ) && is_cart() )
			|| ( function_exists( 'is_checkout' ) && is_checkout() )
			|| ( function_exists( 'is_account_page' ) && is_account_page() );

		/**
		 * Allow overriding WooCommerce page detection (e.g. custom shortcodes).
		 *
		 * @param bool $is Whether this is a WooCommerce page.
		 */
		return (bool) apply_filters( 'upo_is_woocommerce_page', $is );
	}

	/**
	 * Detect a cart/mini-cart on the current page so we never break live totals.
	 *
	 * @return bool
	 */
	private function page_has_cart(): bool {
		if ( $this->is_woo_page() ) {
			return true;
		}
		$post = get_post();
		if ( $post instanceof \WP_Post ) {
			$content = (string) $post->post_content;
			if ( has_shortcode( $content, 'woocommerce_cart' )
				|| has_block( 'woocommerce/cart', $post )
				|| has_block( 'woocommerce/mini-cart', $post )
				|| false !== strpos( $content, 'mini-cart' ) ) {
				return true;
			}
		}
		if ( is_active_widget( false, false, 'woocommerce_widget_cart', true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Dequeue the cart-fragments script when no cart is present.
	 *
	 * @return void
	 */
	public function maybe_disable_cart_fragments(): void {
		if ( $this->page_has_cart() || ! Helpers::is_frontend_optimizable() ) {
			return;
		}
		wp_dequeue_script( 'wc-cart-fragments' );
	}

	/**
	 * Dequeue WooCommerce CSS/JS on non-shop pages.
	 *
	 * @return void
	 */
	public function maybe_unload_assets(): void {
		if ( $this->is_woo_page() || $this->page_has_cart() || ! Helpers::is_frontend_optimizable() ) {
			return;
		}

		$styles = array( 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen', 'wc-blocks-style' );
		foreach ( $styles as $handle ) {
			wp_dequeue_style( $handle );
		}

		$scripts = array( 'woocommerce', 'wc-cart-fragments', 'wc-add-to-cart', 'woocommerce-general' );
		foreach ( $scripts as $handle ) {
			wp_dequeue_script( $handle );
		}
	}

	/**
	 * Remove WooCommerce block styles when Woo blocks are not on the page.
	 *
	 * @return void
	 */
	public function maybe_disable_block_styles(): void {
		if ( $this->is_woo_page() || ! Helpers::is_frontend_optimizable() ) {
			return;
		}
		$post = get_post();
		if ( $post instanceof \WP_Post && false !== strpos( (string) $post->post_content, 'wp:woocommerce' ) ) {
			return;
		}
		wp_dequeue_style( 'wc-blocks-style' );
		wp_dequeue_style( 'wc-blocks-vendors-style' );
	}

	/**
	 * Dequeue the password strength meter off account pages.
	 *
	 * @return void
	 */
	public function maybe_disable_password_meter(): void {
		if ( is_admin() ) {
			return;
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return;
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() ) {
			return;
		}
		wp_dequeue_script( 'wc-password-strength-meter' );
		wp_dequeue_script( 'zxcvbn-async' );
	}
}
