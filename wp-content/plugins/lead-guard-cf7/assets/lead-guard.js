/*
 * Lead Guard for Contact Form 7: what happens in the browser after a successful send (see class-behavior.php).
 *  - .is-sent on the closest [data-lead-guard-wrap]; its [data-lead-guard-status] element is shown.
 *  - data-lg-event: pushed to window.dataLayer.
 *  - data-lg-redirect: after data-lg-delay ms, open that page; sessionStorage "lead_guard_sent" holds
 *    { form, id, name, topic, ts } for it.
 */
(function () {
  'use strict';

  document.addEventListener('wpcf7mailsent', function (e) {
    var root = e.target;
    var form = root && root.matches && root.matches('form') ? root : root && root.querySelector && root.querySelector('form');
    if (!form) return;

    var values = {};
    ((e.detail && e.detail.inputs) || []).forEach(function (i) { values[i.name] = i.value; });
    // The first field whose name contains "name" (your-name, full_name, first-name ...), first word only
    var nameKey = Object.keys(values).filter(function (k) { return /name/i.test(k); })[0];
    var name = nameKey ? String(values[nameKey]).trim().split(/\s+/)[0].slice(0, 40) : '';

    var wrap = form.closest('[data-lead-guard-wrap]');
    if (wrap) {
      wrap.classList.add('is-sent');
      var status = wrap.querySelector('[data-lead-guard-status]');
      if (status) { status.hidden = false; try { status.focus({ preventScroll: true }); } catch (err) {} }
    }

    var event = form.getAttribute('data-lg-event');
    if (event && window.dataLayer) {
      var push = { event: event, form: form.getAttribute('data-lg-form') || '' };
      if (values.topic) push.topic = values.topic;
      window.dataLayer.push(push);
    }

    var redirect = form.getAttribute('data-lg-redirect');
    if (!redirect) return;
    try {
      sessionStorage.setItem('lead_guard_sent', JSON.stringify({
        form: form.getAttribute('data-lg-form') || '', id: e.detail ? e.detail.contactFormId : '', name: name, topic: values.topic || '', ts: Date.now()
      }));
    } catch (err) {}
    setTimeout(function () { location.assign(redirect); }, parseInt(form.getAttribute('data-lg-delay') || '1500', 10));
  });
})();
