(function () {
  'use strict';
  var script = document.currentScript;
  var events = JSON.parse(script.dataset.metaEvents || '[]');
  function choice() { return document.cookie.match(/(?:^|; )aurya_marketing=(yes|no)(?:;|$)/); }
  function save(value) {
    document.cookie = 'aurya_marketing=' + value + '; Path=/; Max-Age=15552000; SameSite=Lax; Secure';
    window.location.reload();
  }
  function preferences() {
    if (document.getElementById('marketing-preferences')) return;
    var box = document.createElement('section');
    box.id = 'marketing-preferences'; box.className = 'marketing-preferences';
    box.setAttribute('role', 'region'); box.setAttribute('aria-label', 'Cookies publicitaires');
    var text = document.createElement('p');
    text.textContent = 'Acceptez-vous les cookies Meta pour mesurer nos publicités ? Des informations de navigation et d’achat sont transmises à Meta uniquement avec votre accord.';
    box.appendChild(text);
    [['Accepter', 'yes'], ['Refuser', 'no']].forEach(function (item) {
      var button = document.createElement('button'); button.type = 'button'; button.className = 'btn'; button.textContent = item[0];
      button.addEventListener('click', function () { save(item[1]); }); box.appendChild(button);
    });
    document.body.appendChild(box);
  }
  var settings = document.createElement('button'); settings.type = 'button'; settings.className = 'marketing-settings'; settings.textContent = 'Cookies publicitaires';
  settings.addEventListener('click', preferences); document.body.appendChild(settings);
  if (!choice()) { preferences(); return; }
  if (choice()[1] !== 'yes') return;
  var fbq = window.fbq = window.fbq || function () { fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments); };
  fbq.queue = fbq.queue || []; fbq.loaded = true; fbq.version = '2.0'; window._fbq = window._fbq || fbq;
  fbq('init', '1731109547867871'); fbq('set', 'autoConfig', false, '1731109547867871');
  var library = document.createElement('script'); library.async = true; library.src = 'https://connect.facebook.net/en_US/fbevents.js'; document.head.appendChild(library);
  events.forEach(function (event) {
    var key = 'aurya-meta-' + event.event_id;
    if (event.event_name === 'Purchase') {
      try { if (sessionStorage.getItem(key)) return; } catch (_) {}
    }
    fbq('track', event.event_name, event.custom_data, { eventID: event.event_id });
    if (event.event_name === 'Purchase') { try { sessionStorage.setItem(key, '1'); } catch (_) {} }
  });
}());
