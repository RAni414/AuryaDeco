<?php if ($customCategories): ?>
<fieldset class="fs"><legend>Catégories personnalisées</legend>
  <p class="hint">Cochez les catégories et sous-catégories dans lesquelles afficher ce produit. Le type « Plante », « Pot » ou « Accessoire » reste utilisé pour ses tailles et caractéristiques.</p>
  <?php
  $categoryNames = ['plante' => 'Plantes artificielles', 'pot' => 'Pots', 'acc' => 'Accessoires'];
  foreach ($customCategories as $entry) $categoryNames['custom_' . $entry['id']] = $entry['name'];
  ?>
  <?php foreach ($customCategories as $entry): ?>
    <label class="check"><input type="checkbox" name="categories[]" value="<?= (int)$entry['id'] ?>" <?= in_array((int)$entry['id'], $selectedCategories, true) ? 'checked' : '' ?>>
      <?= e(($entry['parent_key'] ? ($categoryNames[$entry['parent_key']] ?? '') . ' / ' : '') . $entry['name']) ?><?= $entry['active'] ? '' : ' (masquée)' ?>
    </label>
  <?php endforeach; ?>
</fieldset>
<?php endif; ?>
