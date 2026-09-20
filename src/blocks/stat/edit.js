import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import { useState } from '@wordpress/element';

import './editor.scss';

// Keep in sync with block.json and render.php.
const MAX_VALUE = 1000000000000;
const MAX_DECIMALS = 3;
const MAX_AFFIX_LENGTH = 10;

/**
 * Keeps a number inside [0, MAX_VALUE].
 *
 * @param {number} number Number to clamp.
 * @return {number} The clamped number.
 */
const clampValue = ( number ) => Math.min( MAX_VALUE, Math.max( 0, number ) );

/**
 * Reads what the author typed in the number field. A comma counts as the
 * decimal point; an empty field is 0.
 *
 * @param {string} text Raw text of the field.
 * @return {number|null} The number, or null when the text is not a number.
 */
const parseValue = ( text ) => {
	const clean = text.trim().replace( ',', '.' );

	if ( clean === '' ) {
		return 0;
	}
	if ( ! /^\d*\.?\d*$/.test( clean ) || clean === '.' ) {
		return null;
	}

	return clampValue( Number( clean ) );
};

/**
 * Formats the value for the canvas: the final text, with the language of the
 * editor. On the front end the separators come from the site language
 * (number_format_i18n), so they can differ slightly here.
 *
 * @param {number} value    Value to format.
 * @param {number} decimals Number of decimals.
 * @return {string} The formatted number.
 */
const formatValue = ( value, decimals ) => {
	const options = {
		minimumFractionDigits: decimals,
		maximumFractionDigits: decimals,
	};

	try {
		return new Intl.NumberFormat(
			document.documentElement.lang || undefined,
			options
		).format( value );
	} catch {
		return value.toFixed( decimals );
	}
};

export default function Edit( { attributes, setAttributes } ) {
	const { value, decimals, prefix, suffix, label } = attributes;

	// Guard against out-of-range values, same rules as render.php.
	const safeValue = Number.isFinite( value ) ? clampValue( value ) : 0;
	const safeDecimals = Number.isInteger( decimals )
		? Math.min( MAX_DECIMALS, Math.max( 0, decimals ) )
		: 0;

	const safePrefix = typeof prefix === 'string' ? prefix : '';
	const safeSuffix = typeof suffix === 'string' ? suffix : '';

	// While the author types, the field shows the raw text; otherwise the
	// formatted final value, as it will look on the page.
	const [ draft, setDraft ] = useState( null );
	const numberText = draft ?? formatValue( safeValue, safeDecimals );
	const isInvalid = draft !== null && parseValue( draft ) === null;

	const blockProps = useBlockProps();

	// Same structure and classes as render.php, with only the final layer:
	// the count-up never runs in the editor.
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Stat', 'gl-infinite-theme' ) }>
					<RangeControl
						label={ __( 'Decimal places', 'gl-infinite-theme' ) }
						value={ safeDecimals }
						onChange={ ( nextDecimals ) =>
							setAttributes( {
								decimals: nextDecimals ?? 0,
							} )
						}
						min={ 0 }
						max={ MAX_DECIMALS }
						help={ __(
							'How many digits to show after the decimal point.',
							'gl-infinite-theme'
						) }
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<RichText
					tagName="dt"
					className="glinf-stat__label"
					value={ label }
					onChange={ ( nextLabel ) =>
						setAttributes( { label: nextLabel } )
					}
					placeholder={ __( 'Add a label…', 'gl-infinite-theme' ) }
					aria-label={ __( 'Label', 'gl-infinite-theme' ) }
					allowedFormats={ [ 'core/bold', 'core/italic' ] }
					disableLineBreaks
				/>
				<dd className="glinf-stat__value">
					<span className="glinf-stat__final">
						<input
							type="text"
							className="glinf-stat__input"
							value={ safePrefix }
							onChange={ ( event ) =>
								setAttributes( { prefix: event.target.value } )
							}
							size={ Math.max( 1, safePrefix.length ) }
							maxLength={ MAX_AFFIX_LENGTH }
							placeholder="€"
							aria-label={ __( 'Prefix', 'gl-infinite-theme' ) }
						/>
						<input
							type="text"
							inputMode="decimal"
							className="glinf-stat__input"
							value={ numberText }
							onFocus={ () => setDraft( String( safeValue ) ) }
							onChange={ ( event ) => {
								const text = event.target.value;
								const next = parseValue( text );

								setDraft( text );
								if ( next !== null ) {
									setAttributes( { value: next } );
								}
							} }
							onBlur={ () => setDraft( null ) }
							size={ Math.max( 1, numberText.length ) }
							aria-label={ __( 'Value', 'gl-infinite-theme' ) }
							aria-invalid={ isInvalid }
						/>
						<input
							type="text"
							className="glinf-stat__input"
							value={ safeSuffix }
							onChange={ ( event ) =>
								setAttributes( { suffix: event.target.value } )
							}
							size={ Math.max( 1, safeSuffix.length ) }
							maxLength={ MAX_AFFIX_LENGTH }
							placeholder="+"
							aria-label={ __( 'Suffix', 'gl-infinite-theme' ) }
						/>
					</span>
				</dd>
			</div>
		</>
	);
}
