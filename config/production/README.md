# Production setup (final settings are applied on the live server)

These files are starting points. Nothing here is active on the local site.

| File | Use |
|---|---|
| `wp-config-production.php` | Constants for the live `wp-config.php`: errors off, file editor off, `WP_CACHE`, server cron, auto security updates |
| `nginx-wordpress.conf` | Server block for Nginx + PHP-FPM: HTTPS redirects, security headers, blocked sensitive files, WP Super Cache served without PHP, login rate limit |
| `htaccess-production.txt` | The same rules for Apache hosts |

## Caching: WP Super Cache (installed, inactive locally)

1. Add `define( 'WP_CACHE', true );` (included in `wp-config-production.php`) and activate the plugin.
2. Settings > WP Super Cache > Advanced:
   - Caching: **On**, delivery **Expert** (Nginx serves the files; the config above already has the rules). On Apache, choose Expert and let the plugin write its `.htaccess` rules.
   - Don't cache pages for known users: **on**. Compress pages: **off** (the server compresses). Cache rebuild: **on**. 304 browser caching: **on**.
   - Expiry: **3600 s**, garbage collection every **600 s**.
   - Rejected URIs: add `thank-you` (the page reads the visitor's own details in the browser; caching is safe, but this keeps it clearly per-visitor).
3. Preload: on, refresh every **1440 min**, so the first visitor never waits.
4. Contact Form 7 works with page caching (it submits with REST + nonce refresh). Test one form submission after enabling.

## Security: Wordfence (installed, inactive locally)

1. Activate, enter the license key if you have one, and run **Firewall > Optimize the Wordfence Firewall**. On Nginx + PHP-FPM it adds `auto_prepend_file` through `.user.ini`; reload PHP-FPM afterwards.
2. Login Security: **2FA for administrators**, reCAPTCHA off (CF7 forms have their own spam checks).
3. Brute force: lock out after **5** failures, lockout **4 hours**, immediately lock out invalid usernames, and block the `admin` username.
4. Scans: daily, email **critical** issues to the site admin.
5. Rate limiting: "If anyone's requests exceed 240 per minute, throttle"; crawlers 404s over 30 per minute, throttle.
6. Emails: alerts for admin logins from new devices and for plugin vulnerabilities.

## After the first deploy

- `wp search-replace 'http://192.168.0.25/labora-wp' 'https://www.labora.example' --all-tables --precise` (or use `scripts/db-restore.sh dump.sql.gz https://www.labora.example`)
- Settings > Reading: **untick** "Discourage search engines" (it is ticked locally).
- Yoast: check the sitemap at `/sitemap_index.xml` and submit it in Google Search Console.
- Turn on automatic backups (`scripts/db-backup.sh` on cron, plus uploads) and confirm a restore works.
