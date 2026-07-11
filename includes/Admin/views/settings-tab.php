<?php
/**
 * Generic settings tab: renders schema fields grouped by section, plus any
 * per-tab "extras" partial (reports, snippets, honest limitation notices).
 *
 * @package UPO
 *
 * @var string                 $tab
 * @var \UPO\Settings\Settings $settings
 */

declare( strict_types=1 );

use UPO\Settings\Settings_Schema;
use UPO\Admin\Field_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$upo_schema = Settings_Schema::all();
$upo_def    = $upo_schema[ $tab ] ?? null;

if ( null === $upo_def ) {
	return;
}

$upo_values = $settings->all();

// Human labels for known section ids (fallback: humanized id).
$upo_section_labels = array(
	'wp_head'      => __( 'wp_head cleanup', 'ultimate-performance-optimizer' ),
	'assets'       => __( 'Asset loading', 'ultimate-performance-optimizer' ),
	'security'     => __( 'Security & pings', 'ultimate-performance-optimizer' ),
	'feeds'        => __( 'Feeds', 'ultimate-performance-optimizer' ),
	'heartbeat'    => __( 'Heartbeat API', 'ultimate-performance-optimizer' ),
	'revisions'    => __( 'Revisions & autosave', 'ultimate-performance-optimizer' ),
	'google_fonts' => __( 'Google Fonts', 'ultimate-performance-optimizer' ),
	'preload'      => __( 'Preloading', 'ultimate-performance-optimizer' ),
	'hints'        => __( 'Resource hints', 'ultimate-performance-optimizer' ),
	'loading'      => __( 'Loading behaviour', 'ultimate-performance-optimizer' ),
	'report'       => __( 'Reporting', 'ultimate-performance-optimizer' ),
	'headers'      => __( 'HTTP headers', 'ultimate-performance-optimizer' ),
	'defer'        => __( 'Defer', 'ultimate-performance-optimizer' ),
	'delay'        => __( 'Delay until interaction', 'ultimate-performance-optimizer' ),
	'minify'       => __( 'Minification', 'ultimate-performance-optimizer' ),
	'delivery'     => __( 'Delivery (render-blocking)', 'ultimate-performance-optimizer' ),
	'schedule'     => __( 'Schedule', 'ultimate-performance-optimizer' ),
	'tasks'        => __( 'Cleanup tasks', 'ultimate-performance-optimizer' ),
	'cdn'          => __( 'CDN configuration', 'ultimate-performance-optimizer' ),
	'global'       => __( 'Global controls', 'ultimate-performance-optimizer' ),
);

// Group fields by section, preserving order.
$upo_sections = array();
foreach ( $upo_def['fields'] as $upo_field ) {
	$upo_sections[ (string) $upo_field['section'] ][] = $upo_field;
}

// Per-tab extras partial (rendered above the form).
$upo_extras = UPO_INC . 'Admin/views/extras/' . $tab . '.php';
?>

<div class="upo-panel">
	<div class="upo-panel__head">
		<h2><?php echo esc_html( (string) $upo_def['title'] ); ?></h2>
	</div>

	<?php
	if ( is_readable( $upo_extras ) ) {
		require $upo_extras;
	}
	?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="upo-form">
		<input type="hidden" name="action" value="upo_save_settings">
		<input type="hidden" name="upo_tab" value="<?php echo esc_attr( $tab ); ?>">
		<?php wp_nonce_field( 'upo_save_' . $tab ); ?>

		<?php foreach ( $upo_sections as $upo_section_id => $upo_fields ) : ?>
			<div class="upo-section">
				<?php if ( '' !== $upo_section_id ) : ?>
					<h3 class="upo-section__title">
						<?php
						echo esc_html(
							$upo_section_labels[ $upo_section_id ]
								?? ucwords( str_replace( array( '_', '-' ), ' ', $upo_section_id ) )
						);
						?>
					</h3>
				<?php endif; ?>

				<div class="upo-section__fields">
					<?php
					foreach ( $upo_fields as $upo_field ) {
						$upo_id    = (string) $upo_field['id'];
						$upo_value = $upo_values[ $upo_id ] ?? $upo_field['default'];
						Field_Renderer::render( $upo_field, $upo_value );
					}
					?>
				</div>
			</div>
		<?php endforeach; ?>

		<div class="upo-form__actions">
			<button type="submit" class="button button-primary button-hero">
				<?php esc_html_e( 'Save Changes', 'ultimate-performance-optimizer' ); ?>
			</button>
		</div>
	</form>
</div>
