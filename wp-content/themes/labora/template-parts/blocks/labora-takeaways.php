<?php
/**
 * Block: Key takeaways (inc/blog-blocks.php). Same markup as the HTML article.
 *
 * @package Labora
 * @var array $block
 * @var bool  $is_preview
 */

defined( 'ABSPATH' ) || exit;

$labora_points = array_filter( (array) get_field( 'points' ), fn( $r ) => '' !== trim( (string) ( $r['text'] ?? '' ) ) );
if ( ! $labora_points ) {
	if ( $is_preview ) {
		echo '<p class="labora-block-empty">' . esc_html__( 'Key takeaways: add the points in the block settings.', 'labora' ) . '</p>';
	}
	return;
}
$labora_id = labora_block_dom_id( 'takeaways-title', $block );
?>
<section class="takeaways" aria-labelledby="<?php echo esc_attr( $labora_id ); ?>">
        <h2 id="<?php echo esc_attr( $labora_id ); ?>" class="no-toc"><?php echo labora_icon( 'zap' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( get_field( 'title' ) ?: __( 'Key takeaways', 'labora' ) ); ?></h2>
        <ul>
<?php foreach ( $labora_points as $labora_point ) : ?>
          <li><?php echo labora_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo labora_block_text( $labora_point['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
<?php endforeach; ?>
        </ul>
      </section>
