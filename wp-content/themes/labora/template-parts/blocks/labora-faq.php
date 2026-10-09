<?php
/**
 * Block: Questions and answers (inc/blog-blocks.php). The homepage FAQ's accordion (.faq-item). The same
 * questions are sent to search engines as FAQPage structured data (labora_post_faq() in inc/blog.php).
 *
 * @package Labora
 * @var array $block
 * @var bool  $is_preview
 */

defined( 'ABSPATH' ) || exit;

$labora_items = array_filter( (array) get_field( 'items' ), fn( $r ) => '' !== trim( (string) ( $r['question'] ?? '' ) ) );
if ( ! $labora_items ) {
	if ( $is_preview ) {
		echo '<p class="labora-block-empty">' . esc_html__( 'Questions and answers: add questions in the block settings.', 'labora' ) . '</p>';
	}
	return;
}
$labora_id = labora_block_dom_id( 'faq-title', $block );
?>
<section class="article-faq" aria-labelledby="<?php echo esc_attr( $labora_id ); ?>">
        <h2 id="<?php echo esc_attr( $labora_id ); ?>"><?php echo esc_html( get_field( 'title' ) ?: __( 'Frequently asked questions', 'labora' ) ); ?></h2>
<?php foreach ( $labora_items as $labora_item ) : ?>
        <details class="faq-item"><summary><h3><?php echo esc_html( $labora_item['question'] ); ?></h3><span class="plus" aria-hidden="true"><?php echo labora_icon( 'plus' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></summary>
          <div class="faq-answer"><?php echo wpautop( labora_block_text( $labora_item['answer'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
        </details>
<?php endforeach; ?>
      </section>
