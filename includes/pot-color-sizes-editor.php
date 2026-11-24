<section id="color-sizes" class="color-manager__sizes">
  <h2>Tailles et disponibilités de cette couleur</h2>
  <p class="hint">Renseignez H (hauteur), D (diamètre) et, si nécessaire, L (largeur) en cm. Seules les dimensions renseignées sont affichées. Une ligne par taille. Stock vide = illimité ; 0 = épuisé. Les autres couleurs gardent leurs propres tailles et stocks.</p>
  <?php if ($sizeTemplate): ?><p class="hint">Les tailles et prix ci-dessous sont proposés à partir du pot. Vérifiez-les avant d’enregistrer. Les stocks suivis sont mis à 0 pour cette nouvelle couleur.</p><?php endif; ?>
  <div class="atable-wrap"><table class="at">
    <thead><tr><th>Taille / dimensions (cm)</th><th>Prix (TND)</th><th>Ancien prix</th><th>Stock</th><th>Retirer</th></tr></thead>
    <tbody data-color-size-rows>
      <?php foreach (array_values($colorSizes) as $index => $size):
        $size = is_array($size) ? $size : [];
        $read = static fn($key) => is_scalar($size[$key] ?? '') ? ($size[$key] ?? '') : '';
        $dimensions = pot_dimensions($size);
      ?>
      <tr>
        <td><input type="hidden" name="sizes[<?= $index ?>][id]" value="<?= (int)$read('id') ?>"><input name="sizes[<?= $index ?>][label]" value="<?= e($read('label') ?: (!empty($size['diameter']) ? pot_size_label($size) : '')) ?>" maxlength="80" placeholder="Ex. H 30 cm · D 25 cm" aria-label="Taille"><div class="pot-dimension-inputs">
        <?php foreach (['H' => 'dimension_h', 'D' => 'dimension_d', 'L' => 'dimension_l'] as $axis => $field): ?>
          <label><?= $axis ?> (cm)<input type="number" min="0.01" max="99999.99" step="0.01" name="sizes[<?= $index ?>][<?= $field ?>]" value="<?= e(array_key_exists($field, $size) ? $read($field) : ($dimensions[$axis] ?? '')) ?>" aria-label="<?= e(['H' => 'Hauteur', 'D' => 'Diamètre', 'L' => 'Largeur'][$axis]) ?> en cm"></label>
        <?php endforeach; ?>
        </div></td>
        <td><input name="sizes[<?= $index ?>][price]" value="<?= e($read('price')) ?>" inputmode="decimal" placeholder="Ex. 59" aria-label="Prix en TND"></td>
        <td><input name="sizes[<?= $index ?>][old_price]" value="<?= e($read('old_price')) ?>" inputmode="decimal" placeholder="Facultatif" aria-label="Ancien prix"></td>
        <td><input type="number" name="sizes[<?= $index ?>][stock]" value="<?= e($read('stock')) ?>" min="0" placeholder="Illimité" aria-label="Stock"></td>
        <td><label class="check"><input type="checkbox" name="sizes[<?= $index ?>][remove]" value="1" <?= !empty($size['remove']) ? 'checked' : '' ?>>Retirer</label></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <button type="button" class="sbtn" data-color-size-add>+ Ajouter une taille</button>
  <template data-color-size-template><tr>
    <td><input type="hidden" name="sizes[__INDEX__][id]" value="0"><input name="sizes[__INDEX__][label]" maxlength="80" placeholder="Ex. H 30 cm · D 25 cm" aria-label="Taille"><div class="pot-dimension-inputs"><label>H (cm)<input type="number" min="0.01" max="99999.99" step="0.01" name="sizes[__INDEX__][dimension_h]" aria-label="Hauteur en cm"></label><label>D (cm)<input type="number" min="0.01" max="99999.99" step="0.01" name="sizes[__INDEX__][dimension_d]" aria-label="Diamètre en cm"></label><label>L (cm)<input type="number" min="0.01" max="99999.99" step="0.01" name="sizes[__INDEX__][dimension_l]" aria-label="Largeur en cm"></label></div></td>
    <td><input name="sizes[__INDEX__][price]" inputmode="decimal" placeholder="Ex. 59" aria-label="Prix en TND"></td>
    <td><input name="sizes[__INDEX__][old_price]" inputmode="decimal" placeholder="Facultatif" aria-label="Ancien prix"></td>
    <td><input type="number" name="sizes[__INDEX__][stock]" min="0" placeholder="Illimité" aria-label="Stock"></td>
    <td><label class="check"><input type="checkbox" name="sizes[__INDEX__][remove]" value="1">Retirer</label></td>
  </tr></template>
</section>
