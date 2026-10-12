<?php
/**
 * FAQ entries, shared by the FAQ page and the Trust Center (admin menu "FAQ").
 *
 *   Entry   the question is the title, the answer is the content; "Show on" chooses the pages (FAQ page, Trust
 *           Center, About page); "Link anchor" keeps links like /faq/#q-is-labora-hipaa-compliant working;
 *           order: Order box
 *   Topic   name, icon, intro line, and order (the FAQ page's groups and the "Categories" list)
 *
 * Answers may use [labora_email key="security"] (or contact, sales) to show an email address from Labora Settings.
 * Each page sends search engines FAQPage data built from exactly the questions it shows (labora_faq_schema()).
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

const LABORA_FAQ_TYPE  = 'labora_faq';
const LABORA_FAQ_TOPIC = 'labora_faq_topic';

/** Pages a FAQ entry can appear on: value => label. */
function labora_faq_places(): array {
	return array(
		'faq'      => __( 'FAQ page', 'labora' ),
		'security' => __( 'Trust Center', 'labora' ),
		'about'    => __( 'About page', 'labora' ),
	);
}

add_action( 'init', function () {
	register_post_type( LABORA_FAQ_TYPE, array(
		'labels'          => array(
			'name'          => __( 'FAQ', 'labora' ),
			'singular_name' => __( 'Question', 'labora' ),
			'add_new_item'  => __( 'Add a question', 'labora' ),
			'edit_item'     => __( 'Edit question', 'labora' ),
			'all_items'     => __( 'All questions', 'labora' ),
			'search_items'  => __( 'Search questions', 'labora' ),
			'not_found'     => __( 'No questions found', 'labora' ),
		),
		'public'          => false,
		'show_ui'         => true,
		'show_in_rest'    => true, // the block editor for answers
		'menu_icon'       => 'dashicons-editor-help',
		'menu_position'   => 21,
		'supports'        => array( 'title', 'editor', 'page-attributes', 'revisions' ),
		'capability_type' => 'page',
	) );
	register_taxonomy( LABORA_FAQ_TOPIC, LABORA_FAQ_TYPE, array(
		'labels'            => array(
			'name'          => __( 'Topics', 'labora' ),
			'singular_name' => __( 'Topic', 'labora' ),
			'add_new_item'  => __( 'Add a topic', 'labora' ),
		),
		'public'            => false,
		'show_ui'           => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'hierarchical'      => true, // checkboxes in the editor
	) );
} );

// The editor: answers are short, plain blocks; the title placeholder asks for the question
add_filter( 'enter_title_here', fn( $text, $post ) => LABORA_FAQ_TYPE === $post->post_type ? __( 'The question', 'labora' ) : $text, 10, 2 );

// Admin list: order by the Order number, show where each question appears
add_action( 'pre_get_posts', function ( WP_Query $q ) {
	if ( is_admin() && $q->is_main_query() && LABORA_FAQ_TYPE === $q->get( 'post_type' ) && ! $q->get( 'orderby' ) ) {
		$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	}
} );
add_filter( 'manage_' . LABORA_FAQ_TYPE . '_posts_columns', function ( $cols ) {
	return array_slice( $cols, 0, 2, true ) + array( 'labora_show_on' => __( 'Shown on', 'labora' ), 'labora_order' => __( 'Order', 'labora' ) ) + array_slice( $cols, 2, null, true );
} );
add_action( 'manage_' . LABORA_FAQ_TYPE . '_posts_custom_column', function ( $col, $id ) {
	if ( 'labora_show_on' === $col ) {
		$places = labora_faq_places();
		echo esc_html( implode( ', ', array_map( fn( $p ) => $places[ $p ] ?? $p, (array) get_post_meta( $id, 'faq_show_on', true ) ) ) ?: '—' );
	} elseif ( 'labora_order' === $col ) {
		echo (int) get_post_field( 'menu_order', $id );
	}
}, 10, 2 );

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	acf_add_local_field_group( array(
		'key'      => 'group_labora_faq',
		'title'    => __( 'Question', 'labora' ),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => LABORA_FAQ_TYPE ) ) ),
		'position' => 'side',
		'fields'   => array(
			array( 'key' => 'field_lf_show_on', 'name' => 'faq_show_on', 'label' => __( 'Show on', 'labora' ), 'type' => 'checkbox', 'choices' => labora_faq_places(), 'default_value' => array( 'faq' ), 'return_format' => 'value' ),
			array( 'key' => 'field_lf_anchor', 'name' => 'faq_anchor', 'label' => __( 'Link anchor', 'labora' ), 'type' => 'text', 'prepend' => '#',
				'instructions' => __( 'The part after # in a link to this question. Empty: made from the question. Change it only if no links use it.', 'labora' ) ),
		),
	) );
	acf_add_local_field_group( array(
		'key'      => 'group_labora_faq_topic',
		'title'    => __( 'Topic', 'labora' ),
		'location' => array( array( array( 'param' => 'taxonomy', 'operator' => '==', 'value' => LABORA_FAQ_TOPIC ) ) ),
		'fields'   => array(
			array( 'key' => 'field_lft_icon', 'name' => 'topic_icon', 'label' => __( 'Icon', 'labora' ), 'type' => 'select', 'choices' => labora_icon_choices(), 'default_value' => 'message' ),
			array( 'key' => 'field_lft_intro', 'name' => 'topic_intro', 'label' => __( 'Intro line', 'labora' ), 'type' => 'text', 'instructions' => __( 'Shown under the topic heading on the FAQ page.', 'labora' ) ),
			array( 'key' => 'field_lft_order', 'name' => 'topic_order', 'label' => __( 'Order', 'labora' ), 'type' => 'number', 'min' => 1, 'instructions' => __( 'Lower numbers come first. The slug is the topic\'s link anchor (/faq/#general).', 'labora' ) ),
		),
	) );
} );

