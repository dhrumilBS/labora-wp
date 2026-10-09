<?php
/**
 * WP-CLI commands for setting up the site content the theme expects.
 *
 *   wp labora seed-menus [--force]   Create the header and footer menus from the HTML site, assign the header and
 *                                    bottom-links menus, and put the four footer columns into the "Footer columns"
 *                                    widget area (one "Labora: Menu column" widget each, if the area is empty).
 *                                    Existing menus with the same names are left alone unless --force.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Menus from the HTML site. Paths are relative to the site root; '#x' links point to sections of the homepage.
 * Item: [ title, path, description, icon ] or [ title, path, description, icon, children ].
 */
function labora_cli_menu_data(): array {
	$platform = array(
		array( 'Pathology lab', 'platform/pathology-lab/', 'Tests, results, and validation', 'flask' ),
		array( 'Sample tracking', 'platform/sample-tracking/', 'Barcodes, racks, and rejections', 'barcode' ),
		array( 'Turnaround (TAT)', 'platform/turnaround-tracking/', 'Live targets and alerts', 'clock' ),
		array( 'Radiology reporting', 'platform/radiology-reporting/', 'USG, X-ray, CT, and MRI', 'scan' ),
		array( 'ECG & cardiology', 'platform/ecg-cardiology/', 'ECG, 2D echo, and treadmill', 'heart' ),
		array( 'Home collection', 'platform/home-collection/', 'Bookings, live ETA, patient SMS', 'pin' ),
		array( 'Centers & outsource labs', 'platform/centers/', 'Branches, partners, and B2B', 'building' ),
		array( 'Reports & e-signature', 'platform/reports-e-signature/', 'Sign and send in one click', 'edit' ),
		array( 'Business insights', 'platform/business-insights/', 'Revenue, dues, and referrals', 'chart' ),
	);
	$solutions = array(
		array( 'Pathology labs', 'solutions/pathology-labs/', '', 'flask' ),
		array( 'Diagnostic & imaging centers', 'solutions/diagnostic-imaging-centers/', '', 'scan' ),
		array( 'Multi-center lab chains', 'solutions/multi-center-lab-chains/', '', 'building' ),
		array( 'Hospital laboratories', 'solutions/hospital-laboratories/', '', 'hospital' ),
		array( 'Home collection services', 'solutions/home-collection-services/', '', 'pin' ),
		array( 'Cardiology & ECG clinics', 'solutions/cardiology-clinics/', '', 'heart' ),
	);
	$plain = fn( array $items ) => array_map( fn( $i ) => array( $i[0], $i[1], '', '' ), $items );

	return array(
		'primary' => array( 'Primary', array(
			array( 'Platform', '#', '', '', $platform ),
			array( 'Solutions', '#', '', '', $solutions ),
			array( 'Features', '#home-collection', '', '' ),
			array( 'Resources', 'blog/', '', '' ),
			array( 'Pricing', 'pricing/', '', '' ),
		) ),
		// The footer lists the first eight platform pages (no Business insights), as on the HTML site
		'footer_platform'  => array( 'Platform', $plain( array_slice( $platform, 0, 8 ) ) ),
		'footer_solutions' => array( 'Solutions', $plain( $solutions ) ),
		'footer_resources' => array( 'Resources', array(
			array( 'Resource center', 'blog/', '', '' ),
			array( 'Blog', 'blog/', '', '' ),
			array( 'Guides', 'resources/guides/', '', '' ),
			array( 'API documentation', 'docs/api/', '', '' ),
			array( 'Security', 'security/', '', '' ),
			array( 'FAQ', 'faq/', '', '' ),
		) ),
		'footer_company' => array( 'Company', array(
			array( 'About', 'about/', '', '' ),
			array( 'Pricing', 'pricing/', '', '' ),
			array( 'Contact', 'contact/', '', '' ),
			array( 'Book a demo', '#demo', '', '' ),
		) ),
		'footer_legal' => array( 'Legal', array(
			array( 'Privacy', 'privacy/', '', '' ),
			array( 'Terms', 'terms/', '', '' ),
			array( 'Cookies', 'cookies/', '', '' ),
		) ),
	);
}

WP_CLI::add_command( 'labora seed-menus', function ( $args, $assoc ) {
	$force     = ! empty( $assoc['force'] );
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$columns   = array(); // footer columns: [ heading, menu ID ], shown by widgets

	foreach ( labora_cli_menu_data() as $location => list( $name, $items ) ) {
		$menu = wp_get_nav_menu_object( $name );
		if ( $menu && ! $force ) {
			WP_CLI::log( "Menu \"$name\" exists, kept (use --force to rebuild)." );
		} else {
			if ( $menu ) {
				wp_delete_nav_menu( $menu->term_id );
			}
			$menu_id = wp_create_nav_menu( $name );
			$add     = function ( array $list, int $parent ) use ( &$add, $menu_id ) {
				foreach ( $list as $pos => $i ) {
					list( $title, $path, $desc, $icon ) = $i;
					$url = '#' === $path ? '#' : home_url( '/' . ltrim( $path, '/' ) );
					$id  = wp_update_nav_menu_item( $menu_id, 0, array(
						'menu-item-title'       => $title,
						'menu-item-url'         => $url,
						'menu-item-description' => $desc,
						'menu-item-classes'     => $icon ? "icon-$icon" : '',
						'menu-item-parent-id'   => $parent,
						'menu-item-position'    => $pos + 1,
						'menu-item-type'        => 'custom',
						'menu-item-status'      => 'publish',
					) );
					if ( ! empty( $i[4] ) ) {
						$add( $i[4], (int) $id );
					}
				}
			};
			$add( $items, 0 );
			$menu = wp_get_nav_menu_object( $menu_id );
			WP_CLI::log( "Created menu \"$name\"." );
		}
		if ( in_array( $location, array( 'footer_platform', 'footer_solutions', 'footer_resources', 'footer_company' ), true ) ) {
			$columns[] = array( $name, $menu->term_id );
			unset( $locations[ $location ] ); // these were menu locations before the footer used widgets
		} else {
			$locations[ $location ] = $menu->term_id;
		}
	}
	set_theme_mod( 'nav_menu_locations', $locations );
	if ( $force || ! is_active_sidebar( LABORA_FOOTER_SIDEBAR ) ) {
		labora_seed_footer_widgets( $columns );
		WP_CLI::log( 'Footer columns: ' . count( $columns ) . ' "Labora: Menu column" widgets.' );
	} else {
		WP_CLI::log( 'Footer columns already have widgets, kept (use --force to replace them).' );
	}
	WP_CLI::success( 'Menus assigned.' );
} );
