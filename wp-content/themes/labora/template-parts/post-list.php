<?php
/**
 * Post listing used by home.php, archive.php, search.php, and index.php: the HTML blog hub's hero, card grid, and
 * pagination. $args: label (eyebrow), title, lead, search (bool: show the search form).
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

$labora_args = wp_parse_args( $args ?? array(), array( 'label' => '', 'title' => '', 'lead' => '', 'search' => false ) );
global $wp_query;
?>
<main id="main" class="blog-main wp-list">
  <section class="blog-hero" aria-labelledby="list-title">
    <div class="container">
      <?php if ( $labora_args['label'] ) : ?><p class="eyebrow"><span class="dot" aria-hidden="true"></span><?php echo esc_html( $labora_args['label'] ); ?></p><?php endif; ?>
      <h1 id="list-title"><?php echo esc_html( $labora_args['title'] ); ?></h1>
      <?php if ( $labora_args['lead'] ) : ?><div class="lead"><?php echo wp_kses_post( wpautop( $labora_args['lead'] ) ); ?></div><?php endif; ?>
      <?php if ( $labora_args['search'] ) { get_search_form(); } ?>
    </div>
  </section>

  <div class="container">
    <?php if ( have_posts() ) : ?>
    <div class="posts-head">
      <h2 class="sr-only"><?php esc_html_e( 'Articles', 'labora' ); ?></h2>
      <span class="count">
        <?php
        /* translators: %s: number of posts */
        echo esc_html( sprintf( _n( '%s article', '%s articles', (int) $wp_query->found_posts, 'labora' ), number_format_i18n( (int) $wp_query->found_posts ) ) );
        ?>
      </span>
    </div>
    <div class="post-grid">
      <?php
      while ( have_posts() ) :
        the_post();
        labora_post_card();
      endwhile;
      ?>
    </div>
    <?php labora_pagination(); ?>
    <?php else : ?>
    <div class="wp-empty">
      <h2><?php esc_html_e( 'Nothing here yet', 'labora' ); ?></h2>
      <p><?php echo is_search() ? esc_html__( 'No articles match your search. Try a different word.', 'labora' ) : esc_html__( 'There are no articles to show.', 'labora' ); ?></p>
    </div>
    <?php endif; ?>
  </div>
</main>
