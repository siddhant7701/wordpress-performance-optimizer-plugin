<?php
/**
 * Fonts tab extras: honest note about local self-hosting.
 *
 * @package UPO
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="upo-callout upo-callout--info">
	<h4><?php esc_html_e( 'About hosting Google Fonts locally', 'ultimate-performance-optimizer' ); ?></h4>
	<p>
		<?php esc_html_e( 'Downloading and rewriting every Google Fonts request to a local copy is invasive and easy to get wrong across themes and page builders. Instead, this tab applies the safe, high-impact wins: force font-display: swap (removes invisible text), preconnect to the font hosts, and preload the specific fonts you list.', 'ultimate-performance-optimizer' ); ?>
	</p>
	<p>
		<?php esc_html_e( 'For full local hosting, use your theme’s built-in local fonts option (GeneratePress, Kadence, Blocksy and Astra all provide one) or a dedicated local-fonts plugin, then preload the resulting woff2 files here.', 'ultimate-performance-optimizer' ); ?>
	</p>
</div>
