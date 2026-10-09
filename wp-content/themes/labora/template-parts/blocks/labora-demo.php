<?php
/**
 * Block: Book a demo box (inc/blog-blocks.php). The HTML article's .inline-cta; the button defaults to the
 * "Book a demo" text and link in Labora Settings.
 *
 * @package Labora
 * @var bool $is_preview
 */

defined( 'ABSPATH' ) || exit;

$labora_title = trim( (string) get_field( 'title' ) );
if ( '' === $labora_title ) {
	if ( $is_preview ) {
		echo '<p class="labora-block-empty">' . esc_html__( 'Book a demo box: add a heading in the block settings.', 'labora' ) . '</p>';
	}
	return;
}
$labora_label = trim( (string) get_field( 'label' ) ) ?: (string) labora_setting( 'demo_label' );
$labora_url   = trim( (string) get_field( 'url' ) );
$labora_url   = '' === $labora_url ? labora_setting_url( 'demo_url' ) : ( preg_match( '#^(https?:|mailto:|tel:|\#)#i', $labora_url ) ? $labora_url : home_url( '/' . ltrim( $labora_url, '/' ) ) );
?>
<aside class="inline-cta" aria-label="<?php echo esc_attr( $labora_label ); ?>">
        <div>
          <h3><?php echo esc_html( $labora_title ); ?></h3>
<?php if ( get_field( 'text' ) ) : ?>
          <p><?php echo labora_block_text( get_field( 'text' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
<?php endif; ?>
        </div>
        <a class="btn btn--lg" href="<?php echo esc_url( $labora_url ); ?>"><?php echo esc_html( $labora_label ); ?></a>
      </aside>
