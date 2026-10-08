<?php
/**
 * Homepage "Book a demo" form card with the Contact Form 7 form (inc/forms.php), overriding the parent's static
 * demo-mode form. If Contact Form 7 is inactive, the parent's version is shown.
 *
 * After a successful send, Lead Guard for Contact Form 7 adds .is-sent to [data-lead-guard-wrap] and shows
 * [data-lead-guard-status]: the form is replaced by the confirmation panel below (styles in assets/css/child.css).
 *
 * @package Labora_Child
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WPCF7_VERSION' ) ) {
	require get_template_directory() . '/template-parts/demo-form.php';
	return;
}
?>
    <div class="form-card reveal" data-lead-guard-wrap>
      <h3>Book a demo</h3>
      <p>Tell us a little about your lab and we'll be in touch to schedule a time.</p>
      <?php echo labora_form( 'demo', array( 'html_id' => 'labora-demo-form' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- CF7 output ?>
      <p class="form-note">By submitting, you agree to our <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">privacy policy</a>.</p>
      <div class="form-status" role="status" aria-live="polite" tabindex="-1" data-lead-guard-status hidden>
        <span class="ok-icon"><?php echo labora_icon( 'check', 'icon icon-lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
        <h3>Demo request received</h3>
        <p>Thanks. We'll email you shortly to schedule a time that works for your team.</p>
      </div>
    </div>
