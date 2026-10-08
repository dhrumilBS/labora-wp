<?php
/**
 * Search form.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;
$labora_id = wp_unique_id( 'search-' );
?>
<form role="search" method="get" class="wp-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
  <label class="sr-only" for="<?php echo esc_attr( $labora_id ); ?>"><?php esc_html_e( 'Search articles', 'labora' ); ?></label>
  <input type="search" id="<?php echo esc_attr( $labora_id ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search articles', 'labora' ); ?>">
  <button class="btn btn--primary" type="submit"><?php esc_html_e( 'Search', 'labora' ); ?></button>
</form>
