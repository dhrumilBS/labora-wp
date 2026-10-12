<?php
/**
 * Template Name: Legal page
 *
 * Privacy, Terms, Cookies: the HTML site's legal layout (hero with "Last updated" and the draft notice, "On this
 * page" beside the text, links to the other legal pages), with the text edited in the block editor.
 * See inc/legal.php.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

labora_enqueue_bundle( 'pages' );
get_header();

while ( have_posts() ) :
	the_post();
	list( $labora_html, $labora_toc ) = labora_legal_sections();
	$labora_updated = (string) labora_field( 'updated' ) ?: get_the_modified_date( 'Y-m-d' );
	$labora_slug    = get_post_field( 'post_name' );
	?>
<main id="main" class="<?php echo esc_attr( $labora_slug ); ?>-main">
<section class="lg-hero" aria-labelledby="lg-title">
  <div class="container">
    <p class="eyebrow"><span class="dot" aria-hidden="true"></span><?php esc_html_e( 'Legal', 'labora' ); ?></p>
    <h1 id="lg-title"><?php the_title(); ?></h1>
<?php if ( has_excerpt() ) : ?>
    <p class="lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
<?php endif; ?>
    <p class="lg-updated"><?php esc_html_e( 'Last updated', 'labora' ); ?> <time datetime="<?php echo esc_attr( $labora_updated ); ?>"><?php echo esc_html( wp_date( 'M j, Y', strtotime( $labora_updated . ' 12:00' ) ) ); ?></time></p>
<?php if ( labora_field( 'draft' ) ) : ?>
    <div class="lg-draft" role="note"><?php echo labora_icon( 'alert' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><div><?php echo wp_kses( (string) labora_field( 'draft_text' ), array( 'b' => array(), 'strong' => array(), 'em' => array(), 'a' => array( 'href' => true ) ) ); ?></div></div>
<?php endif; ?>
  </div>
</section>
<div class="container lg-layout">
  <nav class="lg-toc" aria-label="<?php esc_attr_e( 'On this page', 'labora' ); ?>">
    <p class="ab-label"><?php esc_html_e( 'On this page', 'labora' ); ?></p>
    <ol><?php foreach ( $labora_toc as list( $labora_id, $labora_text ) ) : ?><li><a href="#<?php echo esc_attr( $labora_id ); ?>"><?php echo esc_html( $labora_text ); ?></a></li><?php endforeach; ?></ol>
    <p class="lg-others"><?php foreach ( labora_legal_others() as $labora_o ) : ?><a href="<?php echo esc_url( get_permalink( $labora_o ) ); ?>"><?php echo esc_html( get_the_title( $labora_o ) ); ?></a><?php endforeach; ?></p>
  </nav>
  <article class="lg-prose">
<?php echo $labora_html; // phpcs:ignore WordPress.Security.EscapeOutput -- the_content ?>
</article>
</div>
</main>
	<?php
endwhile;

get_footer();
