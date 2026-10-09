<?php
/**
 * Labora theme bootstrap. Each concern lives in its own file under inc/.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

// Parent theme paths (constant even when the Labora Child theme is active). For files a child may override,
// use get_theme_file_path() / get_theme_file_uri() or labora_asset(), which look in the child first.
define( 'LABORA_VERSION', wp_get_theme( 'labora' )->get( 'Version' ) );
define( 'LABORA_DIR', get_template_directory() );
define( 'LABORA_URI', get_template_directory_uri() );

require LABORA_DIR . '/inc/setup.php';    // theme supports, menus, image sizes
require LABORA_DIR . '/inc/assets.php';   // styles, scripts, font preload, resource versions
require LABORA_DIR . '/inc/cleanup.php';  // remove WordPress output the site does not use
require LABORA_DIR . '/inc/helpers.php';  // small template helpers (icons, asset URLs)
require LABORA_DIR . '/inc/blog.php';     // the blog: query, covers, topics, table of contents, block styles, structured data
require LABORA_DIR . '/inc/blog-fields.php'; // blog fields: post, Blog page, topics, authors (SCF)
require LABORA_DIR . '/inc/blog-blocks.php'; // article blocks: key takeaways, notes, demo box, key numbers, before/after, questions
require LABORA_DIR . '/inc/template-tags.php'; // post cards, byline, pagination for the WordPress templates
require LABORA_DIR . '/inc/settings.php'; // Labora Settings: company, contact, social, header buttons (SCF options page)
require LABORA_DIR . '/inc/menus.php';    // menu locations and header/footer menu markup
require LABORA_DIR . '/inc/widgets.php';  // footer link columns as widgets
require LABORA_DIR . '/inc/seo.php';      // Yoast additions (homepage structured data)
require LABORA_DIR . '/inc/cli.php';      // WP-CLI: wp labora seed-menus (loaded only in WP-CLI)
