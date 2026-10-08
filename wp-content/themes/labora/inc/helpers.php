<?php
/**
 * Template helpers.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

/**
 * An icon from the site's SVG sprite (printed once in the header), same markup as the HTML site:
 * <svg class="icon" aria-hidden="true"><use href="#i-check"/></svg>
 */
function labora_icon( string $name, string $class = 'icon' ): string {
	return sprintf( '<svg class="%s" aria-hidden="true"><use href="#i-%s"/></svg>', esc_attr( $class ), esc_attr( $name ) );
}
