<?php
/**
 * Title: Footer credits
 * Slug: glinf/footer-credits
 * Description: Copyright line used by the footer template part.
 * Inserter: false
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><?php
	printf(
		/* translators: 1: current year, 2: site name. */
		esc_html__( '© %1$s %2$s', 'gl-infinite-theme' ),
		esc_html( wp_date( 'Y' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
?></p>
<!-- /wp:paragraph -->
