(function () {
  'use strict';
  var tools = document.querySelector('.site-tools');
  if (!tools) return;
  var scheduled = false;
  function intersects(a, b) { return a.width > 0 && a.height > 0 && b.width > 0 && b.height > 0 && a.left < b.right && a.right > b.left && a.top < b.bottom && a.bottom > b.top; }
  function update() {
    scheduled = false;
    if (document.body.classList.contains('commerce-page')) { tools.classList.remove('is-avoiding'); return; }
    var box = tools.getBoundingClientRect();
    var active = document.activeElement;
    var editing = active && active.matches('input,textarea,select') && !tools.contains(active);
    var collision = Array.prototype.some.call(document.querySelectorAll('#app form, #app .btn, .home-hero__toggle'), function (node) {
      return !node.hidden && intersects(box, node.getBoundingClientRect());
    });
    tools.classList.toggle('is-avoiding', !!editing || collision);
  }
  function schedule() { if (!scheduled) { scheduled = true; requestAnimationFrame(update); } }
  window.addEventListener('scroll', schedule, { passive: true });
  window.addEventListener('resize', schedule);
  document.addEventListener('focusin', schedule);
  document.addEventListener('focusout', schedule);
  window.addEventListener('load', schedule);
  if ('ResizeObserver' in window) new ResizeObserver(schedule).observe(tools);
  update();
})();
