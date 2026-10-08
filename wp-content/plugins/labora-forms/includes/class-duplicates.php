<?php
/**
 * Duplicate submissions: the same person may submit a form once per 24 hours.
 *
 * A submission counts as a repeat when its email OR its phone number matches a successful submission of the same
 * form within the window. Only keyed hashes (HMAC-SHA256 with the site's salt) of the normalized email and phone are
 * stored, never the values themselves, so this log holds no readable personal data. Rows older than two windows are
 * deleted by a daily cron job.
 *
 * Filters:
 *  - labora_forms_duplicate_window (int seconds, default DAY_IN_SECONDS)
 *  - labora_forms_duplicate_match  ('any' = email or phone matches [default], 'both' = email and phone match)
 *  - labora_forms_duplicate_scope  ('form' = per form [default], 'all' = across every form)
 *
 * @package Labora_Forms
 */

defined( 'ABSPATH' ) || exit;

final class Labora_Forms_Duplicates {

	const DB_VERSION = '1';
	const CRON_HOOK  = 'labora_forms_prune_log';

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'labora_form_log';
	}

	/** Create or upgrade the log table. */
	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		dbDelta( "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_id bigint(20) unsigned NOT NULL,
			email_hash char(64) NOT NULL DEFAULT '',
			phone_hash char(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY form_email (form_id, email_hash, created_at),
			KEY form_phone (form_id, phone_hash, created_at),
			KEY created_at (created_at)
		) {$charset};" );
		update_option( 'labora_forms_db_version', self::DB_VERSION, false );
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	public static function maybe_install(): void {
		if ( self::DB_VERSION !== get_option( 'labora_forms_db_version' ) ) {
			self::install();
		}
	}

	public static function hash( string $value ): string {
		return '' === $value ? '' : hash_hmac( 'sha256', $value, wp_salt( 'auth' ) );
	}

	private static function window(): int {
		return max( 60, (int) apply_filters( 'labora_forms_duplicate_window', DAY_IN_SECONDS ) );
	}

	/** True when this email or phone already submitted this form within the window. */
	public static function is_repeat( int $form_id, string $email, string $phone ): bool {
		global $wpdb;
		$eh = self::hash( strtolower( $email ) );
		$ph = self::hash( $phone );
		if ( '' === $eh && '' === $ph ) {
			return false;
		}
		$since = gmdate( 'Y-m-d H:i:s', time() - self::window() );
		$table = self::table();
		$scope = 'all' === apply_filters( 'labora_forms_duplicate_scope', 'form', $form_id ) ? '' : $wpdb->prepare( ' AND form_id = %d', $form_id );
		$both  = 'both' === apply_filters( 'labora_forms_duplicate_match', 'any', $form_id );

		$where = array();
		if ( $both ) {
			if ( '' === $eh || '' === $ph ) {
				return false;
			}
			$where[] = $wpdb->prepare( '(email_hash = %s AND phone_hash = %s)', $eh, $ph );
		} else {
			if ( '' !== $eh ) {
				$where[] = $wpdb->prepare( 'email_hash = %s', $eh );
			}
			if ( '' !== $ph ) {
				$where[] = $wpdb->prepare( 'phone_hash = %s', $ph );
			}
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- every part above is prepared
		$sql = "SELECT 1 FROM {$table} WHERE created_at >= %s{$scope} AND (" . implode( ' OR ', $where ) . ') LIMIT 1';
		return (bool) $wpdb->get_var( $wpdb->prepare( $sql, $since ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/** Remember a successful submission. */
	public static function record( int $form_id, string $email, string $phone ): void {
		global $wpdb;
		$wpdb->insert( self::table(), array(
			'form_id'    => $form_id,
			'email_hash' => self::hash( strtolower( $email ) ),
			'phone_hash' => self::hash( $phone ),
			'created_at' => gmdate( 'Y-m-d H:i:s' ),
		), array( '%d', '%s', '%s', '%s' ) );
	}

	/** Daily cleanup: drop rows older than two windows. */
	public static function prune(): void {
		global $wpdb;
		$table = self::table();
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", gmdate( 'Y-m-d H:i:s', time() - 2 * self::window() ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function message(): string {
		return __( 'We already received a request from you in the last 24 hours. Our team will reply by email, so there is no need to send it again.', 'labora-forms' );
	}
}
