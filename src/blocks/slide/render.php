<?php
/**
 * Server-side render for the glinf/slide block.
 *
 * One slide of a glinf/slider. The block has no way to know its own position
 * (WP_Block has no parent link), so it only prints a marker attribute: the
 * slider counts the markers, then writes the final "N of M" accessible name
 * and removes the marker (see the slider render.php).
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;

// Optional label: plain text, capped. The slider builds "N of M: label" from it.
$glinf_label = isset( $attributes['label'] ) && is_string( $attributes['label'] ) ? trim( wp_html_excerpt( $attributes['label'], 120 ) ) : '';

$glinf_extra_attributes = array(
	'role'                 => 'group',
	/* translators: Screen reader term for one slide of a carousel. Keep it lowercase and short (WAI-ARIA aria-roledescription). */
	'aria-roledescription' => __( 'slide', 'gl-infinite-theme' ),
	'data-glinf-slide'     => '',
);
if ( '' !== $glinf_label ) {
	$glinf_extra_attributes['aria-label'] = $glinf_label;
}

$glinf_wrapper_attributes = get_block_wrapper_attributes( $glinf_extra_attributes );
?>
<div <?php echo $glinf_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already-rendered inner blocks. ?>
</div>
