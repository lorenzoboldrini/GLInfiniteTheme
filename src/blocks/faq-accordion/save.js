import { InnerBlocks } from '@wordpress/block-editor';

// Dynamic block: the markup comes from render.php. The inner blocks (the FAQ
// items) are still serialized here, otherwise they would be lost on save.
export default function save() {
	return <InnerBlocks.Content />;
}
