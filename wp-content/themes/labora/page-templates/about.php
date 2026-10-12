<?php
/**
 * Template Name: About
 *
 * The HTML site's /about/ page with a fixed layout: every heading, paragraph, and list is a field on the page
 * (inc/page-fields.php); the questions are FAQ entries with "About page" ticked (inc/faq.php). Leadership and
 * extra company facts appear only when filled in.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_enqueue_bundle( 'pages' );
get_header();

$labora_check  = labora_icon( 'check' );
$labora_rows   = fn( $name ) => array_filter( (array) labora_field( $name ) );
$labora_text   = fn( $html ) => wp_kses( preg_replace_callback( '#href="(/[^"]*)"#', fn( $m ) => 'href="' . esc_url( home_url( $m[1] ) ) . '"', (string) $html ), array( 'a' => array( 'href' => true ), 'strong' => array(), 'em' => array() ) );
$labora_faq    = labora_faq_entries( 'about' );
$labora_faqurl = ( $labora_faq_page = get_page_by_path( 'faq' ) ) ? get_permalink( $labora_faq_page ) : '';
/** A text and screenshot row ("As you grow", "Security and access"). */
$labora_feature = function ( string $p, string $title_id, bool $flip ) use ( $labora_check ) {
	?>
<section class="page-section ab-feature" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
  <div class="container ab-row<?php echo $flip ? ' ab-row--flip' : ''; ?>">
    <div class="ab-row-copy reveal">
      <p class="ab-label"><?php echo esc_html( labora_field( "{$p}_label" ) ); ?></p>
      <h2 id="<?php echo esc_attr( $title_id ); ?>"><?php echo esc_html( labora_field( "{$p}_title" ) ); ?></h2>
      <p><?php echo esc_html( labora_field( "{$p}_text" ) ); ?></p>
      <ul class="checks ab-checks"><?php foreach ( labora_lines( "{$p}_checks" ) as $line ) { echo '<li>' . $labora_check . esc_html( $line ) . '</li>'; } // phpcs:ignore WordPress.Security.EscapeOutput ?></ul>
<?php if ( labora_field( "{$p}_link_label" ) ) : ?>
      <a class="link-arrow" href="<?php echo esc_url( labora_link( (string) labora_field( "{$p}_link_url" ) ) ); ?>"><?php echo esc_html( labora_field( "{$p}_link_label" ) ); ?> <?php echo labora_icon( 'arrow', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
<?php endif; ?>
    </div>
    <figure class="ab-media  reveal"><?php echo labora_screen_picture( (string) labora_field( "{$p}_screen" ) ?: 'dashboard', (string) labora_field( "{$p}_alt" ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></figure>
  </div>
</section>
	<?php
};
?>
<main id="main" class="about-main">
<section class="ab-hero" aria-labelledby="ab-title">
  <div class="container">
    <p class="eyebrow"><span class="dot" aria-hidden="true"></span><?php echo esc_html( labora_field( 'hero_eyebrow' ) ); ?></p>
    <h1 id="ab-title"><?php echo esc_html( labora_field( 'hero_title' ) ?: get_the_title() ); ?></h1>
    <div class="ab-intro">
      <div>
        <p class="lead"><?php echo esc_html( labora_field( 'hero_lead' ) ); ?></p>
        <div class="ab-ctas">
          <a class="btn btn--primary btn--lg" href="<?php echo esc_url( labora_link( (string) labora_field( 'hero_btn1_url' ) ) ); ?>"><?php echo esc_html( labora_field( 'hero_btn1_label' ) ); ?></a>
          <a class="btn btn--ghost btn--lg" href="<?php echo esc_url( labora_link( (string) labora_field( 'hero_btn2_url' ) ) ); ?>"><?php echo esc_html( labora_field( 'hero_btn2_label' ) ); ?></a>
        </div>
      </div>
      <dl class="ab-glance"><?php foreach ( $labora_rows( 'glance' ) as $labora_r ) : ?><div><dt><?php echo esc_html( $labora_r['term'] ); ?></dt><dd><?php echo esc_html( $labora_r['text'] ); ?></dd></div><?php endforeach; ?></dl>
    </div>
    <figure class="ab-shot">
      <?php echo labora_screen_picture( (string) labora_field( 'hero_screen' ) ?: 'patient-case', (string) labora_field( 'hero_alt' ), true ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php if ( labora_field( 'hero_caption' ) ) : ?>
      <figcaption><?php echo esc_html( labora_field( 'hero_caption' ) ); ?></figcaption>
<?php endif; ?>
    </figure>
  </div>
</section>

<section class="page-section ab-story" aria-labelledby="ab-story-title">
  <div class="container ab-split">
    <div class="ab-side reveal">
      <p class="ab-label"><?php echo esc_html( labora_field( 'story_label' ) ); ?></p>
      <h2 id="ab-story-title"><?php echo esc_html( labora_field( 'story_title' ) ); ?></h2>
    </div>
    <div class="ab-prose reveal">
<?php foreach ( array_filter( array_map( 'trim', preg_split( '/\R\s*\R/', (string) labora_field( 'story_text' ) ) ) ) as $labora_para ) : ?>
      <p><?php echo esc_html( $labora_para ); ?></p>
<?php endforeach; ?>
<?php if ( labora_field( 'quote_text' ) ) : ?>
      <blockquote class="ab-quote">
        <p>“<?php echo esc_html( labora_field( 'quote_text' ) ); ?>”</p>
<?php if ( labora_field( 'quote_link_label' ) ) : ?>
        <a class="link-arrow" href="<?php echo esc_url( labora_link( (string) labora_field( 'quote_link_url' ) ) ); ?>"><?php echo esc_html( labora_field( 'quote_link_label' ) ); ?> <?php echo labora_icon( 'arrow', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
<?php endif; ?>
      </blockquote>
<?php endif; ?>
    </div>
  </div>
</section>

<section class="page-section ab-journey" aria-labelledby="ab-journey-title">
  <div class="container">
    <div class="ab-head reveal">
      <p class="ab-label"><?php echo esc_html( labora_field( 'journey_label' ) ); ?></p>
      <h2 id="ab-journey-title"><?php echo esc_html( labora_field( 'journey_title' ) ); ?></h2>
      <p><?php echo esc_html( labora_field( 'journey_text' ) ); ?></p>
    </div>
    <ol class="ab-track"><?php foreach ( array_values( $labora_rows( 'journey_steps' ) ) as $labora_i => $labora_r ) : ?><li class="reveal"><span class="ab-step"><?php echo esc_html( sprintf( '%02d', $labora_i + 1 ) ); ?></span><span class="t-ic"><?php echo labora_icon( $labora_r['icon'] ?: 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><h3><?php echo esc_html( $labora_r['title'] ); ?></h3><p><?php echo esc_html( $labora_r['text'] ); ?></p></li><?php endforeach; ?></ol>
  </div>
</section>

<section class="page-section ab-beliefs" aria-labelledby="ab-beliefs-title">
  <div class="container ab-split">
    <div class="ab-side reveal">
      <p class="ab-label"><?php echo esc_html( labora_field( 'beliefs_label' ) ); ?></p>
      <h2 id="ab-beliefs-title"><?php echo esc_html( labora_field( 'beliefs_title' ) ); ?></h2>
    </div>
    <ol class="ab-list"><?php foreach ( array_values( $labora_rows( 'beliefs' ) ) as $labora_i => $labora_r ) : ?><li class="reveal"><span class="ab-num"><?php echo esc_html( sprintf( '%02d', $labora_i + 1 ) ); ?></span><h3><?php echo esc_html( $labora_r['title'] ); ?></h3><p><?php echo $labora_text( $labora_r['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p></li><?php endforeach; ?></ol>
  </div>
</section>

<section class="page-section ab-labs" aria-labelledby="ab-labs-title">
  <div class="container ab-split">
    <div class="ab-side reveal">
      <p class="ab-label"><?php echo esc_html( labora_field( 'labs_label' ) ); ?></p>
      <h2 id="ab-labs-title"><?php echo esc_html( labora_field( 'labs_title' ) ); ?></h2>
    </div>
    <div class="reveal">
      <ul class="ab-labs-list"><?php foreach ( $labora_rows( 'labs' ) as $labora_r ) : ?><li><?php echo labora_icon( $labora_r['icon'] ?: 'flask' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $labora_r['name'] ); ?></span></li><?php endforeach; ?></ul>
<?php if ( labora_field( 'labs_note' ) ) : ?>
      <p class="ab-note"><?php echo esc_html( labora_field( 'labs_note' ) ); ?></p>
<?php endif; ?>
    </div>
  </div>
</section>

<?php $labora_feature( 'scale', 'ab-scale-title', false ); ?>

<?php $labora_feature( 'sec', 'ab-sec-title', true ); ?>

<section class="page-section ab-work" aria-labelledby="ab-work-title">
  <div class="container">
    <div class="ab-band on-dark">
      <div class="ab-head reveal">
        <p class="ab-label"><?php echo esc_html( labora_field( 'work_label' ) ); ?></p>
        <h2 id="ab-work-title"><?php echo esc_html( labora_field( 'work_title' ) ); ?></h2>
        <p><?php echo esc_html( labora_field( 'work_text' ) ); ?></p>
      </div>
      <ul class="ab-commit"><?php foreach ( $labora_rows( 'work' ) as $labora_r ) : ?><li class="reveal"><span class="t-ic"><?php echo labora_icon( $labora_r['icon'] ?: 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><h3><?php echo esc_html( $labora_r['title'] ); ?></h3><p><?php echo esc_html( $labora_r['text'] ); ?></p></li><?php endforeach; ?></ul>
    </div>
  </div>
</section>
<?php $labora_team = array_filter( $labora_rows( 'team' ), fn( $r ) => '' !== trim( (string) ( $r['name'] ?? '' ) ) ); if ( $labora_team ) : ?>

<section class="page-section ab-team" aria-labelledby="ab-team-title">
  <div class="container">
    <div class="split-head reveal"><h2 id="ab-team-title"><?php echo esc_html( labora_field( 'team_title' ) ?: __( 'The people behind Labora', 'labora' ) ); ?></h2></div>
    <ul class="ab-people"><?php foreach ( $labora_team as $labora_r ) : ?><li><?php echo $labora_r['photo'] ? wp_get_attachment_image( (int) $labora_r['photo'], 'medium', false, array( 'width' => 320, 'height' => 320, 'alt' => $labora_r['name'], 'loading' => 'lazy', 'decoding' => 'async' ) ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?><h3><?php echo esc_html( $labora_r['name'] ); ?></h3><p><?php echo esc_html( $labora_r['role'] ); ?></p></li><?php endforeach; ?></ul>
  </div>
</section>
<?php endif; ?>

<?php if ( $labora_faq ) : ?>
<section class="page-section faq-block" aria-labelledby="ab-faq-title">
  <div class="container faq-wrap">
    <div class="faq-aside reveal">
      <h2 id="ab-faq-title"><?php echo esc_html( labora_field( 'faq_title' ) ); ?></h2>
      <p><?php echo esc_html( labora_field( 'faq_text' ) ); ?></p>
<?php if ( $labora_faqurl ) : ?>
      <a class="link-arrow faq-more" href="<?php echo esc_url( $labora_faqurl ); ?>"><?php echo esc_html( labora_field( 'faq_link_label' ) ); ?> <?php echo labora_icon( 'arrow', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
<?php endif; ?>
    </div>
    <div class="faq-list"><?php foreach ( $labora_faq as $labora_q ) { echo labora_faq_item( $labora_q, false, false ); } // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
  </div>
</section>
<?php endif; ?>

<section class="page-section ab-cta" aria-labelledby="ab-cta-title">
  <div class="container">
    <div class="ab-cta-card reveal">
      <div>
        <h2 id="ab-cta-title"><?php echo esc_html( labora_field( 'cta_title' ) ); ?></h2>
        <p><?php echo esc_html( labora_field( 'cta_text' ) ); ?></p>
      </div>
      <div class="ab-ctas">
        <a class="btn btn--primary btn--lg" href="<?php echo esc_url( labora_link( (string) labora_field( 'cta_btn1_url' ) ) ); ?>"><?php echo esc_html( labora_field( 'cta_btn1_label' ) ); ?></a>
        <a class="btn btn--ghost btn--lg" href="<?php echo esc_url( labora_link( (string) labora_field( 'cta_btn2_url' ) ) ); ?>"><?php echo esc_html( labora_field( 'cta_btn2_label' ) ); ?></a>
      </div>
    </div>
  </div>
</section>
</main>
<?php
get_footer();
