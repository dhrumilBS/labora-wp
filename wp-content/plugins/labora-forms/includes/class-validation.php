<?php
/**
 * Contact Form 7 integration: applies to every CF7 form on the site.
 *
 *  - Every [tel] field: validated and normalized to E.164 (see Labora_Forms_Phone). CF7's own, looser phone check
 *    is replaced by this one so each problem gets a precise message.
 *  - Every [email] field: CF7's format check, then Labora_Forms_Email (temporary inboxes, domain that receives mail).
 *  - Normalized values replace what the visitor typed in the submission, so saved leads and emails use them.
 *  - The same email or phone may submit a form once per 24 hours (Labora_Forms_Duplicates); a repeat is stopped
 *    with a clear message, nothing is saved and no mail is sent.
 *  - A filled honeypot field named "website" marks the submission as spam (not saved, not mailed).
 *  - Required-field messages can be set per field (labora_required_<name>: Enter your name. in Additional Settings).
 * When any check fails, CF7 stops: the lead is not stored and no email goes out.
 *
 * @package Labora_Forms
 */

defined( 'ABSPATH' ) || exit;

final class Labora_Forms_Validation {

	/** What the visitor typed, before normalization (for messages). [ field => raw ] */
	private static array $raw = array();
	/** Normalization results for this request. [ field => result array ] */
	private static array $phones = array();
	private static array $emails = array();
	/** Set when a repeat submission is stopped. */
	private static bool $duplicate = false;

	public static function init(): void {
		add_action( 'wpcf7_swv_create_schema', array( __CLASS__, 'drop_cf7_tel_rule' ), 99 );
		add_filter( 'wpcf7_is_tel', array( __CLASS__, 'cf7_is_tel' ), 10, 2 );
		add_filter( 'wpcf7_posted_data', array( __CLASS__, 'normalize' ), 20 );
		add_filter( 'wpcf7_validate', array( __CLASS__, 'validate' ), 20, 2 );
		add_filter( 'wpcf7_spam', array( __CLASS__, 'honeypot' ), 5 );
		add_filter( 'wpcf7_feedback_response', array( __CLASS__, 'response' ), 20, 2 );
		add_action( 'wpcf7_mail_sent', array( __CLASS__, 'remember' ) );
	}

	/**
	 * CF7 6 checks [tel] fields with its own rule (TelRule) on the server and, from the same schema, in the browser.
	 * It rejects formats this plugin accepts ("(512) 555-0123 x45") and has one generic message, so remove that rule
	 * from each form's schema; validate() applies Labora_Forms_Phone with precise messages instead. CF7's schema has
	 * no public way to remove a rule, so the rule list is filtered in the schema's own scope. If CF7 changes these
	 * internals, nothing is removed and CF7's rule simply keeps running alongside this plugin's check.
	 */
	public static function drop_cf7_tel_rule( $schema ): void {
		$base = '\RockLobsterInc\Swv\CompositeRule';
		$tel  = '\RockLobsterInc\Swv\Rules\TelRule';
		if ( ! is_object( $schema ) || ! class_exists( $base ) || ! class_exists( $tel ) || ! is_a( $schema, ltrim( $base, '\\' ) ) ) {
			return;
		}
		$drop = function () use ( $tel ) {
			if ( isset( $this->rules ) && is_array( $this->rules ) ) {
				$this->rules = array_values( array_filter( $this->rules, fn( $rule ) => ! is_a( $rule, ltrim( $tel, '\\' ) ) ) );
			}
		};
		try {
			\Closure::bind( $drop, $schema, ltrim( $base, '\\' ) )();
		} catch ( \Throwable $e ) {
			// Keep CF7's rule if its internals changed
		}
	}

	/**
	 * CF7's built-in phone rule rejects formats this plugin accepts (dots, extensions) and gives one generic message.
	 * Let every non-empty value through it; validate() below does the real check with specific messages.
	 */
	public static function cf7_is_tel( $result, $tel ) {
		return '' !== trim( (string) $tel ) ? true : $result;
	}

	/** @return WPCF7_FormTag[] the form's tags of a base type (email, tel). */
	private static function tags( string $basetype ): array {
		$form = WPCF7_ContactForm::get_current();
		return $form ? $form->scan_form_tags( array( 'basetype' => $basetype ) ) : array();
	}

