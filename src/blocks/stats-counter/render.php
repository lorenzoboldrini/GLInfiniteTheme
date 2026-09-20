<?php
/**
 * Server-side render for the glinf/stats-counter block.
 *
 * A grid of key figures printed as a description list: <dl> > <div> > <dt>
 * (the label) + <dd> (the number). A description list is the native way to say
 * "this term has this value", so a screen reader announces "Happy customers,
 * 1,200+" with the two tied together. The figures are the glinf/stat inner
 * blocks, already rendered in $content: each one prints its real final value
 * as text, so the page is complete without JavaScript, in print and for
 * crawlers; the view module (Interactivity API) only adds the count-up on
 * top. Every attribute is validated here: the editor input is never trusted
 * (the block can also be edited by hand in the code editor). The duration of
 * the count-up is not printed here: it travels to the figures through the
 * block context (see providesContext in block.json).
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;

// Columns: only a numeric value is accepted, then clamped to the range declared in block.json.
$glinf_columns = isset( $attributes['columns'] ) && is_numeric( $attributes['columns'] ) ? min( 6, max( 1, (int) $attributes['columns'] ) ) : 3;

// Accessible name of the group: plain text, capped. No default: without a label there is no role and no aria-label.
$glinf_label = isset( $attributes['ariaLabel'] ) && is_string( $attributes['ariaLabel'] ) ? trim( wp_html_excerpt( $attributes['ariaLabel'], 200 ) ) : '';

/*
 * Count the figures in the rendered markup with the HTML API (no regex).
 * Nested counters only exist inside another block, never inside a figure, so
 * "any figure" is the same as "a figure of ours".
 */
$glinf_total     = 0;
$glinf_processor = new WP_HTML_Tag_Processor( $content );
while ( $glinf_processor->next_tag( array( 'class_name' => 'wp-block-glinf-stat' ) ) ) {
	++$glinf_total;
}

// A counter without figures prints nothing at all (an empty <dl> is noise for screen readers).
if ( $glinf_total < 1 ) {
	return;
}

/*
 * Never reserve more columns than there are figures: two figures in a
 * three-column grid would leave an empty third of the row. The medium value
 * caps the columns at two for the tablet breakpoint (CSS cannot clamp a
 * repeat() count itself). Both are validated integers, so the inline style
 * cannot carry anything else.
 */
$glinf_columns = min( $glinf_columns, $glinf_total );

$glinf_extra_attributes = array(
	'data-wp-interactive' => 'glinf/stats-counter',
	'style'               => sprintf(
		'--glinf-stats-columns:%1$d;--glinf-stats-columns-medium:%2$d;',
		$glinf_columns,
		min( $glinf_columns, 2 )
	),
);

// A named group is only useful to assistive technology when it has a name: role="group" without a label would be noise.
if ( '' !== $glinf_label ) {
	$glinf_extra_attributes['role']       = 'group';
	$glinf_extra_attributes['aria-label'] = $glinf_label;
}

$glinf_wrapper_attributes = get_block_wrapper_attributes( $glinf_extra_attributes );
?>
<div <?php echo $glinf_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<dl class="glinf-stats-counter__list">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already-rendered inner blocks (each figure escapes its own output). ?>
	</dl>
</div>
