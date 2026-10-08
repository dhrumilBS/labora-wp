<?php
/**
 * Settings: one option array (lead_guard_cf7) with defaults, and the admin page under Contact > Lead Guard.
 *
 * Per-form overrides go in each form's Additional Settings (CF7 editor):
 *   lead_guard: off                       turn every check off for this form
 *   lead_guard_duplicates: off            allow repeat submissions on this form
 *   lead_guard_required_<field>: Message  message shown when that required field is empty
 *
 * @package Lead_Guard_CF7
 */

defined( 'ABSPATH' ) || exit;

final class Lead_Guard_Settings {

	const OPTION = 'lead_guard_cf7';
	const PAGE   = 'lead-guard-cf7';

	/** Defaults. Messages are short on purpose: they appear under fields or in the form's message box. */
	public static function defaults(): array {
		return array(
			'phone_enabled'      => 1,
			'phone_default'      => 'nanp',   // numbers typed without "+": nanp = US/Canada, none = require a country code
			'phone_save'         => 'e164',   // e164 = +15125550123, typed = keep what the visitor typed
			'email_enabled'      => 1,
			'email_dns'          => 1,
			'email_disposable'   => 1,
			'email_free'         => 0,        // reject gmail.com, yahoo.com ... (work email only)
			'disposable_domains' => implode( "\n", Lead_Guard_Email::DISPOSABLE ),
			'free_domains'       => implode( "\n", Lead_Guard_Email::FREE ),
			'dup_enabled'        => 1,
			'dup_hours'          => 24,
			'dup_match'          => 'any',    // any = email or phone matches, both = email and phone match
			'dup_scope'          => 'form',   // form = per form, all = across every form
			'honeypot'           => 1,
			'msg_phone'          => __( 'Enter a valid phone number.', 'lead-guard-cf7' ),
			'msg_phone_country'  => __( 'Add your country code, e.g. +44.', 'lead-guard-cf7' ),
			'msg_email'          => __( 'Enter a valid email address.', 'lead-guard-cf7' ),
			'msg_email_disposable' => __( 'Temporary email addresses are not accepted.', 'lead-guard-cf7' ),
			'msg_email_domain'   => __( 'This email domain cannot receive mail.', 'lead-guard-cf7' ),
			'msg_email_free'     => __( 'Please use your work email.', 'lead-guard-cf7' ),
			'msg_duplicate'      => __( 'You already sent a request in the last {hours} hours. We will be in touch soon.', 'lead-guard-cf7' ),
		);
	}

	public static function get( ?string $key = null ) {
		static $cache = null;
		if ( null === $cache ) {
			$cache = array_merge( self::defaults(), (array) get_option( self::OPTION, array() ) );
		}
		return null === $key ? $cache : ( $cache[ $key ] ?? null );
	}

	/** A message, with {hours} filled in. Filter: lead_guard_cf7_message. */
	public static function message( string $key ): string {
		$text = (string) self::get( 'msg_' . $key );
		$text = str_replace( '{hours}', (string) (int) self::get( 'dup_hours' ), $text );
		return (string) apply_filters( 'lead_guard_cf7_message', $text, $key );
	}

	/** Domains from a textarea setting: one per line, lowercased. */
	public static function domains( string $key ): array {
		$lines = preg_split( '/[\s,]+/', strtolower( (string) self::get( $key ) ) );
		return array_values( array_filter( array_map( 'trim', $lines ) ) );
	}

	// ---------------------------------------------------------------- Admin page

	public static function admin_init(): void {
		register_setting( self::PAGE, self::OPTION, array( 'sanitize_callback' => array( __CLASS__, 'sanitize' ) ) );
	}

	public static function admin_menu(): void {
		add_submenu_page( 'wpcf7', __( 'Lead Guard', 'lead-guard-cf7' ), __( 'Lead Guard', 'lead-guard-cf7' ), 'manage_options', self::PAGE, array( __CLASS__, 'render' ) );
	}

