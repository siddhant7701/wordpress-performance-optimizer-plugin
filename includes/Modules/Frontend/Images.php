<?php
/**
 * Image loading optimization.
 *
 * @package UPO
 */

declare( strict_types=1 );

namespace UPO\Modules\Frontend;

use UPO\Modules\Abstract_Module;
use UPO\Support\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reinforces native lazy loading, adds decoding="async", and hints the first
 * in-content image as the LCP candidate. Everything defers to what WordPress
 * core already emits (6.3+ adds fetchpriority itself) to avoid duplicates.
 */
final class Images extends Abstract_Module {

	/**
	 * Whether the first content image has been processed.
	 *
	 * @var bool
	 */
	private bool $first_image_done = false;

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function id(): string {
		return 'images';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function register(): void {
		if ( $this->enabled( 'img_async_decoding' ) || $this->enabled( 'img_fetchpriority_lcp' ) || $this->enabled( 'img_lazy_load' ) ) {
			add_filter( 'wp_content_img_tag', array( $this, 'filter_content_image' ), 20, 3 );
		}
		if ( $this->enabled( 'img_async_decoding' ) ) {
			add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_decoding_attr' ), 20 );
		}
	}

	/**
	 * Adjust a single content image tag.
	 *
	 * @param string $filtered_image The image tag.
	 * @param string $context        Context (e.g. 'the_content').
	 * @param int    $attachment_id  Attachment id.
	 * @return string
	 */
	public function filter_content_image( $filtered_image, $context, $attachment_id ): string {
		unset( $context, $attachment_id );
		$img = (string) $filtered_image;

		if ( ! Helpers::is_frontend_optimizable() ) {
			return $img;
		}

		// decoding="async".
		if ( $this->enabled( 'img_async_decoding' ) && false === stripos( $img, ' decoding=' ) ) {
			$img = preg_replace( '/<img\s/i', '<img decoding="async" ', $img, 1 ) ?? $img;
		}

		$is_first = ! $this->first_image_done;
		$this->first_image_done = true;

		// fetchpriority on the first image, only if it is actually a plausible LCP
		// candidate. Blindly promoting the first content <img> (often a tiny logo
		// or thumbnail) *hurts* LCP by stealing bandwidth from the real hero, so
		// we guard it carefully — and never fight a dedicated LCP preload.
		if ( $is_first && $this->enabled( 'img_fetchpriority_lcp' ) && $this->should_prioritize( $img ) ) {
			// The LCP image should not be lazy.
			$img = preg_replace( '/\sloading\s*=\s*(["\'])lazy\1/i', '', $img ) ?? $img;
			if ( false === stripos( $img, 'fetchpriority=' ) ) {
				$img = preg_replace( '/<img\s/i', '<img fetchpriority="high" ', $img, 1 ) ?? $img;
			}
		} elseif ( $this->enabled( 'img_lazy_load' ) && ! $is_first && false === stripos( $img, ' loading=' ) ) {
			// Reinforce native lazy loading on below-the-fold images.
			$img = preg_replace( '/<img\s/i', '<img loading="lazy" ', $img, 1 ) ?? $img;
		}

		return $img;
	}

	/**
	 * Decide whether the first content image is a sensible LCP candidate to
	 * prioritise. Conservative by design — a wrong guess makes LCP worse.
	 *
	 * @param string $img The image tag.
	 * @return bool
	 */
	private function should_prioritize( string $img ): bool {
		// A dedicated LCP preload (manual URL or auto background detection) owns
		// the priority hint. Adding a second one competes for bandwidth.
		if ( '' !== trim( (string) $this->settings->get( 'preload_lcp_image', '' ) ) || $this->enabled( 'lcp_auto_preload' ) ) {
			return false;
		}

		// Skip vector icons / SVGs — they are never the LCP.
		if ( preg_match( '/\.svg\b/i', $img ) ) {
			return false;
		}

		// Skip explicitly small images (icons, thumbnails, tracking pixels).
		if ( preg_match( '/\swidth\s*=\s*(["\'])(\d+)\1/i', $img, $m ) && (int) $m[2] < 200 ) {
			return false;
		}

		return true;
	}

	/**
	 * Add decoding="async" to template attachment images.
	 *
	 * @param array<string, string> $attr Attributes.
	 * @return array<string, string>
	 */
	public function add_decoding_attr( $attr ): array {
		$attr = is_array( $attr ) ? $attr : array();
		if ( ! isset( $attr['decoding'] ) ) {
			$attr['decoding'] = 'async';
		}
		return $attr;
	}
}
