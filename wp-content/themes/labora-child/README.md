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

`child.css` and `child.js` are only loaded once they contain code (their header comments do not count), so empty
files add no request.

Note: `front-page.php` in the parent builds its image URLs from the parent's `assets/` folder. To swap an image on the
homepage, copy `front-page.php` here and change it, or replace the image in the parent.

After switching themes, menu assignments belong to the active theme: run `wp labora seed-menus` (it keeps existing
menus and only assigns them) or set them in Appearance > Menus > Manage Locations.
