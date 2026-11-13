(function () {
  'use strict';
  var hero = document.querySelector('[data-home-hero]');
  if (!hero) return;
  var video = hero.querySelector('video');
  var button = hero.querySelector('[data-video-toggle]');
  if (!video) return;
  var motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  var userPaused = false;
  function sync() {
    var playing = !video.paused && !video.error && !motion.matches;
    hero.classList.toggle('is-playing', playing);
    if (button) button.textContent = playing ? 'Mettre en pause' : 'Lire la vidéo';
    if (button) button.setAttribute('aria-label', playing ? 'Mettre en pause la vidéo' : 'Lire la vidéo');
  }
  function play() {
    if (motion.matches) return;
    if (!video.getAttribute('src')) video.src = video.dataset.videoSrc;
    video.muted = true;
    var attempt = video.play();
    if (attempt) attempt.catch(function () { hero.classList.remove('is-playing'); sync(); });
  }
  function applyMotion() {
    if (button) button.hidden = motion.matches;
    if (motion.matches) {
      video.pause();
      video.removeAttribute('src');
      video.load();
      sync();
    } else if (!userPaused) play();
  }
  if (button) button.addEventListener('click', function () {
    if (video.paused) { userPaused = false; play(); }
    else { userPaused = true; video.pause(); }
  });
  video.addEventListener('playing', sync);
  video.addEventListener('pause', sync);
  video.addEventListener('error', function () { hero.classList.remove('is-playing'); if (button) button.hidden = true; });
  if (motion.addEventListener) motion.addEventListener('change', applyMotion);
  else motion.addListener(applyMotion);
  applyMotion();
})();
