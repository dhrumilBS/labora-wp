<?php
/**
 * The Labora site's Contact Form 7 forms, defined in code so they are versioned and identical on every environment.
 *
 *   wp labora forms-setup                        create or update the forms in CF7 (keeps Additional Settings edits),
 *                                                and the Thank you page they open after a send
 *   wp labora forms-setup --overwrite-settings   also reset each form's Additional Settings to the defaults below
 *   <?php echo labora_form( 'demo' ); ?>         print a form in a template ('demo' = Book a demo, 'contact')
 *
 * Validation, the honeypot, the repeat check, the thank-you redirect, and the analytics event come from the
 * Lead Guard for Contact Form 7 plugin (per-form lines in Additional Settings; see that tab in the CF7 editor).
 * Messages are Contact Form 7's own (Messages tab), set here to the site's wording.
 * The form markup matches the HTML site (.form-grid, .field, labels, ids); CF7 pieces are styled in child.css.
 *
 * @package Labora_Child
 */

defined( 'ABSPATH' ) || exit;

const LABORA_FORMS_OPTION = 'labora_forms_ids'; // [ key => CF7 form id ]

/** Contact Form 7 messages (Messages tab), shared by both forms. Short, in the site's voice. */
function labora_form_messages(): array {
	return array(
		'mail_sent_ng'            => 'Your message could not be sent. Please try again.',
		'validation_error'        => 'Please check the highlighted fields.',
		'spam'                    => 'Your message could not be sent. Please try again.',
		'accept_terms'            => 'Please accept the terms to continue.',
		'invalid_required'        => 'Please fill out this field.',
		'invalid_too_long'        => 'This is too long.',
		'invalid_too_short'       => 'This is too short.',
		'upload_failed'           => 'The file could not be uploaded.',
		'upload_file_type_invalid' => 'This file type is not allowed.',
		'upload_file_too_large'   => 'This file is too large.',
		'upload_failed_php_error' => 'The file could not be uploaded.',
		'invalid_date'            => 'Enter a valid date.',
		'date_too_early'          => 'This date is too early.',
		'date_too_late'           => 'This date is too late.',
		'invalid_number'          => 'Enter a number.',
		'number_too_small'        => 'This number is too small.',
		'number_too_large'        => 'This number is too large.',
		'quiz_answer_not_correct' => 'That answer is not correct.',
		'captcha_not_match'       => 'The code does not match.',
		'invalid_email'           => 'Enter a valid email address.',
		'invalid_url'             => 'Enter a valid web address.',
		'invalid_tel'             => 'Enter a valid phone number.',
	);
}

