<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

$products = load_products('1', [], 'p.position ASC, p.id DESC');
$positions = [];
foreach ($products as $i => $product) $positions[(int)$product['id']] = $i + 1;
$error = '';
if (is_post()) {
    csrf_check();
    $input = $_POST['positions'] ?? [];
    $submitted = [];
    if (is_array($input)) {
        foreach ($input as $id => $value) {
            $rank = is_scalar($value) ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => count($products)]]) : false;
            if ($rank === false || !array_key_exists((int)$id, $positions)) {
                $error = 'Chaque produit doit avoir une place valide dans la liste.';
                break;
            }
            $submitted[(int)$id] = $rank;
        }
    }
    if (!$error && (count($submitted) !== count($positions) || count(array_unique($submitted)) !== count($submitted))) {
        $error = 'Attribuez une place différente à chaque produit : 1, 2, 3… Si la liste a changé, rechargez la page.';
    }
    if (!$error) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $update = $pdo->prepare('UPDATE products SET position = ? WHERE id = ?');
            foreach ($submitted as $id => $rank) $update->execute([$rank, $id]);
            $pdo->commit();
            flash('L’ordre des produits a été enregistré.');
            redirect('admin/ordre-produits.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Product ordering: ' . $e->getMessage());
            $error = 'Impossible d’enregistrer l’ordre. Réessayez.';
        }
    }
    $positions = array_replace($positions, $submitted);
}
admin_header('Ordre des produits', 'ordre-produits');
?>
<div class="shead"><h1>Ordre des produits</h1><a class="btn ghost" href="<?= e(url('admin/produits.php')) ?>">Retour aux produits</a></div>
<p class="hint">Choisissez la place de chaque produit : 1 pour le premier, 2 pour le deuxième, etc. Pour échanger deux produits, échangez leurs numéros, puis enregistrez. Chaque place doit être unique.</p>
<p class="hint">Cet ordre s’applique à la boutique et aux favoris de nos clients. La nouvelle collection et les accessoires déco ont leur propre ordre dans leurs onglets. Un tri par prix ou par nom choisi par le visiteur reste prioritaire.</p>
<?php if ($error): ?><p role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post">
  <?= csrf_field() ?>
  <div class="atable-wrap"><table class="at">
    <thead><tr><th>Place</th><th>Photo</th><th>Produit</th><th>Catégorie</th><th>Visibilité</th></tr></thead>
    <tbody>
    <?php foreach ($products as $product): $id = (int)$product['id']; ?>
      <tr>
        <td><input type="number" name="positions[<?= $id ?>]" value="<?= (int)$positions[$id] ?>" min="1" max="<?= count($products) ?>" required aria-label="<?= e('Place de ' . $product['name']) ?>" style="width:90px"></td>
        <td><div class="th"><?= media($product) ?></div></td>
        <td><a href="<?= e(url('admin/produit.php?id=' . $id)) ?>"><?= e($product['name']) ?></a></td>
        <td><?= e(sub_text($product)) ?></td>
        <td><?= $product['active'] ? 'Visible' : 'Masqué' ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$products): ?><tr><td colspan="5">Aucun produit à organiser.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
  <?php if ($products): ?><p><button class="btn" type="submit">Enregistrer l’ordre</button></p><?php endif; ?>
</form>
<?php admin_footer();
