# TrustLayer Consent

Cookie consent for WordPress, built for US privacy laws first and international ones too. It works with any theme
and has no external services or dependencies.

- **Categories**: strictly necessary (always on), functional, analytics, marketing, plus your own, each with its cookie list.
- **Banner and preferences center**: five layouts, plugin or theme styling, accessible (dialog semantics, focus
  handling, keyboard, switches, reduced motion), responsive.
- **Consent models by region (geo-targeting)**: opt-in (EU/EEA/UK/Brazil/Canada), opt-out with Do Not Sell or Share
  (US states with privacy laws, rest of the US), notice only, or no banner. Per-country and per-state overrides.
  Location comes from CDN headers (Cloudflare, CloudFront, Vercel, App Engine, GeoIP), then a MaxMind GeoLite2 database
  read on the server (downloaded and updated weekly with a free license key, or your own file).
- **Global Privacy Control**: honored as an opt-out of sale and sharing.
- **Script and embed blocking**: automatic (17 known services plus your own rules), by script handle, by hand
  (`type="text/plain" data-tlc-category`), and custom code per category. Blocked iframes get an "Allow and show" placeholder.
- **Google Consent Mode v2** (region-specific defaults, updates, ads data redaction, URL passthrough) and Microsoft UET.
- **Consent log** as proof of consent: consent ID, choice, categories, model, region, page, shortened IP, browser.
  Kept 12 months by default, CSV export, summary of the last 30 days.
- **Import / export** of all settings (JSON), reset, "ask everyone again" (consent revision).
- **Developer API**: PHP functions, `TLC_CONFIG`, filters and actions, a JavaScript API with events, WP-CLI.

Requires WordPress 6.4+ and PHP 8.1+. Menu: **TrustLayer** (Settings, Consent log, Tools).

## Setting up a site

1. Activate the plugin, then turn on **General > Preview** so only administrators see the banner while you set it up.
2. **Regions**: check the model for each region. The defaults follow the usual reading of the laws: opt-in for the
   EU, EEA, Switzerland, UK, Canada, Brazil, and unknown locations; opt-out for the US. Behind Cloudflare, turn on
   "Add visitor location headers" (Rules > Managed Transforms) for US states. Without a CDN, add a free MaxMind account
   ID and license key and click **Download now**. Use **Test** at the bottom to check an address.
3. **Categories and cookies**: list every cookie the site sets in its category (`_ga*` matches a prefix). Untick
   "Shown to visitors" for categories the site does not use. Tick "Sale or sharing (US)" for categories that send data
   to advertisers.
4. **Script blocking**: tick the services the site uses, add rules for others. Check the page source: held-back
   scripts have `type="text/plain" data-tlc-category="..."`.
5. **Policies and Do Not Sell**: choose the cookie and privacy policy pages. Add a footer menu item (Custom link)
   with the URL `#tlc-dnss` and the text "Do Not Sell or Share My Personal Information", and another with
   `#tlc-preferences` and "Cookie settings". The Do Not Sell link only shows where it applies. Put `[tlc_cookie_table]`
   on the cookie policy page.
6. **Banner and design**, **Texts**: match the site.
7. **Google Consent Mode**: keep it on if the site uses Google tags; load Google Tag Manager or gtag.js normally.
8. Turn **Preview** off.

After adding cookies or changing what a category does, use **Tools > Ask everyone again**.

## How it works

1. In `<head>`, first (`wp_head` priority -1000): `window.TrustLayerConfig`, a `window.TrustLayer` stub, and the
   Consent Mode defaults per region, followed by an update from the visitor's saved choice. Nothing in the page depends
   on the visitor, so full-page caching keeps working.
2. Before the page is sent, matching scripts and iframes are rewritten so they do not run or load (output buffer,
   one pass).
3. `assets/js/consent.js` (deferred) reads the choice cookie. Without one, it asks `GET /wp-json/trustlayer/v1/region`
   once per browser session (only when regions use different models), applies the model's defaults, and shows the banner.
