<?php
/**
 * Admin page footer.
 *
 * @package UPO
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="upo-footer">
	<p>
		<?php
		printf(
			/* translators: 1: plugin name, 2: version. */
			esc_html__( '%1$s v%2$s — built for safe, real-world performance gains.', 'ultimate-performance-optimizer' ),
			'<strong>' . esc_html( UPO_NAME ) . '</strong>',
			esc_html( UPO_VERSION )
		);
		?>
	</p>
	<p class="upo-muted">
		<?php
		printf(
			/* translators: 1: author link, 2: portfolio link, 3: GitHub link. */
			wp_kses(
				__( 'Made by %1$s &middot; %2$s &middot; %3$s', 'ultimate-performance-optimizer' ),
				array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) )
			),
			'<a href="' . esc_url( UPO_AUTHOR_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( UPO_AUTHOR ) . '</a>',
			'<a href="' . esc_url( UPO_AUTHOR_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Portfolio', 'ultimate-performance-optimizer' ) . '</a>',
			'<a href="' . esc_url( UPO_GITHUB_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'GitHub', 'ultimate-performance-optimizer' ) . '</a>'
		);
		?>
	</p>
</div>
