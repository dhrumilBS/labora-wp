<?php
/**
 * Repeat-submission log: keyed hashes (HMAC-SHA256 with the site's salt) of the normalized email and phone of each
 * successful submission, never the values themselves. Rows older than two windows are deleted daily.
 *
 * @package Lead_Guard_CF7
 */

defined( 'ABSPATH' ) || exit;

final class Lead_Guard_Log {

	const DB_VERSION = '1';
	const CRON_HOOK  = 'lead_guard_cf7_prune';

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'lead_guard_log';
	}

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( 'CREATE TABLE ' . self::table() . ' (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_id bigint(20) unsigned NOT NULL,
			email_hash char(64) NOT NULL DEFAULT \'\',
			phone_hash char(64) NOT NULL DEFAULT \'\',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY form_email (form_id, email_hash, created_at),
			KEY form_phone (form_id, phone_hash, created_at),
			KEY created_at (created_at)
		) ' . $wpdb->get_charset_collate() . ';' );
		update_option( 'lead_guard_cf7_db', self::DB_VERSION, false );
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	public static function maybe_install(): void {
		if ( self::DB_VERSION !== get_option( 'lead_guard_cf7_db' ) ) {
			self::install();
		}
	}

	private static function hash( string $v ): string {
		return '' === $v ? '' : hash_hmac( 'sha256', $v, wp_salt( 'auth' ) );
	}

	private static function window(): int {
		return max( 1, (int) Lead_Guard_Settings::get( 'dup_hours' ) ) * HOUR_IN_SECONDS;
	}

	public static function is_repeat( int $form_id, string $email, string $phone ): bool {
		global $wpdb;
		$eh = self::hash( strtolower( $email ) );
		$ph = self::hash( $phone );
		if ( '' === $eh && '' === $ph ) {
			return false;
		}
		$table = self::table();
		$scope = 'all' === Lead_Guard_Settings::get( 'dup_scope' ) ? '' : $wpdb->prepare( ' AND form_id = %d', $form_id );
		if ( 'both' === Lead_Guard_Settings::get( 'dup_match' ) ) {
			if ( '' === $eh || '' === $ph ) {
				return false;
			}
			$match = $wpdb->prepare( 'email_hash = %s AND phone_hash = %s', $eh, $ph );
		} else {
			$parts = array();
			if ( '' !== $eh ) {
				$parts[] = $wpdb->prepare( 'email_hash = %s', $eh );
			}
			if ( '' !== $ph ) {
				$parts[] = $wpdb->prepare( 'phone_hash = %s', $ph );
			}
			$match = implode( ' OR ', $parts );
		}
		$since = gmdate( 'Y-m-d H:i:s', time() - self::window() );
		// phpcs:ignore WordPress.DB.PreparedSQL -- every part is prepared above
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$table} WHERE created_at >= %s{$scope} AND ({$match}) LIMIT 1", $since ) );
	}

	public static function record( int $form_id, string $email, string $phone ): void {
		global $wpdb;
		$wpdb->insert( self::table(), array(
			'form_id'    => $form_id,
			'email_hash' => self::hash( strtolower( $email ) ),
			'phone_hash' => self::hash( $phone ),
			'created_at' => gmdate( 'Y-m-d H:i:s' ),
		), array( '%d', '%s', '%s', '%s' ) );
	}

	public static function prune(): void {
		global $wpdb;
		$table = self::table();
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", gmdate( 'Y-m-d H:i:s', time() - 2 * self::window() ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