/** Icons in the theme's icon set, for selects: name => name. */
function labora_icon_choices(): array {
	static $choices = null;
	if ( null === $choices ) {
		preg_match_all( '/<symbol id="i-([a-z0-9-]+)"/', (string) file_get_contents( get_theme_file_path( 'template-parts/sprite.php' ) ), $m );
		$choices = array_combine( $m[1], $m[1] );
		ksort( $choices );
	}
	return $choices;
}

/** The anchor of an entry: its "Link anchor", or q- + the question (as on the HTML site). */
function labora_faq_anchor( WP_Post $post ): string {
	$anchor = trim( (string) get_post_meta( $post->ID, 'faq_anchor', true ), "# \t" );
	if ( '' !== $anchor ) {
		return sanitize_title( $anchor );
	}
	return substr( 'q-' . trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( html_entity_decode( get_the_title( $post ) ) ) ), '-' ), 0, 62 );
}

/** Published entries for a page ('faq' or 'security'), in order. */
function labora_faq_entries( string $place ): array {
	$posts = get_posts( array(
		'post_type'      => LABORA_FAQ_TYPE,
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'meta_query'     => array( array( 'key' => 'faq_show_on', 'value' => '"' . $place . '"', 'compare' => 'LIKE' ) ),
	) );
	return $posts;
}

/**
 * Entries grouped by topic, topics in order: [ [ 'term' => WP_Term, 'items' => [ WP_Post ... ] ], ... ].
 * Entries without a topic are listed last, under "More questions".
 */
function labora_faq_groups( string $place ): array {
	$entries = labora_faq_entries( $place );
	$terms   = get_terms( array( 'taxonomy' => LABORA_FAQ_TOPIC, 'hide_empty' => false ) );
	$terms   = is_wp_error( $terms ) ? array() : $terms;
	usort( $terms, fn( $a, $b ) => ( (int) get_term_meta( $a->term_id, 'topic_order', true ) ?: 999 ) <=> ( (int) get_term_meta( $b->term_id, 'topic_order', true ) ?: 999 ) ?: strcasecmp( $a->name, $b->name ) );
	$groups = array();
	$placed = array();
	foreach ( $terms as $term ) {
		$items = array_values( array_filter( $entries, fn( $p ) => has_term( $term->term_id, LABORA_FAQ_TOPIC, $p ) ) );
		if ( $items ) {
			$groups[] = array( 'term' => $term, 'items' => $items );
			$placed   = array_merge( $placed, wp_list_pluck( $items, 'ID' ) );
		}
	}
	$rest = array_values( array_filter( $entries, fn( $p ) => ! in_array( $p->ID, $placed, true ) ) );
	if ( $rest ) {
		$groups[] = array( 'term' => (object) array( 'slug' => 'more', 'name' => __( 'More questions', 'labora' ), 'term_id' => 0 ), 'items' => $rest );
	}
	return $groups;
}

