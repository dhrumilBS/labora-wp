<?php
/**
 * Geo-targeting: where the visitor is, and which consent model applies there.
 *
 * Location sources, in order (the first that answers wins):
 *   1. CDN or server headers: Cloudflare (CF-IPCountry, CF-Region-Code), Amazon CloudFront, Vercel, Google App
 *      Engine, Fastly/nginx/Apache GeoIP (X-Country-Code, GEOIP_COUNTRY_CODE), or a custom header.
 *   2. A MaxMind GeoLite2 / GeoIP2 database (.mmdb), read locally; nothing is sent anywhere.
 *   3. The "tlc_geo_lookup" filter, for any other service.
 * The browser asks for its region once per session (REST: /trustlayer/v1/region), so pages stay cacheable.
 *
 * Region groups, each with its own consent model (Regions tab): eu (EU, EEA, Switzerland), gb (United Kingdom),
 * us_states (US states with comprehensive privacy laws), us (rest of the US), ca (Canada), br (Brazil),
 * default (everywhere else), unknown (location not found). Per-country or per-state overrides win over groups.
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Geo {

	/** EU and EEA member states, plus Switzerland. */
	const EU = array( 'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'IS', 'LI', 'NO', 'CH' );

	/** United Kingdom and Crown Dependencies. */
	const GB = array( 'GB', 'UK', 'GG', 'JE', 'IM' );

	/**
	 * US states with a comprehensive consumer privacy law (opt-out of sale, sharing, and targeted advertising),
	 * in effect or signed as of 2026. Filter: tlc_us_privacy_states.
	 */
	const US_PRIVACY_STATES = array( 'CA', 'VA', 'CO', 'CT', 'UT', 'TX', 'OR', 'MT', 'IA', 'DE', 'NH', 'NJ', 'TN', 'MN', 'MD', 'IN', 'KY', 'RI', 'NE' );

	/** Codes that mean "not a country": unknown, Tor, anonymous proxy, satellite, other, continent-level. */
	const UNKNOWN = array( 'XX', 'T1', 'A1', 'A2', 'O1', 'EU', 'AP' );

	/** Headers checked for the country, then the region (state). */
	const COUNTRY_HEADERS = array( 'HTTP_CF_IPCOUNTRY', 'HTTP_CLOUDFRONT_VIEWER_COUNTRY', 'HTTP_X_VERCEL_IP_COUNTRY', 'HTTP_X_APPENGINE_COUNTRY', 'HTTP_X_COUNTRY_CODE', 'HTTP_X_GEO_COUNTRY', 'GEOIP_COUNTRY_CODE', 'HTTP_GEOIP_COUNTRY_CODE' );
	const REGION_HEADERS  = array( 'HTTP_CF_REGION_CODE', 'HTTP_CLOUDFRONT_VIEWER_COUNTRY_REGION', 'HTTP_X_VERCEL_IP_COUNTRY_REGION', 'HTTP_X_APPENGINE_REGION', 'HTTP_X_REGION_CODE', 'HTTP_X_GEO_REGION', 'GEOIP_REGION', 'HTTP_GEOIP_REGION' );

	public static function group_labels(): array {
		return array(
			'eu'        => __( 'European Union, EEA, and Switzerland', 'trustlayer-consent' ),
			'gb'        => __( 'United Kingdom', 'trustlayer-consent' ),
			'us_states' => __( 'US states with privacy laws (California, Virginia, Colorado, and others)', 'trustlayer-consent' ),
			'us'        => __( 'Rest of the United States', 'trustlayer-consent' ),
			'ca'        => __( 'Canada', 'trustlayer-consent' ),
			'br'        => __( 'Brazil', 'trustlayer-consent' ),
			'default'   => __( 'Everywhere else', 'trustlayer-consent' ),
			'unknown'   => __( 'Location not found', 'trustlayer-consent' ),
		);
	}

	public static function model_labels(): array {
		return array(
			'opt-in'  => __( 'Opt-in: ask first, nothing optional runs until accepted (GDPR style)', 'trustlayer-consent' ),
			'opt-out' => __( 'Opt-out: optional cookies run, with notice and Do Not Sell or Share (US style)', 'trustlayer-consent' ),
			'notice'  => __( 'Notice only: inform, everything runs', 'trustlayer-consent' ),
			'none'    => __( 'No banner: everything runs', 'trustlayer-consent' ),
		);
	}

	/** True when visitors in different places can get different models, so the browser must ask. */
	public static function is_needed(): bool {
		if ( ! tlc_setting( 'geo.enabled' ) ) {
			return false;
		}
		$models = array_values( (array) tlc_setting( 'geo.models', array() ) );
		foreach ( self::overrides() as $model ) {
			$models[] = $model;
		}
		return count( array_unique( $models ) ) > 1;
	}

	/**
	 * Where the current visitor is.
	 *
	 * @return array{country:string,region:string,source:string}
	 */
	public static function locate( ?string $ip = null ): array {
		$found = array( 'country' => '', 'region' => '', 'source' => '' );

		if ( null === $ip && tlc_setting( 'geo.headers' ) ) {
			$found = self::from_headers();
		}
		if ( '' === $found['country'] && tlc_setting( 'geo.maxmind' ) ) {
			$found = self::from_maxmind( $ip ?? self::client_ip() );
		}
		/**
		 * Look the visitor up another way, or correct the result.
		 *
		 * @param array  $found { country: 'US', region: 'CA', source: 'header' } (empty strings when not found)
		 * @param string $ip
		 */
		$found = (array) apply_filters( 'tlc_geo_lookup', $found, $ip ?? self::client_ip() );

		$country = strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) ( $found['country'] ?? '' ) ), 0, 2 ) );
		if ( in_array( $country, self::UNKNOWN, true ) ) {
			$country = ''; // unknown, Tor, anonymous proxy, satellite, other
		}
		$region = strtoupper( (string) ( $found['region'] ?? '' ) );
		if ( str_contains( $region, '-' ) ) {
			$region = substr( $region, strpos( $region, '-' ) + 1 ); // "US-CA" to "CA"
		}
		$region = substr( preg_replace( '/[^A-Z0-9]/', '', $region ), 0, 3 );
		return array( 'country' => $country, 'region' => $country ? $region : '', 'source' => (string) ( $found['source'] ?? '' ) );
	}

	private static function from_headers(): array {
		$server  = $_SERVER; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$custom  = (string) tlc_setting( 'geo.custom_header' );
		$headers = $custom ? array_merge( array( 'HTTP_' . str_replace( '-', '_', $custom ) ), self::COUNTRY_HEADERS ) : self::COUNTRY_HEADERS;
		foreach ( $headers as $key ) {
			$value = strtoupper( trim( (string) ( $server[ $key ] ?? '' ) ) );
			if ( preg_match( '/^[A-Z]{2}$/', $value ) && ! in_array( $value, self::UNKNOWN, true ) ) {
				$region_custom = (string) tlc_setting( 'geo.region_header' );
				$regions       = $region_custom ? array_merge( array( 'HTTP_' . str_replace( '-', '_', $region_custom ) ), self::REGION_HEADERS ) : self::REGION_HEADERS;
				$region        = '';
				foreach ( $regions as $rkey ) {
					if ( ! empty( $server[ $rkey ] ) ) {
						$region = sanitize_text_field( (string) $server[ $rkey ] );
						break;
					}
				}
				return array( 'country' => $value, 'region' => $region, 'source' => 'header' );
			}
		}
		return array( 'country' => '', 'region' => '', 'source' => '' );
	}

	private static function from_maxmind( string $ip ): array {
		$file = TLC_Maxmind::database_path();
		if ( '' === $file || '' === $ip ) {
			return array( 'country' => '', 'region' => '', 'source' => '' );
		}
		try {
			$record = ( new TLC_Mmdb_Reader( $file ) )->get( $ip );
		} catch ( Throwable $e ) {
			return array( 'country' => '', 'region' => '', 'source' => '' );
		}
		$country = (string) ( $record['country']['iso_code'] ?? $record['registered_country']['iso_code'] ?? '' );
		$region  = (string) ( $record['subdivisions'][0]['iso_code'] ?? '' );
		return array( 'country' => $country, 'region' => $region, 'source' => $country ? 'maxmind' : '' );
	}

	/**
	 * The visitor's IP address. By default REMOTE_ADDR; behind a proxy or load balancer, set the header that holds
	 * the real address (e.g. X-Forwarded-For) in the Regions tab. Only do that when the proxy sets the header,
	 * since visitors can send it themselves.
	 */
	public static function client_ip(): string {
		$header = (string) tlc_setting( 'geo.ip_header' );
		$value  = '';
		if ( $header ) {
			$key   = 'HTTP_' . str_replace( '-', '_', $header );
			$value = isset( $_SERVER[ $key ] ) ? (string) $_SERVER[ $key ] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$value = trim( explode( ',', $value )[0] ); // the first address is the client
		}
		if ( '' === $value || false === filter_var( $value, FILTER_VALIDATE_IP ) ) {
			$value = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}
		return (string) apply_filters( 'tlc_client_ip', filter_var( $value, FILTER_VALIDATE_IP ) ? $value : '' );
	}

	/** Region group for a location. Filter: tlc_region_group. */
	public static function group( string $country, string $region ): string {
		if ( '' === $country ) {
			$group = 'unknown';
		} elseif ( in_array( $country, self::EU, true ) ) {
			$group = 'eu';
		} elseif ( in_array( $country, self::GB, true ) ) {
			$group = 'gb';
		} elseif ( 'US' === $country ) {
			$states = (array) apply_filters( 'tlc_us_privacy_states', self::US_PRIVACY_STATES );
			$group  = in_array( $region, $states, true ) ? 'us_states' : 'us';
		} elseif ( 'CA' === $country ) {
			$group = 'ca';
		} elseif ( 'BR' === $country ) {
			$group = 'br';
		} else {
			$group = 'default';
		}
		return (string) apply_filters( 'tlc_region_group', $group, $country, $region );
	}

	/** Per-country / per-state overrides: [ 'US-CA' => 'opt-in', 'IN' => 'notice' ]. */
	public static function overrides(): array {
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) tlc_setting( 'geo.overrides', '' ) ) as $line ) {
			if ( preg_match( '/^([A-Z]{2}(?:-[A-Z0-9]{1,3})?):\s*(opt-in|opt-out|notice|none)$/', trim( $line ), $m ) ) {
				$out[ $m[1] ] = $m[2];
			}
		}
		return $out;
	}

	/**
	 * Everything the browser needs: location, group, and model.
	 *
	 * @return array{country:string,region:string,group:string,model:string,source:string}
	 */
	public static function resolve( ?string $ip = null ): array {
		$loc       = self::locate( $ip );
		$group     = self::group( $loc['country'], $loc['region'] );
		$models    = (array) tlc_setting( 'geo.models', array() );
		$overrides = self::overrides();
		$model     = $models[ $group ] ?? 'opt-in';
		if ( $loc['country'] && isset( $overrides[ $loc['country'] ] ) ) {
			$model = $overrides[ $loc['country'] ];
		}
		if ( $loc['region'] && isset( $overrides[ $loc['country'] . '-' . $loc['region'] ] ) ) {
			$model = $overrides[ $loc['country'] . '-' . $loc['region'] ];
		}
		if ( ! tlc_setting( 'geo.enabled' ) ) {
			$group = 'default';
			$model = $models['default'] ?? 'opt-in';
		}
		/**
		 * The consent model for a visitor: 'opt-in', 'opt-out', 'notice', or 'none'.
		 *
		 * @param string $model
		 * @param array  $location { country, region, group }
		 */
		$model = (string) apply_filters( 'tlc_region_model', $model, $loc + array( 'group' => $group ) );
		if ( ! in_array( $model, TLC_Settings::MODELS, true ) ) {
			$model = 'opt-in';
		}
		return array(
			'country' => $loc['country'],
			'region'  => $loc['region'],
			'group'   => $group,
			'model'   => $model,
			'source'  => $loc['source'],
		);
	}

	/**
	 * Google Consent Mode region lists per model: [ 'opt-in' => [ 'DE', 'FR', 'US-CA' ... ], 'opt-out' => [ 'US' ] ].
	 * Used for region-specific "default" commands, so Google applies the right defaults before the browser knows
	 * the region. Countries in the "default" group get the command without a region.
	 */
	public static function gcm_regions(): array {
		if ( ! tlc_setting( 'geo.enabled' ) ) {
			return array();
		}
		$models = (array) tlc_setting( 'geo.models', array() );
		$map    = array(); // region code => model; overrides replace the group's model for that code
		$set    = function ( array $codes, string $model ) use ( &$map ) {
			foreach ( $codes as $code ) {
				$map[ $code ] = $model;
			}
		};
		$set( self::EU, $models['eu'] ?? 'opt-in' );
		$set( array( 'GB', 'GG', 'JE', 'IM' ), $models['gb'] ?? 'opt-in' );
		$set( array( 'US' ), $models['us'] ?? 'opt-out' );
		$set( array_map( fn( $s ) => 'US-' . $s, (array) apply_filters( 'tlc_us_privacy_states', self::US_PRIVACY_STATES ) ), $models['us_states'] ?? 'opt-out' );
		$set( array( 'CA' ), $models['ca'] ?? 'opt-in' );
		$set( array( 'BR' ), $models['br'] ?? 'opt-in' );
		foreach ( self::overrides() as $code => $model ) {
			$set( array( $code ), $model );
		}
		$lists = array();
		foreach ( $map as $code => $model ) {
			$lists[ $model ][] = $code;
		}
		return $lists;
	}
}
