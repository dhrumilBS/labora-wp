<?php
/**
 * Front end: the settings the banner script needs, Google Consent Mode defaults, and the banner's files.
 *
 * Printed first in <head>, before any tag manager (wp_head priority -1000):
 *   - window.TrustLayerConfig: categories, texts, layout, regions, REST URLs (filter: tlc_frontend_config)
 *   - window.TrustLayer stub with ready( fn ), replaced by the full API when assets/js/consent.js runs
 *   - Google Consent Mode v2 "default" commands per region, then an "update" from the visitor's saved choice
 * The banner itself is built by consent.js, so pages stay identical for every visitor and can be fully cached.
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Frontend {

	public static function init(): void {
		add_action( 'wp_head', array( __CLASS__, 'head' ), -1000 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 5 );
	}

	/** True when the banner and blocking run for this request. Filter: tlc_should_load. */
	public static function is_active(): bool {
		static $active = null;
		if ( null === $active ) {
			$active = tlc_setting( 'general.enabled' ) && ! is_admin()
				&& ( ! tlc_setting( 'general.preview' ) || current_user_can( 'manage_options' ) );
			/**
			 * Load the banner, Consent Mode, and blocking on this request?
			 *
			 * @param bool $active
			 */
			$active = (bool) apply_filters( 'tlc_should_load', $active );
		}
		return $active;
	}

	/** The object the browser script reads. */
	public static function config(): array {
		$models  = (array) tlc_setting( 'geo.models', array() );
		$texts   = (array) tlc_setting( 'texts', array() );
		$cats    = array();
		foreach ( TLC_Settings::categories() as $cat ) {
			$cats[] = array(
				'id'          => $cat['id'],
				'title'       => $cat['title'],
				'description' => $cat['description'],
				'locked'      => ! empty( $cat['locked'] ),
				'saleShare'   => ! empty( $cat['sale_share'] ),
				'gcm'         => array_values( (array) $cat['gcm'] ),
				'cookies'     => array_map( fn( $c ) => array( $c['name'], $c['provider'], $c['purpose'], $c['duration'] ), (array) $cat['cookies'] ),
			);
		}
		$config = array(
			'version'      => TLC_VERSION,
			'revision'     => (int) tlc_setting( 'general.revision', 1 ),
			'cookie'       => array(
				'name'   => (string) tlc_setting( 'general.cookie_name' ),
				'days'   => (int) tlc_setting( 'general.cookie_days' ),
				'domain' => (string) tlc_setting( 'general.cookie_domain' ),
			),
			'categories'   => $cats,
			'texts'        => $texts,
			'layout'       => (string) tlc_setting( 'banner.layout' ),
			'delay'        => (int) tlc_setting( 'banner.delay' ),
			'showReject'   => (bool) tlc_setting( 'banner.show_reject' ),
			'equalButtons' => (bool) tlc_setting( 'banner.equal_buttons' ),
			'reopen'       => (bool) tlc_setting( 'banner.reopen' ),
			'btn'          => array(
				'primary'   => (string) tlc_setting( 'banner.btn_primary_class' ),
				'secondary' => (string) tlc_setting( 'banner.btn_secondary_class' ),
			),
			'policy'       => array(
				'privacy' => tlc_setting( 'policy.show_privacy' ) ? TLC_Settings::policy_url( 'privacy' ) : '',
				'cookie'  => tlc_setting( 'policy.show_cookie' ) ? TLC_Settings::policy_url( 'cookie' ) : '',
			),
			'dnss'         => (string) tlc_setting( 'policy.dnss_visibility' ),
			'geo'          => TLC_Geo::is_needed(),
			'model'        => tlc_setting( 'geo.enabled' ) ? (string) ( $models['unknown'] ?? 'opt-in' ) : (string) ( $models['default'] ?? 'opt-in' ),
			'rest'         => array(
				'region'  => rest_url( TLC_Rest::NS . '/region' ),
				'consent' => rest_url( TLC_Rest::NS . '/consent' ),
			),
			'log'          => (bool) tlc_setting( 'logs.enabled' ),
			'gpc'          => (bool) tlc_setting( 'general.gpc' ),
			'bots'         => (bool) tlc_setting( 'general.hide_for_bots' ),
			'reload'       => (bool) tlc_setting( 'general.reload' ),
			'open'         => (string) tlc_setting( 'general.open_selectors' ),
			'dnssOpen'     => (string) tlc_setting( 'general.dnss_selectors' ),
			'gcm'          => (bool) tlc_setting( 'consent_mode.enabled' ),
			'uet'          => (bool) tlc_setting( 'consent_mode.uet' ),
			'legacy'       => (string) tlc_setting( 'blocking.legacy_attr' ),
		);
		if ( ! $config['geo'] && tlc_setting( 'geo.enabled' ) ) {
			$config['model'] = (string) ( $models['default'] ?? 'opt-in' ); // every region uses the same model
		}
		/**
		 * Change what the banner script receives, e.g. texts per language.
		 *
		 * @param array $config
		 */
		return (array) apply_filters( 'tlc_frontend_config', $config );
	}

	public static function head(): void {
		if ( ! self::is_active() ) {
			return;
		}
		$config = self::config();
		$json   = wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$js     = 'window.TrustLayerConfig=' . $json . ';'
			. 'window.TrustLayer=window.TrustLayer||{q:[],ready:function(f){this.q.push(f)}};';
		if ( $config['gcm'] || $config['uet'] ) {
			$js .= TLC_Consent_Mode::script( $config );
		}
		echo "<script id=\"tlc-head\">{$js}</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON-encoded
	}

	public static function enqueue(): void {
		if ( ! self::is_active() ) {
			return;
		}
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$js  = file_exists( TLC_DIR . "/assets/js/consent{$min}.js" ) ? "consent{$min}.js" : 'consent.js';
		$css = file_exists( TLC_DIR . "/assets/css/consent{$min}.css" ) ? "consent{$min}.css" : 'consent.css';

		wp_enqueue_script( 'tlc-consent', TLC_URL . 'assets/js/' . $js, array(), (string) filemtime( TLC_DIR . '/assets/js/' . $js ), array( 'strategy' => 'defer', 'in_footer' => true ) );

		if ( 'full' === tlc_setting( 'banner.css' ) ) {
			wp_enqueue_style( 'tlc-consent', TLC_URL . 'assets/css/' . $css, array(), (string) filemtime( TLC_DIR . '/assets/css/' . $css ) );
		} else {
			wp_register_style( 'tlc-consent', false, array(), TLC_VERSION ); // a handle for the inline rules below
			wp_enqueue_style( 'tlc-consent' );
		}
		wp_add_inline_style( 'tlc-consent', self::inline_css() );
	}

	/** Brand colors as CSS variables, the Do Not Sell link visibility, and custom CSS. */
	private static function inline_css(): string {
		$css = '';
		if ( 'full' === tlc_setting( 'banner.css' ) ) {
			$c    = (array) tlc_setting( 'banner.colors', array() );
			$vars = array(
				'--tlc-bg'           => $c['bg'] ?? '',
				'--tlc-text'         => $c['text'] ?? '',
				'--tlc-heading'      => $c['heading'] ?? '',
				'--tlc-muted'        => $c['muted'] ?? '',
				'--tlc-border'       => $c['border'] ?? '',
				'--tlc-accent'       => $c['accent'] ?? '',
				'--tlc-primary'      => $c['primary'] ?? '',
				'--tlc-primary-text' => $c['primary_text'] ?? '',
				'--tlc-radius'       => (int) tlc_setting( 'banner.radius' ) . 'px',
				'--tlc-z'            => (string) (int) tlc_setting( 'banner.z_index' ),
			);
			$decl = '';
			foreach ( $vars as $name => $value ) {
				if ( '' !== $value ) {
					$decl .= "{$name}:{$value};";
				}
			}
			$css .= ":root{{$decl}}";
		}
		// Do Not Sell or Share links stay hidden until the script decides they apply to this visitor
		if ( 'always' !== tlc_setting( 'policy.dnss_visibility' ) ) {
			$css .= '[data-tlc-dnss]:not(.tlc-dnss-on),a[href$="#tlc-dnss"]:not(.tlc-dnss-on){display:none!important}'
				. 'li:has(>a[href$="#tlc-dnss"]:not(.tlc-dnss-on)),li:has(>[data-tlc-dnss]:not(.tlc-dnss-on)){display:none!important}';
		}
		return $css . (string) tlc_setting( 'banner.custom_css' );
	}
}
