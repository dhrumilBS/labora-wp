<?php
/**
 * Contact Form 7 integration. Works on any CF7 form by field type, whatever the field names are:
 *
 *  - [tel]   validated and normalized (Lead_Guard_Phone). CF7's own, looser phone rule is removed from the form's
 *            schema (server and browser) so these checks and messages apply.
 *  - [email] after CF7's format check: temporary inboxes, free providers (optional), mail domain (Lead_Guard_Email).
 *  - Normalized values replace what was typed, so stored leads and emails use them.
 *  - Repeat submissions (same email or phone within the window) are stopped before sending: the form shows one
 *    short message in its message box, nothing is stored, and no email is sent.
 *  - Honeypot: a hidden field added to every form; filling it marks the submission as spam.
 *  - Required-field messages per field: "lead_guard_required_<field>: Message" in Additional Settings.
 *  - "lead_guard: off" in a form's Additional Settings turns all of this off for that form.
 *
 * @package Lead_Guard_CF7
 */

defined( 'ABSPATH' ) || exit;

final class Lead_Guard_Validator {

	const HONEYPOT = '_lg_website'; // starts with "_" so CF7 never stores it with the submission

	private static array $phones = array();
	private static array $emails = array();

	public static function init(): void {
		add_action( 'wpcf7_swv_create_schema', array( __CLASS__, 'drop_cf7_tel_rule' ), 99, 2 );
		add_filter( 'wpcf7_posted_data', array( __CLASS__, 'normalize' ), 20 );
		add_filter( 'wpcf7_validate', array( __CLASS__, 'validate' ), 20, 2 );
		add_action( 'wpcf7_before_send_mail', array( __CLASS__, 'stop_repeat' ), 5, 3 );
		add_action( 'wpcf7_mail_sent', array( __CLASS__, 'remember' ) );
		add_filter( 'wpcf7_feedback_response', array( __CLASS__, 'required_messages' ), 20 );
		add_filter( 'wpcf7_form_elements', array( __CLASS__, 'add_honeypot' ) );
		add_filter( 'wpcf7_spam', array( __CLASS__, 'check_honeypot' ), 5 );
	}

	/** True when Lead Guard is on for this form (no "lead_guard: off"). */
	public static function on( $form, string $setting = 'lead_guard' ): bool {
		if ( ! $form instanceof WPCF7_ContactForm ) {
			return false;
		}
		foreach ( (array) $form->additional_setting( $setting, 1 ) as $value ) {
			if ( in_array( strtolower( trim( (string) $value ) ), array( 'off', 'false', '0', 'no' ), true ) ) {
				return false;
			}
		}
		return true;
	}

	private static function current(): ?WPCF7_ContactForm {
		$form = WPCF7_ContactForm::get_current();
		return $form && self::on( $form ) ? $form : null;
	}

	/**
	 * CF7 6 checks [tel] with its own rule (server and browser). It rejects formats accepted here (extensions) and has
	 * one generic message, so it is removed from the schema when phone validation is on. The schema has no public way
	 * to remove a rule, so the list is filtered in the schema's own scope; if CF7 changes this, nothing is removed.
	 */
	public static function drop_cf7_tel_rule( $schema, $form = null ): void {
		if ( ! Lead_Guard_Settings::get( 'phone_enabled' ) || ( $form && ! self::on( $form ) ) ) {
			return;
		}
		$base = 'RockLobsterInc\Swv\CompositeRule';
		$tel  = 'RockLobsterInc\Swv\Rules\TelRule';
		if ( ! class_exists( $base ) || ! class_exists( $tel ) || ! is_a( $schema, $base ) ) {
			return;
		}
		try {
			\Closure::bind( function () use ( $tel ) {
				if ( isset( $this->rules ) && is_array( $this->rules ) ) {
					$this->rules = array_values( array_filter( $this->rules, fn( $r ) => ! is_a( $r, $tel ) ) );
				}
			}, $schema, $base )();
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
		}
	}

	/** Replace typed phones and emails with their normalized forms. */
	public static function normalize( $posted ) {
		$form = self::current();
		if ( ! $form || ! is_array( $posted ) ) {
			return $posted;
		}
		if ( Lead_Guard_Settings::get( 'phone_enabled' ) ) {
			$nanp = 'nanp' === Lead_Guard_Settings::get( 'phone_default' );
			foreach ( $form->scan_form_tags( array( 'basetype' => 'tel' ) ) as $tag ) {
				$val = isset( $posted[ $tag->name ] ) ? trim( (string) $posted[ $tag->name ] ) : '';
				if ( '' === $val ) {
					continue;
				}
				self::$phones[ $tag->name ] = Lead_Guard_Phone::normalize( $val, $nanp );
				if ( self::$phones[ $tag->name ]['valid'] && 'e164' === Lead_Guard_Settings::get( 'phone_save' ) ) {
					$posted[ $tag->name ] = self::$phones[ $tag->name ]['display'];
				}
			}
		}
		if ( Lead_Guard_Settings::get( 'email_enabled' ) ) {
			foreach ( $form->scan_form_tags( array( 'basetype' => 'email' ) ) as $tag ) {
				$val = isset( $posted[ $tag->name ] ) ? trim( (string) $posted[ $tag->name ] ) : '';
				if ( '' === $val ) {
					continue;
				}
				self::$emails[ $tag->name ] = Lead_Guard_Email::normalize( $val );
				if ( self::$emails[ $tag->name ]['valid'] ) {
					$posted[ $tag->name ] = self::$emails[ $tag->name ]['email'];
				}
			}
		}
		return $posted;
	}

