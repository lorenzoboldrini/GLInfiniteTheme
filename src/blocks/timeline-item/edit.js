import { __ } from '@wordpress/i18n';
import {
	RichText,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';

import './editor.scss';

// Keep in sync with block.json and render.php.
const TITLE_LEVELS = [ 2, 3, 4, 5, 6 ];
const DEFAULT_TITLE_LEVEL = 3;

// Any block can go in the body of a step; a paragraph is the starting point.
const TEMPLATE = [
	[
		'core/paragraph',
		{ placeholder: __( 'Add the step content…', 'gl-infinite-theme' ) },
	],
];

export default function Edit( { attributes, setAttributes, context } ) {
	const { label, title } = attributes;

	// The heading level is chosen on the parent timeline and arrives as block
	// context. Guard against an out-of-range value so the tag is always h2-h6.
	const contextLevel = Number( context[ 'glinf/timelineTitleLevel' ] );
	const safeTitleLevel = TITLE_LEVELS.includes( contextLevel )
		? contextLevel
		: DEFAULT_TITLE_LEVEL;

	const blockProps = useBlockProps();
	const bodyProps = useInnerBlocksProps(
		{ className: 'glinf-timeline-item__body' },
		{ template: TEMPLATE, templateLock: false }
	);

	// Same structure as render.php: rail, then the card with label, title, body.
	return (
		<li { ...blockProps }>
			<span className="glinf-timeline-item__rail" aria-hidden="true" />
			<div className="glinf-timeline-item__card">
				<RichText
					tagName="p"
					className="glinf-timeline-item__label"
					value={ label }
					onChange={ ( nextLabel ) =>
						setAttributes( { label: nextLabel } )
					}
					placeholder={ __(
						'Date or label, e.g. 2024',
						'gl-infinite-theme'
					) }
					aria-label={ __( 'Label', 'gl-infinite-theme' ) }
					allowedFormats={ [] }
					disableLineBreaks
				/>
				<RichText
					tagName={ `h${ safeTitleLevel }` }
					className="glinf-timeline-item__title"
					value={ title }
					onChange={ ( nextTitle ) =>
						setAttributes( { title: nextTitle } )
					}
					placeholder={ __( 'Add a title…', 'gl-infinite-theme' ) }
					aria-label={ __( 'Title', 'gl-infinite-theme' ) }
					allowedFormats={ [ 'core/bold', 'core/italic' ] }
					disableLineBreaks
				/>
				<div { ...bodyProps } />
			</div>
		</li>
	);
}
