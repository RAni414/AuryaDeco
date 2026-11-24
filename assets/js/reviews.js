(function () {
  'use strict';
  document.querySelectorAll('[data-review-photo]').forEach(function (input) {
    var container = input.closest('.review-photo');
    var preview = container.querySelector('[data-review-photo-preview]');
    var image = container.querySelector('[data-review-photo-image]');
    var error = container.querySelector('[data-review-photo-error]');
    var objectUrl = null;
    function reset() {
      if (objectUrl) URL.revokeObjectURL(objectUrl);
      objectUrl = null;
      preview.hidden = true;
      image.removeAttribute('src');
    }
    input.addEventListener('change', function () {
      reset();
      error.hidden = true;
      var photo = input.files && input.files[0];
      if (!photo) return;
      if (photo.size > 5 * 1024 * 1024 || !['image/jpeg', 'image/png', 'image/webp'].includes(photo.type)) {
        error.textContent = 'Choisissez une photo JPG, PNG ou WebP de 5 Mo maximum.';
        error.hidden = false;
        input.value = '';
        return;
      }
      objectUrl = URL.createObjectURL(photo);
      image.src = objectUrl;
      preview.hidden = false;
    });
    container.querySelector('[data-review-photo-remove]').addEventListener('click', function () {
      input.value = '';
      error.hidden = true;
      reset();
      input.focus();
    });
    input.form.addEventListener('reset', reset);
  });
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
