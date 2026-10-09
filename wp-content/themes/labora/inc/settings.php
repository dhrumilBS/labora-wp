<?php
/**
 * Labora Settings (admin menu "Labora Settings"): details used across the whole site, edited in one place.
 *
 *   Company        name, short description (footer), logo for search engines
 *   Contact        contact, sales, and security email, phone, address
 *   Social         profile links (also sent to search engines as the organization's "sameAs")
 *   Header         Sign in, Book a demo, and Get started buttons
 *   Footer         copyright line
 *
 * The fields are Secure Custom Fields defined in code (versioned with the theme). Read a value with
 * labora_setting( 'email_contact' ); empty fields fall back to the defaults below, which match the HTML site,
 * so the site looks the same before anything is filled in, and also when SCF is not active.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

const LABORA_SETTINGS_SLUG = 'labora-settings';

if ( ! function_exists( 'labora_setting_defaults' ) ) :
/** Defaults for every setting. Filter: labora_setting_defaults. */
function labora_setting_defaults(): array {
	return (array) apply_filters( 'labora_setting_defaults', array(
		'company_name'    => 'Labora',
		'company_tagline' => 'Laboratory management software for pathology, imaging, home collection, and multi-center labs.',
		'company_logo'    => 0,
		'email_contact'   => (string) get_option( 'admin_email' ),
		'email_sales'     => '',
		'email_security'  => (string) get_option( 'admin_email' ),
		'phone'           => '',
		'address_street'  => '',
		'address_city'    => '',
		'address_region'  => '',
		'address_postal'  => '',
		'address_country' => '',
		'social'          => array(),
		'signin_label'    => 'Sign in',
		'signin_url'      => '/login/',
		'demo_label'      => 'Book a demo',
		'demo_url'        => '/#demo',
		'start_label'     => 'Get started',
		'start_url'       => '/signup/',
		'copyright'       => '{company}. All rights reserved.',
	) );
}
endif;

if ( ! function_exists( 'labora_setting' ) ) :
/**
 * A site-wide setting, or its default when empty.
 *
 * @param string $name Field name, e.g. 'email_contact', 'social', 'demo_url'.
 */
function labora_setting( string $name ) {
	static $cache = array();
	if ( array_key_exists( $name, $cache ) ) {
		return $cache[ $name ];
	}
	$defaults = labora_setting_defaults();
	$value    = function_exists( 'get_field' ) ? get_field( $name, 'option' ) : null;
	if ( null === $value || false === $value || '' === $value || array() === $value ) {
		$value = $defaults[ $name ] ?? '';
	}
	return $cache[ $name ] = $value;
}
endif;

if ( ! function_exists( 'labora_setting_url' ) ) :
/**
 * A link setting as a full URL. Paths on this site ("/signup/", "/#demo") become absolute; on the homepage,
 * "/#section" becomes "#section" so it scrolls instead of reloading. Full URLs are kept as they are.
 */
function labora_setting_url( string $name ): string {
	$value = trim( (string) labora_setting( $name ) );
	if ( '' === $value || preg_match( '#^(https?:|mailto:|tel:)#i', $value ) ) {
		return $value;
	}
	if ( is_front_page() && str_starts_with( $value, '/#' ) ) {
		return substr( $value, 1 );
	}
	return home_url( '/' . ltrim( $value, '/' ) );
}
endif;

