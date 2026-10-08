<?php
/**
 * Homepage "Book a demo" form card: the HTML site's static form (demo mode: it validates and confirms in the
 * browser but sends nothing). The Labora Child theme replaces this part with the Contact Form 7 form.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;
?>
    <div class="form-card reveal">
      <!-- Set data-endpoint to your form handler. Without it, the form validates, confirms, and goes to data-thanks (demo mode). -->
      <form id="demo-form" novalidate data-endpoint="" data-thanks="thank-you/" aria-describedby="form-error">
        <h3>Book a demo</h3>
        <p>Tell us a little about your lab and we'll be in touch to schedule a time.</p>
        <div class="form-grid">
          <div class="field"><label for="f-name">Full name</label><input id="f-name" name="name" type="text" autocomplete="name" required><span class="err" id="f-name-err">Enter your name.</span></div>
          <div class="field"><label for="f-email">Work email</label><input id="f-email" name="email" type="email" autocomplete="email" required><span class="err" id="f-email-err">Enter a valid work email.</span></div>
          <div class="field"><label for="f-company">Lab or organization</label><input id="f-company" name="company" type="text" autocomplete="organization" required><span class="err" id="f-company-err">Enter your lab or organization.</span></div>
          <div class="field"><label for="f-lab">Type of lab</label>
            <select id="f-lab" name="lab_type" required><option value="">Select one</option><option>Pathology lab</option><option>Diagnostic and imaging center</option><option>Multi-center lab chain</option><option>Hospital laboratory</option><option>Home collection service</option><option>Cardiology or ECG clinic</option><option>Other</option></select>
            <span class="err" id="f-lab-err">Choose a type of lab.</span></div>
          <div class="field field--full"><label for="f-size">Number of centers</label>
            <select id="f-size" name="centers" required><option value="">Select one</option><option>1</option><option>2–5</option><option>6–20</option><option>More than 20</option></select>
            <span class="err" id="f-size-err">Choose the number of centers.</span></div>
          <div class="field field--full"><label for="f-msg">What would you like to see? <span class="opt">(optional)</span></label><textarea id="f-msg" name="message" rows="3"></textarea></div>
        </div>
        <div class="hp" aria-hidden="true"><label for="f-website">Leave this field empty</label><input id="f-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
        <div class="form-submit"><button class="btn btn--primary btn--lg" type="submit">Book a demo</button></div>
        <p class="form-error" id="form-error" role="alert"></p>
        <p class="form-note">By submitting, you agree to our <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">privacy policy</a>.</p>
      </form>
      <div class="form-status" id="form-status" role="status" aria-live="polite" tabindex="-1">
        <span class="ok-icon"><svg class="icon icon-lg" aria-hidden="true"><use href="#i-check"/></svg></span>
        <h3>Demo request received</h3>
        <p>Thanks. We'll email you shortly to schedule a time that works for your team.</p>
      </div>
    </div>
