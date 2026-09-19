<?php
/**
 * Server-side render for the glinf/timeline-item block.
 *
 * One step of a glinf/timeline, printed as an <li> of its <ol>. Reading order:
 * label (date, "Step 1"), title, then the free-form body. The rail (line and
 * marker) is decorative and aria-hidden: the list already announces the
 * position, and the optional step number is a CSS counter inside the rail, so
 * it is not read twice. Every attribute is validated here: the editor input is
 * never trusted.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;

/*
 * An <li> is only valid inside the <ol> of a timeline. The parent always
 * provides its block context (even with the default heading level), so an item
 * without it was placed elsewhere by hand: print nothing rather than invalid
 * markup.
 */
if ( ! isset( $block->context['glinf/timelineTitleLevel'] ) ) {
	return;
}

// Label: plain text (a date, a year, "Step 1"), tags stripped and capped.
$glinf_label = isset( $attributes['label'] ) && is_string( $attributes['label'] ) ? trim( wp_html_excerpt( $attributes['label'], 120 ) ) : '';

/*
 * Title: the inline formats the editor offers, nothing else. Links are left
 * out on purpose: the step is not a link and a stray one would be an
 * unexpected tab stop. Markup-only values (a lone <br>) count as empty.
 */
$glinf_title_kses = array(
	'strong' => array(),
	'b'      => array(),
	'em'     => array(),
	'i'      => array(),
);
$glinf_title_raw  = isset( $attributes['title'] ) && is_string( $attributes['title'] ) ? trim( $attributes['title'] ) : '';
$glinf_title      = '' !== trim( wp_strip_all_tags( $glinf_title_raw ) ) ? wp_kses( $glinf_title_raw, $glinf_title_kses ) : '';

// Heading level from the parent timeline (block context): the tag name is built only from a validated integer.
$glinf_title_level = is_numeric( $block->context['glinf/timelineTitleLevel'] ) ? absint( $block->context['glinf/timelineTitleLevel'] ) : 3;
if ( ! in_array( $glinf_title_level, array( 2, 3, 4, 5, 6 ), true ) ) {
	$glinf_title_level = 3;
}
$glinf_title_tag = 'h' . $glinf_title_level;

/*
 * Body: it counts when it has some text or some media. A lone empty paragraph
 * (what a new item starts with) is not content.
 */
$glinf_has_body = '' !== trim( wp_strip_all_tags( $content ) );
if ( ! $glinf_has_body && '' !== trim( $content ) ) {
	$glinf_media_tags = array( 'IMG', 'PICTURE', 'VIDEO', 'AUDIO', 'IFRAME', 'SVG', 'EMBED', 'OBJECT' );
	$glinf_processor  = new WP_HTML_Tag_Processor( $content );
	while ( $glinf_processor->next_tag() ) {
		if ( in_array( $glinf_processor->get_tag(), $glinf_media_tags, true ) ) {
			$glinf_has_body = true;
			break;
		}
	}
}

// A step with nothing to show prints nothing at all (the CSS step counter only counts printed items).
if ( '' === $glinf_label && '' === $glinf_title && ! $glinf_has_body ) {
	return;
}

$glinf_wrapper_attributes = get_block_wrapper_attributes();
?>
<li <?php echo $glinf_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<span class="glinf-timeline-item__rail" aria-hidden="true"></span>
	<div class="glinf-timeline-item__card">
		<?php if ( '' !== $glinf_label ) : ?>
			<p class="glinf-timeline-item__label"><?php echo esc_html( $glinf_label ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== $glinf_title ) : ?>
			<<?php echo tag_escape( $glinf_title_tag ); ?> class="glinf-timeline-item__title"><?php echo $glinf_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized above with wp_kses() and a minimal whitelist. ?></<?php echo tag_escape( $glinf_title_tag ); ?>>
		<?php endif; ?>

		<?php if ( $glinf_has_body ) : ?>
			<div class="glinf-timeline-item__body">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already-rendered inner blocks. ?>
			</div>
		<?php endif; ?>
	</div>
</li>
