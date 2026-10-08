# Labora Child

The active theme. It inherits everything from the **Labora** parent theme (`../labora`), which holds the design
converted from the HTML site: CSS, JavaScript, fonts, images, templates, menus, and SEO additions.

**Rule of thumb:** change the site here, not in the parent. The parent stays a clean copy of the converted design,
so it can be updated or rebuilt without losing site-specific work.

| To change | Do this in the child theme |
|---|---|
| Styles | Add rules to `assets/css/child.css`. It loads after the parent's `styles.css`, `pages.css`, and `blog.css`, so the same selector wins. Use the design tokens: `var(--ink)`, `var(--green)`, `var(--line)`, `var(--r-lg)`, `var(--fs-h2)` |
| JavaScript | Add code to `assets/js/child.js` (deferred, after the parent's `main.js`) |
| A template | Copy the file from the parent to the same path here (`front-page.php`, `header.php`, `footer.php`, `template-parts/sprite.php` ...) and edit the copy. WordPress uses the child's copy |
| An image, font, or other asset | Put a file at the same path as in the parent's `assets/` (for example `assets/img/og-image.png`). `labora_asset()` serves the child's file instead |
| Homepage structured data | Copy `data/home-schema.json` here and edit it |
| A theme function | Define a function with the same name in this theme's `functions.php` (or a file it requires). The parent's template functions are wrapped in `function_exists()`, and the child's `functions.php` loads first |
| New features | Add a file under `inc/` and `require` it from `functions.php` |

## Forms

`inc/forms.php` defines the site's Contact Form 7 forms (Book a demo, Contact) in code: fields matching the HTML
site's markup, the site's wording for every CF7 message, and default Additional Settings. Run `wp labora forms-setup`
after changing it (`--overwrite-settings` also resets each form's Additional Settings). Print a form with
`labora_form( 'demo' )` or `labora_form( 'contact' )`; CF7's CSS and JS load only on pages that do this.
Validation, repeat blocking, the honeypot, redirects, and analytics events come from the Lead Guard plugin.
`template-parts/demo-form.php` shows the homepage form card and its confirmation panel; CF7 styling is in `child.css`.

After a send, both forms open the **Thank you** page (`lead_guard_redirect: /thank-you/`). `forms-setup` creates that
page (published, noindex in Yoast); its layout is the parent's `page-thank-you.php`, personalized by `pages.js` with
the visitor's first name and request type (copied from Lead Guard's sessionStorage by `inc/forms.php`).

## Cookie consent

The TrustLayer Consent plugin shows the banner. `inc/consent.php` sets its defaults for this site (the wording of
the theme's banner, theme buttons, the footer's "Cookie settings" button, no functional category yet) and the end
of `child.css` maps its CSS variables to the design tokens, so it looks like the theme's own banner. Anything
changed in TrustLayer > Settings wins over these defaults.

`child.css` and `child.js` are only loaded once they contain code (their header comments do not count), so empty
files add no request.

Note: `front-page.php` in the parent builds its image URLs from the parent's `assets/` folder. To swap an image on the
homepage, copy `front-page.php` here and change it, or replace the image in the parent.

After switching themes, menu assignments belong to the active theme: run `wp labora seed-menus` (it keeps existing
menus and only assigns them) or set them in Appearance > Menus > Manage Locations.
