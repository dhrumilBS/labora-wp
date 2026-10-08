<?php
/**
 * Search results.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_template_assets( 'blog' );
get_header();
get_template_part( 'template-parts/post-list', null, array(
	'label'  => __( 'Search', 'labora' ),
	/* translators: %s: search term */
	'title'  => get_search_query() ? sprintf( __( 'Results for "%s"', 'labora' ), get_search_query() ) : __( 'Search', 'labora' ),
	'search' => true,
) );
get_footer();
