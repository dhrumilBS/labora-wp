<?php
/**
 * Single post, in the style of the HTML site's blog article (breadcrumbs, topic, title, standfirst, byline, cover,
 * then the article text). The full article layout with table of contents is added when the blog is converted.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_template_assets( 'blog' );
get_header();

$labora_blog = get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/' );

while ( have_posts() ) :
	the_post();
	$labora_cat = labora_primary_category();
	?>
<main id="main" class="blog-main wp-single">
  <?php // No post_class(): its "post" class is the blog card style (two-line clamp) in blog.css ?>
  <article id="post-<?php the_ID(); ?>" class="wp-article">
    <header class="article-hero">
      <div class="container">
        <div class="wrap">
          <nav class="crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'labora' ); ?>">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'labora' ); ?></a><?php echo labora_icon( 'chev' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <a href="<?php echo esc_url( $labora_blog ); ?>"><?php esc_html_e( 'Blog', 'labora' ); ?></a><?php echo labora_icon( 'chev' ); // phpcs:ignore ?>
            <span aria-current="page"><?php echo esc_html( $labora_cat ? $labora_cat->name : get_the_title() ); ?></span>
          </nav>
          <?php if ( $labora_cat ) : ?><a class="chip" href="<?php echo esc_url( get_term_link( $labora_cat ) ); ?>"><?php echo esc_html( $labora_cat->name ); ?></a><?php endif; ?>
          <h1 style="margin-top:1rem"><?php the_title(); ?></h1>
          <?php if ( has_excerpt() ) : ?><p class="dek"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
          <div class="article-bar"><?php echo labora_byline(); // phpcs:ignore ?></div>
        </div>
        <?php if ( has_post_thumbnail() ) : ?>
        <div class="article-cover"><div class="cover"><?php the_post_thumbnail( 'large', array( 'alt' => '', 'fetchpriority' => 'high', 'sizes' => '(max-width: 1240px) calc(100vw - 40px), 1200px' ) ); ?></div></div>
        <?php endif; ?>
      </div>
    </header>

    <div class="container">
      <div class="prose">
        <?php the_content(); ?>
        <?php wp_link_pages(); ?>
      <?php
        $labora_tags = get_the_tags();
        if ( $labora_tags ) :
		?>
        <footer class="article-foot">
          <div class="tags"><span><?php esc_html_e( 'Topics', 'labora' ); ?></span><?php foreach ( $labora_tags as $labora_tag ) : ?><a class="chip" href="<?php echo esc_url( get_term_link( $labora_tag ) ); ?>"><?php echo esc_html( $labora_tag->name ); ?></a><?php endforeach; ?></div>
        </footer>
        <?php endif; ?>
      </div>
    </div>
  </article>
</main>
	<?php
endwhile;

get_footer();
