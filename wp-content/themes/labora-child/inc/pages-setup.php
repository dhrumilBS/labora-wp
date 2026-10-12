<?php
/**
 * WP-CLI: wp labora pages-setup [--overwrite]
 *
 * Creates the content of the fixed-layout pages from the HTML site, and can be run again at any time:
 *   - FAQ topics and entries (data/faq.json): created when missing, matched by their link anchor
 *   - pages (data/pages/<slug>.json): the page, its template, its fields, and its search title and description
 * With --overwrite, existing entries and page fields are replaced with the files' version (edits made in
 * WordPress are lost). Data files use "/path/" for links on this site and {security_email} for the security email.
 *
 * @package Labora_Child
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/** Field key prefix of each page template (inc/page-fields.php in the parent theme). */
function labora_pages_field_prefixes(): array {
	return array(
		'page-templates/faq.php'          => 'lfp',
		'page-templates/trust-center.php' => 'ltc',
		'page-templates/about.php'        => 'lab',
		'page-templates/platform.php'     => 'lpm',
		'page-templates/solution.php'     => 'lso',
		'page-templates/redirect.php'     => 'lrd',
		'page-templates/legal.php'        => 'lgl',
	);
}

/** An answer from the data file as paragraph blocks, with the email placeholders as shortcodes. */
function labora_pages_answer_blocks( string $html ): string {
	$html = str_replace(
		array( '{security_email_link}', '{security_email}' ),
		array( '[labora_email key="security"]', '[labora_email key="security" link="no"]' ),
		$html
	);
	$html = preg_replace_callback( '#href="(/[^"]*)"#', fn( $m ) => 'href="' . esc_url( home_url( $m[1] ) ) . '"', $html );
	preg_match_all( '#<p>(.*?)</p>#s', $html, $paras );
	return implode( "\n\n", array_map( fn( $p ) => get_comment_delimited_block_content( 'core/paragraph', array(), "\n<p>{$p}</p>\n" ), $paras[1] ?: array( $html ) ) );
}

