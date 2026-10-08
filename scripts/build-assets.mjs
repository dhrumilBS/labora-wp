// Minify the theme's and plugins' CSS and JS. Usage: npm install && npm run build
// Same tools and versions as the HTML site's build (scripts/build.mjs there), so output matches it byte for byte.
//   Theme:  assets/css/{styles,pages,blog}.css  -> .min.css
//           assets/js/main.js (with Lenis ahead) and {pages,blog}.js -> .min.js
//   TrustLayer Consent: assets/js/consent.js, assets/css/consent.css -> .min (loaded unless SCRIPT_DEBUG)
import { readFileSync, writeFileSync } from 'node:fs';
import CleanCSS from 'clean-css';
import { minify } from 'terser';

const theme = 'wp-content/themes/labora/assets';
const tlc = 'wp-content/plugins/trustlayer-consent/assets';
const terserOpts = { compress: true, mangle: true, format: { comments: /^!/ } };

function css(src, out) {
  const r = new CleanCSS({ level: 2 }).minify(readFileSync(src, 'utf8'));
  if (r.errors.length) throw new Error(`${src}: ${r.errors.join('\n')}`);
  writeFileSync(out, r.styles);
  console.log(`${out}  ${r.styles.length} bytes`);
}
async function js(src, out, prefix = '') {
  const r = await minify(readFileSync(src, 'utf8'), terserOpts);
  writeFileSync(out, prefix + r.code);
  console.log(`${out}  ${(prefix + r.code).length} bytes`);
}

for (const name of ['styles', 'pages', 'blog']) css(`${theme}/css/${name}.css`, `${theme}/css/${name}.min.css`);

// Lenis (smooth wheel scrolling, MIT) is bundled ahead of main.js so the page makes one script request
const lenisPkg = JSON.parse(readFileSync('node_modules/lenis/package.json', 'utf8'));
const lenis = readFileSync('node_modules/lenis/dist/lenis.min.js', 'utf8').replace(/\n?\/\/# sourceMappingURL=.*$/m, '');
await js(`${theme}/js/main.js`, `${theme}/js/main.min.js`, `/*! Lenis ${lenisPkg.version} | MIT License | (c) darkroom.engineering */\n${lenis}\n`);
for (const name of ['pages', 'blog']) await js(`${theme}/js/${name}.js`, `${theme}/js/${name}.min.js`);

css(`${tlc}/css/consent.css`, `${tlc}/css/consent.min.css`);
await js(`${tlc}/js/consent.js`, `${tlc}/js/consent.min.js`);
