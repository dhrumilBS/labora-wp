<?php
/**
 * Public PHP helpers for themes and other plugins.
 *
 * Note on page caching: consent is a per-visitor cookie, so pages served from a full-page cache cannot vary on it.
 * Prefer the browser-side API (window.TrustLayer, see README) or blocked scripts for anything consent-dependent;
 * use these helpers for uncached responses (logged-in users, REST and AJAX requests, server-side tracking).
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

/**
 * A setting by "section.key" path, after developer overrides.
 *
 * @example tlc_setting( 'general.cookie_name' )
 */
function tlc_setting( string $path, $fallback = null ) {
	return TLC_Settings::value( $path, $fallback );
}

/**
 * The visitor's stored consent, or null when they have not chosen yet (or the choice is from an older revision).
 *
 * @return array{id:string,v:int,ts:int,c:array<string,int>,a:string,m:string,r:string}|null
 */
function tlc_get_consent(): ?array {
	$name = (string) tlc_setting( 'general.cookie_name' );
	if ( empty( $_COOKIE[ $name ] ) ) {
		return null;
	}
	$data = json_decode( wp_unslash( (string) $_COOKIE[ $name ] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! is_array( $data ) || (int) ( $data['v'] ?? 0 ) !== (int) tlc_setting( 'general.revision' ) || ! is_array( $data['c'] ?? null ) ) {
		return null;
	}
	return $data;
}

/**
 * True when the visitor allowed a category. Locked categories (strictly necessary) are always allowed.
 * Without a stored choice this returns false for every other category (the safe answer for opt-in regions).
 */
function tlc_has_consent( string $category ): bool {
	foreach ( TLC_Settings::categories() as $cat ) {
		if ( $cat['id'] === $category && ! empty( $cat['locked'] ) ) {
			return true;
		}
	}
	$consent = tlc_get_consent();
	return $consent && ! empty( $consent['c'][ $category ] );
}

/** Enabled cookie categories (id, title, description, locked, sale_share, gcm, cookies). */
function tlc_categories(): array {
	return TLC_Settings::categories();
}

/**
 * Print markup that only loads after consent for a category, e.g. a tracking snippet from a theme:
 *   tlc_gated_html( 'analytics', '<script src="https://example.com/a.js"></script>' );
 */
function tlc_gated_html( string $category, string $html ): string {
	return TLC_Blocker::gate( $html, $category );
}