WP_CLI::add_command( 'labora pages-setup', function ( $args, $assoc ) {
	if ( ! function_exists( 'update_field' ) ) {
		WP_CLI::error( 'Secure Custom Fields must be active.' );
	}
	$overwrite = ! empty( $assoc['overwrite'] );
	$dir       = LABORA_CHILD_DIR . '/data';

	// FAQ topics and entries
	$faq = json_decode( (string) file_get_contents( "{$dir}/faq.json" ), true );
	$tid = array();
	foreach ( (array) ( $faq['topics'] ?? array() ) as $t ) {
		$term = get_term_by( 'slug', $t['slug'], LABORA_FAQ_TOPIC );
		$id   = $term ? (int) $term->term_id : (int) wp_insert_term( $t['name'], LABORA_FAQ_TOPIC, array( 'slug' => $t['slug'] ) )['term_id'];
		if ( ! $term || $overwrite ) {
			wp_update_term( $id, LABORA_FAQ_TOPIC, array( 'name' => $t['name'] ) );
			update_field( 'field_lft_icon', $t['icon'], LABORA_FAQ_TOPIC . '_' . $id );
			update_field( 'field_lft_intro', $t['intro'], LABORA_FAQ_TOPIC . '_' . $id );
			update_field( 'field_lft_order', $t['order'], LABORA_FAQ_TOPIC . '_' . $id );
		}
		$tid[ $t['slug'] ] = $id;
	}
	$made = 0;
	foreach ( (array) ( $faq['entries'] ?? array() ) as $e ) {
		$found = get_posts( array( 'post_type' => LABORA_FAQ_TYPE, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => 'faq_anchor', 'meta_value' => $e['anchor'] ) );
		if ( $found && ! $overwrite ) {
			continue;
		}
		$id = wp_insert_post( array(
			'ID'           => $found ? (int) $found[0] : 0,
			'post_type'    => LABORA_FAQ_TYPE,
			'post_status'  => 'publish',
			'post_title'   => $e['question'],
			'post_content' => wp_slash( labora_pages_answer_blocks( $e['answer'] ) ),
			'menu_order'   => (int) $e['order'],
		), true );
		if ( is_wp_error( $id ) ) {
			WP_CLI::warning( $e['anchor'] . ': ' . $id->get_error_message() );
			continue;
		}
		wp_set_object_terms( $id, array( $tid[ $e['topic'] ] ?? 0 ), LABORA_FAQ_TOPIC );
		update_field( 'field_lf_anchor', $e['anchor'], $id );
		update_field( 'field_lf_show_on', $e['show_on'], $id );
		$made++;
	}
	WP_CLI::log( count( $tid ) . " FAQ topics, {$made} entries written." );

	// Pages: data/pages/*.json and data/pages/<parent>/*.json. First every page exists (parents first), then fields,
	// so "@page:<path>" (a page) and "@post:<slug>" (a blog post) in the data can be turned into IDs.
	$files = array_merge( glob( "{$dir}/pages/*.json" ), glob( "{$dir}/pages/*/*.json" ) );
	$pages = array();
	foreach ( $files as $file ) {
		$p = json_decode( (string) file_get_contents( $file ), true );
		if ( ! isset( labora_pages_field_prefixes()[ $p['template'] ?? '' ] ) ) {
			WP_CLI::warning( basename( $file ) . ': unknown template ' . ( $p['template'] ?? '' ) );
			continue;
		}
		$p['path'] = ( empty( $p['parent'] ) ? '' : $p['parent'] . '/' ) . $p['slug'];
		$pages[]   = $p;
	}
	usort( $pages, fn( $a, $b ) => substr_count( $a['path'], '/' ) <=> substr_count( $b['path'], '/' ) );

	$ids = array();
	foreach ( $pages as &$p ) {
		$page      = get_page_by_path( $p['path'] );
		$p['new']  = ! $page;
		$parent_id = empty( $p['parent'] ) ? 0 : (int) ( $ids[ $p['parent'] ] ?? 0 );
		$id        = $page ? (int) $page->ID : (int) wp_insert_post( array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'post_title'     => $p['title'],
			'post_name'      => $p['slug'],
			'post_parent'    => $parent_id,
			'menu_order'     => (int) ( $p['order'] ?? 0 ),
			'comment_status' => 'closed',
		) );
		if ( $page && $overwrite ) {
			wp_update_post( array( 'ID' => $id, 'post_title' => $p['title'], 'post_parent' => $parent_id, 'menu_order' => (int) ( $p['order'] ?? 0 ) ) );
		}
		update_post_meta( $id, '_wp_page_template', $p['template'] );
		$ids[ $p['path'] ] = $id;
	}
	unset( $p );

	$resolve = function ( $value ) use ( &$resolve, $ids ) {
		if ( is_array( $value ) ) {
			return array_map( $resolve, $value );
		}
		if ( is_string( $value ) && str_starts_with( $value, '@page:' ) ) {
			return (int) ( $ids[ substr( $value, 6 ) ] ?? ( ( $pg = get_page_by_path( substr( $value, 6 ) ) ) ? $pg->ID : 0 ) );
		}
		if ( is_string( $value ) && str_starts_with( $value, '@post:' ) ) {
			$post = get_page_by_path( substr( $value, 6 ), OBJECT, 'post' );
			return $post ? (int) $post->ID : 0;
		}
		return $value;
	};
	foreach ( $pages as $p ) {
		$id     = $ids[ $p['path'] ];
		$prefix = labora_pages_field_prefixes()[ $p['template'] ];
		if ( $p['new'] || $overwrite ) {
			foreach ( $p['fields'] as $name => $value ) {
				update_field( "field_{$prefix}_{$name}", $resolve( $value ), $id );
			}
			// Text in the block editor: data/<content file>, converted like the blog articles (inc/blog-setup.php)
			if ( ! empty( $p['content'] ) && is_readable( "{$dir}/{$p['content']}" ) ) {
				$html = (string) file_get_contents( "{$dir}/{$p['content']}" );
				$html = preg_replace_callback( '#href="(/[^"]*)"#', fn( $m ) => 'href="' . esc_url( home_url( $m[1] ) ) . '"', $html );
				wp_update_post( array( 'ID' => $id, 'post_content' => wp_slash( labora_blog_seed_blocks( $html ) ), 'post_excerpt' => (string) ( $p['excerpt'] ?? '' ) ) );
			}
			if ( isset( $p['seo_title'] ) ) {
				update_post_meta( $id, '_yoast_wpseo_title', $p['seo_title'] );
				update_post_meta( $id, '_yoast_wpseo_metadesc', $p['seo_description'] );
			}
		}
		WP_CLI::log( ( $p['new'] ? 'created ' : ( $overwrite ? 'updated ' : 'kept    ' ) ) . "/{$p['path']}/ (#{$id})" );
	}
	WP_CLI::success( 'Pages are set up.' );
} );
