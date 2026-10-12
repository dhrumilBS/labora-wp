<?php
/**
 * Template Name: Trust Center
 *
 * The HTML site's /security/ page with a fixed layout: every heading, paragraph, and list is a field on the page
 * (inc/page-fields.php); the product pictures are template parts (template-parts/trust/); the questions are FAQ
 * entries with "Trust Center" ticked (inc/faq.php); the security email comes from Labora Settings.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_enqueue_bundle( 'pages' );
get_header();

$labora_check   = labora_icon( 'check' );
$labora_updated = (string) labora_field( 'updated' );
$labora_time    = $labora_updated ? sprintf( '<time datetime="%s">%s</time>', esc_attr( $labora_updated ), esc_html( wp_date( 'M j, Y', strtotime( $labora_updated . ' 12:00' ) ) ) ) : '';
$labora_email   = sanitize_email( (string) labora_setting( 'email_security' ) );
$labora_checks  = function ( string $field ) use ( $labora_check ) {
	foreach ( labora_lines( $field ) as $line ) {
		echo '<li>' . $labora_check . esc_html( $line ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
};
$labora_faq       = labora_faq_entries( 'security' );
$labora_faq_page  = get_page_by_path( 'faq' );
$labora_statuses  = array( 'live' => __( 'Complete', 'labora' ), 'progress' => __( 'In progress', 'labora' ), 'planned' => __( 'Planned', 'labora' ) );
?>
<main id="main" class="security-main">
<section class="trust-hero" aria-labelledby="trust-title">
  <div class="container">
    <p class="eyebrow"><span class="dot" aria-hidden="true"></span><?php echo esc_html( labora_field( 'hero_eyebrow' ) ); ?></p>
    <h1 id="trust-title"><?php echo esc_html( labora_field( 'hero_title' ) ?: get_the_title() ); ?></h1>
    <p class="lead"><?php echo esc_html( labora_field( 'hero_lead' ) ); ?></p>
    <div class="trust-ctas">
      <a class="btn btn--primary btn--lg" href="<?php echo esc_url( labora_link( (string) labora_field( 'hero_btn1_url' ) ) ); ?>"><?php echo esc_html( labora_field( 'hero_btn1_label' ) ); ?></a>
      <a class="btn btn--ghost btn--lg" href="<?php echo esc_url( labora_link( (string) labora_field( 'hero_btn2_url' ) ) ); ?>"><?php echo esc_html( labora_field( 'hero_btn2_label' ) ); ?></a>
    </div>
    <ul class="marks"><?php foreach ( (array) labora_field( 'marks' ) as $labora_m ) : ?><li class="mark"><?php if ( '' !== trim( (string) $labora_m['badge'] ) ) : ?><span class="mark-ic mark-ic--text" aria-hidden="true"><?php echo esc_html( $labora_m['badge'] ); ?></span><?php else : ?><span class="mark-ic" aria-hidden="true"><?php echo labora_icon( $labora_m['icon'] ?: 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?><b><?php echo esc_html( $labora_m['title'] ); ?></b><small<?php echo empty( $labora_m['highlight'] ) ? '' : ' class="is-progress"'; ?>><?php echo esc_html( $labora_m['note'] ); ?></small></li><?php endforeach; ?></ul>
<?php if ( $labora_time ) : ?>
    <p class="trust-updated"><?php esc_html_e( 'Last updated', 'labora' ); ?> <?php echo $labora_time; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
<?php endif; ?>
  </div>
</section>

<nav class="trust-nav" aria-label="<?php esc_attr_e( 'On this page', 'labora' ); ?>"><div class="container"><ul><?php foreach ( (array) labora_field( 'nav' ) as $labora_n ) : ?><li><a href="#<?php echo esc_attr( ltrim( (string) $labora_n['anchor'], '#' ) ); ?>"><?php echo esc_html( $labora_n['label'] ); ?></a></li><?php endforeach; ?></ul></div></nav>

<section class="page-section trust-section" id="overview" aria-labelledby="overview-title">
  <div class="container">
    <div class="s-head reveal">
      <h2 id="overview-title"><?php echo esc_html( labora_field( 'overview_title' ) ); ?></h2>
      <p><?php echo esc_html( labora_field( 'overview_text' ) ); ?></p>
    </div>
    <div class="vgrid">
<?php foreach ( array_slice( (array) labora_field( 'overview_cards' ), 0, 4 ) as $labora_i => $labora_c ) : ?>
      <article class="vcard reveal"><div class="vpanel" aria-hidden="true"><?php get_template_part( 'template-parts/trust/overview-mock-' . ( $labora_i + 1 ) ); ?></div><h3><?php echo esc_html( $labora_c['title'] ); ?></h3><p><?php echo esc_html( $labora_c['text'] ); ?></p></article>
<?php endforeach; ?>
    </div>
  </div>
</section>

<section class="page-section trust-section" id="access" aria-labelledby="access-title">
  <div class="container t-row">
    <div class="t-copy reveal">
      <p class="kicker"><?php echo labora_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( labora_field( 'access_kicker' ) ); ?></p>
      <h2 id="access-title"><?php echo esc_html( labora_field( 'access_title' ) ); ?></h2>
      <p><?php echo esc_html( labora_field( 'access_text' ) ); ?></p>
      <ul class="checks">
<?php foreach ( labora_lines( 'access_checks' ) as $labora_line ) : ?>
        <li><?php echo $labora_check; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $labora_line ); ?></li>
<?php endforeach; ?>
      </ul>
    </div>
    <div class="reveal">
      <?php get_template_part( 'template-parts/trust/access-matrix' ); ?>
      <p class="t-note"><?php echo esc_html( labora_field( 'access_note' ) ); ?></p>
    </div>
  </div>
</section>

<section class="page-section trust-section" id="audit" aria-labelledby="audit-title">
  <div class="container t-row t-row--flip">
    <div class="t-copy reveal">
      <p class="kicker"><?php echo labora_icon( 'history' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( labora_field( 'audit_kicker' ) ); ?></p>
      <h2 id="audit-title"><?php echo esc_html( labora_field( 'audit_title' ) ); ?></h2>
      <p><?php echo esc_html( labora_field( 'audit_text' ) ); ?></p>
      <ul class="checks">
<?php foreach ( labora_lines( 'audit_checks' ) as $labora_line ) : ?>
        <li><?php echo $labora_check; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $labora_line ); ?></li>
<?php endforeach; ?>
      </ul>
    </div>
    <?php get_template_part( 'template-parts/trust/audit-timeline' ); ?>
  </div>
</section>

<section class="page-section trust-section" id="protection" aria-labelledby="protection-title">
  <div class="container">
    <div class="s-head reveal">
      <h2 id="protection-title"><?php echo esc_html( labora_field( 'protection_title' ) ); ?></h2>
      <p><?php echo esc_html( labora_field( 'protection_text' ) ); ?></p>
    </div>
    <div class="pcards"><?php foreach ( (array) labora_field( 'protection_cards' ) as $labora_c ) : ?><article class="pcard reveal"><h3><?php echo labora_icon( $labora_c['icon'] ?: 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $labora_c['title'] ); ?></h3><ul><?php foreach ( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $labora_c['items'] ) ) ) as $labora_line ) : ?><li><?php echo esc_html( $labora_line ); ?></li><?php endforeach; ?></ul></article><?php endforeach; ?></div>
  </div>
</section>

<section class="page-section trust-section" id="compliance" aria-labelledby="comp-title">
  <div class="container comp-hl">
    <div class="reveal">
      <div class="s-head">
        <h2 id="comp-title"><?php echo esc_html( labora_field( 'comp_title' ) ); ?></h2>
        <p><?php echo esc_html( labora_field( 'comp_text' ) ); ?></p>
      </div>
      <p class="comp-intro"><?php echo esc_html( labora_field( 'comp_intro' ) ); ?></p>
      <ul class="mock m-checks comp-list"><?php $labora_checks( 'comp_items' ); ?></ul>
      <p class="comp-note"><?php echo wp_kses( preg_replace( '#^<p>(.*)</p>\s*$#s', '$1', (string) labora_field( 'comp_note' ) ), array( 'a' => array( 'href' => true ), 'strong' => array(), 'em' => array() ) ); ?></p>
    </div>
    <div class="comp-status reveal">
      <header><b><?php echo esc_html( labora_field( 'comp_status_title' ) ); ?></b><?php if ( $labora_time ) : ?><span><?php esc_html_e( 'Updated', 'labora' ); ?> <?php echo $labora_time; // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?></header>
      <ul><?php foreach ( (array) labora_field( 'comp_status' ) as $labora_s ) : $labora_st = isset( $labora_statuses[ $labora_s['status'] ] ) ? $labora_s['status'] : 'planned'; ?><li><span class="badge-mark" aria-hidden="true"><?php echo esc_html( $labora_s['badge'] ); ?></span><div><h3><?php echo esc_html( $labora_s['title'] ); ?></h3><p><?php echo esc_html( $labora_s['text'] ); ?></p></div><span class="status status--<?php echo esc_attr( $labora_st ); ?>"><?php echo esc_html( $labora_statuses[ $labora_st ] ); ?></span></li><?php endforeach; ?></ul>
    </div>
  </div>
</section>

<section class="page-section trust-section" id="shared" aria-labelledby="shared-title">
  <div class="container">
    <div class="s-head reveal">
      <h2 id="shared-title"><?php echo esc_html( labora_field( 'shared_title' ) ); ?></h2>
      <p><?php echo esc_html( labora_field( 'shared_text' ) ); ?></p>
    </div>
    <div class="shared"><div class="shared-col reveal"><h3><span class="t-ic"><?php echo labora_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php echo esc_html( labora_field( 'shared_ours_title' ) ); ?></h3><ul class="checks"><?php $labora_checks( 'shared_ours_items' ); ?></ul></div><div class="shared-col shared-col--lab reveal"><h3><span class="t-ic"><?php echo labora_icon( 'building' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php echo esc_html( labora_field( 'shared_lab_title' ) ); ?></h3><ul class="checks"><?php $labora_checks( 'shared_lab_items' ); ?></ul></div></div>
  </div>
</section>

<section class="page-section trust-section" id="disclosure" aria-labelledby="disc-title">
  <div class="container">
    <div class="s-head reveal">
      <h2 id="disc-title"><?php echo esc_html( labora_field( 'disc_title' ) ); ?></h2>
      <p><?php echo esc_html( labora_field( 'disc_text' ) ); ?></p>
    </div>
    <ol class="steps"><?php foreach ( (array) labora_field( 'disc_steps' ) as $labora_s ) : ?><li class="step reveal"><h3><?php echo esc_html( $labora_s['title'] ); ?></h3><p><?php echo labora_fill_email( esc_html( $labora_s['text'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p></li><?php endforeach; ?></ol>
    <div class="disclose">
      <div class="disclose-card reveal">
        <h3><?php echo esc_html( labora_field( 'disc_guidelines_title' ) ); ?></h3>
        <ul>
<?php foreach ( labora_lines( 'disc_guidelines' ) as $labora_line ) : ?>
          <li><?php echo esc_html( $labora_line ); ?></li>
<?php endforeach; ?>
        </ul>
      </div>
      <div class="contact-card reveal">
        <div><h3><?php echo esc_html( labora_field( 'disc_contact_title' ) ); ?></h3><p><?php echo esc_html( labora_field( 'disc_contact_text' ) ); ?></p></div>
        <a class="mail" href="mailto:<?php echo esc_attr( $labora_email ); ?>"><?php echo labora_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $labora_email ); ?></a>
      </div>
    </div>
  </div>
</section>

<?php if ( $labora_faq ) : ?>
<section class="page-section trust-section faq-block" id="security-faq" aria-labelledby="sfaq-title">
  <div class="container faq-wrap">
    <div class="faq-aside reveal">
      <h2 id="sfaq-title"><?php echo esc_html( labora_field( 'faq_title' ) ); ?></h2>
      <p><?php echo esc_html( labora_field( 'faq_text' ) ); ?></p>
<?php if ( $labora_faq_page ) : ?>
      <a class="link-arrow faq-more" href="<?php echo esc_url( get_permalink( $labora_faq_page ) . '#security' ); ?>"><?php echo esc_html( labora_field( 'faq_link_label' ) ); ?> <?php echo labora_icon( 'arrow', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
<?php endif; ?>
    </div>
    <div class="faq-list">
<?php foreach ( $labora_faq as $labora_q ) : ?>
        <?php echo labora_faq_item( $labora_q ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="trust-cta" id="documentation" aria-labelledby="docs-title">
  <div class="container reveal">
    <h2 id="docs-title"><?php echo esc_html( labora_field( 'cta_title' ) ); ?></h2>
    <p><?php echo esc_html( labora_field( 'cta_text' ) ); ?></p>
    <div class="trust-ctas">
      <a class="btn btn--primary btn--lg" href="mailto:<?php echo esc_attr( $labora_email ); ?>?subject=Security%20details%20request"><?php echo esc_html( labora_field( 'cta_btn1_label' ) ); ?></a>
      <a class="btn btn--ghost btn--lg" href="<?php echo esc_url( labora_link( (string) labora_field( 'cta_btn2_url' ) ) ); ?>"><?php echo esc_html( labora_field( 'cta_btn2_label' ) ); ?></a>
    </div>
  </div>
</section>

<div class="toast" role="status" aria-live="polite"></div>
</main>
<?php
get_footer();
