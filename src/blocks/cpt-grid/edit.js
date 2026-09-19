import { __, sprintf } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Disabled,
	PanelBody,
	Placeholder,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';
import ServerSideRender from '@wordpress/server-side-render';

import './editor.scss';

// Keep in sync with block.json and render.php.
const DEFAULT_POST_TYPE = 'post';
const DEFAULT_POSTS_PER_PAGE = 6;
const DEFAULT_COLUMNS = 3;
const MAX_POSTS_PER_PAGE = 24;
const MAX_COLUMNS = 6;
const TITLE_LEVELS = [ 2, 3, 4, 5, 6 ];
const DEFAULT_TITLE_LEVEL = 3;
// Viewable post types that make no sense as a grid of cards.
const EXCLUDED_POST_TYPES = [ 'attachment' ];
// The REST API returns at most 100 terms per request.
const MAX_TERMS = 100;

/**
 * Keeps the saved value selectable when it is not in the loaded options.
 *
 * Without this the select would silently show its first option while the block
 * still holds a value that no longer exists (e.g. a post type of a theme that
 * has been switched).
 *
 * @param {Array}   options Options built from the loaded records.
 * @param {string}  value   Value saved in the block attributes.
 * @param {boolean} loaded  Whether the records finished loading.
 * @return {Array} Options, with an "unavailable" entry appended if needed.
 */
function withCurrentOption( options, value, loaded ) {
	if (
		! loaded ||
		! value ||
		options.some( ( option ) => option.value === value )
	) {
		return options;
	}

	return [
		...options,
		{
			label: sprintf(
				/* translators: %s: internal name of a post type or taxonomy that is no longer registered. */
				__( '%s (unavailable)', 'gl-infinite-theme' ),
				value
			),
			value,
		},
	];
}

/**
 * Preview shown in the editor when the query has no results.
 *
 * @return {Element} Placeholder.
 */
