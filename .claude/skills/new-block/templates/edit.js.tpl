import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';

import './editor.scss';

export default function Edit() {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			{ __( 'Block content goes here.', '{{textdomain}}' ) }
		</div>
	);
}
