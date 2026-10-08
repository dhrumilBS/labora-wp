<?php
/**
 * Phone numbers: validation and normalization to E.164 (+15125550123).
 *
 *  - With "+": any country, 8 to 15 digits (E.164). +1 numbers must follow US/Canada (NANP) rules.
 *  - Without "+": US/Canada (10 digits, or 11 starting with 1) when the setting allows it; otherwise rejected.
 *  - NANP: area code and exchange start with 2-9 and are not N11 service codes (211-911).
 *  - Extensions (x123, ext 123, ext. 123, #123) are kept: "+15125550123 ext. 123".
 *  - Letters (1-800-FLOWERS) are rejected.
 * Filter: lead_guard_cf7_phone_valid( bool $ok, string $e164, string $input ).
 *
 * @package Lead_Guard_CF7
 */

defined( 'ABSPATH' ) || exit;

final class Lead_Guard_Phone {

	/** @return array{valid: bool, e164: string, display: string, reason: string} reason: country | invalid */
	public static function normalize( string $input, bool $allow_nanp = true ): array {
		$out   = array( 'valid' => false, 'e164' => '', 'display' => '', 'reason' => 'invalid' );
		$input = trim( wp_strip_all_tags( $input ) );
		$ext   = '';
		if ( preg_match( '/^(.*?)(?:\s*(?:ext(?:ension)?\.?|x|#)\s*(\d{1,6}))\s*$/i', $input, $m ) ) {
			$input = $m[1];
			$ext   = $m[2];
		}
		if ( '' === $input || ! preg_match( '/^\+?[\d\s().\-\/]+$/', $input ) ) {
			return $out;
		}
		$digits = preg_replace( '/\D/', '', $input );

		if ( str_starts_with( $input, '+' ) ) {
			if ( strlen( $digits ) < 8 || strlen( $digits ) > 15 || '0' === $digits[0] ) {
				return $out;
			}
			if ( '1' === $digits[0] && ! self::nanp( substr( $digits, 1 ) ) ) {
				return $out;
			}
			$e164 = '+' . $digits;
		} else {
			if ( ! $allow_nanp ) {
				$out['reason'] = 'country';
				return $out;
			}
			if ( 11 === strlen( $digits ) && '1' === $digits[0] ) {
				$digits = substr( $digits, 1 );
			}
			if ( 10 !== strlen( $digits ) ) {
				$out['reason'] = strlen( $digits ) > 10 ? 'country' : 'invalid';
				return $out;
			}
			if ( ! self::nanp( $digits ) ) {
				return $out;
			}
			$e164 = '+1' . $digits;
		}

		if ( ! apply_filters( 'lead_guard_cf7_phone_valid', true, $e164, $input ) ) {
			return $out;
		}
		return array(
			'valid'   => true,
			'e164'    => $e164,
			'display' => $e164 . ( '' !== $ext ? ' ext. ' . $ext : '' ),
			'reason'  => '',
		);
	}

	private static function nanp( string $ten ): bool {
		if ( ! preg_match( '/^([2-9]\d{2})([2-9]\d{2})\d{4}$/', $ten, $m ) ) {
			return false;
		}
		return '11' !== substr( $m[1], 1 ) && '11' !== substr( $m[2], 1 );
	}
}
