<?php
/**
 * Integrations with plugins that set cookies of their own.
 *
 * HandL UTM Grabber (v3): it stores the visit's source (UTM parameters, referrer, browser) in cookies and fills
 * them into forms. With TrustLayer active it never sets cookies on the server (its "is_ok_to_capture_utms" consent
 * filter answers no, which also keeps cached pages the same for every visitor); in the browser it starts once the
 * visitor's consent allows its category (Analytics by default; filter: tlc_handl_category), straight away where the
 * consent model allows it before a choice (opt-out regions). Declare its cookies (e.g. user_agent, utm_*) under that
 * category, so they are listed and removed when it is switched off.
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Integrations {

	public static function init(): void {
		if ( function_exists( 'HandLCookieConsented' ) || defined( 'HANDL_UTM_V3_LINK' ) || has_action( 'init', 'CaptureUTMs' ) ) {
			self::handl();
		} else {
			add_action( 'plugins_loaded', function () {
				if ( has_action( 'init', 'CaptureUTMs' ) ) {
					TLC_Integrations::handl();
				}
			}, 20 );
		}
	}

	/** HandL UTM Grabber: no cookies until its category is allowed. */
	public static function handl(): void {
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;

		add_filter( 'is_ok_to_capture_utms', function ( $good2go ) {
			if ( TLC_Frontend::is_active() ) {
				$good2go['good2go'] = 0; // the browser starts it after consent (below)
			}
			return $good2go;
		}, 99 );

		add_action( 'wp_enqueue_scripts', function () {
			if ( ! TLC_Frontend::is_active() || ! wp_script_is( 'handl-utm-grabber', 'enqueued' ) ) {
				return;
			}
			$category = (string) apply_filters( 'tlc_handl_category', 'analytics' );
			wp_add_inline_script( 'handl-utm-grabber', sprintf(
				'(function(c){var ran=false;function go(d){if(ran||!d||!d.categories||!d.categories[c]||typeof RunHandL!=="function")return;ran=true;RunHandL();}'
				. 'document.addEventListener("tlc:consent",function(e){go(e.detail)});'
				. 'if(window.TrustLayer&&TrustLayer.ready)TrustLayer.ready(function(t){var s=t.get();go(s?{categories:s.categories}:{categories:(function(){var o={};o[c]=t.has(c);return o})()})});})(%s);',
				wp_json_encode( $category )
			) );
		}, 20 );
	}
}
