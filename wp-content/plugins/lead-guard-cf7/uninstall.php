<?php
/**
 * Uninstall: remove the settings and the repeat-submission log.
 *
 * @package Lead_Guard_CF7
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}lead_guard_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
delete_option( 'lead_guard_cf7' );
delete_option( 'lead_guard_cf7_db' );
wp_clear_scheduled_hook( 'lead_guard_cf7_prune' );
