<?php
/**
 * Title: {{title}}
 * Slug: {{namespace}}/page-{{slug}}
 * Description: {{title}} page preset.
 * Categories: {{category}}
 * Keywords: page, preset, {{slug}}
 * Viewport Width: 1280
 * Block Types: core/post-content
 * Post Types: page
 * Inserter: true
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:group {"tagName":"section","layout":{"type":"constrained"}} -->
<section class="wp-block-group">
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading"><?php echo esc_html_x( 'Page title', 'Page preset placeholder', '{{textdomain}}' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p><?php echo esc_html_x( 'A short introduction that explains what this page is about.', 'Page preset placeholder', '{{textdomain}}' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<!-- wp:button -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"><?php echo esc_html_x( 'Call to action', 'Page preset placeholder', '{{textdomain}}' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","layout":{"type":"constrained"}} -->
<section class="wp-block-group">
	<!-- wp:heading -->
	<h2 class="wp-block-heading"><?php echo esc_html_x( 'Section title', 'Page preset placeholder', '{{textdomain}}' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p><?php echo esc_html_x( 'Section content goes here.', 'Page preset placeholder', '{{textdomain}}' ); ?></p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
