<?php
/**
 * Template tags for the WordPress templates (posts, archives, search, pages), built from the HTML site's blog
 * markup (.post, .cover, .meta, .byline, .pager) so they match its design.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'labora_reading_time' ) ) :
/** "7 min read" (the post's reading time field, or counted from the text; see labora_read_minutes()). */
function labora_reading_time( $post = null ): string {
	/* translators: %d: minutes */
	return sprintf( __( '%d min read', 'labora' ), labora_read_minutes( $post ) );
}
endif;

if ( ! function_exists( 'labora_byline' ) ) :
/**
 * Author, date, and reading time, as on the HTML blog. With $updated, a post edited more than a day after it was
 * published also shows "Updated <date>".
 */
function labora_byline( $post = null, bool $updated = false ): string {
	$post = get_post( $post );
	$user = (int) $post->post_author;
	$mod  = '';
	if ( $updated && get_post_modified_time( 'U', true, $post ) - get_post_time( 'U', true, $post ) > DAY_IN_SECONDS ) {
		/* translators: %s: date */
		$mod = sprintf( ' &middot; <time datetime="%1$s">%2$s</time>', esc_attr( get_the_modified_date( 'c', $post ) ), esc_html( sprintf( __( 'Updated %s', 'labora' ), get_the_modified_date( '', $post ) ) ) );
	}
	return sprintf(
		'<span class="byline">%1$s<span><strong>%2$s</strong><span><time datetime="%3$s">%4$s</time>%5$s &middot; %6$s</span></span></span>',
		labora_author_avatar( $user ),
		esc_html( get_the_author_meta( 'display_name', $user ) ?: 'Labora Team' ),
		esc_attr( get_the_date( 'Y-m-d', $post ) ),
		esc_html( get_the_date( 'M j, Y', $post ) ),
		$mod,
		esc_html( labora_reading_time( $post ) )
	);
}
endif;

if ( ! function_exists( 'labora_primary_category' ) ) :
/** The post's main category (Yoast's primary category when set), or null. */
function labora_primary_category( $post = null ): ?WP_Term {
	$post = get_post( $post );
	$id   = (int) get_post_meta( $post->ID, '_yoast_wpseo_primary_category', true );
	$term = $id ? get_term( $id, 'category' ) : null;
	if ( ! $term instanceof WP_Term ) {
		$cats = get_the_category( $post->ID );
		$term = $cats ? $cats[0] : null;
	}
	return ( $term && 'uncategorized' !== $term->slug ) ? $term : null;
}
endif;

if ( ! function_exists( 'labora_post_card' ) ) :
/** A post card (blog page, topics, search, related posts): the HTML blog's .post. */
function labora_post_card( $post = null, string $sizes = '(max-width: 720px) calc(100vw - 40px), (max-width: 1024px) 46vw, 380px', bool $eager = false ): void {
	$post = get_post( $post );
	$cat  = labora_primary_category( $post );
	printf(
		'<a class="post reveal" href="%1$s" data-topic="%2$s">
          %3$s
          <span class="meta">%4$s<span>%5$s</span></span>
          <h3>%6$s</h3>
          <p>%7$s</p>
          %8$s
        </a>',
		esc_url( get_permalink( $post ) ),
		esc_attr( $cat ? $cat->slug : '' ),
		labora_post_cover( $post, $sizes, $eager ), // phpcs:ignore WordPress.Security.EscapeOutput
		$cat ? '<span>' . esc_html( $cat->name ) . '</span><span class="dot-sep" aria-hidden="true"></span>' : '',
		esc_html( labora_reading_time( $post ) ),
		esc_html( get_the_title( $post ) ),
		esc_html( has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( get_the_excerpt( $post ), 28 ) ),
		labora_byline( $post ) // phpcs:ignore WordPress.Security.EscapeOutput
	);
}
endif;

if ( ! function_exists( 'labora_pagination' ) ) :
/** Numbered pagination in the blog's .pager style. */
function labora_pagination(): void {
	$links = paginate_links( array(
		'type'      => 'array',
		'mid_size'  => 1,
		'prev_text' => labora_icon( 'chev', 'icon icon-sm' ) . '<span class="sr-only">' . esc_html__( 'Previous page', 'labora' ) . '</span>',
		'next_text' => '<span class="sr-only">' . esc_html__( 'Next page', 'labora' ) . '</span>' . labora_icon( 'chev', 'icon icon-sm' ),
	) );
	if ( $links ) {
		echo '<nav class="pager" aria-label="' . esc_attr__( 'Pages', 'labora' ) . '">' . implode( '', array_map( fn( $l ) => str_replace( 'current', 'current" aria-current="page', $l ), $links ) ) . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
endif;

if ( ! function_exists( 'labora_template_assets' ) ) :
/** CSS/JS for a WordPress template: 'pages' or 'blog' bundle, plus the small WordPress-only stylesheet. */
function labora_template_assets( string $bundle ): void {
	labora_enqueue_bundle( $bundle );
	wp_enqueue_style( 'labora-wp', labora_asset( 'css/wp.css' ), array( 'labora-' . $bundle ), labora_asset_version( 'css/wp.css' ) );
	if ( 'blog' === $bundle ) {
		wp_enqueue_style( 'labora-wp-blog', labora_asset( 'css/wp-blog.css' ), array( 'labora-wp' ), labora_asset_version( 'css/wp-blog.css' ) );
	}
}
endif;
