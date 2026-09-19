<?php
/**
 * Server-side render for the glinf/slider block.
 *
 * An accessible carousel (WAI-ARIA APG "carousel" pattern). The slides are
 * glinf/slide inner blocks, already rendered in $content. Without JavaScript
 * the slides scroll horizontally with CSS scroll-snap and no control is shown:
 * the controls are printed with the "hidden" attribute and the Interactivity
 * API view module reveals them. Every attribute is validated here: the editor
 * input is never trusted (the block can also be edited by hand in the code
 * editor).
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;

// Numbers: clamp to the same ranges declared in block.json.
$glinf_per_view = isset( $attributes['slidesPerView'] ) && is_numeric( $attributes['slidesPerView'] ) ? min( 4, max( 1, (int) $attributes['slidesPerView'] ) ) : 1;
$glinf_delay    = isset( $attributes['autoplayDelay'] ) && is_numeric( $attributes['autoplayDelay'] ) ? min( 20, max( 3, (int) $attributes['autoplayDelay'] ) ) : 6;

// Flags: anything but a real boolean falls back to the default.
$glinf_loop        = isset( $attributes['loop'] ) && true === $attributes['loop'];
$glinf_show_arrows = ! isset( $attributes['showArrows'] ) || false !== $attributes['showArrows'];
$glinf_show_dots   = ! isset( $attributes['showDots'] ) || false !== $attributes['showDots'];
$glinf_autoplay    = isset( $attributes['autoplay'] ) && true === $attributes['autoplay'];

// Accessible name of the carousel: plain text, capped, with a translated default.
$glinf_label = isset( $attributes['ariaLabel'] ) && is_string( $attributes['ariaLabel'] ) ? trim( wp_html_excerpt( $attributes['ariaLabel'], 200 ) ) : '';
if ( '' === $glinf_label ) {
	$glinf_label = __( 'Slider', 'gl-infinite-theme' );
}

/*
 * Count the slides in the rendered markup with the HTML API (no regex). Each
 * glinf/slide prints a "data-glinf-slide" marker; nested sliders have already
 * numbered and cleaned their own slides, so only this slider's are left.
 */
$glinf_total     = 0;
$glinf_processor = new WP_HTML_Tag_Processor( $content );
while ( $glinf_processor->next_tag( array( 'class_name' => 'wp-block-glinf-slide' ) ) ) {
	if ( null !== $glinf_processor->get_attribute( 'data-glinf-slide' ) ) {
		++$glinf_total;
	}
}

// A slider without slides prints nothing at all.
if ( $glinf_total < 1 ) {
	return;
}

// Second pass: write the "N of M" name (plus the optional label) on each slide.
$glinf_index     = 0;
$glinf_processor = new WP_HTML_Tag_Processor( $content );
while ( $glinf_processor->next_tag( array( 'class_name' => 'wp-block-glinf-slide' ) ) ) {
	if ( null === $glinf_processor->get_attribute( 'data-glinf-slide' ) ) {
		continue;
	}
	++$glinf_index;

	$glinf_slide_label = $glinf_processor->get_attribute( 'aria-label' );
	$glinf_slide_label = is_string( $glinf_slide_label ) ? $glinf_slide_label : '';

	if ( '' !== $glinf_slide_label ) {
		$glinf_slide_name = sprintf(
			/* translators: 1: slide number, 2: total number of slides, 3: slide label. Example: "2 of 5: Our services". */
			__( '%1$d of %2$d: %3$s', 'gl-infinite-theme' ),
			$glinf_index,
			$glinf_total,
			$glinf_slide_label
		);
	} else {
		$glinf_slide_name = sprintf(
			/* translators: 1: slide number, 2: total number of slides. Example: "2 of 5". */
			__( '%1$d of %2$d', 'gl-infinite-theme' ),
			$glinf_index,
			$glinf_total
		);
	}

	$glinf_processor->set_attribute( 'aria-label', $glinf_slide_name );
	$glinf_processor->remove_attribute( 'data-glinf-slide' );
}
$glinf_slides = $glinf_processor->get_updated_html();

// One slide (or none): nothing to navigate, so no controls and no script state.
$glinf_is_interactive = $glinf_total > 1;
$glinf_has_controls   = $glinf_is_interactive && ( $glinf_show_arrows || $glinf_show_dots || $glinf_autoplay );
$glinf_track_id       = wp_unique_id( 'glinf-slider-track-' );

$glinf_extra_attributes = array(
	'role'                 => 'region',
	/* translators: Screen reader term for a carousel (WAI-ARIA aria-roledescription). Keep it lowercase and short. */
	'aria-roledescription' => __( 'carousel', 'gl-infinite-theme' ),
	'aria-label'           => $glinf_label,
	// Validated integer only: it feeds the CSS calc() that sizes the slides.
	'style'                => sprintf( '--glinf-slider-per-view:%d;', $glinf_per_view ),
);

