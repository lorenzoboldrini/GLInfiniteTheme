import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';
import {
	Button,
	PanelBody,
	RangeControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

import './editor.scss';

// Keep in sync with block.json and render.php.
const MIN_PER_VIEW = 1;
const MAX_PER_VIEW = 4;
const MIN_DELAY = 3;
const MAX_DELAY = 20;

const ALLOWED_BLOCKS = [ 'glinf/slide' ];
const TEMPLATE = [ [ 'glinf/slide' ], [ 'glinf/slide' ] ];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		slidesPerView,
		loop,
		showArrows,
		showDots,
		autoplay,
		autoplayDelay,
		ariaLabel,
	} = attributes;

	const blockProps = useBlockProps();

	// In the editor the slides are stacked in a column: they are blocks to edit,
	// not a carousel to scroll (no scroll-snap, no autoplay).
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'glinf-slider__slides' },
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
			renderAppender: false,
		}
	);

	const slideCount = useSelect(
		( select ) => select( blockEditorStore ).getBlockCount( clientId ),
		[ clientId ]
	);
	const { insertBlock } = useDispatch( blockEditorStore );

	// A new slide starts with an empty paragraph so it can be typed into at once.
	const addSlide = () => {
		insertBlock(
			createBlock( 'glinf/slide', {}, [
				createBlock( 'core/paragraph' ),
			] ),
			slideCount,
			clientId
		);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Slider', 'gl-infinite-theme' ) }>
					<TextControl
						label={ __( 'Accessible name', 'gl-infinite-theme' ) }
						value={ ariaLabel }
						onChange={ ( nextLabel ) =>
							setAttributes( { ariaLabel: nextLabel } )
						}
						help={ __(
							'Read by screen readers to identify this carousel. Leave empty to use "Slider". Describe the content, for example "Customer stories".',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<RangeControl
						label={ __( 'Slides per view', 'gl-infinite-theme' ) }
						value={ slidesPerView }
						onChange={ ( nextValue ) =>
							setAttributes( {
								slidesPerView: nextValue ?? MIN_PER_VIEW,
							} )
						}
						min={ MIN_PER_VIEW }
						max={ MAX_PER_VIEW }
						help={ __(
							'On screens narrower than about 600px one slide is always shown.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<ToggleControl
						label={ __( 'Loop', 'gl-infinite-theme' ) }
						checked={ !! loop }
						onChange={ ( nextValue ) =>
							setAttributes( { loop: nextValue } )
						}
						help={ __(
							'The next button on the last slide goes back to the first one, and the other way round.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Controls', 'gl-infinite-theme' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'Show previous and next buttons',
							'gl-infinite-theme'
						) }
						checked={ !! showArrows }
						onChange={ ( nextValue ) =>
							setAttributes( { showArrows: nextValue } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show slide dots', 'gl-infinite-theme' ) }
						checked={ !! showDots }
						onChange={ ( nextValue ) =>
							setAttributes( { showDots: nextValue } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Autoplay', 'gl-infinite-theme' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'Change slides automatically',
							'gl-infinite-theme'
						) }
						checked={ !! autoplay }
						onChange={ ( nextValue ) =>
							setAttributes( { autoplay: nextValue } )
						}
						help={ __(
							'A pause button is always shown. Autoplay stops while the pointer or the keyboard focus is inside the slider, and it never starts for visitors who prefer reduced motion.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
					/>
					{ autoplay && (
						<RangeControl
							label={ __(
								'Seconds per slide',
								'gl-infinite-theme'
							) }
							value={ autoplayDelay }
							onChange={ ( nextValue ) =>
								setAttributes( {
									autoplayDelay: nextValue ?? MIN_DELAY,
								} )
							}
							min={ MIN_DELAY }
							max={ MAX_DELAY }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div { ...innerBlocksProps } />
				<div className="glinf-slider__editor-actions">
					<Button variant="secondary" onClick={ addSlide }>
						{ __( 'Add slide', 'gl-infinite-theme' ) }
					</Button>
				</div>
			</div>
		</>
	);
}
