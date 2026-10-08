<?php
/**
 * REST endpoints used by the banner script (namespace trustlayer/v1). Both are public, uncached, and need no
 * nonce, so they work on cached pages.
 *
 *   GET  /region    the visitor's region group and consent model
 *   POST /consent   log a choice: { consent_id, action, categories: { id: 0|1 }, model, region, revision, url }
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Rest {

	const NS = 'trustlayer/v1';

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes(): void {
		register_rest_route( self::NS, '/region', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'region' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( self::NS, '/consent', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( __CLASS__, 'consent' ),
			'permission_callback' => '__return_true',
		) );
	}

	private static function no_cache( WP_REST_Response $response ): WP_REST_Response {
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$response->header( 'X-Robots-Tag', 'noindex' );
		return $response;
	}

	public static function region(): WP_REST_Response {
		$geo = TLC_Geo::resolve();
		unset( $geo['source'] );
		return self::no_cache( new WP_REST_Response( $geo ) );
	}

	public static function consent( WP_REST_Request $request ) {
		if ( ! tlc_setting( 'logs.enabled' ) ) {
			return self::no_cache( new WP_REST_Response( array( 'logged' => false ), 200 ) );
		}
		// A visitor makes a handful of choices at most: limit each address to 20 per 10 minutes
		$limit = (int) apply_filters( 'tlc_rest_rate_limit', 20 );
		$key   = 'tlc_rl_' . md5( TLC_Geo::client_ip() . wp_salt( 'nonce' ) ); // hashed: no address is stored
		$count = (int) get_transient( $key );
		if ( $limit > 0 && $count >= $limit ) {
			return new WP_Error( 'tlc_rate_limited', 'Too many requests.', array( 'status' => 429 ) );
		}
		set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );

		$p  = (array) $request->get_json_params();
		$id = strtolower( (string) ( $p['consent_id'] ?? '' ) );
		if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id ) ) {
			return new WP_Error( 'tlc_invalid', 'Invalid consent ID.', array( 'status' => 400 ) );
		}
		$action = (string) ( $p['action'] ?? '' );
		if ( ! in_array( $action, TLC_Log::ACTIONS, true ) ) {
			return new WP_Error( 'tlc_invalid', 'Invalid action.', array( 'status' => 400 ) );
		}
		$cats = array();
		foreach ( TLC_Settings::categories() as $cat ) {
			$cats[ $cat['id'] ] = ! empty( $cat['locked'] ) ? 1 : (int) ! empty( $p['categories'][ $cat['id'] ] );
		}
		$model  = in_array( $p['model'] ?? '', TLC_Settings::MODELS, true ) ? $p['model'] : '';
		$region = strtoupper( (string) ( $p['region'] ?? '' ) );
		$region = preg_match( '/^[A-Z]{2}(-[A-Z0-9]{1,3})?$/', $region ) ? $region : '';

		// Keep only the path of a page on this site; query strings can hold personal data
		$path = '';
		$url  = (string) ( $p['url'] ?? '' );
		if ( '' !== $url && wp_parse_url( $url, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		}

		$logged = TLC_Log::record( array(
			'consent_id' => $id,
			'action'     => $action,
			'categories' => $cats,
			'model'      => $model,
			'region'     => $region,
			'revision'   => max( 1, (int) ( $p['revision'] ?? 1 ) ),
			'url'        => sanitize_text_field( $path ),
		) );
		return self::no_cache( new WP_REST_Response( array( 'logged' => $logged > 0 ), 201 ) );
	}
}
