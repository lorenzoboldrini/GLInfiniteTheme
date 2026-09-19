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
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

import './editor.scss';

// Keep in sync with block.json and render.php.
const LAYOUTS = [ 'vertical', 'alternating' ];
const DEFAULT_LAYOUT = 'vertical';
const TITLE_LEVELS = [ 2, 3, 4, 5, 6 ];
const DEFAULT_TITLE_LEVEL = 3;

const ALLOWED_BLOCKS = [ 'glinf/timeline-item' ];
// Each new step starts with an empty paragraph so it can be typed into at once.
const TEMPLATE = [
	[ 'glinf/timeline-item', {}, [ [ 'core/paragraph' ] ] ],
	[ 'glinf/timeline-item', {}, [ [ 'core/paragraph' ] ] ],
	[ 'glinf/timeline-item', {}, [ [ 'core/paragraph' ] ] ],
];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { layoutStyle, numbered, titleLevel, ariaLabel } = attributes;

	// Guard against out-of-range values, same rules as render.php.
	const safeLayout = LAYOUTS.includes( layoutStyle )
		? layoutStyle
		: DEFAULT_LAYOUT;
	const safeTitleLevel = TITLE_LEVELS.includes( titleLevel )
		? titleLevel
		: DEFAULT_TITLE_LEVEL;

	// Same classes as the front end, so the editor preview follows the same CSS.
	const classNames = [ `glinf-timeline--${ safeLayout }` ];
	if ( numbered ) {
		classNames.push( 'glinf-timeline--numbered' );
	}
	const blockProps = useBlockProps( { className: classNames.join( ' ' ) } );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'glinf-timeline__list' },
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
			renderAppender: false,
		}
	);

	const itemCount = useSelect(
		( select ) => select( blockEditorStore ).getBlockCount( clientId ),
		[ clientId ]
	);
	const { insertBlock } = useDispatch( blockEditorStore );

	const addItem = () => {
		insertBlock(
			createBlock( 'glinf/timeline-item', {}, [
				createBlock( 'core/paragraph' ),
			] ),
			itemCount,
			clientId
		);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Timeline', 'gl-infinite-theme' ) }>
					<SelectControl
						label={ __( 'Layout', 'gl-infinite-theme' ) }
						value={ safeLayout }
						options={ [
							{
								label: __( 'Vertical', 'gl-infinite-theme' ),
								value: 'vertical',
							},
							{
								label: __(
									'Alternating left and right',
									'gl-infinite-theme'
								),
								value: 'alternating',
							},
						] }
						onChange={ ( nextLayout ) =>
							setAttributes( {
								layoutStyle: LAYOUTS.includes( nextLayout )
									? nextLayout
									: DEFAULT_LAYOUT,
							} )
						}
						help={ __(
							'The alternating layout only applies when there is enough room. On narrow screens the steps are always in a single column.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<ToggleControl
						label={ __( 'Show step numbers', 'gl-infinite-theme' ) }
						checked={ !! numbered }
						onChange={ ( nextValue ) =>
							setAttributes( { numbered: nextValue } )
						}
						help={ __(
							'Puts the number of each step in its marker. Screen readers already announce the position in the list, so the number is not read twice.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __(
							'Title heading level',
							'gl-infinite-theme'
						) }
						value={ safeTitleLevel }
						options={ TITLE_LEVELS.map( ( level ) => ( {
							label: `H${ level }`,
							value: level,
						} ) ) }
						onChange={ ( nextLevel ) =>
							setAttributes( {
								titleLevel: parseInt( nextLevel, 10 ),
							} )
						}
						help={ __(
							'Choose the level that fits the heading hierarchy of the page. It applies to the titles of all the steps.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<TextControl
						label={ __( 'Accessible name', 'gl-infinite-theme' ) }
						value={ ariaLabel }
						onChange={ ( nextLabel ) =>
							setAttributes( { ariaLabel: nextLabel } )
						}
						help={ __(
							'Optional. Read by screen readers to identify this list, for example "Company history". Leave empty for no name.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<ol { ...innerBlocksProps } />
				<div className="glinf-timeline__editor-actions">
					<Button variant="secondary" onClick={ addItem }>
						{ __( 'Add item', 'gl-infinite-theme' ) }
					</Button>
				</div>
			</div>
		</>
	);
}
