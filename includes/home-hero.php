<?php
// Emit only URLs for media actually present on this installation.
$heroVideo = is_file(__DIR__ . '/../assets/videos/hero-deco.mp4');
$heroPoster = is_file(__DIR__ . '/../assets/images/hero-deco-poster.jpg');
$heroVideoUrl = $heroVideo ? url('assets/videos/hero-deco.mp4') . '?v=' . filemtime(__DIR__ . '/../assets/videos/hero-deco.mp4') : '';
$heroPosterUrl = $heroPoster ? url('assets/images/hero-deco-poster.jpg') . '?v=' . filemtime(__DIR__ . '/../assets/images/hero-deco-poster.jpg') : '';
?>
<div class="aurya-home">
<section class="home-hero" aria-labelledby="home-title" data-home-hero>
  <div class="home-hero__media" aria-hidden="true">
    <?php if ($heroPoster): ?>
      <img class="home-hero__poster" src="<?= e($heroPosterUrl) ?>" alt="" fetchpriority="high">
    <?php endif; ?>
    <?php if ($heroVideo): ?>
      <video class="home-hero__video" autoplay muted loop playsinline preload="none" tabindex="-1" aria-hidden="true"<?php if ($heroPoster): ?> poster="<?= e($heroPosterUrl) ?>"<?php endif; ?> data-video-src="<?= e($heroVideoUrl) ?>"></video>
    <?php endif; ?>
  </div>
  <div class="wrap home-hero__content">
    <p class="home-hero__eyebrow">PLANTES &amp; DÉCORATION</p>
    <h1 id="home-title">Une touche de nature, un intérieur qui vous ressemble.</h1>
    <p class="home-hero__description">Découvrez nos plantes artificielles et nos pots décoratifs pour embellir vos espaces, sans entretien.</p>
    <div class="home-hero__actions">
      <a class="btn home-hero__primary" href="<?= e(url('boutique.php?cat=plante')) ?>">Découvrir les plantes</a>
      <a class="btn home-hero__secondary" href="<?= e(url('boutique.php?cat=pot')) ?>">Explorer les pots</a>
    </div>
  </div>
</section>
