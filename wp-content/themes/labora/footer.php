<?php
/**
 * Site footer, same markup as the HTML site.
 *  - Link columns: Appearance > Widgets > Footer columns ("Labora: Menu column" widgets, inc/widgets.php)
 *  - Description under the logo and the copyright line: Labora Settings
 *  - Bottom links: the "Footer: bottom links (Legal)" menu, then "Cookie settings", then "Do Not Sell or Share"
 *    (shown only to the visitors it applies to, by TrustLayer Consent)
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
        <p><?php echo esc_html( labora_setting( 'company_tagline' ) ); ?></p>
      </div>
<?php labora_footer_columns(); ?>
    </div>
    <div class="footer-bottom">
      <span>&copy; <span data-year><?php echo esc_html( wp_date( 'Y' ) ); ?></span> <?php echo esc_html( str_replace( '{company}', (string) labora_setting( 'company_name' ), (string) labora_setting( 'copyright' ) ) ); ?></span>
      <ul><?php labora_footer_legal_items(); ?><li><button class="link-btn" type="button" data-cookie-settings><?php esc_html_e( 'Cookie settings', 'labora' ); ?></button></li><?php labora_footer_dnss_item(); ?></ul>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
