(function () {
  'use strict';
  var config = document.querySelector('[data-card-script]');
  if (!config) return;
  function show(visual, index) {
    var photos = visual.querySelectorAll('.card-photo');
    visual.dataset.slide = String(index);
    photos.forEach(function (photo, i) { photo.classList.toggle('is-active', i === index); });
    visual.querySelectorAll('.card-dots button').forEach(function (dot, i) { dot.setAttribute('aria-pressed', String(i === index)); });
  }
  function init() {
    document.querySelectorAll('[data-card-details]:not([data-card-ready])').forEach(function (visual) {
      visual.dataset.cardReady = '1';
      visual.querySelectorAll('[data-card-cart],[data-card-preview]').forEach(function (b) { b.hidden = false; });
      var photos = visual.querySelectorAll('.card-photo'), gallery = visual.querySelector('.card-gallery');
      if (photos.length < 2) return;
      photos.forEach(function (_, i) {
        var dot = document.createElement('button'); dot.type = 'button'; dot.setAttribute('aria-label', 'Photo ' + (i + 1));
        dot.addEventListener('click', function () { show(visual, i); }); visual.querySelector('.card-dots').appendChild(dot);
      });
      show(visual, 0);
      // In home carousels a horizontal swipe moves products, not their photos.
      if (visual.closest('[data-product-carousel]')) return;
      var start = null, swiped = false;
      gallery.addEventListener('touchstart', function (e) { swiped = false; start = e.touches.length === 1 ? {x:e.touches[0].clientX,y:e.touches[0].clientY} : null; }, {passive:true});
      gallery.addEventListener('touchend', function (e) {
        if (!start || !e.changedTouches.length) return;
        var dx = e.changedTouches[0].clientX - start.x, dy = e.changedTouches[0].clientY - start.y; start = null;
        if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy) * 1.4) {
          swiped = true; show(visual, (Number(visual.dataset.slide || 0) + (dx < 0 ? 1 : -1) + photos.length) % photos.length);
        }
      }, {passive:true});
      gallery.addEventListener('touchcancel', function () { start = null; });
      gallery.addEventListener('click', function (e) { if (swiped) { e.preventDefault(); swiped = false; } });
    });
  }
  var dialog = document.createElement('dialog'); dialog.className = 'card-quick'; dialog.setAttribute('aria-labelledby', 'quick-title');
  dialog.innerHTML = '<button type="button" class="quick-close" aria-label="Fermer l’aperçu">×</button><div class="quick-layout"><div><img class="quick-image" alt=""><div class="quick-controls"><button type="button" data-prev aria-label="Photo précédente">←</button><span aria-live="polite" data-photo-count></span><button type="button" data-next aria-label="Photo suivante">→</button></div><p data-no-photo hidden>Photo non disponible</p></div><div><h2 id="quick-title"></h2><p data-price></p><p data-stock role="status"></p><form method="post"><input type="hidden" name="csrf"><input type="hidden" name="action" value="add"><input type="hidden" name="qty" value="1"><label>Choisir une variante<select name="variant_id" required></select></label><button class="btn" type="submit">Ajouter au panier</button></form><a data-product-link>Voir la fiche produit</a></div></div>';
  document.body.appendChild(dialog);
  var opener = null, current = null, slide = 0;
  function image() {
    var img = dialog.querySelector('img'), has = current.photos.length > 0;
    img.hidden = !has; dialog.querySelector('[data-no-photo]').hidden = has;
    dialog.querySelector('.quick-controls').hidden = current.photos.length < 2;
    if (has) { img.src = current.photos[slide]; img.alt = current.name + ' — photo ' + (slide + 1); }
    else img.removeAttribute('src');
    dialog.querySelector('[data-photo-count]').textContent = (slide + 1) + ' / ' + current.photos.length;
  }
  function variant() {
    var select = dialog.querySelector('select'), v = current.variants.find(function (v) { return String(v.id) === select.value; });
    dialog.querySelector('[data-price]').textContent = v ? v.price : current.price;
    dialog.querySelector('[data-stock]').textContent = v ? (v.available ? 'Disponible' : 'Rupture de stock') : (current.variants.some(function (v) { return v.available; }) ? 'Sélectionnez une variante pour connaître sa disponibilité.' : 'Rupture de stock');
    dialog.querySelector('[type=submit]').disabled = !v || !v.available;
  }
  function open(button) {
    opener = button; current = JSON.parse(button.closest('[data-card-details]').dataset.cardDetails); slide = 0;
    dialog.querySelector('h2').textContent = current.name;
    dialog.querySelector('[data-product-link]').href = current.href;
    var form = dialog.querySelector('form'); form.action = config.dataset.cart; form.elements.csrf.value = config.dataset.csrf;
    var select = form.elements.variant_id; select.replaceChildren(new Option('Choisir une variante', ''));
    current.variants.forEach(function (v) { var option = new Option(v.label + ' — ' + v.price + (v.available ? '' : ' — Rupture de stock'), v.id); option.disabled = !v.available; select.add(option); });
    if (current.variants.length === 1) select.value = current.variants[0].id;
    image(); variant(); dialog.showModal();
    if (button.hasAttribute('data-card-cart') && current.variants.length > 1) select.focus(); else dialog.querySelector('.quick-close').focus();
  }
  document.addEventListener('click', function (e) { var button = e.target.closest('[data-card-preview],[data-card-cart]'); if (!button) return; e.preventDefault(); e.stopPropagation(); open(button); });
  dialog.querySelector('.quick-close').addEventListener('click', function () { dialog.close(); });
  dialog.addEventListener('close', function () { if (opener && opener.isConnected) opener.focus(); });
  dialog.querySelector('select').addEventListener('change', variant);
  dialog.querySelector('[data-prev]').addEventListener('click', function () { slide = (slide - 1 + current.photos.length) % current.photos.length; image(); });
  dialog.querySelector('[data-next]').addEventListener('click', function () { slide = (slide + 1) % current.photos.length; image(); });
  dialog.addEventListener('keydown', function (e) {
    if (e.key !== 'Tab') return;
    var nodes = Array.from(dialog.querySelectorAll('button,input,select,a[href]')).filter(function (n) { return !n.disabled && n.getClientRects().length; });
    var first = nodes[0], last = nodes[nodes.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  });
  init(); new MutationObserver(init).observe(document.body, {childList:true,subtree:true});
})();
