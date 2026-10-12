<?php
/**
 * Shared parts of the Platform module, Solution and Hub templates (page-templates/platform.php, solution.php,
 * hub.php), and the Redirect template (a page that only sends visitors elsewhere).
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

/** A page field (any page), or '' when empty. */
function labora_page_field( string $name, int $page_id ) {
	return function_exists( 'get_field' ) ? get_field( $name, $page_id ) : get_post_meta( $page_id, $name, true );
}

/** A section heading field, or its default; {noun} is replaced with the module's "name in a sentence". */
function labora_heading( string $name, string $default ): string {
	$value = trim( (string) labora_field( $name ) );
	return str_replace( '{noun}', (string) labora_field( 'noun' ), '' !== $value ? $value : $default );
}

/** The short line on a page's card: its excerpt, else its first feature (Platform), else the first sentence of its heading text. */
function labora_card_line( int $id ): string {
	$first = trim( (string) get_post_field( 'post_excerpt', $id ) );
	if ( '' === $first ) {
		$first = trim( (string) strtok( (string) labora_page_field( 'features', $id ), "\r\n" ) );
	}
	if ( '' === $first && preg_match( '/^.+?[.!?](?=\s|$)/u', trim( (string) labora_page_field( 'hero_lead', $id ) ), $m ) ) {
		$first = $m[0];
	}
	return $first;
}

/** The pages a hub lists: its "Cards" choice, or every published page under it in page order. */
function labora_hub_pages( int $hub ): array {
	$chosen = array_filter( array_map( 'intval', (array) labora_page_field( 'cards', $hub ) ) );
	if ( $chosen ) {
		return array_values( $chosen );
	}
	return get_posts( array( 'post_type' => 'page', 'post_parent' => $hub, 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ), 'fields' => 'ids' ) );
}

/** Where a link to a parent page goes: a Redirect page's target, otherwise the page itself (e.g. a hub). */
function labora_parent_url( int $parent ): string {
	if ( 'page-templates/redirect.php' === get_page_template_slug( $parent ) ) {
		$target = trim( (string) labora_page_field( 'redirect_url', $parent ) );
		if ( '' !== $target ) {
			return labora_link( $target );
		}
	}
	return (string) get_permalink( $parent );
}

/** Module cards (the HTML site's .pr-mod): icon, name (page title), and first feature of each Platform page. */
function labora_module_cards( array $ids ): string {
	$html = '';
	foreach ( array_filter( array_map( 'intval', $ids ) ) as $id ) {
		if ( 'publish' !== get_post_status( $id ) ) {
			continue;
		}
		$first = labora_card_line( $id );
		$html .= sprintf(
			'<a class="pr-mod reveal" href="%1$s"><span class="t-ic">%2$s</span><span><b>%3$s</b><small>%4$s</small></span>%5$s</a>',
			esc_url( get_permalink( $id ) ),
			labora_icon( (string) labora_page_field( 'icon', $id ) ?: 'layers' ),
			esc_html( get_the_title( $id ) ),
			esc_html( $first ),
			labora_icon( 'arrow', 'icon icon-sm' )
		);
	}
	return $html;
}

/** The product screenshot, or the integrations diagram when no screenshot is chosen. */
function labora_product_visual(): string {
	$screen = (string) labora_field( 'screen' );
	if ( '' !== $screen ) {
		return '<figure class="pr-shot">' . labora_screen_picture( $screen, (string) labora_field( 'screen_alt' ), true ) . '</figure>';
	}
	$rows = array(
		array( 'cpu', 'Lab instruments', 'Readings and run files' ),
		array( 'key', 'Identity and access', 'Sign-in and users' ),
		array( 'bar', 'BI and analytics', 'Reporting tools' ),
		array( 'cloud', 'Cloud storage', 'Files and archives' ),
		array( 'flow', 'Automation', 'Workflow platforms' ),
		array( 'code', 'REST API', 'Build your own' ),
	);
	$items = '';
	foreach ( $rows as list( $icon, $title, $text ) ) {
		$items .= '<li><span class="t-ic">' . labora_icon( $icon ) . '</span><span><b>' . esc_html( $title ) . '</b><small>' . esc_html( $text ) . '</small></span></li>';
	}
	return '<figure class="pr-shot pr-connect" aria-label="' . esc_attr__( 'Labora connects with lab instruments and business systems', 'labora' ) . '"><div class="pr-hub"><svg class="logo-mark" aria-hidden="true"><use href="#logo-mark-light"/></svg><b>Labora</b><small>' . esc_html__( 'Integration layer and API', 'labora' ) . '</small></div><ul>' . $items . '</ul></figure>';
}

/** The closing demo box of Platform and Solution pages. */
function labora_product_cta(): void {
	?>
<section class="page-section pr-cta-wrap" aria-labelledby="pr-cta-title">
  <div class="container">
    <div class="ab-cta-card reveal">
      <div>
        <h2 id="pr-cta-title"><?php echo esc_html( labora_field( 'cta_title' ) ); ?></h2>
        <p><?php echo esc_html( labora_field( 'cta_text' ) ); ?></p>
      </div>
      <div class="ab-ctas">
        <a class="btn btn--primary btn--lg" href="<?php echo esc_url( labora_setting_url( 'demo_url' ) ); ?>"><?php echo esc_html( labora_setting( 'demo_label' ) ); ?></a>
        <a class="btn btn--ghost btn--lg" href="<?php echo esc_url( home_url( '/pricing/' ) ); ?>"><?php esc_html_e( 'See pricing', 'labora' ); ?></a>
      </div>
    </div>
  </div>
</section>
	<?php
}

/** Breadcrumbs of Platform, Solution and hub pages: Home > parent (its hub, or a Redirect page's target) > this page. */
function labora_product_crumbs(): string {
	$parent = wp_get_post_parent_id( get_the_ID() );
	$link   = '';
	if ( $parent ) {
		$link = sprintf( '<a href="%s">%s</a>%s', esc_url( labora_parent_url( $parent ) ), esc_html( get_the_title( $parent ) ), labora_icon( 'chev' ) );
	}
	return sprintf(
		'<nav class="pr-crumbs" aria-label="%1$s"><a href="%2$s">%3$s</a>%4$s%5$s<span aria-current="page">%6$s</span></nav>',
		esc_attr__( 'Breadcrumb', 'labora' ),
		esc_url( home_url( '/' ) ),
		esc_html__( 'Home', 'labora' ),
		labora_icon( 'chev' ),
		$link,
		esc_html( get_the_title() )
	);
}

// Redirect template: a permanent redirect to the page's "Send visitors to" address
add_action( 'template_redirect', function () {
	if ( ! is_page() || 'page-templates/redirect.php' !== get_page_template_slug() || is_preview() ) {
		return;
	}
	$target = trim( (string) labora_field( 'redirect_url' ) );
	if ( '' !== $target ) {
		wp_redirect( labora_link( $target ), 301 ); // phpcs:ignore WordPress.Security.SafeRedirect -- set by an editor
		exit;
	}
} );

// Yoast: the redirect pages are not indexed (and left out of the sitemap)
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', function ( $ids ) {
	$redirects = get_posts( array( 'post_type' => 'page', 'fields' => 'ids', 'numberposts' => -1, 'meta_key' => '_wp_page_template', 'meta_value' => 'page-templates/redirect.php' ) );
	return array_merge( (array) $ids, $redirects );
} );