4. On a choice: the cookie is written, held-back code of allowed categories runs, cookies of refused categories are
   deleted, Consent Mode and UET are updated, `tlc_consent` is pushed to the dataLayer, and the choice is logged with
   `POST /wp-json/trustlayer/v1/consent`.

The choice cookie (`tlc_consent`, first-party, `SameSite=Lax`, 180 days by default) holds
`{ id, v (revision), ts, c: { category: 0|1 }, a (action), m (model), r (region) }`.

Models:

| Model | Before a choice | Banner buttons | GPC |
|---|---|---|---|
| `opt-in` | only strictly necessary runs | Customize, Reject all, Accept all | saved as an opt-out, no banner |
| `opt-out` | everything runs | Customize, Do not sell or share, OK | sale/sharing categories off, saved, no banner |
| `notice` | everything runs | Customize, OK | sale/sharing categories off, saved, no banner |
| `none` | everything runs | no banner (preferences still open from links) | sale/sharing categories off |

## For developers

### Holding back code

```html
<script type="text/plain" data-tlc-category="analytics" src="https://example.com/a.js"></script>
<script type="text/plain" data-tlc-category="marketing">/* inline code */</script>
<script type="text/plain" data-tlc-category="analytics" data-tlc-type="module" src="..."></script>
<iframe data-tlc-category="marketing" data-tlc-src="https://www.youtube.com/embed/..." width="560" height="315"></iframe>
<script data-tlc-skip src="..."></script>   <!-- never blocked automatically -->
```

```php
echo tlc_gated_html( 'marketing', $html ); // any markup, inserted after consent
```

Links that open the banner: `<a href="#tlc-preferences">`, `<a href="#tlc-dnss">`, `[data-tlc-open]`,
`[data-tlc-dnss]`, or your own selectors (General tab). A page opened with `#tlc-preferences` or `#tlc-dnss` in the
address opens that view.

### JavaScript

```js
TrustLayer.ready(function (tl) {          // safe before consent.js has loaded
  if (tl.has('analytics')) { /* ... */ }
});
TrustLayer.get();                          // { id, categories, action, model, region, time } or null
TrustLayer.open('prefs');                  // 'prefs' | 'dnss' | 'banner'
TrustLayer.close();
TrustLayer.acceptAll(); TrustLayer.rejectAll(); TrustLayer.set({ analytics: true });
TrustLayer.model(); TrustLayer.region();   // 'opt-out', 'US-CA'
TrustLayer.on('consent', function (d) {}); // ready | consent | open | close
TrustLayer.reset();                        // forget the choice on this browser and reload (testing)
document.addEventListener('tlc:consent', function (e) { e.detail.categories; e.detail.action; e.detail.initial; });
```

dataLayer, on every page view and every choice:
`{ event: 'tlc_consent', tlc_action, tlc_initial, tlc_model, tlc_necessary, tlc_analytics, tlc_marketing, ... }`
with `granted` / `denied` values.

### PHP

| Function | |
|---|---|
| `tlc_has_consent( 'analytics' )` | The visitor allowed the category (from the cookie; uncached requests only) |
| `tlc_get_consent()` | The stored choice, or null |
| `tlc_categories()` | Enabled categories |
| `tlc_setting( 'section.key' )` | A setting after overrides |
| `tlc_gated_html( $category, $html )` | Markup that loads after consent |

Constants (wp-config.php): `TLC_CONFIG` (array, same shape as the settings, overrides them), `TLC_DISABLE`
(front end off, e.g. on staging), `TLC_MAXMIND_DB` (database file), `TLC_KEEP_DATA` (keep data on uninstall).

```php
define( 'TLC_CONFIG', array(
	'banner' => array( 'layout' => 'bar-bottom' ),
	'geo'    => array( 'models' => array( 'us' => 'opt-in' ) ),
) );
```

### Filters and actions