function EmptyResponsePlaceholder() {
	return (
		<Placeholder label={ __( 'CPT Grid', 'gl-infinite-theme' ) }>
			{ __(
				'No posts found. Change the post type or the filter in the block settings.',
				'gl-infinite-theme'
			) }
		</Placeholder>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		postType,
		postsPerPage,
		columns,
		orderBy,
		order,
		taxonomy,
		termId,
		showImage,
		showExcerpt,
		showDate,
		titleLevel,
	} = attributes;

	const { postTypes, taxonomies, terms } = useSelect(
		( select ) => {
			const { getEntityRecords } = select( coreStore );

			return {
				postTypes: getEntityRecords( 'root', 'postType', {
					per_page: -1,
				} ),
				taxonomies: getEntityRecords( 'root', 'taxonomy', {
					type: postType,
					per_page: -1,
					context: 'view',
				} ),
				// Terms are only requested once a taxonomy has been chosen.
				terms: taxonomy
					? getEntityRecords( 'taxonomy', taxonomy, {
							per_page: MAX_TERMS,
							orderby: 'name',
							order: 'asc',
							context: 'view',
							_fields: 'id,name',
						} )
					: null,
			};
		},
		[ postType, taxonomy ]
	);

	const postTypeOptions = withCurrentOption(
		( postTypes || [] )
			.filter(
				( type ) =>
					type.viewable && ! EXCLUDED_POST_TYPES.includes( type.slug )
			)
			.map( ( type ) => ( { label: type.name, value: type.slug } ) ),
		postType,
		null !== postTypes
	);

	const taxonomyOptions = withCurrentOption(
		( taxonomies || [] )
			// Post formats are not something visitors browse by.
			.filter( ( item ) => 'post_format' !== item.slug )
			.map( ( item ) => ( { label: item.name, value: item.slug } ) ),
		taxonomy,
		null !== taxonomies
	);

	const termOptions = ( terms || [] ).map( ( term ) => ( {
		label: decodeEntities( term.name ),
		value: String( term.id ),
	} ) );

	// Only the attributes the server render needs: no block supports, no align.
	const previewAttributes = {
		postType,
		postsPerPage,
		columns,
		orderBy,
		order,
		taxonomy,
		termId,
		showImage,
		showExcerpt,
		showDate,
		titleLevel,
	};

	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Content', 'gl-infinite-theme' ) }>
					<SelectControl
						label={ __( 'Post type', 'gl-infinite-theme' ) }
						value={ postType }
						options={ postTypeOptions }
						onChange={ ( nextPostType ) =>
							// Taxonomies belong to a post type: drop the filter with it.
							setAttributes( {
								postType: nextPostType || DEFAULT_POST_TYPE,
								taxonomy: '',
								termId: 0,
							} )
						}
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					{ taxonomyOptions.length > 0 && (
						<SelectControl
							label={ __(
								'Filter by taxonomy',
								'gl-infinite-theme'
							) }
							value={ taxonomy }
							options={ [
								{
									label: __(
										'No filter',
										'gl-infinite-theme'
									),
									value: '',
								},
								...taxonomyOptions,
							] }
							onChange={ ( nextTaxonomy ) =>
								setAttributes( {
									taxonomy: nextTaxonomy,
									termId: 0,
								} )
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					) }
					{ taxonomy && (
						<SelectControl
							label={ __( 'Term', 'gl-infinite-theme' ) }
							value={ String( termId ) }
							options={ [
								{
									label: __(
										'All terms',
										'gl-infinite-theme'
									),
									value: '0',
								},
								...termOptions,
							] }
							onChange={ ( nextTermId ) =>
								setAttributes( {
									termId: parseInt( nextTermId, 10 ) || 0,
								} )
							}
							__nextHasNoMarginBottom
							__next40pxDefaultSize
						/>
					) }
					<RangeControl
						label={ __( 'Number of posts', 'gl-infinite-theme' ) }
						value={ postsPerPage }
						min={ 1 }
						max={ MAX_POSTS_PER_PAGE }
						onChange={ ( nextValue ) =>
							setAttributes( {
								postsPerPage:
									nextValue ?? DEFAULT_POSTS_PER_PAGE,
							} )
						}
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<SelectControl
						label={ __( 'Order by', 'gl-infinite-theme' ) }
						value={ orderBy }
						options={ [
							{
								label: __( 'Date', 'gl-infinite-theme' ),
								value: 'date',
							},
							{
								label: __( 'Title', 'gl-infinite-theme' ),
								value: 'title',
							},
							{
								label: __(
									'Last modified',
									'gl-infinite-theme'
								),
								value: 'modified',
							},
							{
								label: __( 'Menu order', 'gl-infinite-theme' ),
								value: 'menu_order',
							},
						] }
						onChange={ ( nextOrderBy ) =>
							setAttributes( { orderBy: nextOrderBy } )
						}
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<SelectControl
						label={ __( 'Order', 'gl-infinite-theme' ) }
						value={ order }
						options={ [
							{
								label: __( 'Descending', 'gl-infinite-theme' ),
								value: 'DESC',
							},
							{
								label: __( 'Ascending', 'gl-infinite-theme' ),
								value: 'ASC',
							},
						] }
						onChange={ ( nextOrder ) =>
							setAttributes( { order: nextOrder } )
						}
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Layout', 'gl-infinite-theme' ) }
					initialOpen={ false }
				>
					<RangeControl
						label={ __( 'Columns', 'gl-infinite-theme' ) }
						value={ columns }
						min={ 1 }
						max={ MAX_COLUMNS }
						onChange={ ( nextColumns ) =>
							setAttributes( {
								columns: nextColumns ?? DEFAULT_COLUMNS,
							} )
						}
						help={ __(
							'On narrow screens the grid uses fewer columns automatically.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Card', 'gl-infinite-theme' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __(
							'Show featured image',
							'gl-infinite-theme'
						) }
						checked={ showImage }
						onChange={ ( nextValue ) =>
							setAttributes( { showImage: nextValue } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show excerpt', 'gl-infinite-theme' ) }
						checked={ showExcerpt }
						onChange={ ( nextValue ) =>
							setAttributes( { showExcerpt: nextValue } )
						}
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Show date', 'gl-infinite-theme' ) }
						checked={ showDate }
						onChange={ ( nextValue ) =>
							setAttributes( { showDate: nextValue } )
						}
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __(
							'Title heading level',
							'gl-infinite-theme'
						) }
						value={
							TITLE_LEVELS.includes( titleLevel )
								? titleLevel
								: DEFAULT_TITLE_LEVEL
						}
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
				{ /* Disabled: the preview must not add tab stops or navigate away from the editor. */ }
				<Disabled>
					<ServerSideRender
						block="glinf/cpt-grid"
						attributes={ previewAttributes }
						EmptyResponsePlaceholder={ EmptyResponsePlaceholder }
					/>
				</Disabled>
			</div>
		</>
	);
}
