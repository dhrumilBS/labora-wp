<?php
/**
 * Contact Form 7 editor: a "Lead Guard" reference under the Additional Settings box, listing every line this plugin
 * reads, with examples, so editors can set each form up without reading code.
 *
 * @package Lead_Guard_CF7
 */

defined( 'ABSPATH' ) || exit;

final class Lead_Guard_Editor {

	/** @var callable CF7's own Additional Settings panel */
	private static $original = null;

	public static function init(): void {
		add_filter( 'wpcf7_editor_panels', array( __CLASS__, 'panels' ), 20 );
	}

	public static function panels( $panels ) {
		if ( isset( $panels['additional-settings-panel']['callback'] ) ) {
			self::$original = $panels['additional-settings-panel']['callback'];
			$panels['additional-settings-panel']['callback'] = array( __CLASS__, 'render' );
		}
		return $panels;
	}

	/** CF7's panel, then the reference. */
	public static function render( $post ): void {
		if ( is_callable( self::$original ) ) {
			call_user_func( self::$original, $post );
		}
		$fields = array();
		if ( $post instanceof WPCF7_ContactForm ) {
			foreach ( $post->scan_form_tags() as $tag ) {
				if ( $tag->name && $tag->is_required() ) {
					$fields[] = $tag->name;
				}
			}
		}
		$example = $fields ? $fields[0] : 'your-name';
		$rows    = array(
			array( 'Turn checks off', 'lead_guard: off', __( 'Turn every Lead Guard check off for this form (phone, email, repeats, honeypot).', 'lead-guard-cf7' ) ),
			array( 'Allow repeats', 'lead_guard_duplicates: off', __( 'Let the same person submit this form again within the repeat window. The other checks stay on.', 'lead-guard-cf7' ) ),
			array( 'Required-field message', "lead_guard_required_{$example}: Enter your name.", __( 'Message shown when that required field is empty, instead of the general "Please fill out this field." One line per field; use the field name from the Form tab.', 'lead-guard-cf7' ) ),
			array( 'Thank-you page', 'lead_guard_redirect: /thank-you/', __( 'After a successful send, open this page. A path on this site or a full URL. The page can read the visitor\'s first name, the form, and the topic from sessionStorage "lead_guard_sent".', 'lead-guard-cf7' ) ),
			array( 'Redirect delay', 'lead_guard_delay: 1500', __( 'Milliseconds to wait before the redirect, so the confirmation is seen first. Default 1500; 0 redirects at once.', 'lead-guard-cf7' ) ),
			array( 'Analytics event', 'lead_guard_event: demo_request_submitted', __( 'After a successful send, push this event to window.dataLayer (Google Tag Manager), with the form name and topic.', 'lead-guard-cf7' ) ),
			array( 'No email (CF7)', 'skip_mail: on', __( 'Contact Form 7\'s own setting: validate and store the submission, but send no email. Remove the line to send mail.', 'lead-guard-cf7' ) ),
		);
		?>
		<div class="lead-guard-docs" style="margin-top:2em;padding:1em 1.25em;border:1px solid #dcdcde;border-left:4px solid #2271b1;background:#fff;max-width:960px">
			<h3 style="margin-top:0"><?php esc_html_e( 'Lead Guard settings for this form', 'lead-guard-cf7' ); ?></h3>
			<p><?php esc_html_e( 'Add any of these lines to the box above, one per line, as "name: value". Lines you do not add use the site-wide settings.', 'lead-guard-cf7' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Lead_Guard_Settings::PAGE ) ); ?>"><?php esc_html_e( 'Site-wide settings and messages', 'lead-guard-cf7' ); ?></a></p>
			<table class="widefat striped" style="max-width:960px">
				<thead><tr><th style="width:18%"><?php esc_html_e( 'What', 'lead-guard-cf7' ); ?></th><th style="width:34%"><?php esc_html_e( 'Line to add', 'lead-guard-cf7' ); ?></th><th><?php esc_html_e( 'Effect', 'lead-guard-cf7' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $r ) : ?>
					<tr><td><?php echo esc_html( $r[0] ); ?></td><td><code><?php echo esc_html( $r[1] ); ?></code></td><td><?php echo esc_html( $r[2] ); ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $fields ) : ?>
			<p><?php esc_html_e( 'Required fields in this form:', 'lead-guard-cf7' ); ?> <?php echo implode( ' ', array_map( fn( $f ) => '<code>' . esc_html( $f ) . '</code>', $fields ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			<?php endif; ?>
			<p><?php esc_html_e( 'Always on: phone ([tel]) and email ([email]) fields are checked, a hidden honeypot field is added, and the form address has no "#wpcf7-..." part. Messages for every check are set on the Lead Guard settings page; Contact Form 7\'s own messages (sent, spam, validation, invalid email or phone ...) are on the Messages tab.', 'lead-guard-cf7' ); ?></p>
			<p><?php esc_html_e( 'Theme hook: give an element around the form data-lead-guard-wrap, and a hidden confirmation inside it data-lead-guard-status. After a send, the wrapper gets the class "is-sent" and the confirmation is shown.', 'lead-guard-cf7' ); ?></p>
		</div>
		<?php
	}
}
