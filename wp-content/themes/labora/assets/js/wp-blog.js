/*!
 * Labora blog additions for WordPress: the interactive checklist block and "Was this article helpful?".
 * Vanilla JS, no dependencies. Loaded with `defer` after blog.min.js (the HTML site's file, unchanged).
 */
(function () {
  'use strict';
  var doc = document;
  function $(s, c) { return (c || doc).querySelector(s); }
  function $$(s, c) { return Array.prototype.slice.call((c || doc).querySelectorAll(s)); }

  // Browser storage can be blocked (private windows, site data off): the page works without it
  var store = {
    get: function (k) { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
  };

  // The article's toast (single.php)
  var toast = $('.toast');
  function say(msg) {
    if (!toast) return;
    toast.textContent = msg;
    toast.classList.add('is-visible');
    clearTimeout(say.t);
    say.t = setTimeout(function () { toast.classList.remove('is-visible'); }, 2200);
  }

  function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) return navigator.clipboard.writeText(text);
    return new Promise(function (ok, fail) {
      var ta = doc.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      doc.body.appendChild(ta);
      ta.select();
      try { doc.execCommand('copy') ? ok() : fail(); } catch (e) { fail(); }
      doc.body.removeChild(ta);
    });
  }

  /* ---------- Interactive checklist: progress, ticks kept per reader, copy, reset ---------- */
  $$('[data-checklist]').forEach(function (box) {
    var key = 'labora:checklist:' + box.getAttribute('data-checklist');
    var boxes = $$('input[type="checkbox"]', box);
    var done = $('[data-chk-done]', box);
    var bar = $('.lchk-bar span', box);
    var title = $('h3', box);

    [$('.lchk-count', box), $('.lchk-bar', box), $('.lchk-actions', box)].forEach(function (el) { if (el) el.hidden = false; });

    var update = function (save) {
      var ticked = [];
      boxes.forEach(function (cb) {
        cb.closest('li').classList.toggle('is-done', cb.checked);
        if (cb.checked) ticked.push(cb.value);
      });
      if (done) done.textContent = ticked.length;
      if (bar) bar.style.transform = 'scaleX(' + (boxes.length ? ticked.length / boxes.length : 0) + ')';
      box.classList.toggle('is-complete', ticked.length === boxes.length);
      if (save) store.set(key, ticked);
    };

    var saved = store.get(key);
    if (Array.isArray(saved)) boxes.forEach(function (cb) { cb.checked = saved.indexOf(cb.value) !== -1; });
    boxes.forEach(function (cb) { cb.addEventListener('change', function () { update(true); }); });
    update(false);

    var copyBtn = $('[data-chk-copy]', box);
    if (copyBtn) copyBtn.addEventListener('click', function () {
      var lines = [title ? title.textContent.trim() : '', ''];
      boxes.forEach(function (cb) {
        var text = $('.lchk-text', cb.closest('label'));
        var detail = $('small', text);
        var main = text.firstChild ? text.firstChild.textContent.trim() : text.textContent.trim();
        lines.push((cb.checked ? '[x] ' : '[ ] ') + main + (detail ? ' (' + detail.textContent.trim() + ')' : ''));
      });
      var url = (doc.querySelector('link[rel="canonical"]') || {}).href || location.href;
      lines.push('', 'From: ' + doc.title + '\n' + url);
      copyText(lines.join('\n')).then(function () { say('Checklist copied'); }, function () { say('Copy did not work in this browser'); });
    });

    var resetBtn = $('[data-chk-reset]', box);
    if (resetBtn) resetBtn.addEventListener('click', function () {
      boxes.forEach(function (cb) { cb.checked = false; });
      update(true);
      if (boxes[0]) boxes[0].focus();
    });
  });

  /* ---------- "Was this article helpful?" (inc/feedback.php) ---------- */
  var helpful = $('[data-helpful]');
  if (helpful && window.fetch) {
    var post = helpful.getAttribute('data-helpful');
    var endpoint = helpful.getAttribute('data-endpoint');
    var voteKey = 'labora:helpful:' + post;
    var btns = $$('[data-vote]', helpful);
    var form = $('.helpful-note', helpful);
    var msg = $('.helpful-msg', helpful);
    var send = function (data) {
      data.post = Number(post);
      return fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(data), credentials: 'omit' })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); });
    };
    var answered = function (vote) {
      btns.forEach(function (b) { b.disabled = true; b.setAttribute('aria-pressed', String(b.getAttribute('data-vote') === vote)); });
      helpful.classList.add('is-answered');
    };

    var earlier = store.get(voteKey);
    if (earlier === 'yes' || earlier === 'no') {
      answered(earlier);
      msg.textContent = 'Thanks for your feedback.';
    }

    btns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var vote = btn.getAttribute('data-vote');
        answered(vote);
        msg.textContent = '';
        send({ vote: vote }).then(function () {
          store.set(voteKey, vote);
          if (vote === 'no' && form) {
            form.hidden = false;
            $('textarea', form).focus();
          } else {
            msg.textContent = 'Thanks for your feedback.';
          }
        }, function () {
          btns.forEach(function (b) { b.disabled = false; b.removeAttribute('aria-pressed'); });
          helpful.classList.remove('is-answered');
          msg.textContent = 'That did not go through. Try again in a moment.';
        });
      });
    });

    if (form) form.addEventListener('submit', function (e) {
      e.preventDefault();
      var note = $('textarea', form).value.trim();
      form.hidden = true;
      msg.textContent = 'Thanks for your feedback.';
      if (note) send({ note: note }).then(function () { msg.textContent = 'Thanks. This helps us improve the article.'; }, function () {});
    });
  }
})();