/** The answer's HTML (blocks, shortcodes, the email placeholders). */
function labora_faq_answer( WP_Post $post ): string {
	$html = apply_filters( 'the_content', $post->post_content );
	return trim( str_replace( ']]>', ']]&gt;', $html ) );
}

/**
 * One question: the HTML site's <details class="faq-item">, with "Copy link" and "Was this helpful?" when $tools,
 * and its link anchor as id when $anchor (the About page shows plain questions, as on the HTML site).
 */
function labora_faq_item( WP_Post $post, bool $tools = true, bool $anchor = true ): string {
	$tools_html = $tools ? '<div class="faq-tools"><button type="button" data-copy-q>' . labora_icon( 'link' ) . esc_html__( 'Copy link', 'labora' ) . '</button>'
		. '<span class="sep" aria-hidden="true"></span><span>' . esc_html__( 'Was this helpful?', 'labora' ) . '</span>'
		. '<button type="button" data-helpful="yes" aria-pressed="false">' . esc_html__( 'Yes', 'labora' ) . '</button>'
		. '<button type="button" data-helpful="no" aria-pressed="false">' . esc_html__( 'No', 'labora' ) . '</button>'
		. '<span class="faq-thanks" aria-live="polite"></span></div>' : '';
	return sprintf(
		'<details class="faq-item"%1$s><summary><h3>%2$s</h3><span class="plus" aria-hidden="true"><svg class="icon"><use href="#i-plus"/></svg></span></summary><div class="faq-answer">%3$s</div>%4$s</details>',
		$anchor ? ' id="' . esc_attr( labora_faq_anchor( $post ) ) . '"' : '',
		esc_html( get_the_title( $post ) ),
		labora_faq_answer( $post ),
		$tools_html
	);
}

/** FAQPage structured data for the questions a page shows. */
function labora_faq_schema( array $posts, string $page_url ): array {
	return array(
		'@type'      => 'FAQPage',
		'@id'        => $page_url . '#faq',
		'isPartOf'   => array( '@id' => $page_url ),
		'mainEntity' => array_map( fn( $p ) => array(
			'@type'          => 'Question',
			'name'           => html_entity_decode( get_the_title( $p ), ENT_QUOTES ),
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => trim( preg_replace( '/\s+/', ' ', html_entity_decode( wp_strip_all_tags( labora_faq_answer( $p ) ), ENT_QUOTES ) ) ) ),
		), $posts ),
	);
}

/** [labora_email key="security"]: an email address from Labora Settings as a link (contact, sales, security). */
add_shortcode( 'labora_email', function ( $atts ) {
	$a     = shortcode_atts( array( 'key' => 'contact', 'link' => 'yes' ), $atts, 'labora_email' );
	$key   = in_array( $a['key'], array( 'contact', 'sales', 'security' ), true ) ? $a['key'] : 'contact';
	$email = sanitize_email( (string) labora_setting( 'email_' . $key ) ) ?: sanitize_email( (string) labora_setting( 'email_contact' ) );
	return 'no' === $a['link'] ? esc_html( $email ) : sprintf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $email ) );
} );

// FAQ entries have no pages of their own
add_action( 'template_redirect', function () {
	if ( is_singular( LABORA_FAQ_TYPE ) ) {
		wp_safe_redirect( home_url( '/faq/' ), 301 );
		exit;
	}
} );

// Search engines: FAQPage data from exactly the questions each page shows, in the same order
add_filter( 'wpseo_schema_graph', function ( $graph ) {
	if ( ! is_page() ) {
		return $graph;
	}
	$template = get_page_template_slug();
	if ( 'page-templates/faq.php' === $template ) {
		$posts = array_merge( ...array_map( fn( $g ) => $g['items'], labora_faq_groups( 'faq' ) ) ?: array( array() ) );
	} elseif ( 'page-templates/trust-center.php' === $template ) {
		$posts = labora_faq_entries( 'security' );
	} elseif ( 'page-templates/about.php' === $template ) {
		$posts = labora_faq_entries( 'about' );
	} else {
		return $graph;
	}
	if ( $posts ) {
		$graph[] = labora_faq_schema( $posts, (string) get_permalink() );
	}
	return $graph;
} );
