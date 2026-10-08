<?php
/**
 * Email addresses: validation and normalization.
 *
 * Checks, in order:
 *  1. Format: one @, a real domain with a dot, no spaces, 254 characters at most (WordPress is_email()).
 *  2. Temporary inboxes (mailinator.com, guerrillamail.com ...) are rejected: these forms ask for a work email.
 *  3. The domain can receive mail: it has an MX record, or an A/AAAA record as a fallback (RFC 5321).
 *     Results are cached for a day. If the DNS lookup itself is unavailable, the address is accepted.
 * Normalized form: trimmed, with the domain lowercased (the part before @ is kept as typed).
 *
 * Filters: labora_forms_email_dns_check (bool), labora_forms_disposable_domains (array).
 *
 * @package Labora_Forms
 */

defined( 'ABSPATH' ) || exit;

final class Labora_Forms_Email {

	/** Common temporary-inbox domains. Extend with the labora_forms_disposable_domains filter. */
	private const DISPOSABLE = array(
		'mailinator.com', 'guerrillamail.com', 'guerrillamail.net', 'sharklasers.com', '10minutemail.com', 'tempmail.com',
		'temp-mail.org', 'throwawaymail.com', 'yopmail.com', 'getnada.com', 'trashmail.com', 'maildrop.cc', 'dispostable.com',
		'fakeinbox.com', 'mintemail.com', 'emailondeck.com', 'mohmal.com', 'tempinbox.com', 'mailnesia.com', 'spamgourmet.com',
	);

	/**
	 * @return array{valid: bool, email: string, reason: string}
	 */
	public static function normalize( string $input ): array {
		$email = trim( $input );
		$out   = array( 'valid' => false, 'email' => $email, 'reason' => '' );

		if ( '' === $email ) {
			$out['reason'] = 'empty';
			return $out;
		}
		$at = strrpos( $email, '@' );
		if ( false === $at || strlen( $email ) > 254 || ! is_email( $email ) ) {
			$out['reason'] = 'format';
			return $out;
		}
		$domain       = strtolower( substr( $email, $at + 1 ) );
		$email        = substr( $email, 0, $at ) . '@' . $domain;
		$out['email'] = $email;

		$disposable = (array) apply_filters( 'labora_forms_disposable_domains', self::DISPOSABLE );
		foreach ( $disposable as $bad ) {
			if ( $domain === $bad || str_ends_with( $domain, '.' . $bad ) ) {
				$out['reason'] = 'disposable';
				return $out;
			}
		}

		if ( apply_filters( 'labora_forms_email_dns_check', true ) && ! self::domain_accepts_mail( $domain ) ) {
			$out['reason'] = 'domain';
			return $out;
		}

		$out['valid'] = true;
		return $out;
	}

	/** MX record, or A/AAAA as the implicit mail host. Cached per domain for a day. */
	private static function domain_accepts_mail( string $domain ): bool {
		$key    = 'lbrf_dns_' . md5( $domain );
		$cached = get_transient( $key );
		if ( false !== $cached ) {
			return 'yes' === $cached;
		}
		if ( ! function_exists( 'checkdnsrr' ) ) {
			return true; // cannot check: accept
		}
		$ascii = function_exists( 'idn_to_ascii' ) ? ( idn_to_ascii( $domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46 ) ?: $domain ) : $domain;
		$ok    = checkdnsrr( $ascii . '.', 'MX' ) || checkdnsrr( $ascii . '.', 'A' ) || checkdnsrr( $ascii . '.', 'AAAA' );
		if ( ! $ok && ! checkdnsrr( 'wordpress.org.', 'A' ) ) {
			return true; // DNS itself is unavailable (offline server): do not block real visitors
		}
		set_transient( $key, $ok ? 'yes' : 'no', DAY_IN_SECONDS );
		return $ok;
	}

	/** Message for an invalid address. */
	public static function message( string $reason ): string {
		switch ( $reason ) {
			case 'disposable':
				return __( 'Please use your work email. Temporary email addresses are not accepted.', 'labora-forms' );
			case 'domain':
				return __( 'We could not find this email domain. Check the part after the @.', 'labora-forms' );
			default:
				return __( 'Enter a valid email address, for example name@yourlab.com.', 'labora-forms' );
		}
	}
}
