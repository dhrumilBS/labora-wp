<?php
/**
 * The site's Contact Form 7 forms, defined in code so they are versioned with the site and identical on every
 * environment. "wp labora-forms setup" creates or updates them in CF7; their IDs are kept in the
 * labora_forms_ids option. Templates use labora_forms_render( 'demo' ).
 *
 * Markup matches the HTML site's forms (.form-grid, .field, labels, ids), so the theme's CSS styles them unchanged.
 * Every form has "skip_mail: on" in Additional Settings: submissions are validated and stored as leads
 * (Contact > Database), but no email is sent. Remove that line in CF7 to start sending mail.
 *
 * @package Labora_Forms
 */

defined( 'ABSPATH' ) || exit;

final class Labora_Forms_Forms {

	const OPTION = 'labora_forms_ids';

	/** Form definitions: title, CF7 form template, additional settings, analytics event. */
	public static function definitions(): array {
		$centers   = '"1" "2–5" "6–20" "More than 20"';
		$settings  = function ( array $required, string $event ) {
			$lines = array(
				'skip_mail: on',
				'labora_event: ' . $event,
				'# After a successful send, open this page (relative to the site). Leave empty to stay on the form.',
				'labora_thanks: ',
			);
			foreach ( $required as $field => $message ) {
				$lines[] = "lead_guard_required_{$field}: {$message}"; // read by Lead Guard for Contact Form 7
			}
			return implode( "\n", $lines );
		};

		return array(
			'demo' => array(
				'title'    => 'Book a demo',
				'event'    => 'demo_request_submitted',
				'form'     => implode( "\n", array(
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
				'settings' => $settings( array(
					'full_name' => 'Enter your name.',
					'email'    => 'Enter a valid work email.',
					'company'  => 'Enter your lab or organization.',
					'lab_type' => 'Choose a type of lab.',
					'centers'  => 'Choose the number of centers.',
				), 'demo_request_submitted' ),
				'messages' => array(
					'mail_sent_ok'     => "Demo request received. We'll email you shortly.",
					'validation_error' => 'Please check the highlighted fields.',
					'spam'             => 'Your message could not be sent. Please try again.',
					'invalid_email'    => 'Enter a valid email address.',
					'invalid_tel'      => 'Enter a valid phone number.',
				),
				'mail_subject' => 'Demo request from [full_name] ([company])',
				'mail_body'    => "Name: [full_name]\nEmail: [email]\nLab or organization: [company]\nType of lab: [lab_type]\nCenters: [centers]\n\nWhat they want to see:\n[message]",
			),
			'contact' => array(
				'title'    => 'Contact',
				'event'    => 'contact_submitted',
				'form'     => implode( "\n", array(
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
				'settings' => $settings( array(
					'full_name' => 'Enter your name.',
					'email'   => 'Enter a valid work email.',
					'company' => 'Enter your lab or organization.',
					'topic'   => 'Choose a topic.',
					'centers' => 'Choose the number of centers.',
				), 'contact_submitted' ),
				'messages' => array(
					'mail_sent_ok'     => "Message received. We'll reply by email.",
					'validation_error' => 'Please check the highlighted fields.',
					'spam'             => 'Your message could not be sent. Please try again.',
					'invalid_email'    => 'Enter a valid email address.',
					'invalid_tel'      => 'Enter a valid phone number.',
				),
				'mail_subject' => '[topic] from [full_name] ([company])',
				'mail_body'    => "Name: [full_name]\nEmail: [email]\nPhone: [phone]\nLab or organization: [company]\nTopic: [topic]\nCenters: [centers]\nPlan: [plan]\nModules: [modules]\n\nMessage:\n[message]",
			),
		);
	}

	/** Create or update the CF7 forms from definitions(). Returns [ key => form id ]. */
	public static function setup( bool $overwrite_settings = false ): array {
		$ids   = (array) get_option( self::OPTION, array() );
		$admin = get_option( 'admin_email' );
		foreach ( self::definitions() as $key => $def ) {
			$form = ! empty( $ids[ $key ] ) ? wpcf7_contact_form( (int) $ids[ $key ] ) : null;
			if ( ! $form ) {
				$form = WPCF7_ContactForm::get_template( array( 'title' => $def['title'] ) );
			}
			$props = $form->get_properties();

			$props['form'] = $def['form'];
			$props['mail'] = array_merge( (array) $props['mail'], array(
				'active'             => true,
				'subject'            => $def['mail_subject'],
				'sender'             => '[_site_title] <' . $admin . '>',
				'recipient'          => $admin,
				'body'               => $def['mail_body'] . "\n\n--\nSent from [_site_title] ([_url])",
				'additional_headers' => 'Reply-To: [full_name] <[email]>',
				'use_html'           => false,
			) );
			$props['mail_2']['active'] = false;
			$props['messages']         = array_merge( (array) $props['messages'], $def['messages'] );
			// Additional Settings: written on first setup; kept afterwards (so skip_mail or labora_thanks edits in the
			// admin survive) unless --overwrite-settings
			if ( $overwrite_settings || '' === trim( (string) $props['additional_settings'] ) ) {
				$props['additional_settings'] = $def['settings'];
			}
			$form->set_title( $def['title'] );
			$form->set_properties( $props );
			$ids[ $key ] = (int) $form->save();
		}
		update_option( self::OPTION, $ids, false );
		return $ids;
	}

	/** The CF7 form for a key, or null. */
	public static function get( string $key ): ?WPCF7_ContactForm {
		$ids  = (array) get_option( self::OPTION, array() );
		$form = ! empty( $ids[ $key ] ) && function_exists( 'wpcf7_contact_form' ) ? wpcf7_contact_form( (int) $ids[ $key ] ) : null;
		return $form ?: null;
	}

	/**
	 * Render a form. $args: html_id, html_class (passed to the CF7 shortcode).
	 * The form element also gets data-labora-* attributes read by assets/labora-forms.js.
	 */
	public static function render( string $key, array $args = array() ): string {
		$form = self::get( $key );
		if ( ! $form ) {
			return current_user_can( 'manage_options' )
				? '<p class="form-error is-visible">' . esc_html__( 'This form is not set up yet. Run: wp labora-forms setup', 'labora-forms' ) . '</p>'
				: '';
		}
		self::$rendering = $form;
		$shortcode       = sprintf(
			'[contact-form-7 id="%s" html_id="%s" html_class="%s"]',
			esc_attr( $form->hash() ),
			esc_attr( $args['html_id'] ?? 'labora-' . $key . '-form' ),
			esc_attr( trim( 'labora-form ' . ( $args['html_class'] ?? '' ) ) )
		);
		$html            = do_shortcode( $shortcode );
		self::$rendering = null;
		return $html;
	}

	/** Form being rendered (for form_atts). */
	private static ?WPCF7_ContactForm $rendering = null;

	/** True when a CF7 form is one of this plugin's forms. */
	public static function is_ours( ?WPCF7_ContactForm $form ): bool {
		return $form && in_array( (int) $form->id(), array_map( 'intval', (array) get_option( self::OPTION, array() ) ), true );
	}

	/** CF7's automatic paragraphs: off for this plugin's forms. */
	public static function autop( $autop ) {
		return self::is_ours( self::$rendering ?? WPCF7_ContactForm::get_current() ) ? false : $autop;
	}

	/** data-labora-* attributes on the <form>: key, analytics event, thank-you page. */
	public static function form_atts( $atts ) {
		$form = self::$rendering ?? WPCF7_ContactForm::get_current();
		if ( ! $form ) {
			return $atts;
		}
		$key = array_search( (int) $form->id(), array_map( 'intval', (array) get_option( self::OPTION, array() ) ), true );
		if ( false === $key ) {
			return $atts;
		}
		$atts['data-labora-form'] = $key;
		$event                    = $form->additional_setting( 'labora_event', 1 );
		if ( $event && '' !== trim( $event[0] ) ) {
			$atts['data-labora-event'] = sanitize_key( $event[0] );
		}
		$thanks = $form->additional_setting( 'labora_thanks', 1 );
		if ( $thanks && '' !== trim( $thanks[0] ) ) {
			$atts['data-labora-thanks'] = esc_url( home_url( '/' . ltrim( trim( $thanks[0] ), '/' ) ) );
		}
		return $atts;
	}
}
