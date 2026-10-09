<?php
/**
 * The blog ("The Labora Journal"): blog page, topic archives, and single posts, with the HTML site's markup.
 *
 *   Blog page (Settings > Reading > Posts page)   home.php: intro, topic filter, search, featured post, cards,
 *                                                 "Most read", pagination, newsletter
 *   Topics (categories)                           category.php: the same list for one topic, /blog/topic/<slug>/
 *   Posts                                         single.php: hero, cover, table of contents, text, topics,
 *                                                 author, previous/next, related posts, newsletter
 *
 * Editing:
 *   - Blog page intro, featured post, and "Most read": fields on the Blog page (inc/blog-fields.php)
 *   - Per post: excerpt (the line under the title and on cards), cover (featured image, or a product screenshot),
 *     reading time, "Reviewed by", the box under the table of contents; topics = categories, order of the topic
 *     filter = "Order" on each category
 *   - Article components (key takeaways, notes, demo box, key numbers, before/after, questions) are blocks in the
 *     "Labora article" category (inc/blog-blocks.php); a list or table gets the Labora look with its
 *     "Checklist" / "Data table" style
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

const LABORA_BLOG_PER_PAGE = 12;

/* ---------- Query: 12 per page; the featured post is not repeated in the list ---------- */

add_action( 'pre_get_posts', function ( WP_Query $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_home() || $q->is_category() || $q->is_tag() ) {
		$q->set( 'posts_per_page', LABORA_BLOG_PER_PAGE );
		$q->set( 'ignore_sticky_posts', true );
	}
	if ( $q->is_home() && ( $featured = labora_blog_featured_id() ) ) {
		$q->set( 'post__not_in', array( $featured ) );
	}
} );

/** The Blog page (posts page) ID, or 0. */
function labora_blog_page_id(): int {
	return (int) get_option( 'page_for_posts' );
}

/** URL of the blog. */
function labora_blog_url(): string {
	$id = labora_blog_page_id();
	return $id ? (string) get_permalink( $id ) : home_url( '/' );
}

/** A field of the Blog page, or $default when empty. */
function labora_blog_field( string $name, $default = '' ) {
	$id    = labora_blog_page_id();
	$value = ( $id && function_exists( 'get_field' ) ) ? get_field( $name, $id ) : null;
	return ( null === $value || '' === $value || false === $value || array() === $value ) ? $default : $value;
}

/** The featured post on the blog page: the one chosen on the Blog page, or the newest. */
function labora_blog_featured_id(): int {
	static $id = null;
	if ( null === $id ) {
		$chosen = labora_blog_field( 'featured_post', 0 );
		$chosen = $chosen instanceof WP_Post ? $chosen->ID : (int) $chosen;
		if ( $chosen && 'publish' === get_post_status( $chosen ) ) {
			$id = $chosen;
		} else {
			$latest = get_posts( array( 'numberposts' => 1, 'fields' => 'ids', 'ignore_sticky_posts' => true, 'suppress_filters' => false ) );
			$id     = $latest ? (int) $latest[0] : 0;
		}
	}
	return $id;
}

/* ---------- Covers: the featured image, or a product screenshot from the theme ---------- */

/** Product screenshots that can be a post cover: file name => label. */
function labora_cover_screens(): array {
	return array(
		'dashboard'                   => 'Dashboard',
		'turnaround-tracking'         => 'Turnaround tracking',
		'sample-tracking'             => 'Sample tracking',
		'home-collection'             => 'Home collection',
		'pathology-imaging-reporting' => 'Pathology and imaging reporting',
		'business-insights'           => 'Business insights',
		'centers-overview'            => 'Centers overview',
		'patient-case'                => 'Patient case',
		'roles-access'                => 'Roles and access',
	);
}

/** A screenshot <img> with the same sizes as the HTML site (480 to 1200 wide). */
function labora_screen_img( string $name, string $sizes, bool $eager = false, string $alt = '' ): string {
	$srcset = implode( ', ', array_map( fn( $w ) => labora_asset( "img/screens/{$name}-{$w}.webp" ) . " {$w}w", array( 480, 640, 800, 1200 ) ) );
	return sprintf(
		'<img src="%s" srcset="%s" sizes="%s" width="800" height="690" alt="%s" %s>',
		esc_url( labora_asset( "img/screens/{$name}-800.webp" ) ),
		esc_attr( $srcset ),
		esc_attr( $sizes ),
		esc_attr( $alt ),
		$eager ? 'fetchpriority="high" decoding="async"' : 'loading="lazy" decoding="async"'
	);
}

