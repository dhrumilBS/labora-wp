/* TrustLayer Consent admin: repeatable rows, color values, confirmations, the IP lookup link. */
(function () {
  'use strict';
  var counter = Date.now();

  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-tlc-add]');
    if (add) {
      e.preventDefault();
      var tpl = document.getElementById(add.getAttribute('data-tlc-add'));
      var target = document.querySelector(add.getAttribute('data-tlc-target'));
      if (!tpl || !target) return;
      // __c__ = category index (from the button, or new), __k__ = row index
      var c = add.getAttribute('data-tlc-c') || String(counter++);
      var markup = tpl.innerHTML.replace(/__c__/g, c).replace(/__k__/g, String(counter++));
      var holder = document.createElement(target.tagName === 'TBODY' ? 'tbody' : 'div');
      holder.innerHTML = markup.trim();
      var row = holder.firstElementChild;
      target.appendChild(row);
      var first = row.querySelector('input[type="text"], textarea');
      if (first) first.focus();
      return;
    }
    var remove = e.target.closest('[data-tlc-remove]');
    if (remove) {
      e.preventDefault();
      var r = remove.closest('[data-tlc-row]');
      if (r) r.parentNode.removeChild(r);
      return;
    }
    var test = e.target.closest('[data-tlc-test-ip]');
    if (test) {
      e.preventDefault();
      var ip = document.getElementById('tlc-test-ip').value.trim();
      var url = new URL(test.href);
      if (ip) url.searchParams.set('tlc_ip', ip); else url.searchParams.delete('tlc_ip');
      location.href = url.toString();
    }
  });

  document.addEventListener('input', function (e) {
    if (e.target.classList && e.target.classList.contains('tlc-color')) {
      var code = e.target.parentNode.querySelector('.tlc-color-value');
      if (code) code.textContent = e.target.value;
    }
  });

  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute && e.target.getAttribute('data-tlc-confirm');
    if (msg && !window.confirm(msg)) e.preventDefault();
  });
})();
