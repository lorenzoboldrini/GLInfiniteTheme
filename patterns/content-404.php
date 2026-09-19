<?php
/**
 * Title: 404 content
 * Slug: glinf/content-404
 * Description: Content of the 404 template.
 * Inserter: false
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading"><?php echo esc_html_x( 'Page not found', '404 template heading', 'gl-infinite-theme' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html_x( 'The page you are looking for does not exist or has been moved. Try a search instead.', '404 template text', 'gl-infinite-theme' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:search {"showLabel":false} /-->