/** The cover of a post: <div class="cover">image</div>. */
function labora_post_cover( $post, string $sizes, bool $eager = false ): string {
	$post  = get_post( $post );
	$right = (bool) get_post_meta( $post->ID, 'cover_right', true );
	if ( has_post_thumbnail( $post ) ) {
		$img = get_the_post_thumbnail( $post, 'large', array_filter( array(
			'alt'           => '',
			'sizes'         => $sizes,
			'loading'       => $eager ? false : 'lazy',
			'fetchpriority' => $eager ? 'high' : false,
			'decoding'      => 'async',
		) ) );
	} else {
		$screen = (string) get_post_meta( $post->ID, 'cover_screen', true );
		$img    = labora_screen_img( isset( labora_cover_screens()[ $screen ] ) ? $screen : 'dashboard', $sizes, $eager );
	}
	return '<div class="cover' . ( $right ? ' cover--right' : '' ) . '">' . $img . '</div>';
}

/** Image URL for sharing a post: featured image, else its share image file, else the screenshot. */
function labora_post_share_image( $post ): string {
	$post = get_post( $post );
	if ( has_post_thumbnail( $post ) ) {
		return (string) get_the_post_thumbnail_url( $post, 'large' );
	}
	$og = 'img/og/' . $post->post_name . '.jpg';
	if ( file_exists( get_theme_file_path( 'assets/' . $og ) ) ) {
		return labora_asset( $og );
	}
	$screen = (string) get_post_meta( $post->ID, 'cover_screen', true );
	return labora_asset( 'img/screens/' . ( isset( labora_cover_screens()[ $screen ] ) ? $screen : 'dashboard' ) . '-1200.webp' );
}

/* ---------- Authors ---------- */

/** The author's avatar: the Labora logo for team accounts ("Show the Labora logo" on the user), else their photo. */
function labora_author_avatar( int $user_id, int $size = 40 ): string {
	if ( get_user_meta( $user_id, 'labora_logo_avatar', true ) ) {
		return '<span class="avatar" aria-hidden="true"><svg><use href="#logo-mark"/></svg></span>';
	}
	return '<span class="avatar" aria-hidden="true">' . get_avatar( $user_id, $size * 2, '', '', array( 'width' => $size, 'height' => $size ) ) . '</span>';
}

/** Words in a post, counted from its rendered blocks (article blocks keep their text in block fields). */
function labora_post_words( $post = null ): int {
	static $cache = array();
	$post = get_post( $post );
	if ( ! isset( $cache[ $post->ID ] ) ) {
		$cache[ $post->ID ] = str_word_count( wp_strip_all_tags( do_blocks( strip_shortcodes( (string) $post->post_content ) ) ) );
	}
	return $cache[ $post->ID ];
}

/** "9 min read": the post's own value, or about 225 words a minute. */
function labora_read_minutes( $post = null ): int {
	$post  = get_post( $post );
	$given = (int) get_post_meta( $post->ID, 'read_minutes', true );
	return $given > 0 ? $given : max( 1, (int) round( labora_post_words( $post ) / 225 ) );
}

/* ---------- Topics ---------- */

/** Topics for the filter: categories with posts, by their "Order" field, then name. */
function labora_blog_topics(): array {
	$terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => true, 'exclude' => array( (int) get_option( 'default_category' ) ) ) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	usort( $terms, function ( $a, $b ) {
		$oa = (int) get_term_meta( $a->term_id, 'topic_order', true ) ?: 999;
		$ob = (int) get_term_meta( $b->term_id, 'topic_order', true ) ?: 999;
		return $oa <=> $ob ?: strcasecmp( $a->name, $b->name );
	} );
	return $terms;
}

/* ---------- Article text: heading anchors and the table of contents ---------- */

/**
 * The post's content, with an id on every <h2>, and the table of contents built from them.
 * Headings with the class "no-toc" (e.g. "Key takeaways") stay out of the list.
 *
 * @return array{0:string,1:array<int,array{0:string,1:string}>} [ html, [ [ id, text ], ... ] ]
 */
