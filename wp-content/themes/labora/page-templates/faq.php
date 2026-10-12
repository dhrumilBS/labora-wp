<?php
/**
 * Template Name: FAQ page
 *
 * The HTML site's /faq/ page: intro with search, categories, questions grouped by topic (with "Copy link" and
 * "Was this helpful?"), help cards. Texts: fields on the page (inc/page-fields.php); questions: FAQ entries with
 * "FAQ page" ticked (inc/faq.php); search, categories, and links to a question: assets/js/pages.js.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_enqueue_bundle( 'pages' );
get_header();

$labora_groups = labora_faq_groups( 'faq' );
$labora_count  = array_sum( array_map( fn( $g ) => count( $g['items'] ), $labora_groups ) );
$labora_icon   = fn( $term ) => ( $term->term_id ? get_term_meta( $term->term_id, 'topic_icon', true ) : '' ) ?: 'message';
?>
<main id="main" class="faq-main">
<section class="faq-hero" aria-labelledby="faq-title">
  <div class="container">
    <p class="eyebrow"><span class="dot" aria-hidden="true"></span><?php echo esc_html( labora_field( 'hero_eyebrow' ) ); ?></p>
    <h1 id="faq-title"><?php echo esc_html( labora_field( 'hero_title' ) ?: get_the_title() ); ?></h1>
    <p class="lead"><?php echo esc_html( labora_field( 'hero_lead' ) ); ?></p>
    <div class="faq-search" role="search">
      <label class="sr-only" for="faq-search"><?php esc_html_e( 'Search questions', 'labora' ); ?></label>
      <?php echo labora_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

      <?php /* translators: %d: number of questions */ ?>
      <input id="faq-search" type="search" placeholder="<?php echo esc_attr( sprintf( __( 'Search %d questions', 'labora' ), $labora_count ) ); ?>" autocomplete="off">
    </div>
    <p class="faq-results" aria-live="polite"></p>
  </div>
</section>

<div class="container faq-layout" data-faq>
  <nav class="faq-cats" aria-label="<?php esc_attr_e( 'Question categories', 'labora' ); ?>">
    <h2><?php esc_html_e( 'Categories', 'labora' ); ?></h2>
    <ul><?php foreach ( $labora_groups as $labora_g ) : ?><li><a href="#<?php echo esc_attr( $labora_g['term']->slug ); ?>"><span><?php echo labora_icon( $labora_icon( $labora_g['term'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $labora_g['term']->name ); ?></span><span class="n"><?php echo count( $labora_g['items'] ); ?></span></a></li><?php endforeach; ?></ul>
    <div class="help"><strong><?php echo esc_html( labora_field( 'help_title' ) ); ?></strong><?php echo esc_html( labora_field( 'help_text' ) ); ?><br><a class="link-arrow" href="<?php echo esc_url( labora_link( (string) labora_field( 'help_url' ) ) ); ?>"><?php echo esc_html( labora_field( 'help_label' ) ); ?> <?php echo labora_icon( 'arrow', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></div>
  </nav>
  <div>
<?php foreach ( $labora_groups as $labora_g ) :
	$labora_t     = $labora_g['term'];
	$labora_intro = $labora_t->term_id ? (string) get_term_meta( $labora_t->term_id, 'topic_intro', true ) : '';
	?>
      <section class="faq-group" id="<?php echo esc_attr( $labora_t->slug ); ?>" aria-labelledby="<?php echo esc_attr( $labora_t->slug ); ?>-title">
        <div class="faq-group-head"><span class="t-ic"><?php echo labora_icon( $labora_icon( $labora_t ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><div><h2 id="<?php echo esc_attr( $labora_t->slug ); ?>-title"><?php echo esc_html( $labora_t->name ); ?></h2><?php if ( $labora_intro ) : ?><p><?php echo esc_html( $labora_intro ); ?></p><?php endif; ?></div></div>
        <div class="faq-list">
<?php foreach ( $labora_g['items'] as $labora_q ) : ?>
          <?php echo labora_faq_item( $labora_q ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

<?php endforeach; ?>
        </div>
      </section>
<?php endforeach; ?>
    <div class="faq-empty">
      <h3><?php echo esc_html( labora_field( 'empty_title' ) ); ?></h3>
      <p><?php echo esc_html( labora_field( 'empty_text' ) ); ?></p>
      <p style="margin-top:1.25rem"><button class="btn btn--ghost" type="button" data-faq-reset><?php esc_html_e( 'Clear search', 'labora' ); ?></button></p>
    </div>
  </div>
</div>

<?php $labora_cards = (array) labora_field( 'cards' ); if ( $labora_cards ) : ?>
<div class="container">
  <section class="help-cards" aria-label="<?php esc_attr_e( 'More help', 'labora' ); ?>">
<?php foreach ( $labora_cards as $labora_c ) : ?>
    <a class="help-card<?php echo empty( $labora_c['dark'] ) ? '' : ' help-card--dark'; ?> reveal" href="<?php echo esc_url( labora_link( (string) $labora_c['url'] ) ); ?>"><span class="t-ic"><?php echo labora_icon( $labora_c['icon'] ?: 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><h3><?php echo esc_html( $labora_c['title'] ); ?></h3><p><?php echo esc_html( $labora_c['text'] ); ?></p><span class="link-arrow"><?php echo esc_html( $labora_c['label'] ); ?> <?php echo labora_icon( 'arrow', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></a>
<?php endforeach; ?>
  </section>
</div>
<?php endif; ?>

<div class="toast" role="status" aria-live="polite"></div>
</main>
<?php
get_footer();
