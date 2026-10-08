<?php
/**
 * Plugin Name:       Labora Forms
 * Description:       The Labora site's Contact Form 7 forms, defined in code (wp labora-forms setup), with the theme helper labora_forms_render() and what happens after a send (confirmation panel, dataLayer event, thank-you page). Validation comes from Lead Guard for Contact Form 7.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      8.3
 * Requires Plugins:  contact-form-7, lead-guard-cf7
 * Author:            Labora
 * Text Domain:       labora-forms
 * License:           Proprietary
 *
 * @package Labora_Forms
 */

defined( 'ABSPATH' ) || exit;

define( 'LABORA_FORMS_VERSION', '1.0.0' );
define( 'LABORA_FORMS_FILE', __FILE__ );
define( 'LABORA_FORMS_DIR', __DIR__ );

require LABORA_FORMS_DIR . '/includes/class-forms.php';

add_action( 'plugins_loaded', function () {
	if ( ! defined( 'WPCF7_VERSION' ) ) {
		return; // Contact Form 7 is required (WordPress enforces it through "Requires Plugins")
	}
	add_filter( 'wpcf7_form_additional_atts', array( 'Labora_Forms_Forms', 'form_atts' ) );
	// Our form templates are written as exact HTML: no automatic <p>/<br> from CF7 (other CF7 forms keep it)
	add_filter( 'wpcf7_autop_or_not', array( 'Labora_Forms_Forms', 'autop' ) );
} );

// Contact Form 7 loads its CSS and JS on every page by default. Load them only where a form is rendered,
// so pages without a form stay as light as before.
add_filter( 'wpcf7_load_js', '__return_false' );
add_filter( 'wpcf7_load_css', '__return_false' );

// After a successful send: confirmation panel, analytics event, thank-you page
add_action( 'wp_enqueue_scripts', function () {
	wp_register_script( 'labora-forms', plugins_url( 'assets/labora-forms.js', LABORA_FORMS_FILE ), array(), (string) filemtime( LABORA_FORMS_DIR . '/assets/labora-forms.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

/**
 * Print a form (template helper): <?php echo labora_forms_render( 'demo' ); ?>
 * Keys: 'demo' (Book a demo), 'contact' (Contact page). $args: html_id, html_class.
 */
function labora_forms_render( string $key, array $args = array() ): string {
	if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
		wpcf7_enqueue_scripts();
		wpcf7_enqueue_styles();
	}
	wp_enqueue_script( 'labora-forms' );
	return Labora_Forms_Forms::render( $key, $args );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Create or update the site's Contact Form 7 forms from their definitions in code.
	 *
	 * [--overwrite-settings]
	 * : Also reset each form's Additional Settings (skip_mail, labora_thanks, ...) to the defaults.
	 *
	 * ## EXAMPLES
	 *     wp labora-forms setup
	 */
	WP_CLI::add_command( 'labora-forms setup', function ( $args, $assoc ) {
		if ( ! defined( 'WPCF7_VERSION' ) ) {
			WP_CLI::error( 'Contact Form 7 is not active.' );
		}
		foreach ( Labora_Forms_Forms::setup( ! empty( $assoc['overwrite-settings'] ) ) as $key => $id ) {
			WP_CLI::log( sprintf( '%-8s CF7 form #%d (%s)', $key, $id, wpcf7_contact_form( $id )->hash() ) );
		}
		WP_CLI::success( 'Forms are up to date.' );
	} );
}
