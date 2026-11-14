(function () {
  'use strict';
  var entry = document.querySelector('[data-review-entry]');
  var dialog = document.getElementById('review-dialog');
  if (!entry || !dialog) return;
  var heading = document.querySelector('#avis .rhead > div');
  if (heading) heading.appendChild(entry);
  // The link opens the dedicated form if native dialogs are unavailable.
  if (typeof dialog.showModal !== 'function') return;
  var opener = entry.querySelector('[data-review-open]');
  opener.setAttribute('aria-haspopup', 'dialog');
  opener.setAttribute('aria-controls', dialog.id);
  function open() {
    if (!dialog.open) dialog.showModal();
    var target = dialog.querySelector('.customer-review__success') || dialog.querySelector('input[name="name"]');
    if (target) {
      if (!target.matches('input')) target.setAttribute('tabindex', '-1');
      target.focus();
    }
  }
  opener.addEventListener('click', function (event) { event.preventDefault(); open(); });
  dialog.querySelector('[data-review-close]').addEventListener('click', function () { dialog.close(); });
  dialog.addEventListener('close', function () { opener.focus({preventScroll:true}); });
  // Show confirmation after the successful POST redirect.
  if (window.location.hash === '#laisser-avis') open();
})();
