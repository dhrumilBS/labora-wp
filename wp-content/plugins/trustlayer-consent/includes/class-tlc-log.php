<?php
/**
 * Consent log: a record of each choice a visitor makes, as proof of consent.
 *
 * One row per choice: random consent ID (also in the visitor's cookie, so a row can be matched to a person who
 * shows their ID), time, categories allowed, how they chose (accept all, reject all, custom, opt-out, GPC ...),
 * model and region, consent revision, page path (no query string), and optionally the IP address with the last
 * part removed and the browser. Rows older than the retention period (12 months by default) are deleted daily.
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Log {

	const CRON_HOOK  = 'tlc_prune_log';
	const DB_OPTION  = 'tlc_db_version';
	const ACTIONS    = array( 'accept_all', 'reject_all', 'custom', 'opt_out', 'acknowledge', 'gpc', 'dnss' );

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'tlc_consent_log';
	}

	public static function init(): void {
		add_action( self::CRON_HOOK, array( __CLASS__, 'prune' ) );
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			self::schedule();
		}
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 300, 'daily', self::CRON_HOOK );
		}
	}

	public static function action_labels(): array {
		return array(
			'accept_all'  => __( 'Accepted all', 'trustlayer-consent' ),
			'reject_all'  => __( 'Rejected all', 'trustlayer-consent' ),
			'custom'      => __( 'Custom choice', 'trustlayer-consent' ),
			'opt_out'     => __( 'Opted out of sale/sharing', 'trustlayer-consent' ),
			'acknowledge' => __( 'Acknowledged notice', 'trustlayer-consent' ),
			'gpc'         => __( 'Global Privacy Control', 'trustlayer-consent' ),
			'dnss'        => __( 'Do Not Sell or Share form', 'trustlayer-consent' ),
		);
	}

	public static function maybe_install(): void {
		if ( (int) get_option( self::DB_OPTION ) < TLC_DB_VERSION ) {
			self::install();
		}
	}

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		dbDelta( "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			consent_id char(36) NOT NULL,
			created_at datetime NOT NULL,
			action varchar(20) NOT NULL,
			categories varchar(500) NOT NULL,
			model varchar(10) NOT NULL DEFAULT '',
			region varchar(10) NOT NULL DEFAULT '',
			revision int(10) unsigned NOT NULL DEFAULT 1,
			url varchar(255) NOT NULL DEFAULT '',
			ip varchar(45) NOT NULL DEFAULT '',
			user_agent varchar(255) NOT NULL DEFAULT '',
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY consent_id (consent_id),
			KEY created_at (created_at)
		) {$charset};" );
		update_option( self::DB_OPTION, TLC_DB_VERSION, false );
	}

	/**
	 * Store one choice. $entry: consent_id, action, categories (id => 0|1), model, region, revision, url.
	 * Returns the row ID, or 0 when logging is off or the entry was skipped.
	 */
	public static function record( array $entry ): int {
		global $wpdb;
		if ( ! tlc_setting( 'logs.enabled' ) ) {
			return 0;
		}
		$row = array(
			'consent_id' => (string) $entry['consent_id'],
			'created_at' => current_time( 'mysql', true ),
			'action'     => (string) $entry['action'],
			'categories' => wp_json_encode( $entry['categories'] ),
			'model'      => (string) ( $entry['model'] ?? '' ),
			'region'     => (string) ( $entry['region'] ?? '' ),
			'revision'   => (int) ( $entry['revision'] ?? 1 ),
			'url'        => substr( (string) ( $entry['url'] ?? '' ), 0, 255 ),
			'ip'         => 'none' === tlc_setting( 'logs.ip' ) ? '' : self::anonymize_ip( TLC_Geo::client_ip() ),
			'user_agent' => tlc_setting( 'logs.user_agent' ) ? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ), 0, 255 ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'user_id'    => get_current_user_id(),
		);
		/**
		 * Change a log row before it is stored, or return false to skip it.
		 *
		 * @param array|false $row
		 */
		$row = apply_filters( 'tlc_log_entry', $row );
		if ( ! is_array( $row ) ) {
			return 0;
		}
		$wpdb->insert( self::table(), $row ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$id = (int) $wpdb->insert_id;
		/**
		 * Fires after a consent choice is logged.
		 *
		 * @param array $row
		 * @param int   $id
		 */
		do_action( 'tlc_consent_logged', $row, $id );
		return $id;
	}

	/** 203.0.113.25 to 203.0.113.0; 2001:db8:85a3::8a2e:370:7334 to 2001:db8:85a3:: (first 48 bits kept). */
	public static function anonymize_ip( string $ip ): string {
		if ( function_exists( 'wp_privacy_anonymize_ip' ) ) {
			return wp_privacy_anonymize_ip( $ip );
		}
		return '';
	}

	/** Delete rows older than the retention period. Returns the number deleted. */
	public static function prune(): int {
		global $wpdb;
		$days   = max( 1, (int) tlc_setting( 'logs.retention', 365 ) );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		return (int) $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE created_at < %s', $cutoff ) ); // phpcs:ignore WordPress.DB
	}

	public static function delete_all(): void {
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . self::table() ); // phpcs:ignore WordPress.DB
	}

	/**
	 * Rows for the admin list and CSV export.
	 *
	 * @param array $args search (consent ID), action, from, to (Y-m-d), per_page, page, order (ASC|DESC)
	 * @return array{rows:array,total:int}
	 */
	public static function query( array $args = array() ): array {
		global $wpdb;
		$where  = array( '1=1' );
		$params = array();
		if ( ! empty( $args['search'] ) ) {
			$where[]  = 'consent_id LIKE %s';
			$params[] = '%' . $wpdb->esc_like( (string) $args['search'] ) . '%';
		}
		if ( ! empty( $args['action'] ) && in_array( $args['action'], self::ACTIONS, true ) ) {
			$where[]  = 'action = %s';
			$params[] = $args['action'];
		}
		if ( ! empty( $args['from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $args['from'] ) ) {
			$where[]  = 'created_at >= %s';
			$params[] = $args['from'] . ' 00:00:00';
		}
		if ( ! empty( $args['to'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $args['to'] ) ) {
			$where[]  = 'created_at <= %s';
			$params[] = $args['to'] . ' 23:59:59';
		}
		$sql_where = implode( ' AND ', $where );
		$order     = 'ASC' === strtoupper( (string) ( $args['order'] ?? '' ) ) ? 'ASC' : 'DESC';
		$per_page  = max( 1, (int) ( $args['per_page'] ?? 20 ) );
		$offset    = max( 0, ( (int) ( $args['page'] ?? 1 ) - 1 ) * $per_page );
		$table     = self::table();

		// phpcs:disable WordPress.DB
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$sql_where}";
		$total     = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql );
		$rows_sql  = "SELECT * FROM {$table} WHERE {$sql_where} ORDER BY created_at {$order}, id {$order} LIMIT %d OFFSET %d";
		$rows      = $wpdb->get_results( $wpdb->prepare( $rows_sql, array_merge( $params, array( $per_page, $offset ) ) ), ARRAY_A );
		// phpcs:enable
		return array( 'rows' => (array) $rows, 'total' => $total );
	}

	/** Counts per action over the last $days days, for the summary above the log. */
	public static function summary( int $days = 30 ): array {
		global $wpdb;
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT action, COUNT(*) AS n FROM ' . self::table() . ' WHERE created_at >= %s GROUP BY action', $cutoff ), ARRAY_A ); // phpcs:ignore WordPress.DB
		$out    = array_fill_keys( self::ACTIONS, 0 );
		foreach ( (array) $rows as $row ) {
			$out[ $row['action'] ] = (int) $row['n'];
		}
		return $out;
	}

	/** Stream rows as CSV (same filters as query()). */
	public static function export_csv( array $args ): void {
		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		fputcsv( $out, array( 'consent_id', 'time_utc', 'action', 'categories', 'model', 'region', 'revision', 'url', 'ip', 'user_agent' ) );
		$page = 1;
		do {
			$batch = self::query( array_merge( $args, array( 'per_page' => 1000, 'page' => $page++, 'order' => 'ASC' ) ) );
			foreach ( $batch['rows'] as $row ) {
				$cats    = json_decode( $row['categories'], true );
				$allowed = is_array( $cats ) ? implode( ' ', array_keys( array_filter( $cats ) ) ) : '';
				fputcsv( $out, array( $row['consent_id'], $row['created_at'], $row['action'], $allowed, $row['model'], $row['region'], $row['revision'], $row['url'], $row['ip'], $row['user_agent'] ) );
			}
		} while ( count( $batch['rows'] ) === 1000 );
		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
}
