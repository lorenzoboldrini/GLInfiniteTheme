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
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

import './editor.scss';

// Keep in sync with block.json and render.php.
const MIN_COLUMNS = 1;
const MAX_COLUMNS = 6;
const DEFAULT_COLUMNS = 3;
const MIN_DURATION = 0.5;
const MAX_DURATION = 5;
const DEFAULT_DURATION = 2;

const ALLOWED_BLOCKS = [ 'glinf/stat' ];
// Sample figures, so the block looks like something at once. The label is
// required on the front end: a figure without one prints nothing.
const TEMPLATE = [
	[
		'glinf/stat',
		{
			value: 1200,
			suffix: '+',
			label: __( 'Happy customers', 'gl-infinite-theme' ),
		},
	],
	[
		'glinf/stat',
		{
			value: 98,
			suffix: '%',
			label: __( 'Satisfaction rate', 'gl-infinite-theme' ),
		},
	],
	[
		'glinf/stat',
		{
			value: 24,
			suffix: '/7',
			label: __( 'Support', 'gl-infinite-theme' ),
		},
	],
];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { columns, duration, ariaLabel } = attributes;

	// Guard against out-of-range values, same rules as render.php.
	const safeColumns = Number.isFinite( columns )
		? Math.min(
				MAX_COLUMNS,
				Math.max( MIN_COLUMNS, Math.round( columns ) )
			)
		: DEFAULT_COLUMNS;
	const safeDuration = Number.isFinite( duration )
		? Math.min( MAX_DURATION, Math.max( MIN_DURATION, duration ) )
		: DEFAULT_DURATION;

	const statCount = useSelect(
		( select ) => select( blockEditorStore ).getBlockCount( clientId ),
		[ clientId ]
	);
	const { insertBlock } = useDispatch( blockEditorStore );

	// Same columns as the front end: never more than there are figures.
	const effectiveColumns = Math.max( 1, Math.min( safeColumns, statCount ) );
	const blockProps = useBlockProps( {
		style: {
			'--glinf-stats-columns': effectiveColumns,
			'--glinf-stats-columns-medium': Math.min( effectiveColumns, 2 ),
		},
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'glinf-stats-counter__list' },
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
			renderAppender: false,
		}
	);

	const addStat = () => {
		insertBlock( createBlock( 'glinf/stat' ), statCount, clientId );
	};

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Stats Counter', 'gl-infinite-theme' ) }>
					<RangeControl
						label={ __( 'Columns', 'gl-infinite-theme' ) }
						value={ safeColumns }
						onChange={ ( nextColumns ) =>
							setAttributes( {
								columns: nextColumns ?? DEFAULT_COLUMNS,
							} )
						}
						min={ MIN_COLUMNS }
						max={ MAX_COLUMNS }
						help={ __(
							'The number of columns on wide screens. On smaller screens there are fewer, down to one.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<RangeControl
						label={ __(
							'Count-up duration (seconds)',
							'gl-infinite-theme'
						) }
						value={ safeDuration }
						onChange={ ( nextDuration ) =>
							setAttributes( {
								duration: nextDuration ?? DEFAULT_DURATION,
							} )
						}
						min={ MIN_DURATION }
						max={ MAX_DURATION }
						step={ 0.5 }
						help={ __(
							'How long the numbers take to count up when they scroll into view. Visitors who prefer reduced motion always see the final numbers at once.',
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
							'Optional. Read by screen readers to identify this group of figures, for example "Our results". Leave empty for no name.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<dl { ...innerBlocksProps } />
				<div className="glinf-stats-counter__editor-actions">
					<Button variant="secondary" onClick={ addStat }>
						{ __( 'Add stat', 'gl-infinite-theme' ) }
					</Button>
				</div>
			</div>
		</>
	);
}