| Hook | |
|---|---|
| `tlc_defaults` | Default settings. A theme sets its own defaults here; saved settings still win |
| `tlc_settings` | All settings after the saved option and `TLC_CONFIG` (shown as "Set in code" in the admin) |
| `tlc_categories` | Enabled categories |
| `tlc_frontend_config` | What the browser receives (e.g. texts per language) |
| `tlc_should_load` | Banner, Consent Mode, and blocking on this request |
| `tlc_blocker_active`, `tlc_blocker_rules`, `tlc_blocker_presets` | Blocking on a request, the rules, the known services |
| `tlc_geo_lookup` | Another location service, or correct the result: `( $found, $ip )` |
| `tlc_client_ip`, `tlc_region_group`, `tlc_region_model`, `tlc_us_privacy_states` | Geo details |
| `tlc_maxmind_paths` | Where to look for a database file |
| `tlc_consent_mode_defaults` | Consent Mode defaults per model |
| `tlc_log_entry` | A log row before it is stored (return false to skip) |
| `tlc_rest_rate_limit` | Logged choices per address per 10 minutes (20) |
| `tlc_capability` | Admin capability (`manage_options`) |
| `tlc_loaded`, `tlc_settings_saved`, `tlc_consent_logged` | Actions |

### WP-CLI

```
wp tlc export [--file=<file>]       wp tlc import <file>       wp tlc reset
wp tlc revision bump
wp tlc logs export --file=<file> [--from=<Y-m-d>] [--to=<Y-m-d>]       wp tlc logs prune
wp tlc geo [<ip>]                   wp tlc maxmind update
```

### Styling

With "Plugin styles", set the colors in the Banner tab or the CSS variables listed at the top of
`assets/css/consent.css` (`--tlc-bg`, `--tlc-accent`, `--tlc-radius`, ...). Fill in "Theme button classes" to
use the theme's buttons. With "Theme stylesheet only", style the classes yourself: `.tlc` (root, plus `.tlc--box-left`,
`--box-right`, `--bar-bottom`, `--bar-top`, `--modal`), `.tlc-main` / `.tlc-prefs` / `.tlc-dnss` (views),
`.tlc-title`, `.tlc-text`, `.tlc-actions`, `.tlc-btn--primary` / `--secondary`, `.tlc-cats`, `.tlc-cat`, `.tlc-switch`,
`.tlc-track`, `.tlc-store`, `.tlc-embed`, `.tlc-reopen`, `.tlc-backdrop`.

### Files

```
trustlayer-consent.php          bootstrap, autoloader (TLC_Foo_Bar -> includes|admin/class-tlc-foo-bar.php)
includes/class-tlc-plugin.php   wires the modules
includes/class-tlc-settings.php defaults, storage, sanitizing, overrides, import/export
includes/class-tlc-frontend.php head config, assets
includes/class-tlc-consent-mode.php
includes/class-tlc-blocker.php  scripts, iframes, handles, custom code
includes/class-tlc-geo.php      location, groups, models
includes/class-tlc-mmdb-reader.php  MaxMind DB reader (no dependencies)
includes/class-tlc-maxmind.php  GeoLite2 download and updates
includes/class-tlc-log.php      consent log table
includes/class-tlc-rest.php     /region and /consent endpoints
includes/class-tlc-shortcodes.php, class-tlc-privacy.php, class-tlc-cli.php, functions.php
admin/                          settings tabs, consent log list, tools
assets/js/consent.js            the banner (consent.min.js is built from it)
assets/css/consent.css
```

Minified files are built with `npm run build` from the repository root (`scripts/build-assets.mjs`); without
them, or with `SCRIPT_DEBUG`, the plugin loads the readable files.

## Data and uninstall

Option `tlc_settings`, table `{prefix}tlc_consent_log`, options `tlc_db_version` and `tlc_maxmind_status`, cron events
`tlc_prune_log` (daily) and `tlc_maxmind_update` (weekly, with a license key), and
`wp-content/uploads/trustlayer-consent/` (MaxMind database). Uninstalling removes all of it, unless `TLC_KEEP_DATA` is
true. Export the consent log first if you need to keep proof of consent.

GeoLite2 data is created by MaxMind and available from https://www.maxmind.com.

## Not legal advice

The defaults follow common readings of GDPR, the UK GDPR, and US state privacy laws (CCPA/CPRA and others).
Have the site's privacy counsel confirm the models, categories, and texts for your business.
