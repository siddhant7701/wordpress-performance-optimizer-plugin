<?php
/**
 * WooCommerce tab extras: availability status and tips.
 *
 * @package UPO
 *
 * @var \UPO\Support\Environment $env
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $env->has_woocommerce() ) :
	?>
	<div class="upo-callout upo-callout--warn">
		<p><?php esc_html_e( 'WooCommerce is not active on this site. These options will only take effect once WooCommerce is installed and activated.', 'ultimate-performance-optimizer' ); ?></p>
	</div>
	<?php
	return;
endif;
?>
<div class="upo-callout upo-callout--info">
	<p><?php esc_html_e( 'These optimizations detect real cart/checkout usage before unloading anything, so live cart totals and blocks keep working. If a header mini-cart stops updating, disable “cart fragments”.', 'ultimate-performance-optimizer' ); ?></p>
</div>
