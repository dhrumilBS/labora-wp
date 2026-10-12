<?php
/**
 * Shortcodes for policy pages and footers. Links can also be plain menu items:
 * a custom link to "#tlc-preferences" opens the preferences, "#tlc-dnss" opens Do Not Sell or Share.
 *
 *   [tlc_preferences text="Cookie settings" class=""]   button that opens the preferences center (with class: the
 *                                                      theme's styling, e.g. class="btn"; without: a text link)
 *   [tlc_dnss_link text="" class=""]                   Do Not Sell or Share link (shown only where it applies)
 *   [tlc_cookie_table category=""]                     the cookies of every category (or one), for a cookie policy
 *   [tlc_cookie_table layout="summary" class=""]       one table, one row per category: type, purpose, cookies
 *       (labels: type_label, purpose_label, cookies_label, none_label; class goes on the wrapper)
 *   [tlc_consent_details]                              the visitor's consent ID, date, and choices
 *
 * @package TrustLayer_Consent
 */

defined( 'ABSPATH' ) || exit;

final class TLC_Shortcodes {

	public static function init(): void {
		add_shortcode( 'tlc_preferences', array( __CLASS__, 'preferences' ) );
		add_shortcode( 'tlc_dnss_link', array( __CLASS__, 'dnss' ) );
		add_shortcode( 'tlc_cookie_table', array( __CLASS__, 'cookie_table' ) );
		add_shortcode( 'tlc_consent_details', array( __CLASS__, 'details' ) );
	}

	public static function preferences( $atts ): string {
		$a = shortcode_atts( array( 'text' => tlc_setting( 'texts.reopen' ), 'class' => '' ), $atts, 'tlc_preferences' );
		$class = trim( (string) $a['class'] ) ?: 'tlc-link'; // tlc-link: plain text-link look; a class of your own replaces it
		return sprintf( '<button type="button" class="%s" data-tlc-open>%s</button>', esc_attr( $class ), esc_html( $a['text'] ) );
	}

	public static function dnss( $atts ): string {
		$a = shortcode_atts( array( 'text' => tlc_setting( 'texts.dnss_link' ), 'class' => '' ), $atts, 'tlc_dnss_link' );
		return sprintf( '<a href="#tlc-dnss" class="tlc-link %s" data-tlc-dnss>%s</a>', esc_attr( $a['class'] ), esc_html( $a['text'] ) );
	}

	public static function cookie_table( $atts ): string {
		$a     = shortcode_atts( array(
			'category'      => '',
			'layout'        => '',
			'class'         => '',
			'type_label'    => __( 'Type', 'trustlayer-consent' ),
			'purpose_label' => __( 'Purpose', 'trustlayer-consent' ),
			'cookies_label' => __( 'Cookies', 'trustlayer-consent' ),
			'none_label'    => __( 'None', 'trustlayer-consent' ),
		), $atts, 'tlc_cookie_table' );
		$texts = (array) tlc_setting( 'texts', array() );
		if ( 'summary' === $a['layout'] ) {
			return self::cookie_summary( $a );
		}
		$html  = '';
		foreach ( TLC_Settings::categories() as $cat ) {
			if ( $a['category'] && $a['category'] !== $cat['id'] ) {
				continue;
			}
			$cookies      = (array) $cat['cookies'];
			$has_provider = (bool) array_filter( array_column( $cookies, 'provider' ) );
			$html        .= '<section class="tlc-table-section">';
			if ( ! $a['category'] ) {
				$html .= '<h3>' . esc_html( $cat['title'] ) . '</h3>';
			}
			$html .= '<p>' . wp_kses( $cat['description'], TLC_Settings::allowed_html() ) . '</p>';
			if ( $cookies ) {
				$html .= '<div class="tlc-table-wrap"><table class="tlc-table"><thead><tr><th scope="col">' . esc_html( $texts['col_cookie'] ) . '</th>'
					. ( $has_provider ? '<th scope="col">' . esc_html( $texts['col_provider'] ) . '</th>' : '' )
					. '<th scope="col">' . esc_html( $texts['col_purpose'] ) . '</th><th scope="col">' . esc_html( $texts['col_duration'] ) . '</th></tr></thead><tbody>';
				foreach ( $cookies as $c ) {
					$html .= '<tr><td><code>' . esc_html( $c['name'] ) . '</code></td>'
						. ( $has_provider ? '<td>' . esc_html( $c['provider'] ) . '</td>' : '' )
						. '<td>' . esc_html( $c['purpose'] ) . '</td><td>' . esc_html( $c['duration'] ) . '</td></tr>';
				}
				$html .= '</tbody></table></div>';
			} else {
				$html .= '<p class="tlc-table-empty">' . esc_html( str_replace( '{category}', strtolower( $cat['title'] ), (string) $texts['nothing_stored'] ) ) . '</p>';
			}
			$html .= '</section>';
		}
		return '<div class="tlc-cookie-table">' . $html . '</div>';
	}

	/** One table, one row per category: name, description, and its cookies with how long each is kept. */
	private static function cookie_summary( array $a ): string {
		$rows = '';
		foreach ( TLC_Settings::categories() as $cat ) {
			$cookies = array_map( function ( $c ) {
				return '<code>' . esc_html( $c['name'] ) . '</code>' . ( '' !== trim( (string) $c['duration'] ) ? ' (' . esc_html( $c['duration'] ) . ')' : '' );
			}, (array) $cat['cookies'] );
			$rows .= '<tr><td>' . esc_html( $cat['title'] ) . '</td><td>' . wp_kses( $cat['description'], TLC_Settings::allowed_html() ) . '</td><td>' . ( $cookies ? implode( ', ', $cookies ) : esc_html( $a['none_label'] ) ) . '</td></tr>';
		}
		return sprintf(
			'<div class="%1$s"><table><thead><tr><th scope="col">%2$s</th><th scope="col">%3$s</th><th scope="col">%4$s</th></tr></thead><tbody>%5$s</tbody></table></div>',
			esc_attr( trim( 'tlc-cookie-summary ' . $a['class'] ) ),
			esc_html( $a['type_label'] ),
			esc_html( $a['purpose_label'] ),
			esc_html( $a['cookies_label'] ),
			$rows
		);
	}

	/** Filled in by consent.js from the visitor's cookie (pages stay cacheable). */
	public static function details(): string {
		return '<dl class="tlc-details" data-tlc-details>'
			. '<div><dt>' . esc_html__( 'Consent ID', 'trustlayer-consent' ) . '</dt><dd data-tlc-detail="id">' . esc_html__( 'No choice saved yet', 'trustlayer-consent' ) . '</dd></div>'
			. '<div><dt>' . esc_html__( 'Saved', 'trustlayer-consent' ) . '</dt><dd data-tlc-detail="date">-</dd></div>'
			. '<div><dt>' . esc_html__( 'Allowed', 'trustlayer-consent' ) . '</dt><dd data-tlc-detail="allowed">-</dd></div>'
			. '</dl>';
	}
}