/** Social networks offered in the Social tab: value => label. */
function labora_social_networks(): array {
	return array(
		'linkedin'  => 'LinkedIn',
		'x'         => 'X (Twitter)',
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'youtube'   => 'YouTube',
		'github'    => 'GitHub',
		'other'     => 'Other',
	);
}

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}
	acf_add_options_page( array(
		'page_title' => __( 'Labora Settings', 'labora' ),
		'menu_title' => __( 'Labora Settings', 'labora' ),
		'menu_slug'  => LABORA_SETTINGS_SLUG,
		'capability' => 'manage_options',
		'icon_url'   => 'dashicons-admin-site-alt3',
		'position'   => 59,
		'redirect'   => false,
	) );

	$d   = labora_setting_defaults();
	$tab = fn( $key, $label ) => array( 'key' => "field_labora_tab_{$key}", 'label' => $label, 'name' => '', 'type' => 'tab', 'placement' => 'top' );
	$txt = fn( $name, $label, $help = '', $type = 'text' ) => array(
		'key'          => "field_labora_{$name}",
		'label'        => $label,
		'name'         => $name,
		'type'         => $type,
		'instructions' => $help,
		'placeholder'  => is_string( $d[ $name ] ?? null ) ? $d[ $name ] : '',
	);

	acf_add_local_field_group( array(
		'key'      => 'group_labora_settings',
		'title'    => __( 'Labora Settings', 'labora' ),
		'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => LABORA_SETTINGS_SLUG ) ) ),
		'style'    => 'seamless',
		'fields'   => array(
			$tab( 'company', __( 'Company', 'labora' ) ),
			$txt( 'company_name', __( 'Company name', 'labora' ), __( 'Shown in the footer copyright and sent to search engines.', 'labora' ) ),
			$txt( 'company_tagline', __( 'Short description', 'labora' ), __( 'The line under the logo in the footer.', 'labora' ), 'textarea' ) + array( 'rows' => 2 ),
			array(
				'key'           => 'field_labora_company_logo',
				'label'         => __( 'Logo for search engines', 'labora' ),
				'name'          => 'company_logo',
				'type'          => 'image',
				'return_format' => 'id',
				'preview_size'  => 'thumbnail',
				'instructions'  => __( 'A square PNG or JPG, at least 112 x 112 pixels. Used in structured data when Yoast SEO has no logo set (Yoast SEO > Settings > Site representation).', 'labora' ),
			),

			$tab( 'contact', __( 'Contact', 'labora' ) ),
			$txt( 'email_contact', __( 'Contact email', 'labora' ), __( 'Shown on the thank-you page ("Replies from") and the contact page, and sent to search engines.', 'labora' ), 'email' ),
			$txt( 'email_sales', __( 'Sales email', 'labora' ), __( 'Optional. Leave empty to use the contact email.', 'labora' ), 'email' ),
			$txt( 'email_security', __( 'Security email', 'labora' ), __( 'For security reports, shown on the Trust Center.', 'labora' ), 'email' ),
			$txt( 'phone', __( 'Phone', 'labora' ), __( 'Optional, in international format, e.g. +1 512 555 0123.', 'labora' ) ),
			$txt( 'address_street', __( 'Street address', 'labora' ), __( 'Optional. The address is sent to search engines when the city and country are filled in.', 'labora' ) ),
			$txt( 'address_city', __( 'City', 'labora' ) ),
			$txt( 'address_region', __( 'State or region', 'labora' ) ),
			$txt( 'address_postal', __( 'Postal code', 'labora' ) ),
			$txt( 'address_country', __( 'Country', 'labora' ), __( 'Two-letter code, e.g. US.', 'labora' ) ),

			$tab( 'social', __( 'Social', 'labora' ) ),
			array(
				'key'          => 'field_labora_social',
				'label'        => __( 'Social profiles', 'labora' ),
				'name'         => 'social',
				'type'         => 'repeater',
				'layout'       => 'table',
				'button_label' => __( 'Add a profile', 'labora' ),
				'instructions' => __( 'The company\'s own profiles. Sent to search engines as the organization\'s profiles (sameAs).', 'labora' ),
				'sub_fields'   => array(
					array( 'key' => 'field_labora_social_network', 'label' => __( 'Network', 'labora' ), 'name' => 'network', 'type' => 'select', 'choices' => labora_social_networks(), 'default_value' => 'linkedin' ),
					array( 'key' => 'field_labora_social_url', 'label' => __( 'Profile URL', 'labora' ), 'name' => 'url', 'type' => 'url', 'required' => 1 ),
				),
			),

			$tab( 'header', __( 'Header buttons', 'labora' ) ),
			array( 'key' => 'field_labora_header_note', 'label' => '', 'name' => '', 'type' => 'message', 'message' => __( 'Links can be a path on this site (/signup/, /#demo) or a full URL. The menu itself is edited in Appearance > Menus.', 'labora' ) ),
			$txt( 'signin_label', __( 'Sign in: text', 'labora' ) ),
			$txt( 'signin_url', __( 'Sign in: link', 'labora' ) ),
			$txt( 'demo_label', __( 'Book a demo: text', 'labora' ) ),
			$txt( 'demo_url', __( 'Book a demo: link', 'labora' ), __( 'Also used by the "Book a demo" links in blog posts and on product pages.', 'labora' ) ),
			$txt( 'start_label', __( 'Get started: text', 'labora' ) ),
			$txt( 'start_url', __( 'Get started: link', 'labora' ) ),

			$tab( 'footer', __( 'Footer', 'labora' ) ),
			array( 'key' => 'field_labora_footer_note', 'label' => '', 'name' => '', 'type' => 'message', 'message' => __( 'Footer link columns are widgets: Appearance > Widgets > Footer columns. Each "Labora: Menu column" widget shows one menu. The bottom links are the "Footer: bottom links (Legal)" menu.', 'labora' ) ),
			$txt( 'copyright', __( 'Copyright line', 'labora' ), __( 'After "© year". {company} is replaced with the company name.', 'labora' ) ),
		),
	) );
} );

