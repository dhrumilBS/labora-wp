<?php
/**
 * Plugin Name:       TrustLayer Consent
 * Description:       Cookie consent for US and international privacy laws: cookie categories, a preferences center, opt-in or opt-out by region (geo-targeting), Do Not Sell or Share and Global Privacy Control, script and embed blocking, Google Consent Mode v2, consent logs, and import/export. Works with any theme.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Dhrumil
 * Text Domain:       trustlayer-consent
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

define( 'TLC_VERSION', '1.0.0' );
define( 'TLC_FILE', __FILE__ );
define( 'TLC_DIR', __DIR__ );
define( 'TLC_URL', plugin_dir_url( __FILE__ ) );
define( 'TLC_DB_VERSION', 1 );

// One class per file, in includes/ and admin/: TLC_Foo_Bar is includes/class-tlc-foo-bar.php
spl_autoload_register( function ( $class ) {
	if ( 0 !== strpos( $class, 'TLC_' ) ) {
		return;
	}
	$file = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
	foreach ( array( 'includes', 'admin' ) as $dir ) {
		if ( is_readable( TLC_DIR . "/{$dir}/{$file}" ) ) {
			require TLC_DIR . "/{$dir}/{$file}";
			return;
		}
	}
} );

require TLC_DIR . '/includes/functions.php';

register_activation_hook( __FILE__, array( 'TLC_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'TLC_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'TLC_Plugin', 'boot' ) );
