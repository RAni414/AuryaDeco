(function () {
  'use strict';
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
  document.querySelectorAll('[data-product-carousel]').forEach(function (root) {
    var track = root.querySelector('.product-track'), cards = Array.from(track.children);
    var prev = root.querySelector('[data-product-prev]'), next = root.querySelector('[data-product-next]');
    var dots = root.querySelector('.product-pagination'), status = root.querySelector('.product-carousel-status');
    var stops = [], active = 0, visible = 3, timer, drag = null, suppress = false;
    function offset(index) { return cards[index].offsetLeft - cards[0].offsetLeft; }
    function sync(announce) {
      if (!stops.length) return;
      active = stops.reduce(function (best, index, page) { return Math.abs(offset(index) - track.scrollLeft) < Math.abs(offset(stops[best]) - track.scrollLeft) ? page : best; }, 0);
      prev.disabled = track.scrollLeft <= 2; next.disabled = track.scrollLeft >= track.scrollWidth - track.clientWidth - 2;
      Array.from(dots.children).forEach(function (dot, i) { dot.setAttribute('aria-current', String(i === active)); });
      if (announce) status.textContent = 'Produits ' + (stops[active] + 1) + ' à ' + Math.min(stops[active] + visible, cards.length) + ' sur ' + cards.length;
    }
    function go(page, instant) {
      if (!stops.length) return;
      page = Math.max(0, Math.min(stops.length - 1, page));
      track.scrollTo({left:offset(stops[page]), behavior:reduce.matches || instant ? 'instant' : 'smooth'});
      active = page;
    }
    function layout() {
      var oldIndex = stops[active] || 0;
      visible = Number(getComputedStyle(root).getPropertyValue('--product-visible')) || 1;
      stops = [];
      for (var i = 0; i < cards.length; i += visible) {
        var start = Math.max(0, Math.min(i, cards.length - visible));
        if (!stops.includes(start)) stops.push(start);
      }
      dots.replaceChildren();
      stops.forEach(function (index, page) {
        var button = document.createElement('button'); button.type = 'button';
        button.setAttribute('aria-label', 'Afficher les produits ' + (index + 1) + ' à ' + Math.min(index + visible, cards.length));
        button.setAttribute('aria-controls', track.id); button.addEventListener('click', function () { go(page); }); dots.appendChild(button);
      });
      prev.hidden = next.hidden = stops.length < 2; dots.hidden = stops.length < 2;
      active = Math.max(0, stops.findIndex(function (index) { return index >= oldIndex; }));
      go(active, true); sync(false);
    }
    prev.addEventListener('click', function () { go(active - 1); });
    next.addEventListener('click', function () { go(active + 1); });
    track.addEventListener('scroll', function () { sync(false); clearTimeout(timer); timer = setTimeout(function () { sync(true); }, 180); }, {passive:true});
    track.addEventListener('keydown', function (e) {
      if (e.target !== track) return;
      if (!['ArrowLeft','ArrowRight','Home','End'].includes(e.key)) return;
      e.preventDefault(); go(e.key === 'Home' ? 0 : e.key === 'End' ? stops.length - 1 : active + (e.key === 'ArrowRight' ? 1 : -1));
    });
    track.addEventListener('pointerdown', function (e) {
      suppress = false;
      if (e.button !== 0 || e.target.closest('button,input,select,textarea')) return;
      drag = {x:e.clientX,y:e.clientY,left:track.scrollLeft,id:e.pointerId,mouse:e.pointerType === 'mouse',moved:false};
    });
    track.addEventListener('pointermove', function (e) {
      if (!drag || drag.id !== e.pointerId) return;
      var dx = e.clientX - drag.x, dy = e.clientY - drag.y;
      if (!drag.moved && Math.abs(dx) > 10 && Math.abs(dx) > Math.abs(dy) * 1.3) {
        drag.moved = true; suppress = true;
        if (drag.mouse) { track.setPointerCapture(e.pointerId); track.classList.add('is-dragging'); }
      }
      if (drag.mouse && drag.moved) { e.preventDefault(); track.scrollLeft = drag.left - dx; }
    });
    function finish() { if (!drag) return; var moved = drag.moved && drag.mouse; drag = null; track.classList.remove('is-dragging'); if (moved) { sync(false); go(active); } }
    track.addEventListener('pointerup', finish); track.addEventListener('pointercancel', finish);
    track.addEventListener('lostpointercapture', finish);
    track.addEventListener('dragstart', function (e) { e.preventDefault(); });
    track.addEventListener('click', function (e) { if (suppress) { e.preventDefault(); e.stopImmediatePropagation(); suppress = false; } }, true);
    // Focus scrolling is horizontal only; the page keeps its vertical position.
    track.addEventListener('focusin', function (e) {
      var card = e.target.closest('.pcard'); if (!card) return;
      var index = cards.indexOf(card), left = offset(index);
      if (left < track.scrollLeft || left + card.offsetWidth > track.scrollLeft + track.clientWidth + 2) track.scrollTo({left:left,behavior:'instant'});
    });
    layout(); new ResizeObserver(layout).observe(track);
  });
})();
