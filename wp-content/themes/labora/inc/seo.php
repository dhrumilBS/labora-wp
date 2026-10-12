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

/* ---------- Breadcrumbs and page types, as on the HTML site ---------- */
// (The default share image is Yoast's own setting, Settings > Site basics; `wp labora blog-setup` seeds it.)

/**
 * Breadcrumbs (visible ones and the BreadcrumbList data):
 *  - names without HTML entities ("Centers & outsource labs", not "&#038;")
 *  - Platform and Solutions pages: the parent links to its hub (or, for a Redirect page, to where that leads)
 *  - topic pages: Home > Blog > topic
 */
add_filter( 'wpseo_breadcrumb_links', function ( $links ) {
	if ( is_page() && in_array( get_page_template_slug(), array( 'page-templates/platform.php', 'page-templates/solution.php' ), true ) ) {
		$parent = wp_get_post_parent_id( get_the_ID() );
		if ( $parent ) {
			$crumb = array( 'url' => labora_parent_url( $parent ), 'text' => get_the_title( $parent ) );
			$found = false;
			foreach ( $links as &$link ) {
				if ( (int) ( $link['id'] ?? 0 ) === $parent ) {
					$link  = $crumb;
					$found = true;
				}
			}
			unset( $link );
			if ( ! $found ) {
				array_splice( $links, count( $links ) - 1, 0, array( $crumb ) );
			}
		}
	}
	if ( ( is_category() || is_tag() ) && labora_blog_page_id() ) {
		$blog = array( 'url' => labora_blog_url(), 'text' => get_the_title( labora_blog_page_id() ) );
		if ( ! in_array( $blog['url'], array_column( array_filter( $links, 'is_array' ), 'url' ), true ) ) {
			array_splice( $links, 1, 0, array( $blog ) );
		}
	}
	foreach ( $links as &$link ) {
		if ( isset( $link['text'] ) ) {
			$link['text'] = html_entity_decode( (string) $link['text'], ENT_QUOTES, 'UTF-8' );
		}
	}
	unset( $link );
	return $links;
} );

// The About page is an AboutPage, blog posts are BlogPosting (as on the HTML site)
add_filter( 'wpseo_schema_webpage_type', function ( $type ) {
	if ( is_page() && 'page-templates/about.php' === get_page_template_slug() ) {
		return array( 'WebPage', 'AboutPage' );
	}
	if ( is_page() && 'page-templates/hub.php' === get_page_template_slug() ) {
		return array( 'WebPage', 'CollectionPage' ); // Platform and Solutions hubs list their pages
	}
	return $type;
} );
add_filter( 'wpseo_schema_article_type', function ( $type ) {
	return is_singular( 'post' ) ? 'BlogPosting' : $type;
} );
