<?php
/**
 * Patch: HandL UTM Grabber v3 (premium, tested on 3.0.55) for PHP 8.3 and Contact Form 7 6.x.
 *
 * Fixes the deprecation notices the plugin logs on every request:
 *  1. handl-utm-grabber-v3.php: "${var}" string interpolation (deprecated in PHP 8.2) -> "{$var}".
 *  2. premiums/contact-form-7.php: its Contact Form 7 tag generators (one per UTM field, ~30) use CF7's old
 *     version 1 API ("Use of tag generator instances older than version 2 is deprecated"). They are rewritten
 *     to the version 2 API CF7 itself uses, with the same options (name, default value, id, class).
 *
 * The plugin is third-party: an update replaces these files, so run this again after updating it.
 * It is idempotent (already-patched files are left alone) and refuses to touch code it does not recognize.
 *
 * Usage (from the WordPress root):  php scripts/patches/handl-utm-grabber-v3.php
 *                                   or: scripts/apply-patches.sh
 */

$root   = dirname( __DIR__, 2 );
$plugin = $root . '/wp-content/plugins/handl-utm-grabber-v3';
if ( ! is_dir( $plugin ) ) {
	fwrite( STDERR, "HandL UTM Grabber v3 is not installed; nothing to patch.\n" );
	exit( 0 );
}

$patches = array(
	'handl-utm-grabber-v3.php' => array(
		array(
			'find'    => 'apply_filters("get_admin_tab_content_${tab}", \'\');',
			'replace' => 'apply_filters("get_admin_tab_content_{$tab}", \'\'); // patched: PHP 8.2+ interpolation',
			'done'    => 'apply_filters("get_admin_tab_content_{$tab}", \'\');',
		),
		array(
			// getDomainName(): $_SERVER['SERVER_NAME'] is not set outside a web request (WP-CLI, server cron),
			// which logged "Undefined array key" plus an explode(null) deprecation. Fall back to the site's host.
			'find'    => '            $host = $_SERVER["SERVER_NAME"];',
			'replace' => '            $host = isset( $_SERVER["SERVER_NAME"] ) ? $_SERVER["SERVER_NAME"] : (string) wp_parse_url( home_url(), PHP_URL_HOST ); // patched: no SERVER_NAME outside web requests',
			'done'    => '// patched: no SERVER_NAME outside web requests',
		),
		array(
			'find'    => '$domain = $_SERVER["SERVER_NAME"];',
			'replace' => '$domain = $host; // patched: same host as above',
			'done'    => '$domain = $host; // patched',
		),
		array(
			// getDomainName() sets a test cookie; outside a web request, or after output, that warned "headers already sent"
			'find'    => "setcookie(\$testCookieName, \$testCookieValue, time() + 60 * 60 * 24, '/', \$domain);",
			'replace' => "if ( ! headers_sent() ) { setcookie(\$testCookieName, \$testCookieValue, time() + 60 * 60 * 24, '/', \$domain); } // patched: no cookie once output started",
			'done'    => '// patched: no cookie once output started',
		),
		array(
			// getHandLTestCookie(): the test cookie is missing when no Set-Cookie header was sent ("Undefined array key")
			'find'    => 'return $cookies[$name];',
			'replace' => 'return isset( $cookies[$name] ) ? $cookies[$name] : \'\'; // patched: cookie may be missing',
			'done'    => '// patched: cookie may be missing',
		),
		array(
			// getDomainName(): on an IP-address host (e.g. http://192.168.0.25/) it built ".168.0.25" as the cookie
			// domain, which browsers reject, so no tracking cookie was ever saved. IP hosts get host-only cookies.
			'find'    => '$domainParts = explode(".",$host);',
			'replace' => '$domainParts = filter_var( $host, FILTER_VALIDATE_IP ) ? array( $host ) : explode(".",$host); // patched: IP hosts take host-only cookies',
			'done'    => '// patched: IP hosts take host-only cookies',
		),
	),
	'js/handl-utm-grabber.js' => array(
		array(
			// Organic-source detection called .match() on an undefined cookie and threw on every page
			// ("Cannot read properties of undefined (reading 'match')") whenever handl_ref_domain was not set.
			'find'    => "let original_ref =  Cookies.get('handl_ref_domain')\n",
			'replace' => "let original_ref =  Cookies.get('handl_ref_domain') || '' // patched: the cookie may be missing\n",
			'done'    => '// patched: the cookie may be missing',
		),
		array(
			// Same IP-host problem as getDomainName() in PHP: use host-only cookies on an IP address
			'find'    => "    if (host.split('.').length === 1){",
			'replace' => "    if (host.split('.').length === 1 || /^\\d{1,3}(\\.\\d{1,3}){3}(:\\d+)?$/.test(host)){ // patched: IP hosts take host-only cookies",
			'done'    => '// patched: IP hosts take host-only cookies',
		),
	),
	'premiums/contact-form-7.php' => array(
		array(
			'find'    => "        \$tag_generator->add( \$this->tag, \$this->title,\n            array(\$this, 'wpcf7_tag_generator_handl'));",
			'replace' => "        // patched: Contact Form 7 tag generator API version 2 (CF7 6.0+)\n        \$tag_generator->add( \$this->tag, \$this->title,\n            array(\$this, 'wpcf7_tag_generator_handl'), array( 'version' => '2' ) );",
			'done'    => "array( 'version' => '2' )",
		),
		array(
			// The whole version 1 panel callback, from its signature to the end of the class
			'find_regex' => '/    public function wpcf7_tag_generator_handl\(\$cf, \$args\)\{.*?\n        <\?php\n    \}\n\}/s',
			'replace'    => <<<'PHP'
    // patched: Contact Form 7 tag generator panel, API version 2 (CF7 6.0+). Same options as the original panel.
    public function wpcf7_tag_generator_handl($contact_form, $options){
        $tgg = new WPCF7_TagGeneratorGenerator( $options['content'] );
        ?>
        <header class="description-box">
            <h3><?php echo esc_html( sprintf( '%s form-tag generator', $this->title ) ); ?></h3>
            <p><?php echo esc_html( sprintf( 'Adds a hidden field filled with the visitor\'s %s value, tracked by HandL UTM Grabber.', $this->title ) ); ?></p>
        </header>

        <div class="control-box">
            <?php
            $tgg->print( 'field_type', array(
                'select_options' => array( $this->tag => $this->title ),
            ) );
            $tgg->print( 'field_name' );
            $tgg->print( 'default_value', array( 'title' => __( 'Default value', 'contact-form-7' ) ) );
            $tgg->print( 'id_attr' );
            $tgg->print( 'class_attr' );
            ?>
        </div>

        <footer class="insert-box">
            <?php
            $tgg->print( 'insert_box_content' );
            $tgg->print( 'mail_tag_tip' );
            ?>
        </footer>
        <?php
    }
}
PHP
			,
			'done'       => 'new WPCF7_TagGeneratorGenerator( $options[\'content\'] )',
		),
	),
);

