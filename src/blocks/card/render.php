<?php
/**
 * Server-side render for the glinf/card block.
 *
 * A standalone card: image, title, short text and an optional link. Every
 * attribute is validated here: the editor input is never trusted (the block can
 * also be saved by a filtered user or edited by hand in the code editor).
 *
 * The whole card is clickable through ONE tab stop (a "stretched link"):
 * - URL and label set: the label link is stretched, the title is plain text.
 * - URL set, no label: the title becomes the stretched link.
 * - No URL: the card is not clickable.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;

// Only these schemes may end up in the link: no javascript:, data:, etc.
$glinf_link_protocols = array( 'http', 'https', 'mailto', 'tel' );

/*
 * Inline formats the editor offers, nothing else. Links are left out on
 * purpose: a link inside the text would be a second tab stop and would sit
 * under the stretched link of the card.
 */
$glinf_title_kses = array(
	'em' => array(),
	'i'  => array(),
);
$glinf_text_kses  = array(
	'strong' => array(),
	'b'      => array(),
	'em'     => array(),
	'i'      => array(),
	'br'     => array(),
);

// Text fields: keep the allowed inline markup, and ignore markup-only values (e.g. a lone <br>).
$glinf_title_raw = isset( $attributes['title'] ) && is_string( $attributes['title'] ) ? trim( $attributes['title'] ) : '';
$glinf_text_raw  = isset( $attributes['text'] ) && is_string( $attributes['text'] ) ? trim( $attributes['text'] ) : '';
$glinf_title     = '' !== trim( wp_strip_all_tags( $glinf_title_raw ) ) ? wp_kses( $glinf_title_raw, $glinf_title_kses ) : '';
$glinf_text      = '' !== trim( wp_strip_all_tags( $glinf_text_raw ) ) ? wp_kses( $glinf_text_raw, $glinf_text_kses ) : '';

// Heading level: the tag name is built only from a validated integer.
$glinf_title_level = isset( $attributes['titleLevel'] ) && is_numeric( $attributes['titleLevel'] ) ? absint( $attributes['titleLevel'] ) : 3;
if ( ! in_array( $glinf_title_level, array( 2, 3, 4, 5, 6 ), true ) ) {
	$glinf_title_level = 3;
}
$glinf_title_tag = 'h' . $glinf_title_level;

/*
 * Image: it must be an image attachment. The alt text comes only from the
 * Media Library; an empty alt is kept empty (decorative image), core must not
 * invent one. wp_get_attachment_image() adds width/height, srcset and sizes,
 * and decides loading/fetchpriority.
 */
$glinf_media_id = isset( $attributes['mediaId'] ) && is_numeric( $attributes['mediaId'] ) ? absint( $attributes['mediaId'] ) : 0;
$glinf_image    = '';
if ( $glinf_media_id > 0 && 'attachment' === get_post_type( $glinf_media_id ) && wp_attachment_is_image( $glinf_media_id ) ) {
	$glinf_image = wp_get_attachment_image(
		$glinf_media_id,
		'large',
		false,
		array(
			'class' => 'glinf-card__image',
			'alt'   => trim( wp_strip_all_tags( (string) get_post_meta( $glinf_media_id, '_wp_attachment_image_alt', true ) ) ),
		)
	);
}

// Link: esc_url_raw() returns an empty string for any scheme outside the whitelist.
$glinf_link_raw   = isset( $attributes['linkUrl'] ) && is_string( $attributes['linkUrl'] ) ? trim( $attributes['linkUrl'] ) : '';
$glinf_link_url   = '' !== $glinf_link_raw ? esc_url_raw( $glinf_link_raw, $glinf_link_protocols ) : '';
$glinf_link_label = isset( $attributes['linkLabel'] ) && is_string( $attributes['linkLabel'] ) ? trim( wp_strip_all_tags( $attributes['linkLabel'] ) ) : '';
$glinf_new_tab    = isset( $attributes['linkNewTab'] ) && true === $attributes['linkNewTab'];

$glinf_has_url    = '' !== $glinf_link_url;
$glinf_label_link = $glinf_has_url && '' !== $glinf_link_label;
$glinf_title_link = $glinf_has_url && ! $glinf_label_link && '' !== $glinf_title;
$glinf_is_linked  = $glinf_label_link || $glinf_title_link;

// Nothing to show: print nothing at all. A label link counts as content.
if ( '' === $glinf_image && '' === $glinf_title && '' === $glinf_text && ! $glinf_label_link ) {
	return;
}

// "is-linked" turns on the hover effect and the stretched link: a card that goes nowhere must not look clickable.
$glinf_wrapper_attributes = get_block_wrapper_attributes(
	array( 'class' => $glinf_is_linked ? 'is-linked' : '' )
);
?>
<article <?php echo $glinf_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core. ?>>
	<?php if ( '' !== $glinf_image ) : ?>
		<?php echo $glinf_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core-built <img>, attributes escaped by core. wp_kses_post() would strip srcset/sizes/fetchpriority. ?>
	<?php endif; ?>

	<?php if ( '' !== $glinf_title ) : ?>
		<<?php echo tag_escape( $glinf_title_tag ); ?> class="glinf-card__title">
			<?php if ( $glinf_title_link ) : ?>
				<a class="glinf-card__link" href="<?php echo esc_url( $glinf_link_url, $glinf_link_protocols ); ?>"<?php echo $glinf_new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo $glinf_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized above with wp_kses() and a minimal whitelist. ?><?php if ( $glinf_new_tab ) : ?> <span class="glinf-card__sr-only"><?php esc_html_e( '(opens in a new tab)', 'gl-infinite-theme' ); ?></span><?php endif; ?></a>
			<?php else : ?>
				<?php echo $glinf_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized above with wp_kses() and a minimal whitelist. ?>
			<?php endif; ?>
		</<?php echo tag_escape( $glinf_title_tag ); ?>>
	<?php endif; ?>

	<?php if ( '' !== $glinf_text ) : ?>
		<p class="glinf-card__text"><?php echo $glinf_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized above with wp_kses() and a minimal whitelist. ?></p>
	<?php endif; ?>

	<?php if ( $glinf_label_link ) : ?>
		<a class="glinf-card__link" href="<?php echo esc_url( $glinf_link_url, $glinf_link_protocols ); ?>"<?php echo $glinf_new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $glinf_link_label ); ?><?php if ( $glinf_new_tab ) : ?> <span class="glinf-card__sr-only"><?php esc_html_e( '(opens in a new tab)', 'gl-infinite-theme' ); ?></span><?php endif; ?></a>
	<?php endif; ?>
</article>
