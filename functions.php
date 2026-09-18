<?php
/**
 * Theme bootstrap: constants and includes only. No logic here.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;

define( 'TU_VERSION', wp_get_theme( get_template() )->get( 'Version' ) );
define( 'TU_DIR', trailingslashit( get_template_directory() ) );

require_once TU_DIR . 'inc/setup.php';
require_once TU_DIR . 'inc/blocks/register.php';
