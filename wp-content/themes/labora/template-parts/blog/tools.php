<?php
/**
 * Topic filter and search under the blog and topic headings.
 *
 * On a blog page whose posts all fit on one page, the topics are buttons that filter the cards at once
 * (assets/js/blog.js, as on the HTML site). Otherwise (more pages, or a topic page) they are links to the topic
 * pages. The search filters the cards while typing; Enter runs a full search of the blog.
 *
 * @package Labora
 * @var array $args { mode: 'filter'|'links', current: term ID or 0 }
 */

defined( 'ABSPATH' ) || exit;

$labora_mode    = $args['mode'] ?? 'links';
$labora_current = (int) ( $args['current'] ?? 0 );
?>
    <div class="blog-tools">
      <div class="topics" role="group" aria-label="<?php esc_attr_e( 'Filter by topic', 'labora' ); ?>">
<?php if ( 'filter' === $labora_mode ) : ?>
          <button class="topic" type="button" data-filter="all" aria-pressed="true"><?php esc_html_e( 'All', 'labora' ); ?></button>
<?php foreach ( labora_blog_topics() as $labora_t ) : ?>
          <button class="topic" type="button" data-filter="<?php echo esc_attr( $labora_t->slug ); ?>" aria-pressed="false"><?php echo esc_html( $labora_t->name ); ?></button>
<?php endforeach; ?>
<?php else : ?>
          <a class="topic" href="<?php echo esc_url( labora_blog_url() ); ?>"<?php echo $labora_current ? '' : ' aria-current="page"'; ?>><?php esc_html_e( 'All', 'labora' ); ?></a>
<?php foreach ( labora_blog_topics() as $labora_t ) : ?>
          <a class="topic" href="<?php echo esc_url( get_term_link( $labora_t ) ); ?>"<?php echo $labora_current === $labora_t->term_id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $labora_t->name ); ?></a>
<?php endforeach; ?>
<?php endif; ?>
      </div>
      <form class="search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <label class="sr-only" for="blog-search"><?php esc_html_e( 'Search articles', 'labora' ); ?></label>
        <?php echo labora_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
        <input id="blog-search" name="s" type="search" placeholder="<?php esc_attr_e( 'Search articles', 'labora' ); ?>" autocomplete="off" value="<?php echo esc_attr( get_search_query() ); ?>">
        <input type="hidden" name="post_type" value="post">
        <kbd aria-hidden="true">/</kbd>
      </form>
    </div>
