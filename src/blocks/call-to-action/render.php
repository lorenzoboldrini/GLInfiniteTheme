<?php
/**
 * Server-side render for the glinf/call-to-action block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;

// Read raw attributes defensively: never trust their type or value.
$glinf_title       = isset( $attributes['title'] ) && is_string( $attributes['title'] ) ? trim( $attributes['title'] ) : '';
$glinf_description = isset( $attributes['description'] ) && is_string( $attributes['description'] ) ? trim( $attributes['description'] ) : '';
$glinf_button_text = isset( $attributes['buttonText'] ) && is_string( $attributes['buttonText'] ) ? trim( $attributes['buttonText'] ) : '';
$glinf_button_url  = isset( $attributes['buttonUrl'] ) && is_string( $attributes['buttonUrl'] ) ? esc_url( trim( $attributes['buttonUrl'] ) ) : '';

// Whitelist the heading level: the tag name is built only from a validated integer.
$glinf_title_level = isset( $attributes['titleLevel'] ) ? absint( $attributes['titleLevel'] ) : 2;
if ( ! in_array( $glinf_title_level, array( 2, 3, 4, 5, 6 ), true ) ) {
	$glinf_title_level = 2;
}
$glinf_title_tag = 'h' . $glinf_title_level;

// Whitelist the text alignment.
$glinf_text_align = isset( $attributes['textAlign'] ) && is_string( $attributes['textAlign'] ) ? $attributes['textAlign'] : 'center';
if ( ! in_array( $glinf_text_align, array( 'left', 'center', 'right' ), true ) ) {
	$glinf_text_align = 'center';
}

// Ignore markup-only values (e.g. a lone <br>) so empty headings/paragraphs are never printed.
$glinf_has_title       = '' !== trim( wp_strip_all_tags( $glinf_title ) );
$glinf_has_description = '' !== trim( wp_strip_all_tags( $glinf_description ) );
$glinf_has_button      = '' !== trim( wp_strip_all_tags( $glinf_button_text ) ) && '' !== $glinf_button_url;

// Nothing to show: print nothing at all.
if ( ! $glinf_has_title && ! $glinf_has_description && ! $glinf_has_button ) {
	return;
}

$glinf_wrapper_attributes = get_block_wrapper_attributes(
	array( 'class' => 'has-text-align-' . $glinf_text_align )
);
?>
<div <?php echo $glinf_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php if ( $glinf_has_title ) : ?>
		<<?php echo tag_escape( $glinf_title_tag ); ?> class="glinf-call-to-action__title"><?php echo wp_kses_post( $glinf_title ); ?></<?php echo tag_escape( $glinf_title_tag ); ?>>
	<?php endif; ?>

	<?php if ( $glinf_has_description ) : ?>
		<p class="glinf-call-to-action__description"><?php echo wp_kses_post( $glinf_description ); ?></p>
	<?php endif; ?>

	<?php if ( $glinf_has_button ) : ?>
		<a class="wp-element-button glinf-call-to-action__button" href="<?php echo esc_url( $glinf_button_url ); ?>"><?php echo esc_html( $glinf_button_text ); ?></a>
	<?php endif; ?>
</div>
