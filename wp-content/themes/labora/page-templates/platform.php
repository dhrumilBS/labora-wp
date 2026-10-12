<?php
/**
 * Template Name: Platform module
 *
 * A Platform page (/platform/<slug>/), as on the HTML site: hero with screenshot, features, three steps,
 * related modules, closing box. Fields: inc/page-fields.php; shared parts: inc/product.php.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_enqueue_bundle( 'pages' );
get_header();

$labora_guide = (int) labora_field( 'guide' );
$labora_steps = array_values( array_filter( (array) labora_field( 'steps' ) ) );
?>
<main id="main" class="product-main">
<section class="pr-hero" aria-labelledby="pr-title">
  <div class="container">
    <?php echo labora_product_crumbs(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

    <div class="pr-intro">
      <div>
        <p class="eyebrow"><span class="dot" aria-hidden="true"></span><?php the_title(); ?></p>
        <h1 id="pr-title"><?php echo esc_html( labora_field( 'hero_title' ) ?: get_the_title() ); ?></h1>
        <p class="lead"><?php echo esc_html( labora_field( 'hero_lead' ) ); ?></p>
        <div class="ab-ctas">
          <a class="btn btn--primary btn--lg" href="<?php echo esc_url( labora_setting_url( 'demo_url' ) ); ?>"><?php echo esc_html( labora_setting( 'demo_label' ) ); ?></a>
          <a class="btn btn--ghost btn--lg" href="<?php echo esc_url( home_url( '/#product-tour' ) ); ?>"><?php esc_html_e( 'Watch the product tour', 'labora' ); ?></a>
        </div>
      </div>
    </div>
    <?php echo labora_product_visual(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

  </div>
</section>

<section class="page-section" aria-labelledby="pr-feat-title">
  <div class="container ab-split">
    <div class="ab-side reveal">
      <p class="ab-label"><?php echo esc_html( labora_heading( 'feat_label', 'What you get' ) ); ?></p>
      <h2 id="pr-feat-title"><?php echo esc_html( labora_heading( 'feat_title', 'Built into {noun}' ) ); ?></h2>
    </div>
    <div class="reveal">
      <ul class="pr-feats"><?php foreach ( labora_lines( 'features' ) as $labora_line ) : ?><li><?php echo labora_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $labora_line ); ?></span></li><?php endforeach; ?></ul>
<?php if ( $labora_guide && 'publish' === get_post_status( $labora_guide ) ) : ?>
      <p class="pr-guide reveal"><?php echo labora_icon( 'book' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php esc_html_e( 'Read our guide:', 'labora' ); ?> <a href="<?php echo esc_url( get_permalink( $labora_guide ) ); ?>"><?php echo esc_html( get_the_title( $labora_guide ) ); ?></a></span></p>
<?php endif; ?>
    </div>
  </div>
</section>

<section class="page-section" aria-labelledby="pr-how-title">
  <div class="container">
    <div class="ab-head reveal">
      <p class="ab-label"><?php echo esc_html( labora_heading( 'how_label', 'How it works' ) ); ?></p>
      <h2 id="pr-how-title"><?php echo esc_html( labora_heading( 'how_title', 'Three steps, one patient case' ) ); ?></h2>
    </div>
    <ol class="ab-track pr-track"><?php foreach ( $labora_steps as $labora_i => $labora_s ) : ?><li class="reveal"><span class="ab-step"><?php echo esc_html( sprintf( '%02d', $labora_i + 1 ) ); ?></span><h3><?php echo esc_html( $labora_s['title'] ); ?></h3><p><?php echo esc_html( $labora_s['text'] ); ?></p></li><?php endforeach; ?></ol>
  </div>
</section>

<section class="page-section" aria-labelledby="pr-rel-title">
  <div class="container">
    <div class="ab-head reveal">
      <p class="ab-label"><?php echo esc_html( labora_heading( 'rel_label', 'Works with' ) ); ?></p>
      <h2 id="pr-rel-title"><?php echo esc_html( labora_heading( 'rel_title', 'Connected to the rest of Labora' ) ); ?></h2>
      <p><?php echo esc_html( labora_heading( 'rel_text', 'Every module shares the same data, so information entered once is available everywhere.' ) ); ?></p>
    </div>
    <div class="pr-mods"><?php echo labora_module_cards( (array) labora_field( 'related' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
  </div>
</section>

<?php labora_product_cta(); ?>
</main>
<?php
get_footer();
