<?php
require_once __DIR__ . '/catalogue-categories.php';
$customCategories = catalogue_categories();
$parentNames = [];
foreach ($items as $key => $item) $parentNames[$key] = $item['label'];
foreach ($customCategories as $category) $parentNames['custom_' . $category['id']] = $category['name'];
?>
<p class="hint">Créez une catégorie principale ou une sous-catégorie dans Plantes, Pots, Accessoires ou une catégorie personnalisée. Affectez ensuite vos produits depuis leurs fiches.</p>
<?php if ($customCategories): ?>
<div class="atable-wrap"><table class="at">
  <thead><tr><th>Nom</th><th>Catégorie parente</th><th>Ordre</th><th>Visible</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($customCategories as $category): ?>
    <tr>
      <td><?= e($category['name']) ?></td>
      <td><?= e($category['parent_key'] ? ($parentNames[$category['parent_key']] ?? '') : 'Catégorie principale') ?></td>
      <td><?= (int)$category['position'] ?></td>
      <td><?= $category['active'] ? 'Oui' : 'Non' ?></td>
      <td><a class="sbtn" href="<?= e(url('admin/categorie.php?id=' . (int)$category['id'])) ?>">Modifier</a> <a class="sbtn" href="<?= e(url('includes/categorie.php?id=' . (int)$category['id'])) ?>" target="_blank" rel="noopener">Voir</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
