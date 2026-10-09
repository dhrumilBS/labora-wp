<?php
/**
 * Block: Note, tip, or warning (inc/blog-blocks.php). The HTML article's .callout, with a type modifier.
 *
 * @package Labora
 * @var bool $is_preview
 */

defined( 'ABSPATH' ) || exit;

$labora_type  = in_array( get_field( 'type' ), array( 'tip', 'note', 'warning' ), true ) ? get_field( 'type' ) : 'tip';
$labora_title = trim( (string) get_field( 'title' ) );
$labora_text  = trim( (string) get_field( 'text' ) );
if ( '' === $labora_title && '' === $labora_text ) {
	if ( $is_preview ) {
		echo '<p class="labora-block-empty">' . esc_html__( 'Note, tip, or warning: add a title and text in the block settings.', 'labora' ) . '</p>';
	}
	return;
}
$labora_icons = array( 'tip' => 'zap', 'note' => 'check', 'warning' => 'alert' );
// A single paragraph loses its <p>, as in the HTML site's markup (title and text in one <div>)
if ( 1 === substr_count( $labora_text, '<p>' ) ) {
	$labora_text = preg_replace( '#^<p>(.*)</p>$#s', '$1', $labora_text );
}
?>
<div class="callout callout--<?php echo esc_attr( $labora_type ); ?>"<?php echo 'warning' === $labora_type ? ' role="note"' : ''; ?>>
        <span class="c-icon" aria-hidden="true"><svg class="icon"><use href="#i-<?php echo esc_attr( $labora_icons[ $labora_type ] ); ?>"/></svg></span>
        <div><?php if ( '' !== $labora_title ) : ?><strong><?php echo esc_html( $labora_title ); ?></strong><?php endif; ?><?php echo labora_block_text( $labora_text ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
      </div>
