# Labora on WordPress

The Labora marketing site as a custom WordPress theme, converted from the HTML site in `D:/xampp/htdocs/labora`
(design, responsive layouts, animations, and assets are carried over unchanged).

Local site: http://192.168.0.25/labora-wp/ (admin: `/wp-admin/`)

## What is in this repository

Only code and configuration that are safe to share. Everything else is ignored by an allow-list `.gitignore`.

| Path | What it is |
|---|---|
| `wp-content/themes/labora/` | The Labora theme. Design files are in `assets/` (CSS, JS, fonts, images, video), as on the HTML site |
| `wp-content/plugins/labora-forms/` | Contact Form 7 additions: phone and email validation, 24-hour duplicate check (added with the forms) |
| `wp-content/mu-plugins/` | Must-use plugins, if any |
| `plugins.txt` | The third-party plugins and versions this site uses (installed by a script, not committed) |
| `scripts/` | Database backup and restore, plugin install |
| `config/wp-config.example.php` | Template for `wp-config.php` (no real credentials) |
| `config/production/` | Recommended production settings: Nginx and Apache rules, `wp-config` constants, cache and security setup |

**Never committed:** WordPress core, `wp-config.php` (database credentials, keys), uploads, third-party plugins,
database dumps (they contain form leads), logs, `.env` files, and API keys.

## Local setup from scratch

Requirements: PHP 8.3+ (Contact Form 7 6.2 needs it), MariaDB 10.4+ or MySQL 8, Apache with mod_rewrite, and WP-CLI.
PHP extensions: gd, intl, zip, sodium, curl, openssl, mysqli, mbstring, exif, fileinfo.
On this machine WP-CLI is `D:\xampp\tools\wp.bat` (Windows) or `/d/xampp/tools/wp` (Git Bash).

```bash
git clone https://github.com/dhrumilBS/labora-wp.git labora-wp && cd labora-wp
wp core download --version=7.1.3 --skip-content
cp config/wp-config.example.php wp-config.php        # then fill in the database and keys
wp config shuffle-salts
scripts/db-restore.sh backups/<latest dump>.sql.gz http://192.168.0.25/labora-wp   # or: wp core install ...
scripts/install-plugins.sh
wp theme activate labora
```

## Backups

```bash
scripts/db-backup.sh          # Git Bash: backups/labora-wp-YYYY-MM-DD-HHMM.sql.gz, keeps the newest 14
```

```powershell
powershell -ExecutionPolicy Bypass -File scripts\db-backup.ps1     # Windows; see the file for a daily Task Scheduler entry
```

Dumps stay in `backups/` on this machine (git-ignored, because they contain leads and user data). Copy them to a
safe place, such as a private cloud folder, for off-site backup. Uploads (`wp-content/uploads/`) also need their own backup.

Restore, optionally to a different URL (handles serialized data):

```bash
scripts/db-restore.sh backups/labora-wp-2026-10-08-1700.sql.gz [https://www.labora.example]
```

## Plugins

See `plugins.txt`. Notes:
- Contact Form 7 6.2 and later need PHP 8.3, so the live server must run PHP 8.3 or newer.
- HandL UTM Grabber v3 is a premium plugin: put its zip in `backups/plugins/handl-utm-grabber-v3.zip` and run the install script.
  It needs its license key activated to capture UTMs, and it saves its cookies as `Secure`, so tracking only works over HTTPS.
- **Local fixes to third-party plugins** live in `scripts/patches/` and are applied by `scripts/apply-patches.sh`.
  **Run it after every update of a patched plugin** (an update replaces the fixed files). Each patch is safe to run twice,
  refuses to change code it does not recognize, and leaves the untouched vendor file next to the original as `*.orig`.
  - `handl-utm-grabber-v3.php` (tested on 3.0.55): fixes the PHP 8.2+ `${var}` deprecation, moves its ~40 Contact Form 7
    field buttons to CF7's tag-generator API v2, stops `SERVER_NAME` warnings outside web requests (WP-CLI, cron),
    fixes a JavaScript error on every page when the referrer cookie is missing, and lets cookies work on IP-address hosts.
- WP Super Cache and Wordfence are installed but inactive locally; production settings are in `config/production/README.md`.

## This machine (XAMPP)

- PHP was upgraded from 8.2.12 to 8.3.35 on Oct 8, 2026. The old PHP is kept at `D:\xampp\php-8.2.12`.
  To switch back: stop Apache, rename `D:\xampp\php` to `php-8.3.35` and `php-8.2.12` to `php`, then restore
  `D:\xampp\apache\conf\extra\httpd-xampp.conf.bak-2026-10-08`.
- `httpd-xampp.conf` loads PHP 8.3's extension libraries (ICU 72 for intl, libsodium, libssh2, nghttp2, and brotlidec for curl).

## Development workflow

Each page is converted only after its WordPress structure (what is editable, what is fixed) is approved.
The theme reuses the HTML site's CSS and JS files as they are; page templates output the same markup.
