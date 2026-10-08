<?php
/**
 * Template tags for the WordPress templates (posts, archives, search, pages), built from the HTML site's blog
 * markup (.post, .cover, .meta, .byline, .pager) so they match its design.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'labora_reading_time' ) ) :
/** "7 min read" for a post (about 225 words a minute). */
function labora_reading_time( $post = null ): string {
	$words = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $post ) ) );
	/* translators: %d: minutes */
	return sprintf( __( '%d min read', 'labora' ), max( 1, (int) round( $words / 225 ) ) );
}
endif;

if ( ! function_exists( 'labora_byline' ) ) :
/** Author, date, and reading time, as on the HTML blog. */
function labora_byline( $post = null ): string {
	$post = get_post( $post );
	return sprintf(
		'<span class="byline"><span class="avatar" aria-hidden="true"><svg><use href="#logo-mark"/></svg></span><span><strong>%1$s</strong><span><time datetime="%2$s">%3$s</time> &middot; %4$s</span></span></span>',
		esc_html( get_the_author_meta( 'display_name', (int) $post->post_author ) ?: 'Labora Team' ),
		esc_attr( get_the_date( 'c', $post ) ),
		esc_html( get_the_date( '', $post ) ),
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
/** A post card for grids (home, archive, search). */
function labora_post_card( $post = null ): void {
	$post = get_post( $post );
	$cat  = labora_primary_category( $post );
	?>
	<a class="post reveal" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
		<div class="cover"><?php
		if ( has_post_thumbnail( $post ) ) {
			echo get_the_post_thumbnail( $post, 'medium_large', array( 'alt' => '', 'loading' => 'lazy', 'sizes' => '(max-width: 720px) calc(100vw - 40px), (max-width: 1024px) 46vw, 380px' ) );
		}
		?></div>
		<span class="meta"><?php if ( $cat ) : ?><span><?php echo esc_html( $cat->name ); ?></span><span class="dot-sep" aria-hidden="true"></span><?php endif; ?><span><?php echo esc_html( labora_reading_time( $post ) ); ?></span></span>
		<h3><?php echo esc_html( get_the_title( $post ) ); ?></h3>
		<?php if ( has_excerpt( $post ) || $post->post_content ) : ?><p><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 28 ) ); ?></p><?php endif; ?>
		<?php echo labora_byline( $post ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</a>
	<?php
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
}
endif;
