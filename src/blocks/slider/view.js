/**
 * Front-end behavior of glinf/slider (Interactivity API).
 *
 * The scrolling itself is CSS scroll-snap; this module only reads where the
 * track is, keeps the controls in sync, moves the track when a control is
 * used and runs the optional autoplay. Everything the module knows comes from
 * the context validated by render.php.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const ROOT_SELECTOR = '.wp-block-glinf-slider';
const MOUSE_POINTER = 'mouse';

/**
 * Autoplay runs only when it is enabled, there is something to scroll and
 * nothing asks it to stop: reduced motion, the pause button, the pointer over
 * the carousel or the keyboard focus inside it.
 *
 * @param {Object} ctx Slider context.
 * @return {boolean} Whether the slides are rotating on their own.
 */
const isRunning = ( ctx ) =>
	ctx.autoplay &&
	ctx.maxIndex > 0 &&
	! ctx.reducedMotion &&
	! ctx.userPaused &&
	! ctx.hover &&
	! ctx.focus;

/**
 * @param {Element} root Slider root element.
 * @return {{track: Element, slides: Element[]}} The scroll container and its slides.
 */
const getParts = ( root ) => {
	const track = root.querySelector( '.glinf-slider__track' );
	const slides = Array.from( track.children ).filter( ( child ) =>
		child.classList.contains( 'wp-block-glinf-slide' )
	);
	return { track, slides };
};

/**
 * Distance to scroll so the start edge of a slide meets the start edge of
 * the track (inside its scroll padding). Works in both text directions.
 *
 * @param {Element} track Scroll container.
 * @param {Element} slide Slide element.
 * @return {number} Signed distance in pixels to add to scrollLeft.
 */
const getOffset = ( track, slide ) => {
	const trackRect = track.getBoundingClientRect();
	const slideRect = slide.getBoundingClientRect();
	const style = window.getComputedStyle( track );
	const padding = parseFloat( style.scrollPaddingInlineStart ) || 0;

	return style.direction === 'rtl'
		? slideRect.right - ( trackRect.right - padding )
		: slideRect.left - ( trackRect.left + padding );
};

/**
 * Scrolls the track so the slide at `index` is the first visible one.
 *
 * @param {Element} root  Slider root element.
 * @param {Object}  ctx   Slider context.
 * @param {number}  index Target slide index.
 */
const scrollToIndex = ( root, ctx, index ) => {
	const { track, slides } = getParts( root );
	const slide = slides[ Math.min( Math.max( index, 0 ), ctx.maxIndex ) ];

	if ( ! slide ) {
		return;
	}

	// scrollTo() with an absolute position, not scrollBy(): with
	// scroll-snap-stop: always a relative scroll stops at the next snap point,
	// so a jump over several slides (dots, Home/End, wrap-around) would fall
	// short. Unlike scrollIntoView() it also never scrolls the page, which
	// matters when autoplay runs off-screen.
	track.scrollTo( {
		left: track.scrollLeft + getOffset( track, slide ),
		behavior: ctx.reducedMotion ? 'instant' : 'smooth',
	} );
};

/**
 * Index reached by moving one step, wrapping around only when loop is on.
 *
 * @param {Object}  ctx       Slider context.
 * @param {number}  direction 1 for next, -1 for previous.
 * @param {boolean} wrap      Wrap even without loop (autoplay).
 * @return {number} Target index.
 */
const getStepTarget = ( ctx, direction, wrap = ctx.loop ) => {
	const target = ctx.current + direction;

	if ( target < 0 ) {
		return wrap ? ctx.maxIndex : 0;
	}
	if ( target > ctx.maxIndex ) {
		return wrap ? 0 : ctx.maxIndex;
	}
	return target;
};

