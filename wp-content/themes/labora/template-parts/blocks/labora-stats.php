<?php
/**
 * Block: Key numbers (inc/blog-blocks.php). Two to four figures, each with a label, and an optional source line.
 *
 * @package Labora
 * @var bool $is_preview
 */

defined( 'ABSPATH' ) || exit;

$labora_items = array_filter( (array) get_field( 'items' ), fn( $r ) => '' !== trim( (string) ( $r['value'] ?? '' ) ) );
if ( ! $labora_items ) {
	if ( $is_preview ) {
		echo '<p class="labora-block-empty">' . esc_html__( 'Key numbers: add two to four numbers in the block settings.', 'labora' ) . '</p>';
	}
	return;
}
?>
<figure class="stat-row" style="--stat-cols:<?php echo (int) count( $labora_items ); ?>">
        <dl>
<?php foreach ( $labora_items as $labora_item ) : ?>
          <div class="stat"><dt><?php echo esc_html( $labora_item['label'] ?? '' ); ?></dt><dd><?php echo esc_html( $labora_item['value'] ); ?></dd></div>
<?php endforeach; ?>
        </dl>
<?php if ( get_field( 'note' ) ) : ?>
        <figcaption><?php echo esc_html( get_field( 'note' ) ); ?></figcaption>
<?php endif; ?>
      </figure>
