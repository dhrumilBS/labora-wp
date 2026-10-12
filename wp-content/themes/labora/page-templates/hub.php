<?php
/**
 * Template Name: Hub
 *
 * A parent page that introduces the pages under it (/platform/, /solutions/), built from the Platform and
 * Solution pages' own parts: hero with screenshot, a card for every page under it, "why" points, the other hub's
 * pages, and the closing box. Fields: inc/page-fields.php ("Hub"); shared parts: inc/product.php.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_template_assets( 'pages' );
get_header();

$labora_cards  = labora_hub_pages( get_the_ID() );
$labora_points = labora_lines( 'why_points' );
$labora_other  = (int) labora_field( 'other_hub' );
$labora_others = $labora_other && 'publish' === get_post_status( $labora_other ) ? labora_hub_pages( $labora_other ) : array();
$labora_second = trim( (string) labora_field( 'second_url' ) ) ?: '/#product-tour';
?>
<main id="main" class="product-main hub-main">
<section class="pr-hero" aria-labelledby="pr-title">
  <div class="container">
    <?php echo labora_product_crumbs(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

    <div class="pr-intro">
      <div>
        <p class="eyebrow"><span class="dot" aria-hidden="true"></span><?php echo esc_html( labora_field( 'eyebrow' ) ?: get_the_title() ); ?></p>
        <h1 id="pr-title"><?php echo esc_html( labora_field( 'hero_title' ) ?: get_the_title() ); ?></h1>
<?php if ( labora_field( 'hero_lead' ) ) : ?>
        <p class="lead"><?php echo esc_html( labora_field( 'hero_lead' ) ); ?></p>
<?php endif; ?>
        <div class="ab-ctas">
          <a class="btn btn--primary btn--lg" href="<?php echo esc_url( labora_setting_url( 'demo_url' ) ); ?>"><?php echo esc_html( labora_setting( 'demo_label' ) ); ?></a>
          <a class="btn btn--ghost btn--lg" href="<?php echo esc_url( labora_link( $labora_second ) ); ?>"><?php echo esc_html( trim( (string) labora_field( 'second_label' ) ) ?: __( 'Watch the product tour', 'labora' ) ); ?></a>
        </div>
      </div>
    </div>
    <?php echo labora_product_visual(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

  </div>
</section>
<?php if ( $labora_cards ) : ?>

<section class="page-section" aria-labelledby="hub-cards-title">
  <div class="container">
    <div class="ab-head reveal">
      <p class="ab-label"><?php echo esc_html( labora_heading( 'cards_label', 'All modules' ) ); ?></p>
      <h2 id="hub-cards-title"><?php echo esc_html( labora_heading( 'cards_title', 'Everything your lab runs on, in one system' ) ); ?></h2>
<?php if ( labora_field( 'cards_text' ) ) : ?>
      <p><?php echo esc_html( labora_field( 'cards_text' ) ); ?></p>
<?php endif; ?>
    </div>
    <div class="pr-mods hub-cards"><?php echo labora_module_cards( $labora_cards ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
  </div>
</section>
<?php endif; ?>
<?php if ( $labora_points ) : ?>

<section class="page-section" aria-labelledby="hub-why-title">
  <div class="container ab-split">
    <div class="ab-side reveal">
      <p class="ab-label"><?php echo esc_html( labora_heading( 'why_label', 'Why one system' ) ); ?></p>
      <h2 id="hub-why-title"><?php echo esc_html( labora_field( 'why_title' ) ); ?></h2>
    </div>
    <div class="reveal">
      <ul class="pr-feats"><?php foreach ( $labora_points as $labora_line ) : ?><li><?php echo labora_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $labora_line ); ?></span></li><?php endforeach; ?></ul>
    </div>
  </div>
</section>
<?php endif; ?>
<?php if ( $labora_others ) : ?>

<section class="page-section" aria-labelledby="hub-other-title">
  <div class="container ab-split">
    <div class="ab-side reveal">
      <p class="ab-label"><?php echo esc_html( trim( (string) labora_field( 'other_label' ) ) ?: get_the_title( $labora_other ) ); ?></p>
      <h2 id="hub-other-title"><?php echo esc_html( trim( (string) labora_field( 'other_title' ) ) ?: get_the_title( $labora_other ) ); ?></h2>
      <a class="link-arrow hub-other-link" href="<?php echo esc_url( get_permalink( $labora_other ) ); ?>"><?php echo esc_html( trim( (string) labora_field( 'other_link' ) ) ?: sprintf( /* translators: %s: hub page title, e.g. Solutions */ __( 'All %s', 'labora' ), strtolower( get_the_title( $labora_other ) ) ) ); ?> <?php echo labora_icon( 'arrow', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
    </div>
    <ul class="ab-labs-list pr-others reveal"><?php foreach ( $labora_others as $labora_o ) : ?><li><a href="<?php echo esc_url( get_permalink( $labora_o ) ); ?>"><?php echo labora_icon( (string) labora_page_field( 'icon', $labora_o ) ?: 'flask' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( get_the_title( $labora_o ) ); ?></span></a></li><?php endforeach; ?></ul>
  </div>
</section>
<?php endif; ?>

<?php labora_product_cta(); ?>
</main>
<?php
get_footer();