/** Form definitions. */
function labora_form_definitions(): array {
	$centers  = '"1" "2–5" "6–20" "More than 20"';
	$settings = function ( string $event, array $required ) {
		$lines = array(
			'# Lead Guard and CF7 settings for this form. The full list is documented below this box.',
			'skip_mail: on',
			'lead_guard_event: ' . $event,
			'lead_guard_redirect: /thank-you/',
		);
		foreach ( $required as $field => $message ) {
			$lines[] = "lead_guard_required_{$field}: {$message}";
		}
		return implode( "\n", $lines );
	};
	return array(
		'demo'    => array(
			'title'        => 'Book a demo',
			'form'         => implode( "\n", array(
				'<div class="form-grid">',
				'  <div class="field"><label for="f-name">Full name</label>[text* full_name id:f-name autocomplete:name]</div>',
				'  <div class="field"><label for="f-email">Work email</label>[email* email id:f-email autocomplete:email]</div>',
				'  <div class="field"><label for="f-company">Lab or organization</label>[text* company id:f-company autocomplete:organization]</div>',
				'  <div class="field"><label for="f-lab">Type of lab</label>[select* lab_type id:f-lab first_as_label "Select one" "Pathology lab" "Diagnostic and imaging center" "Multi-center lab chain" "Hospital laboratory" "Home collection service" "Cardiology or ECG clinic" "Other"]</div>',
				'  <div class="field field--full"><label for="f-size">Number of centers</label>[select* centers id:f-size first_as_label "Select one" ' . $centers . ']</div>',
				'  <div class="field field--full"><label for="f-msg">What would you like to see? <span class="opt">(optional)</span></label>[textarea message id:f-msg x3]</div>',
				'</div>',
				'<div class="form-submit">[submit class:btn class:btn--primary class:btn--lg "Book a demo"]</div>',
			) ),
			'settings'     => $settings( 'demo_request_submitted', array(
				'full_name' => 'Enter your name.',
				'email'     => 'Enter a valid work email.',
				'company'   => 'Enter your lab or organization.',
				'lab_type'  => 'Choose a type of lab.',
				'centers'   => 'Choose the number of centers.',
			) ),
			'sent'         => "Demo request received. We'll email you shortly.",
			'mail_subject' => 'Demo request from [full_name] ([company])',
			'mail_body'    => "Name: [full_name]\nEmail: [email]\nLab or organization: [company]\nType of lab: [lab_type]\nCenters: [centers]\n\nWhat they want to see:\n[message]",
		),
		'contact' => array(
			'title'        => 'Contact',
			'form'         => implode( "\n", array(
				'<div class="form-grid">',
				'  <div class="field"><label for="c-name">Full name</label>[text* full_name id:c-name autocomplete:name]</div>',
				'  <div class="field"><label for="c-email">Work email</label>[email* email id:c-email autocomplete:email]</div>',
				'  <div class="field"><label for="c-company">Lab or organization</label>[text* company id:c-company autocomplete:organization]</div>',
				'  <div class="field"><label for="c-phone">Phone <span class="opt">(optional)</span></label>[tel phone id:c-phone autocomplete:tel]</div>',
				'  <div class="field"><label for="c-topic">How can we help?</label>[select* topic id:c-topic first_as_label "Select one" "Book a demo" "Pricing and a quote" "Partnership" "Something else"]</div>',
				'  <div class="field"><label for="c-centers">Number of centers</label>[select* centers id:c-centers first_as_label "Select one" ' . $centers . ']</div>',
				'  <div class="field field--full"><label for="c-msg">Message <span class="opt">(optional)</span></label>[textarea message id:c-msg x4]</div>',
				'</div>',
				'[hidden plan id:c-plan][hidden modules id:c-modules]',
				'<div class="form-submit">[submit class:btn class:btn--primary class:btn--lg "Send message"]</div>',
			) ),
			'settings'     => $settings( 'contact_submitted', array(
				'full_name' => 'Enter your name.',
				'email'     => 'Enter a valid work email.',
				'company'   => 'Enter your lab or organization.',
				'topic'     => 'Choose a topic.',
				'centers'   => 'Choose the number of centers.',
			) ),
			'sent'         => "Message received. We'll reply by email.",
			'mail_subject' => '[topic] from [full_name] ([company])',
			'mail_body'    => "Name: [full_name]\nEmail: [email]\nPhone: [phone]\nLab or organization: [company]\nTopic: [topic]\nCenters: [centers]\nPlan: [plan]\nModules: [modules]\n\nMessage:\n[message]",
		),
	);
}

/** Create or update the CF7 forms. Returns [ key => form id ]. */
function labora_forms_setup( bool $overwrite_settings = false ): array {
	$ids   = (array) get_option( LABORA_FORMS_OPTION, array() );
	$admin = get_option( 'admin_email' );
	foreach ( labora_form_definitions() as $key => $def ) {
		$form = ! empty( $ids[ $key ] ) ? wpcf7_contact_form( (int) $ids[ $key ] ) : null;
		$form = $form ?: WPCF7_ContactForm::get_template( array( 'title' => $def['title'] ) );
		$p    = $form->get_properties();

		$p['form'] = $def['form'];
		$p['mail'] = array_merge( (array) $p['mail'], array(
			'active'             => true,
			'subject'            => $def['mail_subject'],
			'sender'             => '[_site_title] <' . $admin . '>',
			'recipient'          => $admin,
			'body'               => $def['mail_body'] . "\n\n--\nSent from [_site_title] ([_url])",
			'additional_headers' => 'Reply-To: [full_name] <[email]>',
			'use_html'           => false,
		) );
		$p['mail_2']['active'] = false;
		$p['messages']         = array_merge( (array) $p['messages'], labora_form_messages(), array( 'mail_sent_ok' => $def['sent'] ) );
		if ( $overwrite_settings || '' === trim( (string) $p['additional_settings'] ) ) {
			$p['additional_settings'] = $def['settings'];
		}
		$form->set_title( $def['title'] );
		$form->set_properties( $p );
		$ids[ $key ] = (int) $form->save();
	}
	update_option( LABORA_FORMS_OPTION, $ids, false );
	labora_thank_you_page();
	return $ids;
}

