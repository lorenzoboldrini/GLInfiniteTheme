import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	MediaPlaceholder,
	MediaUpload,
	MediaUploadCheck,
	RichText,
	URLInput,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	Flex,
	FlexItem,
	PanelBody,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

import './editor.scss';

// Keep in sync with block.json and render.php.
const TITLE_LEVELS = [ 2, 3, 4, 5, 6 ];
const DEFAULT_TITLE_LEVEL = 3;
const ALLOWED_MEDIA_TYPES = [ 'image' ];

export default function Edit( { attributes, setAttributes } ) {
	const { mediaId, title, text, linkUrl, linkLabel, linkNewTab, titleLevel } =
		attributes;

	// Guard against an out-of-range value so the tag name is always h2-h6.
	const safeTitleLevel = TITLE_LEVELS.includes( titleLevel )
		? titleLevel
		: DEFAULT_TITLE_LEVEL;

	const { media, mediaResolved } = useSelect(
		( select ) => {
			if ( ! mediaId ) {
				return { media: null, mediaResolved: true };
			}

			const { getMedia, hasFinishedResolution } = select( coreStore );
			const args = [ mediaId, { context: 'view' } ];

			return {
				media: getMedia( ...args ),
				mediaResolved: hasFinishedResolution( 'getMedia', args ),
			};
		},
		[ mediaId ]
	);

	const onSelectImage = ( selected ) => {
		if ( selected?.id ) {
			setAttributes( { mediaId: selected.id } );
		}
	};

	const onRemoveImage = () => {
		setAttributes( { mediaId: 0 } );
	};

	// Same rule as render.php: the label link only exists together with a URL.
	const hasLabelLink = '' !== linkUrl.trim() && '' !== linkLabel.trim();

	const imageSize = media?.media_details?.sizes?.large;
	const imageUrl = imageSize?.source_url ?? media?.source_url;
	const imageWidth = imageSize?.width ?? media?.media_details?.width;
	const imageHeight = imageSize?.height ?? media?.media_details?.height;
	const hasImage = !! mediaId && !! imageUrl && 'image' === media?.media_type;

	const blockProps = useBlockProps();

	let imageElement;
	if ( hasImage ) {
		// The alt comes from the Media Library, exactly as on the front end.
		imageElement = (
			<img
				className="glinf-card__image"
				src={ imageUrl }
				width={ imageWidth }
				height={ imageHeight }
				alt={ media.alt_text || '' }
			/>
		);
	} else if ( mediaId && ! mediaResolved ) {
		imageElement = <Spinner />;
	} else {
		imageElement = (
			<MediaPlaceholder
				icon="format-image"
				labels={ {
					title: __( 'Image', 'gl-infinite-theme' ),
					instructions: mediaId
						? __(
								'The selected image is no longer available. Choose another one.',
								'gl-infinite-theme'
							)
						: __(
								'Upload an image or pick one from the Media Library.',
								'gl-infinite-theme'
							),
				} }
				onSelect={ onSelectImage }
				accept="image/*"
				allowedTypes={ ALLOWED_MEDIA_TYPES }
				multiple={ false }
			/>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Image', 'gl-infinite-theme' ) }>
					<Flex justify="flex-start" gap={ 4 }>
						<FlexItem>
							<MediaUploadCheck>
								<MediaUpload
									onSelect={ onSelectImage }
									allowedTypes={ ALLOWED_MEDIA_TYPES }
									value={ mediaId || undefined }
									render={ ( { open } ) => (
										<Button
											variant="secondary"
											onClick={ open }
											__next40pxDefaultSize
										>
											{ mediaId
												? __(
														'Replace image',
														'gl-infinite-theme'
													)
												: __(
														'Select image',
														'gl-infinite-theme'
													) }
										</Button>
									) }
								/>
							</MediaUploadCheck>
						</FlexItem>
						<FlexItem>
							{ /* Always rendered and only disabled: a button that disappears would drop the keyboard focus. */ }
							<Button
								variant="link"
								isDestructive
								disabled={ ! mediaId }
								accessibleWhenDisabled
								onClick={ onRemoveImage }
							>
								{ __( 'Remove image', 'gl-infinite-theme' ) }
							</Button>
						</FlexItem>
					</Flex>
					<p className="components-base-control__help">
						{ __(
							'The alt text comes from the Media Library. Leave it empty there to mark the image as decorative.',
							'gl-infinite-theme'
						) }
					</p>
				</PanelBody>

				<PanelBody title={ __( 'Link', 'gl-infinite-theme' ) }>
					<URLInput
						label={ __( 'Link URL', 'gl-infinite-theme' ) }
						value={ linkUrl }
						onChange={ ( nextUrl ) =>
							setAttributes( { linkUrl: nextUrl } )
						}
						__nextHasNoMarginBottom
					/>
					<p className="components-base-control__help">
						{ __(
							'Only http, https, mailto and tel links are accepted. Without a URL the card is not clickable.',
							'gl-infinite-theme'
						) }
					</p>
					<TextControl
						label={ __( 'Link label', 'gl-infinite-theme' ) }
						value={ linkLabel }
						onChange={ ( nextLabel ) =>
							setAttributes( { linkLabel: nextLabel } )
						}
						help={ __(
							'Shown as a link at the bottom of the card. Leave empty to make the title the link.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<ToggleControl
						label={ __( 'Open in a new tab', 'gl-infinite-theme' ) }
						checked={ !! linkNewTab }
						onChange={ ( nextValue ) =>
							setAttributes( { linkNewTab: nextValue } )
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

			<article { ...blockProps }>
				{ imageElement }
				<RichText
					tagName={ `h${ safeTitleLevel }` }
					className="glinf-card__title"
					value={ title }
					onChange={ ( nextTitle ) =>
						setAttributes( { title: nextTitle } )
					}
					placeholder={ __( 'Add a title…', 'gl-infinite-theme' ) }
					aria-label={ __( 'Title', 'gl-infinite-theme' ) }
					allowedFormats={ [ 'core/italic' ] }
					disableLineBreaks
				/>
				<RichText
					tagName="p"
					className="glinf-card__text"
					value={ text }
					onChange={ ( nextText ) =>
						setAttributes( { text: nextText } )
					}
					placeholder={ __(
						'Add a short text…',
						'gl-infinite-theme'
					) }
					aria-label={ __( 'Text', 'gl-infinite-theme' ) }
					allowedFormats={ [ 'core/bold', 'core/italic' ] }
				/>
				{ hasLabelLink && (
					<span className="glinf-card__link">{ linkLabel }</span>
				) }
			</article>
		</>
	);
}
