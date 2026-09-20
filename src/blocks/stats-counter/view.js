/**
 * Front-end behavior of glinf/stats-counter (Interactivity API).
 *
 * The page is complete without this module: every figure already contains its
 * real final value as text (see the render.php of glinf/stat). All this module
 * does is a purely visual count-up on top of it: when the number scrolls into
 * view a decorative layer (aria-hidden) shows the intermediate values, then
 * hides itself and the untouched server text is what remains. Screen readers
 * never hear the intermediate numbers because they only exist in that hidden
 * layer, and there is no live region.
 *
 * Nothing runs at all (the final value just stays) with reduced motion, when
 * the tab is hidden, when IntersectionObserver is missing or when there is
 * nothing to count.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

// Share of the number that must be on screen before the count starts.
const VISIBLE_RATIO = 0.5;

// A figure that is already in view when the module wakes up later than this
// (milliseconds since the navigation started) is left alone: the visitor has
// been reading its final value for a while and a sudden reset to 0 would only
// be a glitch.
const LATE_START = 2000;

const REDUCED_MOTION = '(prefers-reduced-motion: reduce)';

/**
 * Decelerating curve: fast at first, gentle at the end.
 *
 * @param {number} t Progress from 0 to 1.
 * @return {number} Eased progress from 0 to 1.
 */
const easeOutCubic = ( t ) => 1 - Math.pow( 1 - t, 3 );

/**
 * Formats a number like number_format_i18n() did on the server: same number
 * of decimals, same separators (they come from the block context).
 *
 * @param {number} number Number to format.
 * @param {Object} ctx    Figure context.
 * @return {string} Formatted number, without prefix and suffix.
 */
const format = ( number, ctx ) => {
	const [ integer, fraction ] = number.toFixed( ctx.decimals ).split( '.' );
	const grouped = integer.replace(
		/\B(?=(\d{3})+(?!\d))/g,
		ctx.thousandsSep
	);

	return fraction === undefined
		? grouped
		: grouped + ctx.decimalSep + fraction;
};

store( 'glinf/stats-counter', {
	callbacks: {
		init() {
			const ctx = getContext();
			const { ref } = getElement();
			const target = ref.querySelector( '.glinf-stat__value' );
			const motion = window.matchMedia( REDUCED_MOTION );

			// Reduced motion, nothing to count, or no way to know when the
			// number is on screen: the final value simply stays.
			if (
				! target ||
				motion.matches ||
				! ( ctx.value > 0 ) ||
				! ( ctx.duration > 0 ) ||
				! ( 'IntersectionObserver' in window )
			) {
				return undefined;
			}

			let observer = null;
			let frame = 0;

			// Hands the number back to the server text (the live layer is hidden
			// by the "is-counting" class), for good.
			const finish = () => {
				window.cancelAnimationFrame( frame );
				frame = 0;
				if ( observer ) {
					observer.disconnect();
					observer = null;
				}
				ctx.active = false;
				ctx.text = '';
			};

			// The number is at 0 and waiting: the live layer takes the place of
			// the final text. Done while the figure is still off screen, so the
			// visitor never sees the final value jump back to 0.
			const arm = () => {
				ctx.text = format( 0, ctx );
				ctx.active = true;
			};

			const start = () => {
				// A background tab does not animate (its frames are paused): the
				// value is there already.
				if ( window.document.hidden ) {
					finish();
					return;
				}

				if ( observer ) {
					observer.disconnect();
					observer = null;
				}
				arm();

				// The clock starts at the first frame, so the visitor sees 0 before
				// anything moves and the time spent waiting for it is not counted.
				const length = ctx.duration * 1000;
				let begin = null;
				const tick = ( now ) => {
					begin = begin === null ? now : begin;
					const progress = Math.min( ( now - begin ) / length, 1 );

					if ( progress >= 1 ) {
						finish();
						return;
					}

					ctx.text = format(
						ctx.value * easeOutCubic( progress ),
						ctx
					);
					frame = window.requestAnimationFrame( tick );
				};
				frame = window.requestAnimationFrame( tick );
			};

			let first = true;
			observer = new window.IntersectionObserver(
				( entries ) => {
					const { isIntersecting } = entries[ entries.length - 1 ];
					const wasFirst = first;
					first = false;

					if ( isIntersecting ) {
						if (
							wasFirst &&
							window.performance.now() > LATE_START
						) {
							finish();
							return;
						}
						start();
					} else if ( wasFirst ) {
						arm();
					}
				},
				{ threshold: VISIBLE_RATIO }
			);
			observer.observe( target );

			// The preference can change while the page is open: stop at once.
			const onMotionChange = ( event ) => {
				if ( event.matches ) {
					finish();
				}
			};
			motion.addEventListener( 'change', onMotionChange );

			return () => {
				window.cancelAnimationFrame( frame );
				if ( observer ) {
					observer.disconnect();
				}
				motion.removeEventListener( 'change', onMotionChange );
			};
		},
	},
} );