	public static function sanitize( $in ): array {
		$d   = self::defaults();
		$in  = (array) $in;
		$out = array();
		foreach ( array( 'phone_enabled', 'email_enabled', 'email_dns', 'email_disposable', 'email_free', 'dup_enabled', 'honeypot' ) as $k ) {
			$out[ $k ] = empty( $in[ $k ] ) ? 0 : 1;
		}
		$out['phone_default'] = in_array( $in['phone_default'] ?? '', array( 'nanp', 'none' ), true ) ? $in['phone_default'] : $d['phone_default'];
		$out['phone_save']    = in_array( $in['phone_save'] ?? '', array( 'e164', 'typed' ), true ) ? $in['phone_save'] : $d['phone_save'];
		$out['dup_hours']     = max( 1, min( 720, (int) ( $in['dup_hours'] ?? $d['dup_hours'] ) ) );
		$out['dup_match']     = in_array( $in['dup_match'] ?? '', array( 'any', 'both' ), true ) ? $in['dup_match'] : $d['dup_match'];
		$out['dup_scope']     = in_array( $in['dup_scope'] ?? '', array( 'form', 'all' ), true ) ? $in['dup_scope'] : $d['dup_scope'];
		foreach ( array( 'disposable_domains', 'free_domains' ) as $k ) {
			$out[ $k ] = sanitize_textarea_field( $in[ $k ] ?? $d[ $k ] );
		}
		foreach ( $d as $k => $v ) {
			if ( str_starts_with( $k, 'msg_' ) ) {
				$out[ $k ] = '' === trim( (string) ( $in[ $k ] ?? '' ) ) ? $v : sanitize_text_field( $in[ $k ] );
			}
		}
		return $out;
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s    = self::get();
		$name = fn( $k ) => esc_attr( self::OPTION . "[$k]" );
		$chk  = fn( $k, $label ) => sprintf( '<label><input type="checkbox" name="%s" value="1"%s> %s</label>', $name( $k ), checked( ! empty( $s[ $k ] ), true, false ), esc_html( $label ) );
		$sel  = function ( $k, array $opts ) use ( $s, $name ) {
			$h = sprintf( '<select name="%s">', $name( $k ) );
			foreach ( $opts as $v => $l ) {
				$h .= sprintf( '<option value="%s"%s>%s</option>', esc_attr( $v ), selected( $s[ $k ], $v, false ), esc_html( $l ) );
			}
			return $h . '</select>';
		};
		$txt  = fn( $k ) => sprintf( '<input type="text" class="large-text" name="%s" value="%s">', $name( $k ), esc_attr( $s[ $k ] ) );
		$area = fn( $k ) => sprintf( '<textarea class="large-text code" rows="6" name="%s">%s</textarea>', $name( $k ), esc_textarea( $s[ $k ] ) );
		$row  = fn( $label, $html, $help = '' ) => sprintf( '<tr><th scope="row">%s</th><td>%s%s</td></tr>', esc_html( $label ), $html, $help ? '<p class="description">' . esc_html( $help ) . '</p>' : '' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Lead Guard for Contact Form 7', 'lead-guard-cf7' ); ?></h1>
			<p><?php esc_html_e( 'Checks every Contact Form 7 form on this site before it is sent or stored. When a check fails, the submission is stopped: nothing is saved and no email is sent.', 'lead-guard-cf7' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( self::PAGE ); ?>
				<h2><?php esc_html_e( 'Phone fields ([tel])', 'lead-guard-cf7' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					echo $row( __( 'Validate phones', 'lead-guard-cf7' ), $chk( 'phone_enabled', __( 'Check and normalize every [tel] field', 'lead-guard-cf7' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo $row( __( 'Numbers without "+"', 'lead-guard-cf7' ), $sel( 'phone_default', array( 'nanp' => __( 'Treat as US/Canada (10 digits)', 'lead-guard-cf7' ), 'none' => __( 'Reject: a country code is required', 'lead-guard-cf7' ) ) ) ); // phpcs:ignore
					echo $row( __( 'Save as', 'lead-guard-cf7' ), $sel( 'phone_save', array( 'e164' => __( 'International format (+15125550123)', 'lead-guard-cf7' ), 'typed' => __( 'As typed', 'lead-guard-cf7' ) ) ) ); // phpcs:ignore
					?>
				</table>
				<h2><?php esc_html_e( 'Email fields ([email])', 'lead-guard-cf7' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					echo $row( __( 'Validate emails', 'lead-guard-cf7' ), $chk( 'email_enabled', __( 'Stricter checks on every [email] field', 'lead-guard-cf7' ) ) ); // phpcs:ignore
					echo $row( __( 'Mail domain', 'lead-guard-cf7' ), $chk( 'email_dns', __( 'The domain must be able to receive mail (DNS check, cached for a day)', 'lead-guard-cf7' ) ) ); // phpcs:ignore
					echo $row( __( 'Temporary inboxes', 'lead-guard-cf7' ), $chk( 'email_disposable', __( 'Reject the domains below', 'lead-guard-cf7' ) ) . $area( 'disposable_domains' ), __( 'One domain per line. Subdomains are included.', 'lead-guard-cf7' ) ); // phpcs:ignore
					echo $row( __( 'Free email providers', 'lead-guard-cf7' ), $chk( 'email_free', __( 'Reject the domains below (work email only)', 'lead-guard-cf7' ) ) . $area( 'free_domains' ) ); // phpcs:ignore
					?>
				</table>
				<h2><?php esc_html_e( 'Repeat submissions', 'lead-guard-cf7' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					echo $row( __( 'Block repeats', 'lead-guard-cf7' ), $chk( 'dup_enabled', __( 'Stop a person from submitting again within the window', 'lead-guard-cf7' ) ) ); // phpcs:ignore
					echo $row( __( 'Window (hours)', 'lead-guard-cf7' ), sprintf( '<input type="number" min="1" max="720" name="%s" value="%d" class="small-text">', $name( 'dup_hours' ), (int) $s['dup_hours'] ) ); // phpcs:ignore
					echo $row( __( 'Same person when', 'lead-guard-cf7' ), $sel( 'dup_match', array( 'any' => __( 'The email or the phone matches', 'lead-guard-cf7' ), 'both' => __( 'The email and the phone both match', 'lead-guard-cf7' ) ) ) ); // phpcs:ignore
					echo $row( __( 'Count', 'lead-guard-cf7' ), $sel( 'dup_scope', array( 'form' => __( 'Per form', 'lead-guard-cf7' ), 'all' => __( 'Across all forms', 'lead-guard-cf7' ) ) ), __( 'Only keyed hashes of the email and phone are stored, and old rows are deleted daily.', 'lead-guard-cf7' ) ); // phpcs:ignore
					?>
				</table>
				<h2><?php esc_html_e( 'Spam', 'lead-guard-cf7' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php echo $row( __( 'Honeypot', 'lead-guard-cf7' ), $chk( 'honeypot', __( 'Add a hidden field to every form; a submission that fills it is marked as spam', 'lead-guard-cf7' ) ) ); // phpcs:ignore ?>
				</table>
				<h2><?php esc_html_e( 'Messages', 'lead-guard-cf7' ); ?></h2>
				<p><?php esc_html_e( 'Field errors appear under the field; the repeat message appears in the form\'s message box. {hours} is replaced by the window.', 'lead-guard-cf7' ); ?></p>
				<table class="form-table" role="presentation">
					<?php
					$labels = array(
						'msg_phone'            => __( 'Invalid phone', 'lead-guard-cf7' ),
						'msg_phone_country'    => __( 'Country code needed', 'lead-guard-cf7' ),
						'msg_email'            => __( 'Invalid email', 'lead-guard-cf7' ),
						'msg_email_disposable' => __( 'Temporary inbox', 'lead-guard-cf7' ),
						'msg_email_domain'     => __( 'Domain without mail', 'lead-guard-cf7' ),
						'msg_email_free'       => __( 'Free provider', 'lead-guard-cf7' ),
						'msg_duplicate'        => __( 'Repeat submission', 'lead-guard-cf7' ),
					);
					foreach ( $labels as $k => $l ) {
						echo $row( $l, $txt( $k ) ); // phpcs:ignore
					}
					?>
				</table>
				<?php submit_button(); ?>
			</form>
			<h2><?php esc_html_e( 'Per-form settings', 'lead-guard-cf7' ); ?></h2>
			<p><?php esc_html_e( 'Add these lines to a form\'s Additional Settings tab:', 'lead-guard-cf7' ); ?></p>
			<p><code>lead_guard: off</code> &nbsp; <code>lead_guard_duplicates: off</code> &nbsp; <code>lead_guard_required_your-name: Enter your name.</code></p>
		</div>
		<?php
	}
}
