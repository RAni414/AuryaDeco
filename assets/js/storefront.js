(function () {
  'use strict';
  var filterBox = document.querySelector('[data-shop-filters]');
  if (filterBox && window.matchMedia('(max-width:700px)').matches) filterBox.open = false;
  var form = document.getElementById('buyForm');
  var mobileBar = document.querySelector('[data-mobile-purchase]');
  if (form) {
    var status = form.querySelector('[data-selection-status]');
    var primary = document.getElementById('addBtn');
    function syncPurchase() {
      var select = form.querySelector('select[name=variant_id]');
      var selected = select ? select.selectedOptions[0] : form.querySelector('input[name=variant_id]:checked');
      var available = selected && selected.dataset.instock === '1';
      if (status) {
        status.classList.toggle('is-unavailable', !available);
        status.textContent = !selected ? 'Aucune taille disponible pour cette couleur.' : available ? ('En stock' + (selected.dataset.stock ? ' · ' + selected.dataset.stock + ' disponible(s)' : '')) : 'Cette taille est épuisée.';
      }
      if (mobileBar) {
        mobileBar.querySelector('[data-mobile-price]').textContent = document.getElementById('price').textContent;
        var button = mobileBar.querySelector('[data-mobile-add]'); button.disabled = primary.disabled; button.textContent = primary.textContent;
      }
    }
    form.addEventListener('change', syncPurchase); syncPurchase();
    if (mobileBar && primary && 'IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) { mobileBar.hidden = !(entries[0].boundingClientRect.bottom < 0); }).observe(primary);
    }
  }
  var help = document.querySelector('[data-mobile-help]');
  var tools = help && help.closest('.site-tools');
  if (help && tools) {
    function closeHelp() { tools.classList.remove('help-open'); help.setAttribute('aria-expanded', 'false'); }
    help.addEventListener('click', function () {
      var open = tools.classList.toggle('help-open'); help.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('pointerdown', function (event) { if (!tools.contains(event.target)) closeHelp(); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeHelp(); });
    var assistant = tools.querySelector('.deco-launch'); if (assistant) assistant.addEventListener('click', closeHelp);
  }
})();
