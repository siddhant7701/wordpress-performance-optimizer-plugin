<?php
/**
 * CDN tab extras: provider hint and safety note.
 *
 * @package UPO
 *
 * @var \UPO\Settings\Settings $settings
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$upo_provider = (string) $settings->get( 'cdn_provider', 'custom' );
$upo_hints    = array(
	'cloudflare' => __( 'Cloudflare works as a reverse proxy — you usually do NOT need URL rewriting. Leave rewriting off unless you use Cloudflare with a separate pull-zone hostname.', 'ultimate-performance-optimizer' ),
	'bunnycdn'   => __( 'Enter your BunnyCDN pull-zone hostname, e.g. https://yoursite.b-cdn.net', 'ultimate-performance-optimizer' ),
	'cloudfront' => __( 'Enter your CloudFront distribution domain, e.g. https://d1234.cloudfront.net', 'ultimate-performance-optimizer' ),
	'keycdn'     => __( 'Enter your KeyCDN zone URL, e.g. https://yoursite-hash.kxcdn.com', 'ultimate-performance-optimizer' ),
	'custom'     => __( 'Enter the full URL of your CDN/pull-zone hostname. It must already mirror your origin.', 'ultimate-performance-optimizer' ),
);
?>
<div class="upo-callout upo-callout--info">
	<p><?php echo esc_html( $upo_hints[ $upo_provider ] ?? $upo_hints['custom'] ); ?></p>
	<p><?php esc_html_e( 'Rewriting only touches enqueued CSS/JS, theme assets, attachment URLs and srcset. Admin URLs and excluded fragments are never rewritten.', 'ultimate-performance-optimizer' ); ?></p>
</div>
