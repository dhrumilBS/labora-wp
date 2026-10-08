<?php
/**
 * Fallback template. Every page gets its own template once its structure is approved;
 * until then WordPress falls back to this minimal view.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<main id="main" class="section">
	<div class="container">
		<p class="eyebrow"><?php esc_html_e( 'Labora on WordPress', 'labora' ); ?></p>
		<h1><?php esc_html_e( 'Theme installed', 'labora' ); ?></h1>
		<p class="lead"><?php esc_html_e( 'Pages are added one at a time as their structure is approved.', 'labora' ); ?></p>
	</div>
</main>
<?php wp_footer(); ?>
</body>
</html>
