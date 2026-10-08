/*!
 * TrustLayer Consent | GPL-2.0-or-later
 *
 * Reads window.TrustLayerConfig (printed in <head> by the plugin) and:
 *  - decides the consent model for this visitor (opt-in, opt-out, notice, none), asking the site for the region
 *    once per session when the model depends on it
 *  - shows the banner, the preferences center, and the Do Not Sell or Share view
 *  - stores the choice in one first-party cookie and logs it (REST: trustlayer/v1/consent)
 *  - releases blocked scripts, <template> code, and embeds of allowed categories, and removes cookies of
 *    categories that are switched off
 *  - sends Google Consent Mode and Microsoft UET updates, pushes "tlc_consent" to the dataLayer
 *
 * API (window.TrustLayer): get(), has(category), open('prefs'|'dnss'|'banner'), close(), acceptAll(),
 * rejectAll(), set({ analytics: true }), model(), region(), on(event, fn), ready(fn), reset().
 * Events on document: tlc:ready, tlc:consent, tlc:open, tlc:close (details in README.md).
 */
(function () {
  'use strict';

  var cfg = window.TrustLayerConfig;
  var stub = window.TrustLayer;
  if (!cfg || (stub && stub.version)) return;

  var doc = document;
  var T = cfg.texts || {};
  var CATS = cfg.categories || [];
  var OPTIONAL = CATS.filter(function (c) { return !c.locked; });
  var SALE = OPTIONAL.filter(function (c) { return c.saleShare; });
  var NAME = cfg.cookie.name;
  var REGION_KEY = 'tlc_region';
  var gpc = !!(cfg.gpc && navigator.globalPrivacyControl === true);

  var saved = read();          // the stored choice, or null
  var model = cfg.model;       // consent model for this visitor
  var region = '';             // "US-CA", "DE" ... when known
  var state = null;            // { categoryId: true|false } in effect now
  var isReady = false;
  var readyQueue = (stub && stub.q) || [];
  var listeners = {};
  var root = null, backdrop = null, view = null, lastFocus = null, reopenBtn = null;

  /* ---------- Helpers ---------- */

  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
  function el(tag, cls, text) {
    var e = doc.createElement(tag);
    if (cls) e.className = cls;
    if (text != null) e.textContent = text;
    return e;
  }
  function html(tag, cls, markup) { var e = el(tag, cls); e.innerHTML = markup || ''; return e; } // texts are sanitized on save
  function fill(text, cat) { return String(text || '').replace(/\{category\}/g, cat ? cat.title.toLowerCase() : ''); }
  function emit(name, detail) {
    (listeners[name] || []).forEach(function (fn) { try { fn(detail); } catch (e) { setTimeout(function () { throw e; }); } });
    doc.dispatchEvent(new CustomEvent('tlc:' + name, { detail: detail }));
  }
  function uuid() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    var b = new Uint8Array(16);
    (window.crypto || window.msCrypto).getRandomValues(b);
    b[6] = (b[6] & 15) | 64; b[8] = (b[8] & 63) | 128;
    var h = Array.prototype.map.call(b, function (x) { return (x + 256).toString(16).slice(1); }).join('');
    return h.slice(0, 8) + '-' + h.slice(8, 12) + '-' + h.slice(12, 16) + '-' + h.slice(16, 20) + '-' + h.slice(20);
  }
  function catById(id) { for (var i = 0; i < CATS.length; i++) if (CATS[i].id === id) return CATS[i]; return null; }
  function copy(choice) { var o = {}; CATS.forEach(function (c) { o[c.id] = c.locked ? true : !!(choice && choice[c.id]); }); return o; }
  function isBot() { return /bot|crawl|spider|slurp|bingpreview|facebookexternalhit|embedly|quora link preview|pinterestbot|vkshare|w3c_validator|whatsapp|lighthouse-ci/i.test(navigator.userAgent || ''); }

  /* ---------- The consent cookie: { id, v, ts, c: { cat: 0|1 }, a, m, r } ---------- */

  function read() {
    var m = doc.cookie.match(new RegExp('(?:^|; )' + NAME.replace(/[-]/g, '\\-') + '=([^;]*)'));
    if (!m) return null;
    try {
      var v = JSON.parse(decodeURIComponent(m[1]));
      return v && v.v === cfg.revision && v.c && typeof v.c === 'object' ? v : null;
    } catch (e) { return null; }
  }
  function write(choice, action) {
    var c = {};
    CATS.forEach(function (cat) { c[cat.id] = choice[cat.id] ? 1 : 0; });
    var value = { id: (saved && saved.id) || uuid(), v: cfg.revision, ts: Math.floor(Date.now() / 1000), c: c, a: action, m: model, r: region };
    doc.cookie = NAME + '=' + encodeURIComponent(JSON.stringify(value)) + '; Max-Age=' + cfg.cookie.days * 86400 + '; Path=/; SameSite=Lax' +
      (cfg.cookie.domain ? '; Domain=' + cfg.cookie.domain : '') + (location.protocol === 'https:' ? '; Secure' : '');
    return value;
  }
  function toChoice(stored) { var o = {}; CATS.forEach(function (c) { o[c.id] = c.locked || !!stored.c[c.id]; }); return o; }

  /** What applies before any choice, for a model. */
  function defaultsFor(m) {
    var o = {};
    CATS.forEach(function (c) {
      o[c.id] = c.locked ? true : m !== 'opt-in';
      if (!c.locked && gpc && c.saleShare) o[c.id] = false;
    });
    return o;
  }

  /* ---------- Applying a choice ---------- */

  function allowed(id) { var c = catById(id); return !!(c && (c.locked || (state && state[id]))); }

  function makeScript(old) {
    var s = doc.createElement('script');
    Array.prototype.forEach.call(old.attributes, function (a) {
      if (a.name === 'type' || a.name === 'data-tlc-category' || a.name === 'data-tlc-type' || (cfg.legacy && a.name === cfg.legacy)) return;
      s.setAttribute(a.name, a.value);
    });
    var type = old.getAttribute('data-tlc-type') || (old.type && old.type !== 'text/plain' ? old.type : '');
    if (type) s.type = type;
    if (old.src || old.getAttribute('src')) s.async = old.hasAttribute('async'); // keep the order of the page
    else s.text = old.text || old.textContent;
    return s;
  }

  function release() {
    var sel = 'script[type="text/plain"][data-tlc-category]' + (cfg.legacy ? ',script[type="text/plain"][' + cfg.legacy + ']' : '');
    $$(sel).forEach(function (old) {
      if (!allowed(old.getAttribute('data-tlc-category') || old.getAttribute(cfg.legacy))) return;
      old.parentNode.replaceChild(makeScript(old), old);
    });
    $$('template[data-tlc-category]').forEach(function (tpl) {
      if (!allowed(tpl.getAttribute('data-tlc-category'))) return;
      var frag = tpl.content.cloneNode(true);
      $$('script', frag).forEach(function (old) { old.parentNode.replaceChild(makeScript(old), old); });
      tpl.parentNode.insertBefore(frag, tpl);
      tpl.parentNode.removeChild(tpl);
    });
    $$('iframe[data-tlc-src][data-tlc-category]').forEach(function (frame) {
      var id = frame.getAttribute('data-tlc-category');
      var holder = frame.previousElementSibling && frame.previousElementSibling.hasAttribute('data-tlc-embed') ? frame.previousElementSibling : null;
      if (allowed(id)) {
        frame.src = frame.getAttribute('data-tlc-src');
        frame.removeAttribute('data-tlc-src');
        frame.hidden = false;
        if (holder) holder.parentNode.removeChild(holder);
      } else if (!holder) {
        placeholder(frame, catById(id));
      }
    });
  }

  function placeholder(frame, cat) {
    if (!cat) return;
    var box = el('div', 'tlc-embed');
    box.setAttribute('data-tlc-embed', '');
    var w = parseInt(frame.getAttribute('width'), 10), h = parseInt(frame.getAttribute('height'), 10);
    if (w && h) box.style.aspectRatio = w + ' / ' + h;
    box.appendChild(el('p', null, fill(T.embed_text, cat)));
    var b = el('button', btnClass('primary'), T.embed_button);
    b.type = 'button';
    b.setAttribute('data-tlc-allow', cat.id);
    box.appendChild(b);
    frame.hidden = true;
    frame.parentNode.insertBefore(box, frame);
  }

  function deleteCookies(choice) {
    var names = doc.cookie ? doc.cookie.split('; ').map(function (c) { return c.split('=')[0]; }) : [];
    if (!names.length) return;
    var host = location.hostname, parts = host.split('.'), domains = [''];
    for (var i = 0; i < parts.length - 1; i++) { var d = parts.slice(i).join('.'); domains.push(d, '.' + d); }
    OPTIONAL.forEach(function (cat) {
      if (choice[cat.id]) return;
      cat.cookies.forEach(function (row) {
        var pattern = row[0];
        names.forEach(function (n) {
          var hit = pattern.slice(-1) === '*' ? n.indexOf(pattern.slice(0, -1)) === 0 : n === pattern;
          if (!hit || n === NAME) return;
          domains.forEach(function (d) { doc.cookie = n + '=; Max-Age=0; Path=/' + (d ? '; Domain=' + d : ''); });
        });
      });
    });
  }

  function signals(choice) {
    var u = {};
    CATS.forEach(function (c) { c.gcm.forEach(function (g) { u[g] = u[g] === 'granted' || choice[c.id] ? 'granted' : 'denied'; }); });
    return u;
  }

  function apply(choice, action, initial) {
    var before = state;
    state = copy(choice);
    release();
    if (!initial) {
      var u = signals(state);
      if (cfg.gcm && typeof window.gtag === 'function') window.gtag('consent', 'update', u);
      if (cfg.uet && u.ad_storage) { window.uetq = window.uetq || []; window.uetq.push('consent', 'update', { ad_storage: u.ad_storage }); }
    }
    deleteCookies(state);
    var push = { event: 'tlc_consent', tlc_action: action || '', tlc_initial: !!initial, tlc_model: model };
    CATS.forEach(function (c) { push['tlc_' + c.id] = state[c.id] ? 'granted' : 'denied'; });
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(push);
    emit('consent', { categories: copy(state), action: action || '', initial: !!initial, model: model, region: region });
    details();
    // Scripts that already ran cannot be stopped: reload so a revoked category is really gone
    if (!initial && cfg.reload && before && OPTIONAL.some(function (c) { return before[c.id] && !state[c.id]; })) location.reload();
  }

  function log(action) {
    if (!cfg.log || !saved || !window.fetch) return;
    try {
      fetch(cfg.rest.consent, {
        method: 'POST', keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ consent_id: saved.id, action: action, categories: saved.c, model: model, region: region, revision: cfg.revision, url: location.href })
      }).catch(function () {});
    } catch (e) {}
  }

  function save(choice, action) {
    saved = write(choice, action);
    apply(choice, action, false);
    log(action);
  }

  /* ---------- Region ---------- */

  function resolveRegion(done) {
    if (!cfg.geo) return done();
    var finish = function (d) {
      if (d && d.model) {
        model = d.model;
        region = d.country ? d.country + (d.region ? '-' + d.region : '') : '';
      }
      done();
    };
    try {
      var cached = JSON.parse(sessionStorage.getItem(REGION_KEY) || 'null');
      if (cached && cached.rev === cfg.revision) return finish(cached);
    } catch (e) {}
    var ctrl = window.AbortController ? new AbortController() : null;
    var timer = setTimeout(function () { if (ctrl) ctrl.abort(); }, 4000);
    fetch(cfg.rest.region, { credentials: 'omit', signal: ctrl ? ctrl.signal : undefined })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        clearTimeout(timer);
        if (d && d.model) { d.rev = cfg.revision; try { sessionStorage.setItem(REGION_KEY, JSON.stringify(d)); } catch (e) {} }
        finish(d);
      })
      .catch(function () { clearTimeout(timer); finish(null); });
  }

  /* ---------- The banner ---------- */

  function btnClass(kind) {
    // Theme button classes (Banner tab) replace the plugin's own button look (tlc-btn--skin)
    var extra = kind === 'primary' ? cfg.btn.primary : cfg.btn.secondary;
    return 'tlc-btn tlc-btn--' + kind + (extra ? ' ' + extra : ' tlc-btn--skin');
  }
  function button(kind, label, action) {
    var b = el('button', btnClass(kind), label);
    b.type = 'button';
    b.setAttribute('data-tlc-action', action);
    return b;
  }
  function heading(id, text) { var h = el('h2', 'tlc-title', text); h.id = id; h.tabIndex = -1; return h; }

  function policyLinks(p) {
    var links = [];
    if (cfg.policy.cookie) links.push([cfg.policy.cookie, T.cookie_policy]);
    if (cfg.policy.privacy) links.push([cfg.policy.privacy, T.privacy_policy]);
    links.forEach(function (l, i) {
      p.appendChild(doc.createTextNode(i ? ' · ' : ' '));
      var a = el('a', 'tlc-policy', l[1]);
      a.href = l[0];
      p.appendChild(a);
    });
  }

  function buildMain() {
    var wrap = el('div', 'tlc-view tlc-main');
    var title = model === 'opt-out' ? T.optout_title : model === 'notice' ? T.notice_title : T.banner_title;
    var text = model === 'opt-out' ? T.optout_text : model === 'notice' ? T.notice_text : T.banner_text;
    wrap.appendChild(heading('tlc-title', title));
    var p = html('p', 'tlc-text', text);
    p.id = 'tlc-desc';
    policyLinks(p);
    wrap.appendChild(p);
    var actions = el('div', 'tlc-actions');
    actions.appendChild(button('secondary', T.customize, 'customize'));
    if (model === 'opt-out') {
      if (SALE.length) actions.appendChild(button('secondary', T.opt_out, 'opt-out'));
      actions.appendChild(button('primary', T.ok, 'ok'));
    } else if (model === 'notice') {
      actions.appendChild(button('primary', T.ok, 'ok'));
    } else {
      if (cfg.showReject) actions.appendChild(button(cfg.equalButtons ? 'primary' : 'secondary', T.reject_all, 'reject'));
      actions.appendChild(button('primary', T.accept_all, 'accept'));
    }
    actions.setAttribute('data-count', actions.children.length);
    wrap.appendChild(actions);
    return wrap;
  }

  function switchRow(name, title, checked, locked, cls) {
    var label = el('label', 'tlc-switch');
    label.appendChild(el('span', 'tlc-cat-title', title));
    if (locked) label.appendChild(el('span', 'tlc-always', T.always_on));
    var input = el('input');
    input.type = 'checkbox';
    input.setAttribute('role', 'switch');
    input.name = name;
    input.checked = locked || checked;
    input.disabled = !!locked;
    label.appendChild(input);
    label.appendChild(el('span', 'tlc-track'));
    var head = el('div', cls || 'tlc-cat-head');
    head.appendChild(label);
    return head;
  }

  function buildPrefs() {
    var current = state || defaultsFor(model);
    var wrap = el('div', 'tlc-view tlc-prefs');
    wrap.appendChild(heading('tlc-prefs-title', T.prefs_title));
    wrap.appendChild(html('p', 'tlc-text tlc-sub', T.prefs_text));
    if (gpc && SALE.length) wrap.appendChild(el('p', 'tlc-text tlc-gpc', T.gpc_notice));
    var list = el('ul', 'tlc-cats');
    CATS.forEach(function (c) {
      var li = el('li', 'tlc-cat');
      li.appendChild(switchRow(c.id, c.title, current[c.id], c.locked));
      li.appendChild(html('p', 'tlc-text tlc-cat-desc', c.description));
      var det = el('details', 'tlc-store');
      det.appendChild(el('summary', null, T.what_we_store));
      if (c.cookies.length) {
        var withProvider = c.cookies.some(function (r) { return r[1]; });
        var tbl = el('table'), thead = el('thead'), tb = el('tbody'), hr = el('tr');
        [T.col_cookie].concat(withProvider ? [T.col_provider] : [], [T.col_purpose, T.col_duration]).forEach(function (x) {
          var th = el('th', null, x); th.scope = 'col'; hr.appendChild(th);
        });
        thead.appendChild(hr); tbl.appendChild(thead);
        c.cookies.forEach(function (r) {
          var tr = el('tr');
          [r[0]].concat(withProvider ? [r[1]] : [], [r[2], r[3]]).forEach(function (x) { tr.appendChild(el('td', null, x)); });
          tb.appendChild(tr);
        });
        tbl.appendChild(tb); det.appendChild(tbl);
      } else {
        det.appendChild(el('p', 'tlc-text', fill(T.nothing_stored, c)));
      }
      li.appendChild(det);
      list.appendChild(li);
    });
    wrap.appendChild(list);
    var actions = el('div', 'tlc-actions');
    actions.appendChild(button(cfg.equalButtons ? 'primary' : 'secondary', T.reject_all, 'reject'));
    actions.appendChild(button('secondary', T.save, 'save'));
    actions.appendChild(button('primary', T.accept_all, 'accept'));
    actions.setAttribute('data-count', 3);
    wrap.appendChild(actions);
    return wrap;
  }

  function buildDnss() {
    var current = state || defaultsFor(model);
    var wrap = el('div', 'tlc-view tlc-dnss');
    wrap.appendChild(heading('tlc-dnss-title', T.dnss_title));
    wrap.appendChild(html('p', 'tlc-text', T.dnss_text));
    if (gpc) wrap.appendChild(el('p', 'tlc-text tlc-gpc', T.gpc_notice));
    var on = SALE.length ? SALE.every(function (c) { return current[c.id]; }) : false;
    var list = el('ul', 'tlc-cats');
    var li = el('li', 'tlc-cat');
    li.appendChild(switchRow('tlc-sale', T.dnss_toggle, on, false));
    list.appendChild(li);
    wrap.appendChild(list);
    var actions = el('div', 'tlc-actions');
    actions.appendChild(button('primary', T.save, 'dnss-save'));
    actions.setAttribute('data-count', 1);
    wrap.appendChild(actions);
    return wrap;
  }

  function show(name) {
    view = name;
    root.innerHTML = '';
    var part = name === 'prefs' ? buildPrefs() : name === 'dnss' ? buildDnss() : buildMain();
    root.appendChild(part);
    var titleId = part.querySelector('.tlc-title').id;
    root.setAttribute('aria-labelledby', titleId);
    if (name === 'main') root.setAttribute('aria-describedby', 'tlc-desc'); else root.removeAttribute('aria-describedby');
    // Bars become a centered dialog for the longer views
    var modal = cfg.layout === 'modal' || (name !== 'main' && /^bar/.test(cfg.layout));
    root.classList.toggle('tlc--modal', modal);
    root.setAttribute('aria-modal', modal ? 'true' : 'false');
    if (modal && !backdrop) {
      backdrop = el('div', 'tlc-backdrop');
      backdrop.addEventListener('click', function () { if (saved) close(); });
      root.parentNode.insertBefore(backdrop, root);
      requestAnimationFrame(function () { if (backdrop) backdrop.classList.add('is-in'); });
    } else if (!modal && backdrop) {
      backdrop.parentNode.removeChild(backdrop);
      backdrop = null;
    }
    doc.documentElement.classList.toggle('tlc-lock', modal);
  }

  function focusTitle() { var h = root && root.querySelector('.tlc-title'); if (h) h.focus({ preventScroll: true }); }

  function open(name, opts) {
    name = name === 'banner' ? 'main' : name || 'prefs';
    opts = opts || {};
    if (!root) {
      lastFocus = doc.activeElement;
      root = el('section', 'tlc tlc--' + cfg.layout);
      root.setAttribute('role', 'dialog');
      root.setAttribute('data-model', model);
      root.addEventListener('click', onClick);
      root.addEventListener('keydown', onKey);
      doc.body.appendChild(root);
      show(name);
      requestAnimationFrame(function () { if (root) root.classList.add('is-in'); });
      hideReopen();
    } else {
      show(name);
    }
    if (opts.focus || name !== 'main' || root.classList.contains('tlc--modal')) focusTitle();
    emit('open', { view: view });
  }

  function close() {
    if (!root) return;
    var r = root, b = backdrop;
    root = null; backdrop = null; view = null;
    r.classList.remove('is-in');
    if (b) b.classList.remove('is-in');
    doc.documentElement.classList.remove('tlc-lock');
    setTimeout(function () { if (r.parentNode) r.parentNode.removeChild(r); if (b && b.parentNode) b.parentNode.removeChild(b); }, 250);
    if (lastFocus && lastFocus.focus && lastFocus !== doc.body) lastFocus.focus({ preventScroll: true });
    showReopen();
    emit('close', {});
  }

  function choiceFromSwitches() {
    var o = {};
    CATS.forEach(function (c) {
      var input = root.querySelector('input[name="' + c.id + '"]');
      o[c.id] = c.locked || !!(input && input.checked);
    });
    return o;
  }

  function all(on) { var o = {}; CATS.forEach(function (c) { o[c.id] = c.locked || on; }); return o; }

  function onClick(e) {
    var b = e.target.closest('[data-tlc-action]');
    if (!b) return;
    var act = b.getAttribute('data-tlc-action');
    if (act === 'customize') { show('prefs'); focusTitle(); return; }
    if (act === 'accept') save(all(true), 'accept_all');
    else if (act === 'reject') save(all(false), 'reject_all');
    else if (act === 'save') save(choiceFromSwitches(), 'custom');
    else if (act === 'ok') save(state || defaultsFor(model), model === 'notice' ? 'acknowledge' : 'accept_all');
    else if (act === 'opt-out') {
      var o = copy(state || defaultsFor(model));
      SALE.forEach(function (c) { o[c.id] = false; });
      save(o, 'opt_out');
    } else if (act === 'dnss-save') {
      var input = root.querySelector('input[name="tlc-sale"]');
      var d = copy(state || defaultsFor(model));
      SALE.forEach(function (c) { d[c.id] = !!(input && input.checked); });
      save(d, 'dnss');
      var actions = root.querySelector('.tlc-actions');
      actions.innerHTML = '';
      var msg = el('p', 'tlc-text tlc-saved', T.dnss_saved);
      msg.setAttribute('role', 'status');
      actions.appendChild(msg);
      setTimeout(close, 1600);
      return;
    }
    close();
  }

  function onKey(e) {
    if (e.key === 'Escape') {
      if (view !== 'main' && saved) close();
      else if (view !== 'main') { show('main'); focusTitle(); }
      else if (saved) close();
      return;
    }
    if (e.key === 'Tab' && root.classList.contains('tlc--modal')) {
      var f = $$('a[href], button:not([disabled]), input:not([disabled]), summary, [tabindex]:not([tabindex="-1"])', root);
      if (!f.length) return;
      if (e.shiftKey && (doc.activeElement === f[0] || doc.activeElement === root.querySelector('.tlc-title'))) { e.preventDefault(); f[f.length - 1].focus(); }
      else if (!e.shiftKey && doc.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
    }
  }

  /* ---------- Reopen button, links, details ---------- */

  function showReopen() {
    if (!cfg.reopen || !saved || root || reopenBtn) return;
    reopenBtn = el('button', 'tlc-reopen' + (cfg.layout === 'box-right' ? ' tlc-reopen--right' : ''));
    reopenBtn.type = 'button';
    reopenBtn.setAttribute('aria-label', T.reopen);
    reopenBtn.title = T.reopen;
    reopenBtn.innerHTML = '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 1 0 9 9 4 4 0 0 1-4-4 4 4 0 0 1-4-4 1 1 0 0 0-1-1Z"/><circle cx="8.5" cy="11.5" r="1" fill="currentColor"/><circle cx="12" cy="16" r="1" fill="currentColor"/><circle cx="16" cy="13.5" r="1" fill="currentColor"/></svg>';
    reopenBtn.addEventListener('click', function () { open('prefs', { focus: true }); });
    doc.body.appendChild(reopenBtn);
  }
  function hideReopen() { if (reopenBtn) { reopenBtn.parentNode.removeChild(reopenBtn); reopenBtn = null; } }

  var OPEN_SEL = '[data-tlc-open],a[href$="#tlc-preferences"]' + (cfg.open ? ',' + cfg.open : '');
  var DNSS_SEL = '[data-tlc-dnss],a[href$="#tlc-dnss"]' + (cfg.dnssOpen ? ',' + cfg.dnssOpen : '');

  function dnssApplies() { return SALE.length > 0 && (cfg.dnss === 'always' || (cfg.dnss === 'opt-out' && model === 'opt-out')); }
  function links() {
    var on = dnssApplies();
    try { $$(DNSS_SEL).forEach(function (a) { a.classList.toggle('tlc-dnss-on', on); }); } catch (e) {}
  }

  doc.addEventListener('click', function (e) {
    var t = e.target.closest && e.target.closest('[data-tlc-allow]');
    if (t) {
      e.preventDefault();
      var o = copy(state); o[t.getAttribute('data-tlc-allow')] = true;
      save(o, 'custom');
      return;
    }
    try {
      if (e.target.closest(DNSS_SEL)) { e.preventDefault(); open('dnss', { focus: true }); return; }
      if (e.target.closest(OPEN_SEL)) { e.preventDefault(); open('prefs', { focus: true }); }
    } catch (err) {} // an invalid custom selector must not break the page
  });

  function details() {
    $$('[data-tlc-details]').forEach(function (box) {
      var put = function (k, v) { var d = box.querySelector('[data-tlc-detail="' + k + '"]'); if (d) d.textContent = v; };
      if (!saved) return;
      put('id', saved.id);
      put('date', new Date(saved.ts * 1000).toLocaleString());
      put('allowed', CATS.filter(function (c) { return state && state[c.id]; }).map(function (c) { return c.title; }).join(', '));
    });
  }

  /* ---------- Start ---------- */

  function becomeReady() {
    links();
    showReopen();
    isReady = true;
    var q = readyQueue; readyQueue = [];
    q.forEach(function (fn) { try { fn(api); } catch (e) { setTimeout(function () { throw e; }); } });
    emit('ready', { model: model, region: region, choice: saved ? copy(state) : null });
    if (location.hash === '#tlc-preferences') open('prefs', { focus: true });
    else if (location.hash === '#tlc-dnss') open('dnss', { focus: true });
  }

  function start() {
    if (saved) {
      model = saved.m || model;
      region = saved.r || '';
      apply(toChoice(saved), saved.a, true);
      return becomeReady();
    }
    resolveRegion(function () {
      var choice = defaultsFor(model);
      apply(choice, '', true);
      if (model === 'none') return becomeReady();
      if (gpc) { saved = write(choice, 'gpc'); log('gpc'); return becomeReady(); }
      becomeReady();
      if (cfg.bots && isBot()) return;
      var later = function () { setTimeout(function () { if (!read() && !root) open('main'); }, cfg.delay); };
      if (doc.readyState === 'complete') later(); else window.addEventListener('load', later, { once: true });
    });
  }

  var api = {
    version: cfg.version,
    get: function () {
      return saved ? { id: saved.id, categories: copy(state), action: saved.a, model: model, region: region, time: new Date(saved.ts * 1000) } : null;
    },
    has: allowed,
    open: function (name) { open(name || 'prefs', { focus: true }); },
    close: close,
    acceptAll: function () { save(all(true), 'accept_all'); close(); },
    rejectAll: function () { save(all(false), 'reject_all'); close(); },
    set: function (choice) { var o = copy(state); for (var k in choice) if (catById(k)) o[k] = !!choice[k]; save(o, 'custom'); close(); },
    model: function () { return model; },
    region: function () { return region; },
    on: function (name, fn) { (listeners[name] = listeners[name] || []).push(fn); return api; },
    ready: function (fn) { if (isReady) fn(api); else readyQueue.push(fn); return api; },
    reset: function () {
      doc.cookie = NAME + '=; Max-Age=0; Path=/' + (cfg.cookie.domain ? '; Domain=' + cfg.cookie.domain : '');
      try { sessionStorage.removeItem(REGION_KEY); } catch (e) {}
      saved = null;
      location.reload();
    }
  };
  window.TrustLayer = api;

  if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', start, { once: true }); else start();
})();
