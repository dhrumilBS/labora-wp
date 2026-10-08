<?php
/**
 * Settings: defaults, storage, sanitizing, and developer overrides.
 *
 * Everything lives in one option, "tlc_settings", grouped by section (general, banner, texts, categories, policy,
 * geo, blocking, consent_mode, scripts, logs). Read it with TLC_Settings::get() or tlc_setting( 'section.key' ).
 *
 * Developers can override any value without touching the database, in this order (later wins):
 *   1. the saved option
 *   2. the TLC_CONFIG constant (an array with the same shape, e.g. in wp-config.php or a must-use plugin)
 *   3. the "tlc_settings" filter
 * Overridden values are shown as "set in code" in the admin.
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Settings {

	const OPTION = 'tlc_settings';

	/** Consent models a region can use. */
	const MODELS = array( 'opt-in', 'opt-out', 'notice', 'none' );

	/** Banner layouts. */
	const LAYOUTS = array( 'box-left', 'box-right', 'bar-bottom', 'bar-top', 'modal' );

	/** Google Consent Mode v2 signals a category can grant. */
	const GCM_SIGNALS = array( 'ad_storage', 'ad_user_data', 'ad_personalization', 'analytics_storage', 'functionality_storage', 'personalization_storage', 'security_storage' );

	/** Keys never exported or shown back in the admin. */
	const SECRETS = array( 'geo' => array( 'maxmind_key' ) );

	private static ?array $cache = null;

	/** The full default settings. Filter: tlc_defaults. */
	public static function defaults(): array {
		$defaults = array(
			'general'      => array(
				'enabled'        => 1,
				'preview'        => 0,
				'cookie_name'    => 'tlc_consent',
				'cookie_days'    => 180,
				'cookie_domain'  => '',
				'revision'       => 1,
				'gpc'            => 1,
				'hide_for_bots'  => 1,
				'reload'         => 0,
				'open_selectors' => '',
				'dnss_selectors' => '',
			),
			'banner'       => array(
				'layout'              => 'box-left',
				'delay'               => 600,
				'show_reject'         => 1,
				'equal_buttons'       => 0,
				'reopen'              => 0,
				'css'                 => 'full',
				'btn_primary_class'   => '',
				'btn_secondary_class' => '',
				'colors'              => array(
					'bg'           => '#ffffff',
					'text'         => '#334155',
					'heading'      => '#0f172a',
					'muted'        => '#526072',
					'border'       => '#e2e8f0',
					'accent'       => '#2457d6',
					'primary'      => '#0f172a',
					'primary_text' => '#ffffff',
				),
				'radius'              => 20,
				'z_index'             => 9999,
				'custom_css'          => '',
			),
			'texts'        => self::default_texts(),
			'categories'   => self::default_categories(),
			'policy'       => array(
				'privacy_page'    => (int) get_option( 'wp_page_for_privacy_policy' ),
				'privacy_url'     => '',
				'cookie_page'     => 0,
				'cookie_url'      => '',
				'show_privacy'    => 0,
				'show_cookie'     => 1,
				'dnss_visibility' => 'opt-out',
			),
			'geo'          => array(
				'enabled'         => 1,
				'headers'         => 1,
				'custom_header'   => '',
				'region_header'   => '',
				'maxmind'         => 1,
				'maxmind_path'    => '',
				'maxmind_account' => '',
				'maxmind_key'     => '',
				'maxmind_edition' => 'GeoLite2-Country',
				'ip_header'       => '',
				'models'          => array(
					'eu'        => 'opt-in',
					'gb'        => 'opt-in',
					'us_states' => 'opt-out',
					'us'        => 'opt-out',
					'ca'        => 'opt-in',
					'br'        => 'opt-in',
					'default'   => 'opt-in',
					'unknown'   => 'opt-in',
				),
				'overrides'       => '',
			),
			'blocking'     => array(
				'auto'         => 1,
				'iframes'      => 1,
				'google_gcm'   => 1,
				'presets'      => array_keys( TLC_Blocker::presets() ),
				'rules'        => array(),
				'handles'      => array(),
				'legacy_attr'  => '',
			),
			'consent_mode' => array(
				'enabled'            => 1,
				'wait'               => 500,
				'ads_data_redaction' => 1,
				'url_passthrough'    => 0,
				'uet'                => 0,
			),
			'scripts'      => array(),
			'logs'         => array(
				'enabled'    => 1,
				'retention'  => 365,
				'ip'         => 'anonymize',
				'user_agent' => 1,
			),
		);
		return apply_filters( 'tlc_defaults', $defaults );
	}

	/** Visitor-facing texts. {category} is replaced with a category name. */
	public static function default_texts(): array {
		return array(
			'banner_title'      => __( 'Your privacy choices', 'trustlayer-consent' ),
			'banner_text'       => __( 'We use cookies to make this site work. With your permission, we would also like to use analytics and marketing cookies.', 'trustlayer-consent' ),
			'optout_title'      => __( 'Your privacy choices', 'trustlayer-consent' ),
			'optout_text'       => __( 'We use cookies for analytics and advertising. Some of this may count as "selling" or "sharing" personal information under US state privacy laws. You can opt out at any time.', 'trustlayer-consent' ),
			'notice_title'      => __( 'Cookies on this site', 'trustlayer-consent' ),
			'notice_text'       => __( 'We use cookies to run and improve this site.', 'trustlayer-consent' ),
			'accept_all'        => __( 'Accept all', 'trustlayer-consent' ),
			'reject_all'        => __( 'Reject all', 'trustlayer-consent' ),
			'customize'         => __( 'Customize', 'trustlayer-consent' ),
			'save'              => __( 'Save choices', 'trustlayer-consent' ),
			'ok'                => __( 'OK', 'trustlayer-consent' ),
			'opt_out'           => __( 'Do not sell or share', 'trustlayer-consent' ),
			'prefs_title'       => __( 'Customize cookies', 'trustlayer-consent' ),
			'prefs_text'        => __( 'Choose which cookies we may use. You can change this at any time.', 'trustlayer-consent' ),
			'always_on'         => __( 'Always on', 'trustlayer-consent' ),
			'what_we_store'     => __( 'What we store', 'trustlayer-consent' ),
			'nothing_stored'    => __( 'Nothing today. This site does not use any {category} tools. If we add one, it will run only with your permission.', 'trustlayer-consent' ),
			'col_cookie'        => __( 'Cookie', 'trustlayer-consent' ),
			'col_provider'      => __( 'Provider', 'trustlayer-consent' ),
			'col_purpose'       => __( 'Purpose', 'trustlayer-consent' ),
			'col_duration'      => __( 'Kept for', 'trustlayer-consent' ),
			'cookie_policy'     => __( 'Cookie policy', 'trustlayer-consent' ),
			'privacy_policy'    => __( 'Privacy policy', 'trustlayer-consent' ),
			'dnss_link'         => __( 'Do Not Sell or Share My Personal Information', 'trustlayer-consent' ),
			'dnss_title'        => __( 'Do Not Sell or Share My Personal Information', 'trustlayer-consent' ),
			'dnss_text'         => __( 'We do not sell personal information for money. Some advertising and analytics cookies may still count as "selling" or "sharing" under US state privacy laws. Switch this off to opt out on this browser.', 'trustlayer-consent' ),
			'dnss_toggle'       => __( 'Allow the sale or sharing of my personal information', 'trustlayer-consent' ),
			'dnss_saved'        => __( 'Your choice is saved for this browser.', 'trustlayer-consent' ),
			'gpc_notice'        => __( 'Your browser sent a Global Privacy Control signal, so sale and sharing are switched off.', 'trustlayer-consent' ),
			'embed_text'        => __( 'This content is hosted by a third party that may set {category} cookies.', 'trustlayer-consent' ),
			'embed_button'      => __( 'Allow and show', 'trustlayer-consent' ),
			'reopen'            => __( 'Cookie settings', 'trustlayer-consent' ),
			'close'             => __( 'Close', 'trustlayer-consent' ),
		);
	}

	/** Texts that may contain links and emphasis (sanitized with wp_kses). */
	public static function html_texts(): array {
		return array( 'banner_text', 'optout_text', 'notice_text', 'prefs_text', 'dnss_text' );
	}

	public static function default_categories(): array {
		return array(
			array(
				'id'          => 'necessary',
				'enabled'     => 1,
				'locked'      => 1,
				'title'       => __( 'Strictly necessary', 'trustlayer-consent' ),
				'description' => __( 'Needed for the site to work. They cannot be switched off.', 'trustlayer-consent' ),
				'sale_share'  => 0,
				'gcm'         => array( 'security_storage' ),
				'cookies'     => array(),
			),
			array(
				'id'          => 'functional',
				'enabled'     => 1,
				'locked'      => 0,
				'title'       => __( 'Functional', 'trustlayer-consent' ),
				'description' => __( 'Remember choices such as language or region, and allow features like embedded videos, maps, and chat.', 'trustlayer-consent' ),
				'sale_share'  => 0,
				'gcm'         => array( 'functionality_storage', 'personalization_storage' ),
				'cookies'     => array(),
			),
			array(
				'id'          => 'analytics',
				'enabled'     => 1,
				'locked'      => 0,
				'title'       => __( 'Analytics', 'trustlayer-consent' ),
				'description' => __( 'Help us understand which pages are useful, through visit statistics.', 'trustlayer-consent' ),
				'sale_share'  => 0,
				'gcm'         => array( 'analytics_storage' ),
				'cookies'     => array(),
			),
			array(
				'id'          => 'marketing',
				'enabled'     => 1,
				'locked'      => 0,
				'title'       => __( 'Marketing', 'trustlayer-consent' ),
				'description' => __( 'Measure our advertising and show relevant ads on other sites.', 'trustlayer-consent' ),
				'sale_share'  => 1,
				'gcm'         => array( 'ad_storage', 'ad_user_data', 'ad_personalization' ),
				'cookies'     => array(),
			),
		);
	}

	/** Saved settings merged over the defaults, then developer overrides. */
	public static function get(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}
		$saved = get_option( self::OPTION, array() );
		$all   = self::merge( self::defaults(), is_array( $saved ) ? $saved : array() );
		if ( defined( 'TLC_CONFIG' ) && is_array( TLC_CONFIG ) ) {
			$all = self::merge( $all, TLC_CONFIG );
		}
		/**
		 * Filter all settings before use. Same shape as the defaults.
		 *
		 * @param array $settings
		 */
		$all = (array) apply_filters( 'tlc_settings', $all );
		// Themes add their filters in functions.php: only keep the result once the theme has loaded
		if ( did_action( 'after_setup_theme' ) ) {
			self::$cache = $all;
		}
		return $all;
	}

	/** Saved settings only (no code overrides), as the admin edits them. */
	public static function saved(): array {
		$saved = get_option( self::OPTION, array() );
		return self::merge( self::defaults(), is_array( $saved ) ? $saved : array() );
	}

	/** One value by "section.key" path, e.g. "general.cookie_name". */
	public static function value( string $path, $fallback = null ) {
		$node = self::get();
		foreach ( explode( '.', $path ) as $key ) {
			if ( ! is_array( $node ) || ! array_key_exists( $key, $node ) ) {
				return $fallback;
			}
			$node = $node[ $key ];
		}
		return $node;
	}

	/** True when a value differs between the saved option and what is used (TLC_CONFIG or the filter). */
	public static function is_overridden( string $path ): bool {
		$used  = self::get();
		$saved = self::saved();
		foreach ( explode( '.', $path ) as $key ) {
			$used  = is_array( $used ) && array_key_exists( $key, $used ) ? $used[ $key ] : null;
			$saved = is_array( $saved ) && array_key_exists( $key, $saved ) ? $saved[ $key ] : null;
		}
		return $used !== $saved;
	}

	public static function flush(): void {
		self::$cache = null;
	}

	/**
	 * Recursive merge for settings: associative arrays merge key by key; lists (categories, rules, cookies ...)
	 * are replaced as a whole, so removing a row in the admin really removes it.
	 */
	public static function merge( array $base, array $over ): array {
		foreach ( $over as $key => $value ) {
			if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) && ! array_is_list( $value ) && ! array_is_list( $base[ $key ] ) ) {
				$base[ $key ] = self::merge( $base[ $key ], $value );
			} else {
				$base[ $key ] = $value;
			}
		}
		return $base;
	}

	/** Save one or more sections (already sanitized). */
	public static function save_sections( array $sections ): void {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		foreach ( $sections as $section => $values ) {
			$saved[ $section ] = $values;
		}
		update_option( self::OPTION, $saved, true );
		self::flush();
		/**
		 * Fires after settings are saved.
		 *
		 * @param array $sections The sections that changed.
		 */
		do_action( 'tlc_settings_saved', array_keys( $sections ) );
	}

	/* ---------- Derived values ---------- */

	/** Enabled categories, with the consent cookie listed under the locked one. Filter: tlc_categories. */
	public static function categories(): array {
		$cats = array_values( array_filter( (array) self::value( 'categories', array() ), fn( $c ) => ! empty( $c['enabled'] ) ) );
		$name = (string) self::value( 'general.cookie_name' );
		foreach ( $cats as &$cat ) {
			if ( ! empty( $cat['locked'] ) && ! in_array( $name, array_column( (array) $cat['cookies'], 'name' ), true ) ) {
				array_unshift( $cat['cookies'], array(
					'name'     => $name,
					'provider' => '',
					'purpose'  => __( 'Remembers the cookie choices you make here', 'trustlayer-consent' ),
					'duration' => self::human_days( (int) self::value( 'general.cookie_days' ) ),
				) );
				break;
			}
		}
		unset( $cat );
		return (array) apply_filters( 'tlc_categories', $cats );
	}

	public static function category_ids(): array {
		return array_column( self::categories(), 'id' );
	}

	public static function human_days( int $days ): string {
		if ( $days % 365 === 0 ) {
			$n = intdiv( $days, 365 );
			/* translators: %d: number of years */
			return sprintf( _n( '%d year', '%d years', $n, 'trustlayer-consent' ), $n );
		}
		if ( $days >= 28 && abs( $days / 30 - round( $days / 30 ) ) < 0.1 ) {
			$n = (int) round( $days / 30 );
			/* translators: %d: number of months */
			return sprintf( _n( '%d month', '%d months', $n, 'trustlayer-consent' ), $n );
		}
		/* translators: %d: number of days */
		return sprintf( _n( '%d day', '%d days', $days, 'trustlayer-consent' ), $days );
	}

	/** Privacy and cookie policy links ('' when not set). */
	public static function policy_url( string $which ): string {
		$page = (int) self::value( "policy.{$which}_page" );
		if ( $page && 'publish' === get_post_status( $page ) ) {
			return (string) get_permalink( $page );
		}
		return (string) self::value( "policy.{$which}_url", '' );
	}

	/* ---------- Sanitizing ---------- */

	/**
	 * Sanitize one section from raw input (admin form or import). Missing checkboxes are treated as off,
	 * so pass $partial = true for imports where a missing key means "keep the default".
	 */
	public static function sanitize_section( string $section, $raw, bool $partial = false ): array {
		$raw      = is_array( $raw ) ? wp_unslash( $raw ) : array();
		$defaults = self::defaults()[ $section ] ?? array();
		$current  = self::saved()[ $section ] ?? $defaults;
		$bool     = fn( $key ) => $partial && ! array_key_exists( $key, $raw ) ? (int) ! empty( $current[ $key ] ) : (int) ! empty( $raw[ $key ] );
		$text     = fn( $key ) => array_key_exists( $key, $raw ) ? sanitize_text_field( (string) $raw[ $key ] ) : (string) ( $current[ $key ] ?? '' );
		$int      = fn( $key, $min, $max ) => array_key_exists( $key, $raw ) ? max( $min, min( $max, (int) $raw[ $key ] ) ) : (int) ( $current[ $key ] ?? $min );
		$choice   = fn( $key, $allowed ) => isset( $raw[ $key ] ) && in_array( $raw[ $key ], $allowed, true ) ? $raw[ $key ] : ( $current[ $key ] ?? $allowed[0] );

		switch ( $section ) {
			case 'general':
				$name = array_key_exists( 'cookie_name', $raw ) ? preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $raw['cookie_name'] ) : $current['cookie_name'];
				return array(
					'enabled'        => $bool( 'enabled' ),
					'preview'        => $bool( 'preview' ),
					'cookie_name'    => $name ?: 'tlc_consent',
					'cookie_days'    => $int( 'cookie_days', 1, 730 ),
					'cookie_domain'  => preg_replace( '/[^a-z0-9.\-]/', '', strtolower( $text( 'cookie_domain' ) ) ),
					'revision'       => $int( 'revision', 1, PHP_INT_MAX ),
					'gpc'            => $bool( 'gpc' ),
					'hide_for_bots'  => $bool( 'hide_for_bots' ),
					'reload'         => $bool( 'reload' ),
					'open_selectors' => self::selector_list( $text( 'open_selectors' ) ),
					'dnss_selectors' => self::selector_list( $text( 'dnss_selectors' ) ),
				);

			case 'banner':
				$colors = array();
				foreach ( $defaults['colors'] as $key => $default ) {
					$value          = $raw['colors'][ $key ] ?? ( $current['colors'][ $key ] ?? $default );
					$colors[ $key ] = sanitize_hex_color( (string) $value ) ?: $default;
				}
				return array(
					'layout'              => $choice( 'layout', self::LAYOUTS ),
					'delay'               => $int( 'delay', 0, 30000 ),
					'show_reject'         => $bool( 'show_reject' ),
					'equal_buttons'       => $bool( 'equal_buttons' ),
					'reopen'              => $bool( 'reopen' ),
					'css'                 => $choice( 'css', array( 'full', 'none' ) ),
					'btn_primary_class'   => self::class_list( $text( 'btn_primary_class' ) ),
					'btn_secondary_class' => self::class_list( $text( 'btn_secondary_class' ) ),
					'colors'              => $colors,
					'radius'              => $int( 'radius', 0, 48 ),
					'z_index'             => $int( 'z_index', 1, 2147483647 ),
					'custom_css'          => array_key_exists( 'custom_css', $raw ) ? self::css( (string) $raw['custom_css'] ) : (string) $current['custom_css'],
				);

			case 'texts':
				$out = array();
				foreach ( self::default_texts() as $key => $default ) {
					if ( ! array_key_exists( $key, $raw ) ) {
						$out[ $key ] = $current[ $key ] ?? $default;
						continue;
					}
					$value       = in_array( $key, self::html_texts(), true ) ? wp_kses( (string) $raw[ $key ], self::allowed_html() ) : sanitize_text_field( (string) $raw[ $key ] );
					$out[ $key ] = '' === trim( $value ) ? $default : $value;
				}
				return $out;

			case 'categories':
				return self::sanitize_categories( $raw );

			case 'policy':
				return array(
					'privacy_page'    => array_key_exists( 'privacy_page', $raw ) ? absint( $raw['privacy_page'] ) : (int) $current['privacy_page'],
					'privacy_url'     => array_key_exists( 'privacy_url', $raw ) ? esc_url_raw( (string) $raw['privacy_url'] ) : (string) $current['privacy_url'],
					'cookie_page'     => array_key_exists( 'cookie_page', $raw ) ? absint( $raw['cookie_page'] ) : (int) $current['cookie_page'],
					'cookie_url'      => array_key_exists( 'cookie_url', $raw ) ? esc_url_raw( (string) $raw['cookie_url'] ) : (string) $current['cookie_url'],
					'show_privacy'    => $bool( 'show_privacy' ),
					'show_cookie'     => $bool( 'show_cookie' ),
					'dnss_visibility' => $choice( 'dnss_visibility', array( 'opt-out', 'always', 'never' ) ),
				);

			case 'geo':
				$models = array();
				foreach ( $defaults['models'] as $group => $default ) {
					$value            = $raw['models'][ $group ] ?? ( $current['models'][ $group ] ?? $default );
					$models[ $group ] = in_array( $value, self::MODELS, true ) ? $value : $default;
				}
				$key = array_key_exists( 'maxmind_key', $raw ) && '' !== trim( (string) $raw['maxmind_key'] ) ? sanitize_text_field( (string) $raw['maxmind_key'] ) : (string) ( $current['maxmind_key'] ?? '' );
				if ( ! empty( $raw['maxmind_key_clear'] ) ) {
					$key = '';
				}
				return array(
					'enabled'         => $bool( 'enabled' ),
					'headers'         => $bool( 'headers' ),
					'custom_header'   => self::header_name( $text( 'custom_header' ) ),
					'region_header'   => self::header_name( $text( 'region_header' ) ),
					'maxmind'         => $bool( 'maxmind' ),
					'maxmind_path'    => $text( 'maxmind_path' ),
					'maxmind_account' => preg_replace( '/\D/', '', $text( 'maxmind_account' ) ),
					'maxmind_key'     => $key,
					'maxmind_edition' => $choice( 'maxmind_edition', array( 'GeoLite2-Country', 'GeoLite2-City' ) ),
					'ip_header'       => self::header_name( $text( 'ip_header' ) ),
					'models'          => $models,
					'overrides'       => array_key_exists( 'overrides', $raw ) ? self::overrides_text( (string) $raw['overrides'] ) : (string) $current['overrides'],
				);

			case 'blocking':
				$ids     = self::category_ids_raw();
				$presets = array_keys( TLC_Blocker::presets() );
				$rules   = array();
				foreach ( (array) ( $raw['rules'] ?? ( $partial ? $current['rules'] : array() ) ) as $row ) {
					$pattern = trim( sanitize_text_field( (string) ( $row['pattern'] ?? '' ) ) );
					$cat     = (string) ( $row['category'] ?? '' );
					if ( '' !== $pattern && in_array( $cat, $ids, true ) ) {
						$rules[] = array( 'pattern' => $pattern, 'category' => $cat );
					}
				}
				$handles = array();
				foreach ( (array) ( $raw['handles'] ?? ( $partial ? $current['handles'] : array() ) ) as $row ) {
					$handle = sanitize_key( (string) ( $row['handle'] ?? '' ) );
					$cat    = (string) ( $row['category'] ?? '' );
					if ( '' !== $handle && in_array( $cat, $ids, true ) ) {
						$handles[] = array( 'handle' => $handle, 'category' => $cat );
					}
				}
				$chosen = array_key_exists( 'presets', $raw ) || ! $partial ? array_values( array_intersect( $presets, (array) ( $raw['presets'] ?? array() ) ) ) : (array) $current['presets'];
				return array(
					'auto'        => $bool( 'auto' ),
					'iframes'     => $bool( 'iframes' ),
					'google_gcm'  => $bool( 'google_gcm' ),
					'presets'     => $chosen,
					'rules'       => $rules,
					'handles'     => $handles,
					'legacy_attr' => preg_replace( '/[^a-z0-9\-]/', '', strtolower( $text( 'legacy_attr' ) ) ),
				);

			case 'consent_mode':
				return array(
					'enabled'            => $bool( 'enabled' ),
					'wait'               => $int( 'wait', 0, 10000 ),
					'ads_data_redaction' => $bool( 'ads_data_redaction' ),
					'url_passthrough'    => $bool( 'url_passthrough' ),
					'uet'                => $bool( 'uet' ),
				);

			case 'scripts':
				// Raw code: only saved for users who may post unfiltered HTML (administrators on single sites)
				if ( ! current_user_can( 'unfiltered_html' ) && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
					return (array) $current;
				}
				$out = array();
				foreach ( self::category_ids_raw() as $id ) {
					$head   = (string) ( $raw[ $id ]['head'] ?? '' );
					$footer = (string) ( $raw[ $id ]['footer'] ?? '' );
					if ( '' !== trim( $head . $footer ) ) {
						$out[ $id ] = array( 'head' => $head, 'footer' => $footer );
					}
				}
				return $out;

			case 'logs':
				return array(
					'enabled'    => $bool( 'enabled' ),
					'retention'  => $int( 'retention', 1, 3650 ),
					'ip'         => $choice( 'ip', array( 'anonymize', 'none' ) ),
					'user_agent' => $bool( 'user_agent' ),
				);
		}
		return array();
	}

	/** Categories from the admin form (or an import). The locked "necessary" category always exists. */
	public static function sanitize_categories( $raw ): array {
		$out  = array();
		$seen = array();
		foreach ( (array) $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$id = sanitize_key( (string) ( $row['id'] ?? '' ) );
			if ( '' === $id || isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;
			$cookies     = array();
			foreach ( (array) ( $row['cookies'] ?? array() ) as $cookie ) {
				$name = trim( sanitize_text_field( (string) ( $cookie['name'] ?? '' ) ) );
				if ( '' === $name ) {
					continue;
				}
				$cookies[] = array(
					'name'     => $name,
					'provider' => sanitize_text_field( (string) ( $cookie['provider'] ?? '' ) ),
					'purpose'  => sanitize_text_field( (string) ( $cookie['purpose'] ?? '' ) ),
					'duration' => sanitize_text_field( (string) ( $cookie['duration'] ?? '' ) ),
				);
			}
			$out[] = array(
				'id'          => $id,
				'enabled'     => 'necessary' === $id ? 1 : (int) ! empty( $row['enabled'] ),
				'locked'      => 'necessary' === $id ? 1 : 0,
				'title'       => sanitize_text_field( (string) ( $row['title'] ?? $id ) ) ?: $id,
				'description' => wp_kses( (string) ( $row['description'] ?? '' ), self::allowed_html() ),
				'sale_share'  => 'necessary' === $id ? 0 : (int) ! empty( $row['sale_share'] ),
				'gcm'         => array_values( array_intersect( self::GCM_SIGNALS, (array) ( $row['gcm'] ?? array() ) ) ),
				'cookies'     => $cookies,
			);
		}
		if ( ! isset( $seen['necessary'] ) ) {
			array_unshift( $out, self::default_categories()[0] );
		}
		return $out;
	}

	/** Category ids from the saved option, including disabled ones (rules may point at them). */
	private static function category_ids_raw(): array {
		return array_column( (array) ( self::saved()['categories'] ?? array() ), 'id' );
	}

	public static function allowed_html(): array {
		return array(
			'a'      => array( 'href' => true, 'target' => true, 'rel' => true, 'class' => true, 'data-tlc-open' => true, 'data-tlc-dnss' => true ),
			'strong' => array(),
			'em'     => array(),
			'b'      => array(),
			'i'      => array(),
			'br'     => array(),
			'span'   => array( 'class' => true ),
		);
	}

	private static function selector_list( string $value ): string {
		// CSS selectors, comma-separated; no braces or tags
		return trim( preg_replace( '/[{}<>;]/', '', $value ) );
	}

	private static function class_list( string $value ): string {
		return trim( preg_replace( '/[^A-Za-z0-9_\- ]/', '', $value ) );
	}

	private static function header_name( string $value ): string {
		return strtoupper( preg_replace( '/[^A-Za-z0-9_\-]/', '', $value ) );
	}

	private static function css( string $css ): string {
		return trim( str_ireplace( array( '</style', '<script', '<' ), '', wp_strip_all_tags( $css ) ) );
	}

	/** "XX: model" lines, one per country (XX) or US state (US-CA). */
	private static function overrides_text( string $text ): string {
		$lines = array();
		foreach ( preg_split( '/\r\n|\r|\n|,/', $text ) as $line ) {
			if ( preg_match( '/^\s*([A-Za-z]{2}(?:-[A-Za-z0-9]{1,3})?)\s*[:=]\s*(opt-in|opt-out|notice|none)\s*$/i', $line, $m ) ) {
				$lines[] = strtoupper( $m[1] ) . ': ' . strtolower( $m[2] );
			}
		}
		return implode( "\n", array_unique( $lines ) );
	}

	/* ---------- Import / export ---------- */

	/** Settings as an array for export: everything except secrets (MaxMind license key). */
	public static function export(): array {
		$data = self::saved();
		foreach ( self::SECRETS as $section => $keys ) {
			foreach ( $keys as $key ) {
				unset( $data[ $section ][ $key ] );
			}
		}
		return array(
			'plugin'   => 'trustlayer-consent',
			'version'  => TLC_VERSION,
			'exported' => gmdate( 'c' ),
			'site'     => home_url( '/' ),
			'settings' => $data,
		);
	}

	/**
	 * Import settings from an export. Every section is sanitized; unknown sections are ignored.
	 * Returns the list of imported sections, or a WP_Error.
	 */
	public static function import( $data ) {
		if ( ! is_array( $data ) || ( $data['plugin'] ?? '' ) !== 'trustlayer-consent' || ! is_array( $data['settings'] ?? null ) ) {
			return new WP_Error( 'tlc_import', __( 'This is not a TrustLayer Consent settings file.', 'trustlayer-consent' ) );
		}
		$order    = array( 'categories', 'general', 'banner', 'texts', 'policy', 'geo', 'blocking', 'consent_mode', 'scripts', 'logs' );
		$imported = array();
		foreach ( $order as $section ) {
			if ( ! isset( $data['settings'][ $section ] ) || ! is_array( $data['settings'][ $section ] ) ) {
				continue;
			}
			$raw = 'categories' === $section ? $data['settings'][ $section ] : wp_slash( $data['settings'][ $section ] );
			// Save categories first: rules and scripts are checked against them
			self::save_sections( array( $section => 'categories' === $section ? self::sanitize_categories( $raw ) : self::sanitize_section( $section, $raw, true ) ) );
			$imported[] = $section;
		}
		return $imported;
	}

	public static function reset(): void {
		$key = (string) ( self::saved()['geo']['maxmind_key'] ?? '' );
		update_option( self::OPTION, '' !== $key ? array( 'geo' => array( 'maxmind_key' => $key ) ) : array(), true ); // keep the license key
		self::flush();
	}
}
