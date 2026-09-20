import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	RichText,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';

import './editor.scss';

// Keep in sync with block.json and render.php.
const TITLE_LEVELS = [ 2, 3, 4, 5, 6 ];
const DEFAULT_TITLE_LEVEL = 3;

// Any block can go in the answer; a paragraph is the starting point.
const TEMPLATE = [
	[
		'core/paragraph',
		{ placeholder: __( 'Add the answer…', 'gl-infinite-theme' ) },
	],
];

export default function Edit( {
	attributes,
	setAttributes,
	context,
	clientId,
} ) {
	const { question, open } = attributes;

	// The heading level is chosen on the parent accordion and arrives as block
	// context. Guard against an out-of-range value so the tag is always h2-h6.
	const contextLevel = Number( context[ 'glinf/faqTitleLevel' ] );
	const safeTitleLevel = TITLE_LEVELS.includes( contextLevel )
		? contextLevel
		: DEFAULT_TITLE_LEVEL;

	/*
	 * The canvas cannot use a real <details>: an interactive element that
	 * holds editable blocks fights with the editor (clicks and keys on the
	 * summary toggle it instead of placing the caret). It uses the same
	 * classes on plain elements and an explicit toggle button instead. This
	 * state only affects the canvas; the "Open by default" attribute is what
	 * is saved. A new (empty) item starts open so it can be typed into at once.
	 */
	const [ isOpen, setIsOpen ] = useState( open || ! question );

	// Selecting a block of the answer (e.g. from the list view) reveals it.
	const hasSelectedAnswer = useSelect(
		( select ) =>
			select( blockEditorStore ).hasSelectedInnerBlock( clientId, true ),
		[ clientId ]
	);
	useEffect( () => {
		if ( hasSelectedAnswer ) {
			setIsOpen( true );
		}
	}, [ hasSelectedAnswer ] );

	const blockProps = useBlockProps( {
		className: isOpen ? 'is-open' : undefined,
	} );
	const answerProps = useInnerBlocksProps(
		{ className: 'glinf-faq-item__answer', hidden: ! isOpen },
		{ template: TEMPLATE, templateLock: false }
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Question', 'gl-infinite-theme' ) }>
					<ToggleControl
						label={ __( 'Open by default', 'gl-infinite-theme' ) }
						checked={ !! open }
						onChange={ ( nextOpen ) =>
							setAttributes( { open: nextOpen } )
						}
						help={ __(
							'The answer is already visible when the page loads. When the accordion shows one answer at a time, only the first open question stays open.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="glinf-faq-item__summary">
					<RichText
						tagName={ `h${ safeTitleLevel }` }
						className="glinf-faq-item__question"
						value={ question }
						onChange={ ( nextQuestion ) =>
							setAttributes( { question: nextQuestion } )
						}
						placeholder={ __(
							'Add the question…',
							'gl-infinite-theme'
						) }
						aria-label={ __( 'Question', 'gl-infinite-theme' ) }
						allowedFormats={ [ 'core/bold', 'core/italic' ] }
						disableLineBreaks
					/>
					<button
						type="button"
						className="glinf-faq-item__toggle"
						aria-expanded={ isOpen }
						aria-label={ __(
							'Show or hide the answer',
							'gl-infinite-theme'
						) }
						onClick={ () => setIsOpen( ! isOpen ) }
					/>
				</div>
				<div { ...answerProps } />
			</div>
		</>
	);
}
