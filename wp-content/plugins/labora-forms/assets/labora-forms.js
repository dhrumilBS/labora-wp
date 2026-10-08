/*
 * Labora Forms: what happens in the browser after a Contact Form 7 form on this site is sent.
 *  - Adds .is-sent to the form's wrapper (the theme shows its confirmation panel).
 *  - Pushes the form's analytics event (data-labora-event) to window.dataLayer.
 *  - If the form has a thank-you page (data-labora-thanks), waits 1.5 s so the confirmation registers, then opens it.
 *    The thank-you page reads { form, topic, name, ts } from sessionStorage (never the URL).
 * Field errors and messages come from the server (labora-forms plugin), so they match with or without JavaScript.
 */
(function () {
  'use strict';

  document.addEventListener('wpcf7mailsent', function (e) {
    var form = e.target && e.target.querySelector ? e.target.querySelector('form') || e.target : null;
    if (!form || !form.hasAttribute('data-labora-form')) return;
    var key = form.getAttribute('data-labora-form');
    var data = {};
    (e.detail && e.detail.inputs || []).forEach(function (i) { data[i.name] = i.value; });

    var wrap = form.closest('[data-labora-form-wrap]') || form.parentElement;
    if (wrap) wrap.classList.add('is-sent');
    var status = wrap && wrap.querySelector('[data-labora-status]');
    if (status) { status.hidden = false; status.focus({ preventScroll: true }); }

    var event = form.getAttribute('data-labora-event');
    if (event && window.dataLayer) {
      var push = { event: event };
      if (data.topic) push.topic = data.topic;
      window.dataLayer.push(push);
    }

    var thanks = form.getAttribute('data-labora-thanks');
    if (!thanks) return;
    try {
      sessionStorage.setItem('labora_thanks', JSON.stringify({
        form: key, topic: data.topic || '', name: String(data.full_name || data.name || '').trim().split(/\s+/)[0].slice(0, 40), ts: Date.now()
      }));
    } catch (err) {}
    setTimeout(function () { location.assign(thanks); }, 1500);
  });
})();
