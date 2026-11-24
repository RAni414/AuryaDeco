(function () {
  'use strict';
  var palette = document.querySelector('[data-pot-palette]');
  var select = document.getElementById('pot-size');
  if (!palette || !select) return;
  var colors;
  try { colors = JSON.parse(palette.dataset.potPalette); } catch (_) { return; }
  var options = Array.from(select.options);
  var buttons = Array.from(palette.querySelectorAll('[data-pot-color]'));
  var main = document.getElementById('mainMedia');
  var image = main && main.querySelector('img');
  var originalImage = image ? {src:image.src, alt:image.alt} : null;
  var art = main && main.querySelector('.art-box');
  var originalThumbs = main && main.parentElement.querySelector('.thumbs');
  var colorThumbs = null;
  function showPhotos(color) {
    if (!main) return;
    var photos = color.photos || [];
    if (!photos.length) {
      if (colorThumbs) colorThumbs.hidden = true;
      if (originalThumbs) originalThumbs.hidden = false;
      if (image) {
        image.hidden = !originalImage;
        if (originalImage) { image.src = originalImage.src; image.alt = originalImage.alt; }
      }
      if (art) art.hidden = false;
      return;
    }
    if (!image) {
      image = document.createElement('img'); image.className = 'ph';
      (main.querySelector('.favorite-media') || main).prepend(image);
    }
    image.hidden = false; image.src = photos[0]; image.alt = color.name;
    if (art) art.hidden = true;
    if (originalThumbs) originalThumbs.hidden = true;
    if (!colorThumbs) {
      colorThumbs = document.createElement('div'); colorThumbs.className = 'thumbs pot-color-thumbs';
      main.insertAdjacentElement('afterend', colorThumbs);
    }
    colorThumbs.replaceChildren(); colorThumbs.hidden = photos.length < 2;
    photos.forEach(function (src, index) {
      var button = document.createElement('button'); button.type = 'button'; button.dataset.src = src;
      button.setAttribute('aria-label', color.name + ' — photo ' + (index + 1));
      button.setAttribute('aria-pressed', String(index === 0));
      var thumb = document.createElement('img'); thumb.className = 'ph'; thumb.src = src; thumb.alt = '';
      button.appendChild(thumb); colorThumbs.appendChild(button);
    });
  }
  function choose(id) {
    if (!colors[id]) return;
    var previous = select.selectedOptions[0];
    var size = previous && previous.dataset.size;
    options.forEach(function (option) {
      var visible = option.dataset.color === String(id);
      option.hidden = !visible; option.disabled = !visible;
      option.textContent = option.dataset.size + (option.dataset.instock === '1' ? '' : ' (épuisé)');
    });
    var matching = options.filter(function (option) { return !option.disabled; });
    var chosen = matching.find(function (option) { return option.dataset.size === size; }) || matching.find(function (option) { return option.dataset.instock === '1'; }) || matching[0];
    select.disabled = !chosen;
    if (chosen) select.value = chosen.value;
    else select.selectedIndex = -1;
    select.dispatchEvent(new Event('change', {bubbles:true}));
    palette.querySelector('[data-pot-color-name]').textContent = colors[id].name;
    buttons.forEach(function (button) { button.setAttribute('aria-pressed', String(button.dataset.potColor === String(id))); });
    showPhotos(colors[id]);
    var photoNote = document.querySelector('[data-pot-photo-note]');
    if (photoNote) {
      photoNote.hidden = !!colors[id].photos.length || String(id) === '0';
      photoNote.textContent = 'Photo générale du modèle. Teinte sélectionnée : ' + colors[id].name + '.';
    }
  }
  buttons.forEach(function (button) { button.addEventListener('click', function () { choose(button.dataset.potColor); }); });
  palette.hidden = false;
  var firstAvailable = buttons.find(function (button) {
    return options.some(function (option) { return option.dataset.color === button.dataset.potColor && option.dataset.instock === '1'; });
  });
  var firstWithSizes = buttons.find(function (button) {
    return options.some(function (option) { return option.dataset.color === button.dataset.potColor; });
  });
  choose((firstAvailable || firstWithSizes || buttons[0]).dataset.potColor);
})();