store( 'glinf/slider', {
	state: {
		// The dot of the slide the track is on.
		get dotCurrent() {
			const ctx = getContext();
			return ctx.index === ctx.current;
		},
		// Dots past the last reachable position (several slides per view) are dropped.
		get dotHidden() {
			const ctx = getContext();
			return ctx.index > ctx.maxIndex;
		},
		// APG: "off" while the slides rotate by themselves, "polite" otherwise.
		get live() {
			return isRunning( getContext() ) ? 'off' : 'polite';
		},
	},

	actions: {
		prev() {
			const ctx = getContext();
			const root = getElement().ref.closest( ROOT_SELECTOR );
			scrollToIndex( root, ctx, getStepTarget( ctx, -1 ) );
		},

		next() {
			const ctx = getContext();
			const root = getElement().ref.closest( ROOT_SELECTOR );
			scrollToIndex( root, ctx, getStepTarget( ctx, 1 ) );
		},

		goTo() {
			const ctx = getContext();
			const root = getElement().ref.closest( ROOT_SELECTOR );
			scrollToIndex( root, ctx, ctx.index );
		},

		// A pause chosen by the visitor is final: nothing restarts it.
		togglePause() {
			const ctx = getContext();
			ctx.userPaused = ! ctx.userPaused;
			ctx.pauseLabel = ctx.userPaused ? ctx.labelPlay : ctx.labelPause;
		},

		keydown( event ) {
			if (
				event.defaultPrevented ||
				event.altKey ||
				event.ctrlKey ||
				event.metaKey ||
				event.shiftKey
			) {
				return;
			}

			// Never steal the arrow keys from a control that uses them.
			if (
				event.target.closest(
					'input, textarea, select, [contenteditable]:not([contenteditable="false"]), [role="slider"], [role="listbox"], [role="tablist"], [role="menu"]'
				)
			) {
				return;
			}

			const ctx = getContext();
			const root = getElement().ref;
			const rtl = window.getComputedStyle( root ).direction === 'rtl';
			let target = null;

			if ( event.key === 'ArrowRight' ) {
				target = getStepTarget( ctx, rtl ? -1 : 1 );
			} else if ( event.key === 'ArrowLeft' ) {
				target = getStepTarget( ctx, rtl ? 1 : -1 );
			} else if ( event.key === 'Home' ) {
				target = 0;
			} else if ( event.key === 'End' ) {
				target = ctx.maxIndex;
			}

			if ( target === null ) {
				return;
			}

			event.preventDefault();
			scrollToIndex( root, ctx, target );
		},

		// Only a real mouse counts: on touch screens the emulated hover would stick.
		hoverStart( event ) {
			if ( event.pointerType === MOUSE_POINTER ) {
				getContext().hover = true;
			}
		},

		hoverEnd( event ) {
			if ( event.pointerType === MOUSE_POINTER ) {
				getContext().hover = false;
			}
		},

		// Only keyboard focus pauses: a mouse click on an arrow must not freeze the autoplay.
		focusIn( event ) {
			if ( event.target.matches( ':focus-visible' ) ) {
				getContext().focus = true;
			}
		},

		focusOut( event ) {
			const root = getElement().ref;

			if ( ! root.contains( event.relatedTarget ) ) {
				getContext().focus = false;
			}
		},
	},

	callbacks: {
		init() {
			const ctx = getContext();
			const root = getElement().ref;
			const { track, slides } = getParts( root );
			const prevButton = root.querySelector( '.glinf-slider__prev' );
			const nextButton = root.querySelector( '.glinf-slider__next' );
			const motionQuery = window.matchMedia(
				'(prefers-reduced-motion: reduce)'
			);
			let frame = 0;

			// Number of slides in view, from the CSS (it changes at the breakpoint).
			const readPerView = () => {
				const value = parseInt(
					window
						.getComputedStyle( track )
						.getPropertyValue( '--glinf-slider-visible' ),
					10
				);
				return Math.min(
					Math.max( Number.isNaN( value ) ? 1 : value, 1 ),
					slides.length
				);
			};

			const sync = () => {
				frame = 0;

				const perView = readPerView();
				const maxIndex = Math.max( 0, slides.length - perView );

				// The current slide is the one whose start edge is closest to the track start.
				let current = 0;
				let best = Infinity;
				slides.forEach( ( slide, index ) => {
					const distance = Math.abs( getOffset( track, slide ) );
					if ( distance < best ) {
						best = distance;
						current = index;
					}
				} );
				current = Math.min( current, maxIndex );

				const prevDisabled = ! ctx.loop && current <= 0;
				const nextDisabled = ! ctx.loop && current >= maxIndex;
				const active = root.ownerDocument.activeElement;

				// A button that is about to be disabled must not keep the focus:
				// hand it to the other arrow, or to the track.
				if ( prevButton && active === prevButton && prevDisabled ) {
					( nextDisabled ? track : nextButton ).focus();
				} else if (
					nextButton &&
					active === nextButton &&
					nextDisabled
				) {
					( prevDisabled ? track : prevButton ).focus();
				}

				// Slides out of view leave the tab order and the accessibility tree.
				const paged = maxIndex > 0;
				slides.forEach( ( slide, index ) => {
					const hidden =
						paged &&
						( index < current || index >= current + perView );

					// Same for content that has the focus: keep it inside the carousel.
					if ( hidden && slide.contains( active ) ) {
						track.focus( { preventScroll: true } );
					}
					slide.inert = hidden;
				} );

				ctx.perView = perView;
				ctx.maxIndex = maxIndex;
				ctx.current = current;
				ctx.prevDisabled = prevDisabled;
				ctx.nextDisabled = nextDisabled;
				// Nothing to page through (all slides fit): no controls at all.
				ctx.controlsHidden = ! paged;
			};

			const schedule = () => {
				if ( ! frame ) {
					frame = window.requestAnimationFrame( sync );
				}
			};

			const onMotionChange = ( event ) => {
				ctx.reducedMotion = event.matches;
				// Nothing is rotating, so there is nothing to pause.
				ctx.pauseHidden = event.matches;
			};

			ctx.reducedMotion = motionQuery.matches;
			ctx.pauseHidden = motionQuery.matches;
			motionQuery.addEventListener( 'change', onMotionChange );

			track.addEventListener( 'scroll', schedule, { passive: true } );
			const observer = new window.ResizeObserver( schedule );
			observer.observe( track );

			sync();
			ctx.ready = true;

			return () => {
				window.cancelAnimationFrame( frame );
				track.removeEventListener( 'scroll', schedule );
				observer.disconnect();
				motionQuery.removeEventListener( 'change', onMotionChange );
			};
		},

		// Runs again whenever a value it reads changes: a new slide, a pause, a hover...
		autoplay() {
			const ctx = getContext();
			const root = getElement().ref;
			const delay = ctx.delay * 1000;

			if ( ! isRunning( ctx ) ) {
				return undefined;
			}

			// Autoplay always wraps to the first slide, even with loop off.
			// Reading "current" here also restarts the countdown after every
			// manual move.
			const target = getStepTarget( ctx, 1, true );

			let timer = 0;
			const tick = () => {
				// A hidden tab does not rotate: check again later.
				if ( document.hidden ) {
					timer = window.setTimeout( tick, delay );
					return;
				}
				scrollToIndex( root, ctx, target );
			};
			timer = window.setTimeout( tick, delay );

			return () => window.clearTimeout( timer );
		},
	},
} );
