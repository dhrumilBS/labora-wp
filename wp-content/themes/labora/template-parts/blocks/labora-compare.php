<?php
/**
 * Block: Before and after (inc/blog-blocks.php). Two lists side by side, like the homepage's "What changes" table.
 *
 * @package Labora
 * @var bool $is_preview
 */

defined( 'ABSPATH' ) || exit;

$labora_lines = fn( $name ) => array_values( array_filter( array_map( fn( $r ) => trim( (string) ( $r['text'] ?? '' ) ), (array) get_field( $name ) ) ) );
$labora_cols  = array(
	array( trim( (string) get_field( 'before_label' ) ) ?: __( 'Today', 'labora' ), $labora_lines( 'before' ), '' ),
	array( trim( (string) get_field( 'after_label' ) ) ?: __( 'With Labora', 'labora' ), $labora_lines( 'after' ), ' compare-col--after' ),
);
if ( ! $labora_cols[0][1] && ! $labora_cols[1][1] ) {
	if ( $is_preview ) {
		echo '<p class="labora-block-empty">' . esc_html__( 'Before and after: add the lines in the block settings.', 'labora' ) . '</p>';
	}
	return;
}
?>
<div class="compare">
<?php foreach ( $labora_cols as list( $labora_label, $labora_list, $labora_mod ) ) : ?>
        <div class="compare-col<?php echo esc_attr( $labora_mod ); ?>">
          <p class="compare-label"><?php echo esc_html( $labora_label ); ?></p>
          <ul>
<?php foreach ( $labora_list as $labora_line ) : ?>
            <li><?php echo esc_html( $labora_line ); ?></li>
<?php endforeach; ?>
          </ul>
        </div>
<?php endforeach; ?>
      </div>
