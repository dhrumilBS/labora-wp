<?php
/**
 * Site footer, same markup as the HTML site.
 * The four link columns and the bottom links come from Appearance > Menus (the column heading is the menu name);
 * the logo, the company line, and "Cookie settings" are fixed here.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

?>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'Labora home', 'labora' ); ?>"><svg class="logo-mark" aria-hidden="true"><use href="#logo-mark-light"/></svg>Labora</a>
        <p><?php esc_html_e( 'Laboratory management software for pathology, imaging, home collection, and multi-center labs.', 'labora' ); ?></p>
      </div>
<?php
labora_footer_col( 'footer_platform' );
labora_footer_col( 'footer_solutions' );
labora_footer_col( 'footer_resources' );
labora_footer_col( 'footer_company' );
?>
    </div>
    <div class="footer-bottom">
      <span>&copy; <span data-year><?php echo esc_html( wp_date( 'Y' ) ); ?></span> Labora. <?php esc_html_e( 'All rights reserved.', 'labora' ); ?></span>
      <ul><?php labora_footer_legal_items(); ?><li><button class="link-btn" type="button" data-cookie-settings><?php esc_html_e( 'Cookie settings', 'labora' ); ?></button></li></ul>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
