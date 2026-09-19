import { __, sprintf } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import './editor.scss';

// Any block can go inside a slide; a paragraph is the starting point.
const TEMPLATE = [
	[
		'core/paragraph',
		{ placeholder: __( 'Add the slide content…', 'gl-infinite-theme' ) },
	],
];

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { label } = attributes;

	const { position, total } = useSelect(
		( select ) => {
			const { getBlockIndex, getBlockRootClientId, getBlockCount } =
				select( blockEditorStore );
			const rootClientId = getBlockRootClientId( clientId );

			return {
				position: getBlockIndex( clientId ) + 1,
				total: getBlockCount( rootClientId ),
			};
		},
		[ clientId ]
	);

	// The "Slide N of M" tag is drawn by CSS from this attribute (editor only).
	const blockProps = useBlockProps( {
		'data-glinf-slide-position': sprintf(
			/* translators: 1: slide number, 2: total number of slides. */
			__( 'Slide %1$d of %2$d', 'gl-infinite-theme' ),
			position,
			total
		),
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
		templateLock: false,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Slide', 'gl-infinite-theme' ) }>
					<TextControl
						label={ __( 'Label', 'gl-infinite-theme' ) }
						value={ label }
						onChange={ ( nextLabel ) =>
							setAttributes( { label: nextLabel } )
						}
						help={ __(
							'Optional. Screen readers announce it after the slide number, for example "2 of 5: Our services". It is not shown on the page.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...innerBlocksProps } />
		</>
	);
}
