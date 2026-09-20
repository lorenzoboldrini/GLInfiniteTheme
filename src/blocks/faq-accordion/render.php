<?php
/**
 * Server-side render for the glinf/faq-accordion block.
 *
 * A list of questions built on the native <details> element: it opens and
 * closes with click, Enter and Space with no JavaScript. The questions are
 * the glinf/faq-item inner blocks, already rendered in $content. Every
 * attribute is validated here: the editor input is never trusted (the block
 * can also be edited by hand in the code editor).
 *
 * Exclusive mode ("allowMultiple" off): the native "name" attribute of
 * <details> makes the browser keep only one item of a group open, again with
 * no script. The group name has to be unique per accordion and per request,
 * and the parent is rendered after its children, so the name is written on
 * the already-rendered items with the HTML API (no regex). Each item prints a
 * "data-glinf-faq-item" marker and the accordion removes it after use: a
 * nested accordion has already taken and cleaned its own items when the outer
 * one runs, so each accordion only ever touches its own items.
 *
 * The heading level of the questions is not printed here: it travels to the
 * items through the block context (see providesContext in block.json), which
 * also keeps the levels of nested accordions independent.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;

// Flag: only a real false turns exclusive mode on; anything else keeps the default (several items can be open).
$glinf_allow_multiple = ! isset( $attributes['allowMultiple'] ) || false !== $attributes['allowMultiple'];

// Accessible name of the group: plain text, capped. No default: without a label there is no role and no aria-label.
$glinf_label = isset( $attributes['ariaLabel'] ) && is_string( $attributes['ariaLabel'] ) ? trim( wp_html_excerpt( $attributes['ariaLabel'], 200 ) ) : '';

$glinf_item_query = array(
	'tag_name'   => 'DETAILS',
	'class_name' => 'wp-block-glinf-faq-item',
);

// Count our items. Nested accordions have already removed the markers of their own.
$glinf_total     = 0;
$glinf_processor = new WP_HTML_Tag_Processor( $content );
while ( $glinf_processor->next_tag( $glinf_item_query ) ) {
	if ( null !== $glinf_processor->get_attribute( 'data-glinf-faq-item' ) ) {
		++$glinf_total;
	}
}

// An accordion without questions prints nothing at all.
if ( $glinf_total < 1 ) {
	return;
}

// Group name: unique per accordion, also when the same accordion is rendered twice in a page.
$glinf_group    = $glinf_allow_multiple ? '' : wp_unique_id( 'glinf-faq-group-' );
$glinf_has_open = false;

$glinf_processor = new WP_HTML_Tag_Processor( $content );
while ( $glinf_processor->next_tag( $glinf_item_query ) ) {
	if ( null === $glinf_processor->get_attribute( 'data-glinf-faq-item' ) ) {
		continue;
	}
	$glinf_processor->remove_attribute( 'data-glinf-faq-item' );

	if ( $glinf_allow_multiple ) {
		continue;
	}

	$glinf_processor->set_attribute( 'name', $glinf_group );

	// Two items marked "open" would fight over the group: the first one wins.
	if ( null !== $glinf_processor->get_attribute( 'open' ) ) {
		if ( $glinf_has_open ) {
			$glinf_processor->remove_attribute( 'open' );
		}
		$glinf_has_open = true;
	}
}
$glinf_items = $glinf_processor->get_updated_html();

/*
 * A named group is only useful to assistive technology when it has a name:
 * role="group" without a label would be noise, and aria-label on a plain
 * <div> is not allowed.
 */
$glinf_extra_attributes = array();
if ( '' !== $glinf_label ) {
	$glinf_extra_attributes['role']       = 'group';
	$glinf_extra_attributes['aria-label'] = $glinf_label;
}

$glinf_wrapper_attributes = get_block_wrapper_attributes( $glinf_extra_attributes );
?>
<div <?php echo $glinf_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php echo $glinf_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already-rendered inner blocks (each item escapes its own output), only attributes set by the HTML API added. ?>
</div>
