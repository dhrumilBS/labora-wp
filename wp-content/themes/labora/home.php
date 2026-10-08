<?php
/**
 * Blog posts index (Settings > Reading > Posts page). The full blog hub (featured story, topic filters, most read,
 * newsletter) is converted from the HTML site when the blog is approved; until then this lists the posts.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_template_assets( 'blog' );
get_header();
$labora_page = (int) get_option( 'page_for_posts' );
get_template_part( 'template-parts/post-list', null, array(
	'label' => __( 'The Labora Journal', 'labora' ),
	'title' => $labora_page ? get_the_title( $labora_page ) : __( 'Blog', 'labora' ),
	'lead'  => $labora_page ? get_the_excerpt( $labora_page ) : '',
) );
get_footer();
