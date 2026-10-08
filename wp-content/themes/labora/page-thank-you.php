<?php
/**
 * Thank-you page (the page with the slug "thank-you"), converted unchanged from the HTML site's thank-you/index.html.
 * The forms open it after a successful send. assets/js/pages.js personalizes it from sessionStorage "labora_thanks"
 * ({ form: 'demo'|'contact', topic, name, ts }): the visitor's first name, the request type, matching next steps,
 * and when it was sent. A direct visit shows the general "message received" version below.
 * Set the page to noindex in Yoast (done by the setup), so it stays out of search results and the sitemap.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_enqueue_bundle( 'pages' );
get_header();

$labora_reply = (string) apply_filters( 'labora_reply_email', get_option( 'admin_email' ) );
$labora_arrow = '<svg class="icon icon-sm" aria-hidden="true"><use href="#i-arrow"/></svg>';
$labora_links = apply_filters( 'labora_thank_you_links', array(
	array( '/#product-tour', 'Product tour', 'From registration to a signed report.' ),
	array( '/security/', 'Trust Center', 'How patient data is protected.' ),
	array( '/blog/how-to-cut-lab-turnaround-time/', 'How to cut lab turnaround time', 'A practical guide from our blog.' ),
	array( '/pricing/', 'Pricing', 'How plans are put together for your lab.' ),
) );
?>

<main id="main" class="thank-you-main">
<section class="ty" aria-labelledby="ty-title">
  <div class="container">
    <div class="ty-head">
      <p class="hm-label" data-ty-label>Message received</p>
      <h1 id="ty-title"><span data-ty-hello>Thank you.</span> <span data-ty-title>Your message is with our team.</span></h1>
      <p class="ty-lead" data-ty-lead>We'll reply by email to the address you gave us. There's nothing else you need to do.</p>
    </div>
    <dl class="ty-meta">
      <div><dt>Request</dt><dd data-ty-kind>Message</dd></div>
      <div data-ty-sent hidden><dt>Sent</dt><dd><time data-ty-time></time></dd></div>
      <?php if ( is_email( $labora_reply ) ) : ?><div><dt>Replies from</dt><dd><a href="mailto:<?php echo esc_attr( $labora_reply ); ?>"><?php echo esc_html( $labora_reply ); ?></a></dd></div><?php endif; ?>
    </dl>
    <div class="ty-grid">
      <section aria-labelledby="ty-next">
        <h2 class="ty-h2" id="ty-next">What happens next</h2>
        <ol class="ty-steps" data-ty-steps="contact"><li class="is-done"><span class="ty-num">01</span><div><h3>Message received</h3><p>Your topic and number of centers came through with it.</p></div><span class="ty-state">Received</span></li><li><span class="ty-num">02</span><div><h3>We reply by email</h3><p>Someone from our team answers and, if it helps, suggests a time to talk.</p></div><span class="ty-state">Next</span></li><li><span class="ty-num">03</span><div><h3>A demo or a quote, if you want one</h3><p>Set up for the modules and centers you need, with no commitment.</p></div><span class="ty-state">Then</span></li></ol>
        <ol class="ty-steps" data-ty-steps="demo" hidden><li class="is-done"><span class="ty-num">01</span><div><h3>Request received</h3><p>Your lab type and number of centers came through with it.</p></div><span class="ty-state">Received</span></li><li><span class="ty-num">02</span><div><h3>We email you to pick a time</h3><p>Someone from our team replies to the work email you gave us.</p></div><span class="ty-state">Next</span></li><li><span class="ty-num">03</span><div><h3>A 20-minute walkthrough</h3><p>Set up with your tests, departments, and price list, so you see your own lab day.</p></div><span class="ty-state">Then</span></li></ol>
      </section>
      <nav class="nf-index ty-read" aria-labelledby="ty-wait">
        <h2 id="ty-wait">While you wait</h2>
        <ul><?php foreach ( $labora_links as list( $labora_path, $labora_title, $labora_desc ) ) : ?><li><a href="<?php echo esc_url( home_url( $labora_path ) ); ?>"><span class="nf-i-t"><?php echo esc_html( $labora_title ); ?></span><span class="nf-i-d"><?php echo esc_html( $labora_desc ); ?></span><?php echo $labora_arrow; // phpcs:ignore WordPress.Security.EscapeOutput ?></a></li><?php endforeach; ?></ul>
        <p class="ty-back"><a class="nf-alt" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to the homepage</a></p>
      </nav>
    </div>
  </div>
</section>
</main>

<?php
get_footer();