$failed = false;
foreach ( $patches as $rel => $edits ) {
	$file = "$plugin/$rel";
	$code = file_get_contents( $file );
	if ( false === $code ) {
		fwrite( STDERR, "Cannot read $rel\n" );
		$failed = true;
		continue;
	}
	$orig = $code;
	foreach ( $edits as $i => $e ) {
		if ( false !== strpos( $code, $e['done'] ) ) {
			echo "  $rel #" . ( $i + 1 ) . ": already patched\n";
			continue;
		}
		if ( isset( $e['find_regex'] ) ) {
			$new = preg_replace( $e['find_regex'], str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $e['replace'] ), $code, 1, $n );
		} else {
			$n   = substr_count( $code, $e['find'] );
			$new = 1 === $n ? str_replace( $e['find'], $e['replace'], $code ) : $code;
		}
		if ( 1 !== $n ) {
			fwrite( STDERR, "  $rel #" . ( $i + 1 ) . ": code not recognized (plugin changed?). Not patched.\n" );
			$failed = true;
			continue;
		}
		$code = $new;
		echo "  $rel #" . ( $i + 1 ) . ": patched\n";
	}
	if ( $code !== $orig ) {
		if ( ! file_exists( $file . '.orig' ) ) {
			copy( $file, $file . '.orig' ); // keep the untouched vendor file next to it (written once, never overwritten)
		}
		file_put_contents( $file, $code );
	}
}
exit( $failed ? 1 : 0 );
