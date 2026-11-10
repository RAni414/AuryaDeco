<?php
/** Homepage categories; real photos only, keeping existing illustration fallbacks. */
$universeImages = ['univers-plantes.jpg', 'univers-pots.jpg', 'univers-tombantes.jpg', 'univers-accessoires.jpg'];
$universeWhere = ["p.kind='plante' AND p.tomb=0", "p.kind='pot'", "p.kind='plante' AND p.tomb=1", "p.kind='acc'"];
$validUniversePhoto = static function (string $path, string $root): bool {
    $resolved = realpath($path); $base = realpath($root);
    if (!$resolved || !$base || strpos(str_replace('\\', '/', $resolved), str_replace('\\', '/', $base) . '/') !== 0 || !is_file($resolved)) return false;
    $info = @getimagesize($resolved);
    return $info && in_array($info['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp'], true);
};
?>
<section class="block home-universes" aria-labelledby="universes-title"><div class="wrap">
  <div class="shead"><h2 id="universes-title">Nos univers</h2></div>
  <div class="cats">
    <?php foreach ($tiles as $i => [$qs, $title, $sub, $prod, $fallback]):
      $photoUrl = null;
      $file = $universeImages[$i];
      if ($validUniversePhoto(APP_DIR . '/assets/images/' . $file, APP_DIR . '/assets/images')) {
          $photoUrl = url('assets/images/' . $file);
      } else {
          $choices = load_products('p.active=1 AND (' . $universeWhere[$i] . ') AND EXISTS (SELECT 1 FROM product_images i WHERE i.product_id=p.id)', [], 'p.featured DESC,p.position ASC,p.id ASC');
          foreach ($choices as $choice) {
              foreach ($choice['images'] as $image) {
                  if ($validUniversePhoto(UPLOAD_DIR . '/' . $image['file'], UPLOAD_DIR)) {
                      $photoUrl = url('uploads/' . implode('/', array_map('rawurlencode', explode('/', str_replace('\\', '/', $image['file'])))));
                      break 2;
                  }
              }
          }
      }
    ?>
      <a class="cat home-universe<?= $photoUrl ? ' home-universe--photo' : '' ?>" href="<?= e(url('boutique.php?' . $qs)) ?>">
        <div class="home-universe__copy"><h3><?= e($title) ?></h3><p><?= e($sub) ?></p><span class="home-universe__cta">Découvrir <span aria-hidden="true">→</span></span></div>
        <?php if ($photoUrl): ?><img class="home-universe__photo" src="<?= e($photoUrl) ?>" alt="" loading="lazy" decoding="async">
        <?php else: ?><div class="art<?= $i === 3 ? ' small' : '' ?>" aria-hidden="true"><?= $prod ? art_tag($prod) : art_tag($fallback) ?></div><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </div>
</div></section>
