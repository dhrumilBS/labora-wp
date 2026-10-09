<?php
/**
 * Single post: the HTML site's article layout.
 *   hero       breadcrumbs, topic, title, excerpt, byline (with "Updated"), "Reviewed by", share, cover
 *   body       table of contents (from the <h2> headings) with a demo box, then the text and article blocks
 *   footer     topics, author box, previous / next
 *   after      related posts ("Keep reading"), newsletter
 * Fields: "Article" box on the post (inc/blog-fields.php). Structured data: Yoast + inc/blog.php.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_template_assets( 'blog' );
get_header();

while ( have_posts() ) :
	the_post();
	$labora_post = get_post();
	$labora_cat  = labora_primary_category();
	$labora_url  = (string) get_permalink();
	list( $labora_html, $labora_toc ) = labora_article_content();
	// Topics: the main one first, as on the HTML site
	$labora_cats = get_the_category();
	usort( $labora_cats, fn( $a, $b ) => (int) ( $labora_cat && $b->term_id === $labora_cat->term_id ) <=> (int) ( $labora_cat && $a->term_id === $labora_cat->term_id ) );
	$labora_tags = (array) get_the_tags();
	$labora_user = (int) $labora_post->post_author;
	$labora_prev = get_previous_post();
	$labora_next = get_next_post();
	$labora_rel  = labora_related_posts( $labora_post, 3 );
	?>
<main id="main" class="blog-main">
<div class="read-progress" aria-hidden="true"><span></span></div>

<?php // No post_class(): its "post" class is the blog card style in blog.css ?>
<article id="post-<?php the_ID(); ?>" data-article>
  <header class="article-hero">
    <div class="container">
      <div class="wrap">
        <nav class="crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'labora' ); ?>">
          <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'labora' ); ?></a><?php echo labora_icon( 'chev' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
          <a href="<?php echo esc_url( labora_blog_url() ); ?>"><?php esc_html_e( 'Blog', 'labora' ); ?></a><?php echo labora_icon( 'chev' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
          <span aria-current="page"><?php echo esc_html( $labora_cat ? $labora_cat->name : get_the_title() ); ?></span>
        </nav>
        <?php if ( $labora_cat ) : ?><span class="chip"><?php echo esc_html( $labora_cat->name ); ?></span><?php endif; ?>
        <h1 style="margin-top:1rem"><?php the_title(); ?></h1>
        <?php if ( has_excerpt() ) : ?><p class="dek"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
        <?php if ( $labora_reviewer = trim( (string) get_post_meta( get_the_ID(), 'reviewed_by', true ) ) ) : ?>
        <p class="reviewed"><?php echo labora_icon( 'check', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php /* translators: %s: reviewer */ echo esc_html( sprintf( __( 'Reviewed by %s', 'labora' ), $labora_reviewer ) ); ?></p>
        <?php endif; ?>
        <div class="article-bar">
          <?php echo labora_byline( $labora_post, true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
          <div class="share">
            <span class="share-label"><?php esc_html_e( 'Share', 'labora' ); ?></span>
            <a href="<?php echo esc_url( 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $labora_url ) ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Share on LinkedIn', 'labora' ); ?>"><svg class="brand" aria-hidden="true"><use href="#b-linkedin"/></svg></a>
            <a href="<?php echo esc_url( 'https://x.com/intent/post?url=' . rawurlencode( $labora_url ) ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_attr_e( 'Share on X', 'labora' ); ?>"><svg class="brand" aria-hidden="true"><use href="#b-x"/></svg></a>
            <button type="button" data-copy-link aria-label="<?php esc_attr_e( 'Copy link', 'labora' ); ?>"><?php echo labora_icon( 'link' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
          </div>
        </div>
      </div>
      <div class="article-cover">
        <?php echo labora_post_cover( $labora_post, '(max-width: 1260px) calc(100vw - 40px), 1200px', true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
      </div>
    </div>
  </header>

  <div class="container article-layout<?php echo $labora_toc ? '' : ' article-layout--no-toc'; ?>">
<?php if ( $labora_toc ) : ?>
    <details class="toc" open>
      <summary><?php esc_html_e( 'On this page', 'labora' ); ?> <?php echo labora_icon( 'chev', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></summary>
      <ol>
<?php foreach ( $labora_toc as list( $labora_id, $labora_text ) ) : ?>
          <li><a href="#<?php echo esc_attr( $labora_id ); ?>"><?php echo esc_html( $labora_text ); ?></a></li>
<?php endforeach; ?>
      </ol>
      <div class="toc-cta">
        <strong><?php echo esc_html( trim( (string) get_post_meta( get_the_ID(), 'toc_cta_title', true ) ) ?: __( 'See Labora live', 'labora' ) ); ?></strong>
        <?php echo esc_html( trim( (string) get_post_meta( get_the_ID(), 'toc_cta_text', true ) ) ?: __( 'A 20-minute walkthrough, set up with your own tests.', 'labora' ) ); ?>
        <a class="link-arrow" href="<?php echo esc_url( labora_setting_url( 'demo_url' ) ); ?>"><?php echo esc_html( labora_setting( 'demo_label' ) ); ?> <?php echo labora_icon( 'arrow', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
      </div>
    </details>
<?php endif; ?>

    <div class="prose">
      <?php echo $labora_html; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content ?>
      <?php wp_link_pages( array( 'before' => '<nav class="pager" aria-label="' . esc_attr__( 'Article pages', 'labora' ) . '">', 'after' => '</nav>' ) ); ?>

      <footer class="article-foot">
<?php if ( $labora_cats || array_filter( $labora_tags ) ) : ?>
        <div class="tags"><span><?php esc_html_e( 'Topics', 'labora' ); ?></span><?php
		foreach ( array_merge( $labora_cats, array_filter( $labora_tags ) ) as $labora_term ) {
			if ( (int) get_option( 'default_category' ) === $labora_term->term_id ) {
				continue;
			}
			printf( '<a class="chip chip--soft" href="%s">%s</a>', esc_url( get_term_link( $labora_term ) ), esc_html( $labora_term->name ) );
		}
		?></div>
<?php endif; ?>
        <div class="author-card">
          <?php echo labora_author_avatar( $labora_user, 56 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
          <div>
            <h3><?php echo esc_html( get_the_author_meta( 'display_name', $labora_user ) ); ?></h3>
            <?php if ( $labora_role = (string) get_user_meta( $labora_user, 'labora_role', true ) ) : ?><div class="role"><?php echo esc_html( $labora_role ); ?></div><?php endif; ?>
            <?php if ( $labora_bio = (string) get_the_author_meta( 'description', $labora_user ) ) : ?><p><?php echo esc_html( $labora_bio ); ?></p><?php endif; ?>
          </div>
        </div>
<?php if ( $labora_prev || $labora_next ) : ?>
        <nav class="post-nav" aria-label="<?php esc_attr_e( 'More articles', 'labora' ); ?>">
          <?php if ( $labora_prev ) : ?><a href="<?php echo esc_url( get_permalink( $labora_prev ) ); ?>"><small><?php esc_html_e( 'Previous', 'labora' ); ?></small><?php echo esc_html( get_the_title( $labora_prev ) ); ?></a><?php endif; ?>
          <?php if ( $labora_next ) : ?><a href="<?php echo esc_url( get_permalink( $labora_next ) ); ?>"><small><?php esc_html_e( 'Next', 'labora' ); ?></small><?php echo esc_html( get_the_title( $labora_next ) ); ?></a><?php endif; ?>
        </nav>
<?php endif; ?>
      </footer>
    </div>
  </div>
</article>

<div class="container">
<?php if ( $labora_rel ) : ?>
  <section class="related" aria-labelledby="related-title">
    <div class="posts-head"><h2 id="related-title"><?php esc_html_e( 'Keep reading', 'labora' ); ?></h2><a class="link-arrow" href="<?php echo esc_url( labora_blog_url() ); ?>"><?php esc_html_e( 'All articles', 'labora' ); ?> <?php echo labora_icon( 'arrow', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></div>
    <div class="post-grid">
<?php foreach ( $labora_rel as $labora_r ) { echo '        '; labora_post_card( $labora_r ); echo "\n"; } ?>
    </div>
  </section>
<?php endif; ?>

  <?php get_template_part( 'template-parts/blog/newsletter' ); ?>
</div>

<div class="toast" role="status" aria-live="polite"></div>
</main>
	<?php
endwhile;

get_footer();