	/** Field errors for phones and emails (shown under the field). */
	public static function validate( $result, $tags ) {
		if ( ! self::current() ) {
			return $result;
		}
		foreach ( (array) $tags as $tag ) {
			if ( ! $tag instanceof WPCF7_FormTag || '' === $tag->name || ! $result->is_valid( $tag->name ) ) {
				continue;
			}
			if ( 'tel' === $tag->basetype && isset( self::$phones[ $tag->name ] ) && ! self::$phones[ $tag->name ]['valid'] ) {
				$result->invalidate( $tag, Lead_Guard_Settings::message( 'country' === self::$phones[ $tag->name ]['reason'] ? 'phone_country' : 'phone' ) );
			} elseif ( 'email' === $tag->basetype && isset( self::$emails[ $tag->name ] ) && ! self::$emails[ $tag->name ]['valid'] ) {
				$result->invalidate( $tag, Lead_Guard_Settings::message( self::$emails[ $tag->name ]['reason'] ) );
			}
		}
		return $result;
	}

	/** First email and first phone of a submission (normalized). */
	private static function identity( WPCF7_ContactForm $form, WPCF7_Submission $submission ): array {
		$email = $phone = '';
		foreach ( $form->scan_form_tags( array( 'basetype' => 'email' ) ) as $tag ) {
			$email = trim( (string) $submission->get_posted_data( $tag->name ) );
			break;
		}
		foreach ( $form->scan_form_tags( array( 'basetype' => 'tel' ) ) as $tag ) {
			$phone = self::$phones[ $tag->name ]['e164'] ?? preg_replace( '/[^\d+]/', '', (string) $submission->get_posted_data( $tag->name ) );
			break;
		}
		return array( $email, $phone );
	}

	/** A repeat within the window: stop before anything is sent or stored, with one message in the form's box. */
	public static function stop_repeat( $form, &$abort, $submission ): void {
		if ( $abort || ! Lead_Guard_Settings::get( 'dup_enabled' ) || ! self::on( $form ) || ! self::on( $form, 'lead_guard_duplicates' ) || ! $submission ) {
			return;
		}
		list( $email, $phone ) = self::identity( $form, $submission );
		if ( Lead_Guard_Log::is_repeat( (int) $form->id(), $email, $phone ) ) {
			$abort = true;
			$submission->set_response( Lead_Guard_Settings::message( 'duplicate' ) );
		}
	}

	public static function remember( $form ): void {
		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission || ! self::on( $form ) || ! Lead_Guard_Settings::get( 'dup_enabled' ) ) {
			return;
		}
		list( $email, $phone ) = self::identity( $form, $submission );
		if ( '' !== $email || '' !== $phone ) {
			Lead_Guard_Log::record( (int) $form->id(), $email, $phone );
		}
	}

	/** Per-field required messages from Additional Settings. */
	public static function required_messages( $response ) {
		$form = self::current();
		if ( ! $form || empty( $response['invalid_fields'] ) ) {
			return $response;
		}
		$generic = $form->message( 'invalid_required' );
		foreach ( $response['invalid_fields'] as &$field ) {
			$custom = $form->additional_setting( 'lead_guard_required_' . ( $field['field'] ?? '' ), 1 );
			if ( $custom && $field['message'] === $generic ) {
				$field['message'] = wp_strip_all_tags( $custom[0] );
			}
		}
		unset( $field );
		return $response;
	}

	/** Hidden honeypot field, positioned off-screen with inline styles so it works with any theme. */
	public static function add_honeypot( $html ) {
		if ( ! Lead_Guard_Settings::get( 'honeypot' ) || ! self::on( WPCF7_ContactForm::get_current() ) ) {
			return $html;
		}
		return $html . sprintf(
			'<span class="lead-guard-hp" aria-hidden="true" style="position:absolute!important;left:-9999px!important;width:1px;height:1px;overflow:hidden"><label>%s <input type="text" name="%s" value="" tabindex="-1" autocomplete="off"></label></span>',
			esc_html__( 'Leave this field empty', 'lead-guard-cf7' ),
			esc_attr( self::HONEYPOT )
		);
	}

	public static function check_honeypot( $spam ) {
		if ( $spam || ! Lead_Guard_Settings::get( 'honeypot' ) || ! self::current() ) {
			return $spam;
		}
		$value = isset( $_POST[ self::HONEYPOT ] ) ? trim( (string) wp_unslash( $_POST[ self::HONEYPOT ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- CF7 handles the request
		if ( '' !== $value ) {
			$submission = WPCF7_Submission::get_instance();
			if ( $submission ) {
				$submission->add_spam_log( array( 'agent' => 'lead-guard-cf7', 'reason' => 'Honeypot field was filled.' ) );
			}
			return true;
		}
		return false;
	}
}
