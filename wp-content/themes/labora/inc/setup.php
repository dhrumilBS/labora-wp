<?php
/**
 * Theme supports. Menus and editable regions are added page by page, once each structure is approved.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'labora', LABORA_DIR . '/languages' );

	add_theme_support( 'title-tag' );          // Yoast SEO manages the <title>
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );

	// The site's own CSS sets the design; keep the block editor's default styles out of the front end.
	remove_theme_support( 'core-block-patterns' );
} );
