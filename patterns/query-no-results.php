<?php
/**
 * Title: Query no results
 * Slug: tu/query-no-results
 * Description: Message shown when a query loop has no posts.
 * Inserter: false
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- wp:paragraph -->
<p><?php echo esc_html_x( 'No posts were found.', 'Query loop empty state', 'gl-infinite-theme' ); ?></p>
<!-- /wp:paragraph -->
