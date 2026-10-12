<?php
/**
 * Fields for the page templates whose layout is fixed but whose text is edited in WordPress:
 *   FAQ page       page-templates/faq.php           intro, help box, empty search message, help cards
 *   Trust Center   page-templates/trust-center.php  every heading, paragraph, and list of the Trust Center
 * Lists of short lines are one text box with one line per item. The questions themselves are FAQ entries
 * (inc/faq.php). Product mockups on the Trust Center are part of the design (template-parts/trust/).
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

/** A field value of the current page ('' when empty). */
function labora_field( string $name, $post = null ) {
	return function_exists( 'get_field' ) ? get_field( $name, $post ?: get_the_ID() ) : get_post_meta( get_the_ID(), $name, true );
}

/** Lines of a "one per line" field. */
function labora_lines( string $name ): array {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) labora_field( $name ) ) ) ) );
}

/** A link field: paths on this site become full URLs; #anchors, mailto:, and full URLs stay. */
function labora_link( string $value ): string {
	$value = trim( $value );
	if ( '' === $value || preg_match( '#^(https?:|mailto:|tel:|\#)#i', $value ) ) {
		return $value;
	}
	return home_url( '/' . ltrim( $value, '/' ) );
}

/** {security_email} and {security_email_link} in a text field: the security email from Labora Settings. */
function labora_fill_email( string $text ): string {
	$email = esc_html( sanitize_email( (string) labora_setting( 'email_security' ) ) );
	return str_replace( array( '{security_email_link}', '{security_email}' ), array( '<a href="mailto:' . $email . '">' . $email . '</a>', $email ), $text );
}

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	$k     = 0;
	$tab   = function ( $label ) use ( &$k ) { return array( 'key' => 'field_lpg_tab_' . ( ++$k ), 'label' => $label, 'name' => '', 'type' => 'tab', 'placement' => 'top' ); };
	$f     = fn( $prefix, $name, $label, $type = 'text', $extra = array() ) => array_merge( array( 'key' => "field_{$prefix}_{$name}", 'name' => $name, 'label' => $label, 'type' => $type ), $extra );
	$lines = array( 'rows' => 5, 'new_lines' => '', 'instructions' => __( 'One item per line.', 'labora' ) );
	$area  = array( 'rows' => 2, 'new_lines' => '' );
	$icons = array( 'choices' => labora_icon_choices(), 'ui' => 1 );
	$tpl   = fn( $file ) => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => $file ) ) );

	// ---------- FAQ page ----------
	$p = 'lfp';
	acf_add_local_field_group( array(
		'key'            => 'group_labora_faq_page',
		'position'       => 'acf_after_title', // the page's text first, above Yoast
		'title'          => __( 'FAQ page', 'labora' ),
		'location'       => $tpl( 'page-templates/faq.php' ),
		'hide_on_screen' => array( 'the_content' ),
		'fields'         => array(
			array( 'key' => "field_{$p}_msg", 'name' => '', 'label' => '', 'type' => 'message', 'message' => __( 'The questions are edited under FAQ in the admin menu (tick "FAQ page" on each). The search box counts them.', 'labora' ) ),
			$tab( __( 'Intro', 'labora' ) ),
			$f( $p, 'hero_eyebrow', __( 'Label above the heading', 'labora' ) ),
			$f( $p, 'hero_title', __( 'Heading', 'labora' ) ),
			$f( $p, 'hero_lead', __( 'Text under the heading', 'labora' ), 'textarea', $area ),
			$tab( __( 'Help box', 'labora' ) ),
			$f( $p, 'help_title', __( 'Help box: title', 'labora' ), 'text', array( 'instructions' => __( 'Under the list of categories.', 'labora' ) ) ),
			$f( $p, 'help_text', __( 'Help box: text', 'labora' ) ),
			$f( $p, 'help_label', __( 'Help box: link text', 'labora' ) ),
			$f( $p, 'help_url', __( 'Help box: link', 'labora' ) ),
			$f( $p, 'empty_title', __( 'No search results: title', 'labora' ) ),
			$f( $p, 'empty_text', __( 'No search results: text', 'labora' ) ),
			$tab( __( 'Help cards', 'labora' ) ),
			$f( $p, 'cards', __( 'Cards under the questions', 'labora' ), 'repeater', array(
				'layout' => 'block', 'button_label' => __( 'Add a card', 'labora' ), 'max' => 3,
				'sub_fields' => array(
					$f( $p, 'card_title', __( 'Title', 'labora' ), 'text', array( 'name' => 'title', 'wrapper' => array( 'width' => 50 ) ) ),
					$f( $p, 'card_icon', __( 'Icon', 'labora' ), 'select', $icons + array( 'name' => 'icon', 'wrapper' => array( 'width' => 25 ) ) ),
					$f( $p, 'card_dark', __( 'Dark card', 'labora' ), 'true_false', array( 'name' => 'dark', 'ui' => 1, 'wrapper' => array( 'width' => 25 ) ) ),
					$f( $p, 'card_text', __( 'Text', 'labora' ), 'text', array( 'name' => 'text' ) ),
					$f( $p, 'card_label', __( 'Link text', 'labora' ), 'text', array( 'name' => 'label', 'wrapper' => array( 'width' => 50 ) ) ),
					$f( $p, 'card_url', __( 'Link', 'labora' ), 'text', array( 'name' => 'url', 'wrapper' => array( 'width' => 50 ) ) ),
				),
			) ),
		),
	) );

	// ---------- Trust Center ----------
	$p    = 'ltc';
	$n    = 0; // sub-field keys: stable (fields are always defined in this order) and unique across repeaters
	$sub  = function ( $name, $label, $type = 'text', $extra = array() ) use ( &$n, $p ) {
		return array_merge( array( 'key' => "field_{$p}_s" . ( ++$n ) . "_{$name}", 'name' => $name, 'label' => $label, 'type' => $type ), $extra );
	};
	$head = fn( $prefix, $title ) => array(
		$f( $p, "{$prefix}_title", __( 'Heading', 'labora' ) ),
		$f( $p, "{$prefix}_text", __( 'Text under the heading', 'labora' ), 'textarea', $area ),
	);
	acf_add_local_field_group( array(
		'key'            => 'group_labora_trust',
		'position'       => 'acf_after_title', // the page's text first, above Yoast
		'title'          => __( 'Trust Center', 'labora' ),
		'location'       => $tpl( 'page-templates/trust-center.php' ),
		'hide_on_screen' => array( 'the_content' ),
		'fields'         => array_merge(
			array(
				array( 'key' => "field_{$p}_msg", 'name' => '', 'label' => '', 'type' => 'message', 'message' => __( 'Texts of every section. The security email comes from Labora Settings; write {security_email} where it should appear. The questions are FAQ entries with "Trust Center" ticked. The product pictures (activity, permissions table, audit trail) are part of the design.', 'labora' ) ),
				$tab( __( 'Top', 'labora' ) ),
				$f( $p, 'hero_eyebrow', __( 'Label above the heading', 'labora' ) ),
				$f( $p, 'hero_title', __( 'Heading', 'labora' ) ),
				$f( $p, 'hero_lead', __( 'Text under the heading', 'labora' ), 'textarea', array( 'rows' => 3, 'new_lines' => '' ) ),
				$f( $p, 'hero_btn1_label', __( 'First button: text', 'labora' ), 'text', array( 'wrapper' => array( 'width' => 50 ) ) ),
				$f( $p, 'hero_btn1_url', __( 'First button: link', 'labora' ), 'text', array( 'wrapper' => array( 'width' => 50 ) ) ),
				$f( $p, 'hero_btn2_label', __( 'Second button: text', 'labora' ), 'text', array( 'wrapper' => array( 'width' => 50 ) ) ),
				$f( $p, 'hero_btn2_url', __( 'Second button: link', 'labora' ), 'text', array( 'wrapper' => array( 'width' => 50 ) ) ),
				$f( $p, 'marks', __( 'Badges under the buttons', 'labora' ), 'repeater', array(
					'layout' => 'table', 'button_label' => __( 'Add a badge', 'labora' ),
					'sub_fields' => array(
						$sub( 'icon', __( 'Icon', 'labora' ), 'select', $icons + array( 'allow_null' => 1 ) ),
						$sub( 'badge', __( 'Or short text (instead of an icon)', 'labora' ) ),
						$sub( 'title', __( 'Title', 'labora' ) ),
						$sub( 'note', __( 'Small line', 'labora' ) ),
						$sub( 'highlight', __( 'Highlight the small line', 'labora' ), 'true_false', array( 'ui' => 1 ) ),
					),
				) ),
				$f( $p, 'updated', __( 'Last updated', 'labora' ), 'date_picker', array( 'display_format' => 'M j, Y', 'return_format' => 'Y-m-d', 'instructions' => __( 'Shown at the top and on the program status. Change it when you update this page.', 'labora' ) ) ),
				$f( $p, 'nav', __( 'Section menu', 'labora' ), 'repeater', array(
					'layout' => 'table', 'button_label' => __( 'Add a menu item', 'labora' ),
					'instructions' => __( 'The bar under the top section. Anchors: overview, access, audit, protection, compliance, shared, disclosure, security-faq.', 'labora' ),
					'sub_fields' => array( $sub( 'label', __( 'Text', 'labora' ) ), $sub( 'anchor', __( 'Section', 'labora' ) ) ),
				) ),

				$tab( __( 'Overview', 'labora' ) ),
			),
			$head( 'overview', '' ),
			array(
				$f( $p, 'overview_cards', __( 'Cards', 'labora' ), 'repeater', array(
					'layout' => 'block', 'min' => 4, 'max' => 4, 'instructions' => __( 'Four cards, each above its product picture (activity, records, access, safeguards).', 'labora' ),
					'sub_fields' => array( $sub( 'title', __( 'Title', 'labora' ) ), $sub( 'text', __( 'Text', 'labora' ), 'textarea', $area ) ),
				) ),

				$tab( __( 'Access and audit', 'labora' ) ),
				$f( $p, 'access_kicker', __( 'Access control: label', 'labora' ) ),
				$f( $p, 'access_title', __( 'Access control: heading', 'labora' ) ),
				$f( $p, 'access_text', __( 'Access control: text', 'labora' ), 'textarea', $area ),
				$f( $p, 'access_checks', __( 'Access control: points', 'labora' ), 'textarea', $lines ),
				$f( $p, 'access_note', __( 'Note under the permissions table', 'labora' ) ),
				$f( $p, 'audit_kicker', __( 'Audit trail: label', 'labora' ) ),
				$f( $p, 'audit_title', __( 'Audit trail: heading', 'labora' ) ),
				$f( $p, 'audit_text', __( 'Audit trail: text', 'labora' ), 'textarea', $area ),
				$f( $p, 'audit_checks', __( 'Audit trail: points', 'labora' ), 'textarea', $lines ),

				$tab( __( 'Protection', 'labora' ) ),
			),
			$head( 'protection', '' ),
			array(
				$f( $p, 'protection_cards', __( 'Cards', 'labora' ), 'repeater', array(
					'layout' => 'block', 'button_label' => __( 'Add a card', 'labora' ),
					'sub_fields' => array(
						$sub( 'icon', __( 'Icon', 'labora' ), 'select', $icons + array( 'wrapper' => array( 'width' => 30 ) ) ),
						$sub( 'title', __( 'Title', 'labora' ), 'text', array( 'wrapper' => array( 'width' => 70 ) ) ),
						$sub( 'items', __( 'Points', 'labora' ), 'textarea', $lines ),
					),
				) ),

				$tab( __( 'Compliance', 'labora' ) ),
			),
			$head( 'comp', '' ),
			array(
				$f( $p, 'comp_intro', __( 'Line above the list', 'labora' ) ),
				$f( $p, 'comp_items', __( 'What the program covers', 'labora' ), 'textarea', $lines ),
				$f( $p, 'comp_note', __( 'Note under the list', 'labora' ), 'wysiwyg', array( 'tabs' => 'visual', 'toolbar' => 'basic', 'media_upload' => 0, 'delay' => 0 ) ),
				$f( $p, 'comp_status_title', __( 'Status panel: title', 'labora' ) ),
				$f( $p, 'comp_status', __( 'Status panel: items', 'labora' ), 'repeater', array(
					'layout' => 'block', 'button_label' => __( 'Add an item', 'labora' ),
					'instructions' => __( 'Confirm each status with your compliance owner before publishing.', 'labora' ),
					'sub_fields' => array(
						$sub( 'badge', __( 'Short mark', 'labora' ), 'text', array( 'wrapper' => array( 'width' => 20 ) ) ),
						$sub( 'title', __( 'Title', 'labora' ), 'text', array( 'wrapper' => array( 'width' => 50 ) ) ),
						$sub( 'status', __( 'Status', 'labora' ), 'select', array( 'choices' => array( 'live' => __( 'Complete', 'labora' ), 'progress' => __( 'In progress', 'labora' ), 'planned' => __( 'Planned', 'labora' ) ), 'wrapper' => array( 'width' => 30 ) ) ),
						$sub( 'text', __( 'Text', 'labora' ) ),
					),
				) ),

				$tab( __( 'Shared responsibility', 'labora' ) ),
			),
			$head( 'shared', '' ),
			array(
				$f( $p, 'shared_ours_title', __( 'Left column: title', 'labora' ) ),
				$f( $p, 'shared_ours_items', __( 'Left column: points', 'labora' ), 'textarea', $lines ),
				$f( $p, 'shared_lab_title', __( 'Right column: title', 'labora' ) ),
				$f( $p, 'shared_lab_items', __( 'Right column: points', 'labora' ), 'textarea', $lines ),

				$tab( __( 'Disclosure', 'labora' ) ),
			),
			$head( 'disc', '' ),
			array(
				$f( $p, 'disc_steps', __( 'Steps', 'labora' ), 'repeater', array(
					'layout' => 'table', 'button_label' => __( 'Add a step', 'labora' ),
					'sub_fields' => array( $sub( 'title', __( 'Title', 'labora' ) ), $sub( 'text', __( 'Text', 'labora' ) ) ),
				) ),
				$f( $p, 'disc_guidelines_title', __( 'Guidelines: title', 'labora' ) ),
				$f( $p, 'disc_guidelines', __( 'Guidelines', 'labora' ), 'textarea', $lines ),
				$f( $p, 'disc_contact_title', __( 'Report box: title', 'labora' ) ),
				$f( $p, 'disc_contact_text', __( 'Report box: text', 'labora' ), 'textarea', $area ),

				$tab( __( 'Questions and contact', 'labora' ) ),
				$f( $p, 'faq_title', __( 'Questions: heading', 'labora' ) ),
				$f( $p, 'faq_text', __( 'Questions: text', 'labora' ) ),
				$f( $p, 'faq_link_label', __( 'Questions: link to the FAQ page', 'labora' ) ),
				$f( $p, 'cta_title', __( 'Closing box: heading', 'labora' ) ),
				$f( $p, 'cta_text', __( 'Closing box: text', 'labora' ), 'textarea', $area ),
				$f( $p, 'cta_btn1_label', __( 'Closing box: email button text', 'labora' ), 'text', array( 'instructions' => __( 'Opens an email to the security address.', 'labora' ) ) ),
				$f( $p, 'cta_btn2_label', __( 'Closing box: second button text', 'labora' ), 'text', array( 'wrapper' => array( 'width' => 50 ) ) ),
				$f( $p, 'cta_btn2_url', __( 'Closing box: second button link', 'labora' ), 'text', array( 'wrapper' => array( 'width' => 50 ) ) ),
			)
		),
	) );
} );

/**
 * Pages whose text is all in fields open in the classic edit screen, so the fields fill the page (the block
 * editor would show an empty canvas and tuck the fields away at the bottom). Filter: labora_field_templates.
 */
add_filter( 'use_block_editor_for_post', function ( $use, $post ) {
	$templates = (array) apply_filters( 'labora_field_templates', array( 'page-templates/faq.php', 'page-templates/trust-center.php' ) );
	return ( $post && 'page' === $post->post_type && in_array( get_page_template_slug( $post ), $templates, true ) ) ? false : $use;
}, 10, 2 );
