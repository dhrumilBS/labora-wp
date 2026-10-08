<?php
/**
 * Email addresses: format (WordPress is_email), temporary inboxes, free providers (optional), and a domain that can
 * receive mail (MX, or A/AAAA). The domain part is lowercased. DNS results are cached for a day; if DNS itself is
 * unreachable, the address is accepted rather than blocking real visitors.
 *
 * @package Lead_Guard_CF7
 */

defined( 'ABSPATH' ) || exit;

final class Lead_Guard_Email {

	/** Default temporary-inbox domains (editable on the settings page). */
	const DISPOSABLE = array(
		'mailinator.com', 'guerrillamail.com', 'guerrillamail.net', 'sharklasers.com', '10minutemail.com', 'tempmail.com',
		'temp-mail.org', 'throwawaymail.com', 'yopmail.com', 'getnada.com', 'trashmail.com', 'maildrop.cc', 'dispostable.com',
		'fakeinbox.com', 'mintemail.com', 'emailondeck.com', 'mohmal.com', 'tempinbox.com', 'mailnesia.com', 'spamgourmet.com',
	);

	/** Default free providers, used only when "work email only" is on. */
	const FREE = array(
		'gmail.com', 'googlemail.com', 'yahoo.com', 'ymail.com', 'outlook.com', 'hotmail.com', 'live.com', 'msn.com',
		'aol.com', 'icloud.com', 'me.com', 'mac.com', 'proton.me', 'protonmail.com', 'gmx.com', 'mail.com', 'zoho.com', 'yandex.com',
	);

	/** @return array{valid: bool, email: string, reason: string} reason: email | email_disposable | email_free | email_domain */
	public static function normalize( string $input ): array {
		$email = trim( $input );
		$at    = strrpos( $email, '@' );
		if ( false === $at || strlen( $email ) > 254 || ! is_email( $email ) ) {
			return array( 'valid' => false, 'email' => $email, 'reason' => 'email' );
		}
		$domain = strtolower( substr( $email, $at + 1 ) );
		$email  = substr( $email, 0, $at ) . '@' . $domain;

		if ( Lead_Guard_Settings::get( 'email_disposable' ) && self::listed( $domain, Lead_Guard_Settings::domains( 'disposable_domains' ) ) ) {
			return array( 'valid' => false, 'email' => $email, 'reason' => 'email_disposable' );
		}
		if ( Lead_Guard_Settings::get( 'email_free' ) && self::listed( $domain, Lead_Guard_Settings::domains( 'free_domains' ) ) ) {
			return array( 'valid' => false, 'email' => $email, 'reason' => 'email_free' );
		}
		if ( Lead_Guard_Settings::get( 'email_dns' ) && ! self::receives_mail( $domain ) ) {
			return array( 'valid' => false, 'email' => $email, 'reason' => 'email_domain' );
		}
		return array( 'valid' => true, 'email' => $email, 'reason' => '' );
	}

	private static function listed( string $domain, array $list ): bool {
		foreach ( $list as $d ) {
			if ( $domain === $d || str_ends_with( $domain, '.' . $d ) ) {
				return true;
			}
		}
		return false;
	}

	private static function receives_mail( string $domain ): bool {
		$key    = 'lgcf7_dns_' . md5( $domain );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return 'yes' === $cached;
		}
		if ( ! function_exists( 'checkdnsrr' ) ) {
			return true;
		}
		$ascii = function_exists( 'idn_to_ascii' ) ? ( idn_to_ascii( $domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46 ) ?: $domain ) : $domain;
		$ok    = checkdnsrr( $ascii . '.', 'MX' ) || checkdnsrr( $ascii . '.', 'A' ) || checkdnsrr( $ascii . '.', 'AAAA' );
		if ( ! $ok && ! checkdnsrr( 'wordpress.org.', 'A' ) ) {
			return true; // DNS unavailable on this server: do not block real visitors
		}
		set_transient( $key, $ok ? 'yes' : 'no', DAY_IN_SECONDS );
		return $ok;
	}
}
