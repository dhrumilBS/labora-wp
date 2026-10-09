<?php
/**
 * Article components as blocks ("Labora article" in the block inserter). Each block has a few fields and prints
 * the HTML site's markup (template-parts/blocks/<name>.php), so it looks the same in the editor and on the site.
 *
 *   Key takeaways          a short list of the main points, at the top of an article
 *   Note / Tip / Warning   a highlighted remark (type chosen per block)
 *   Book a demo            the inline demo box
 *   Key numbers            two to four figures with a label each
 *   Before and after       two lists side by side: how it is today, and with Labora
 *   Questions and answers  an accordion; also sent to search engines as FAQ structured data (inc/blog.php)
 *
 * Plain text, headings, lists ("Checklist" style), tables ("Data table" style), images, and quotes are the
 * standard WordPress blocks.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'block_categories_all', function ( array $categories ) {
	array_unshift( $categories, array( 'slug' => 'labora-article', 'title' => __( 'Labora article', 'labora' ), 'icon' => null ) );
	return $categories;
} );

/** Blocks: name => [ title, description, icon, keywords ]. */
function labora_article_blocks(): array {
	return array(
		'labora-takeaways' => array( __( 'Key takeaways', 'labora' ), __( 'The main points of the article, as a short list.', 'labora' ), 'yes-alt', array( 'summary', 'tldr', 'points' ) ),
		'labora-callout'   => array( __( 'Note, tip, or warning', 'labora' ), __( 'A highlighted remark next to the text.', 'labora' ), 'lightbulb', array( 'tip', 'note', 'warning', 'callout' ) ),
		'labora-demo'      => array( __( 'Book a demo box', 'labora' ), __( 'A short call to action with a button.', 'labora' ), 'megaphone', array( 'cta', 'demo', 'button' ) ),
		'labora-stats'     => array( __( 'Key numbers', 'labora' ), __( 'Two to four figures, each with a label.', 'labora' ), 'chart-bar', array( 'stats', 'numbers', 'figures', 'benchmark' ) ),
		'labora-compare'   => array( __( 'Before and after', 'labora' ), __( 'Two lists side by side: today, and with Labora.', 'labora' ), 'columns', array( 'compare', 'before', 'after', 'versus' ) ),
		'labora-faq'       => array( __( 'Questions and answers', 'labora' ), __( 'An accordion of questions. Search engines also receive them as FAQ data.', 'labora' ), 'editor-help', array( 'faq', 'questions', 'accordion' ) ),
	);
}

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_register_block_type' ) ) {
		return;
	}
	foreach ( labora_article_blocks() as $name => list( $title, $desc, $icon, $keywords ) ) {
		acf_register_block_type( array(
			'name'            => $name,
			'title'           => $title,
			'description'     => $desc,
			'category'        => 'labora-article',
			'icon'            => $icon,
			'keywords'        => $keywords,
			'post_types'      => array( 'post', 'page' ),
			'mode'            => 'preview',
			'supports'        => array( 'align' => false, 'anchor' => false, 'customClassName' => false, 'html' => false ),
			'render_template' => get_theme_file_path( "template-parts/blocks/{$name}.php" ),
		) );
	}

	$f   = fn( $key, $name, $label, $type, $extra = array() ) => array_merge( array( 'key' => "field_lb_{$key}", 'name' => $name, 'label' => $label, 'type' => $type ), $extra );
	$loc = fn( $name ) => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => "acf/{$name}" ) ) );
	$rich = array( 'tabs' => 'visual', 'toolbar' => 'basic', 'media_upload' => 0, 'delay' => 0 );

	acf_add_local_field_group( array(
		'key'      => 'group_lb_takeaways',
		'title'    => __( 'Key takeaways', 'labora' ),
		'location' => $loc( 'labora-takeaways' ),
		'fields'   => array(
			$f( 'takeaways_title', 'title', __( 'Heading', 'labora' ), 'text', array( 'placeholder' => __( 'Key takeaways', 'labora' ) ) ),
			$f( 'takeaways_points', 'points', __( 'Points', 'labora' ), 'repeater', array(
				'layout' => 'table', 'min' => 1, 'button_label' => __( 'Add a point', 'labora' ),
				'sub_fields' => array( $f( 'takeaways_point', 'text', __( 'Point', 'labora' ), 'textarea', array( 'rows' => 2, 'new_lines' => '' ) ) ),
			) ),
		),
	) );

	acf_add_local_field_group( array(
		'key'      => 'group_lb_callout',
		'title'    => __( 'Note, tip, or warning', 'labora' ),
		'location' => $loc( 'labora-callout' ),
		'fields'   => array(
			$f( 'callout_type', 'type', __( 'Type', 'labora' ), 'button_group', array( 'choices' => array( 'tip' => __( 'Tip', 'labora' ), 'note' => __( 'Note', 'labora' ), 'warning' => __( 'Warning', 'labora' ) ), 'default_value' => 'tip' ) ),
			$f( 'callout_title', 'title', __( 'Title', 'labora' ), 'text', array( 'instructions' => __( 'One short line, shown in bold, e.g. "Tip: count calendar time, not working hours".', 'labora' ) ) ),
			$f( 'callout_text', 'text', __( 'Text', 'labora' ), 'wysiwyg', $rich ),
		),
	) );

	acf_add_local_field_group( array(
		'key'      => 'group_lb_demo',
		'title'    => __( 'Book a demo box', 'labora' ),
		'location' => $loc( 'labora-demo' ),
		'fields'   => array(
			$f( 'demo_title', 'title', __( 'Heading', 'labora' ), 'text', array( 'required' => 1 ) ),
			$f( 'demo_text', 'text', __( 'Text', 'labora' ), 'textarea', array( 'rows' => 2, 'new_lines' => '' ) ),
			$f( 'demo_label', 'label', __( 'Button text', 'labora' ), 'text', array( 'instructions' => __( 'Empty: the "Book a demo" text from Labora Settings.', 'labora' ) ) ),
			$f( 'demo_url', 'url', __( 'Button link', 'labora' ), 'text', array( 'instructions' => __( 'Empty: the "Book a demo" link from Labora Settings. A path (/pricing/) or a full URL.', 'labora' ) ) ),
		),
	) );

	acf_add_local_field_group( array(
		'key'      => 'group_lb_stats',
		'title'    => __( 'Key numbers', 'labora' ),
		'location' => $loc( 'labora-stats' ),
		'fields'   => array(
			$f( 'stats_items', 'items', __( 'Numbers', 'labora' ), 'repeater', array(
				'layout' => 'table', 'min' => 2, 'max' => 4, 'button_label' => __( 'Add a number', 'labora' ),
				'sub_fields' => array(
					$f( 'stats_value', 'value', __( 'Number', 'labora' ), 'text', array( 'placeholder' => '4 h', 'wrapper' => array( 'width' => 30 ) ) ),
					$f( 'stats_label', 'label', __( 'What it means', 'labora' ), 'text', array( 'placeholder' => __( 'from registration to signed report', 'labora' ) ) ),
				),
			) ),
			$f( 'stats_note', 'note', __( 'Source or note', 'labora' ), 'text', array( 'instructions' => __( 'Say where the numbers come from, or that they are an example (e.g. "Example targets; set your own per test").', 'labora' ) ) ),
		),
	) );

	$items = fn( $key, $label ) => $f( $key, $key, $label, 'repeater', array(
		'layout' => 'table', 'min' => 1, 'button_label' => __( 'Add a line', 'labora' ),
		'sub_fields' => array( $f( "{$key}_text", 'text', __( 'Line', 'labora' ), 'text' ) ),
	) );
	acf_add_local_field_group( array(
		'key'      => 'group_lb_compare',
		'title'    => __( 'Before and after', 'labora' ),
		'location' => $loc( 'labora-compare' ),
		'fields'   => array(
			$f( 'compare_before_label', 'before_label', __( 'Left heading', 'labora' ), 'text', array( 'placeholder' => __( 'Today', 'labora' ), 'wrapper' => array( 'width' => 50 ) ) ),
			$f( 'compare_after_label', 'after_label', __( 'Right heading', 'labora' ), 'text', array( 'placeholder' => __( 'With Labora', 'labora' ), 'wrapper' => array( 'width' => 50 ) ) ),
			$items( 'before', __( 'Left lines', 'labora' ) ) + array( 'wrapper' => array( 'width' => 50 ) ),
			$items( 'after', __( 'Right lines', 'labora' ) ) + array( 'wrapper' => array( 'width' => 50 ) ),
		),
	) );

	acf_add_local_field_group( array(
		'key'      => 'group_lb_faq',
		'title'    => __( 'Questions and answers', 'labora' ),
		'location' => $loc( 'labora-faq' ),
		'fields'   => array(
			$f( 'faq_title', 'title', __( 'Heading', 'labora' ), 'text', array( 'placeholder' => __( 'Frequently asked questions', 'labora' ) ) ),
			$f( 'faq_items', 'items', __( 'Questions', 'labora' ), 'repeater', array(
				'layout' => 'block', 'min' => 1, 'button_label' => __( 'Add a question', 'labora' ), 'collapsed' => 'field_lb_faq_question',
				'sub_fields' => array(
					$f( 'faq_question', 'question', __( 'Question', 'labora' ), 'text', array( 'required' => 1 ) ),
					$f( 'faq_answer', 'answer', __( 'Answer', 'labora' ), 'wysiwyg', $rich + array( 'required' => 1 ) ),
				),
			) ),
		),
	) );
} );

/** A unique, stable id for a heading inside a block (several blocks of one kind can be on a page). */
function labora_block_dom_id( string $prefix, array $block ): string {
	static $n = array();
	$n[ $prefix ] = ( $n[ $prefix ] ?? 0 ) + 1;
	return $prefix . ( $n[ $prefix ] > 1 ? '-' . $n[ $prefix ] : '' );
}

/** Text from a block field for the page: limited HTML (links, emphasis, lists). */
function labora_block_text( $html ): string {
	return wp_kses( (string) $html, array(
		'a'      => array( 'href' => true, 'target' => true, 'rel' => true ),
		'strong' => array(), 'b' => array(), 'em' => array(), 'i' => array(), 'br' => array(),
		'p'      => array(), 'ul' => array(), 'ol' => array(), 'li' => array(), 'code' => array(),
	) );
}
