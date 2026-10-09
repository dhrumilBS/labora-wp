<?php
/**
 * Blog page ("The Labora Journal"), the HTML site's /blog/ hub: intro, topic filter and search, featured post,
 * latest articles with "Most read", pagination, newsletter. Texts and picks: fields on the Blog page
 * (inc/blog-fields.php); see inc/blog.php.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_template_assets( 'blog' );
get_header();

global $wp_query;
$labora_paged    = max( 1, (int) get_query_var( 'paged' ) );
$labora_featured = 1 === $labora_paged ? labora_blog_featured_id() : 0;
$labora_single   = (int) $wp_query->max_num_pages <= 1;

// "Most read": the posts chosen on the Blog page, or the five newest
$labora_most = array_filter( array_map( 'get_post', (array) labora_blog_field( 'most_read', array() ) ) );
if ( ! $labora_most ) {
	$labora_most = get_posts( array( 'numberposts' => 5, 'ignore_sticky_posts' => true ) );
}
$labora_cards = $wp_query->posts;
?>
<main id="main" class="blog-main">
<section class="blog-hero" aria-labelledby="blog-title">
  <div class="container">
    <p class="eyebrow"><span class="dot" aria-hidden="true"></span><?php echo esc_html( labora_blog_field( 'hub_eyebrow', 'The Labora Journal' ) ); ?></p>
    <h1 id="blog-title"><?php echo esc_html( labora_blog_field( 'hub_title', 'Ideas for running a faster, calmer diagnostic lab' ) ); ?></h1>
    <p class="lead"><?php echo esc_html( labora_blog_field( 'hub_lead', 'Practical guides on turnaround time, sample tracking, reporting, and growth, written for lab owners, managers, and reporting doctors.' ) ); ?></p>
<?php get_template_part( 'template-parts/blog/tools', null, array( 'mode' => $labora_single ? 'filter' : 'links' ) ); ?>
  </div>
</section>

<div class="container">
<?php if ( $labora_featured ) :
	$labora_f   = get_post( $labora_featured );
	$labora_cat = labora_primary_category( $labora_f );
	?>
  <a class="featured reveal" href="<?php echo esc_url( get_permalink( $labora_f ) ); ?>" data-featured>
    <?php echo labora_post_cover( $labora_f, '(max-width: 1024px) calc(100vw - 40px), 640px', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
    <div class="featured-body">
      <div class="chips"><span class="chip badge-new"><?php esc_html_e( 'Featured', 'labora' ); ?></span><?php if ( $labora_cat ) : ?><span class="chip"><?php echo esc_html( $labora_cat->name ); ?></span><?php endif; ?></div>
      <h2><?php echo esc_html( get_the_title( $labora_f ) ); ?></h2>
      <p><?php echo esc_html( get_the_excerpt( $labora_f ) ); ?></p>
      <?php echo labora_byline( $labora_f ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
      <span class="link-arrow"><?php esc_html_e( 'Read the guide', 'labora' ); ?> <?php echo labora_icon( 'arrow', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
    </div>
  </a>
<?php endif; ?>

  <section aria-labelledby="latest-title">
    <div class="posts-head">
      <h2 id="latest-title"><?php esc_html_e( 'Latest articles', 'labora' ); ?></h2>
      <?php /* translators: %d: number of articles */ ?>
      <span class="count" data-count aria-live="polite"><?php echo esc_html( sprintf( _n( '%d article', '%d articles', count( $labora_cards ), 'labora' ), count( $labora_cards ) ) ); ?></span>
    </div>
<?php if ( $labora_cards ) : ?>
    <div class="post-grid" data-posts>
<?php
	foreach ( $labora_cards as $labora_i => $labora_post ) {
		echo '        ';
		labora_post_card( $labora_post );
		echo "\n";
		// "Most read" sits after the first two cards, as on the HTML site (first page only)
		if ( 1 === $labora_i && 1 === $labora_paged && $labora_most ) :
			?>
        <aside class="most-read" aria-labelledby="mr-title">
          <h2 id="mr-title"><?php esc_html_e( 'Most read this month', 'labora' ); ?></h2>
          <ol>
<?php foreach ( $labora_most as $labora_m ) : $labora_mc = labora_primary_category( $labora_m ); ?>
          <li><a href="<?php echo esc_url( get_permalink( $labora_m ) ); ?>"><span><?php echo esc_html( get_the_title( $labora_m ) ); ?><small><?php echo esc_html( ( $labora_mc ? $labora_mc->name . ' · ' : '' ) . labora_read_minutes( $labora_m ) . ' min' ); ?></small></span></a></li>
<?php endforeach; ?>
          </ol>
        </aside>
<?php
		endif;
	}
	?>
    </div>
    <div class="empty" data-empty>
      <h3><?php esc_html_e( 'No articles match that search', 'labora' ); ?></h3>
      <p><?php esc_html_e( 'Try another word, or browse every topic.', 'labora' ); ?></p>
      <button class="btn btn--ghost" type="button" data-reset><?php esc_html_e( 'Show all articles', 'labora' ); ?></button>
    </div>
    <?php labora_pagination(); ?>
<?php else : ?>
    <div class="wp-empty"><h2><?php esc_html_e( 'No articles yet', 'labora' ); ?></h2><p><?php esc_html_e( 'The first guides are on their way.', 'labora' ); ?></p></div>
<?php endif; ?>
  </section>

  <?php get_template_part( 'template-parts/blog/newsletter' ); ?>
</div>
</main>
<?php
get_footer();