/* ---------- Search engines: the organization's contact details and profiles ---------- */

add_filter( 'wpseo_schema_organization', function ( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}
	$same = array_values( array_filter( array_map( fn( $row ) => esc_url_raw( (string) ( $row['url'] ?? '' ) ), (array) labora_setting( 'social' ) ) ) );
	if ( $same ) {
		$data['sameAs'] = array_values( array_unique( array_merge( (array) ( $data['sameAs'] ?? array() ), $same ) ) );
	}
	$email = sanitize_email( (string) labora_setting( 'email_contact' ) );
	$phone = trim( (string) labora_setting( 'phone' ) );
	if ( $email || $phone ) {
		$point = array( '@type' => 'ContactPoint', 'contactType' => 'customer support' );
		if ( $email ) {
			$point['email'] = $email;
		}
		if ( $phone ) {
			$point['telephone'] = $phone;
		}
		$data['contactPoint'] = array( $point );
	}
	if ( labora_setting( 'address_city' ) && labora_setting( 'address_country' ) ) {
		$data['address'] = array_filter( array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => (string) labora_setting( 'address_street' ),
			'addressLocality' => (string) labora_setting( 'address_city' ),
			'addressRegion'   => (string) labora_setting( 'address_region' ),
			'postalCode'      => (string) labora_setting( 'address_postal' ),
			'addressCountry'  => strtoupper( (string) labora_setting( 'address_country' ) ),
		) );
	}
	// Logo from Labora Settings when Yoast has none
	$logo = (int) labora_setting( 'company_logo' );
	if ( empty( $data['logo'] ) && $logo && ( $src = wp_get_attachment_image_src( $logo, 'full' ) ) ) {
		$data['logo']  = array( '@type' => 'ImageObject', '@id' => home_url( '/#/schema/logo/image/' ), 'url' => $src[0], 'contentUrl' => $src[0], 'width' => $src[1], 'height' => $src[2], 'caption' => (string) labora_setting( 'company_name' ) );
		$data['image'] = array( '@id' => home_url( '/#/schema/logo/image/' ) );
	}
	return $data;
} );

// The thank-you page's "Replies from" address
add_filter( 'labora_reply_email', fn() => (string) labora_setting( 'email_contact' ) );
