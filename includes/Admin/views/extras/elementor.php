<?php
/**
 * Elementor tab extras: availability + recommended native experiments.
 *
 * @package UPO
 *
 * @var \UPO\Support\Environment $env
 * @var \UPO\Settings\Settings   $settings
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $env->has_elementor() ) :
	?>
	<div class="upo-callout upo-callout--warn">
		<p><?php esc_html_e( 'Elementor is not active. These options apply only when Elementor (or Elementor Pro) is installed.', 'ultimate-performance-optimizer' ); ?></p>
	</div>
	<?php
	return;
endif;

if ( $settings->is_enabled( 'elementor_recommend_experiments' ) ) :
	?>
	<div class="upo-callout upo-callout--info">
		<h4><?php esc_html_e( 'Enable these Elementor experiments for the biggest wins', 'ultimate-performance-optimizer' ); ?></h4>
		<p><?php esc_html_e( 'Elementor’s own performance features are safer and more effective than forcibly stripping its assets. In Elementor → Settings → Features, turn on:', 'ultimate-performance-optimizer' ); ?></p>
		<ul class="upo-list">
			<li><?php esc_html_e( 'Improved CSS Loading', 'ultimate-performance-optimizer' ); ?></li>
			<li><?php esc_html_e( 'Optimized DOM Output', 'ultimate-performance-optimizer' ); ?></li>
			<li><?php esc_html_e( 'Inline Font Icons', 'ultimate-performance-optimizer' ); ?></li>
			<li><?php esc_html_e( 'Lazy Load Background Images', 'ultimate-performance-optimizer' ); ?></li>
		</ul>
	</div>
	<?php
endif;
