<?php
/**
 * Phone numbers: validation and normalization to E.164 (+15125550123).
 *
 * Accepted input (US-first, international allowed):
 *  - US/Canada (NANP) without a country code: (512) 555-0123, 512.555.0123, 512 555 0123, 5125550123
 *  - With the US country code: 1 512 555 0123, +1 (512) 555-0123
 *  - International with a "+": +44 20 7946 0958, +91 98765 43210 (8 to 15 digits, E.164)
 *  - An extension may follow: "x123", "ext 123", "ext. 123", "#123"; it is kept as " ext. 123"
 * NANP numbers must have a valid area code and exchange (both start with 2-9, and neither is an N11 service code).
 *
 * @package Labora_Forms
 */

defined( 'ABSPATH' ) || exit;

final class Labora_Forms_Phone {

	/**
	 * Normalize a phone number.
	 *
	 * @return array{valid: bool, e164: string, display: string, ext: string, reason: string}
	 *   e164 is the number alone (+15125550123); display adds the extension (" ext. 123") when there is one.
	 */
	public static function normalize( string $input ): array {
		$out   = array( 'valid' => false, 'e164' => '', 'display' => '', 'ext' => '', 'reason' => '' );
		$input = trim( wp_strip_all_tags( $input ) );
		if ( '' === $input ) {
			$out['reason'] = 'empty';
			return $out;
		}

		// Split off an extension: x123, ext 123, ext. 123, extension 123, #123
		if ( preg_match( '/^(.*?)(?:\s*(?:ext(?:ension)?\.?|x|#)\s*(\d{1,6}))\s*$/i', $input, $m ) ) {
			$input      = $m[1];
			$out['ext'] = $m[2];
		}

		// Only digits, spaces, and common separators are allowed (no letters such as 1-800-FLOWERS)
		if ( ! preg_match( '/^\+?[\d\s().\-\/]+$/', $input ) ) {
			$out['reason'] = 'characters';
			return $out;
		}

		$has_plus = str_starts_with( ltrim( $input ), '+' );
		$digits   = preg_replace( '/\D/', '', $input );

		if ( $has_plus ) {
			if ( strlen( $digits ) < 8 || strlen( $digits ) > 15 || '0' === $digits[0] ) {
				$out['reason'] = 'length';
				return $out;
			}
			if ( '1' === $digits[0] && ! self::valid_nanp( substr( $digits, 1 ) ) ) {
				$out['reason'] = 'nanp';
				return $out;
			}
			$e164 = '+' . $digits;
		} else {
			if ( 11 === strlen( $digits ) && '1' === $digits[0] ) {
				$digits = substr( $digits, 1 );
			}
			if ( 10 !== strlen( $digits ) ) {
				// Without a "+", only US/Canada numbers are accepted: other countries need their code
				$out['reason'] = strlen( $digits ) > 10 ? 'country_code' : 'length';
				return $out;
			}
			if ( ! self::valid_nanp( $digits ) ) {
				$out['reason'] = 'nanp';
				return $out;
			}
			$e164 = '+1' . $digits;
		}

		/**
		 * Final say on a phone number, e.g. to allow only US numbers:
		 * add_filter( 'labora_forms_phone_valid', fn( $ok, $e164 ) => $ok && str_starts_with( $e164, '+1' ), 10, 2 );
		 */
		if ( ! apply_filters( 'labora_forms_phone_valid', true, $e164, $input ) ) {
			$out['reason'] = 'filtered';
			return $out;
		}

		$out['valid']   = true;
		$out['e164']    = $e164;
		$out['display'] = $e164 . ( '' !== $out['ext'] ? ' ext. ' . $out['ext'] : '' );
		return $out;
	}

	/** A 10-digit US/Canada number: area code and exchange start with 2-9 and are not N11 codes (211-911). */
	private static function valid_nanp( string $ten ): bool {
		if ( ! preg_match( '/^([2-9]\d{2})([2-9]\d{2})\d{4}$/', $ten, $m ) ) {
			return false;
		}
		foreach ( array( $m[1], $m[2] ) as $code ) {
			if ( '11' === substr( $code, 1 ) ) {
				return false;
			}
		}
		return true;
	}

	/** Message for an invalid number. */
	public static function message( string $reason ): string {
		switch ( $reason ) {
			case 'country_code':
				return __( 'Add your country code with a +, for example +44 20 7946 0958.', 'labora-forms' );
			case 'characters':
				return __( 'Use digits only, for example (512) 555-0123.', 'labora-forms' );
			default:
				return __( 'Enter a valid phone number, for example (512) 555-0123 or +44 20 7946 0958.', 'labora-forms' );
		}
	}
}
