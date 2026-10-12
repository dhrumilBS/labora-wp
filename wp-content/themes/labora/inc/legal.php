<?php
/**
 * Legal pages (template "Legal page": Privacy, Terms, Cookies), edited in the block editor.
 *
 *   Summary under the title   the page's excerpt
 *   "Last updated"            field in the sidebar (empty: the date of the last edit)
 *   Draft notice              switch and text in the sidebar
 *   "On this page"            every <h2> in the text; its "HTML anchor" (Block > Advanced) is the section's link
 *   Other legal pages         the other pages using this template, in page order
 *   "To confirm" highlight    a text format in the editor toolbar (marks wording counsel still has to confirm)
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

// Pages have an excerpt (the summary under the title of legal pages)
add_action( 'init', fn() => add_post_type_support( 'page', 'excerpt' ) );

// "To confirm" text format in the block editor
add_action( 'enqueue_block_editor_assets', function () {
	wp_enqueue_script( 'labora-editor-formats', labora_asset( 'js/editor-formats.js' ), array( 'wp-rich-text', 'wp-block-editor', 'wp-element', 'wp-i18n' ), labora_asset_version( 'js/editor-formats.js' ), true );
} );

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	acf_add_local_field_group( array(
		'key'      => 'group_labora_legal',
		'title'    => __( 'Legal page', 'labora' ),
		'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-templates/legal.php' ) ) ),
		'position' => 'side',
		'fields'   => array(
			array( 'key' => 'field_lgl_updated', 'name' => 'updated', 'label' => __( 'Last updated', 'labora' ), 'type' => 'date_picker', 'display_format' => 'M j, Y', 'return_format' => 'Y-m-d',
				'instructions' => __( 'Empty: the date of the last edit.', 'labora' ) ),
			array( 'key' => 'field_lgl_draft', 'name' => 'draft', 'label' => __( 'Show the draft notice', 'labora' ), 'type' => 'true_false', 'ui' => 1,
				'instructions' => __( 'Turn off once counsel has approved the text.', 'labora' ) ),
			array( 'key' => 'field_lgl_draft_text', 'name' => 'draft_text', 'label' => __( 'Draft notice', 'labora' ), 'type' => 'textarea', 'rows' => 3, 'new_lines' => '',
				'conditional_logic' => array( array( array( 'field' => 'field_lgl_draft', 'operator' => '==', 'value' => '1' ) ) ) ),
		),
	) );
} );

/**
 * The page's text in <section>s, one per <h2>, as on the HTML site: <section id="anchor"><h2 id="anchor-h">.
 * The anchor is the heading's "HTML anchor", or made from its text.
 *
 * @return array{0:string,1:array<int,array{0:string,1:string}>} [ html, [ [ anchor, heading text ], ... ] ]
 */
function labora_legal_sections(): array {
	$html  = str_replace( ']]>', ']]&gt;', apply_filters( 'the_content', get_the_content() ) );
	$parts = preg_split( '#(?=<h2[\s>])#i', $html );
	$out   = '';
	$toc   = array();
	$used  = array();
	foreach ( $parts as $part ) {
		if ( ! preg_match( '#^<h2(\s[^>]*)?>(.*?)</h2>#is', $part, $h ) ) {
			$out .= $part; // text before the first heading
			continue;
		}
		$text   = trim( wp_strip_all_tags( $h[2] ) );
		$anchor = preg_match( '/\bid=["\']([^"\']+)["\']/', $h[1] ?? '', $id ) ? $id[1] : ( sanitize_title( $text ) ?: 'section' );
		for ( $base = $anchor, $i = 2; isset( $used[ $anchor ] ); $i++ ) {
			$anchor = "{$base}-{$i}";
		}
		$used[ $anchor ] = true;
		$toc[]           = array( $anchor, $text );
		$body            = trim( substr( $part, strlen( $h[0] ) ) );
		$out            .= sprintf( '<section id="%1$s" aria-labelledby="%1$s-h"><h2 id="%1$s-h">%2$s</h2>%3$s</section>' . "\n", esc_attr( $anchor ), $h[2], $body );
	}
	return array( $out, $toc );
}

/** The other legal pages: [ WP_Post ... ] in page order. */
function labora_legal_others(): array {
	return get_pages( array(
		'meta_key'    => '_wp_page_template',
		'meta_value'  => 'page-templates/legal.php',
		'sort_column' => 'menu_order,post_title',
		'exclude'     => array( get_the_ID() ),
	) );
}
