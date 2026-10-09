<?php
/**
 * WP-CLI: wp labora blog-setup [--overwrite]
 *
 * Sets up the blog as on the HTML site, and can be run again at any time:
 *   - URLs: posts at /blog/<slug>/, topics at /blog/topic/<slug>/, tags at /blog/tag/<slug>/
 *   - the "Blog" page as the posts page, with its search title and description
 *   - the "Labora Team" author (shown with the Labora logo; credited to the organization in search results)
 *   - the topics (categories) with their order and descriptions
 *   - the articles in data/blog/ (posts.json + one .html file each): created when missing; with --overwrite,
 *     existing articles are replaced with the files' version (changes made in WordPress are lost)
 *
 * Article files are HTML with a few extra tags, converted to WordPress blocks:
 *   <p> <h2 id> <h3> <ul> <ol> <ul class="checklist"> <table> <blockquote>
 *   <shot name="turnaround-tracking" alt="...">caption</shot>     product screenshot (added to the Media Library)
 *   <takeaways title=""><li>...</li></takeaways>                  Key takeaways block
 *   <callout type="tip|note|warning" title="...">text</callout>   Note, tip, or warning block
 *   <demo title="..." label="" url="">text</demo>                Book a demo box
 *   <stats note="..."><stat value="..." label="..."></stat></stats>   Key numbers block
 *   <compare before="Today" after="With Labora"><before>..</before><after>..</after></compare>
 *   <faq title=""><qa question="...">answer</qa></faq>             Questions and answers block
 *
 * @package Labora_Child
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/** Topics: slug => [ name, order, description ]. */
function labora_blog_seed_topics(): array {
	return array(
		'turnaround'      => array( 'Turnaround time', 1, 'Measuring and cutting turnaround time (TAT) in a diagnostic lab: targets by test, delay alerts, and faster sign-off.' ),
		'operations'      => array( 'Lab operations', 2, 'Running the lab day to day: sample tracking, handoffs, racks, rejections, and the front desk.' ),
		'home-collection' => array( 'Home collection', 3, 'Home sample collection that patients trust: bookings, arrival times, preparation, and getting samples to the bench.' ),
		'reporting'       => array( 'Reporting', 4, 'Pathology and radiology reporting: templates, review, digital signatures, and delivery to patients and doctors.' ),
		'growth'          => array( 'Business growth', 5, 'Growing a diagnostic lab or lab chain: revenue by center, referring doctors, and the numbers to watch each week.' ),
		'product'         => array( 'Product updates', 6, 'What is new in Labora.' ),
		'guides'          => array( 'Buyer guides', 7, 'Choosing laboratory management software: questions to ask, data migration, and rollout.' ),
	);
}

/** The value of a block field and its key, as SCF stores block data. */
function labora_blog_seed_data( array $fields ): array {
	$data = array();
	foreach ( $fields as $name => list( $value, $key ) ) {
		$data[ $name ]       = $value;
		$data[ '_' . $name ] = $key;
	}
	return $data;
}

/** inner HTML of a DOM node. */
function labora_blog_seed_inner( DOMNode $node ): string {
	$html = '';
	foreach ( $node->childNodes as $child ) {
		$html .= $node->ownerDocument->saveHTML( $child );
	}
	return trim( preg_replace( '/\s+/', ' ', $html ) );
}

