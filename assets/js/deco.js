(function () {
  'use strict';
  var panel = document.querySelector('.deco-panel'), launch = document.querySelector('.deco-launch');
  if (!panel || !launch) return;
  var form = panel.querySelector('form'), select = form.elements.selection;
  var submit = form.querySelector('[type=submit]'), status = panel.querySelector('[data-deco-status]');
  var log = panel.querySelector('.deco-conversation'), preview = panel.querySelector('.deco-preview');
  var fileInput = panel.querySelector('[data-deco-file]'), camera = panel.querySelector('[data-deco-capture]');
  var photo = null, objectURL = null, csrf = '', demo = true, busy = false, loaded = false, controller = null;
  var simulationEnabled = false, simURLs = [], recommendations = panel.querySelector('.deco-recommendations');
  function element(tag, text, className) { var node = document.createElement(tag); if (text) node.textContent = text; if (className) node.className = className; return node; }
  function step(number) { panel.querySelectorAll('[data-deco-step]').forEach(function (node) { node.hidden = node.dataset.decoStep !== String(number); }); }
  launch.hidden = false;
  function message(who, text) {
    var box = document.createElement('div'); box.className = 'deco-message' + (who === 'Vous' ? ' deco-message--user' : '');
    var label = document.createElement('strong'); label.textContent = who;
    box.appendChild(label); box.appendChild(document.createTextNode(text)); log.appendChild(box);
    box.scrollIntoView({ behavior: 'auto', block: 'nearest' });
  }
  async function json(response) {
    var data;
    try { data = await response.json(); } catch (_) { throw new Error('Le serveur ne répond pas correctement. Réessayez plus tard.'); }
    if (!response.ok) throw new Error(data.error || 'La demande a échoué.');
    return data;
  }
  async function load() {
    submit.disabled = true;
    try {
      var data = await json(await fetch(panel.dataset.endpoint, { credentials: 'same-origin', cache: 'no-store' }));
      csrf = data.csrf; demo = data.demo; simulationEnabled = data.simulation;
      panel.querySelector('[data-deco-mode]').textContent = demo ? 'Gemini non configuré · aucune analyse IA' : 'Aurya · Gemini · texte et photos';
      var privacy = panel.querySelector('[data-deco-privacy]');
      privacy.textContent = demo
        ? 'Aucune analyse n’est disponible tant que la boutique n’a pas configuré Gemini côté serveur. Aucune réponse de démonstration n’est présentée comme un conseil IA.'
        : 'Votre demande et la photo sélectionnée seront transmises à Google Gemini. Avec l’offre gratuite, Google peut utiliser ces contenus pour améliorer ses produits. Évitez les visages et informations privées. AuryaDeco ne conserve pas votre photo.';
      if (!demo) {
        var link = document.createElement('a'); link.href = 'https://ai.google.dev/gemini-api/terms'; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.textContent = ' Conditions du service Gemini'; privacy.appendChild(link);
      }
      panel.querySelector('[data-deco-consent]').textContent = 'J’accepte la transmission de ma demande et de la photo sélectionnée à Google Gemini selon les conditions indiquées.';
      var previous = select.value;
      select.replaceChildren(new Option('Laisser Aurya proposer une sélection', ''));
      data.plants.forEach(function (p) {
        var size = p.height ? ' — H ' + p.height + ' cm' : (p.label ? ' — ' + p.label : ' — hauteur non renseignée');
        var option = new Option(p.name + size + (p.stock !== null && Number(p.stock) <= 0 ? ' — indisponible' : ''), p.id + ':' + (p.variant_id || '')); select.add(option);
      });
      select.disabled = false;
      if (!loaded) {
        log.replaceChildren();
        (data.messages || []).forEach(function (item) { message(item.role === 'user' ? 'Vous' : (item.demo ? 'Aurya · démonstration' : 'Aurya'), item.text); });
        var needs = data.needs || {};



        if (data.messages && data.messages.length) step(2);
      } else select.value = previous;
      renderCards(data.cards || []);
      loaded = true; submit.disabled = busy || demo;
      if (demo) status.textContent = 'La boutique doit configurer GEMINI_API_KEY pour activer la conversation et l’analyse photo.';
    } catch (e) { status.textContent = e.message + ' Fermez puis rouvrez l’assistant pour réessayer.'; }
  }
  function close() { panel.hidden = true; document.body.classList.remove('deco-is-open'); launch.setAttribute('aria-expanded', 'false'); launch.focus(); }
  launch.addEventListener('click', function () {
    if (!panel.hidden) { close(); return; }
    panel.hidden = false; document.body.classList.add('deco-is-open'); launch.setAttribute('aria-expanded', 'true'); panel.querySelector('[data-deco-close]').focus();
    if (!busy) load();
  });
  panel.querySelector('[data-deco-close]').addEventListener('click', close);
  panel.addEventListener('keydown', function (e) { if (e.key === 'Escape') { e.preventDefault(); close(); } });
  var photoRevision = 0, preparingPhoto = false;
  function removePhoto() {
    photoRevision++; preparingPhoto = false;
    clearSimulations();
    if (objectURL) URL.revokeObjectURL(objectURL);
    photo = null; objectURL = null; fileInput.value = ''; camera.value = ''; preview.hidden = true;
    preview.querySelector('img').removeAttribute('src');
    form.elements.consent.checked = false;
  }
  panel.querySelector('[data-deco-import]').addEventListener('click', function () { fileInput.click(); });
  panel.querySelector('[data-deco-camera]').addEventListener('click', function () { camera.click(); });
  panel.querySelector('[data-deco-remove]').addEventListener('click', removePhoto);
  panel.querySelector('[data-deco-replace]').addEventListener('click', function () { fileInput.click(); });
  async function choose(input) {
    var file = input.files[0]; if (!file) return;
    removePhoto(); var revision = photoRevision;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 25 * 1024 * 1024) {
      status.textContent = 'Choisissez un JPEG, PNG ou WebP de moins de 25 Mo.'; return;
    }
    preparingPhoto = true; status.textContent = 'Preparation de la photo sur votre appareil...';
    var source = URL.createObjectURL(file);
    try {
      var image = await new Promise(function (resolve, reject) {
        var img = new Image(); img.onload = function () { resolve(img); }; img.onerror = reject; img.src = source;
      });
      if (image.naturalWidth < 100 || image.naturalHeight < 100) throw new Error('Photo trop petite : 100 pixels minimum par cote.');
      var scale = Math.min(1, 1600 / Math.max(image.naturalWidth, image.naturalHeight));
      var canvas = document.createElement('canvas');
      canvas.width = Math.round(image.naturalWidth * scale); canvas.height = Math.round(image.naturalHeight * scale);
      var context = canvas.getContext('2d');
      if (!context) throw new Error('Impossible de preparer cette photo. Essayez une autre image.');
      context.fillStyle = '#ffffff'; context.fillRect(0, 0, canvas.width, canvas.height);
      context.drawImage(image, 0, 0, canvas.width, canvas.height);
      var blob = await new Promise(function (resolve) { canvas.toBlob(resolve, 'image/jpeg', 0.85); });
      if (!blob || blob.size > 5 * 1024 * 1024) throw new Error('Impossible de compresser la photo sous 5 Mo.');
      if (revision !== photoRevision) return;
      photo = new File([blob], 'piece.jpg', {type: 'image/jpeg'});
      objectURL = URL.createObjectURL(photo);
      preview.querySelector('img').src = objectURL; preview.hidden = false;
      status.textContent = 'Photo prete. Elle sera envoyee uniquement apres votre accord.';
    } catch (error) {
      if (revision === photoRevision) status.textContent = error.message || 'Photo illisible. Essayez un JPEG, PNG ou WebP.';
    } finally {
      URL.revokeObjectURL(source);
      if (revision === photoRevision) preparingPhoto = false;
    }
  }
  fileInput.addEventListener('change', function () { choose(fileInput); });
  camera.addEventListener('change', function () { choose(camera); });
  form.addEventListener('submit', async function (event) {
    event.preventDefault(); if (preparingPhoto) { status.textContent = 'Patientez pendant la preparation de la photo.'; return; }
    if (busy || demo || !loaded || !form.reportValidity()) return;
    var text = form.elements.message.value.trim();
    if (!text && photo) text = 'O\u00f9 placer une plante dans cette pi\u00e8ce ?';
    if (!text) { status.textContent = 'Ecrivez un message ou ajoutez une photo.'; return; }
    var selected = select.value.split(':'), body = new FormData();
    body.set('csrf', csrf); body.set('plant', selected[0]); body.set('variant', selected[1] || '');
    body.set('kind', 'artificielle'); body.set('message', text);
    body.set('dimensions', form.elements.dimensions.value);
    body.set('consent', '1');
    ['room','style','budget','max_height'].forEach(function (key) { body.set(key, form.elements[key].value); });
    if (photo) body.set('photo', photo);
    busy = true; submit.disabled = true; form.setAttribute('aria-busy', 'true');
    status.textContent = 'Gemini prépare votre conseil…';
    controller = new AbortController(); var timeout = setTimeout(function () { controller.abort(); }, 95000);
    try {
      var data = await json(await fetch(panel.dataset.endpoint, { method: 'POST', body: body, credentials: 'same-origin', signal: controller.signal }));
      message('Vous', text + (photo ? '\n[Photo jointe]' : ''));
      message('Aurya · Gemini', data.reply);
      renderCards(data.cards || [], data.catalogue_note);
      form.elements.message.value = ''; status.textContent = '';
    } catch (e) { status.textContent = e.name === 'AbortError' ? 'Le délai a été dépassé. Réessayez dans un instant.' : e.message; }
    finally { clearTimeout(timeout); busy = false; controller = null; submit.disabled = demo; form.removeAttribute('aria-busy'); }
  });
  form.querySelector('[data-deco-reset]').addEventListener('click', async function () {
    if (busy || !csrf) return;
    busy = true; submit.disabled = true;
    var body = new FormData(); body.set('csrf', csrf); body.set('action', 'reset');
    try {
      await json(await fetch(panel.dataset.endpoint, { method: 'POST', body: body, credentials: 'same-origin' }));
      log.replaceChildren(); recommendations.replaceChildren(); form.reset(); removePhoto(); step(1); status.textContent = 'Nouvelle conversation prête.';
    } catch (e) { status.textContent = e.message; }
    finally { busy = false; submit.disabled = !loaded || demo; }
  });

  function clearSimulations() {
    simURLs.forEach(function (url) { URL.revokeObjectURL(url); }); simURLs = [];
    panel.querySelectorAll('.deco-comparison').forEach(function (node) { node.remove(); });
  }
  function renderCards(cards, note) {
    clearSimulations(); recommendations.replaceChildren();
    if (note) recommendations.appendChild(element('p', note, 'deco-hint'));
    cards.forEach(function (card) { recommendations.appendChild(productCard(card, false)); });
    if (window.AuryaArt) window.AuryaArt.render(recommendations);
  }
  function productCard(card, isPot) {
    var box = element('article', '', 'deco-product' + (isPot ? ' deco-product--pot' : ''));
    box.dataset.favoriteProduct = card.id;
    if (card.image) {
      var image = element('img'); image.src = card.image; image.alt = card.name; image.loading = 'lazy'; box.appendChild(image);
    } else {
      var art = element('div', '', 'deco-product-art'); art.dataset.art = JSON.stringify(card.art); box.appendChild(art);
      box.appendChild(element('p', 'Illustration · photo produit non disponible', 'deco-hint'));
    }
    box.appendChild(element('h3', card.name));
    box.appendChild(element('strong', card.price_label));
    var dimensions = [];
    if (card.height) dimensions.push('Hauteur : ' + card.height + ' cm');
    if (card.diameter) dimensions.push('Diamètre : ' + card.diameter + ' cm');
    box.appendChild(element('p', dimensions.join(' · ') || 'Dimensions non renseignées', 'deco-hint'));
    box.appendChild(element('p', card.availability, 'deco-hint'));
    var link = element('a', 'Voir le produit'); link.href = card.href; box.appendChild(link);
    var available = card.stock === null || card.stock > 0;
    var purchase = element('div', '', 'deco-purchase'), qtyLabel = element('label', 'Quantité pour ' + card.name), qty = element('input');
    qty.type = 'number'; qty.min = '1'; qty.max = String(card.stock === null ? 99 : Math.min(99, card.stock)); qty.value = '1'; qty.disabled = !available;
    qtyLabel.appendChild(qty); purchase.appendChild(qtyLabel);
    var add = element('button', 'Ajouter au panier'); add.type = 'button'; add.disabled = !available;
    var feedback = element('p', '', 'deco-status'); feedback.setAttribute('role', 'status');
    add.addEventListener('click', async function () {
      if (busy || !qty.reportValidity()) return;
      var body = new FormData(); body.set('action', 'cart'); body.set('csrf', csrf); body.set('variant', card.variant_id); body.set('qty', qty.value);
      add.disabled = true; busy = true;
      try {
        var result = await json(await fetch(panel.dataset.endpoint, { method: 'POST', body: body, credentials: 'same-origin' }));
        feedback.textContent = result.message;
        document.querySelectorAll('.badge[data-n]').forEach(function (badge) { badge.dataset.n = result.count; badge.textContent = result.count; });
        var cartLink = element('a', ' Ouvrir le panier'); cartLink.href = result.cart_url; feedback.appendChild(cartLink);
      } catch (e) { feedback.textContent = e.message; }
      finally { busy = false; add.disabled = !available; }
    });
    purchase.appendChild(add); box.appendChild(purchase); box.appendChild(feedback);
    if (!isPot) {
      var simulation = element('details', '', 'deco-simulation');
      simulation.appendChild(element('summary', 'Voir cette plante chez moi'));
      if (!simulationEnabled || !card.image) {
        simulation.appendChild(element('p', 'Prévisualisation indisponible : ' + (!simulationEnabled ? 'service de simulation non configuré.' : 'ce produit nécessite une vraie photo.'), 'deco-hint'));
      } else {
        var placeLabel = element('label', 'Emplacement souhaité'), place = element('input'); place.type = 'text'; place.maxLength = 500; place.placeholder = 'Par exemple : à droite du canapé'; placeLabel.appendChild(place); simulation.appendChild(placeLabel);
        var consentLabel = element('label', '', 'deco-consent'), consent = element('input'); consent.type = 'checkbox';
        consentLabel.appendChild(consent); consentLabel.appendChild(element('span', 'J’accepte l’envoi à OpenAI de ma photo et de la référence produit pour générer cette simulation indicative.')); simulation.appendChild(consentLabel);
        var generate = element('button', 'Générer la simulation'), progress = element('p', '', 'deco-status'); generate.type = 'button'; progress.setAttribute('role', 'status');
        generate.addEventListener('click', async function () {
          if (busy) return;
          if (!photo) { progress.textContent = 'Importez d’abord une photo de votre pièce.'; return; }
          if (!consent.checked || !place.value.trim()) { progress.textContent = 'Décrivez l’emplacement et acceptez l’envoi avant de continuer.'; return; }
          var original = photo, body = new FormData(); body.set('action', 'simulate'); body.set('csrf', csrf); body.set('variant', card.variant_id); body.set('placement', place.value); body.set('consent', '1'); body.set('photo', original);
          busy = true; generate.disabled = true; progress.textContent = 'Création du photomontage… Cela peut prendre une minute.';
          var abort = new AbortController(), timeout = setTimeout(function () { abort.abort(); }, 110000);
          try {
            var result = await json(await fetch(panel.dataset.endpoint, { method: 'POST', body: body, credentials: 'same-origin', signal: abort.signal }));
            if (photo !== original) { progress.textContent = 'La photo a changé. Relancez la simulation avec la nouvelle photo.'; return; }
            var prior = simulation.querySelector('.deco-comparison'); if (prior) prior.remove();
            var compare = element('div', '', 'deco-comparison'); compare.appendChild(element('p', result.label, 'deco-hint'));
            var before = element('img'), after = element('img'); before.src = URL.createObjectURL(original); simURLs.push(before.src); before.alt = 'Photo originale de votre pièce'; after.src = result.image; after.alt = 'Simulation indicative avec ' + card.name; after.hidden = true;
            var controls = element('div', '', 'deco-photo-actions');
            ['Originale', 'Simulation'].forEach(function (name, index) { var button = element('button', name); button.type = 'button'; button.setAttribute('aria-pressed', index === 0 ? 'true' : 'false'); button.addEventListener('click', function () { before.hidden = index !== 0; after.hidden = index === 0; controls.querySelectorAll('button').forEach(function (b) { b.setAttribute('aria-pressed', b === button ? 'true' : 'false'); }); }); controls.appendChild(button); });
            compare.appendChild(controls); compare.appendChild(before); compare.appendChild(after); simulation.appendChild(compare); progress.textContent = '';
          } catch (e) { progress.textContent = e.name === 'AbortError' ? 'La simulation a dépassé le délai prévu. Réessayez plus tard.' : e.message; }
          finally { clearTimeout(timeout); busy = false; generate.disabled = false; }
        });
        simulation.appendChild(generate); simulation.appendChild(progress);
      }
      box.appendChild(simulation);
      if (card.pot) { box.appendChild(element('p', 'Pot assorti selon l’ouverture conseillée · vendu séparément', 'deco-hint')); box.appendChild(productCard(card.pot, true)); }
      else box.appendChild(element('p', 'Aucun pot assorti proposé dans le budget et les dimensions renseignés.', 'deco-hint'));
    }
    return box;
  }
})();