$glinf_interactive_attributes = '';
if ( $glinf_is_interactive ) {
	$glinf_extra_attributes += array(
		'data-wp-interactive'        => 'glinf/slider',
		'data-wp-init'               => 'callbacks.init',
		'data-wp-watch'              => 'callbacks.autoplay',
		'data-wp-class--is-enhanced' => 'context.ready',
		'data-wp-on--keydown'        => 'actions.keydown',
		'data-wp-on--pointerenter'   => 'actions.hoverStart',
		'data-wp-on--pointerleave'   => 'actions.hoverEnd',
		'data-wp-on--focusin'        => 'actions.focusIn',
		'data-wp-on--focusout'       => 'actions.focusOut',
	);

	// Only validated values (integers, booleans, translated labels) reach the client.
	$glinf_interactive_attributes = wp_interactivity_data_wp_context(
		array(
			'current'        => 0,
			'total'          => $glinf_total,
			'perView'        => $glinf_per_view,
			'maxIndex'       => max( 0, $glinf_total - $glinf_per_view ),
			'loop'           => $glinf_loop,
			'autoplay'       => $glinf_autoplay,
			'delay'          => $glinf_delay,
			'ready'          => false,
			'controlsHidden' => true,
			'pauseHidden'    => false,
			'reducedMotion'  => false,
			'userPaused'     => false,
			'hover'          => false,
			'focus'          => false,
			'prevDisabled'   => ! $glinf_loop,
			'nextDisabled'   => false,
			'pauseLabel'     => __( 'Pause automatic slide show', 'gl-infinite-theme' ),
			'labelPause'     => __( 'Pause automatic slide show', 'gl-infinite-theme' ),
			'labelPlay'      => __( 'Start automatic slide show', 'gl-infinite-theme' ),
		)
	);
}

$glinf_wrapper_attributes = get_block_wrapper_attributes( $glinf_extra_attributes );

/*
 * Icons: 24px grid, filled with the button text color. Both arguments are
 * static literals from this file, never attribute values; they are still
 * escaped so the helper stays safe if it is ever reused.
 */
$glinf_icon = static function ( string $class_name, string $path ): string {
	return sprintf(
		'<svg class="glinf-slider__icon %1$s" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill="currentColor" d="%2$s"/></svg>',
		esc_attr( $class_name ),
		esc_attr( $path )
	);
};
?>
<div <?php echo $glinf_wrapper_attributes . ' ' . $glinf_interactive_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by core / wp_interactivity_data_wp_context(). ?>>
	<?php
	/*
	 * The track is the scroll container, so it is a keyboard tab stop with a
	 * name (WCAG 2.1.1, "scrollable region must be focusable"): with the
	 * arrow keys, Home and End it works with or without the buttons.
	 */
	?>
	<div class="glinf-slider__track" id="<?php echo esc_attr( $glinf_track_id ); ?>" role="group" aria-label="<?php echo esc_attr__( 'Slides', 'gl-infinite-theme' ); ?>" tabindex="0"<?php echo $glinf_is_interactive ? ' data-wp-bind--aria-live="state.live"' : ''; ?>>
		<?php echo $glinf_slides; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already-rendered inner blocks; only aria-label (escaped by the HTML API) and a marker were changed. ?>
	</div>

	<?php if ( $glinf_has_controls ) : ?>
		<div class="glinf-slider__controls" hidden data-wp-bind--hidden="context.controlsHidden">
			<?php if ( $glinf_autoplay ) : ?>
				<button
					type="button"
					class="glinf-slider__button glinf-slider__pause wp-element-button"
					aria-label="<?php echo esc_attr__( 'Pause automatic slide show', 'gl-infinite-theme' ); ?>"
					data-wp-on--click="actions.togglePause"
					data-wp-bind--aria-label="context.pauseLabel"
					data-wp-bind--hidden="context.pauseHidden"
					data-wp-class--is-paused="context.userPaused"
				>
					<?php
					// CSS shows the icon that matches the state.
					echo $glinf_icon( 'glinf-slider__icon--pause', 'M6 19h4V5H6v14zm8-14v14h4V5h-4z' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built and escaped by the helper above.
					echo $glinf_icon( 'glinf-slider__icon--play', 'M8 5v14l11-7z' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built and escaped by the helper above.
					?>
				</button>
			<?php endif; ?>

			<?php if ( $glinf_show_arrows ) : ?>
				<button
					type="button"
					class="glinf-slider__button glinf-slider__prev wp-element-button"
					aria-label="<?php echo esc_attr__( 'Previous slide', 'gl-infinite-theme' ); ?>"
					aria-controls="<?php echo esc_attr( $glinf_track_id ); ?>"
					data-wp-on--click="actions.prev"
					data-wp-bind--disabled="context.prevDisabled"
					data-wp-bind--aria-disabled="context.prevDisabled"
				>
					<?php echo $glinf_icon( 'glinf-slider__icon--arrow', 'M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built and escaped by the helper above. ?>
				</button>
			<?php endif; ?>

			<?php if ( $glinf_show_dots ) : ?>
				<ul class="glinf-slider__dots">
					<?php for ( $glinf_i = 0; $glinf_i < $glinf_total; $glinf_i++ ) : ?>
						<li <?php echo wp_interactivity_data_wp_context( array( 'index' => $glinf_i ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Integer only, escaped by core. ?> data-wp-bind--hidden="state.dotHidden">
							<button
								type="button"
								class="glinf-slider__dot wp-element-button"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number. */ __( 'Go to slide %d', 'gl-infinite-theme' ), $glinf_i + 1 ) ); ?>"
								aria-controls="<?php echo esc_attr( $glinf_track_id ); ?>"
								data-wp-on--click="actions.goTo"
								data-wp-bind--aria-current="state.dotCurrent"
							></button>
						</li>
					<?php endfor; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $glinf_show_arrows ) : ?>
				<button
					type="button"
					class="glinf-slider__button glinf-slider__next wp-element-button"
					aria-label="<?php echo esc_attr__( 'Next slide', 'gl-infinite-theme' ); ?>"
					aria-controls="<?php echo esc_attr( $glinf_track_id ); ?>"
					data-wp-on--click="actions.next"
					data-wp-bind--disabled="context.nextDisabled"
					data-wp-bind--aria-disabled="context.nextDisabled"
				>
					<?php echo $glinf_icon( 'glinf-slider__icon--arrow', 'M10 6 8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built and escaped by the helper above. ?>
				</button>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
