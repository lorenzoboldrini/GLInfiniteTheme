<?php
/**
 * Custom blocks registration: registers every block compiled by wp-scripts.
 *
 * @package gl-infinite-theme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Registers each compiled block found in build/blocks/.
 *
 * Registration points at the COMPILED block.json (build/), not at src/, so the
 * asset file paths declared there resolve to the built files. No manual
 * enqueue is needed: WordPress loads the assets declared in block.json on its
 * own. "style" and "viewScript*" only when the block is rendered on the page,
 * "editorScript" and "editorStyle" only inside the editor.
 *
 * When build/ is missing (theme installed from git without running the build)
 * the glob finds nothing and no block is registered: no output, no notices.
 *
 * @return void
 */
function glinf_register_blocks(): void {
	$block_files = glob( GLINF_DIR . 'build/blocks/*/block.json' );

	// glob() returns false on failure and an empty array when nothing matches.
	if ( false === $block_files || array() === $block_files ) {
		return;
	}

	foreach ( $block_files as $block_file ) {
		register_block_type( dirname( $block_file ) );
	}
}
add_action( 'init', 'glinf_register_blocks' );
