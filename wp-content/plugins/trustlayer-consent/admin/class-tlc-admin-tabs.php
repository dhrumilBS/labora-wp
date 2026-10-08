<?php
/**
 * The settings tabs. Each method prints one tab's fields (the form, nonce, and Save button come from TLC_Admin).
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Admin_Tabs {

	private static function intro( string $text ): void {
		echo '<p class="tlc-intro">' . wp_kses( $text, array( 'code' => array(), 'strong' => array(), 'a' => array( 'href' => true ) ) ) . '</p>';
	}

	private static function table_open( string $title = '' ): void {
		if ( $title ) {
			echo '<h2 class="title">' . esc_html( $title ) . '</h2>';
		}
		echo '<table class="form-table" role="presentation"><tbody>';
	}

	private static function table_close(): void {
		echo '</tbody></table>';
	}

	private static function check_row( string $label, string $section, string $key, string $text, string $help = '' ): void {
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
		TLC_Admin_Fields::checkbox( $section, $key, $text, $help );
		echo '</td></tr>';
	}

	/* ---------- General ---------- */

	public static function general(): void {
		$f = 'TLC_Admin_Fields';
		self::intro( __( 'TrustLayer shows a consent banner, holds back optional scripts until they are allowed, and records each choice. Start here, then set the regions, categories, and design in the other tabs. Use <strong>Preview</strong> while setting up: only administrators see the banner.', 'trustlayer-consent' ) );
		self::table_open();
		self::check_row( __( 'Banner', 'trustlayer-consent' ), 'general', 'enabled', __( 'Show the banner and block scripts on this site', 'trustlayer-consent' ) );
		self::check_row( __( 'Preview', 'trustlayer-consent' ), 'general', 'preview', __( 'Only administrators see the banner (for setting up). Visitors get the site as if the plugin were off.', 'trustlayer-consent' ) );
		$f::row( __( 'Cookie name', 'trustlayer-consent' ), $f::text( 'general', 'cookie_name', array( 'class' => 'regular-text code' ) ), __( 'The one first-party cookie that stores the choice. Letters, numbers, - and _.', 'trustlayer-consent' ), 'tlc-general-cookie_name', 'general.cookie_name' );
		$f::row( __( 'Remember the choice for', 'trustlayer-consent' ), $f::number( 'general', 'cookie_days', 1, 730, __( 'days', 'trustlayer-consent' ) ), __( '180 days (6 months) is common; regulators generally expect no more than 12 months before asking again.', 'trustlayer-consent' ), 'tlc-general-cookie_days', 'general.cookie_days' );
		$f::row( __( 'Cookie domain', 'trustlayer-consent' ), $f::text( 'general', 'cookie_domain', array( 'placeholder' => 'example.com', 'class' => 'regular-text code' ) ), __( 'Leave empty for this host only. Enter the main domain (example.com) to share one choice across its subdomains.', 'trustlayer-consent' ), 'tlc-general-cookie_domain', 'general.cookie_domain' );
		/* translators: %d: consent revision */
		$f::row( __( 'Consent revision', 'trustlayer-consent' ), '<code>' . (int) TLC_Settings::saved()['general']['revision'] . '</code> <a class="button" href="' . esc_url( TLC_Admin::url( '', TLC_Admin::SLUG . '-tools' ) ) . '">' . esc_html__( 'Ask everyone again', 'trustlayer-consent' ) . '</a>', __( 'Choices made under an older revision no longer count. Raise it (Tools) after adding cookies or changing what a category does.', 'trustlayer-consent' ) );
		self::check_row( __( 'Global Privacy Control', 'trustlayer-consent' ), 'general', 'gpc', __( 'Honor the browser\'s GPC signal as an opt-out of sale and sharing', 'trustlayer-consent' ), __( 'Required in California, Colorado, Connecticut, New Jersey, and other states. Categories marked "sale or sharing" are switched off and no banner is shown; visitors can still change this in the preferences.', 'trustlayer-consent' ) );
		self::check_row( __( 'Search engines', 'trustlayer-consent' ), 'general', 'hide_for_bots', __( 'Do not show the banner to search engine crawlers and link previews', 'trustlayer-consent' ) );
		self::check_row( __( 'Reload on withdrawal', 'trustlayer-consent' ), 'general', 'reload', __( 'Reload the page when a visitor switches a category off, so scripts that already ran stop', 'trustlayer-consent' ) );
		$f::row( __( 'Elements that open the preferences', 'trustlayer-consent' ), $f::text( 'general', 'open_selectors', array( 'class' => 'large-text code', 'placeholder' => '.footer-cookie-link, [data-cookie-settings]' ) ), __( 'Already built in: <code>[data-tlc-open]</code>, links to <code>#tlc-preferences</code> (use it as a menu item URL), and the <code>[tlc_preferences]</code> shortcode. Add your theme\'s own CSS selectors here, comma-separated.', 'trustlayer-consent' ), 'tlc-general-open_selectors', 'general.open_selectors' );
		$f::row( __( 'Elements that open Do Not Sell or Share', 'trustlayer-consent' ), $f::text( 'general', 'dnss_selectors', array( 'class' => 'large-text code' ) ), __( 'Built in: <code>[data-tlc-dnss]</code>, links to <code>#tlc-dnss</code>, and <code>[tlc_dnss_link]</code>.', 'trustlayer-consent' ), 'tlc-general-dnss_selectors', 'general.dnss_selectors' );
		self::table_close();

		self::table_open( __( 'Consent log', 'trustlayer-consent' ) );
		self::check_row( __( 'Logging', 'trustlayer-consent' ), 'logs', 'enabled', __( 'Record each choice as proof of consent (TrustLayer > Consent log)', 'trustlayer-consent' ) );
		$f::row( __( 'Keep log rows for', 'trustlayer-consent' ), $f::number( 'logs', 'retention', 1, 3650, __( 'days', 'trustlayer-consent' ) ), __( 'Older rows are deleted daily. 365 days (12 months) by default.', 'trustlayer-consent' ), 'tlc-logs-retention', 'logs.retention' );
		$f::row( __( 'IP address', 'trustlayer-consent' ), $f::select( 'logs', 'ip', array( 'anonymize' => __( 'Store it shortened (last part removed)', 'trustlayer-consent' ), 'none' => __( 'Do not store it', 'trustlayer-consent' ) ) ), '', 'tlc-logs-ip', 'logs.ip' );
		self::check_row( __( 'Browser', 'trustlayer-consent' ), 'logs', 'user_agent', __( 'Store the browser\'s user agent with each choice', 'trustlayer-consent' ) );
		self::table_close();
	}

	/* ---------- Banner ---------- */

	public static function banner(): void {
		$f = 'TLC_Admin_Fields';
		self::intro( __( 'How the banner looks and behaves. To match a theme exactly, choose "Theme stylesheet only" and style the <code>.tlc</code> classes in the theme, or keep the plugin styles and set the CSS variables listed in <code>assets/css/consent.css</code>.', 'trustlayer-consent' ) );
		self::table_open();
		$f::row( __( 'Layout', 'trustlayer-consent' ), $f::select( 'banner', 'layout', array(
			'box-left'   => __( 'Box, bottom left', 'trustlayer-consent' ),
			'box-right'  => __( 'Box, bottom right', 'trustlayer-consent' ),
			'bar-bottom' => __( 'Bar, bottom of the screen', 'trustlayer-consent' ),
			'bar-top'    => __( 'Bar, top of the screen', 'trustlayer-consent' ),
			'modal'      => __( 'Dialog in the middle (blocks the page until a choice)', 'trustlayer-consent' ),
		) ), __( 'Bars open the preferences as a dialog in the middle. All layouts work on phones.', 'trustlayer-consent' ), 'tlc-banner-layout', 'banner.layout' );
		$f::row( __( 'Show after', 'trustlayer-consent' ), $f::number( 'banner', 'delay', 0, 30000, __( 'milliseconds after the page has loaded', 'trustlayer-consent' ) ), '', 'tlc-banner-delay', 'banner.delay' );
		self::check_row( __( 'Reject button', 'trustlayer-consent' ), 'banner', 'show_reject', __( 'Show "Reject all" on the first screen (opt-in regions)', 'trustlayer-consent' ), __( 'Recommended: EU and UK regulators expect rejecting to be as easy as accepting.', 'trustlayer-consent' ) );
		self::check_row( __( 'Equal buttons', 'trustlayer-consent' ), 'banner', 'equal_buttons', __( 'Give "Reject all" the same style as "Accept all"', 'trustlayer-consent' ) );
		self::check_row( __( 'Reopen button', 'trustlayer-consent' ), 'banner', 'reopen', __( 'Show a small floating button to reopen the preferences after a choice', 'trustlayer-consent' ), __( 'Not needed when the footer has a "Cookie settings" link.', 'trustlayer-consent' ) );
		$f::row( __( 'Styles', 'trustlayer-consent' ), $f::select( 'banner', 'css', array( 'full' => __( 'Plugin styles with the colors below', 'trustlayer-consent' ), 'none' => __( 'Theme stylesheet only (no plugin CSS)', 'trustlayer-consent' ) ) ), '', 'tlc-banner-css', 'banner.css' );
		$f::row( __( 'Theme button classes', 'trustlayer-consent' ), $f::text( 'banner', 'btn_primary_class', array( 'placeholder' => 'btn btn-primary', 'class' => 'regular-text code' ) ) . ' ' . $f::text( 'banner', 'btn_secondary_class', array( 'placeholder' => 'btn btn-outline', 'class' => 'regular-text code' ) ), __( 'Primary (Accept) and secondary (other) buttons. Leave empty to use the plugin\'s buttons; filled in, the theme\'s button styles are used.', 'trustlayer-consent' ) );
		$colors = array(
			'bg'           => __( 'Background', 'trustlayer-consent' ),
			'text'         => __( 'Text', 'trustlayer-consent' ),
			'heading'      => __( 'Headings', 'trustlayer-consent' ),
			'muted'        => __( 'Secondary text', 'trustlayer-consent' ),
			'border'       => __( 'Borders', 'trustlayer-consent' ),
			'accent'       => __( 'Links and switches', 'trustlayer-consent' ),
			'primary'      => __( 'Primary button', 'trustlayer-consent' ),
			'primary_text' => __( 'Primary button text', 'trustlayer-consent' ),
		);
		$grid = '<div class="tlc-colors">';
		foreach ( $colors as $key => $label ) {
			$grid .= '<label>' . $f::color( 'banner', 'colors', $key ) . ' <span>' . esc_html( $label ) . '</span></label>';
		}
		$f::row( __( 'Colors', 'trustlayer-consent' ), $grid . '</div>', __( 'Used with the plugin styles.', 'trustlayer-consent' ), '', 'banner.colors' );
		$f::row( __( 'Corner radius', 'trustlayer-consent' ), $f::number( 'banner', 'radius', 0, 48, 'px' ), '', 'tlc-banner-radius', 'banner.radius' );
		$f::row( __( 'Stacking order', 'trustlayer-consent' ), $f::number( 'banner', 'z_index', 1, 2147483647 ), __( 'CSS z-index. Lower it if the banner should sit under a sticky header or chat widget.', 'trustlayer-consent' ), 'tlc-banner-z_index', 'banner.z_index' );
		$f::row( __( 'Custom CSS', 'trustlayer-consent' ), $f::textarea( 'banner', 'custom_css', 6, 'large-text code' ), __( 'Added after the plugin styles, also with "Theme stylesheet only".', 'trustlayer-consent' ), 'tlc-banner-custom_css', 'banner.custom_css' );
		self::table_close();
	}

	/* ---------- Texts ---------- */

	public static function texts(): void {
		$f      = 'TLC_Admin_Fields';
		$labels = array(
			'banner_title'   => array( __( 'Opt-in banner: title', 'trustlayer-consent' ), '' ),
			'banner_text'    => array( __( 'Opt-in banner: text', 'trustlayer-consent' ), __( 'Shown where visitors must agree first (EU, UK ...). Links to your policies are added after it (Policies tab).', 'trustlayer-consent' ) ),
			'optout_title'   => array( __( 'Opt-out banner: title', 'trustlayer-consent' ), '' ),
			'optout_text'    => array( __( 'Opt-out banner: text', 'trustlayer-consent' ), __( 'Shown where optional cookies run until the visitor opts out (US).', 'trustlayer-consent' ) ),
			'notice_title'   => array( __( 'Notice banner: title', 'trustlayer-consent' ), '' ),
			'notice_text'    => array( __( 'Notice banner: text', 'trustlayer-consent' ), '' ),
			'accept_all'     => array( __( 'Button: accept all', 'trustlayer-consent' ), '' ),
			'reject_all'     => array( __( 'Button: reject all', 'trustlayer-consent' ), '' ),
			'customize'      => array( __( 'Button: customize', 'trustlayer-consent' ), '' ),
			'save'           => array( __( 'Button: save choices', 'trustlayer-consent' ), '' ),
			'ok'             => array( __( 'Button: OK (opt-out and notice banners)', 'trustlayer-consent' ), '' ),
			'opt_out'        => array( __( 'Button: opt out (opt-out banner)', 'trustlayer-consent' ), '' ),
			'prefs_title'    => array( __( 'Preferences: title', 'trustlayer-consent' ), '' ),
			'prefs_text'     => array( __( 'Preferences: text', 'trustlayer-consent' ), '' ),
			'always_on'      => array( __( 'Preferences: "always on" label', 'trustlayer-consent' ), '' ),
			'what_we_store'  => array( __( 'Preferences: cookie list toggle', 'trustlayer-consent' ), '' ),
			'nothing_stored' => array( __( 'Preferences: category with no cookies', 'trustlayer-consent' ), __( '{category} is replaced with the category name.', 'trustlayer-consent' ) ),
			'col_cookie'     => array( __( 'Cookie table: name column', 'trustlayer-consent' ), '' ),
			'col_provider'   => array( __( 'Cookie table: provider column', 'trustlayer-consent' ), __( 'Only shown when a cookie in the category has a provider.', 'trustlayer-consent' ) ),
			'col_purpose'    => array( __( 'Cookie table: purpose column', 'trustlayer-consent' ), '' ),
			'col_duration'   => array( __( 'Cookie table: duration column', 'trustlayer-consent' ), '' ),
			'cookie_policy'  => array( __( 'Link: cookie policy', 'trustlayer-consent' ), '' ),
			'privacy_policy' => array( __( 'Link: privacy policy', 'trustlayer-consent' ), '' ),
			'dnss_link'      => array( __( 'Do Not Sell: link text', 'trustlayer-consent' ), __( 'Used by the [tlc_dnss_link] shortcode. California also accepts "Your Privacy Choices".', 'trustlayer-consent' ) ),
			'dnss_title'     => array( __( 'Do Not Sell: title', 'trustlayer-consent' ), '' ),
			'dnss_text'      => array( __( 'Do Not Sell: text', 'trustlayer-consent' ), '' ),
			'dnss_toggle'    => array( __( 'Do Not Sell: switch label', 'trustlayer-consent' ), '' ),
			'dnss_saved'     => array( __( 'Do Not Sell: saved message', 'trustlayer-consent' ), '' ),
			'gpc_notice'     => array( __( 'Global Privacy Control notice', 'trustlayer-consent' ), '' ),
			'embed_text'     => array( __( 'Blocked embed: text', 'trustlayer-consent' ), __( '{category} is replaced with the category name.', 'trustlayer-consent' ) ),
			'embed_button'   => array( __( 'Blocked embed: button', 'trustlayer-consent' ), '' ),
			'reopen'         => array( __( 'Reopen button and [tlc_preferences] label', 'trustlayer-consent' ), '' ),
			'close'          => array( __( 'Close (screen readers)', 'trustlayer-consent' ), '' ),
		);
		self::intro( __( 'Everything visitors read. Longer texts may contain links (<code>&lt;a href="..."&gt;</code>) and <code>&lt;strong&gt;</code>. Empty fields go back to the default. For more languages, change texts per language with the <code>tlc_frontend_config</code> filter.', 'trustlayer-consent' ) );
		self::table_open();
		$html = TLC_Settings::html_texts();
		foreach ( $labels as $key => list( $label, $help ) ) {
			$field = in_array( $key, $html, true ) ? $f::textarea( 'texts', $key, 3 ) : $f::text( 'texts', $key, array( 'class' => 'large-text' ) );
			$f::row( $label, $field, $help, 'tlc-texts-' . $key, 'texts.' . $key );
		}
		self::table_close();
	}

	/* ---------- Categories ---------- */

	public static function categories(): void {
		self::intro( __( 'Visitors allow or refuse each category. List every cookie the site sets in the right category, so the preferences and the <code>[tlc_cookie_table]</code> shortcode show them; names ending in <code>*</code> match a prefix (<code>_ga*</code>). When a visitor switches a category off, its listed cookies are deleted. <strong>Sale or sharing</strong> marks categories covered by US "Do Not Sell or Share" and Global Privacy Control. Consent Mode signals are granted when the category is allowed.', 'trustlayer-consent' ) );
		$cats = (array) ( TLC_Settings::saved()['categories'] ?? array() );
		echo '<div class="tlc-cats-admin" data-tlc-rows="categories">';
		foreach ( $cats as $i => $cat ) {
			self::category_card( (string) $i, $cat );
		}
		echo '</div>';
		echo '<p><button type="button" class="button" data-tlc-add="tlc-tpl-category" data-tlc-target="[data-tlc-rows=categories]">' . esc_html__( 'Add a category', 'trustlayer-consent' ) . '</button></p>';
		echo '<template id="tlc-tpl-category">';
		self::category_card( '__c__', array( 'id' => '', 'enabled' => 1, 'locked' => 0, 'title' => '', 'description' => '', 'sale_share' => 0, 'gcm' => array(), 'cookies' => array() ), true );
		echo '</template>';
		echo '<template id="tlc-tpl-cookie">';
		self::cookie_row( '__c__', '__k__', array( 'name' => '', 'provider' => '', 'purpose' => '', 'duration' => '' ) );
		echo '</template>';
	}

	private static function category_card( string $i, array $cat, bool $is_new = false ): void {
		$n      = "tlc[categories][{$i}]";
		$locked = ! empty( $cat['locked'] );
		echo '<div class="tlc-card tlc-cat-card" data-tlc-row>';
		echo '<div class="tlc-cat-card-head">';
		if ( $is_new ) {
			printf( '<label>%s <input type="text" name="%s[id]" class="code" pattern="[a-z0-9_\-]+" required placeholder="preferences"></label>', esc_html__( 'ID', 'trustlayer-consent' ), esc_attr( $n ) );
		} else {
			printf( '<code class="tlc-cat-id">%s</code><input type="hidden" name="%s[id]" value="%s">', esc_html( $cat['id'] ), esc_attr( $n ), esc_attr( $cat['id'] ) );
		}
		if ( $locked ) {
			echo '<span class="tlc-chip is-on">' . esc_html__( 'Always on', 'trustlayer-consent' ) . '</span>';
		} else {
			printf( '<label><input type="checkbox" name="%s[enabled]" value="1" %s> %s</label>', esc_attr( $n ), checked( ! empty( $cat['enabled'] ), true, false ), esc_html__( 'Shown to visitors', 'trustlayer-consent' ) );
			printf( '<label><input type="checkbox" name="%s[sale_share]" value="1" %s> %s</label>', esc_attr( $n ), checked( ! empty( $cat['sale_share'] ), true, false ), esc_html__( 'Sale or sharing (US)', 'trustlayer-consent' ) );
			if ( ! in_array( $cat['id'], array( 'functional', 'analytics', 'marketing' ), true ) ) {
				echo '<button type="button" class="button-link button-link-delete" data-tlc-remove>' . esc_html__( 'Remove category', 'trustlayer-consent' ) . '</button>';
			}
		}
		echo '</div>';
		printf( '<p><label>%s<br><input type="text" name="%s[title]" value="%s" class="regular-text" required></label></p>', esc_html__( 'Name', 'trustlayer-consent' ), esc_attr( $n ), esc_attr( $cat['title'] ) );
		printf( '<p><label>%s<br><textarea name="%s[description]" rows="2" class="large-text">%s</textarea></label></p>', esc_html__( 'Description', 'trustlayer-consent' ), esc_attr( $n ), esc_textarea( $cat['description'] ) );
		echo '<fieldset class="tlc-gcm"><legend>' . esc_html__( 'Google Consent Mode signals granted by this category', 'trustlayer-consent' ) . '</legend>';
		foreach ( TLC_Settings::GCM_SIGNALS as $signal ) {
			printf( '<label><input type="checkbox" name="%s[gcm][]" value="%s" %s> <code>%s</code></label> ', esc_attr( $n ), esc_attr( $signal ), checked( in_array( $signal, (array) $cat['gcm'], true ), true, false ), esc_html( $signal ) );
		}
		echo '</fieldset>';
		echo '<table class="widefat tlc-cookies"><thead><tr><th>' . esc_html__( 'Cookie name', 'trustlayer-consent' ) . '</th><th>' . esc_html__( 'Provider', 'trustlayer-consent' ) . '</th><th>' . esc_html__( 'Purpose', 'trustlayer-consent' ) . '</th><th>' . esc_html__( 'Kept for', 'trustlayer-consent' ) . '</th><th><span class="screen-reader-text">' . esc_html__( 'Remove', 'trustlayer-consent' ) . '</span></th></tr></thead><tbody data-tlc-rows="cookies-' . esc_attr( $i ) . '">';
		if ( $locked ) {
			/* translators: %s: cookie name */
			echo '<tr class="tlc-auto-row"><td colspan="5">' . esc_html( sprintf( __( '%s (the consent cookie) is listed automatically.', 'trustlayer-consent' ), tlc_setting( 'general.cookie_name' ) ) ) . '</td></tr>';
		}
		foreach ( (array) $cat['cookies'] as $k => $cookie ) {
			self::cookie_row( $i, (string) $k, $cookie );
		}
		echo '</tbody></table>';
		printf( '<p><button type="button" class="button button-small" data-tlc-add="tlc-tpl-cookie" data-tlc-target="[data-tlc-rows=\'cookies-%1$s\']" data-tlc-c="%1$s">%2$s</button></p>', esc_attr( $i ), esc_html__( 'Add a cookie', 'trustlayer-consent' ) );
		echo '</div>';
	}

	private static function cookie_row( string $i, string $k, array $c ): void {
		$n = "tlc[categories][{$i}][cookies][{$k}]";
		echo '<tr data-tlc-row>';
		printf( '<td><input type="text" name="%s[name]" value="%s" class="code" placeholder="_ga*"></td>', esc_attr( $n ), esc_attr( $c['name'] ) );
		printf( '<td><input type="text" name="%s[provider]" value="%s" placeholder="Google"></td>', esc_attr( $n ), esc_attr( $c['provider'] ) );
		printf( '<td><input type="text" name="%s[purpose]" value="%s" class="widefat"></td>', esc_attr( $n ), esc_attr( $c['purpose'] ) );
		printf( '<td><input type="text" name="%s[duration]" value="%s" placeholder="2 years"></td>', esc_attr( $n ), esc_attr( $c['duration'] ) );
		echo '<td><button type="button" class="button-link button-link-delete" data-tlc-remove>' . esc_html__( 'Remove', 'trustlayer-consent' ) . '</button></td></tr>';
	}

	/* ---------- Regions ---------- */

	public static function regions(): void {
		$f = 'TLC_Admin_Fields';
		self::intro( __( 'Each region gets a consent model. <strong>Opt-in</strong>: nothing optional runs until the visitor agrees (EU GDPR, UK, Brazil, Quebec). <strong>Opt-out</strong>: optional cookies run, visitors are told and can opt out of sale and sharing (US state laws such as CCPA/CPRA). <strong>Notice</strong>: a short notice, everything runs. <strong>None</strong>: no banner. The location comes from your CDN\'s headers first, then from a MaxMind database on this server.', 'trustlayer-consent' ) );
		self::table_open();
		self::check_row( __( 'Geo-targeting', 'trustlayer-consent' ), 'geo', 'enabled', __( 'Use the visitor\'s location to choose the model', 'trustlayer-consent' ), __( 'Off: everyone gets the "Everywhere else" model.', 'trustlayer-consent' ) );
		$models = TLC_Geo::model_labels();
		foreach ( TLC_Geo::group_labels() as $group => $label ) {
			$f::row( $label, $f::select( 'geo', 'models', $models, $group ), 'unknown' === $group ? __( 'Used when no header or database knows the location. Opt-in is the safe choice.', 'trustlayer-consent' ) : '', 'tlc-geo-models-' . $group, 'geo.models.' . $group );
		}
		$f::row( __( 'Overrides', 'trustlayer-consent' ), $f::textarea( 'geo', 'overrides', 4, 'large-text code' ), __( 'One per line: a country or US state code and a model, e.g. <code>US-CA: opt-in</code> or <code>IN: notice</code>. Overrides win over the regions above.', 'trustlayer-consent' ), 'tlc-geo-overrides', 'geo.overrides' );
		self::table_close();

		self::table_open( __( 'Location sources', 'trustlayer-consent' ) );
		self::check_row( __( 'CDN headers', 'trustlayer-consent' ), 'geo', 'headers', __( 'Read the country from CDN or server headers', 'trustlayer-consent' ), __( 'Cloudflare (CF-IPCountry; turn on "Add visitor location headers" for US states), Amazon CloudFront, Vercel, Google App Engine, and GeoIP modules (X-Country-Code, GEOIP_COUNTRY_CODE).', 'trustlayer-consent' ) );
		$f::row( __( 'Custom headers', 'trustlayer-consent' ), $f::text( 'geo', 'custom_header', array( 'placeholder' => 'X-My-Country', 'class' => 'regular-text code' ) ) . ' ' . $f::text( 'geo', 'region_header', array( 'placeholder' => 'X-My-Region', 'class' => 'regular-text code' ) ), __( 'Country header and region (state) header set by your host, if it uses other names.', 'trustlayer-consent' ) );
		self::check_row( __( 'MaxMind', 'trustlayer-consent' ), 'geo', 'maxmind', __( 'Look the address up in a MaxMind database when no header answers', 'trustlayer-consent' ) );
		$f::row( __( 'MaxMind account', 'trustlayer-consent' ), $f::text( 'geo', 'maxmind_account', array( 'class' => 'small-text code', 'style' => 'width:8em', 'placeholder' => '123456', 'autocomplete' => 'off' ) ) . ' ' . sprintf(
			'<input type="password" name="tlc[geo][maxmind_key]" id="tlc-geo-maxmind_key" class="regular-text code" autocomplete="new-password" placeholder="%s">',
			esc_attr( '' !== (string) ( TLC_Settings::saved()['geo']['maxmind_key'] ?? '' ) ? __( 'License key saved (enter a new one to replace it)', 'trustlayer-consent' ) : __( 'License key', 'trustlayer-consent' ) )
		) . ( '' !== (string) ( TLC_Settings::saved()['geo']['maxmind_key'] ?? '' ) ? ' <label><input type="checkbox" name="tlc[geo][maxmind_key_clear]" value="1"> ' . esc_html__( 'Remove key', 'trustlayer-consent' ) . '</label>' : '' ), __( 'Free at maxmind.com (GeoLite2). With an account ID and license key, the database is downloaded here and updated weekly. The key is stored in the database, never exported.', 'trustlayer-consent' ), 'tlc-geo-maxmind_account' );
		$f::row( __( 'Database', 'trustlayer-consent' ), $f::select( 'geo', 'maxmind_edition', array( 'GeoLite2-Country' => __( 'GeoLite2 Country (about 10 MB; countries)', 'trustlayer-consent' ), 'GeoLite2-City' => __( 'GeoLite2 City (about 60 MB; countries and US states)', 'trustlayer-consent' ) ) ), __( 'Choose City when US states need different models.', 'trustlayer-consent' ), 'tlc-geo-maxmind_edition', 'geo.maxmind_edition' );
		$f::row( __( 'Or your own file', 'trustlayer-consent' ), $f::text( 'geo', 'maxmind_path', array( 'class' => 'large-text code', 'placeholder' => '/var/lib/GeoIP/GeoLite2-City.mmdb' ) ), __( 'Path to a .mmdb file kept up to date by your server (geoipupdate). Also possible: the TLC_MAXMIND_DB constant.', 'trustlayer-consent' ), 'tlc-geo-maxmind_path', 'geo.maxmind_path' );
		$mm    = TLC_Maxmind::status();
		$state = $mm['file'] ? sprintf( '<code>%s</code><br>%s', esc_html( $mm['file'] ), esc_html( trim( $mm['type'] . ( $mm['built'] ? ', ' . $mm['built'] : '' ) ) ) ) : esc_html__( 'No database found.', 'trustlayer-consent' );
		if ( ! empty( $mm['last']['time'] ) ) {
			/* translators: %s: date */
			$state .= '<br>' . esc_html( sprintf( __( 'Last download: %s', 'trustlayer-consent' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $mm['last']['time'] ) ) ) . ( $mm['last']['ok'] ? '' : ' <strong class="tlc-error">' . esc_html( $mm['last']['error'] ) . '</strong>' );
		}
		$download = wp_nonce_url( add_query_arg( 'action', 'tlc_maxmind_update', admin_url( 'admin-post.php' ) ), 'tlc_maxmind_update' );
		$f::row( __( 'Status', 'trustlayer-consent' ), $state . '<p><a class="button" href="' . esc_url( $download ) . '">' . esc_html__( 'Download now', 'trustlayer-consent' ) . '</a></p>', __( 'Save the account and key first. This product includes GeoLite2 data created by MaxMind, available from https://www.maxmind.com.', 'trustlayer-consent' ) );
		$f::row( __( 'Visitor IP header', 'trustlayer-consent' ), $f::text( 'geo', 'ip_header', array( 'class' => 'regular-text code', 'placeholder' => 'X-Forwarded-For' ) ), __( 'Leave empty unless the site is behind a proxy or load balancer that puts the visitor\'s address in a header. Visitors can send this header themselves, so only set it when the proxy always overwrites it.', 'trustlayer-consent' ), 'tlc-geo-ip_header', 'geo.ip_header' );
		self::table_close();

		// Test: the admin's own request, or an IP from ?tlc_ip=
		$ip   = isset( $_GET['tlc_ip'] ) ? sanitize_text_field( wp_unslash( $_GET['tlc_ip'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$test = TLC_Geo::resolve( filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : null );
		echo '<h2 class="title">' . esc_html__( 'Test', 'trustlayer-consent' ) . '</h2><p>';
		/* translators: 1: country, 2: region, 3: source, 4: group, 5: model */
		echo esc_html( sprintf( __( '%1$s: country %2$s, state %3$s (from %4$s), region "%5$s", model %6$s.', 'trustlayer-consent' ), $ip ?: __( 'Your request', 'trustlayer-consent' ), $test['country'] ?: '-', $test['region'] ?: '-', $test['source'] ?: __( 'nothing', 'trustlayer-consent' ), $test['group'], $test['model'] ) );
		echo '</p><p><input type="text" class="regular-text code" id="tlc-test-ip" placeholder="8.8.8.8" value="' . esc_attr( $ip ) . '"> <a class="button" href="' . esc_url( TLC_Admin::url( 'regions' ) ) . '" data-tlc-test-ip>' . esc_html__( 'Look up an address', 'trustlayer-consent' ) . '</a> <span class="description">' . esc_html__( 'Save changes first; the lookup uses the MaxMind database.', 'trustlayer-consent' ) . '</span></p>';
	}

	/* ---------- Script blocking ---------- */

	public static function blocking(): void {
		$f      = 'TLC_Admin_Fields';
		$saved  = TLC_Settings::saved()['blocking'];
		$opts   = TLC_Admin_Fields::category_options();
		self::intro( __( 'Optional scripts and embeds must not run before they are allowed. TrustLayer scans each page once and holds back matching <code>&lt;script&gt;</code> tags (as <code>type="text/plain"</code>) and <code>&lt;iframe&gt;</code> embeds (with an "Allow and show" placeholder), then releases them in the browser when the visitor allows their category. In themes, mark scripts by hand: <code>&lt;script type="text/plain" data-tlc-category="analytics" src="..."&gt;&lt;/script&gt;</code>.', 'trustlayer-consent' ) );
		self::table_open();
		self::check_row( __( 'Automatic blocking', 'trustlayer-consent' ), 'blocking', 'auto', __( 'Scan pages and hold back scripts that match the rules below', 'trustlayer-consent' ) );
		self::check_row( __( 'Embeds', 'trustlayer-consent' ), 'blocking', 'iframes', __( 'Also hold back matching iframes (YouTube, Vimeo, maps ...)', 'trustlayer-consent' ) );
		self::check_row( __( 'Google tags', 'trustlayer-consent' ), 'blocking', 'google_gcm', __( 'Let Google tags run when Google Consent Mode is on (they respect the consent state)', 'trustlayer-consent' ), __( 'Turn off to block Google Analytics and Ads tags like other scripts ("basic" Consent Mode).', 'trustlayer-consent' ) );
		self::table_close();

		echo '<h2 class="title">' . esc_html__( 'Known services', 'trustlayer-consent' ) . '</h2><table class="widefat striped tlc-presets"><thead><tr><td class="check-column"></td><th>' . esc_html__( 'Service', 'trustlayer-consent' ) . '</th><th>' . esc_html__( 'Category', 'trustlayer-consent' ) . '</th><th>' . esc_html__( 'Matches', 'trustlayer-consent' ) . '</th></tr></thead><tbody>';
		foreach ( TLC_Blocker::presets() as $id => $p ) {
			printf(
				'<tr><th scope="row" class="check-column"><input type="checkbox" name="tlc[blocking][presets][]" value="%s" id="tlc-preset-%s" %s></th><td><label for="tlc-preset-%2$s">%s</label>%s</td><td>%s</td><td><code>%s</code></td></tr>',
				esc_attr( $id ),
				esc_attr( $id ),
				checked( in_array( $id, (array) $saved['presets'], true ), true, false ),
				esc_html( $p[0] ),
				$p[3] ? ' <span class="tlc-chip">' . esc_html__( 'Google: follows the setting above', 'trustlayer-consent' ) . '</span>' : '',
				esc_html( $opts[ $p[1] ] ?? $p[1] ),
				esc_html( implode( ', ', $p[2] ) )
			);
		}
		echo '</tbody></table>';

		echo '<h2 class="title">' . esc_html__( 'Your rules', 'trustlayer-consent' ) . '</h2><p class="description">' . esc_html__( 'Text found in a script\'s address or code, or an iframe\'s address (case does not matter). Example: "cdn.example-chat.com" in Functional.', 'trustlayer-consent' ) . '</p>';
		echo '<table class="widefat tlc-repeat"><thead><tr><th>' . esc_html__( 'Text to match', 'trustlayer-consent' ) . '</th><th>' . esc_html__( 'Category', 'trustlayer-consent' ) . '</th><th></th></tr></thead><tbody data-tlc-rows="rules">';
		foreach ( (array) $saved['rules'] as $k => $rule ) {
			self::rule_row( 'rules', 'pattern', (string) $k, $rule['pattern'], $rule['category'], $opts );
		}
		echo '</tbody></table><p><button type="button" class="button" data-tlc-add="tlc-tpl-rule" data-tlc-target="[data-tlc-rows=rules]">' . esc_html__( 'Add a rule', 'trustlayer-consent' ) . '</button></p>';
		echo '<template id="tlc-tpl-rule">';
		self::rule_row( 'rules', 'pattern', '__k__', '', '', $opts );
		echo '</template>';

		echo '<h2 class="title">' . esc_html__( 'Script handles', 'trustlayer-consent' ) . '</h2><p class="description">' . esc_html__( 'Scripts added by plugins with wp_enqueue_script(), by handle. Works even with automatic blocking off.', 'trustlayer-consent' ) . '</p>';
		echo '<table class="widefat tlc-repeat"><thead><tr><th>' . esc_html__( 'Handle', 'trustlayer-consent' ) . '</th><th>' . esc_html__( 'Category', 'trustlayer-consent' ) . '</th><th></th></tr></thead><tbody data-tlc-rows="handles">';
		foreach ( (array) $saved['handles'] as $k => $row ) {
			self::rule_row( 'handles', 'handle', (string) $k, $row['handle'], $row['category'], $opts );
		}
		echo '</tbody></table><p><button type="button" class="button" data-tlc-add="tlc-tpl-handle" data-tlc-target="[data-tlc-rows=handles]">' . esc_html__( 'Add a handle', 'trustlayer-consent' ) . '</button></p>';
		echo '<template id="tlc-tpl-handle">';
		self::rule_row( 'handles', 'handle', '__k__', '', '', $opts );
		echo '</template>';

		self::table_open( __( 'Compatibility', 'trustlayer-consent' ) );
		$f::row( __( 'Other attribute', 'trustlayer-consent' ), $f::text( 'blocking', 'legacy_attr', array( 'class' => 'regular-text code', 'placeholder' => 'data-consent' ) ), __( 'Also release <code>&lt;script type="text/plain" data-consent="analytics"&gt;</code> tags written for another consent tool (enter the attribute name).', 'trustlayer-consent' ), 'tlc-blocking-legacy_attr', 'blocking.legacy_attr' );
		self::table_close();
	}

	private static function rule_row( string $list, string $field, string $k, string $value, string $category, array $opts ): void {
		echo '<tr data-tlc-row>';
		printf( '<td><input type="text" name="tlc[blocking][%s][%s][%s]" value="%s" class="regular-text code"></td>', esc_attr( $list ), esc_attr( $k ), esc_attr( $field ), esc_attr( $value ) );
		printf( '<td><select name="tlc[blocking][%s][%s][category]">%s</select></td>', esc_attr( $list ), esc_attr( $k ), TLC_Admin_Fields::options_html( $opts, $category ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<td><button type="button" class="button-link button-link-delete" data-tlc-remove>' . esc_html__( 'Remove', 'trustlayer-consent' ) . '</button></td></tr>';
	}

	/* ---------- Consent Mode ---------- */

	public static function consent_mode(): void {
		$f = 'TLC_Admin_Fields';
		self::intro( __( 'Google Consent Mode v2 tells Google tags (Analytics, Ads, Tag Manager) what the visitor allowed. TrustLayer sets the defaults first in &lt;head&gt;, per region, then updates them when the visitor chooses. Load Google Tag Manager or gtag.js as usual, after it. Required by Google for ads measurement in the EEA and UK.', 'trustlayer-consent' ) );
		self::table_open();
		self::check_row( __( 'Consent Mode', 'trustlayer-consent' ), 'consent_mode', 'enabled', __( 'Send Google Consent Mode v2 signals', 'trustlayer-consent' ) );
		$f::row( __( 'Wait for the choice', 'trustlayer-consent' ), $f::number( 'consent_mode', 'wait', 0, 10000, __( 'milliseconds', 'trustlayer-consent' ) ), __( 'How long Google tags wait for an update in opt-in regions (wait_for_update).', 'trustlayer-consent' ), 'tlc-consent_mode-wait', 'consent_mode.wait' );
		self::check_row( __( 'Ads data redaction', 'trustlayer-consent' ), 'consent_mode', 'ads_data_redaction', __( 'Remove ad click identifiers while ad_storage is denied', 'trustlayer-consent' ) );
		self::check_row( __( 'URL passthrough', 'trustlayer-consent' ), 'consent_mode', 'url_passthrough', __( 'Pass ad click information in links while cookies are denied', 'trustlayer-consent' ) );
		self::check_row( __( 'Microsoft UET', 'trustlayer-consent' ), 'consent_mode', 'uet', __( 'Also send consent to Microsoft Advertising (UET consent mode, ad_storage)', 'trustlayer-consent' ) );
		self::table_close();
		echo '<h2 class="title">' . esc_html__( 'In Google Tag Manager', 'trustlayer-consent' ) . '</h2><p>' . wp_kses( __( 'Each choice pushes <code>{ event: "tlc_consent", tlc_analytics: "granted", tlc_marketing: "denied", ... }</code> to the dataLayer, also on every page view. Use Consent Mode\'s built-in consent checks on your tags, or a Custom Event trigger on <code>tlc_consent</code> for tags without them.', 'trustlayer-consent' ), array( 'code' => array() ) ) . '</p>';
		$cfg = TLC_Frontend::config();
		echo '<details><summary>' . esc_html__( 'Show the code printed in <head>', 'trustlayer-consent' ) . '</summary><pre class="tlc-code">' . esc_html( str_replace( ';', ";\n", TLC_Consent_Mode::script( $cfg ) ) ) . '</pre></details>';
	}

	/* ---------- Custom scripts ---------- */

	public static function scripts(): void {
		$can   = current_user_can( 'unfiltered_html' );
		$saved = (array) ( TLC_Settings::saved()['scripts'] ?? array() );
		self::intro( __( 'Paste tracking code here instead of in the theme: it is printed inert and only inserted after the visitor allows its category (Strictly necessary code runs right away). Paste it exactly as the provider gives it, with its &lt;script&gt; tags.', 'trustlayer-consent' ) );
		if ( ! $can ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Only users allowed to add unfiltered HTML (administrators) can change custom scripts.', 'trustlayer-consent' ) . '</p></div>';
		}
		foreach ( (array) ( TLC_Settings::saved()['categories'] ?? array() ) as $cat ) {
			echo '<div class="tlc-card"><h2>' . esc_html( $cat['title'] ) . ( empty( $cat['enabled'] ) ? ' <span class="tlc-chip">' . esc_html__( 'Off', 'trustlayer-consent' ) . '</span>' : '' ) . '</h2>';
			foreach ( array( 'head' => __( 'In &lt;head&gt;', 'trustlayer-consent' ), 'footer' => __( 'Before &lt;/body&gt;', 'trustlayer-consent' ) ) as $place => $label ) {
				printf(
					'<p><label for="tlc-script-%1$s-%2$s"><strong>%3$s</strong></label><br><textarea id="tlc-script-%1$s-%2$s" name="tlc[scripts][%1$s][%2$s]" rows="5" class="large-text code" %4$s>%5$s</textarea></p>',
					esc_attr( $cat['id'] ),
					esc_attr( $place ),
					wp_kses( $label, array() ),
					$can ? '' : 'disabled',
					esc_textarea( (string) ( $saved[ $cat['id'] ][ $place ] ?? '' ) )
				);
			}
			echo '</div>';
		}
	}

	/* ---------- Policies ---------- */

	public static function policy(): void {
		$f = 'TLC_Admin_Fields';
		self::intro( __( 'Link the banner to your policies, and choose where the US "Do Not Sell or Share My Personal Information" link appears. Put the link in the footer as a menu item pointing to <code>#tlc-dnss</code>, or with the <code>[tlc_dnss_link]</code> shortcode; it is hidden for visitors it does not apply to.', 'trustlayer-consent' ) );
		self::table_open();
		$pages = function ( string $key ) {
			return wp_dropdown_pages( array(
				'name'              => "tlc[policy][{$key}]",
				'id'                => "tlc-policy-{$key}",
				'selected'          => (int) TLC_Admin_Fields::value( 'policy', $key ),
				'show_option_none'  => __( '(none)', 'trustlayer-consent' ),
				'option_none_value' => '0',
				'echo'              => false,
				'post_status'       => array( 'publish', 'draft' ),
			) );
		};
		$f::row( __( 'Cookie policy', 'trustlayer-consent' ), $pages( 'cookie_page' ) . ' ' . esc_html__( 'or', 'trustlayer-consent' ) . ' ' . $f::text( 'policy', 'cookie_url', array( 'type' => 'url', 'placeholder' => 'https://', 'class' => 'regular-text code' ) ), __( 'A page wins over the address. Draft pages are linked once published. Tip: add [tlc_cookie_table] to the page to list every cookie.', 'trustlayer-consent' ), 'tlc-policy-cookie_page' );
		$f::row( __( 'Privacy policy', 'trustlayer-consent' ), $pages( 'privacy_page' ) . ' ' . esc_html__( 'or', 'trustlayer-consent' ) . ' ' . $f::text( 'policy', 'privacy_url', array( 'type' => 'url', 'placeholder' => 'https://', 'class' => 'regular-text code' ) ), '', 'tlc-policy-privacy_page' );
		echo '<tr><th scope="row">' . esc_html__( 'Links in the banner', 'trustlayer-consent' ) . '</th><td>';
		TLC_Admin_Fields::checkbox( 'policy', 'show_cookie', __( 'Cookie policy', 'trustlayer-consent' ) );
		TLC_Admin_Fields::checkbox( 'policy', 'show_privacy', __( 'Privacy policy', 'trustlayer-consent' ) );
		echo '</td></tr>';
		$f::row( __( 'Do Not Sell or Share link', 'trustlayer-consent' ), $f::select( 'policy', 'dnss_visibility', array(
			'opt-out' => __( 'Show to visitors in opt-out regions (US)', 'trustlayer-consent' ),
			'always'  => __( 'Show to everyone', 'trustlayer-consent' ),
			'never'   => __( 'Never show', 'trustlayer-consent' ),
		) ), __( 'It opens a small form to opt out of the categories marked "Sale or sharing".', 'trustlayer-consent' ), 'tlc-policy-dnss_visibility', 'policy.dnss_visibility' );
		self::table_close();
		echo '<h2 class="title">' . esc_html__( 'Shortcodes', 'trustlayer-consent' ) . '</h2><table class="widefat striped"><tbody>';
		$codes = array(
			'[tlc_cookie_table]'                  => __( 'Every category with its cookies (or one: category="analytics"), for the cookie policy.', 'trustlayer-consent' ),
			'[tlc_preferences text="Cookie settings"]' => __( 'A button that opens the preferences.', 'trustlayer-consent' ),
			'[tlc_dnss_link]'                     => __( 'The Do Not Sell or Share link, shown only where it applies.', 'trustlayer-consent' ),
			'[tlc_consent_details]'               => __( 'The visitor\'s consent ID, date, and choices (proof for them, matches the log).', 'trustlayer-consent' ),
		);
		foreach ( $codes as $code => $text ) {
			echo '<tr><td><code>' . esc_html( $code ) . '</code></td><td>' . esc_html( $text ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	/* ---------- Developers ---------- */

	public static function developers(): void {
		self::intro( __( 'Reference for themes and plugins. The full guide is in the plugin\'s README.md.', 'trustlayer-consent' ) );
		$sections = array(
			__( 'Hold back code in a theme', 'trustlayer-consent' ) => array(
				'<script type="text/plain" data-tlc-category="analytics" src="https://example.com/a.js"></script>' => __( 'Runs once Analytics is allowed. Inline scripts work the same way.', 'trustlayer-consent' ),
				'<iframe data-tlc-category="marketing" data-tlc-src="https://www.youtube.com/embed/..."></iframe>' => __( 'Loads once allowed; a placeholder with an Allow button is shown until then.', 'trustlayer-consent' ),
				'<?php echo tlc_gated_html( \'marketing\', $html ); ?>' => __( 'Any markup, inserted after consent.', 'trustlayer-consent' ),
				'<a href="#tlc-preferences">  <a href="#tlc-dnss">' => __( 'Links (or menu items) that open the preferences / Do Not Sell or Share.', 'trustlayer-consent' ),
			),
			__( 'JavaScript', 'trustlayer-consent' ) => array(
				'TrustLayer.ready(function (tl) { ... })' => __( 'Run code once the visitor\'s consent is known (safe to call before the script loads).', 'trustlayer-consent' ),
				'TrustLayer.has(\'analytics\')' => __( 'true when the category is allowed now.', 'trustlayer-consent' ),
				'TrustLayer.get()' => __( '{ id, categories, action, model, region, time } or null.', 'trustlayer-consent' ),
				'TrustLayer.open(\'prefs\' | \'dnss\' | \'banner\')  TrustLayer.close()' => __( 'Open or close the banner views.', 'trustlayer-consent' ),
				'TrustLayer.acceptAll()  TrustLayer.rejectAll()  TrustLayer.set({ analytics: true })' => __( 'Make a choice from your own UI.', 'trustlayer-consent' ),
				'TrustLayer.on(\'consent\', fn)  document.addEventListener(\'tlc:consent\', e => e.detail)' => __( 'Events: ready, consent, open, close.', 'trustlayer-consent' ),
				'TrustLayer.reset()' => __( 'Forget the choice on this browser and reload (testing).', 'trustlayer-consent' ),
			),
			__( 'PHP', 'trustlayer-consent' ) => array(
				'tlc_has_consent( \'analytics\' )  tlc_get_consent()' => __( 'The visitor\'s choice from the cookie (uncached requests only).', 'trustlayer-consent' ),
				'tlc_setting( \'general.cookie_name\' )  tlc_categories()' => __( 'Read settings and categories.', 'trustlayer-consent' ),
				"define( 'TLC_CONFIG', array( 'banner' => array( 'layout' => 'bar-bottom' ) ) );" => __( 'Set any setting in code (wp-config.php or a must-use plugin).', 'trustlayer-consent' ),
				"define( 'TLC_DISABLE', true );  define( 'TLC_MAXMIND_DB', '/path/City.mmdb' );" => __( 'Turn the front end off (e.g. staging); use a database file.', 'trustlayer-consent' ),
			),
			__( 'Filters and actions', 'trustlayer-consent' ) => array(
				'tlc_settings, tlc_defaults, tlc_categories, tlc_frontend_config' => __( 'Change settings, categories, or what the browser receives (e.g. texts per language).', 'trustlayer-consent' ),
				'tlc_should_load, tlc_blocker_active, tlc_blocker_rules, tlc_blocker_presets' => __( 'Where the banner and blocking run, and what is blocked.', 'trustlayer-consent' ),
				'tlc_geo_lookup, tlc_client_ip, tlc_region_group, tlc_region_model, tlc_us_privacy_states' => __( 'Geo-targeting: another lookup service, groups, and models.', 'trustlayer-consent' ),
				'tlc_consent_mode_defaults, tlc_log_entry, tlc_rest_rate_limit, tlc_capability' => __( 'Consent Mode defaults, log rows, REST limits, admin capability.', 'trustlayer-consent' ),
				'tlc_loaded, tlc_settings_saved, tlc_consent_logged' => __( 'Actions.', 'trustlayer-consent' ),
			),
			__( 'WP-CLI', 'trustlayer-consent' ) => array(
				'wp tlc export [--file=<file>]  wp tlc import <file>  wp tlc reset' => __( 'Settings.', 'trustlayer-consent' ),
				'wp tlc revision bump' => __( 'Ask everyone again.', 'trustlayer-consent' ),
				'wp tlc logs export --file=<file>  wp tlc logs prune' => __( 'Consent log.', 'trustlayer-consent' ),
				'wp tlc geo [<ip>]  wp tlc maxmind update' => __( 'Geo-targeting.', 'trustlayer-consent' ),
			),
		);
		foreach ( $sections as $title => $rows ) {
			echo '<h2 class="title">' . esc_html( $title ) . '</h2><table class="widefat striped tlc-dev"><tbody>';
			foreach ( $rows as $code => $text ) {
				echo '<tr><td><code>' . esc_html( $code ) . '</code></td><td>' . esc_html( $text ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}
	}
}