	/** Replace typed phone and email values with their normalized forms. */
	public static function normalize( $posted ) {
		if ( ! is_array( $posted ) ) {
			return $posted;
		}
		foreach ( self::tags( 'tel' ) as $tag ) {
			$name = $tag->name;
			$val  = isset( $posted[ $name ] ) ? (string) $posted[ $name ] : '';
			if ( '' === trim( $val ) ) {
				continue;
			}
			self::$raw[ $name ]    = $val;
			self::$phones[ $name ] = Labora_Forms_Phone::normalize( $val );
			if ( self::$phones[ $name ]['valid'] ) {
				$posted[ $name ] = self::$phones[ $name ]['display'];
			}
		}
		foreach ( self::tags( 'email' ) as $tag ) {
			$name = $tag->name;
			$val  = isset( $posted[ $name ] ) ? (string) $posted[ $name ] : '';
			if ( '' === trim( $val ) ) {
				continue;
			}
			self::$raw[ $name ]    = $val;
			self::$emails[ $name ] = Labora_Forms_Email::normalize( $val );
			if ( self::$emails[ $name ]['valid'] ) {
				$posted[ $name ] = self::$emails[ $name ]['email'];
			}
		}
		return $posted;
	}

	/** Strict phone and email checks, then the 24-hour duplicate check. */
	public static function validate( $result, $tags ) {
		$submission = WPCF7_Submission::get_instance();
		$form       = WPCF7_ContactForm::get_current();
		if ( ! $submission || ! $form ) {
			return $result;
		}

		$first_email = $first_tel = null;
		foreach ( (array) $tags as $tag ) {
			if ( ! $tag instanceof WPCF7_FormTag || '' === $tag->name ) {
				continue;
			}
			if ( 'tel' === $tag->basetype ) {
				$first_tel = $first_tel ?? $tag;
				$check     = self::$phones[ $tag->name ] ?? null;
				if ( $check && ! $check['valid'] && $result->is_valid( $tag->name ) ) {
					$result->invalidate( $tag, Labora_Forms_Phone::message( $check['reason'] ) );
				}
			} elseif ( 'email' === $tag->basetype ) {
				$first_email = $first_email ?? $tag;
				$check       = self::$emails[ $tag->name ] ?? null;
				if ( $check && ! $check['valid'] && $result->is_valid( $tag->name ) ) {
					$result->invalidate( $tag, Labora_Forms_Email::message( $check['reason'] ) );
				}
			}
		}

		// Only a submission that is otherwise valid is checked for a repeat, so fixable errors show first
		if ( $result->is_valid() && ( $first_email || $first_tel ) ) {
			$email = $first_email ? (string) $submission->get_posted_data( $first_email->name ) : '';
			$phone = $first_tel ? ( self::$phones[ $first_tel->name ]['e164'] ?? '' ) : '';
			if ( Labora_Forms_Duplicates::is_repeat( (int) $form->id(), $email, $phone ) ) {
				self::$duplicate = true;
				$result->invalidate( $first_email ?: $first_tel, Labora_Forms_Duplicates::message() );
			}
		}
		return $result;
	}

	/** A filled "website" honeypot field means a bot. */
	public static function honeypot( $spam ) {
		if ( $spam ) {
			return $spam;
		}
		$submission = WPCF7_Submission::get_instance();
		$value      = $submission ? $submission->get_posted_data( 'website' ) : '';
		if ( is_string( $value ) && '' !== trim( $value ) ) {
			$submission->add_spam_log( array( 'agent' => 'labora-forms', 'reason' => 'Honeypot field "website" was filled.' ) );
			return true;
		}
		return false;
	}

	/**
	 * Response sent back to the browser:
	 *  - a repeat submission gets the duplicate message as the form's message;
	 *  - required-field errors use the per-field text from Additional Settings ("labora_required_name: Enter your name.").
	 */
	public static function response( $response, $result ) {
		$form = WPCF7_ContactForm::get_current();
		if ( self::$duplicate ) {
			$response['message'] = Labora_Forms_Duplicates::message();
		}
		if ( $form && ! empty( $response['invalid_fields'] ) ) {
			$generic = $form->message( 'invalid_required' );
			foreach ( $response['invalid_fields'] as &$field ) {
				$custom = $form->additional_setting( 'labora_required_' . ( $field['field'] ?? '' ), 1 );
				if ( $custom && $field['message'] === $generic ) {
					$field['message'] = wp_strip_all_tags( $custom[0] );
				}
			}
			unset( $field );
		}
		return $response;
	}

	/** After a successful submission: remember its email and phone for the duplicate check. */
	public static function remember( $form ): void {
		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission || ! $form instanceof WPCF7_ContactForm ) {
			return;
		}
		$email = $phone = '';
		foreach ( $form->scan_form_tags( array( 'basetype' => 'email' ) ) as $tag ) {
			$email = (string) $submission->get_posted_data( $tag->name );
			break;
		}
		foreach ( $form->scan_form_tags( array( 'basetype' => 'tel' ) ) as $tag ) {
			$phone = self::$phones[ $tag->name ]['e164'] ?? '';
			break;
		}
		if ( '' !== $email || '' !== $phone ) {
			Labora_Forms_Duplicates::record( (int) $form->id(), $email, $phone );
		}
	}
}
