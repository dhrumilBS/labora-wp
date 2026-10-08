<?php
/**
 * Remove default WordPress front-end output the Labora design does not use. It keeps pages as light as the
 * HTML site (which scored 100 on Lighthouse). The admin and the block editor are not affected.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

// Emoji detection script and styles
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
add_filter( 'emoji_svg_url', '__return_false' );

// Header links nobody uses on a marketing site
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_generator' );               // hides the WordPress version
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'feed_links_extra', 3 );        // comment feeds (comments are off)
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );

// Block library CSS: the site's own stylesheets style the content. Templates that render block content
// re-enqueue what they need.
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_singular( 'post' ) ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'classic-theme-styles' );
		wp_dequeue_style( 'global-styles' );
	}
}, 100 );

// No XML-RPC (not needed; a common attack target)
add_filter( 'xmlrpc_enabled', '__return_false' );

// WordPress sends /login/, /admin/, and /dashboard/ to its own admin screens. On this site "Sign in" (/login/) is
// the product's sign-in, so those shortcuts are turned off: /login/ shows the "coming soon" 404 page until the
// product sign-in exists, and the WordPress admin stays at /wp-admin/ only.
remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
