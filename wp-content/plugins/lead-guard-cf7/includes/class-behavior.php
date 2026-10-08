<?php
/**
 * Form behavior in the browser, set per form in Additional Settings:
 *
 *   lead_guard_redirect: /thank-you/   after a successful send, open this page (path on this site, or a full URL)
 *   lead_guard_delay: 1500             milliseconds to wait before the redirect (default 1500, 0 = at once)
 *   lead_guard_event: form_submitted   push { event: ... } to window.dataLayer (Google Tag Manager) after a send
 *
 * Also, on every form:
 *  - The form's action URL has no "#wpcf7-f123-o1" fragment, so a submission never adds it to the address bar
 *    or jumps the page to the form when the browser submits without JavaScript.
 *  - After a successful send, the closest element with data-lead-guard-wrap gets the class "is-sent", and an element
 *    inside it with data-lead-guard-status is shown, so a theme can swap the form for its own confirmation panel.
 *  - Before redirecting, sessionStorage "lead_guard_sent" holds { form, id, name, topic, ts } for a thank-you page
 *    (never the URL, so no personal data reaches server logs or analytics).
 *
 * @package Lead_Guard_CF7
 */

defined( 'ABSPATH' ) || exit;

final class Lead_Guard_Behavior {

	public static function init(): void {
		add_filter( 'wpcf7_form_action_url', array( __CLASS__, 'action_url' ) );
		add_filter( 'wpcf7_form_additional_atts', array( __CLASS__, 'form_atts' ) );
		add_action( 'wpcf7_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/** No "#wpcf7-..." fragment on the form's action. */
	public static function action_url( $url ) {
		$hash = strpos( (string) $url, '#' );
		return false === $hash ? $url : substr( $url, 0, $hash );
	}

	/** First value of an Additional Settings line, or ''. */
	private static function setting( WPCF7_ContactForm $form, string $name ): string {
		$values = $form->additional_setting( $name, 1 );
		return $values ? trim( (string) $values[0] ) : '';
	}

	/** data-lg-* attributes read by assets/lead-guard.js. */
	public static function form_atts( $atts ) {
		$form = WPCF7_ContactForm::get_current();
		if ( ! $form ) {
			return $atts;
		}
		$atts['data-lg-form'] = sanitize_title( $form->title() );
		$redirect             = self::setting( $form, 'lead_guard_redirect' );
		if ( '' !== $redirect ) {
			$atts['data-lg-redirect'] = esc_url( preg_match( '#^https?://#i', $redirect ) ? $redirect : home_url( '/' . ltrim( $redirect, '/' ) ) );
			$delay                    = self::setting( $form, 'lead_guard_delay' );
			$atts['data-lg-delay']    = (string) ( '' === $delay ? 1500 : max( 0, (int) $delay ) );
		}
		$event = self::setting( $form, 'lead_guard_event' );
		if ( '' !== $event ) {
			$atts['data-lg-event'] = sanitize_key( $event );
		}
		return $atts;
	}

	/** Loaded together with Contact Form 7's own script, wherever CF7 loads it. */
	public static function enqueue(): void {
		$file = dirname( LEAD_GUARD_CF7_FILE ) . '/assets/lead-guard.js';
		wp_enqueue_script( 'lead-guard-cf7', plugins_url( 'assets/lead-guard.js', LEAD_GUARD_CF7_FILE ), array( 'contact-form-7' ), (string) filemtime( $file ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
}
