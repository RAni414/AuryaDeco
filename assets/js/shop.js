(function () {
  // Menu mobile
  var btn = document.getElementById('menuBtn'), nav = document.getElementById('mainNav');
  if (btn && nav) btn.addEventListener('click', function () {
    var open = nav.classList.toggle('open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.setAttribute('aria-label', open ? 'Fermer le menu' : 'Ouvrir le menu');
  });
  if (btn && nav) nav.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { nav.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); btn.setAttribute('aria-label', 'Ouvrir le menu'); btn.focus(); }
  });

  // Boutons + / − des quantités
  document.addEventListener('click', function (e) {
    var s = e.target.closest('[data-step]');
    if (!s) return;
    var input = s.parentElement.querySelector('input[type=number]');
    var min = parseInt(input.min || '0', 10), max = parseInt(input.max || '99', 10);
    var v = Math.min(max, Math.max(min, (parseInt(input.value, 10) || 0) + parseInt(s.dataset.step, 10)));
    input.value = v;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });

  // Fiche produit : prix et disponibilité selon la taille choisie
  var form = document.getElementById('buyForm');
  if (form) {
    var price = document.getElementById('price'), old = document.getElementById('oldPrice'),
        oos = document.getElementById('oosMsg'), add = document.getElementById('addBtn');
    form.addEventListener('change', function (e) {
      if (e.target.name !== 'variant_id') return;
      var r = e.target.tagName === 'SELECT' ? e.target.selectedOptions[0] : e.target;
      price.textContent = r.dataset.price;
      old.textContent = r.dataset.old; old.hidden = !r.dataset.old;
      var ok = r.dataset.instock === '1';
      oos.hidden = ok; add.disabled = !ok; add.textContent = ok ? 'Ajouter au panier' : 'Rupture de stock';
    });
  }

  // Galerie photos
  document.addEventListener('click', function (e) {
    var t = e.target.closest('.thumbs button');
    if (!t) return;
    var main = document.querySelector('#mainMedia img');
    if (main) main.src = t.dataset.src;
    t.parentElement.querySelectorAll('button').forEach(function (b) { b.setAttribute('aria-pressed', b === t ? 'true' : 'false'); });
  });

  // Filtres de la boutique : envoi automatique
  document.querySelectorAll('form[data-autosubmit] select').forEach(function (sel) {
    sel.addEventListener('change', function () {
      var f = sel.form;
      (sel.dataset.reset || '').split(',').forEach(function (n) {
        var h = n && f.querySelector('input[type=hidden][name="' + n + '"]');
        if (h) h.remove();
      });
      f.submit();
    });
  });

  // Panier : mise à jour automatique des quantités
  var t;
  document.addEventListener('change', function (e) {
    if (!e.target.matches('[data-autosave]')) return;
    clearTimeout(t);
    var f = e.target.form;
    t = setTimeout(function () { f.submit(); }, 500);
  });

  // Avis : agrandir la photo
  document.addEventListener('click', function (e) {
    var z = e.target.closest('[data-zoom]');
    if (!z) return;
    var o = document.createElement('div'); o.className = 'zoom'; o.setAttribute('role', 'dialog'); o.setAttribute('aria-label', 'Photo agrandie');
    var img = document.createElement('img'); img.src = z.dataset.zoom; img.alt = '';
    o.appendChild(img);
    var close = function () { o.remove(); document.removeEventListener('keydown', onKey); };
    var onKey = function (ev) { if (ev.key === 'Escape') close(); };
    o.addEventListener('click', close);
    document.addEventListener('keydown', onKey);
    document.body.appendChild(o);
  });

  // Avis : carrousel (flèches, points, glisser, clavier, défilement automatique)
  document.querySelectorAll('[data-carousel]').forEach(function (root) {
    var track = root.querySelector('.rtrack');
    var cards = [].slice.call(track.children);
    var band = root.closest('section');
    var ctl = band.querySelector('[data-rctl]');
    var dotsBox = root.querySelector('[data-rdots]');
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var positions = 1, timer = null, stopped = false, hover = false, focus = false, visible = true, raf = 0;

    function step() {
      var gap = parseFloat(getComputedStyle(track).columnGap) || 22;
      return cards[0] ? cards[0].offsetWidth + gap : 1;
    }
    function overflow() { return track.scrollWidth > track.clientWidth + 4; }
    function index() { return Math.max(0, Math.min(positions - 1, Math.round(track.scrollLeft / step()))); }
    function go(i) { track.scrollTo({ left: i * step(), behavior: reduce ? 'auto' : 'smooth' }); }
    function move(dir) {
      var cur = index();
      if (dir > 0 && cur >= positions - 1) go(0);
      else if (dir < 0 && cur <= 0) go(positions - 1);
      else go(cur + dir);
    }
    function stop() { stopped = true; clearInterval(timer); }

    function build() {
      var can = overflow();
      positions = can ? Math.round((track.scrollWidth - track.clientWidth) / step()) + 1 : 1;
      if (ctl) ctl.hidden = !can;
      dotsBox.innerHTML = '';
      dotsBox.hidden = !can;
      if (can) {
        for (var i = 0; i < positions; i++) {
          var b = document.createElement('button');
          b.type = 'button'; b.tabIndex = -1; b.dataset.i = i;
          b.setAttribute('aria-label', 'Aller à l\u2019avis ' + (i + 1));
          dotsBox.appendChild(b);
        }
      }
      sync();
    }
    function sync() {
      var cur = index();
      [].forEach.call(dotsBox.children, function (d, i) { d.setAttribute('aria-current', i === cur ? 'true' : 'false'); });
    }

    track.addEventListener('scroll', function () {
      cancelAnimationFrame(raf);
      raf = requestAnimationFrame(sync);
    }, { passive: true });
    window.addEventListener('resize', build);

    band.addEventListener('click', function (e) {
      var a = e.target.closest('.rarrow');
      if (a) { stop(); move(parseInt(a.dataset.dir, 10)); return; }
      var d = e.target.closest('.rdots button');
      if (d) { stop(); go(parseInt(d.dataset.i, 10)); }
    });
    track.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') { e.preventDefault(); stop(); move(1); }
      else if (e.key === 'ArrowLeft') { e.preventDefault(); stop(); move(-1); }
    });

    // Glisser à la souris (le tactile défile déjà nativement)
    var down = false, startX = 0, startLeft = 0, moved = false;
    track.addEventListener('pointerdown', function (e) {
      stop();
      if (e.pointerType !== 'mouse' || e.button !== 0) return;
      down = true; moved = false; startX = e.clientX; startLeft = track.scrollLeft;
    });
    window.addEventListener('pointermove', function (e) {
      if (!down) return;
      var dx = e.clientX - startX;
      if (!moved && Math.abs(dx) > 6) { moved = true; track.classList.add('dragging'); }
      if (moved) track.scrollLeft = startLeft - dx;
    });
    window.addEventListener('pointerup', function () {
      if (!down) return;
      down = false;
      if (moved) { track.classList.remove('dragging'); go(index()); }
    });

    // Défilement automatique : seulement si visible, sans survol, et arrêté dès qu'on touche au carrousel
    root.addEventListener('mouseenter', function () { hover = true; });
    root.addEventListener('mouseleave', function () { hover = false; });
    root.addEventListener('focusin', function () { focus = true; });
    root.addEventListener('focusout', function () { focus = false; });
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (en) { visible = en[0].isIntersecting; }, { threshold: 0.3 }).observe(root);
    }
    if (!reduce) {
      timer = setInterval(function () {
        if (stopped || hover || focus || !visible || document.hidden || !overflow()) return;
        move(1);
      }, 6000);
    }
    build();
    window.addEventListener('load', build);
  });
})();
