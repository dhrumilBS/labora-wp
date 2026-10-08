<?php
/**
 * Navigation: menu locations and the markup for the header, mobile menu, and footer menus.
 *
 * Editing (Appearance > Menus):
 *  - "Primary" drives both the desktop header and the mobile menu. A top-level item with children becomes a
 *    dropdown. In a dropdown, each item's Description (enable it under Screen Options) is the line under its title,
 *    and its icon is set in CSS Classes as icon-<name>, e.g. icon-flask (names: see template-parts/sprite.php).
 *    A dropdown whose items have no descriptions renders in the narrow style (like Solutions).
 *  - Footer columns: one menu per location. The column heading is the menu's name.
 *  - Links to homepage sections (https://site/#demo) become plain #demo links on the homepage itself.
 * Buttons, the logo, and the footer text are fixed in header.php / footer.php.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	register_nav_menus( array(
		'primary'          => __( 'Primary (header and mobile menu)', 'labora' ),
		'footer_platform'  => __( 'Footer: column 1 (Platform)', 'labora' ),
		'footer_solutions' => __( 'Footer: column 2 (Solutions)', 'labora' ),
		'footer_resources' => __( 'Footer: column 3 (Resources)', 'labora' ),
		'footer_company'   => __( 'Footer: column 4 (Company)', 'labora' ),
		'footer_legal'     => __( 'Footer: bottom links (Legal)', 'labora' ),
	) );
} );

/**
 * Menu items for a location as a tree: [ ['item' => WP_Post, 'children' => [...]], ... ]. Empty if unassigned.
 */
function labora_menu_tree( string $location ): array {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return array();
	}
	$items = wp_get_nav_menu_items( $locations[ $location ], array( 'update_post_term_cache' => false ) );
	if ( ! $items ) {
		return array();
	}
	_wp_menu_item_classes_by_context( $items ); // adds current-menu-item etc.
	$by_parent = array();
	foreach ( $items as $item ) {
		$by_parent[ (int) $item->menu_item_parent ][] = $item;
	}
	$build = function ( int $parent ) use ( &$build, $by_parent ) {
		$out = array();
		foreach ( $by_parent[ $parent ] ?? array() as $item ) {
			$out[] = array( 'item' => $item, 'children' => $build( (int) $item->ID ) );
		}
		return $out;
	};
	return $build( 0 );
}

/** Name of the menu assigned to a location (used as the footer column heading). */
function labora_menu_name( string $location ): string {
	$locations = get_nav_menu_locations();
	$menu      = empty( $locations[ $location ] ) ? null : wp_get_nav_menu_object( $locations[ $location ] );
	return $menu ? $menu->name : '';
}

/** Link target for a menu item: homepage-section links become "#section" on the homepage. */
function labora_menu_url( WP_Post $item ): string {
	$url = (string) $item->url;
	if ( is_front_page() ) {
		$home = trailingslashit( home_url( '/' ) );
		if ( str_starts_with( $url, $home . '#' ) ) {
			return substr( $url, strlen( $home ) );
		}
	}
	return $url;
}

/** Icon name from an item's CSS classes (icon-flask -> flask), or ''. */
function labora_menu_icon( WP_Post $item ): string {
	foreach ( (array) $item->classes as $class ) {
		if ( str_starts_with( (string) $class, 'icon-' ) ) {
			return substr( $class, 5 );
		}
	}
	return '';
}

/** aria-current="page" for the current page's item. */
function labora_menu_current( WP_Post $item ): string {
	return in_array( 'current-menu-item', (array) $item->classes, true ) ? ' aria-current="page"' : '';
}

/** Desktop navigation, same markup as the HTML site. */
function labora_primary_nav(): void {
	$tree = labora_menu_tree( 'primary' );
	if ( ! $tree ) {
		return;
	}
	echo '<nav class="nav" aria-label="' . esc_attr__( 'Primary', 'labora' ) . '">' . "\n";
	foreach ( $tree as $node ) {
		$item = $node['item'];
		if ( $node['children'] ) {
			$id     = 'dd-' . sanitize_title( $item->title );
			$narrow = ! array_filter( $node['children'], fn( $c ) => '' !== trim( (string) $c['item']->description ) );
			printf(
				'      <div class="nav-item" data-dropdown>' . "\n" .
				'        <button class="nav-link" type="button" aria-expanded="false" aria-controls="%1$s">%2$s %3$s</button>' . "\n" .
				'        <div class="dropdown%4$s" id="%1$s">',
				esc_attr( $id ),
				esc_html( $item->title ),
				labora_icon( 'chev', 'icon chev' ),
				$narrow ? ' dropdown--narrow' : ''
			);
			foreach ( $node['children'] as $child ) {
				$c    = $child['item'];
				$desc = trim( (string) $c->description );
				$ic   = labora_menu_icon( $c );
				printf(
					'<a href="%1$s"%2$s>%3$s<span><strong>%4$s</strong>%5$s</span></a>',
					esc_url( labora_menu_url( $c ) ),
					labora_menu_current( $c ),
					$ic ? '<span class="dd-icon">' . labora_icon( $ic ) . '</span>' : '',
					esc_html( $c->title ),
					'' !== $desc ? '<span>' . esc_html( $desc ) . '</span>' : ''
				);
			}
			echo "</div>\n      </div>\n";
		} else {
			printf(
				'      <div class="nav-item"><a class="nav-link" href="%s"%s>%s</a></div>' . "\n",
				esc_url( labora_menu_url( $item ) ),
				labora_menu_current( $item ),
				esc_html( $item->title )
			);
		}
	}
	echo "    </nav>\n";
}

/** Mobile menu links (the panel and its buttons are in header.php). */
function labora_mobile_nav_items(): void {
	foreach ( labora_menu_tree( 'primary' ) as $node ) {
		$item = $node['item'];
		if ( $node['children'] ) {
			printf( '  <details><summary>%s %s</summary><div class="m-sub">', esc_html( $item->title ), labora_icon( 'chev', 'icon chev' ) );
			foreach ( $node['children'] as $child ) {
				printf( '<a href="%s"%s>%s</a>', esc_url( labora_menu_url( $child['item'] ) ), labora_menu_current( $child['item'] ), esc_html( $child['item']->title ) );
			}
			echo "</div></details>\n";
		} else {
			printf( '  <a class="m-link" href="%s"%s>%s</a>' . "\n", esc_url( labora_menu_url( $item ) ), labora_menu_current( $item ), esc_html( $item->title ) );
		}
	}
}

/** A footer column: <nav class="footer-col"><h2>Menu name</h2><ul>...</ul></nav>. */
function labora_footer_col( string $location ): void {
	$tree = labora_menu_tree( $location );
	if ( ! $tree ) {
		return;
	}
	$name = labora_menu_name( $location );
	printf( '      <nav class="footer-col" aria-label="%1$s"><h2>%2$s</h2><ul>', esc_attr( $name ), esc_html( $name ) );
	foreach ( $tree as $node ) {
		printf( '<li><a href="%s"%s>%s</a></li>', esc_url( labora_menu_url( $node['item'] ) ), labora_menu_current( $node['item'] ), esc_html( $node['item']->title ) );
	}
	echo "</ul></nav>\n";
}

/** Bottom-row links (Privacy, Terms, Cookies) as <li> items. */
function labora_footer_legal_items(): void {
	foreach ( labora_menu_tree( 'footer_legal' ) as $node ) {
		printf( '<li><a href="%s"%s>%s</a></li>', esc_url( labora_menu_url( $node['item'] ) ), labora_menu_current( $node['item'] ), esc_html( $node['item']->title ) );
	}
}
