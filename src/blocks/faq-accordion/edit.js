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
const TITLE_LEVELS = [ 2, 3, 4, 5, 6 ];
const DEFAULT_TITLE_LEVEL = 3;

const ALLOWED_BLOCKS = [ 'glinf/faq-item' ];
// Each new question starts with an empty paragraph so the answer can be typed at once.
const TEMPLATE = [
	[ 'glinf/faq-item', {}, [ [ 'core/paragraph' ] ] ],
	[ 'glinf/faq-item', {}, [ [ 'core/paragraph' ] ] ],
	[ 'glinf/faq-item', {}, [ [ 'core/paragraph' ] ] ],
];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { allowMultiple, titleLevel, ariaLabel } = attributes;

	// Guard against an out-of-range value, same rules as render.php.
	const safeTitleLevel = TITLE_LEVELS.includes( titleLevel )
		? titleLevel
		: DEFAULT_TITLE_LEVEL;

	const blockProps = useBlockProps();

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'glinf-faq-accordion__list' },
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
			createBlock( 'glinf/faq-item', {}, [
				createBlock( 'core/paragraph' ),
			] ),
			itemCount,
			clientId
		);
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'FAQ Accordion', 'gl-infinite-theme' ) }>
					<ToggleControl
						label={ __(
							'Allow several answers open at once',
							'gl-infinite-theme'
						) }
						checked={ allowMultiple !== false }
						onChange={ ( nextValue ) =>
							setAttributes( { allowMultiple: nextValue } )
						}
						help={ __(
							'When off, opening a question closes the one that was open. This only applies on the page: in the editor every answer can be opened.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __(
							'Question heading level',
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
							'Choose the level that fits the heading hierarchy of the page. It applies to all the questions.',
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
							'Optional. Read by screen readers to identify this group of questions, for example "Shipping questions". Leave empty for no name.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div { ...innerBlocksProps } />
				<div className="glinf-faq-accordion__editor-actions">
					<Button variant="secondary" onClick={ addItem }>
						{ __( 'Add question', 'gl-infinite-theme' ) }
					</Button>
				</div>
			</div>
		</>
	);
}
