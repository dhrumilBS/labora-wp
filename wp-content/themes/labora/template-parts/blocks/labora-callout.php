<?php
/**
 * Block: Note box (inc/blog-blocks.php). The HTML article's .callout, with a type modifier.
 * Without a label it is the HTML site's ruled remark (tip, note, warning). With a label, and always for
 * Important, Example and Learn more, it is a tinted box with a small heading and icon (.callout--boxed).
 *
 * @package Labora
 * @var bool $is_preview
 */

defined( 'ABSPATH' ) || exit;

$labora_types = labora_callout_types();
$labora_type  = array_key_exists( (string) get_field( 'type' ), $labora_types ) ? (string) get_field( 'type' ) : 'tip';
$labora_title = trim( (string) get_field( 'title' ) );
$labora_text  = trim( (string) get_field( 'text' ) );
$labora_href  = 'learn' === $labora_type ? labora_block_url( (string) get_field( 'link_url' ) ) : '';
if ( '' === $labora_title && '' === $labora_text && '' === $labora_href ) {
	if ( $is_preview ) {
		echo '<p class="labora-block-empty">' . esc_html__( 'Note box: add a title and text in the block settings.', 'labora' ) . '</p>';
	}
	return;
}
$labora_label = trim( (string) get_field( 'label' ) );
if ( '' === $labora_label && in_array( $labora_type, array( 'important', 'example', 'learn' ), true ) ) {
	$labora_label = $labora_types[ $labora_type ];
}
$labora_icons = array( 'tip' => 'zap', 'note' => 'check', 'warning' => 'alert', 'important' => 'target', 'example' => 'clipboard', 'learn' => 'book' );
// A single paragraph loses its <p>, as in the HTML site's markup (title and text in one <div>)
if ( 1 === substr_count( $labora_text, '<p>' ) ) {
	$labora_text = preg_replace( '#^<p>(.*)</p>$#s', '$1', $labora_text );
}
$labora_role = in_array( $labora_type, array( 'warning', 'important' ), true ) ? ' role="note"' : '';
?>
<div class="callout callout--<?php echo esc_attr( $labora_type ); ?><?php echo '' !== $labora_label ? ' callout--boxed' : ''; ?>"<?php echo $labora_role; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
        <span class="c-icon" aria-hidden="true"><svg class="icon"><use href="#i-<?php echo esc_attr( $labora_icons[ $labora_type ] ); ?>"/></svg></span>
        <div><?php if ( '' !== $labora_label ) : ?><span class="c-label"><?php echo esc_html( $labora_label ); ?></span><?php endif; ?><?php if ( '' !== $labora_title ) : ?><strong><?php echo esc_html( $labora_title ); ?></strong><?php endif; ?><?php echo labora_block_text( $labora_text ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php if ( '' !== $labora_href ) : ?>
          <a class="link-arrow c-link" href="<?php echo esc_url( $labora_href ); ?>"><?php echo esc_html( trim( (string) get_field( 'link_label' ) ) ?: __( 'Read the guide', 'labora' ) ); ?> <?php echo labora_icon( 'arrow', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a><?php endif; ?></div>
      </div>
