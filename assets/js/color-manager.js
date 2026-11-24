(function () {
  'use strict';
  var form = document.querySelector('[data-color-manager]');
  if (!form) return;
  var name = form.elements.name;
  var hex = form.elements.hex;
  function previewColor() {
    form.querySelector('[data-color-name]').textContent = name.value.trim() || 'Votre couleur';
    form.querySelector('[data-color-dot]').style.backgroundColor = hex.value;
  }
  name.addEventListener('input', previewColor); hex.addEventListener('input', previewColor);
  var rows = form.querySelector('[data-color-size-rows]');
  var template = form.querySelector('[data-color-size-template]');
  var add = form.querySelector('[data-color-size-add]');
  var next = rows.children.length;
  add.addEventListener('click', function () {
    if (rows.children.length >= 40) return;
    var holder = document.createElement('template');
    holder.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(next++));
    rows.appendChild(holder.content.cloneNode(true));
    rows.lastElementChild.querySelector('input:not([type=hidden])').focus();
    add.disabled = rows.children.length >= 40;
  });
  var fileInput = form.querySelector('input[type=file]');
  if (fileInput) {
    var gallery = document.createElement('div'); gallery.className = 'photos color-manager__uploads';
    fileInput.parentElement.insertAdjacentElement('afterend', gallery);
    var urls = [];
    fileInput.addEventListener('change', function () {
      urls.forEach(function (url) { URL.revokeObjectURL(url); }); urls = [];
      gallery.replaceChildren();
      Array.from(fileInput.files || []).forEach(function (file) {
        if (!/^image\//.test(file.type)) return;
        var photo = document.createElement('div'); photo.className = 'photo';
        var image = document.createElement('img'); image.alt = 'Nouvelle photo : ' + file.name;
        var src = URL.createObjectURL(file); urls.push(src); image.src = src;
        photo.appendChild(image); gallery.appendChild(photo);
      });
    });
  }
})();
