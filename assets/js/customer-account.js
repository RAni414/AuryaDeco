document.querySelectorAll('[data-show-password]').forEach(function (button) {
  button.addEventListener('click', function () {
    var input = button.parentElement.querySelector('input');
    var show = input.type === 'password'; input.type = show ? 'text' : 'password';
    button.textContent = show ? 'Masquer' : 'Afficher'; button.setAttribute('aria-pressed', String(show));
  });
});
