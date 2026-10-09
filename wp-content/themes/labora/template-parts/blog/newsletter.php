<?php
/**
 * Newsletter box (blog page and posts). Texts: Labora Settings > Newsletter.
 * The form comes from the "labora_newsletter_form" filter (the child theme returns its Contact Form 7 form);
 * without it, the HTML site's demo form is shown (it validates and confirms, but saves nothing).
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

$labora_form = (string) apply_filters( 'labora_newsletter_form', '' );
$labora_privacy = get_privacy_policy_url() ?: home_url( '/privacy/' );
?>
<section class="newsletter reveal" aria-labelledby="nl-title">
      <div>
        <h2 id="nl-title"><?php echo esc_html( labora_setting( 'newsletter_title' ) ); ?></h2>
        <p><?php echo esc_html( labora_setting( 'newsletter_text' ) ); ?></p>
      </div>
      <div>
<?php if ( '' !== $labora_form ) : ?>
        <?php echo $labora_form; // phpcs:ignore WordPress.Security.EscapeOutput -- form markup ?>
<?php else : ?>
        <form class="nl-form" data-newsletter data-endpoint="" novalidate>
          <label class="sr-only" for="nl-email"><?php esc_html_e( 'Work email', 'labora' ); ?></label>
          <input id="nl-email" name="email" type="email" autocomplete="email" placeholder="you@yourlab.com" required>
          <button class="btn btn--lg" type="submit"><?php esc_html_e( 'Subscribe', 'labora' ); ?></button>
        </form>
        <p class="nl-msg" role="status" aria-live="polite"></p>
<?php endif; ?>
        <p class="nl-note"><?php printf( wp_kses( __( 'By subscribing, you agree to our <a href="%s" style="color:inherit">privacy policy</a>.', 'labora' ), array( 'a' => array( 'href' => true, 'style' => true ) ) ), esc_url( $labora_privacy ) ); ?></p>
      </div>
    </section>
