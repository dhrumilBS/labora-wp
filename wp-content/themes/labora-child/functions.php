<?php
/**
 * Labora Child: site-specific changes on top of the Labora parent theme.
 *
 * How to change things here:
 *  - CSS:        assets/css/child.css   (loaded after the parent's styles and page bundles)
 *  - JavaScript: assets/js/child.js     (loaded after the parent's scripts)
 *  - Templates:  copy a file from the parent (e.g. front-page.php, header.php, footer.php,
 *                template-parts/sprite.php) to the same path here and edit the copy. WordPress uses the child's copy.
 *  - Images:     put a file at the same path as in the parent's assets/ (e.g. assets/img/og-image.png) and
 *                labora_asset() serves the child's version instead.
 *  - PHP:        add files under inc/ and require them below. Parent functions wrapped in function_exists()
 *                can be replaced by defining them here first.
 *
 * The parent loads first for template files but after this file for functions.php, so hooks registered here
 * run alongside the parent's; use priorities to run before or after them.
 *
 * @package Labora_Child
 */

defined( 'ABSPATH' ) || exit;

define( 'LABORA_CHILD_DIR', get_stylesheet_directory() );
define( 'LABORA_CHILD_URI', get_stylesheet_directory_uri() );

/** Version a child asset by its modification time. */
function labora_child_asset_version( string $path ): string {
	$file = LABORA_CHILD_DIR . '/assets/' . ltrim( $path, '/' );
	return file_exists( $file ) ? (string) filemtime( $file ) : wp_get_theme()->get( 'Version' );
}

/**
 * True when a child asset has real code in it, not just its explanatory comment. Empty files are not
 * enqueued, so they cost no extra request (the site keeps its Lighthouse performance).
 */
function labora_child_asset_has_code( string $path ): bool {
	$file = LABORA_CHILD_DIR . '/assets/' . ltrim( $path, '/' );
	if ( ! file_exists( $file ) ) {
		return false;
	}
	$code = preg_replace( '#/\*.*?\*/#s', '', (string) file_get_contents( $file ) ); // drop /* comments */
	$code = preg_replace( '#\(function \(\) \{\s*\'use strict\';\s*\}\)\(\);#', '', $code ); // the empty JS wrapper
	return '' !== trim( $code );
}

// Child CSS and JS, after everything the parent enqueues (styles, page bundles)
add_action( 'wp_enqueue_scripts', function () {
	if ( labora_child_asset_has_code( 'css/child.css' ) ) {
		$deps = array_values( array_filter( array( 'labora', 'labora-pages', 'labora-blog' ), 'wp_style_is' ) );
		wp_enqueue_style( 'labora-child', LABORA_CHILD_URI . '/assets/css/child.css', $deps, labora_child_asset_version( 'css/child.css' ) );
	}
	if ( labora_child_asset_has_code( 'js/child.js' ) ) {
		wp_enqueue_script( 'labora-child', LABORA_CHILD_URI . '/assets/js/child.js', array( 'labora' ), labora_child_asset_version( 'js/child.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
}, 20 );

// Site-specific PHP
require LABORA_CHILD_DIR . '/inc/forms.php'; // the site's Contact Form 7 forms (wp labora forms-setup, labora_form())
require LABORA_CHILD_DIR . '/inc/consent.php'; // TrustLayer Consent defaults for this site (banner wording, buttons, categories)
