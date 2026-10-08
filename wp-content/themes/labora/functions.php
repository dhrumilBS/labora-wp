<?php
/**
 * Labora theme bootstrap. Each concern lives in its own file under inc/.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

define( 'LABORA_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'LABORA_DIR', get_template_directory() );
define( 'LABORA_URI', get_template_directory_uri() );

require LABORA_DIR . '/inc/setup.php';    // theme supports, menus, image sizes
require LABORA_DIR . '/inc/assets.php';   // styles, scripts, font preload, resource versions
require LABORA_DIR . '/inc/cleanup.php';  // remove WordPress output the site does not use
require LABORA_DIR . '/inc/helpers.php';  // small template helpers (icons, asset URLs)
require LABORA_DIR . '/inc/menus.php';    // menu locations and header/footer menu markup
require LABORA_DIR . '/inc/seo.php';      // Yoast additions (homepage structured data)
require LABORA_DIR . '/inc/cli.php';      // WP-CLI: wp labora seed-menus (loaded only in WP-CLI)
