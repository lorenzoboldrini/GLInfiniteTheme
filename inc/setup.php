<?php
/**
 * Theme setup: text domain and theme supports.
 *
 * @package gl-infinite-theme
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Registers theme supports and loads translations.
 *
 * @return void
 */
function glinf_setup(): void {
	load_theme_textdomain( 'gl-infinite-theme', GLINF_DIR . 'languages' );

	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'responsive-embeds' );
}
add_action( 'after_setup_theme', 'glinf_setup' );