function labora_article_content(): array {
	$html = apply_filters( 'the_content', get_the_content() );
	$html = str_replace( ']]>', ']]&gt;', $html );
	$toc  = array();
	$used = array();
	$html = preg_replace_callback( '#<h2(\s[^>]*)?>(.*?)</h2>#is', function ( $m ) use ( &$toc, &$used ) {
		$attrs = $m[1] ?? '';
		$text  = trim( wp_strip_all_tags( $m[2] ) );
		if ( preg_match( '/\bid=["\']([^"\']+)["\']/', $attrs, $id ) ) {
			$id = $id[1];
		} else {
			$base = sanitize_title( $text ) ?: 'section';
			$id   = $base;
			for ( $i = 2; isset( $used[ $id ] ); $i++ ) {
				$id = "{$base}-{$i}";
			}
			$attrs .= ' id="' . esc_attr( $id ) . '"';
		}
		$used[ $id ] = true;
		if ( '' !== $text && ! preg_match( '/class=["\'][^"\']*\bno-toc\b/', $attrs ) ) {
			$toc[] = array( $id, $text );
		}
		return '<h2' . $attrs . '>' . $m[2] . '</h2>';
	}, $html );
	return array( $html, $toc );
}

/** Up to $n related posts: same primary topic first, then the newest. */
function labora_related_posts( $post, int $n = 3 ): array {
	$post = get_post( $post );
	$cat  = labora_primary_category( $post );
	$ids  = array();
	if ( $cat ) {
		$ids = get_posts( array( 'numberposts' => $n, 'fields' => 'ids', 'category' => $cat->term_id, 'post__not_in' => array( $post->ID ), 'ignore_sticky_posts' => true ) );
	}
	if ( count( $ids ) < $n ) {
		$ids = array_merge( $ids, get_posts( array( 'numberposts' => $n - count( $ids ), 'fields' => 'ids', 'post__not_in' => array_merge( array( $post->ID ), $ids ), 'ignore_sticky_posts' => true ) ) );
	}
	return array_map( 'get_post', $ids );
}

/* ---------- Block styles: the Labora look for core lists and tables ---------- */

add_action( 'init', function () {
	register_block_style( 'core/list', array( 'name' => 'checklist', 'label' => __( 'Checklist', 'labora' ) ) );
	register_block_style( 'core/table', array( 'name' => 'data-table', 'label' => __( 'Data table', 'labora' ) ) );
} );

/**
 * Give those blocks the HTML site's markup, so its CSS applies as it is:
 *   list "Checklist"   <ul class="checklist">
 *   table "Data table" <div class="table-wrap"><table class="data-table">, each cell labeled with its column
 *                      (on phones every row becomes a small card)
 */
add_filter( 'render_block', function ( string $html, array $block ) {
	$class = (string) ( $block['attrs']['className'] ?? '' );
	if ( 'core/list' === $block['blockName'] && str_contains( $class, 'is-style-checklist' ) ) {
		return preg_replace( '/<(ul|ol)\b([^>]*)class="/', '<$1$2class="checklist ', $html, 1 );
	}
	if ( 'core/table' === $block['blockName'] && str_contains( $class, 'is-style-data-table' ) ) {
		$heads = array();
		if ( preg_match( '#<thead>(.*?)</thead>#is', $html, $thead ) && preg_match_all( '#<th\b[^>]*>(.*?)</th>#is', $thead[1], $th ) ) {
			$heads = array_map( fn( $h ) => trim( wp_strip_all_tags( $h ) ), $th[1] );
		}
		$html = preg_replace( '#<figure\b[^>]*>#i', '<div class="table-wrap">', $html, 1 );
		$html = preg_replace( '#</figure>\s*$#i', '</div>', $html );
		$html = preg_replace( '#<table\b([^>]*)>#i', '<table class="data-table">', $html, 1 );
		$html = preg_replace_callback( '#<thead>.*?</thead>#is', fn( $m ) => preg_replace( '#<th(?=[\s>])(?![^>]*scope)#i', '<th scope="col"', $m[0] ), $html );
		$html = preg_replace_callback( '#<tbody>(.*?)</tbody>#is', function ( $tb ) use ( $heads ) {
			return '<tbody>' . preg_replace_callback( '#<tr>(.*?)</tr>#is', function ( $tr ) use ( $heads ) {
				$i = 0;
				return '<tr>' . preg_replace_callback( '#<td\b([^>]*)>#i', function ( $td ) use ( $heads, &$i ) {
					$label = $heads[ $i++ ] ?? '';
					return '' !== $label ? '<td data-label="' . esc_attr( $label ) . '"' . $td[1] . '>' : $td[0];
				}, $tr[1] ) . '</tr>';
			}, $tb[1] ) . '</tbody>';
		}, $html );
		$html = preg_replace( '#<figcaption\b[^>]*>(.*?)</figcaption>#is', '<p class="table-note">$1</p>', $html );
	}
	return $html;
}, 10, 2 );

