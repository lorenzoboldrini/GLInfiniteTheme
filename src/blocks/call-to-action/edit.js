import { __ } from '@wordpress/i18n';
import {
	AlignmentControl,
	BlockControls,
	InspectorControls,
	RichText,
	URLInput,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

import './editor.scss';

const TITLE_LEVELS = [ 2, 3, 4, 5, 6 ];
const DEFAULT_TITLE_LEVEL = 2;
const DEFAULT_TEXT_ALIGN = 'center';

export default function Edit( { attributes, setAttributes } ) {
	const { title, description, buttonText, buttonUrl, titleLevel, textAlign } =
		attributes;

	// Guard against an out-of-range value so the tag name is always h2-h6.
	const safeTitleLevel = TITLE_LEVELS.includes( titleLevel )
		? titleLevel
		: DEFAULT_TITLE_LEVEL;
	const safeTextAlign = textAlign || DEFAULT_TEXT_ALIGN;

	const blockProps = useBlockProps( {
		className: `has-text-align-${ safeTextAlign }`,
	} );

	return (
		<>
			<BlockControls group="block">
				<AlignmentControl
					value={ safeTextAlign }
					onChange={ ( nextAlign ) =>
						setAttributes( {
							textAlign: nextAlign || DEFAULT_TEXT_ALIGN,
						} )
					}
				/>
			</BlockControls>

			<InspectorControls>
				<PanelBody title={ __( 'Button', 'gl-infinite-theme' ) }>
					<URLInput
						label={ __( 'Button link', 'gl-infinite-theme' ) }
						value={ buttonUrl }
						onChange={ ( nextUrl ) =>
							setAttributes( { buttonUrl: nextUrl } )
						}
						__nextHasNoMarginBottom
					/>
				</PanelBody>
				<PanelBody title={ __( 'Title', 'gl-infinite-theme' ) }>
					<SelectControl
						label={ __( 'Heading level', 'gl-infinite-theme' ) }
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
							'Choose the level that fits the heading hierarchy of the page.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<RichText
					tagName={ `h${ safeTitleLevel }` }
					className="tu-call-to-action__title"
					value={ title }
					onChange={ ( nextTitle ) =>
						setAttributes( { title: nextTitle } )
					}
					placeholder={ __( 'Add a title…', 'gl-infinite-theme' ) }
					aria-label={ __( 'Title', 'gl-infinite-theme' ) }
					allowedFormats={ [ 'core/italic', 'core/link' ] }
					disableLineBreaks
				/>
				<RichText
					tagName="p"
					className="tu-call-to-action__description"
					value={ description }
					onChange={ ( nextDescription ) =>
						setAttributes( { description: nextDescription } )
					}
					placeholder={ __(
						'Add a short description…',
						'gl-infinite-theme'
					) }
					aria-label={ __( 'Description', 'gl-infinite-theme' ) }
					allowedFormats={ [
						'core/bold',
						'core/italic',
						'core/link',
					] }
				/>
				<RichText
					tagName="span"
					className="wp-element-button tu-call-to-action__button"
					value={ buttonText }
					onChange={ ( nextText ) =>
						setAttributes( { buttonText: nextText } )
					}
					placeholder={ __( 'Button text', 'gl-infinite-theme' ) }
					aria-label={ __( 'Button text', 'gl-infinite-theme' ) }
					allowedFormats={ [] }
					disableLineBreaks
				/>
			</div>
		</>
	);
}
