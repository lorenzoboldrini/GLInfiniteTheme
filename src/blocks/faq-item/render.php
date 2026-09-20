<?php
/**
 * Server-side render for the glinf/faq-item block.
 *
 * One question of a glinf/faq-accordion, printed as a native <details>: the
 * <summary> holds the question and is the control (focusable, opened with
 * Enter and Space by the browser itself, no script), the answer sits in a
 * <div> after it. The question is a heading of the level chosen on the
 * accordion, placed inside the <summary>: HTML allows one heading there, the
 * summary stays the control, and the questions show up in the heading
 * navigation of screen readers. The open/closed state is exposed by the
 * browser; the indicator drawn by the CSS is decorative and not announced.
 * Every attribute is validated here: the editor input is never trusted.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;

/*
 * An item only makes sense inside an accordion. The parent always provides its
 * block context (even with the default heading level), so an item without it
 * was placed elsewhere by hand: print nothing rather than a stray <details>.
 */
if ( ! isset( $block->context['glinf/faqTitleLevel'] ) ) {
	return;
}

/*
 * Question: the inline formats the editor offers, nothing else. Links are left
 * out on purpose: a link inside the summary would be a second control in the
 * same click target. Markup-only values (a lone <br>) count as empty.
 */
$glinf_question_kses = array(
	'strong' => array(),
	'b'      => array(),
	'em'     => array(),
	'i'      => array(),
);
$glinf_question_raw  = isset( $attributes['question'] ) && is_string( $attributes['question'] ) ? trim( $attributes['question'] ) : '';
$glinf_question      = '' !== trim( wp_strip_all_tags( $glinf_question_raw ) ) ? wp_kses( $glinf_question_raw, $glinf_question_kses ) : '';

/*
 * Answer: it counts when it has some text or some media. A lone empty
 * paragraph (what a new item starts with) is not content.
 */
$glinf_has_answer = '' !== trim( wp_strip_all_tags( $content ) );
if ( ! $glinf_has_answer && '' !== trim( $content ) ) {
	$glinf_media_tags = array( 'IMG', 'PICTURE', 'VIDEO', 'AUDIO', 'IFRAME', 'SVG', 'EMBED', 'OBJECT' );
	$glinf_processor  = new WP_HTML_Tag_Processor( $content );
	while ( $glinf_processor->next_tag() ) {
		if ( in_array( $glinf_processor->get_tag(), $glinf_media_tags, true ) ) {
			$glinf_has_answer = true;
			break;
		}
	}
}

/*
 * Without a question the summary would be a control with no name, and without
 * an answer it would open onto nothing: in both cases nothing is printed (the
 * editor still shows the item, so no content is lost).
 */
if ( '' === $glinf_question || ! $glinf_has_answer ) {
	return;
}

// Heading level from the parent accordion (block context): the tag name is built only from a validated integer.
$glinf_title_level = is_numeric( $block->context['glinf/faqTitleLevel'] ) ? absint( $block->context['glinf/faqTitleLevel'] ) : 3;
if ( ! in_array( $glinf_title_level, array( 2, 3, 4, 5, 6 ), true ) ) {
	$glinf_title_level = 3;
}
$glinf_title_tag = 'h' . $glinf_title_level;

// The marker lets the parent find its own items (and is removed by it); "open" only for a real true.
$glinf_extra_attributes = array( 'data-glinf-faq-item' => '' );
if ( isset( $attributes['open'] ) && true === $attributes['open'] ) {
	$glinf_extra_attributes['open'] = '';
}

$glinf_wrapper_attributes = get_block_wrapper_attributes( $glinf_extra_attributes );
?>
<details <?php echo $glinf_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<summary class="glinf-faq-item__summary">
		<<?php echo tag_escape( $glinf_title_tag ); ?> class="glinf-faq-item__question"><?php echo $glinf_question; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized above with wp_kses() and a minimal whitelist. ?></<?php echo tag_escape( $glinf_title_tag ); ?>>
	</summary>
	<div class="glinf-faq-item__answer">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already-rendered inner blocks. ?>
	</div>
</details>
