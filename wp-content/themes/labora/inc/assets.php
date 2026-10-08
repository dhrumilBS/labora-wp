<?php
/**
 * Front-end assets, taken unchanged from the HTML site (assets/).
 * Files are versioned by modification time, so browsers fetch a fresh copy after every change.
 *
 * Global:  styles.min.css + main.min.js (header, menu, reveal animations, Lenis smooth scroll, forms, slider, video)
 * Pages:   pages.min.css + pages.min.js and blog.min.css + blog.min.js are enqueued by the templates that need them
 *          (labora_enqueue_bundle( 'pages' ) / labora_enqueue_bundle( 'blog' )).
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'labora_asset' ) ) :
/**
 * URL of a file in assets/. The child theme's copy wins when it exists (same path under labora-child/assets/),
 * so a child can replace any image, font, script, or stylesheet without editing the parent.
 */
function labora_asset( string $path ): string {
	return get_theme_file_uri( 'assets/' . ltrim( $path, '/' ) );
}
endif;

if ( ! function_exists( 'labora_asset_version' ) ) :
/** Version string for an asset: its modification time (child copy first), or the theme version if it is missing. */
function labora_asset_version( string $path ): string {
	$file = get_theme_file_path( 'assets/' . ltrim( $path, '/' ) );
	return file_exists( $file ) ? (string) filemtime( $file ) : LABORA_VERSION;
}
endif;

if ( ! function_exists( 'labora_enqueue_bundle' ) ) :
/** Enqueue one of the extra CSS/JS bundles (pages, blog) from a template. */
function labora_enqueue_bundle( string $name ): void {
	$css = "css/{$name}.min.css";
	$js  = "js/{$name}.min.js";
	wp_enqueue_style( "labora-{$name}", labora_asset( $css ), array( 'labora' ), labora_asset_version( $css ) );
	wp_enqueue_script( "labora-{$name}", labora_asset( $js ), array( 'labora' ), labora_asset_version( $js ), array( 'strategy' => 'defer', 'in_footer' => true ) );
}
endif;

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'labora', labora_asset( 'css/styles.min.css' ), array(), labora_asset_version( 'css/styles.min.css' ) );
	wp_enqueue_script( 'labora', labora_asset( 'js/main.min.js' ), array(), labora_asset_version( 'js/main.min.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

// Preload the one web font (same as the HTML site) and mark that JavaScript is available before first paint,
// so the .js .reveal animation styles apply without a flash.
add_action( 'wp_head', function () {
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( labora_asset( 'fonts/figtree-latin-wght-normal.woff2' ) )
	);
	echo "<script>document.documentElement.classList.add('js');</script>\n";
	printf( '<meta name="theme-color" content="#0d3a33">' . "\n" );
}, 1 );

// Favicon and touch icon from the theme (a Site Icon set in Customizer replaces these automatically).
add_action( 'wp_head', function () {
	if ( has_site_icon() ) {
		return;
	}
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( labora_asset( 'img/favicon.svg' ) ) );
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( labora_asset( 'img/apple-touch-icon.png' ) ) );
}, 2 );
