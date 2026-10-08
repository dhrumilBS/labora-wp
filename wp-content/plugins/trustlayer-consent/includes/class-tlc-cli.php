<?php
/**
 * WP-CLI commands: wp tlc ...
 *
 *   wp tlc export [--file=<file>]       settings as JSON (to stdout or a file)
 *   wp tlc import <file>                settings from an export
 *   wp tlc reset                        default settings
 *   wp tlc revision bump                ask every visitor again
 *   wp tlc logs export --file=<file>    consent log as CSV
 *   wp tlc logs prune                   delete rows older than the retention period
 *   wp tlc geo [<ip>]                   which region and model an address gets
 *   wp tlc maxmind update               download the MaxMind database now
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Cli {

	public static function register(): void {
		WP_CLI::add_command( 'tlc export', array( __CLASS__, 'export' ) );
		WP_CLI::add_command( 'tlc import', array( __CLASS__, 'import' ) );
		WP_CLI::add_command( 'tlc reset', array( __CLASS__, 'reset' ) );
		WP_CLI::add_command( 'tlc revision bump', array( __CLASS__, 'bump' ) );
		WP_CLI::add_command( 'tlc logs export', array( __CLASS__, 'logs_export' ) );
		WP_CLI::add_command( 'tlc logs prune', array( __CLASS__, 'logs_prune' ) );
		WP_CLI::add_command( 'tlc geo', array( __CLASS__, 'geo' ) );
		WP_CLI::add_command( 'tlc maxmind update', array( __CLASS__, 'maxmind' ) );
	}

	/**
	 * Export settings as JSON (the MaxMind license key is left out).
	 *
	 * [--file=<file>]
	 * : Write to this file instead of the screen.
	 */
	public static function export( $args, $assoc ): void {
		$json = wp_json_encode( TLC_Settings::export(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( ! empty( $assoc['file'] ) ) {
			file_put_contents( $assoc['file'], $json . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			WP_CLI::success( 'Settings written to ' . $assoc['file'] );
			return;
		}
		WP_CLI::line( $json );
	}

	/**
	 * Import settings from an export file.
	 *
	 * <file>
	 * : The JSON file.
	 */
	public static function import( $args ): void {
		if ( ! is_readable( $args[0] ) ) {
			WP_CLI::error( 'Cannot read ' . $args[0] );
		}
		$result = TLC_Settings::import( json_decode( (string) file_get_contents( $args[0] ), true ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		WP_CLI::success( 'Imported: ' . implode( ', ', $result ) );
	}

	/** Reset every setting to its default (the log and MaxMind key are kept). */
	public static function reset(): void {
		TLC_Settings::reset();
		WP_CLI::success( 'Settings reset.' );
	}

	/** Raise the consent revision so every visitor is asked again. */
	public static function bump(): void {
		$general             = TLC_Settings::saved()['general'];
		$general['revision'] = (int) $general['revision'] + 1;
		TLC_Settings::save_sections( array( 'general' => $general ) );
		WP_CLI::success( 'Consent revision is now ' . $general['revision'] . '.' );
	}

	/**
	 * Export the consent log as CSV.
	 *
	 * --file=<file>
	 * : The CSV file to write.
	 *
	 * [--from=<date>]
	 * : Y-m-d
	 *
	 * [--to=<date>]
	 * : Y-m-d
	 */
	public static function logs_export( $args, $assoc ): void {
		ob_start();
		TLC_Log::export_csv( array( 'from' => $assoc['from'] ?? '', 'to' => $assoc['to'] ?? '' ) );
		file_put_contents( $assoc['file'], ob_get_clean() ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		WP_CLI::success( 'Log written to ' . $assoc['file'] );
	}

	/** Delete log rows older than the retention period. */
	public static function logs_prune(): void {
		WP_CLI::success( TLC_Log::prune() . ' rows deleted.' );
	}

	/**
	 * Show the region group and consent model for an IP address (MaxMind), or for this request.
	 *
	 * [<ip>]
	 * : An IPv4 or IPv6 address.
	 */
	public static function geo( $args ): void {
		$result = TLC_Geo::resolve( $args[0] ?? null );
		WP_CLI\Utils\format_items( 'table', array( $result ), array_keys( $result ) );
	}

	/** Download the MaxMind database now (needs the account ID and license key). */
	public static function maxmind(): void {
		$result = TLC_Maxmind::update();
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		WP_CLI::success( 'Database saved: ' . TLC_Maxmind::database_path() );
	}
}
