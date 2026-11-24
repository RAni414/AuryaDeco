(function () {
  'use strict';
  document.querySelectorAll('[data-review-media]').forEach(function (container) {
    var inputs = Array.from(container.querySelectorAll('[data-review-media-input]'));
    var preview = container.querySelector('[data-review-media-preview]');
    var image = container.querySelector('[data-review-media-image]');
    var name = container.querySelector('[data-review-media-name]');
    var error = container.querySelector('[data-review-media-error]');
    var objectUrl = null;
    function resetPreview() {
      image.removeAttribute('src'); image.hidden = true;
      if (objectUrl) URL.revokeObjectURL(objectUrl); objectUrl = null;
      preview.hidden = true; name.textContent = '';
    }
    inputs.forEach(function (input) {
      input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) return;
        resetPreview(); error.hidden = true;
        inputs.forEach(function (other) { if (other !== input) other.value = ''; });
        var isVideo = input.dataset.reviewMediaInput === 'video';
        var types = isVideo ? ['video/mp4', 'video/webm'] : ['image/jpeg', 'image/png', 'image/webp'];
        var limit = (isVideo ? 25 : 5) * 1024 * 1024;
        if (!types.includes(file.type) || file.size > limit) {
          error.textContent = isVideo ? 'Choisissez une vidéo MP4 ou WebM de 25 Mo maximum.' : 'Choisissez une photo JPG, PNG ou WebP de 5 Mo maximum.';
          error.hidden = false; input.value = ''; return;
        }
        if (!isVideo) { objectUrl = URL.createObjectURL(file); image.src = objectUrl; image.hidden = false; }
        name.textContent = file.name; preview.hidden = false;
      });
    });
    container.querySelector('[data-review-media-remove]').addEventListener('click', function () {
      inputs.forEach(function (input) { input.value = ''; }); error.hidden = true; resetPreview(); inputs[0].focus();
    });
    inputs[0].form.addEventListener('reset', resetPreview);
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
  dialog.addEventListener('close', function () { dialog.querySelectorAll('video').forEach(function (video) { video.pause(); }); opener.focus({preventScroll:true}); });
  // Show confirmation after the successful POST redirect.
  if (window.location.hash === '#laisser-avis') open();
})();
