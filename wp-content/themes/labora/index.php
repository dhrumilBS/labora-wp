<?php
/**
 * Fallback template: WordPress uses it when no more specific template applies (see home.php, archive.php,
 * search.php, single.php, page.php, 404.php, front-page.php).
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_template_assets( 'blog' );
get_header();
get_template_part( 'template-parts/post-list', null, array( 'title' => wp_get_document_title() ) );
get_footer();
