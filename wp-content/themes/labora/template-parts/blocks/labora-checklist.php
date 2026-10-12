<?php
/**
 * Block: Interactive checklist (inc/blog-blocks.php). Real checkboxes, so it works without JavaScript too;
 * assets/js/wp-blog.js adds the progress count, keeps the ticks in the reader's browser (localStorage), and
 * shows the "Copy checklist" and "Reset" buttons.
 *
 * @package Labora
 * @var bool $is_preview
 */

defined( 'ABSPATH' ) || exit;

$labora_title = trim( (string) get_field( 'title' ) );
$labora_items = array_values( array_filter( (array) get_field( 'items' ), fn( $r ) => '' !== trim( (string) ( $r['text'] ?? '' ) ) ) );
if ( '' === $labora_title || ! $labora_items ) {
	if ( $is_preview ) {
		echo '<p class="labora-block-empty">' . esc_html__( 'Interactive checklist: add a heading and items in the block settings.', 'labora' ) . '</p>';
	}
	return;
}
$labora_id    = labora_block_dom_id( 'checklist', $block );
$labora_total = count( $labora_items );
?>
<section class="lchk" data-checklist="<?php echo esc_attr( get_the_ID() . ':' . $labora_id ); ?>" aria-labelledby="<?php echo esc_attr( $labora_id ); ?>">
        <div class="lchk-head">
          <h3 id="<?php echo esc_attr( $labora_id ); ?>"><?php echo esc_html( $labora_title ); ?></h3>
          <p class="lchk-count" aria-live="polite" hidden><span data-chk-done>0</span> <?php echo esc_html( sprintf( /* translators: %d: number of items */ __( 'of %d done', 'labora' ), $labora_total ) ); ?></p>
        </div>
<?php if ( $labora_intro = trim( (string) get_field( 'intro' ) ) ) : ?>
        <p class="lchk-intro"><?php echo labora_block_text( $labora_intro ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
<?php endif; ?>
        <div class="lchk-bar" aria-hidden="true" hidden><span></span></div>
        <ul class="lchk-list">
<?php foreach ( $labora_items as $labora_i => $labora_item ) : ?>
          <li><label><input type="checkbox" value="<?php echo (int) $labora_i; ?>"><span class="lchk-box" aria-hidden="true"><svg class="icon"><use href="#i-check"/></svg></span><span class="lchk-text"><?php echo esc_html( trim( (string) $labora_item['text'] ) ); ?><?php if ( $labora_detail = trim( (string) ( $labora_item['detail'] ?? '' ) ) ) : ?><small><?php echo esc_html( $labora_detail ); ?></small><?php endif; ?></span></label></li>
<?php endforeach; ?>
        </ul>
        <div class="lchk-actions" hidden>
          <button type="button" class="btn btn--ghost btn--sm" data-chk-copy><?php echo labora_icon( 'clipboard', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Copy checklist', 'labora' ); ?></button>
          <button type="button" class="lchk-reset" data-chk-reset><?php echo labora_icon( 'refresh', 'icon icon-sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'Reset', 'labora' ); ?></button>
        </div>
      </section>
