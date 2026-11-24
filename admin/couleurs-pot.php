<?php
require __DIR__ . '/../includes/admin.php';
require_admin();
require_once __DIR__ . '/../includes/pot-colors.php';
$productId = (int)($_GET['produit'] ?? 0);
$product = load_product_by('id', $productId, false);
if (!$product || $product['kind'] !== 'pot') { flash('Choisissez un pot pour gérer ses couleurs.', 'err'); redirect('admin/produits.php?kind=pot'); }
$colors = pot_colors($productId);
$id = (int)($_GET['id'] ?? 0);
if ($id && !isset($colors[$id])) { flash('Cette couleur ne correspond pas au pot.', 'err'); redirect('admin/couleurs-pot.php?produit=' . $productId); }
$color = $colors[$id] ?? ['name' => '', 'hex' => '#B6B2A9', 'position' => count($colors) + 1, 'photos' => []];
$error = '';
$editingId = $id;
if (is_post()) {
    csrf_check();
    $newFiles = [];
    $removeFiles = [];
    $pdo = db();
    try {
        $values = pot_color_values($_POST);
        $color = array_merge($color, $values);
        $remove = is_array($_POST['remove_photos'] ?? null) ? array_map('intval', $_POST['remove_photos']) : [];
        $files = $_FILES['photos'] ?? null;
        $count = is_array($files['name'] ?? null) ? count($files['name']) : 0;
        if ($count > 8) throw new InvalidArgumentException('Ajoutez au maximum huit photos à la fois.');
        for ($i = 0; $i < $count; $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
            $newFiles[] = save_upload(['name' => $files['name'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]]);
        }
        $pdo->beginTransaction();
        if ($id) $pdo->prepare('UPDATE pot_colors SET name=?,hex=?,position=? WHERE id=? AND product_id=?')->execute([$values['name'], $values['hex'], $values['position'], $id, $productId]);
        else {
            $pdo->prepare('INSERT INTO pot_colors (product_id,name,hex,position) VALUES (?,?,?,?)')->execute([$productId, $values['name'], $values['hex'], $values['position']]);
            $id = (int)$pdo->lastInsertId();
            // Keep existing size IDs for the first color, preserving carts and orders.
            if (!$colors) $pdo->prepare('UPDATE variants SET color_id=? WHERE product_id=? AND color_id IS NULL')->execute([$id, $productId]);
        }
        foreach ($color['photos'] as $photo) {
            if (in_array((int)$photo['id'], $remove, true)) {
                $pdo->prepare('DELETE FROM pot_color_photos WHERE id=? AND color_id=?')->execute([$photo['id'], $id]);
                $removeFiles[] = $photo['file'];
            }
        }
        $save = $pdo->prepare('INSERT INTO pot_color_photos (color_id,file,position) VALUES (?,?,?)');
        foreach ($newFiles as $position => $file) $save->execute([$id, $file, count($color['photos']) + $position]);
        $pdo->commit();
        foreach ($removeFiles as $file) @unlink(UPLOAD_DIR . '/' . basename($file));
        flash('Couleur enregistrée. Dans la fiche du pot, associez chaque ligne de taille à sa couleur.');
        redirect('admin/couleurs-pot.php?produit=' . $productId . '&id=' . $id);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        foreach ($newFiles as $file) @unlink(UPLOAD_DIR . '/' . basename($file));
        $id = $editingId;
        $error = $e instanceof RuntimeException || $e instanceof InvalidArgumentException ? $e->getMessage() : 'Impossible d’enregistrer cette couleur.';
    }
}
admin_header('Couleurs de ' . $product['name'], 'produits');
?>
<div class="shead"><h1>Couleurs — <?= e($product['name']) ?></h1><a class="btn ghost" href="<?= e(url('admin/produit.php?id=' . $productId)) ?>">Retour au pot</a></div>
<p class="hint">Créez une couleur avec son nom, sa teinte et ses photos. La première couleur reprend les tailles existantes. Pour les suivantes, ajoutez les lignes de taille correspondantes dans la fiche du pot, avec leurs prix et stocks. Seules les couleurs associées à une taille apparaissent sur le site.</p>
<div class="chips">
  <?php foreach ($colors as $key => $item): ?><a class="sbtn" href="<?= e(url('admin/couleurs-pot.php?produit=' . $productId . '&id=' . $key)) ?>"><?= e($item['name']) ?></a><?php endforeach; ?>
  <a class="btn ghost" href="<?= e(url('admin/couleurs-pot.php?produit=' . $productId)) ?>">Ajouter une couleur</a>
</div>
<?php if ($error): ?><p role="alert"><?= e($error) ?></p><?php endif; ?>
<form class="fs" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <h2><?= $id ? 'Modifier la couleur' : 'Nouvelle couleur' ?></h2>
  <div class="row">
    <label class="field">Nom de la couleur<input name="name" maxlength="60" value="<?= e($color['name']) ?>" placeholder="Ex. Ivoire, Noir mat, Terracotta" required></label>
    <label class="field">Teinte de la pastille<input type="color" name="hex" value="<?= e($color['hex']) ?>"></label>
    <label class="field">Ordre<input type="number" name="position" min="0" max="999" value="<?= (int)$color['position'] ?>" required></label>
  </div>
  <div class="photos">
    <?php foreach ($color['photos'] as $photo): ?><div class="photo"><img src="<?= e(url('uploads/' . $photo['file'])) ?>" alt="<?= e($color['name']) ?>"><label class="check"><input type="checkbox" name="remove_photos[]" value="<?= (int)$photo['id'] ?>">Retirer cette photo</label></div><?php endforeach; ?>
  </div>
  <label class="field">Photos de cette couleur<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple></label>
  <p class="hint">JPG, PNG ou WebP, jusqu’à 10 Mo par photo. La première photo est la principale de la couleur. Sans photo, les images générales du pot sont utilisées.</p>
  <button class="btn" type="submit">Enregistrer la couleur</button>
</form>
<?php admin_footer();
