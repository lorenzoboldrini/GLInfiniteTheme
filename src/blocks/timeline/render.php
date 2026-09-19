<?php
/**
 * Server-side render for the glinf/timeline block.
 *
 * A sequence of steps as an ordered list: <ol> > <li>. The list already tells
 * screen readers "list, N items" and "item X of N", so the line and the
 * markers are decorative (the item block prints them as aria-hidden) and the
 * step number, when shown, is a CSS counter inside that aria-hidden marker:
 * it is never read twice. The items are the glinf/timeline-item inner blocks,
 * already rendered in $content. Every attribute is validated here: the editor
 * input is never trusted (the block can also be edited by hand in the code
 * editor). The heading level of the item titles is not printed here: it travels
 * to the items through the block context (see providesContext in block.json).
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;

// Layout: whitelist only, anything else is the default vertical layout.
$glinf_layout = isset( $attributes['layoutStyle'] ) && is_string( $attributes['layoutStyle'] ) && 'alternating' === $attributes['layoutStyle'] ? 'alternating' : 'vertical';

// Flag: anything but a real boolean falls back to the default.
$glinf_numbered = isset( $attributes['numbered'] ) && true === $attributes['numbered'];

// Accessible name of the list: plain text, capped. No default: without a label there is no aria-label.
$glinf_label = isset( $attributes['ariaLabel'] ) && is_string( $attributes['ariaLabel'] ) ? trim( wp_html_excerpt( $attributes['ariaLabel'], 200 ) ) : '';

/*
 * A timeline without steps prints nothing at all (an empty <ol> is noise for
 * screen readers). The HTML API looks for the first item: nested timelines
 * only exist inside an item, so "any item" is the same as "an item of ours".
 */
$glinf_processor = new WP_HTML_Tag_Processor( $content );
if ( ! $glinf_processor->next_tag(
	array(
		'tag_name'   => 'LI',
		'class_name' => 'wp-block-glinf-timeline-item',
	)
) ) {
	return;
}

$glinf_classes = array( 'glinf-timeline--' . $glinf_layout );
if ( $glinf_numbered ) {
	$glinf_classes[] = 'glinf-timeline--numbered';
}

$glinf_wrapper_attributes = get_block_wrapper_attributes(
	array( 'class' => implode( ' ', $glinf_classes ) )
);
?>
<div <?php echo $glinf_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php
	/*
	 * role="list" is explicit on purpose: with "list-style: none" Safari and
	 * VoiceOver stop announcing the list, and the sequence would be lost.
	 */
	?>
	<ol class="glinf-timeline__list" role="list"<?php echo '' !== $glinf_label ? ' aria-label="' . esc_attr( $glinf_label ) . '"' : ''; ?>>
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already-rendered inner blocks (each item escapes its own output). ?>
	</ol>
</div>
