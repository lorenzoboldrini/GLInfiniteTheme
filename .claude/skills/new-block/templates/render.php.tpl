<?php
/**
 * Server-side render for the {{namespace}}/{{slug}} block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php esc_html_e( 'Block content goes here.', '{{textdomain}}' ); ?>
</div>