/**
 * The page the forms open after a send (lead_guard_redirect: /thank-you/). Its layout is the parent's
 * page-thank-you.php; it is created once, published, and set to noindex in Yoast (also keeps it out of the sitemap).
 */
function labora_thank_you_page(): int {
	$page = get_page_by_path( 'thank-you' );
	$id   = $page ? (int) $page->ID : (int) wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Thank you',
		'post_name'    => 'thank-you',
		'post_content' => '',
		'comment_status' => 'closed',
	) );
	if ( $id && ! $page ) {
		update_post_meta( $id, '_yoast_wpseo_meta-robots-noindex', '1' );
		update_post_meta( $id, '_yoast_wpseo_metadesc', 'Your message has reached the Labora team.' );
	}
	return $id;
}

/**
 * Thank-you page: the theme's pages.js reads sessionStorage "labora_thanks" ({ form: 'demo'|'contact', topic, name, ts }).
 * Lead Guard leaves "lead_guard_sent" ({ form: <form title slug>, ... }), so copy it across before pages.js runs.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_page( 'thank-you' ) || ! wp_script_is( 'labora-pages', 'enqueued' ) ) {
		return;
	}
	wp_add_inline_script( 'labora-pages', "try{var s=JSON.parse(sessionStorage.getItem('lead_guard_sent'));if(s&&s.form){s.form=s.form==='book-a-demo'?'demo':'contact';sessionStorage.setItem('labora_thanks',JSON.stringify(s));sessionStorage.removeItem('lead_guard_sent');}}catch(e){}", 'before' );
}, 30 );

/** True when a CF7 form is one of these site forms. */
function labora_is_site_form( $form ): bool {
	return $form instanceof WPCF7_ContactForm && in_array( (int) $form->id(), array_map( 'intval', (array) get_option( LABORA_FORMS_OPTION, array() ) ), true );
}

/**
 * Print a form: CF7's CSS and JS load only on pages that render a form (they are off elsewhere, see below).
 * $args: html_id, html_class.
 */
function labora_form( string $key, array $args = array() ): string {
	$ids  = (array) get_option( LABORA_FORMS_OPTION, array() );
	$form = ( ! empty( $ids[ $key ] ) && function_exists( 'wpcf7_contact_form' ) ) ? wpcf7_contact_form( (int) $ids[ $key ] ) : null;
	if ( ! $form ) {
		return current_user_can( 'manage_options' ) ? '<p class="form-error is-visible">This form is not set up yet. Run: wp labora forms-setup</p>' : '';
	}
	if ( function_exists( 'wpcf7_enqueue_scripts' ) ) {
		wpcf7_enqueue_scripts();
		wpcf7_enqueue_styles();
	}
	return do_shortcode( sprintf(
		'[contact-form-7 id="%s" html_id="%s" html_class="%s"]',
		esc_attr( $form->hash() ),
		esc_attr( $args['html_id'] ?? 'labora-' . $key . '-form' ),
		esc_attr( trim( 'labora-form ' . ( $args['html_class'] ?? '' ) ) )
	) );
}

if ( defined( 'WPCF7_VERSION' ) ) {
	// CF7 loads its files on every page by default; here only where labora_form() renders a form
	add_filter( 'wpcf7_load_js', '__return_false' );
	add_filter( 'wpcf7_load_css', '__return_false' );
	// The form templates are exact HTML: no automatic <p>/<br> from CF7 for these forms
	add_filter( 'wpcf7_autop_or_not', fn( $autop ) => labora_is_site_form( WPCF7_ContactForm::get_current() ) ? false : $autop );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Create or update the site's Contact Form 7 forms.
	 *
	 * [--overwrite-settings]
	 * : Also reset each form's Additional Settings to the defaults.
	 */
	WP_CLI::add_command( 'labora forms-setup', function ( $args, $assoc ) {
		if ( ! defined( 'WPCF7_VERSION' ) ) {
			WP_CLI::error( 'Contact Form 7 is not active.' );
		}
		foreach ( labora_forms_setup( ! empty( $assoc['overwrite-settings'] ) ) as $key => $id ) {
			WP_CLI::log( sprintf( '%-8s CF7 form #%d (%s)', $key, $id, wpcf7_contact_form( $id )->hash() ) );
		}
		WP_CLI::success( 'Forms are up to date.' );
	} );
}
