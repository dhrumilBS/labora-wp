<?php
/**
 * Default page template, in the style of the HTML site's legal pages (hero + readable text column).
 * Used by any page that has no template of its own; page-specific templates are added as pages are approved.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_template_assets( 'pages' );
get_header();

while ( have_posts() ) :
	the_post();
	$labora_parent = wp_get_post_parent_id( get_the_ID() );
	?>
<main id="main" class="page-main wp-page">
  <section class="lg-hero" aria-labelledby="page-title">
    <div class="container">
      <?php if ( $labora_parent ) : ?><p class="eyebrow"><span class="dot" aria-hidden="true"></span><?php echo esc_html( get_the_title( $labora_parent ) ); ?></p><?php endif; ?>
      <h1 id="page-title"><?php the_title(); ?></h1>
      <?php if ( has_excerpt() ) : ?><p class="lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
    </div>
  </section>
  <div class="container wp-page-body">
    <div class="lg-prose">
      <?php the_content(); ?>
      <?php wp_link_pages( array( 'before' => '<nav class="pager" aria-label="' . esc_attr__( 'Page sections', 'labora' ) . '">', 'after' => '</nav>' ) ); ?>
    </div>
  </div>
</main>
	<?php
endwhile;

get_footer();
