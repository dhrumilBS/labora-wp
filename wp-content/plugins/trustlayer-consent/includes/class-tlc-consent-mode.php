<?php
/**
 * Google Consent Mode v2 (and Microsoft UET consent mode).
 *
 * In <head>, before any Google tag:
 *   1. gtag( 'consent', 'default', ... ) per region: denied where the model is opt-in, granted where it is
 *      opt-out, notice, or none; then one default without a region for everywhere else. Google applies the most
 *      specific region, so the defaults are right before the browser knows where it is.
 *   2. If the visitor already chose: gtag( 'consent', 'update', ... ) from the saved cookie, in the same script.
 *      With Global Privacy Control and no saved choice, the advertising signals are updated to denied.
 * The banner script sends further updates when the visitor chooses. Each category grants the signals ticked for
 * it in the Categories tab (security_storage is always granted).
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Consent_Mode {

	const AD_SIGNALS = array( 'ad_storage', 'ad_user_data', 'ad_personalization' );

	/** Signal => granted|denied for a model, before any choice. Filter: tlc_consent_mode_defaults. */
	public static function defaults_for( string $model ): array {
		$out = array();
		foreach ( TLC_Settings::GCM_SIGNALS as $signal ) {
			$out[ $signal ] = ( 'security_storage' === $signal || 'opt-in' !== $model ) ? 'granted' : 'denied';
		}
		return (array) apply_filters( 'tlc_consent_mode_defaults', $out, $model );
	}

	/** The inline JavaScript printed in <head> (appended to TLC_Frontend's script). */
	public static function script( array $config ): string {
		$js     = '';
		$wait   = (int) tlc_setting( 'consent_mode.wait', 500 );
		$models = (array) tlc_setting( 'geo.models', array() );

		if ( $config['gcm'] ) {
			$js .= 'window.dataLayer=window.dataLayer||[];window.gtag=window.gtag||function(){dataLayer.push(arguments)};';
			foreach ( TLC_Geo::gcm_regions() as $model => $regions ) {
				$cmd           = self::defaults_for( $model );
				$cmd['region'] = array_values( array_unique( $regions ) );
				if ( 'opt-in' === $model && $wait ) {
					$cmd['wait_for_update'] = $wait;
				}
				$js .= "gtag('consent','default'," . wp_json_encode( $cmd ) . ');';
			}
			$global = tlc_setting( 'geo.enabled' ) ? (string) ( $models['default'] ?? 'opt-in' ) : (string) $config['model'];
			$cmd    = self::defaults_for( $global );
			if ( 'opt-in' === $global && $wait ) {
				$cmd['wait_for_update'] = $wait;
			}
			$js .= "gtag('consent','default'," . wp_json_encode( $cmd ) . ');';
			if ( tlc_setting( 'consent_mode.ads_data_redaction' ) ) {
				$js .= "gtag('set','ads_data_redaction',true);";
			}
			if ( tlc_setting( 'consent_mode.url_passthrough' ) ) {
				$js .= "gtag('set','url_passthrough',true);";
			}
		}
		if ( $config['uet'] ) {
			$global = tlc_setting( 'geo.enabled' ) ? (string) ( $models['default'] ?? 'opt-in' ) : (string) $config['model'];
			$js    .= "window.uetq=window.uetq||[];window.uetq.push('consent','default',{ad_storage:'" . ( 'opt-in' === $global ? 'denied' : 'granted' ) . "'});";
		}

		// Saved choice: update at once. Same cookie format as consent.js writes.
		$ad = wp_json_encode( self::AD_SIGNALS );
		$js .= '(function(c){try{'
			. 'var m=document.cookie.match(new RegExp("(?:^|; )"+c.cookie.name+"=([^;]*)")),s=m&&JSON.parse(decodeURIComponent(m[1])),u={};'
			. 'if(s&&s.v===c.revision&&s.c){c.categories.forEach(function(k){k.gcm.forEach(function(g){u[g]=u[g]==="granted"||k.locked||s.c[k.id]?"granted":"denied"})})}'
			. 'else if(c.gpc&&navigator.globalPrivacyControl){' . $ad . '.forEach(function(g){u[g]="denied"})}'
			. 'else return;'
			. 'if(c.gcm)gtag("consent","update",u);'
			. 'if(c.uet&&u.ad_storage)window.uetq.push("consent","update",{ad_storage:u.ad_storage})'
			. '}catch(e){}})(window.TrustLayerConfig);';
		return $js;
	}
}
