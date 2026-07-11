<?php
/**
 * CSS tab extras: an honest explanation of what we do and do not do.
 *
 * @package UPO
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="upo-callout upo-callout--info">
	<h4><?php esc_html_e( 'About Critical CSS and Unused CSS', 'ultimate-performance-optimizer' ); ?></h4>
	<p>
		<?php esc_html_e( 'True Critical CSS generation and safe Unused CSS removal require rendering each page in a real browser to measure what is actually used. That cannot be done reliably in PHP alone, and doing it badly breaks layouts. We therefore do not fake these features.', 'ultimate-performance-optimizer' ); ?>
	</p>
	<p>
		<?php esc_html_e( 'What this tab does safely: minify inline <style> blocks. For genuine Critical/Unused CSS, use a rendering-based service (for example the features built into WP Rocket, LiteSpeed Cache, or a dedicated Critical CSS tool) and paste the result into your theme.', 'ultimate-performance-optimizer' ); ?>
	</p>
</div>