/* ---------- Editor: the site's fonts and article styles while writing ---------- */

add_action( 'after_setup_theme', function () {
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/styles.min.css', 'assets/css/blog.min.css', 'assets/css/wp-blog.css', 'assets/css/editor.css' ) );
} );

/* ---------- Search engines ---------- */

/** Questions and answers in the post's "Questions and answers" blocks, for FAQPage structured data. */
function labora_post_faq( $post ): array {
	$post = get_post( $post );
	$out  = array();
	$walk = function ( array $blocks ) use ( &$walk, &$out ) {
		foreach ( $blocks as $b ) {
			if ( 'acf/labora-faq' === $b['blockName'] ) {
				$data = (array) ( $b['attrs']['data'] ?? array() );
				for ( $i = 0, $n = (int) ( $data['items'] ?? 0 ); $i < $n; $i++ ) {
					$q = trim( wp_strip_all_tags( (string) ( $data[ "items_{$i}_question" ] ?? '' ) ) );
					$a = trim( (string) ( $data[ "items_{$i}_answer" ] ?? '' ) );
					if ( '' !== $q && '' !== $a ) {
						$out[] = array( $q, $a );
					}
				}
			}
			if ( ! empty( $b['innerBlocks'] ) ) {
				$walk( $b['innerBlocks'] );
			}
		}
	};
	$walk( parse_blocks( (string) $post->post_content ) );
	return $out;
}

add_filter( 'wpseo_schema_graph', function ( $graph ) {
	if ( ! is_singular( 'post' ) ) {
		return $graph;
	}
	$post = get_queried_object();
	$faq  = labora_post_faq( $post );
	if ( $faq ) {
		$url     = (string) get_permalink( $post );
		$graph[] = array(
			'@type'      => 'FAQPage',
			'@id'        => $url . '#faq',
			'isPartOf'   => array( '@id' => $url ),
			'mainEntity' => array_map( fn( $qa ) => array(
				'@type'          => 'Question',
				'name'           => $qa[0],
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_kses( $qa[1], array( 'a' => array( 'href' => true ), 'strong' => array(), 'em' => array(), 'br' => array(), 'p' => array(), 'ul' => array(), 'ol' => array(), 'li' => array() ) ) ),
			), $faq ),
		);
	}
	return $graph;
} );

/**
 * Article data: team accounts ("Show the Labora logo" on the user) write as the organization, as on the HTML site;
 * the topic is the article section and the post's topics and tags are its keywords.
 */
add_filter( 'wpseo_schema_article', function ( $data ) {
	$post = get_post();
	if ( ! $post || 'post' !== $post->post_type ) {
		return $data;
	}
	if ( get_user_meta( (int) $post->post_author, 'labora_logo_avatar', true ) ) {
		$data['author'] = array( '@id' => home_url( '/#organization' ) );
	}
	$cat = labora_primary_category( $post );
	if ( $cat ) {
		$data['articleSection'] = array( $cat->name );
	}
	$data['wordCount'] = labora_post_words( $post );
	// Posts without a featured image or a picture in the text: the cover screenshot
	if ( empty( $data['image'] ) ) {
		$data['image'] = array( '@type' => 'ImageObject', 'url' => labora_post_share_image( $post ) );
	}
	$words = array_merge( wp_list_pluck( get_the_category( $post->ID ), 'name' ), wp_list_pluck( (array) get_the_tags( $post->ID ), 'name' ) );
	if ( $words ) {
		$data['keywords'] = array_values( array_unique( $words ) );
	}
	return $data;
} );

// No separate Person piece for team accounts: the organization is the author
add_filter( 'wpseo_schema_needs_author', function ( $needs ) {
	$post = get_post();
	return ( $post && is_singular( 'post' ) && get_user_meta( (int) $post->post_author, 'labora_logo_avatar', true ) ) ? false : $needs;
} );

// Share image for posts without a featured image: the post's share image file or its cover screenshot
add_action( 'wpseo_add_opengraph_images', function ( $images ) {
	if ( is_singular( 'post' ) && ! has_post_thumbnail( get_queried_object_id() ) ) {
		$images->add_image_by_url( labora_post_share_image( get_queried_object() ) );
	}
} );
