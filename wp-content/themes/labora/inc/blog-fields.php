<?php
/**
 * Blog fields (Secure Custom Fields, defined in code):
 *
 *   Posts       "Article": cover (featured image, or a product screenshot), cover anchored right, reading time,
 *               reviewed by, the box under the table of contents
 *   Blog page   "Blog page": intro (label, heading, text), featured post, "Most read" list
 *   Categories  "Order" in the topic filter
 *   Users       role line under the name, and "Show the Labora logo" instead of a photo (team accounts)
 *
 * The newsletter box's texts are in Labora Settings > Newsletter (inc/settings.php).
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	$screens = array( '' => __( '(none: use the featured image)', 'labora' ) ) + labora_cover_screens();

	acf_add_local_field_group( array(
		'key'      => 'group_labora_post',
		'title'    => __( 'Article', 'labora' ),
		'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ) ) ),
		'position' => 'side',
		'fields'   => array(
			array( 'key' => 'field_lp_cover_screen', 'name' => 'cover_screen', 'label' => __( 'Cover screenshot', 'labora' ), 'type' => 'select', 'choices' => $screens, 'default_value' => 'dashboard',
				'instructions' => __( 'A product screenshot as the cover. A featured image, when set, is used instead.', 'labora' ) ),
			array( 'key' => 'field_lp_cover_right', 'name' => 'cover_right', 'label' => __( 'Show the right side of the cover', 'labora' ), 'type' => 'true_false', 'ui' => 1,
				'instructions' => __( 'For screenshots whose important part is on the right.', 'labora' ) ),
			array( 'key' => 'field_lp_read_minutes', 'name' => 'read_minutes', 'label' => __( 'Reading time (minutes)', 'labora' ), 'type' => 'number', 'min' => 1, 'max' => 90,
				'instructions' => __( 'Empty: counted from the text.', 'labora' ) ),
			array( 'key' => 'field_lp_reviewed_by', 'name' => 'reviewed_by', 'label' => __( 'Reviewed by', 'labora' ), 'type' => 'text',
				'instructions' => __( 'Optional, e.g. "Dr. A. Patel, MD Pathology". Shown under the title.', 'labora' ) ),
			array( 'key' => 'field_lp_toc_title', 'name' => 'toc_cta_title', 'label' => __( 'Box under the contents: heading', 'labora' ), 'type' => 'text', 'placeholder' => __( 'See Labora live', 'labora' ) ),
			array( 'key' => 'field_lp_toc_text', 'name' => 'toc_cta_text', 'label' => __( 'Box under the contents: text', 'labora' ), 'type' => 'textarea', 'rows' => 2, 'new_lines' => '',
				'placeholder' => __( 'A 20-minute walkthrough, set up with your own tests.', 'labora' ) ),
		),
	) );

	{
		acf_add_local_field_group( array(
			'key'      => 'group_labora_blog_page',
			'title'    => __( 'Blog page', 'labora' ),
			'location' => array( array( array( 'param' => 'page_type', 'operator' => '==', 'value' => 'posts_page' ) ) ),
			'fields'   => array(
				array( 'key' => 'field_lb_hub_message', 'name' => '', 'label' => '', 'type' => 'message', 'message' => __( 'This page lists the posts. Its text comes from the fields below; the page content is not shown.', 'labora' ) ),
				array( 'key' => 'field_lb_hub_eyebrow', 'name' => 'hub_eyebrow', 'label' => __( 'Label above the heading', 'labora' ), 'type' => 'text', 'placeholder' => 'The Labora Journal' ),
				array( 'key' => 'field_lb_hub_title', 'name' => 'hub_title', 'label' => __( 'Heading', 'labora' ), 'type' => 'text', 'placeholder' => 'Ideas for running a faster, calmer diagnostic lab' ),
				array( 'key' => 'field_lb_hub_lead', 'name' => 'hub_lead', 'label' => __( 'Text under the heading', 'labora' ), 'type' => 'textarea', 'rows' => 2, 'new_lines' => '' ),
				array( 'key' => 'field_lb_featured', 'name' => 'featured_post', 'label' => __( 'Featured post', 'labora' ), 'type' => 'post_object', 'post_type' => array( 'post' ), 'return_format' => 'id', 'allow_null' => 1,
					'instructions' => __( 'The large card at the top. Empty: the newest post.', 'labora' ) ),
				array( 'key' => 'field_lb_most_read', 'name' => 'most_read', 'label' => __( '"Most read" list', 'labora' ), 'type' => 'relationship', 'post_type' => array( 'post' ), 'return_format' => 'id', 'max' => 5, 'filters' => array( 'search', 'taxonomy' ),
					'instructions' => __( 'Up to five posts, in order. Empty: the five newest.', 'labora' ) ),
			),
		) );
	}

	acf_add_local_field_group( array(
		'key'      => 'group_labora_topic',
		'title'    => __( 'Topic', 'labora' ),
		'location' => array( array( array( 'param' => 'taxonomy', 'operator' => '==', 'value' => 'category' ) ) ),
		'fields'   => array(
			array( 'key' => 'field_lt_order', 'name' => 'topic_order', 'label' => __( 'Order in the topic filter', 'labora' ), 'type' => 'number', 'min' => 1,
				'instructions' => __( 'Lower numbers come first. The description is shown under the topic\'s heading and sent to search engines.', 'labora' ) ),
		),
	) );

	acf_add_local_field_group( array(
		'key'      => 'group_labora_author',
		'title'    => __( 'Author box', 'labora' ),
		'location' => array( array( array( 'param' => 'user_form', 'operator' => '==', 'value' => 'all' ) ) ),
		'fields'   => array(
			array( 'key' => 'field_la_role', 'name' => 'labora_role', 'label' => __( 'Role line', 'labora' ), 'type' => 'text', 'placeholder' => 'Product and lab operations',
				'instructions' => __( 'Shown under the name in the author box at the end of a post. The text is the "Biographical Info" above.', 'labora' ) ),
			array( 'key' => 'field_la_logo', 'name' => 'labora_logo_avatar', 'label' => __( 'Show the Labora logo instead of a photo', 'labora' ), 'type' => 'true_false', 'ui' => 1,
				'instructions' => __( 'For team accounts such as "Labora Team". Their posts are credited to the organization in search results.', 'labora' ) ),
		),
	) );
} );
