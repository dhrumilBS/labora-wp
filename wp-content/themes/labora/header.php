<?php
/**
 * Site header and mobile menu, same markup as the HTML site.
 * Menu links come from Appearance > Menus ("Primary"); the logo and the buttons are fixed here.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

$labora_home = home_url( '/' );
// On the homepage the demo form is on the same page; elsewhere the buttons lead to it.
$labora_demo = is_front_page() ? '#demo' : home_url( '/#demo' );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="format-detection" content="telephone=no">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'labora' ); ?></a>

<?php get_template_part( 'template-parts/sprite' ); ?>

<header class="site-header" id="top">
  <div class="container header-inner">
    <a class="logo" href="<?php echo esc_url( $labora_home ); ?>" aria-label="<?php esc_attr_e( 'Labora home', 'labora' ); ?>"><svg class="logo-mark" aria-hidden="true"><use href="#logo-mark"/></svg>Labora</a>
    <?php labora_primary_nav(); ?>
    <div class="header-actions">
      <a class="signin" href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php esc_html_e( 'Sign in', 'labora' ); ?></a>
      <a class="btn btn--ghost btn--sm" href="<?php echo esc_url( $labora_demo ); ?>"><?php esc_html_e( 'Book a demo', 'labora' ); ?></a>
      <a class="btn btn--primary btn--sm" href="<?php echo esc_url( home_url( '/signup/' ) ); ?>"><?php esc_html_e( 'Get started', 'labora' ); ?></a>
    </div>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu" aria-label="<?php esc_attr_e( 'Open menu', 'labora' ); ?>">
      <span class="bars" aria-hidden="true"><span></span><span></span><span></span></span>
    </button>
  </div>
</header>

<nav class="mobile-menu" id="mobile-menu" aria-label="<?php esc_attr_e( 'Mobile', 'labora' ); ?>" data-lenis-prevent>
<?php labora_mobile_nav_items(); ?>
  <a class="m-link" href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php esc_html_e( 'Sign in', 'labora' ); ?></a>
  <div class="m-actions">
    <a class="btn btn--primary btn--lg" href="<?php echo esc_url( $labora_demo ); ?>"><?php esc_html_e( 'Book a demo', 'labora' ); ?></a>
    <a class="btn btn--ghost btn--lg" href="<?php echo esc_url( home_url( '/signup/' ) ); ?>"><?php esc_html_e( 'Get started', 'labora' ); ?></a>
  </div>
</nav>
