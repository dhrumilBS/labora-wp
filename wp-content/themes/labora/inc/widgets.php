<?php
/**
 * Footer widgets (Appearance > Widgets > "Footer columns").
 *
 * Each "Labora: Menu column" widget is one footer link column: a heading and a menu chosen from
 * Appearance > Menus. Add, remove, rename, or reorder columns there; the markup is the HTML site's
 * <nav class="footer-col"><h2>...</h2><ul>...</ul></nav>, so any number of columns keeps the footer design.
 * The classic widgets screen is used (no block widgets), because these widgets are simple forms.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

const LABORA_FOOTER_SIDEBAR = 'footer-columns';

// Simple form-based widgets: the classic Widgets screen instead of the block editor
add_filter( 'use_widgets_block_editor', '__return_false' );

add_action( 'widgets_init', function () {
	register_sidebar( array(
		'id'            => LABORA_FOOTER_SIDEBAR,
		'name'          => __( 'Footer columns', 'labora' ),
		'description'   => __( 'The link columns in the footer. Add a "Labora: Menu column" widget for each column and choose its menu.', 'labora' ),
		'before_widget' => '',
		'after_widget'  => '',
		'before_title'  => '',
		'after_title'   => '',
	) );
	register_widget( 'Labora_Menu_Column_Widget' );
} );

if ( ! class_exists( 'Labora_Menu_Column_Widget' ) ) :
/** A footer link column: heading + menu. */
class Labora_Menu_Column_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct( 'labora_menu_column', __( 'Labora: Menu column', 'labora' ), array(
			'classname'   => 'labora-menu-column',
			'description' => __( 'A footer link column: a heading and one menu.', 'labora' ),
		) );
	}

	/** Same markup as the HTML site's footer columns. */
	public function widget( $args, $instance ) {
		$menu  = ! empty( $instance['menu'] ) ? wp_get_nav_menu_object( (int) $instance['menu'] ) : false;
		$items = $menu ? wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) ) : array();
		if ( ! $items ) {
			return;
		}
		_wp_menu_item_classes_by_context( $items ); // marks the current page's item
		$title = '' !== trim( (string) ( $instance['title'] ?? '' ) ) ? (string) $instance['title'] : $menu->name;
		printf( '      <nav class="footer-col" aria-label="%1$s"><h2>%2$s</h2><ul>', esc_attr( $title ), esc_html( $title ) );
		foreach ( $items as $item ) {
			if ( (int) $item->menu_item_parent ) {
				continue; // footer columns are one level
			}
			printf( '<li><a href="%s"%s>%s</a></li>', esc_url( labora_menu_url( $item ) ), labora_menu_current( $item ), esc_html( $item->title ) );
		}
		echo "</ul></nav>\n";
	}

	public function form( $instance ) {
		$title = (string) ( $instance['title'] ?? '' );
		$menu  = (int) ( $instance['menu'] ?? 0 );
		$menus = wp_get_nav_menus();
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Heading', 'labora' ); ?></label>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>">
			<small><?php esc_html_e( 'Leave empty to use the menu\'s name.', 'labora' ); ?></small>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'menu' ) ); ?>"><?php esc_html_e( 'Menu', 'labora' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'menu' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'menu' ) ); ?>">
				<option value="0"><?php esc_html_e( '(choose a menu)', 'labora' ); ?></option>
				<?php foreach ( $menus as $m ) : ?>
					<option value="<?php echo esc_attr( (string) $m->term_id ); ?>" <?php selected( $menu, (int) $m->term_id ); ?>><?php echo esc_html( $m->name ); ?></option>
				<?php endforeach; ?>
			</select>
			<small><?php printf( wp_kses( __( 'Create or edit menus in <a href="%s">Appearance &gt; Menus</a>. Only top-level items are shown.', 'labora' ), array( 'a' => array( 'href' => true ) ) ), esc_url( admin_url( 'nav-menus.php' ) ) ); ?></small>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'title' => sanitize_text_field( (string) ( $new_instance['title'] ?? '' ) ),
			'menu'  => absint( $new_instance['menu'] ?? 0 ),
		);
	}
}
endif;

if ( ! function_exists( 'labora_footer_columns' ) ) :
/** The footer link columns (the "Footer columns" widgets). */
function labora_footer_columns(): void {
	if ( is_active_sidebar( LABORA_FOOTER_SIDEBAR ) ) {
		dynamic_sidebar( LABORA_FOOTER_SIDEBAR );
	}
}
endif;

/**
 * Put one "Labora: Menu column" widget per menu into "Footer columns" (used by wp labora seed-menus).
 *
 * @param array $columns [ [ heading, menu term ID ], ... ] in order.
 */
function labora_seed_footer_widgets( array $columns ): void {
	$instances = array( '_multiwidget' => 1 );
	$ids       = array();
	foreach ( array_values( $columns ) as $i => list( $title, $menu_id ) ) {
		$instances[ $i + 1 ] = array( 'title' => $title, 'menu' => (int) $menu_id );
		$ids[]               = 'labora_menu_column-' . ( $i + 1 );
	}
	update_option( 'widget_labora_menu_column', $instances );
	$sidebars                          = get_option( 'sidebars_widgets', array() );
	$sidebars[ LABORA_FOOTER_SIDEBAR ] = $ids;
	update_option( 'sidebars_widgets', $sidebars );
}
