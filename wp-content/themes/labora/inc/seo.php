<?php
/**
 * SEO additions on top of Yoast SEO.
 *
 * Yoast outputs the title, meta description, canonical, Open Graph, and the schema.org graph (Organization,
 * WebSite, WebPage). The homepage also carried SoftwareApplication, VideoObject (product tour), and FAQPage data on
 * the HTML site; they are added to Yoast's graph here so the page keeps one consistent JSON-LD block.
 * Source: data/home-schema.json (taken from the HTML site; its URLs are rewritten to this site).
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

/** The HTML site's placeholder origin, replaced by this site's URLs in the stored schema data. */
const LABORA_HTML_ORIGIN = 'https://www.labora.example';

/** Rewrite the HTML site's URLs (pages and /assets/...) to this WordPress site. */
function labora_seo_rewrite_urls( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'labora_seo_rewrite_urls', $value );
	}
	if ( ! is_string( $value ) || ! str_starts_with( $value, LABORA_HTML_ORIGIN ) ) {
		return $value;
	}
	$path = substr( $value, strlen( LABORA_HTML_ORIGIN ) );
	if ( str_starts_with( $path, '/assets/' ) ) {
		return LABORA_URI . $path;
	}
	return home_url( $path ?: '/' );
}

add_filter( 'wpseo_schema_graph', function ( $graph ) {
	if ( ! is_front_page() ) {
		return $graph;
	}
	$file  = get_theme_file_path( 'data/home-schema.json' ); // child theme's copy wins
	$extra = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null;
	if ( ! is_array( $extra ) ) {
		return $graph;
	}
	foreach ( labora_seo_rewrite_urls( $extra ) as $piece ) {
		// Link the pieces to Yoast's own WebPage / Organization nodes
		if ( 'FAQPage' === ( $piece['@type'] ?? '' ) ) {
			$piece['@id']      = home_url( '/#faq' );
			$piece['isPartOf'] = array( '@id' => home_url( '/' ) );
		}
		$graph[] = $piece;
	}
	return $graph;
} );

// Yoast: the homepage has no author archive, comments, or feed links worth exposing
add_filter( 'wpseo_next_rel_link', '__return_false' );
