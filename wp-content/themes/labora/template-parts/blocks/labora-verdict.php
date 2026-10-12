<?php
/**
 * Block: Verdict box (inc/blog-blocks.php). A score out of 5 with a meter, a summary, pros and cons side by side
 * (like "Before and after"), who it suits, and an optional button.
 * No Review structured data: reviews a site publishes about its own product or category are not eligible for
 * review rich results, and marking them up can count as spam.
 *
 * @package Labora
 * @var bool $is_preview
 */

defined( 'ABSPATH' ) || exit;

$labora_title = trim( (string) get_field( 'title' ) );
if ( '' === $labora_title ) {
	if ( $is_preview ) {
		echo '<p class="labora-block-empty">' . esc_html__( 'Verdict box: add a heading in the block settings.', 'labora' ) . '</p>';
	}
	return;
}
$labora_lines = fn( $name ) => array_values( array_filter( array_map( fn( $r ) => trim( (string) ( $r['text'] ?? '' ) ), (array) get_field( $name ) ) ) );
$labora_pros  = $labora_lines( 'pros' );
$labora_cons  = $labora_lines( 'cons' );
$labora_score = get_field( 'score' );
$labora_score = ( '' === $labora_score || null === $labora_score ) ? null : max( 0, min( 5, round( (float) $labora_score * 2 ) / 2 ) );
$labora_id    = labora_block_dom_id( 'verdict', $block );
$labora_cta   = trim( (string) get_field( 'verdict_cta_label' ) );
$labora_cta_u = labora_block_url( (string) get_field( 'verdict_cta_url' ) );
?>
<section class="verdict" aria-labelledby="<?php echo esc_attr( $labora_id ); ?>">
        <div class="verdict-head<?php echo null === $labora_score ? ' verdict-head--no-score' : ''; ?>">
          <div>
            <p class="verdict-eyebrow"><?php echo esc_html( trim( (string) get_field( 'eyebrow' ) ) ?: __( 'Our verdict', 'labora' ) ); ?></p>
            <h3 id="<?php echo esc_attr( $labora_id ); ?>"><?php echo esc_html( $labora_title ); ?></h3>
<?php if ( $labora_summary = trim( (string) get_field( 'summary' ) ) ) : ?>
            <p class="verdict-summary"><?php echo labora_block_text( $labora_summary ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
<?php endif; ?>
          </div>
<?php if ( null !== $labora_score ) : ?>
          <div class="verdict-score">
            <p class="v-num"><span class="sr-only"><?php esc_html_e( 'Score:', 'labora' ); ?> </span><?php echo esc_html( rtrim( rtrim( number_format( $labora_score, 1 ), '0' ), '.' ) ); ?><span class="v-out"> / 5</span></p>
            <span class="v-meter" aria-hidden="true"><span style="width:<?php echo esc_attr( (string) ( $labora_score * 20 ) ); ?>%"></span></span>
<?php if ( $labora_sl = trim( (string) get_field( 'score_label' ) ) ) : ?>
            <p class="v-label"><?php echo esc_html( $labora_sl ); ?></p>
<?php endif; ?>
          </div>
<?php endif; ?>
        </div>
<?php if ( $labora_pros || $labora_cons ) : ?>
        <div class="verdict-cols">
<?php foreach ( array( array( __( 'Pros', 'labora' ), $labora_pros, 'pros', 'check' ), array( __( 'Cons', 'labora' ), $labora_cons, 'cons', 'x' ) ) as list( $labora_label, $labora_list, $labora_mod, $labora_icon ) ) : ?>
<?php if ( $labora_list ) : ?>
          <div class="verdict-col verdict-col--<?php echo esc_attr( $labora_mod ); ?>">
            <p class="verdict-label"><?php echo esc_html( $labora_label ); ?></p>
            <ul>
<?php foreach ( $labora_list as $labora_line ) : ?>
              <li><svg class="icon" aria-hidden="true"><use href="#i-<?php echo esc_attr( $labora_icon ); ?>"/></svg><?php echo esc_html( $labora_line ); ?></li>
<?php endforeach; ?>
            </ul>
          </div>
<?php endif; ?>
<?php endforeach; ?>
        </div>
<?php endif; ?>
<?php if ( ( $labora_best = trim( (string) get_field( 'best_for' ) ) ) || ( '' !== $labora_cta && '' !== $labora_cta_u ) ) : ?>
        <div class="verdict-foot">
          <?php if ( $labora_best ) : ?><p><strong><?php esc_html_e( 'Best for:', 'labora' ); ?></strong> <?php echo esc_html( $labora_best ); ?></p><?php endif; ?>
          <?php if ( '' !== $labora_cta && '' !== $labora_cta_u ) : ?><a class="btn btn--primary" href="<?php echo esc_url( $labora_cta_u ); ?>"><?php echo esc_html( $labora_cta ); ?></a><?php endif; ?>
        </div>
<?php endif; ?>
      </section>
