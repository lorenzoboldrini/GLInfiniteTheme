<?php
/**
 * Server-side render for the glinf/stat block.
 *
 * One figure of a glinf/stats-counter, printed as a group of its <dl>:
 * <dt> is the label, <dd> is the number. The reading order is label first
 * ("Happy customers, 1,200+"); CSS shows the number above the label.
 *
 * The real final value is always printed here, formatted with the site
 * language (number_format_i18n), inside ".glinf-stat__final". That text is
 * what screen readers read, what crawlers index and what the visitor sees
 * without JavaScript, in print or with reduced motion, so it is complete on
 * its own and reserves the exact width the number needs (no layout shift).
 * The count-up is a second, decorative layer (".glinf-stat__live",
 * aria-hidden) stacked on top of it: the view module fills it with the
 * intermediate numbers and the assistive technology never sees them. When the
 * count ends the layer is hidden again and the untouched server text is what
 * remains.
 *
 * Every attribute is validated here: the editor input is never trusted. Only
 * validated numbers and short plain strings reach the client, as the block
 * context of the Interactivity API.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content (unused: a figure has no inner blocks).
 * @var WP_Block $block      Block instance.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;

/*
 * A <dt>/<dd> pair is only valid inside the <dl> of a counter. The parent
 * always provides its block context (even with the default duration), so a
 * figure without it was placed elsewhere by hand: print nothing rather than
 * invalid markup.
 */
if ( ! isset( $block->context['glinf/statsDuration'] ) ) {
	return;
}

/**
 * Turns a raw attribute into a finite number inside a range.
 *
 * Anything that is not numeric (arrays, booleans, "abc", "NAN") gives the
 * fallback; numeric strings such as "1e30" or "INF"-like overflows are clamped
 * (a float can overflow to INF, which is never printed nor cast to int).
 *
 * @param mixed $raw      Raw attribute.
 * @param float $min      Lowest allowed value.
 * @param float $max      Highest allowed value.
 * @param float $fallback Value used when $raw is not numeric.
 * @return float
 */
$glinf_clamp = static function ( $raw, float $min, float $max, float $fallback ): float {
	if ( ! is_int( $raw ) && ! is_float( $raw ) && ! ( is_string( $raw ) && is_numeric( $raw ) ) ) {
		return $fallback;
	}

	$number = (float) $raw;
	if ( is_nan( $number ) ) {
		return $fallback;
	}

	return min( $max, max( $min, $number ) );
};

// Numbers: same ranges as block.json. Decimals first, the value is rounded to them.
$glinf_decimals = (int) $glinf_clamp( $attributes['decimals'] ?? 0, 0.0, 3.0, 0.0 );
$glinf_value    = round( $glinf_clamp( $attributes['value'] ?? 0, 0.0, 1000000000000.0, 0.0 ), $glinf_decimals );
$glinf_duration = $glinf_clamp( $block->context['glinf/statsDuration'], 0.5, 5.0, 2.0 );

/*
 * Prefix and suffix: short plain text ("€", "+", "%", "k"). Tags are stripped
 * and the length is capped in characters, not bytes, so a multibyte symbol is
 * never cut in half. Escaped at output.
 */
$glinf_affix_max = 10;
$glinf_prefix    = isset( $attributes['prefix'] ) && is_string( $attributes['prefix'] ) ? mb_substr( sanitize_text_field( $attributes['prefix'] ), 0, $glinf_affix_max ) : '';
$glinf_suffix    = isset( $attributes['suffix'] ) && is_string( $attributes['suffix'] ) ? mb_substr( sanitize_text_field( $attributes['suffix'] ), 0, $glinf_affix_max ) : '';

/*
 * Label: the inline formats the editor offers, nothing else. Links are left
 * out on purpose: the figure is not a link. Markup-only values (a lone <br>)
 * count as empty.
 */
$glinf_label_kses = array(
	'strong' => array(),
	'b'      => array(),
	'em'     => array(),
	'i'      => array(),
);
$glinf_label_raw  = isset( $attributes['label'] ) && is_string( $attributes['label'] ) ? trim( $attributes['label'] ) : '';
$glinf_label      = '' !== trim( wp_strip_all_tags( $glinf_label_raw ) ) ? wp_kses( $glinf_label_raw, $glinf_label_kses ) : '';

/*
 * A number without its term means nothing to a screen reader, and a <dd>
 * without a <dt> is invalid inside the <dl>: a figure with no label prints
 * nothing at all.
 */
if ( '' === $glinf_label ) {
	return;
}

// Final text: exactly what the visitor ends up seeing, in the site language.
$glinf_final = $glinf_prefix . number_format_i18n( $glinf_value, $glinf_decimals ) . $glinf_suffix;

/*
 * Separators for the count-up. The view module builds the intermediate
 * numbers with the very same decimal point and thousands separator the
 * server used, not with the browser locale (which can differ from the site
 * language), so the live layer and the final text look alike. Entities are
 * decoded (a locale can use "&nbsp;") and the length is capped.
 */
$glinf_separators = static function ( string $key, string $fallback ): string {
	global $wp_locale;

	$separator = isset( $wp_locale->number_format[ $key ] ) && is_string( $wp_locale->number_format[ $key ] ) ? $wp_locale->number_format[ $key ] : $fallback;
	$separator = mb_substr( html_entity_decode( wp_strip_all_tags( $separator ), ENT_QUOTES, 'UTF-8' ), 0, 4 );

	return $separator;
};

// Only validated values (numbers, booleans, short strings) reach the client.
$glinf_context = wp_interactivity_data_wp_context(
	array(
		'value'        => $glinf_value,
		'decimals'     => $glinf_decimals,
		'duration'     => $glinf_duration,
		'decimalSep'   => $glinf_separators( 'decimal_point', '.' ),
		'thousandsSep' => $glinf_separators( 'thousands_sep', ',' ),
		'active'       => false,
		'text'         => '',
	)
);

$glinf_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'data-wp-init'               => 'callbacks.init',
		'data-wp-class--is-counting' => 'context.active',
	)
);
?>
<div <?php echo $glinf_wrapper_attributes . ' ' . $glinf_context; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core / wp_interactivity_data_wp_context(). ?>>
	<dt class="glinf-stat__label"><?php echo $glinf_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized above with wp_kses() and a minimal whitelist. ?></dt>
	<dd class="glinf-stat__value">
		<span class="glinf-stat__final"><?php echo esc_html( $glinf_final ); ?></span>
		<span class="glinf-stat__live" aria-hidden="true"><?php echo esc_html( $glinf_prefix ); ?><span data-wp-text="context.text"></span><?php echo esc_html( $glinf_suffix ); ?></span>
	</dd>
</div>
