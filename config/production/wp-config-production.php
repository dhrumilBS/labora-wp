<?php
/**
 * Recommended wp-config.php constants for the live server. Paste these into the production
 * wp-config.php (above the "That's all, stop editing!" line) in place of the local debug settings.
 * Final values are set on deployment.
 */

define( 'WP_ENVIRONMENT_TYPE', 'production' );

// Errors: log, never display
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );
@ini_set( 'display_errors', '0' );

// Security
define( 'DISALLOW_FILE_EDIT', true );      // no code editor in the admin
// define( 'DISALLOW_FILE_MODS', true );   // also block plugin/theme installs and updates from the admin (updates then go through deploys)
define( 'FORCE_SSL_ADMIN', true );
define( 'WP_HTTP_BLOCK_EXTERNAL', false ); // set true only with an allow-list (WP_ACCESSIBLE_HOSTS) after testing plugins

// HTTPS behind a proxy or load balancer (uncomment if the host terminates TLS in front of PHP)
// if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
//     $_SERVER['HTTPS'] = 'on';
// }

// Performance
define( 'WP_CACHE', true );                // required by WP Super Cache; the plugin manages wp-content/advanced-cache.php
define( 'WP_POST_REVISIONS', 20 );
define( 'AUTOSAVE_INTERVAL', 120 );
define( 'EMPTY_TRASH_DAYS', 30 );
define( 'WP_MEMORY_LIMIT', '256M' );

// Cron: run WP-Cron from the server's cron instead of on page views
//   */5 * * * * cd /var/www/labora && wp cron event run --due-now >/dev/null 2>&1
define( 'DISABLE_WP_CRON', true );

// Updates: security releases of WordPress core install automatically
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
