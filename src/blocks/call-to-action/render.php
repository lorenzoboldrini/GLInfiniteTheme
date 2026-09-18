<?php
/**
 * Server-side render for the tu/call-to-action block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;

// Read raw attributes defensively: never trust their type or value.
$tu_title       = isset( $attributes['title'] ) && is_string( $attributes['title'] ) ? trim( $attributes['title'] ) : '';
$tu_description = isset( $attributes['description'] ) && is_string( $attributes['description'] ) ? trim( $attributes['description'] ) : '';
$tu_button_text = isset( $attributes['buttonText'] ) && is_string( $attributes['buttonText'] ) ? trim( $attributes['buttonText'] ) : '';
$tu_button_url  = isset( $attributes['buttonUrl'] ) && is_string( $attributes['buttonUrl'] ) ? esc_url( trim( $attributes['buttonUrl'] ) ) : '';

// Whitelist the heading level: the tag name is built only from a validated integer.
$tu_title_level = isset( $attributes['titleLevel'] ) ? absint( $attributes['titleLevel'] ) : 2;
if ( ! in_array( $tu_title_level, array( 2, 3, 4, 5, 6 ), true ) ) {
	$tu_title_level = 2;
}
$tu_title_tag = 'h' . $tu_title_level;

// Whitelist the text alignment.
$tu_text_align = isset( $attributes['textAlign'] ) && is_string( $attributes['textAlign'] ) ? $attributes['textAlign'] : 'center';
if ( ! in_array( $tu_text_align, array( 'left', 'center', 'right' ), true ) ) {
	$tu_text_align = 'center';
}

// Ignore markup-only values (e.g. a lone <br>) so empty headings/paragraphs are never printed.
$tu_has_title       = '' !== trim( wp_strip_all_tags( $tu_title ) );
$tu_has_description = '' !== trim( wp_strip_all_tags( $tu_description ) );
$tu_has_button      = '' !== trim( wp_strip_all_tags( $tu_button_text ) ) && '' !== $tu_button_url;

// Nothing to show: print nothing at all.
if ( ! $tu_has_title && ! $tu_has_description && ! $tu_has_button ) {
	return;
}

$tu_wrapper_attributes = get_block_wrapper_attributes(
	array( 'class' => 'has-text-align-' . $tu_text_align )
);
?>
<div <?php echo $tu_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php if ( $tu_has_title ) : ?>
		<<?php echo tag_escape( $tu_title_tag ); ?> class="tu-call-to-action__title"><?php echo wp_kses_post( $tu_title ); ?></<?php echo tag_escape( $tu_title_tag ); ?>>
	<?php endif; ?>

	<?php if ( $tu_has_description ) : ?>
		<p class="tu-call-to-action__description"><?php echo wp_kses_post( $tu_description ); ?></p>
	<?php endif; ?>

	<?php if ( $tu_has_button ) : ?>
		<a class="wp-element-button tu-call-to-action__button" href="<?php echo esc_url( $tu_button_url ); ?>"><?php echo esc_html( $tu_button_text ); ?></a>
	<?php endif; ?>
</div>
