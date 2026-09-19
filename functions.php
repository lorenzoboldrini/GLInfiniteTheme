<?php
/**
 * Theme bootstrap: constants and includes only. No logic here.
 *
 * @package gl-infinite-theme
 */

defined( 'ABSPATH' ) || exit;

define( 'GLINF_VERSION', wp_get_theme( get_template() )->get( 'Version' ) );
define( 'GLINF_DIR', trailingslashit( get_template_directory() ) );

require_once GLINF_DIR . 'inc/setup.php';
require_once GLINF_DIR . 'inc/blocks/register.php';
require_once GLINF_DIR . 'inc/entities/config.php';
require_once GLINF_DIR . 'inc/entities/capabilities.php';
require_once GLINF_DIR . 'inc/entities/register.php';
require_once GLINF_DIR . 'inc/entities/templates.php';
require_once GLINF_DIR . 'inc/entities/list-data.php';
require_once GLINF_DIR . 'inc/entities/admin-common.php';
require_once GLINF_DIR . 'inc/entities/admin.php';
require_once GLINF_DIR . 'inc/entities/admin-taxonomies.php';
