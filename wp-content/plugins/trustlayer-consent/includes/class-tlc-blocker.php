<?php
/**
 * Script and embed blocking: nothing optional runs or loads before the visitor allows its category.
 *
 * Ways a script is held back (the browser script releases it once its category is allowed):
 *   1. Automatic: the page's HTML is scanned once before it is sent; <script> tags whose src or code match a rule
 *      become <script type="text/plain" data-tlc-category="...">, and matching <iframe>s get data-tlc-src instead
 *      of src (a placeholder with an "Allow" button is shown). Rules come from the built-in presets (Meta Pixel,
 *      LinkedIn, Hotjar, YouTube ...) and your own patterns.
 *   2. By script handle: scripts enqueued with wp_enqueue_script() are matched by handle.
 *   3. By hand in a theme: <script type="text/plain" data-tlc-category="analytics" src="..."></script>
 *      or tlc_gated_html( 'analytics', $html ) for any markup.
 *   4. Custom code per category (Scripts tab), printed inert in <template> and inserted after consent.
 *
 * With Google Consent Mode on, Google tags are left to run by default: they read the consent state themselves
 * (Consent Mode "advanced"). Turn that off in the Script blocking tab to block them like any other script.
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Blocker {

	private static array $rules = array();

	/**
	 * Built-in rules. id => [ label, category, patterns, is_google ]. Patterns are matched, case-insensitive, as
	 * plain text inside a script's src or inline code, or an iframe's src. Filter: tlc_blocker_presets.
	 */
	public static function presets(): array {
		$presets = array(
			'google-analytics' => array( 'Google Analytics', 'analytics', array( 'google-analytics.com/analytics.js', 'google-analytics.com/ga.js', 'googletagmanager.com/gtag/js?id=G-', 'googletagmanager.com/gtag/js?id=UA-' ), true ),
			'google-ads'       => array( 'Google Ads and DoubleClick', 'marketing', array( 'googletagmanager.com/gtag/js?id=AW-', 'googleadservices.com', 'googlesyndication.com', 'doubleclick.net' ), true ),
			'meta-pixel'       => array( 'Meta (Facebook) Pixel', 'marketing', array( 'connect.facebook.net', 'fbevents.js' ), false ),
			'linkedin'         => array( 'LinkedIn Insight Tag', 'marketing', array( 'snap.licdn.com', '_linkedin_partner_id' ), false ),
			'microsoft-ads'    => array( 'Microsoft Advertising (UET)', 'marketing', array( 'bat.bing.com' ), false ),
			'tiktok'           => array( 'TikTok Pixel', 'marketing', array( 'analytics.tiktok.com' ), false ),
			'pinterest'        => array( 'Pinterest Tag', 'marketing', array( 's.pinimg.com/ct/' ), false ),
			'x-ads'            => array( 'X (Twitter) Pixel', 'marketing', array( 'static.ads-twitter.com' ), false ),
			'reddit'           => array( 'Reddit Pixel', 'marketing', array( 'redditstatic.com/ads' ), false ),
			'hubspot'          => array( 'HubSpot tracking', 'marketing', array( 'js.hs-scripts.com', 'js.hs-analytics.net', 'js.hsadspixel.net' ), false ),
			'hotjar'           => array( 'Hotjar', 'analytics', array( 'static.hotjar.com', 'script.hotjar.com' ), false ),
			'clarity'          => array( 'Microsoft Clarity', 'analytics', array( 'clarity.ms/tag' ), false ),
			'matomo-cloud'     => array( 'Matomo Cloud', 'analytics', array( 'cdn.matomo.cloud' ), false ),
			'youtube'          => array( 'YouTube embeds', 'marketing', array( 'youtube.com/embed', 'youtube-nocookie.com/embed' ), false ),
			'vimeo'            => array( 'Vimeo embeds', 'functional', array( 'player.vimeo.com' ), false ),
			'google-maps'      => array( 'Google Maps embeds', 'functional', array( 'google.com/maps/embed', 'maps.google.com/maps' ), false ),
			'intercom'         => array( 'Intercom chat', 'functional', array( 'widget.intercom.io' ), false ),
		);
		return (array) apply_filters( 'tlc_blocker_presets', $presets );
	}

	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'start' ), 9999 );
		add_filter( 'script_loader_tag', array( __CLASS__, 'by_handle' ), 20, 2 );
		add_action( 'wp_head', array( __CLASS__, 'custom_head' ), 20 );
		add_action( 'wp_footer', array( __CLASS__, 'custom_footer' ), 20 );
	}

	/** True when this request should get blocking (front-end HTML for visitors who see the banner). */
	public static function is_active(): bool {
		$active = TLC_Frontend::is_active() && ! is_feed() && ! is_robots() && ! is_trackback() && ! wp_doing_ajax() && ! wp_doing_cron()
			&& ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) && ! is_customize_preview();
		/**
		 * Turn script blocking off for a request, e.g. on a page builder's editing screen.
		 *
		 * @param bool $active
		 */
		return (bool) apply_filters( 'tlc_blocker_active', $active );
	}

	/** Rules in use: [ [ pattern, category ], ... ] for enabled categories. Filter: tlc_blocker_rules. */
	public static function rules(): array {
		$categories = array();
		foreach ( TLC_Settings::categories() as $cat ) {
			if ( empty( $cat['locked'] ) ) {
				$categories[ $cat['id'] ] = true;
			}
		}
		$skip_google = tlc_setting( 'consent_mode.enabled' ) && tlc_setting( 'blocking.google_gcm' );
		$enabled     = (array) tlc_setting( 'blocking.presets', array() );
		$rules       = array();
		foreach ( self::presets() as $id => $preset ) {
			list( , $category, $patterns, $is_google ) = $preset;
			if ( ! in_array( $id, $enabled, true ) || ( $is_google && $skip_google ) || empty( $categories[ $category ] ) ) {
				continue;
			}
			foreach ( $patterns as $pattern ) {
				$rules[] = array( $pattern, $category );
			}
		}
		foreach ( (array) tlc_setting( 'blocking.rules', array() ) as $rule ) {
			if ( ! empty( $categories[ $rule['category'] ] ) ) {
				$rules[] = array( (string) $rule['pattern'], (string) $rule['category'] );
			}
		}
		return (array) apply_filters( 'tlc_blocker_rules', $rules );
	}

	public static function start(): void {
		if ( ! tlc_setting( 'blocking.auto' ) || ! self::is_active() ) {
			return;
		}
		self::$rules = self::rules();
		if ( self::$rules ) {
			ob_start( array( __CLASS__, 'filter_html' ) );
		}
	}

	/** Output-buffer callback: block matching scripts and iframes in a full HTML page. */
	public static function filter_html( $html ) {
		if ( ! is_string( $html ) || '' === $html || false === stripos( $html, '<html' ) ) {
			return $html; // not a page (JSON, XML, a partial ...)
		}
		foreach ( headers_list() as $header ) {
			if ( 0 === stripos( $header, 'content-type:' ) && false === stripos( $header, 'html' ) ) {
				return $html;
			}
		}
		$out = preg_replace_callback( '#<script\b([^>]*)>(.*?)</script>#is', array( __CLASS__, 'script_tag' ), $html );
		if ( tlc_setting( 'blocking.iframes' ) && is_string( $out ) ) {
			$out = preg_replace_callback( '#<iframe\b([^>]*)>#i', array( __CLASS__, 'iframe_tag' ), $out );
		}
		return is_string( $out ) ? $out : $html; // on a regex failure, send the page unchanged
	}

	private static function script_tag( array $m ): string {
		$attrs = $m[1];
		if ( preg_match( '/\bdata-tlc-(category|skip)\b/i', $attrs ) || preg_match( '/(?<![\w-])id\s*=\s*["\']?tlc-/i', $attrs ) ) {
			return $m[0]; // already handled, or ours
		}
		$type = '';
		if ( preg_match( '/(?<![\w-])type\s*=\s*(["\']?)([^"\'\s>]+)\1/i', $attrs, $t ) ) {
			$type = strtolower( $t[2] );
			if ( ! in_array( $type, array( 'text/javascript', 'application/javascript', 'module' ), true ) ) {
				return $m[0]; // JSON-LD, templates, already text/plain ...
			}
		}
		$subject = preg_match( '/(?<![\w-])src\s*=\s*(["\']?)([^"\'\s>]+)\1/i', $attrs, $s ) ? $s[2] : $m[2];
		$category = self::match( $subject );
		if ( null === $category ) {
			return $m[0];
		}
		$attrs = preg_replace( '/\s*(?<![\w-])type\s*=\s*(["\']?)[^"\'\s>]+\1/i', '', $attrs );
		$extra = ' type="text/plain" data-tlc-category="' . esc_attr( $category ) . '"' . ( 'module' === $type ? ' data-tlc-type="module"' : '' );
		return '<script' . $extra . $attrs . '>' . $m[2] . '</script>';
	}

	private static function iframe_tag( array $m ): string {
		$attrs = $m[1];
		if ( preg_match( '/\bdata-tlc-(category|skip)\b/i', $attrs ) || ! preg_match( '/(?<![\w-])src\s*=\s*(["\'])(.*?)\1/is', $attrs, $s ) ) {
			return $m[0];
		}
		$category = self::match( html_entity_decode( $s[2] ) );
		if ( null === $category ) {
			return $m[0];
		}
		$attrs = str_replace( $s[0], 'data-tlc-src=' . $s[1] . $s[2] . $s[1], $attrs );
		return '<iframe data-tlc-category="' . esc_attr( $category ) . '"' . $attrs . '>';
	}

	/** The category of the first rule found in $subject, or null. */
	public static function match( string $subject ): ?string {
		if ( '' === $subject ) {
			return null;
		}
		foreach ( self::$rules ?: self::rules() as $rule ) {
			if ( '' !== $rule[0] && false !== stripos( $subject, $rule[0] ) ) {
				return $rule[1];
			}
		}
		return null;
	}

	/** Enqueued scripts held back by handle (Script blocking tab). */
	public static function by_handle( $tag, $handle ) {
		static $map = null;
		if ( null === $map ) {
			$map = array();
			foreach ( (array) tlc_setting( 'blocking.handles', array() ) as $row ) {
				$map[ $row['handle'] ] = $row['category'];
			}
		}
		if ( ! isset( $map[ $handle ] ) || ! self::is_active() ) {
			return $tag;
		}
		$category = esc_attr( $map[ $handle ] );
		return preg_replace_callback( '#<script\b([^>]*)>#i', function ( $m ) use ( $category ) {
			if ( str_contains( $m[1], 'data-tlc-' ) ) {
				return $m[0];
			}
			$attrs = preg_replace( '/\s*(?<![\w-])type\s*=\s*(["\']?)[^"\'\s>]+\1/i', '', $m[1] );
			return '<script type="text/plain" data-tlc-category="' . $category . '"' . $attrs . '>';
		}, $tag );
	}

	/** Wrap markup so it stays inert until its category is allowed. */
	public static function gate( string $html, string $category ): string {
		if ( '' === trim( $html ) ) {
			return '';
		}
		return '<template data-tlc-category="' . esc_attr( $category ) . '">' . $html . '</template>';
	}

	public static function custom_head(): void {
		self::custom( 'head' );
	}

	public static function custom_footer(): void {
		self::custom( 'footer' );
	}

	/** Custom code per category from the Scripts tab. Strictly necessary code is printed as is. */
	private static function custom( string $place ): void {
		if ( ! TLC_Frontend::is_active() ) {
			return;
		}
		$scripts = (array) tlc_setting( 'scripts', array() );
		foreach ( TLC_Settings::categories() as $cat ) {
			$code = (string) ( $scripts[ $cat['id'] ][ $place ] ?? '' );
			if ( '' === trim( $code ) ) {
				continue;
			}
			echo "\n" . ( ! empty( $cat['locked'] ) ? $code : self::gate( $code, $cat['id'] ) ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- admin code (unfiltered_html)
		}
	}
}
