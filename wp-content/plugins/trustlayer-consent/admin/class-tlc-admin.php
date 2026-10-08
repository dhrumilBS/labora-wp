<?php
/**
 * Admin: the TrustLayer menu (Settings, Consent log, Tools), saving, import/export, and maintenance actions.
 * Every action posts to admin-post.php with a nonce and needs the manage_options capability
 * (filter: tlc_capability).
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Admin {

	const SLUG = 'trustlayer';

	/** Settings tabs: slug => [ label, sections it saves ]. */
	public static function tabs(): array {
		return array(
			'general'      => array( __( 'General', 'trustlayer-consent' ), array( 'general', 'logs' ) ),
			'banner'       => array( __( 'Banner and design', 'trustlayer-consent' ), array( 'banner' ) ),
			'texts'        => array( __( 'Texts', 'trustlayer-consent' ), array( 'texts' ) ),
			'categories'   => array( __( 'Categories and cookies', 'trustlayer-consent' ), array( 'categories' ) ),
			'regions'      => array( __( 'Regions', 'trustlayer-consent' ), array( 'geo' ) ),
			'blocking'     => array( __( 'Script blocking', 'trustlayer-consent' ), array( 'blocking' ) ),
			'consent_mode' => array( __( 'Google Consent Mode', 'trustlayer-consent' ), array( 'consent_mode' ) ),
			'scripts'      => array( __( 'Custom scripts', 'trustlayer-consent' ), array( 'scripts' ) ),
			'policy'       => array( __( 'Policies and Do Not Sell', 'trustlayer-consent' ), array( 'policy' ) ),
			'developers'   => array( __( 'Developers', 'trustlayer-consent' ), array() ),
		);
	}

	public static function capability(): string {
		return (string) apply_filters( 'tlc_capability', 'manage_options' );
	}

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( TLC_FILE ), array( __CLASS__, 'action_links' ) );
		foreach ( array( 'save', 'bump', 'export', 'import', 'reset', 'delete_logs', 'export_logs', 'maxmind_update' ) as $action ) {
			add_action( "admin_post_tlc_{$action}", array( __CLASS__, "handle_{$action}" ) );
		}
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	public static function menu(): void {
		$cap = self::capability();
		add_menu_page( __( 'TrustLayer Consent', 'trustlayer-consent' ), __( 'TrustLayer', 'trustlayer-consent' ), $cap, self::SLUG, array( __CLASS__, 'page_settings' ), 'dashicons-shield', 81 );
		add_submenu_page( self::SLUG, __( 'TrustLayer Consent settings', 'trustlayer-consent' ), __( 'Settings', 'trustlayer-consent' ), $cap, self::SLUG, array( __CLASS__, 'page_settings' ) );
		add_submenu_page( self::SLUG, __( 'Consent log', 'trustlayer-consent' ), __( 'Consent log', 'trustlayer-consent' ), $cap, self::SLUG . '-log', array( __CLASS__, 'page_log' ) );
		add_submenu_page( self::SLUG, __( 'TrustLayer tools', 'trustlayer-consent' ), __( 'Tools', 'trustlayer-consent' ), $cap, self::SLUG . '-tools', array( __CLASS__, 'page_tools' ) );
	}

	public static function assets( string $hook ): void {
		if ( false === strpos( $hook, self::SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'tlc-admin', TLC_URL . 'assets/css/admin.css', array(), (string) filemtime( TLC_DIR . '/assets/css/admin.css' ) );
		wp_enqueue_script( 'tlc-admin', TLC_URL . 'assets/js/admin.js', array(), (string) filemtime( TLC_DIR . '/assets/js/admin.js' ), true );
	}

	public static function action_links( array $links ): array {
		array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( self::url() ), esc_html__( 'Settings', 'trustlayer-consent' ) ) );
		return $links;
	}

	public static function url( string $tab = '', string $page = self::SLUG, array $args = array() ): string {
		$args = array_merge( array( 'page' => $page ), $tab ? array( 'tab' => $tab ) : array(), $args );
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	private static function check( string $action ): void {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'trustlayer-consent' ), 403 );
		}
		check_admin_referer( "tlc_{$action}" );
	}

	private static function back( string $notice, string $url = '' ): void {
		$url = $url ?: wp_get_referer() ?: self::url();
		wp_safe_redirect( add_query_arg( 'tlc_notice', $notice, remove_query_arg( array( 'tlc_notice', 'tlc_error' ), $url ) ) );
		exit;
	}

	public static function notices(): void {
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( (string) $screen->id, self::SLUG ) ) {
			return;
		}
		$notice   = sanitize_key( $_GET['tlc_notice'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		$messages = array(
			'saved'      => __( 'Settings saved.', 'trustlayer-consent' ),
			'bumped'     => __( 'Done. Every visitor will be asked again on their next page view.', 'trustlayer-consent' ),
			'imported'   => __( 'Settings imported.', 'trustlayer-consent' ),
			'reset'      => __( 'Settings reset to the defaults.', 'trustlayer-consent' ),
			'logs_gone'  => __( 'The consent log is empty now.', 'trustlayer-consent' ),
			'mm_ok'      => __( 'The MaxMind database was downloaded.', 'trustlayer-consent' ),
			'mm_error'   => __( 'The MaxMind database could not be downloaded. See the status below.', 'trustlayer-consent' ),
			'bad_import' => __( 'That file could not be imported. Choose a settings file exported by TrustLayer Consent.', 'trustlayer-consent' ),
		);
		if ( isset( $messages[ $notice ] ) ) {
			$type = in_array( $notice, array( 'mm_error', 'bad_import' ), true ) ? 'error' : 'success';
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $type ), esc_html( $messages[ $notice ] ) );
		}
		if ( ( defined( 'TLC_CONFIG' ) || has_filter( 'tlc_settings' ) ) && 'toplevel_page_' . self::SLUG === $screen->id ) {
			printf( '<div class="notice notice-info"><p>%s</p></div>', esc_html__( 'Some settings are set in code (TLC_CONFIG or the tlc_settings filter). Those fields are marked "Set in code"; changing them here has no effect until the code is changed.', 'trustlayer-consent' ) );
		}
		if ( defined( 'TLC_DISABLE' ) && TLC_DISABLE ) {
			printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html__( 'TLC_DISABLE is set in wp-config.php: the banner and script blocking are off on this site.', 'trustlayer-consent' ) );
		}
	}

	/* ---------- Pages ---------- */

	public static function page_settings(): void {
		$tabs = self::tabs();
		$tab  = sanitize_key( $_GET['tab'] ?? 'general' ); // phpcs:ignore WordPress.Security.NonceVerification
		$tab  = isset( $tabs[ $tab ] ) ? $tab : 'general';
		echo '<div class="wrap tlc-admin"><h1>' . esc_html__( 'TrustLayer Consent', 'trustlayer-consent' ) . '</h1>';
		echo '<nav class="nav-tab-wrapper tlc-tabs">';
		foreach ( $tabs as $slug => $info ) {
			printf( '<a href="%s" class="nav-tab%s">%s</a>', esc_url( self::url( $slug ) ), $slug === $tab ? ' nav-tab-active' : '', esc_html( $info[0] ) );
		}
		echo '</nav>';
		if ( $tabs[ $tab ][1] ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="tlc-form">';
			wp_nonce_field( 'tlc_save' );
			echo '<input type="hidden" name="action" value="tlc_save"><input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
			call_user_func( array( 'TLC_Admin_Tabs', $tab ) );
			submit_button();
			echo '</form>';
		} else {
			call_user_func( array( 'TLC_Admin_Tabs', $tab ) );
		}
		echo '</div>';
	}

	public static function page_log(): void {
		$table = new TLC_Logs_Table();
		$table->prepare_items();
		$summary = TLC_Log::summary( 30 );
		$total   = array_sum( $summary );
		$choices = $summary['accept_all'] + $summary['reject_all'] + $summary['custom'];
		echo '<div class="wrap tlc-admin"><h1 class="wp-heading-inline">' . esc_html__( 'Consent log', 'trustlayer-consent' ) . '</h1>';
		$export = wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'tlc_export_logs' ), $table->filters() ), admin_url( 'admin-post.php' ) ), 'tlc_export_logs' );
		echo ' <a href="' . esc_url( $export ) . '" class="page-title-action">' . esc_html__( 'Export CSV', 'trustlayer-consent' ) . '</a><hr class="wp-header-end">';
		if ( ! tlc_setting( 'logs.enabled' ) ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Logging is off (General tab), so new choices are not recorded.', 'trustlayer-consent' ) . '</p></div>';
		}
		echo '<div class="tlc-stats">';
		/* translators: %s: number of choices */
		printf( '<div><strong>%s</strong><span>%s</span></div>', esc_html( number_format_i18n( $total ) ), esc_html__( 'choices in the last 30 days', 'trustlayer-consent' ) );
		foreach ( array( 'accept_all', 'reject_all', 'custom', 'opt_out', 'gpc' ) as $action ) {
			$pct = $choices && in_array( $action, array( 'accept_all', 'reject_all', 'custom' ), true ) ? ' (' . round( 100 * $summary[ $action ] / $choices ) . '%)' : '';
			printf( '<div><strong>%s</strong><span>%s</span></div>', esc_html( number_format_i18n( $summary[ $action ] ) . $pct ), esc_html( TLC_Log::action_labels()[ $action ] ) );
		}
		echo '</div>';
		/* translators: %d: number of days */
		echo '<p class="description">' . esc_html( sprintf( __( 'Each row is one choice. The consent ID is also in the visitor\'s cookie, so a visitor can quote it to prove a choice (the [tlc_consent_details] shortcode shows it to them). Rows are deleted after %d days.', 'trustlayer-consent' ), (int) tlc_setting( 'logs.retention' ) ) ) . '</p>';
		echo '<form method="get"><input type="hidden" name="page" value="' . esc_attr( self::SLUG . '-log' ) . '">';
		$table->search_box( __( 'Search consent ID', 'trustlayer-consent' ), 'tlc-log' );
		$table->display();
		echo '</form></div>';
	}

	public static function page_tools(): void {
		$post = esc_url( admin_url( 'admin-post.php' ) );
		echo '<div class="wrap tlc-admin"><h1>' . esc_html__( 'TrustLayer tools', 'trustlayer-consent' ) . '</h1>';

		echo '<div class="tlc-card"><h2>' . esc_html__( 'Export settings', 'trustlayer-consent' ) . '</h2><p>' . esc_html__( 'Download every setting as a JSON file, to copy this setup to another site or keep a backup. The MaxMind license key is left out.', 'trustlayer-consent' ) . '</p>';
		echo '<form method="post" action="' . $post . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
		wp_nonce_field( 'tlc_export' );
		echo '<input type="hidden" name="action" value="tlc_export">';
		submit_button( __( 'Download settings', 'trustlayer-consent' ), 'secondary', 'submit', false );
		echo '</form></div>';

		echo '<div class="tlc-card"><h2>' . esc_html__( 'Import settings', 'trustlayer-consent' ) . '</h2><p>' . esc_html__( 'Replace the settings in the file\'s sections with the file\'s values. Everything is checked as if saved in the settings tabs. Custom scripts are only imported for users allowed to add unfiltered HTML.', 'trustlayer-consent' ) . '</p>';
		echo '<form method="post" action="' . $post . '" enctype="multipart/form-data">'; // phpcs:ignore WordPress.Security.EscapeOutput
		wp_nonce_field( 'tlc_import' );
		echo '<input type="hidden" name="action" value="tlc_import"><input type="file" name="tlc_file" accept=".json,application/json" required> ';
		submit_button( __( 'Import', 'trustlayer-consent' ), 'secondary', 'submit', false );
		echo '</form></div>';

		echo '<div class="tlc-card"><h2>' . esc_html__( 'Ask everyone again', 'trustlayer-consent' ) . '</h2><p>';
		/* translators: %d: consent revision number */
		echo esc_html( sprintf( __( 'Use this after adding cookies or changing what a category does. Current consent revision: %d. Older choices stop counting, so every visitor sees the banner again; the log keeps the old choices.', 'trustlayer-consent' ), (int) tlc_setting( 'general.revision' ) ) );
		echo '</p><form method="post" action="' . $post . '" data-tlc-confirm="' . esc_attr__( 'Ask every visitor for consent again?', 'trustlayer-consent' ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
		wp_nonce_field( 'tlc_bump' );
		echo '<input type="hidden" name="action" value="tlc_bump">';
		submit_button( __( 'Ask everyone again', 'trustlayer-consent' ), 'secondary', 'submit', false );
		echo '</form></div>';

		echo '<div class="tlc-card"><h2>' . esc_html__( 'Reset settings', 'trustlayer-consent' ) . '</h2><p>' . esc_html__( 'Go back to the default settings. The consent log and the MaxMind license key are kept.', 'trustlayer-consent' ) . '</p>';
		echo '<form method="post" action="' . $post . '" data-tlc-confirm="' . esc_attr__( 'Reset every setting to its default?', 'trustlayer-consent' ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
		wp_nonce_field( 'tlc_reset' );
		echo '<input type="hidden" name="action" value="tlc_reset">';
		submit_button( __( 'Reset to defaults', 'trustlayer-consent' ), 'delete', 'submit', false );
		echo '</form></div>';

		echo '<div class="tlc-card"><h2>' . esc_html__( 'Delete the consent log', 'trustlayer-consent' ) . '</h2><p>' . esc_html__( 'Remove every row now. Old rows are already deleted automatically after the retention period.', 'trustlayer-consent' ) . '</p>';
		echo '<form method="post" action="' . $post . '" data-tlc-confirm="' . esc_attr__( 'Delete the whole consent log? This cannot be undone.', 'trustlayer-consent' ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput
		wp_nonce_field( 'tlc_delete_logs' );
		echo '<input type="hidden" name="action" value="tlc_delete_logs">';
		submit_button( __( 'Delete all log rows', 'trustlayer-consent' ), 'delete', 'submit', false );
		echo '</form></div>';

		global $wpdb;
		$rows = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . TLC_Log::table() ); // phpcs:ignore WordPress.DB
		$mm   = TLC_Maxmind::status();
		$next = wp_next_scheduled( TLC_Log::CRON_HOOK );
		echo '<div class="tlc-card"><h2>' . esc_html__( 'Status', 'trustlayer-consent' ) . '</h2><table class="widefat striped tlc-status"><tbody>';
		$status = array(
			__( 'Plugin version', 'trustlayer-consent' )       => TLC_VERSION,
			__( 'Consent revision', 'trustlayer-consent' )     => (string) (int) tlc_setting( 'general.revision' ),
			__( 'Log rows', 'trustlayer-consent' )             => number_format_i18n( $rows ),
			__( 'Next log cleanup', 'trustlayer-consent' )     => $next ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next ) : __( 'Not scheduled', 'trustlayer-consent' ),
			__( 'MaxMind database', 'trustlayer-consent' )     => $mm['file'] ? $mm['type'] . ( $mm['built'] ? ' (' . $mm['built'] . ')' : '' ) : __( 'None', 'trustlayer-consent' ),
			__( 'Front end', 'trustlayer-consent' )            => ( defined( 'TLC_DISABLE' ) && TLC_DISABLE ) ? 'TLC_DISABLE' : ( tlc_setting( 'general.enabled' ) ? ( tlc_setting( 'general.preview' ) ? __( 'Preview: administrators only', 'trustlayer-consent' ) : __( 'On', 'trustlayer-consent' ) ) : __( 'Off', 'trustlayer-consent' ) ),
		);
		foreach ( $status as $label => $value ) {
			echo '<tr><th>' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
		}
		echo '</tbody></table></div></div>';
	}

	/* ---------- Actions ---------- */

	public static function handle_save(): void {
		self::check( 'save' );
		$tabs = self::tabs();
		$tab  = sanitize_key( $_POST['tab'] ?? '' );
		if ( ! isset( $tabs[ $tab ] ) ) {
			self::back( '' );
		}
		$input    = isset( $_POST['tlc'] ) && is_array( $_POST['tlc'] ) ? $_POST['tlc'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized per section
		$sections = array();
		foreach ( $tabs[ $tab ][1] as $section ) {
			$sections[ $section ] = 'categories' === $section
				? TLC_Settings::sanitize_categories( wp_unslash( $input['categories'] ?? array() ) )
				: TLC_Settings::sanitize_section( $section, $input[ $section ] ?? array() );
		}
		TLC_Settings::save_sections( $sections );
		self::back( 'saved', self::url( $tab ) );
	}

	public static function handle_bump(): void {
		self::check( 'bump' );
		$general             = TLC_Settings::saved()['general'];
		$general['revision'] = (int) $general['revision'] + 1;
		TLC_Settings::save_sections( array( 'general' => $general ) );
		self::back( 'bumped' );
	}

	public static function handle_export(): void {
		self::check( 'export' );
		$host = sanitize_file_name( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="trustlayer-consent-' . $host . '-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( TLC_Settings::export(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		exit;
	}

	public static function handle_import(): void {
		self::check( 'import' );
		$file = $_FILES['tlc_file']['tmp_name'] ?? ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		if ( ! $file || ! is_uploaded_file( $file ) || filesize( $file ) > 2 * MB_IN_BYTES ) {
			self::back( 'bad_import' );
		}
		$result = TLC_Settings::import( json_decode( (string) file_get_contents( $file ), true ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		self::back( is_wp_error( $result ) ? 'bad_import' : 'imported' );
	}

	public static function handle_reset(): void {
		self::check( 'reset' );
		TLC_Settings::reset();
		self::back( 'reset' );
	}

	public static function handle_delete_logs(): void {
		self::check( 'delete_logs' );
		TLC_Log::delete_all();
		self::back( 'logs_gone' );
	}

	public static function handle_export_logs(): void {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( '', 403 );
		}
		check_admin_referer( 'tlc_export_logs' );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="consent-log-' . gmdate( 'Y-m-d' ) . '.csv"' );
		TLC_Log::export_csv( array(
			'search' => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ),
			'action' => sanitize_key( $_GET['consent_action'] ?? '' ),
			'from'   => sanitize_text_field( wp_unslash( $_GET['from'] ?? '' ) ),
			'to'     => sanitize_text_field( wp_unslash( $_GET['to'] ?? '' ) ),
		) );
		exit;
	}

	public static function handle_maxmind_update(): void {
		self::check( 'maxmind_update' );
		$result = TLC_Maxmind::update();
		self::back( is_wp_error( $result ) ? 'mm_error' : 'mm_ok', self::url( 'regions' ) );
	}
}
