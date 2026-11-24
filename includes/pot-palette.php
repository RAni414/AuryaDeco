<?php
require_once __DIR__ . '/pot-colors.php';
$palette = !empty($p['id']) ? pot_colors((int)$p['id']) : [];
$usedColors = array_map(fn($variant) => (int)($variant['color_id'] ?? 0), $p['variants']);
$paletteData = [];
foreach ($palette as $colorId => $color) $paletteData[$colorId] = ['name' => $color['name'], 'photos' => array_map(fn($photo) => url('uploads/' . $photo['file']), $color['photos'])];
if ($palette && in_array(0, $usedColors, true)) $paletteData[0] = ['name' => 'Standard', 'photos' => []];
?>
<?php if ($palette): ?>
<fieldset class="pot-palette" data-pot-palette="<?= e(json_encode($paletteData, JSON_UNESCAPED_UNICODE)) ?>" hidden>
  <legend>Couleur : <span data-pot-color-name aria-live="polite"></span></legend>
  <div class="pot-palette__choices">
    <?php foreach ($palette as $colorId => $color): ?>
      <button type="button" class="pot-swatch" data-pot-color="<?= $colorId ?>" style="--pot-swatch:<?= e($color['hex']) ?>" aria-label="<?= e($color['name']) ?>" aria-pressed="false" title="<?= e($color['name']) ?>"><span aria-hidden="true"></span><span class="pot-swatch__name"><?= e($color['name']) ?></span></button>
    <?php endforeach; ?>
    <?php if (isset($paletteData[0])): ?><button type="button" class="pot-swatch" data-pot-color="0" style="--pot-swatch:#B6B2A9" aria-label="Standard" aria-pressed="false"><span aria-hidden="true"></span><span class="pot-swatch__name">Standard</span></button><?php endif; ?>
  </div>
</fieldset>
<p class="pot-photo-note" data-pot-photo-note hidden></p>
<?php endif; ?>
