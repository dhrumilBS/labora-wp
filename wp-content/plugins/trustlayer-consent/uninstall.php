<?php
/**
 * Uninstall: removes the settings, the consent log table, scheduled events, and the downloaded MaxMind database.
 * To keep the consent log (proof of consent) after removing the plugin, export it first (Consent log > Export CSV),
 * or define TLC_KEEP_DATA as true in wp-config.php.
 *
 * @package TrustLayer_Consent
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( defined( 'TLC_KEEP_DATA' ) && TLC_KEEP_DATA ) {
	return;
}

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tlc_consent_log" ); // phpcs:ignore WordPress.DB
delete_option( 'tlc_settings' );
delete_option( 'tlc_db_version' );
delete_option( 'tlc_maxmind_status' );
wp_clear_scheduled_hook( 'tlc_prune_log' );
wp_clear_scheduled_hook( 'tlc_maxmind_update' );

$uploads = wp_upload_dir( null, false );
$dir     = trailingslashit( $uploads['basedir'] ) . 'trustlayer-consent';
if ( is_dir( $dir ) ) {
	foreach ( (array) glob( $dir . '/{,.}*', GLOB_BRACE ) as $file ) {
		if ( is_file( $file ) ) {
			wp_delete_file( $file );
		}
	}
	rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}
