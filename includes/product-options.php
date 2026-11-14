<?php
if ($p['kind'] === 'acc' && ($p['type'] ?? '') === 'pebbles') $optLabel = 'Poids';
?>
<?php if ($p['kind'] === 'pot'): ?>
  <label class="opt-label" for="pot-size">Taille des pots</label>
  <select id="pot-size" name="variant_id" required style="width:100%;max-width:420px;margin:10px 0 20px;padding:14px;border:1px solid #cbd4c3;border-radius:8px;background:#fff;color:#294735">
    <?php foreach ($p['variants'] as $i => $x): $ok = in_stock($x); ?>
      <option value="<?= (int)$x['id'] ?>" <?= $i === $sel ? 'selected' : '' ?>
        data-price="<?= e(money($x['price'])) ?>"
        data-old="<?= ($x['old_price'] !== null && (float)$x['old_price'] > (float)$x['price']) ? e(money($x['old_price'])) : '' ?>"
        data-instock="<?= $ok ? '1' : '0' ?>"><?= e(variant_label($p, $x)) ?><?= $ok ? '' : ' (épuisé)' ?></option>
    <?php endforeach; ?>
  </select>
<?php else: ?>
  <div class="opt-label"><?= e($optLabel) ?></div>
  <div class="opts" role="radiogroup" aria-label="<?= e($optLabel) ?>">
    <?php foreach ($p['variants'] as $i => $x): $ok = in_stock($x); ?>
      <label class="opt<?= $ok ? '' : ' out' ?>">
        <input type="radio" name="variant_id" value="<?= (int)$x['id'] ?>" <?= $i === $sel ? 'checked' : '' ?>
          data-price="<?= e(money($x['price'])) ?>"
          data-old="<?= ($x['old_price'] !== null && (float)$x['old_price'] > (float)$x['price']) ? e(money($x['old_price'])) : '' ?>"
          data-instock="<?= $ok ? '1' : '0' ?>">
        <span><?= e(variant_label($p, $x)) ?><?= $ok ? '' : ' (épuisé)' ?></span>
      </label>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
