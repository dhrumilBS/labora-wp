<?php
/**
 * Archives: categories, tags, authors, dates.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_template_assets( 'blog' );
get_header();
get_template_part( 'template-parts/post-list', null, array(
	'label' => is_category() ? __( 'Topic', 'labora' ) : ( is_tag() ? __( 'Tag', 'labora' ) : ( is_author() ? __( 'Author', 'labora' ) : __( 'Archive', 'labora' ) ) ),
	// Term and author archives show just their name ("Turnaround time", not "Category: Turnaround time")
	'title' => ( is_category() || is_tag() || is_tax() ) ? single_term_title( '', false ) : ( is_author() ? get_the_author() : wp_strip_all_tags( get_the_archive_title() ) ),
	'lead'  => get_the_archive_description(),
) );
get_footer();