/** A screenshot in the Media Library (added once), or 0. */
function labora_blog_seed_screenshot( string $name ): int {
	$found = get_posts( array( 'post_type' => 'attachment', 'meta_key' => '_labora_screen', 'meta_value' => $name, 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( $found ) {
		return (int) $found[0];
	}
	$file = get_theme_file_path( "assets/img/screens/{$name}-1200.webp" );
	if ( ! file_exists( $file ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( $name );
	copy( $file, $tmp );
	$id = media_handle_sideload( array( 'name' => "labora-{$name}.webp", 'tmp_name' => $tmp ), 0, 'Labora: ' . $name );
	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( "Screenshot {$name}: " . $id->get_error_message() );
		return 0;
	}
	update_post_meta( $id, '_labora_screen', $name );
	return (int) $id;
}

/** A theme image in the Media Library (added once), or 0. */
function labora_blog_seed_theme_image( string $path, string $title ): int {
	$found = get_posts( array( 'post_type' => 'attachment', 'meta_key' => '_labora_asset', 'meta_value' => $path, 'numberposts' => 1, 'fields' => 'ids' ) );
	if ( $found ) {
		return (int) $found[0];
	}
	$file = get_theme_file_path( 'assets/' . $path );
	if ( ! file_exists( $file ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( basename( $path ) );
	copy( $file, $tmp );
	$id = media_handle_sideload( array( 'name' => 'labora-' . basename( $path ), 'tmp_name' => $tmp ), 0, $title );
	if ( is_wp_error( $id ) ) {
		return 0;
	}
	update_post_meta( $id, '_labora_asset', $path );
	return (int) $id;
}

/** Convert one article file to block markup. */
function labora_blog_seed_blocks( string $html ): string {
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8"?><body>' . $html . '</body>', LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	$body = $dom->getElementsByTagName( 'body' )->item( 0 );
	$out  = array();
	$b    = 'get_comment_delimited_block_content';

	$list = function ( DOMElement $el, array $attrs = array() ) use ( $b ) {
		$tag   = $el->tagName;
		$items = '';
		foreach ( $el->childNodes as $li ) {
			if ( $li instanceof DOMElement && 'li' === $li->tagName ) {
				$items .= $b( 'core/list-item', array(), '<li>' . labora_blog_seed_inner( $li ) . '</li>' );
			}
		}
		$class = 'wp-block-list' . ( isset( $attrs['className'] ) ? ' ' . $attrs['className'] : '' );
		return $b( 'core/list', $attrs, "\n<{$tag} class=\"{$class}\">{$items}</{$tag}>\n" );
	};

	foreach ( $body->childNodes as $el ) {
		if ( ! $el instanceof DOMElement ) {
			continue;
		}
		$attr = fn( $n ) => trim( (string) $el->getAttribute( $n ) );
		switch ( $el->tagName ) {
			case 'p':
				$out[] = $b( 'core/paragraph', array(), "\n<p>" . labora_blog_seed_inner( $el ) . "</p>\n" );
				break;
			case 'h2':
			case 'h3':
				$id    = $attr( 'id' );
				$level = (int) substr( $el->tagName, 1 );
				$out[] = $b( 'core/heading', 3 === $level ? array( 'level' => 3 ) : array(), "\n<{$el->tagName} class=\"wp-block-heading\"" . ( $id ? " id=\"{$id}\"" : '' ) . '>' . labora_blog_seed_inner( $el ) . "</{$el->tagName}>\n" );
				break;
			case 'ul':
				$out[] = $list( $el, str_contains( $attr( 'class' ), 'checklist' ) ? array( 'className' => 'is-style-checklist' ) : array() );
				break;
			case 'ol':
				$out[] = $list( $el, array( 'ordered' => true ) );
				break;
			case 'blockquote':
				$out[] = $b( 'core/quote', array(), "\n<blockquote class=\"wp-block-quote\">" . $b( 'core/paragraph', array(), "\n<p>" . labora_blog_seed_inner( $el ) . "</p>\n" ) . "</blockquote>\n" );
				break;
			case 'table':
				$table = preg_replace( '/\s*(<\/?(?:thead|tbody|tr|th|td)\b[^>]*>)\s*/', '$1', labora_blog_seed_inner( $el ) );
				$out[] = $b( 'core/table', array( 'hasFixedLayout' => false, 'className' => 'is-style-data-table' ), "\n<figure class=\"wp-block-table is-style-data-table\"><table>{$table}</table></figure>\n" );
				break;
			case 'shot':
				$id = labora_blog_seed_screenshot( $attr( 'name' ) );
				if ( ! $id ) {
					break;
				}
				$src   = (string) wp_get_attachment_image_url( $id, 'large' );
				$cap   = labora_blog_seed_inner( $el );
				$out[] = $b( 'core/image', array( 'id' => $id, 'sizeSlug' => 'large', 'linkDestination' => 'none' ),
					"\n<figure class=\"wp-block-image size-large\"><img src=\"" . esc_url( $src ) . '" alt="' . esc_attr( $attr( 'alt' ) ) . "\" class=\"wp-image-{$id}\"/>" . ( $cap ? "<figcaption class=\"wp-element-caption\">{$cap}</figcaption>" : '' ) . "</figure>\n" );
				break;
			case 'takeaways':
				$fields = array( 'title' => array( $attr( 'title' ), 'field_lb_takeaways_title' ) );
				$i      = 0;
				foreach ( $el->getElementsByTagName( 'li' ) as $li ) {
					$fields[ "points_{$i}_text" ] = array( labora_blog_seed_inner( $li ), 'field_lb_takeaways_point' );
					$i++;
				}
				$fields['points'] = array( $i, 'field_lb_takeaways_points' );
				$out[]            = labora_blog_seed_acf( 'labora-takeaways', $fields );
				break;
			case 'callout':
				$out[] = labora_blog_seed_acf( 'labora-callout', array(
					'type'  => array( $attr( 'type' ) ?: 'tip', 'field_lb_callout_type' ),
					'title' => array( $attr( 'title' ), 'field_lb_callout_title' ),
					'text'  => array( labora_blog_seed_inner( $el ), 'field_lb_callout_text' ),
				) );
				break;
			case 'demo':
				$out[] = labora_blog_seed_acf( 'labora-demo', array(
					'title' => array( $attr( 'title' ), 'field_lb_demo_title' ),
					'text'  => array( labora_blog_seed_inner( $el ), 'field_lb_demo_text' ),
					'label' => array( $attr( 'label' ), 'field_lb_demo_label' ),
					'url'   => array( $attr( 'url' ), 'field_lb_demo_url' ),
				) );
				break;
			case 'stats':
				$fields = array( 'note' => array( $attr( 'note' ), 'field_lb_stats_note' ) );
				$i      = 0;
				foreach ( $el->getElementsByTagName( 'stat' ) as $st ) {
					$fields[ "items_{$i}_value" ] = array( $st->getAttribute( 'value' ), 'field_lb_stats_value' );
					$fields[ "items_{$i}_label" ] = array( $st->getAttribute( 'label' ), 'field_lb_stats_label' );
					$i++;
				}
				$fields['items'] = array( $i, 'field_lb_stats_items' );
				$out[]           = labora_blog_seed_acf( 'labora-stats', $fields );
				break;
			case 'compare':
				$fields = array(
					'before_label' => array( $attr( 'before' ), 'field_lb_compare_before_label' ),
					'after_label'  => array( $attr( 'after' ), 'field_lb_compare_after_label' ),
				);
				foreach ( array( 'before', 'after' ) as $side ) {
					$i = 0;
					foreach ( $el->getElementsByTagName( $side ) as $line ) {
						$fields[ "{$side}_{$i}_text" ] = array( labora_blog_seed_inner( $line ), "field_lb_{$side}_text" );
						$i++;
					}
					$fields[ $side ] = array( $i, "field_lb_{$side}" );
				}
				$out[] = labora_blog_seed_acf( 'labora-compare', $fields );
				break;
			case 'faq':
				$fields = array( 'title' => array( $attr( 'title' ), 'field_lb_faq_title' ) );
				$i      = 0;
				foreach ( $el->getElementsByTagName( 'qa' ) as $qa ) {
					$fields[ "items_{$i}_question" ] = array( $qa->getAttribute( 'question' ), 'field_lb_faq_question' );
					$fields[ "items_{$i}_answer" ]   = array( labora_blog_seed_inner( $qa ), 'field_lb_faq_answer' );
					$i++;
				}
				$fields['items'] = array( $i, 'field_lb_faq_items' );
				$out[]           = labora_blog_seed_acf( 'labora-faq', $fields );
				break;
			default:
				WP_CLI::warning( "Unknown element <{$el->tagName}> left out." );
		}
	}
	return implode( "\n\n", $out );
}

/** An SCF block comment: <!-- wp:acf/name {"name":..,"data":{..},"mode":"preview"} /-->. */
function labora_blog_seed_acf( string $name, array $fields ): string {
	return serialize_block( array(
		'blockName'    => "acf/{$name}",
		'attrs'        => array( 'name' => "acf/{$name}", 'data' => labora_blog_seed_data( $fields ), 'mode' => 'preview' ),
		'innerBlocks'  => array(),
		'innerHTML'    => '',
		'innerContent' => array(),
	) );
}

WP_CLI::add_command( 'labora blog-setup', function ( $args, $assoc ) {
	global $wp_rewrite;
	$overwrite = ! empty( $assoc['overwrite'] );

	// URLs (chr(47) is "/": avoids Git Bash rewriting a leading slash on Windows)
	$wp_rewrite->set_permalink_structure( chr( 47 ) . 'blog' . chr( 47 ) . '%postname%' . chr( 47 ) );
	$wp_rewrite->set_category_base( 'blog' . chr( 47 ) . 'topic' ); // a custom base does not get the /blog/ front
	$wp_rewrite->set_tag_base( 'blog' . chr( 47 ) . 'tag' );
	WP_CLI::log( 'URLs: /blog/<post>/, /blog/topic/<topic>/, /blog/tag/<tag>/' );

	// Blog page
	$page = get_page_by_path( 'blog' );
	$id   = $page ? (int) $page->ID : (int) wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Blog', 'post_name' => 'blog', 'comment_status' => 'closed' ) );
	update_option( 'page_for_posts', $id );
	update_option( 'show_on_front', 'page' );
	update_post_meta( $id, '_yoast_wpseo_title', 'Lab Management Blog: Guides for Diagnostic Labs %%sep%% %%sitename%%' );
	update_post_meta( $id, '_yoast_wpseo_metadesc', 'Practical guides on lab turnaround time, barcode sample tracking, pathology and radiology reporting, home collection, and growing a multi-center diagnostic lab.' );
	WP_CLI::log( "Blog page: #{$id}" );

	// Organization logo in Yoast (Yoast outputs the Organization, the articles' author, only with a logo)
	if ( class_exists( 'WPSEO_Options' ) && ! WPSEO_Options::get( 'company_logo_id' ) ) {
		$logo = labora_blog_seed_theme_image( 'img/icon-512.png', 'Labora logo' );
		if ( $logo ) {
			WPSEO_Options::set( 'company_logo', (string) wp_get_attachment_url( $logo ) );
			WPSEO_Options::set( 'company_logo_id', $logo );
			WPSEO_Options::set( 'company_or_person', 'company' );
			WPSEO_Options::set( 'company_name', (string) labora_setting( 'company_name' ) );
			WP_CLI::log( 'Yoast organization logo set.' );
		}
	}

	// Topic and tag pages: the description is the search description
	if ( class_exists( 'WPSEO_Options' ) ) {
		WPSEO_Options::set( 'metadesc-tax-category', '%%category_description%%' );
		WPSEO_Options::set( 'metadesc-tax-post_tag', '%%tag_description%%' );
		WPSEO_Options::set( 'title-tax-category', '%%term_title%% articles %%sep%% %%sitename%% blog' );
	}

	// Author
	$user = get_user_by( 'login', 'labora-team' );
	$uid  = $user ? (int) $user->ID : (int) wp_insert_user( array(
		'user_login'   => 'labora-team',
		'user_pass'    => wp_generate_password( 32 ),
		'user_email'   => 'labora-team@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '.invalid',
		'display_name' => 'Labora Team',
		'nickname'     => 'Labora Team',
		'first_name'   => 'Labora',
		'last_name'    => 'Team',
		'role'         => 'author',
		'description'  => 'We build Labora with pathology, imaging, and multi-center labs, and write about what we learn running faster, calmer labs.',
	) );
	if ( is_wp_error( $uid ) || ! $uid ) {
		WP_CLI::error( 'Could not create the Labora Team author.' );
	}
	update_user_meta( $uid, 'labora_role', 'Product and lab operations' );
	update_user_meta( $uid, 'labora_logo_avatar', 1 );

	// Topics
	$topics = array();
	foreach ( labora_blog_seed_topics() as $slug => list( $name, $order, $desc ) ) {
		$term = get_term_by( 'slug', $slug, 'category' );
		$tid  = $term ? (int) $term->term_id : (int) wp_insert_term( $name, 'category', array( 'slug' => $slug, 'description' => $desc ) )['term_id'];
		if ( $term && ( $overwrite || '' === $term->description ) ) {
			wp_update_term( $tid, 'category', array( 'name' => $name, 'description' => $desc ) );
		}
		update_term_meta( $tid, 'topic_order', $order );
		$topics[ $slug ] = $tid;
	}
	WP_CLI::log( count( $topics ) . ' topics.' );

	// Articles
	$dir   = LABORA_CHILD_DIR . '/data/blog';
	$posts = json_decode( (string) file_get_contents( "{$dir}/posts.json" ), true );
	if ( ! is_array( $posts ) ) {
		WP_CLI::error( 'data/blog/posts.json is missing or not valid JSON.' );
	}
	foreach ( $posts as $p ) {
		$existing = get_page_by_path( $p['slug'], OBJECT, 'post' );
		if ( $existing && ! $overwrite ) {
			WP_CLI::log( "  kept   {$p['slug']} (use --overwrite to replace it)" );
			continue;
		}
		if ( ! is_readable( "{$dir}/{$p['slug']}.html" ) ) {
			WP_CLI::warning( "data/blog/{$p['slug']}.html is missing, skipped." );
			continue;
		}
		$content = labora_blog_seed_blocks( (string) file_get_contents( "{$dir}/{$p['slug']}.html" ) );
		$date    = $p['date'] . ' 09:00:00';
		$pid     = wp_insert_post( array(
			'ID'             => $existing ? $existing->ID : 0,
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'post_name'      => $p['slug'],
			'post_title'     => $p['title'],
			'post_excerpt'   => $p['excerpt'],
			'post_content'   => wp_slash( $content ),
			'post_author'    => $uid,
			'post_date'      => $date,
			'post_date_gmt'  => get_gmt_from_date( $date ),
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		), true );
		if ( is_wp_error( $pid ) ) {
			WP_CLI::warning( "{$p['slug']}: " . $pid->get_error_message() );
			continue;
		}
		$cats = array_values( array_filter( array_map( fn( $s ) => $topics[ $s ] ?? 0, $p['topics'] ) ) );
		wp_set_post_categories( $pid, $cats );
		wp_set_post_tags( $pid, $p['tags'] ?? array() );
		update_post_meta( $pid, '_yoast_wpseo_primary_category', $cats[0] ?? 0 );
		foreach ( array( 'cover_screen' => 'field_lp_cover_screen', 'cover_right' => 'field_lp_cover_right', 'read_minutes' => 'field_lp_read_minutes', 'toc_cta_title' => 'field_lp_toc_title', 'toc_cta_text' => 'field_lp_toc_text', 'reviewed_by' => 'field_lp_reviewed_by' ) as $meta => $key ) {
			update_post_meta( $pid, $meta, $p[ $meta ] ?? '' );
			update_post_meta( $pid, "_{$meta}", $key );
		}
		update_post_meta( $pid, '_yoast_wpseo_title', $p['seo_title'] );
		update_post_meta( $pid, '_yoast_wpseo_metadesc', $p['seo_description'] );
		update_post_meta( $pid, '_yoast_wpseo_focuskw', $p['focus_keyphrase'] ?? '' );
		WP_CLI::log( ( $existing ? '  update ' : '  create ' ) . $p['slug'] );
	}

	// The topic and tag rules were registered before their base changed in this run: rebuild them in a new process
	WP_CLI::runcommand( 'rewrite flush', array( 'launch' => true, 'exit_error' => false ) );
	WP_CLI::success( 'Blog is set up.' );
} );
