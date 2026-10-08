<?php
/**
 * Wires the modules together. Each module is a final class with static init(); none of them depend on a theme.
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Plugin {

	public static function boot(): void {
		load_plugin_textdomain( 'trustlayer-consent', false, dirname( plugin_basename( TLC_FILE ) ) . '/languages' );

		TLC_Log::maybe_install();
		TLC_Log::init();
		TLC_Rest::init();
		TLC_Maxmind::init();
		TLC_Privacy::init();

		// TLC_DISABLE (wp-config.php) turns the front end off without deactivating, e.g. on a staging copy
		if ( ! ( defined( 'TLC_DISABLE' ) && TLC_DISABLE ) ) {
			TLC_Frontend::init();
			TLC_Blocker::init();
			TLC_Shortcodes::init();
		}

		if ( is_admin() ) {
			TLC_Admin::init();
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			TLC_Cli::register();
		}

		/**
		 * Fires once TrustLayer Consent has loaded. Add-ons hook in here.
		 */
		do_action( 'tlc_loaded' );
	}

	public static function activate(): void {
		TLC_Log::install();
		// Only what the admin changes is stored; everything else follows the defaults (and the tlc_defaults filter)
		if ( false === get_option( TLC_Settings::OPTION ) ) {
			add_option( TLC_Settings::OPTION, array(), '', true );
		}
		TLC_Log::schedule();
		TLC_Maxmind::schedule();
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( TLC_Log::CRON_HOOK );
		wp_clear_scheduled_hook( TLC_Maxmind::CRON_HOOK );
	}
}
