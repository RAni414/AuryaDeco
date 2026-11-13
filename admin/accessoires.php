<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

$pots = load_products("p.kind = 'acc'", [], 'p.featured DESC, p.position ASC, p.id DESC');
$saved = settings()['home_accessory_collection_ids'] ?? null;
$selected = $saved === null
    ? array_column(array_slice(array_values(array_filter($pots, fn($p) => $p['active'])), 0, 8), 'id')
    : array_map('intval', (array)json_decode($saved, true));
$error = '';
if (is_post()) {
    csrf_check();
    $input = $_POST['products'] ?? [];
    $selected = array_values(array_unique(array_map('intval', is_array($input) ? $input : [])));
    $validIds = array_column($pots, 'id');
    if (count($selected) > 8 || array_diff($selected, $validIds)) {
        $error = 'Sélectionnez au maximum huit accessoires de la liste.';
    } else {
        db()->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)')
            ->execute(['home_accessory_collection_ids', json_encode($selected)]);
        flash('La sélection d’accessoires a été enregistrée sur la page d’accueil.');
        redirect('admin/accessoires.php');
    }
}
admin_header('Accessoires déco', 'accessoires');
?>
<div class="shead"><h1>Accessoires déco sur l’accueil</h1><a class="btn" href="<?= e(url('admin/produit.php?kind=acc')) ?>">Ajouter un produit</a></div>
<p><strong>Sublimez votre intérieur et votre extérieur avec des accessoires déco pleins de charme.</strong></p>
<p class="hint">Cochez jusqu’à huit accessoires à afficher dans cet espace de l’accueil, puis enregistrez. Les photos, noms et prix proviennent de leurs fiches produits. L’ordre suit la mise en avant et la position définies dans ces fiches. Les produits masqués restent invisibles sur le site. Décochez tout pour vider la collection.</p>
<?php if ($error): ?><p role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post">
  <?= csrf_field() ?>
  <div class="atable-wrap"><table class="at">
    <thead><tr><th>Dans la collection</th><th>Photo</th><th>Produit</th><th>Prix</th><th>Visibilité</th><th>Action</th></tr></thead>
    <tbody>
    <?php if (!$pots): ?><tr><td colspan="6">Aucun accessoire. Ajoutez un produit pour commencer.</td></tr><?php endif; ?>
    <?php foreach ($pots as $pot): ?>
      <tr>
        <td><input type="checkbox" name="products[]" value="<?= (int)$pot['id'] ?>" aria-label="<?= e('Afficher ' . $pot['name'] . ' dans la collection') ?>" <?= in_array((int)$pot['id'], $selected, true) ? 'checked' : '' ?>></td>
        <td><div class="th"><?= media($pot) ?></div></td>
        <td><?= e($pot['name']) ?><div class="sub"><?= e(sub_text($pot)) ?></div></td>
        <td><?= e(price_range($pot)) ?></td>
        <td><?= $pot['active'] ? 'Visible' : 'Masqué' ?></td>
        <td><a class="sbtn" href="<?= e(url('admin/produit.php?id=' . $pot['id'])) ?>">Modifier le produit</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <p><button class="btn" type="submit">Enregistrer la collection</button></p>
</form>
<?php admin_footer();
