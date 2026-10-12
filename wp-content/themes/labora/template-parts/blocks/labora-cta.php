<?php
/**
 * Block: Call-to-action banner (inc/blog-blocks.php). The homepage's closing box (.final-box): heading, text,
 * two buttons, and an optional numbered list. The main button defaults to "Book a demo" in Labora Settings.
 *
 * @package Labora
 * @var bool $is_preview
 */

defined( 'ABSPATH' ) || exit;

$labora_title = trim( (string) get_field( 'title' ) );
if ( '' === $labora_title ) {
	if ( $is_preview ) {
		echo '<p class="labora-block-empty">' . esc_html__( 'Call-to-action banner: add a heading in the block settings.', 'labora' ) . '</p>';
	}
	return;
}
$labora_points = array_values( array_filter( array_map( fn( $r ) => trim( (string) ( $r['text'] ?? '' ) ), (array) get_field( 'points' ) ) ) );
$labora_main   = trim( (string) get_field( 'cta_primary_label' ) ) ?: (string) labora_setting( 'demo_label' );
$labora_main_u = labora_block_url( (string) get_field( 'cta_primary_url' ) ) ?: labora_setting_url( 'demo_url' );
$labora_alt    = trim( (string) get_field( 'cta_secondary_label' ) );
$labora_alt_u  = labora_block_url( (string) get_field( 'cta_secondary_url' ) );
$labora_id     = labora_block_dom_id( 'cta-banner', $block );
?>
<aside class="final-box cta-banner<?php echo $labora_points ? '' : ' cta-banner--solo'; ?>" aria-labelledby="<?php echo esc_attr( $labora_id ); ?>">
        <div>
          <h2 class="no-toc" id="<?php echo esc_attr( $labora_id ); ?>"><?php echo esc_html( $labora_title ); ?></h2>
<?php if ( get_field( 'text' ) ) : ?>
          <p><?php echo labora_block_text( get_field( 'text' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
<?php endif; ?>
          <div class="ctas">
            <a class="btn btn--primary btn--lg" href="<?php echo esc_url( $labora_main_u ); ?>"><?php echo esc_html( $labora_main ); ?></a>
<?php if ( '' !== $labora_alt && '' !== $labora_alt_u ) : ?>
            <a class="btn btn--outline-light btn--lg" href="<?php echo esc_url( $labora_alt_u ); ?>"><?php echo esc_html( $labora_alt ); ?></a>
<?php endif; ?>
          </div>
        </div>
<?php if ( $labora_points ) : ?>
        <div>
          <p class="final-label"><?php echo esc_html( trim( (string) get_field( 'points_label' ) ) ?: __( 'In the demo', 'labora' ) ); ?></p>
          <ol class="final-list">
<?php foreach ( $labora_points as $labora_point ) : ?>
            <li><?php echo esc_html( $labora_point ); ?></li>
<?php endforeach; ?>
          </ol>
        </div>
<?php endif; ?>
      </aside>
