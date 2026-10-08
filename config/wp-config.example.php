<?php
/**
 * Template for wp-config.php. Copy to the WordPress root as wp-config.php and fill in the values.
 * The real wp-config.php is never committed: it holds database credentials and secret keys.
 *
 * New keys and salts: https://api.wordpress.org/secret-key/1.1/salt/  (or: wp config shuffle-salts)
 */

// ** Database ** //
define( 'DB_NAME', 'database_name' );
define( 'DB_USER', 'database_user' );
define( 'DB_PASSWORD', 'database_password' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
$table_prefix = 'lbr_';

// ** Keys and salts: generate unique values per environment ** //
define( 'AUTH_KEY',         'put-unique-phrase-here' );
define( 'SECURE_AUTH_KEY',  'put-unique-phrase-here' );
define( 'LOGGED_IN_KEY',    'put-unique-phrase-here' );
define( 'NONCE_KEY',        'put-unique-phrase-here' );
define( 'AUTH_SALT',        'put-unique-phrase-here' );
define( 'SECURE_AUTH_SALT', 'put-unique-phrase-here' );
define( 'LOGGED_IN_SALT',   'put-unique-phrase-here' );
define( 'NONCE_SALT',       'put-unique-phrase-here' );

// ** Environment: local | staging | production ** //
define( 'WP_ENVIRONMENT_TYPE', 'local' );

// Local development
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );          // wp-content/debug.log (git-ignored)
define( 'WP_DEBUG_DISPLAY', false );

// Production: see config/production/wp-config-production.php for the recommended set.

define( 'DISALLOW_FILE_EDIT', true );   // no theme/plugin code editor in the admin
define( 'WP_POST_REVISIONS', 20 );
define( 'AUTOSAVE_INTERVAL', 120 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
