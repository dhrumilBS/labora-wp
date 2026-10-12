<?php
/**
 * Article components as blocks ("Labora article" in the block inserter). Each block has a few fields and prints
 * the HTML site's markup (template-parts/blocks/<name>.php), so it looks the same in the editor and on the site.
 *
 *   Key takeaways          a short list of the main points, at the top of an article
 *   Note box               a highlighted remark: tip, note, warning, important, example, or "learn more" link
 *   Book a demo            the inline demo box
 *   Call-to-action banner  the homepage's dark closing box, for the end of an article
 *   Key numbers            two to four figures with a label each
 *   Before and after       two lists side by side: how it is today, and with Labora
 *   Comparison table       options side by side, with yes / no / partial marks and one column highlighted
 *   Interactive checklist  items readers tick off (kept in their browser), with a progress count and "Copy"
 *   Verdict box            a score, pros and cons, and who it suits
 *   Questions and answers  an accordion; also sent to search engines as FAQ structured data (inc/blog.php)
 *
 * Plain text, headings, lists ("Checklist", "Ticks", "Crosses" styles), tables ("Data table" style), images, and
 * quotes are the standard WordPress blocks. "Was this article helpful?" is part of every post (inc/feedback.php).
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
		'labora-callout'   => array( __( 'Note box', 'labora' ), __( 'A highlighted remark: tip, note, warning, important, example, or a "learn more" link.', 'labora' ), 'lightbulb', array( 'tip', 'note', 'warning', 'important', 'example', 'learn more', 'callout' ) ),
		'labora-demo'      => array( __( 'Book a demo box', 'labora' ), __( 'A short call to action with a button.', 'labora' ), 'megaphone', array( 'cta', 'demo', 'button' ) ),
		'labora-cta'       => array( __( 'Call-to-action banner', 'labora' ), __( 'A dark closing box with a heading, up to three points, and two buttons.', 'labora' ), 'flag', array( 'cta', 'banner', 'demo', 'button' ) ),
		'labora-stats'     => array( __( 'Key numbers', 'labora' ), __( 'Two to four figures, each with a label.', 'labora' ), 'chart-bar', array( 'stats', 'numbers', 'figures', 'benchmark' ) ),
		'labora-compare'   => array( __( 'Before and after', 'labora' ), __( 'Two lists side by side: today, and with Labora.', 'labora' ), 'columns', array( 'compare', 'before', 'after', 'versus' ) ),
		'labora-table'     => array( __( 'Comparison table', 'labora' ), __( 'Up to four options side by side, with yes / no / partial marks.', 'labora' ), 'editor-table', array( 'table', 'compare', 'versus', 'features' ) ),
		'labora-checklist' => array( __( 'Interactive checklist', 'labora' ), __( 'Items readers tick off, with a progress count and a "Copy checklist" button.', 'labora' ), 'yes', array( 'checklist', 'todo', 'tasks', 'copy' ) ),
		'labora-verdict'   => array( __( 'Verdict box', 'labora' ), __( 'A score out of 5, pros and cons, and who it suits.', 'labora' ), 'star-filled', array( 'review', 'verdict', 'rating', 'pros', 'cons' ) ),
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
		'title'    => __( 'Note box', 'labora' ),
		'location' => $loc( 'labora-callout' ),
		'fields'   => array(
			$f( 'callout_type', 'type', __( 'Type', 'labora' ), 'button_group', array( 'choices' => labora_callout_types(), 'default_value' => 'tip' ) ),
			$f( 'callout_label', 'label', __( 'Label', 'labora' ), 'text', array(
				'instructions' => __( 'A small heading above the box, e.g. "Pro tip". With a label the box gets a tinted background. Important, Example and Learn more always show one (their type name when this is empty).', 'labora' ),
			) ),
			$f( 'callout_title', 'title', __( 'Title', 'labora' ), 'text', array( 'instructions' => __( 'One short line, shown in bold, e.g. "Tip: count calendar time, not working hours".', 'labora' ) ) ),
			$f( 'callout_text', 'text', __( 'Text', 'labora' ), 'wysiwyg', $rich ),
			$f( 'callout_link_url', 'link_url', __( 'Link', 'labora' ), 'text', array(
				'instructions'      => __( 'A path (/blog/barcode-sample-tracking/) or a full URL.', 'labora' ), 'wrapper' => array( 'width' => 60 ),
				'conditional_logic' => array( array( array( 'field' => 'field_lb_callout_type', 'operator' => '==', 'value' => 'learn' ) ) ),
			) ),
			$f( 'callout_link_label', 'link_label', __( 'Link text', 'labora' ), 'text', array(
				'placeholder' => __( 'Read the guide', 'labora' ), 'wrapper' => array( 'width' => 40 ),
				'conditional_logic' => array( array( array( 'field' => 'field_lb_callout_type', 'operator' => '==', 'value' => 'learn' ) ) ),
			) ),
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

	$link = fn( $key, $label, $instructions ) => array(
		$f( "{$key}_label", "{$key}_label", sprintf( /* translators: %s: button */ __( '%s: text', 'labora' ), $label ), 'text', array( 'wrapper' => array( 'width' => 40 ), 'instructions' => $instructions ) ),
		$f( "{$key}_url", "{$key}_url", sprintf( /* translators: %s: button */ __( '%s: link', 'labora' ), $label ), 'text', array( 'wrapper' => array( 'width' => 60 ), 'instructions' => __( 'A path (/pricing/), #demo, or a full URL.', 'labora' ) ) ),
	);

	acf_add_local_field_group( array(
		'key'      => 'group_lb_cta',
		'title'    => __( 'Call-to-action banner', 'labora' ),
		'location' => $loc( 'labora-cta' ),
		'fields'   => array_merge(
			array(
				$f( 'cta_title', 'title', __( 'Heading', 'labora' ), 'text', array( 'required' => 1 ) ),
				$f( 'cta_text', 'text', __( 'Text', 'labora' ), 'textarea', array( 'rows' => 2, 'new_lines' => '' ) ),
				$f( 'cta_points_label', 'points_label', __( 'Points: small heading', 'labora' ), 'text', array( 'placeholder' => __( 'In the demo', 'labora' ) ) ),
				$f( 'cta_points', 'points', __( 'Points', 'labora' ), 'repeater', array(
					'layout' => 'table', 'max' => 4, 'button_label' => __( 'Add a point', 'labora' ),
					'instructions' => __( 'Optional: shown as a numbered list beside the heading.', 'labora' ),
					'sub_fields' => array( $f( 'cta_point', 'text', __( 'Point', 'labora' ), 'text' ) ),
				) ),
			),
			$link( 'cta_primary', __( 'Main button', 'labora' ), __( 'Empty: "Book a demo" from Labora Settings.', 'labora' ) ),
			$link( 'cta_secondary', __( 'Second button', 'labora' ), __( 'Optional.', 'labora' ) )
		),
	) );

	$cols = array();
	for ( $c = 1; $c <= 4; $c++ ) {
		/* translators: %d: column number */
		$cols[] = $f( "table_col{$c}", "col{$c}", sprintf( __( 'Column %d heading', 'labora' ), $c ), 'text', array( 'wrapper' => array( 'width' => 25 ), 'required' => $c <= 2 ? 1 : 0 ) );
	}
	$cells = array( $f( 'table_row_feature', 'feature', __( 'Row', 'labora' ), 'text', array( 'required' => 1 ) ) );
	for ( $c = 1; $c <= 4; $c++ ) {
		/* translators: %d: column number */
		$cells[] = $f( "table_row_c{$c}", "c{$c}", sprintf( __( 'Column %d', 'labora' ), $c ), 'text' );
	}
	acf_add_local_field_group( array(
		'key'      => 'group_lb_table',
		'title'    => __( 'Comparison table', 'labora' ),
		'location' => $loc( 'labora-table' ),
		'fields'   => array_merge(
			array( $f( 'table_feature', 'feature_label', __( 'First column heading', 'labora' ), 'text', array( 'placeholder' => __( 'What matters', 'labora' ) ) ) ),
			$cols,
			array(
				$f( 'table_highlight', 'highlight', __( 'Highlight a column', 'labora' ), 'button_group', array(
					'choices' => array( '0' => __( 'None', 'labora' ), '1' => '1', '2' => '2', '3' => '3', '4' => '4' ), 'default_value' => '0',
				) ),
				$f( 'table_rows', 'rows', __( 'Rows', 'labora' ), 'repeater', array(
					'layout' => 'table', 'min' => 1, 'button_label' => __( 'Add a row', 'labora' ),
					'instructions' => __( 'In a cell, "yes", "no" or "partial" become a mark (with that word for screen readers); anything else is shown as text. Leave the cells of an unused column empty.', 'labora' ),
					'sub_fields' => $cells,
				) ),
				$f( 'table_note', 'note', __( 'Note under the table', 'labora' ), 'text' ),
			)
		),
	) );

	acf_add_local_field_group( array(
		'key'      => 'group_lb_checklist',
		'title'    => __( 'Interactive checklist', 'labora' ),
		'location' => $loc( 'labora-checklist' ),
		'fields'   => array(
			$f( 'checklist_title', 'title', __( 'Heading', 'labora' ), 'text', array( 'required' => 1 ) ),
			$f( 'checklist_intro', 'intro', __( 'Text above the items', 'labora' ), 'textarea', array( 'rows' => 2, 'new_lines' => '' ) ),
			$f( 'checklist_items', 'items', __( 'Items', 'labora' ), 'repeater', array(
				'layout' => 'table', 'min' => 1, 'button_label' => __( 'Add an item', 'labora' ),
				'instructions' => __( 'Readers tick items off; their ticks stay in their own browser. "Copy checklist" copies the heading and items as text (with ticks).', 'labora' ),
				'sub_fields' => array(
					$f( 'checklist_item', 'text', __( 'Item', 'labora' ), 'text', array( 'required' => 1 ) ),
					$f( 'checklist_detail', 'detail', __( 'Detail (optional)', 'labora' ), 'text' ),
				),
			) ),
		),
	) );

	acf_add_local_field_group( array(
		'key'      => 'group_lb_verdict',
		'title'    => __( 'Verdict box', 'labora' ),
		'location' => $loc( 'labora-verdict' ),
		'fields'   => array_merge(
			array(
				$f( 'verdict_eyebrow', 'eyebrow', __( 'Small heading', 'labora' ), 'text', array( 'placeholder' => __( 'Our verdict', 'labora' ), 'wrapper' => array( 'width' => 40 ) ) ),
				$f( 'verdict_title', 'title', __( 'Heading', 'labora' ), 'text', array( 'required' => 1, 'wrapper' => array( 'width' => 60 ) ) ),
				$f( 'verdict_score', 'score', __( 'Score out of 5', 'labora' ), 'number', array( 'min' => 0, 'max' => 5, 'step' => 0.5, 'wrapper' => array( 'width' => 25 ), 'instructions' => __( 'Empty: no score.', 'labora' ) ) ),
				$f( 'verdict_score_label', 'score_label', __( 'Score in words', 'labora' ), 'text', array( 'placeholder' => __( 'Worth it for most labs', 'labora' ), 'wrapper' => array( 'width' => 75 ) ) ),
				$f( 'verdict_summary', 'summary', __( 'Summary', 'labora' ), 'textarea', array( 'rows' => 3, 'new_lines' => '' ) ),
				$items( 'pros', __( 'Pros', 'labora' ) ) + array( 'wrapper' => array( 'width' => 50 ) ),
				$items( 'cons', __( 'Cons', 'labora' ) ) + array( 'wrapper' => array( 'width' => 50 ) ),
				$f( 'verdict_best_for', 'best_for', __( 'Best for', 'labora' ), 'text' ),
			),
			$link( 'verdict_cta', __( 'Button', 'labora' ), __( 'Optional; empty text: no button.', 'labora' ) )
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

/** Note box types: value => name (also the label shown on the boxed types). */
function labora_callout_types(): array {
	return array(
		'tip'       => __( 'Pro tip', 'labora' ),
		'note'      => __( 'Note', 'labora' ),
		'warning'   => __( 'Warning', 'labora' ),
		'important' => __( 'Important', 'labora' ),
		'example'   => __( 'Example', 'labora' ),
		'learn'     => __( 'Learn more', 'labora' ),
	);
}

/** A link field for the page: a path becomes a site URL; full URLs, #anchors, mailto: and tel: are kept. */
function labora_block_url( string $value ): string {
	$value = trim( $value );
	if ( '' === $value || preg_match( '#^(https?:|mailto:|tel:|\#)#i', $value ) ) {
		return $value;
	}
	return home_url( '/' . ltrim( $value, '/' ) );
}
