<?php
/**
 * TrustLayer Consent, set up for Labora: the banner keeps the site's design and wording (the parent theme's own
 * banner steps aside while the plugin is active).
 *
 * These are defaults (filter tlc_defaults): anything changed in TrustLayer > Settings wins over them, and
 * TrustLayer > Tools > Reset goes back to them. Styling is in assets/css/child.css ("TrustLayer Consent").
 *
 * @package Labora_Child
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'tlc_defaults', function ( array $d ): array {
	// The footer's "Cookie settings" button (footer.php) opens the preferences
	$d['general']['open_selectors'] = '[data-cookie-settings]';

	// Bottom-left card with the theme's buttons, like the built-in banner
	$d['banner']['layout']              = 'box-left';
	$d['banner']['btn_primary_class']   = 'btn btn--primary';
	$d['banner']['btn_secondary_class'] = 'btn btn--ghost';
	$d['banner']['z_index']             = 95; // above the mobile CTA bar (90), below the header (100)

	$d['policy']['cookie_url'] = home_url( '/cookies/' ); // the Cookie policy page, once it is published

	$d['texts'] = array_merge( $d['texts'], array(
		'banner_text'    => 'We use one cookie to remember this choice. With your permission, we may also use analytics and marketing cookies.',
		'reject_all'     => 'Deny all',
		'prefs_text'     => 'Choose what we may store. You can change this at any time from "Cookie settings" at the bottom of every page.',
	) );

	// The site uses no functional cookies today: hide the category until it does
	foreach ( $d['categories'] as &$cat ) {
		if ( 'functional' === $cat['id'] ) {
			$cat['enabled'] = 0;
		}
	}
	unset( $cat );
	return $d;
} );
