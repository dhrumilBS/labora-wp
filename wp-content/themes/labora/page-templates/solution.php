<?php
/**
 * Template Name: Solution
 *
 * A Solutions page (/solutions/<slug>/), as on the HTML site: hero with screenshot, problems and what changes,
 * the modules used most, the other lab types, closing box. Fields: inc/page-fields.php; shared parts:
 * inc/product.php. "Labora also runs" lists the other pages under the same parent.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_enqueue_bundle( 'pages' );
get_header();

$labora_pains  = array_values( array_filter( (array) labora_field( 'pains' ) ) );
$labora_parent = wp_get_post_parent_id( get_the_ID() );
$labora_others = $labora_parent ? get_pages( array( 'parent' => $labora_parent, 'sort_column' => 'menu_order,post_title', 'exclude' => array( get_the_ID() ) ) ) : array();
?>
<main id="main" class="product-main">
<section class="pr-hero" aria-labelledby="pr-title">
  <div class="container">
    <?php echo labora_product_crumbs(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

    <div class="pr-intro">
      <div>
        <p class="eyebrow"><span class="dot" aria-hidden="true"></span><?php echo esc_html( labora_field( 'eyebrow' ) ?: get_the_title() ); ?></p>
        <h1 id="pr-title"><?php echo esc_html( labora_field( 'hero_title' ) ?: get_the_title() ); ?></h1>
        <p class="lead"><?php echo esc_html( labora_field( 'hero_lead' ) ); ?></p>
        <div class="ab-ctas">
          <a class="btn btn--primary btn--lg" href="<?php echo esc_url( labora_setting_url( 'demo_url' ) ); ?>"><?php echo esc_html( labora_setting( 'demo_label' ) ); ?></a>
          <a class="btn btn--ghost btn--lg" href="<?php echo esc_url( home_url( '/pricing/' ) ); ?>"><?php esc_html_e( 'See pricing', 'labora' ); ?></a>
        </div>
      </div>
    </div>
    <?php echo labora_product_visual(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

  </div>
</section>

<section class="page-section" aria-labelledby="pr-pain-title">
  <div class="container ab-split">
    <div class="ab-side reveal">
      <p class="ab-label"><?php echo esc_html( labora_heading( 'pain_label', 'What changes' ) ); ?></p>
      <h2 id="pr-pain-title"><?php echo esc_html( labora_heading( 'pain_title', 'The everyday problems Labora removes' ) ); ?></h2>
    </div>
    <ol class="ab-list pr-pains"><?php foreach ( $labora_pains as $labora_i => $labora_r ) : ?><li class="reveal"><span class="ab-num"><?php echo esc_html( sprintf( '%02d', $labora_i + 1 ) ); ?></span><h3><?php echo esc_html( $labora_r['problem'] ); ?></h3><p><?php echo esc_html( $labora_r['solution'] ); ?></p></li><?php endforeach; ?></ol>
  </div>
</section>

<section class="page-section" aria-labelledby="pr-mod-title">
  <div class="container">
    <div class="ab-head reveal">
      <p class="ab-label"><?php echo esc_html( labora_heading( 'mod_label', 'Modules you will use most' ) ); ?></p>
      <h2 id="pr-mod-title"><?php echo esc_html( labora_heading( 'mod_title', 'The parts of Labora built for your lab' ) ); ?></h2>
      <p><?php echo esc_html( labora_heading( 'mod_text', 'Start with the modules you need and add more as you grow. Every module shares the same patient case.' ) ); ?></p>
    </div>
    <div class="pr-mods"><?php echo labora_module_cards( (array) labora_field( 'modules' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
  </div>
</section>
<?php if ( $labora_others ) : ?>

<section class="page-section" aria-labelledby="pr-other-title">
  <div class="container ab-split">
    <div class="ab-side reveal">
      <p class="ab-label"><?php echo esc_html( labora_heading( 'other_label', 'Other labs' ) ); ?></p>
      <h2 id="pr-other-title"><?php echo esc_html( labora_heading( 'other_title', 'Labora also runs' ) ); ?></h2>
    </div>
    <ul class="ab-labs-list pr-others reveal"><?php foreach ( $labora_others as $labora_o ) : ?><li><a href="<?php echo esc_url( get_permalink( $labora_o ) ); ?>"><?php echo labora_icon( (string) labora_page_field( 'icon', $labora_o->ID ) ?: 'flask' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( get_the_title( $labora_o ) ); ?></span></a></li><?php endforeach; ?></ul>
  </div>
</section>
<?php endif; ?>

<?php labora_product_cta(); ?>
</main>
<?php
get_footer();
