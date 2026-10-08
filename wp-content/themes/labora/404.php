<?php
/**
 * 404 page, converted unchanged from the HTML site's 404.html.
 * Planned pages that are linked from the menu but not published yet (the Platform pages are drafts) show
 * "coming soon" and a link to the closest live content: that is the map below, read by assets/js/pages.js.
 * Remove a page from the map once it is published.
 *
 * @package Labora
 */

defined( 'ABSPATH' ) || exit;

/**
 * Planned pages: site path => [ page name, closest live content (path relative to the site), link label ].
 * Filter: labora_404_planned.
 */
function labora_404_planned(): array {
	return apply_filters( 'labora_404_planned', array(
		'platform/pathology-lab/' => array( 'Pathology lab', '#reporting', 'See pathology and imaging reporting' ),
		'platform/sample-tracking/' => array( 'Sample tracking', '#sample-tracking', 'See sample tracking' ),
		'platform/turnaround-tracking/' => array( 'Turnaround tracking', '#turnaround-tracking', 'See turnaround tracking' ),
		'platform/radiology-reporting/' => array( 'Radiology reporting', '#reporting', 'See pathology and imaging reporting' ),
		'platform/ecg-cardiology/' => array( 'ECG and cardiology', '#home-collection', 'See the product features' ),
		'platform/home-collection/' => array( 'Home collection', '#home-collection', 'See home collection' ),
		'platform/centers/' => array( 'Centers and outsource labs', '#home-collection', 'See the product features' ),
		'platform/reports-e-signature/' => array( 'Reports and e-signature', '#reporting', 'See reporting and sign-off' ),
		'platform/business-insights/' => array( 'Business insights', '#business-insights', 'See business insights' ),
		'platform/integrations/' => array( 'Integrations and API', 'faq/#q-can-labora-connect-with-lab-analyzers-and-other-systems', 'See how integrations work' ),
		'resources/guides/' => array( 'Guides', 'blog/', 'Read the blog' ),
		'docs/api/' => array( 'API documentation', 'faq/#q-can-labora-connect-with-lab-analyzers-and-other-systems', 'See how integrations work' ),
		'login/' => array( 'Sign in', '#demo', 'Book a demo to get access' ),
		'signup/' => array( 'Sign up', '#demo', 'Book a demo to get started' ),
	) );
}

/** The map as pages.js expects it: keys are paths as seen in the address bar under this install. */
function labora_404_planned_json(): void {
	$base = ltrim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' ); // "" or "labora-wp/"
	$map  = array();
	foreach ( labora_404_planned() as $path => list( $name, $target, $label ) ) {
		$map[ $base . ltrim( $path, '/' ) ] = array( $name, home_url( '/' . ltrim( $target, '/' ) ), $label );
	}
	echo '<script type="application/json" id="nf-planned">' . wp_json_encode( $map, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
}

labora_enqueue_bundle( 'pages' );
add_filter( 'body_class', fn( $c ) => array_merge( $c, array( 'error404-labora' ) ) );

get_header();
?>

<main id="main" class="nf-main">
<section class="nf" aria-labelledby="nf-title">
  <div class="container nf-grid">
    <div class="nf-copy">
      <p class="nf-code" aria-hidden="true"><span>4</span><span class="nf-zero">0</span><span>4</span></p>
      <p class="hm-label" data-nf-eyebrow>Error 404</p>
      <h1 id="nf-title">We couldn't find that page</h1>
      <p class="nf-lead" data-nf-lead>The link may be out of date, or the page may have moved. The pages people look for most are listed here.</p>
      <dl class="nf-addr"><dt>Address</dt><dd data-nf-query>/unknown-page</dd></dl>
      <div class="nf-ctas">
        <a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>" data-nf-primary>Go to the homepage</a>
        <a class="nf-alt" href="<?php echo esc_url( home_url( '/' ) ); ?>#demo" data-nf-alt>Book a demo</a>
      </div>
    </div>
    <nav class="nf-index" aria-labelledby="nf-links-title">
      <h2 id="nf-links-title">Where to go instead</h2>
      <ul><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>#product-tour"><span class="nf-i-t">Product tour</span><span class="nf-i-d">From registration to a signed report.</span><svg class="icon icon-sm" aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></li><li><a href="<?php echo esc_url( home_url( '/' ) ); ?>#home-collection"><span class="nf-i-t">Product features</span><span class="nf-i-d">Sample tracking, reporting, home collection, billing.</span><svg class="icon icon-sm" aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></li><li><a href="<?php echo esc_url( home_url( '/pricing/' ) ); ?>"><span class="nf-i-t">Pricing</span><span class="nf-i-d">How plans are put together for your lab.</span><svg class="icon icon-sm" aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></li><li><a href="<?php echo esc_url( home_url( '/security/' ) ); ?>"><span class="nf-i-t">Trust Center</span><span class="nf-i-d">How patient data is protected.</span><svg class="icon icon-sm" aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></li><li><a href="<?php echo esc_url( home_url( '/faq/' ) ); ?>"><span class="nf-i-t">FAQ</span><span class="nf-i-d">Features, security, setup, and pricing.</span><svg class="icon icon-sm" aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></li><li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"><span class="nf-i-t">Blog</span><span class="nf-i-d">Practical guides for running a faster lab.</span><svg class="icon icon-sm" aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></li></ul>
      <p class="nf-note">Followed a broken link from another site? <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Tell us where</a> and we'll fix it.</p>
    </nav>
  </div>
</section>
<?php labora_404_planned_json(); ?>
</main>

<?php
get_footer();
