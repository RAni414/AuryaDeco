(function () {
  // Confirmation avant suppression
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.dataset.confirm && !window.confirm(f.dataset.confirm)) e.preventDefault();
  });

  var form = document.getElementById('edForm');
  if (!form) return;

  // Catégorie : affiche les bons champs
  var kind = document.getElementById('kindSel'), head = document.getElementById('sizeHead');
  var heads = { plante: 'Hauteur (cm)', pot: "Diamètre d'ouverture (cm)", acc: 'Format (ex. Sac de 2 kg)' };
  kind.addEventListener('change', function () {
    form.dataset.kind = kind.value;
    head.textContent = heads[kind.value];
  });

  // Tailles : ajouter / retirer une ligne
  var tbody = document.querySelector('#vTable tbody');
  document.getElementById('vAdd').addEventListener('click', function () {
    var last = tbody.querySelector('tr:last-child');
    var row = last.cloneNode(true);
    row.querySelectorAll('input').forEach(function (i) { i.value = ''; });
    row.querySelector('input[name="v[id][]"]').value = '0';
    tbody.appendChild(row);
    row.querySelector(form.dataset.kind === 'acc' ? '.sz-txt' : '.sz-num').focus();
    syncRemove();
  });
  tbody.addEventListener('click', function (e) {
    if (!e.target.matches('[data-vrm]')) return;
    if (tbody.rows.length > 1) e.target.closest('tr').remove();
    syncRemove();
  });
  function syncRemove() {
    var one = tbody.rows.length < 2;
    tbody.querySelectorAll('[data-vrm]').forEach(function (b) { b.disabled = one; });
  }
  syncRemove();

  // Aperçu des nouvelles photos
  document.querySelectorAll('[data-photo-input]').forEach(function (input) {
  var prev = document.getElementById(input.dataset.photoInput);
  input.addEventListener('change', function () {
    prev.querySelectorAll('img').forEach(function (img) { URL.revokeObjectURL(img.src); });
    prev.innerHTML = '';
    Array.prototype.forEach.call(input.files, function (f) {
      if (!/^image\//.test(f.type)) return;
      var d = document.createElement('div'); d.className = 'photo';
      var img = document.createElement('img'); img.src = URL.createObjectURL(f); img.alt = '';
      d.appendChild(img); prev.appendChild(d);
    });
  });
  });
})();
