<?php
/**
 * WordPress privacy tools: suggested text for the privacy policy guide (Settings > Privacy > Policy Guide).
 * The consent log holds no names or emails, so there is nothing to export or erase per person by email;
 * a visitor can quote the consent ID shown by [tlc_consent_details], and the log can be searched by it.
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Privacy {

	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'policy_text' ) );
	}

	public static function policy_text(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		$days = (int) tlc_setting( 'logs.retention', 365 );
		$text = '<p class="privacy-policy-tutorial">' . esc_html__( 'TrustLayer Consent stores each visitor\'s cookie choices and keeps a log of them as proof of consent.', 'trustlayer-consent' ) . '</p>'
			. '<p><strong class="privacy-policy-tutorial">' . esc_html__( 'Suggested text:', 'trustlayer-consent' ) . '</strong> '
			/* translators: 1: cookie name, 2: how long the cookie is kept, 3: days the log is kept */
			. esc_html( sprintf( __( 'We store your cookie choices in a cookie named %1$s, kept for %2$s, together with a random consent ID. To be able to show that you gave or refused consent, we keep a record of each choice (the consent ID, the date, the choices, the page, and, if enabled, a shortened IP address and your browser type) for %3$d days.', 'trustlayer-consent' ), tlc_setting( 'general.cookie_name' ), TLC_Settings::human_days( (int) tlc_setting( 'general.cookie_days' ) ), $days ) )
			. ' ' . esc_html__( 'If your browser sends a Global Privacy Control signal, we treat it as a request to opt out of the sale or sharing of your personal information.', 'trustlayer-consent' ) . '</p>';
		wp_add_privacy_policy_content( 'TrustLayer Consent', wp_kses_post( $text ) );
	}
}
