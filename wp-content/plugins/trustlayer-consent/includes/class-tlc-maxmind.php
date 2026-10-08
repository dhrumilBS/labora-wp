<?php
/**
 * The MaxMind database used for geo-targeting when no CDN header answers.
 *
 * Either point to a .mmdb file you manage (Regions tab, or the TLC_MAXMIND_DB constant), or enter a free MaxMind
 * account ID and license key: the plugin then downloads GeoLite2 to wp-content/uploads/trustlayer-consent/ and
 * refreshes it weekly. GeoLite2-Country is enough for country rules; choose GeoLite2-City for US state rules.
 * GeoLite2 data is created by MaxMind (https://www.maxmind.com) and its license requires keeping it up to date.
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Maxmind {

	const CRON_HOOK = 'tlc_maxmind_update';
	const STATUS    = 'tlc_maxmind_status';

	public static function init(): void {
		add_action( self::CRON_HOOK, array( __CLASS__, 'update' ) );
		add_action( 'tlc_settings_saved', array( __CLASS__, 'schedule' ) );
	}

	/** Weekly update while a license key is set. */
	public static function schedule(): void {
		$has_key = '' !== (string) tlc_setting( 'geo.maxmind_key' ) && '' !== (string) tlc_setting( 'geo.maxmind_account' );
		$next    = wp_next_scheduled( self::CRON_HOOK );
		if ( $has_key && ! $next ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', self::CRON_HOOK );
		} elseif ( ! $has_key && $next ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}
	}

	/** Folder for downloaded databases (not web-readable). */
	public static function dir(): string {
		$uploads = wp_upload_dir( null, false );
		return trailingslashit( $uploads['basedir'] ) . 'trustlayer-consent';
	}

	/** The database file to read, or '' when there is none. */
	public static function database_path(): string {
		$candidates = array();
		if ( defined( 'TLC_MAXMIND_DB' ) ) {
			$candidates[] = (string) TLC_MAXMIND_DB;
		}
		$custom = (string) tlc_setting( 'geo.maxmind_path' );
		if ( '' !== $custom ) {
			$candidates[] = path_is_absolute( $custom ) ? $custom : ABSPATH . ltrim( $custom, '/\\' );
		}
		$edition      = (string) tlc_setting( 'geo.maxmind_edition', 'GeoLite2-Country' );
		$candidates[] = self::dir() . "/{$edition}.mmdb";
		$candidates[] = self::dir() . '/GeoLite2-City.mmdb';
		$candidates[] = self::dir() . '/GeoLite2-Country.mmdb';
		foreach ( (array) apply_filters( 'tlc_maxmind_paths', $candidates ) as $file ) {
			if ( is_readable( $file ) && is_file( $file ) ) {
				return $file;
			}
		}
		return '';
	}

	/** Download the configured edition. Returns true or a WP_Error; the result is kept for the admin. */
	public static function update() {
		$result = self::download();
		update_option( self::STATUS, array(
			'time'  => time(),
			'ok'    => ! is_wp_error( $result ),
			'error' => is_wp_error( $result ) ? $result->get_error_message() : '',
		), false );
		return $result;
	}

	private static function download() {
		$account = (string) tlc_setting( 'geo.maxmind_account' );
		$key     = (string) tlc_setting( 'geo.maxmind_key' );
		$edition = (string) tlc_setting( 'geo.maxmind_edition', 'GeoLite2-Country' );
		if ( '' === $account || '' === $key ) {
			return new WP_Error( 'tlc_maxmind', __( 'Add your MaxMind account ID and license key first.', 'trustlayer-consent' ) );
		}
		if ( ! class_exists( 'PharData' ) ) {
			return new WP_Error( 'tlc_maxmind', __( 'The PHP Phar extension is needed to unpack the download.', 'trustlayer-consent' ) );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';

		// MaxMind answers with a redirect to a signed storage URL, which must be fetched without the credentials
		$url  = 'https://download.maxmind.com/geoip/databases/' . rawurlencode( $edition ) . '/download?suffix=tar.gz';
		$head = wp_remote_get( $url, array(
			'timeout'     => 20,
			'redirection' => 0,
			'headers'     => array( 'Authorization' => 'Basic ' . base64_encode( $account . ':' . $key ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		) );
		if ( is_wp_error( $head ) ) {
			return $head;
		}
		$code = (int) wp_remote_retrieve_response_code( $head );
		if ( 401 === $code ) {
			return new WP_Error( 'tlc_maxmind', __( 'MaxMind did not accept the account ID and license key.', 'trustlayer-consent' ) );
		}
		$location = (string) wp_remote_retrieve_header( $head, 'location' );
		if ( $code < 300 || $code > 399 || '' === $location ) {
			/* translators: %d: HTTP status code */
			return new WP_Error( 'tlc_maxmind', sprintf( __( 'Unexpected answer from MaxMind (HTTP %d).', 'trustlayer-consent' ), $code ) );
		}

		$tmp = download_url( $location, 300 );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}
		$archive = $tmp . '.tar.gz';
		rename( $tmp, $archive ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$work = trailingslashit( get_temp_dir() ) . 'tlc-mmdb-' . wp_generate_password( 8, false );
		try {
			$phar = new PharData( $archive );
			$phar->extractTo( $work, null, true );
		} catch ( Throwable $e ) {
			wp_delete_file( $archive );
			return new WP_Error( 'tlc_maxmind', __( 'The download could not be unpacked.', 'trustlayer-consent' ) );
		}
		wp_delete_file( $archive );

		$found = '';
		$it    = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $work, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $it as $file ) {
			if ( str_ends_with( $file->getFilename(), '.mmdb' ) ) {
				$found = $file->getPathname();
				break;
			}
		}
		if ( '' === $found ) {
			self::rmdir( $work );
			return new WP_Error( 'tlc_maxmind', __( 'The download did not contain a database.', 'trustlayer-consent' ) );
		}
		try {
			new TLC_Mmdb_Reader( $found ); // check it before replacing the current file
		} catch ( Throwable $e ) {
			self::rmdir( $work );
			return new WP_Error( 'tlc_maxmind', $e->getMessage() );
		}

		$dir = self::dir();
		if ( ! wp_mkdir_p( $dir ) ) {
			self::rmdir( $work );
			return new WP_Error( 'tlc_maxmind', __( 'The uploads folder is not writable.', 'trustlayer-consent' ) );
		}
		self::protect( $dir );
		$target = "{$dir}/{$edition}.mmdb";
		$ok     = copy( $found, $target . '.new' ) && rename( $target . '.new', $target ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		self::rmdir( $work );
		return $ok ? true : new WP_Error( 'tlc_maxmind', __( 'The database could not be saved.', 'trustlayer-consent' ) );
	}

	/** Keep the folder out of reach of browsers (Apache; on Nginx, deny it in the server config). */
	private static function protect( string $dir ): void {
		if ( ! file_exists( "{$dir}/.htaccess" ) ) {
			file_put_contents( "{$dir}/.htaccess", "Require all denied\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		if ( ! file_exists( "{$dir}/index.php" ) ) {
			file_put_contents( "{$dir}/index.php", "<?php // Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
	}

	private static function rmdir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $it as $file ) {
			$file->isDir() ? rmdir( $file->getPathname() ) : wp_delete_file( $file->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	/** For the admin: which file is used, its build date, and the last update result. */
	public static function status(): array {
		$file  = self::database_path();
		$built = '';
		$type  = '';
		if ( $file ) {
			try {
				$meta  = ( new TLC_Mmdb_Reader( $file ) )->metadata;
				$built = isset( $meta['build_epoch'] ) ? wp_date( get_option( 'date_format' ), (int) $meta['build_epoch'] ) : '';
				$type  = (string) ( $meta['database_type'] ?? '' );
			} catch ( Throwable $e ) {
				$type = $e->getMessage();
			}
		}
		return array( 'file' => $file, 'type' => $type, 'built' => $built, 'last' => (array) get_option( self::STATUS, array() ) );
	}
}
