<?php
/**
 * Plugin Name:       Lead Guard for Contact Form 7
 * Description:       Stricter checks for every Contact Form 7 form (phone validation and normalization, email checks, one submission per person per time window, honeypot), plus per-form thank-you redirect and analytics event, and a clean form address without #wpcf7. Failed checks stop the submission before anything is sent or stored. Settings: Contact > Lead Guard; per-form lines are documented in each form's Additional Settings tab.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Requires Plugins:  contact-form-7
 * Author:            Dhrumil
 * Text Domain:       lead-guard-cf7
 * License:           GPL-2.0-or-later
 *
 * @package Lead_Guard_CF7
 */

defined( 'ABSPATH' ) || exit;

define( 'LEAD_GUARD_CF7_VERSION', '1.0.0' );
define( 'LEAD_GUARD_CF7_FILE', __FILE__ );

require __DIR__ . '/includes/class-phone.php';
require __DIR__ . '/includes/class-email.php';
require __DIR__ . '/includes/class-settings.php';
require __DIR__ . '/includes/class-log.php';
require __DIR__ . '/includes/class-validator.php';
require __DIR__ . '/includes/class-behavior.php';
require __DIR__ . '/includes/class-editor.php';

register_activation_hook( __FILE__, array( 'Lead_Guard_Log', 'install' ) );
register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( Lead_Guard_Log::CRON_HOOK );
} );

add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'lead-guard-cf7', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	if ( ! defined( 'WPCF7_VERSION' ) ) {
		return;
	}
	Lead_Guard_Log::maybe_install();
	Lead_Guard_Validator::init();
	Lead_Guard_Behavior::init();
	add_action( Lead_Guard_Log::CRON_HOOK, array( 'Lead_Guard_Log', 'prune' ) );
	if ( is_admin() ) {
		Lead_Guard_Editor::init();
		add_action( 'admin_init', array( 'Lead_Guard_Settings', 'admin_init' ) );
		add_action( 'admin_menu', array( 'Lead_Guard_Settings', 'admin_menu' ), 20 );
	}
} );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	array_unshift( $links, sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=' . Lead_Guard_Settings::PAGE ) ), esc_html__( 'Settings', 'lead-guard-cf7' ) ) );
	return $links;
} );
