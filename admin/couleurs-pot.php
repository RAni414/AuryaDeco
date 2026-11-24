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
$colorSizes = array_values(array_filter($product['variants'], fn($v) => (int)($v['color_id'] ?? 0) === $id));
$sizeTemplate = !$colorSizes;
if (!$colorSizes) {
    $seenSizes = [];
    foreach ($product['variants'] as $variant) {
        $sizeLabel = pot_size_label($variant);
        if (isset($seenSizes[$sizeLabel])) continue;
        $seenSizes[$sizeLabel] = true;
        $variant['id'] = 0;
        $variant['stock'] = $variant['stock'] === null ? null : 0;
        $colorSizes[] = $variant;
    }
}
if (!$colorSizes) $colorSizes = [['id' => 0, 'label' => '', 'price' => '', 'old_price' => null, 'stock' => null]];
$error = '';
$editingId = $id;
if (is_post()) {
    csrf_check();
    $newFiles = [];
    $removeFiles = [];
    $pdo = db();
    try {
        $values = pot_color_values($_POST);
        if (!is_array($_POST['sizes'] ?? null)) throw new InvalidArgumentException('Le tableau des tailles est incomplet. Rechargez la page.');
        $colorSizes = $_POST['sizes'];
        $sizeRows = pot_color_size_values($colorSizes);
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
        save_pot_color_sizes($pdo, $productId, $id, $sizeRows);
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
        flash('Couleur, photos, tailles, prix et stocks enregistrés. Les changements sont visibles sur la fiche du pot.');
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
<p class="hint">Tout se règle ici : choisissez une couleur, ajoutez ses photos, puis ses tailles avec leurs prix et stocks. Un seul enregistrement met la fiche du pot à jour.</p>
<?php
$sizeCounts = [];
foreach ($product['variants'] as $variant) {
    $colorKey = (int)($variant['color_id'] ?? 0);
    $sizeCounts[$colorKey] = ($sizeCounts[$colorKey] ?? 0) + 1;
}
?>
<?php if ($id && empty($sizeCounts[$id])): ?>
<div class="flash flash-err" role="status">Cette couleur a des photos mais aucune taille associée. Elle apparaît sur le site comme indisponible. <a href="#color-sizes">Configurer ses tailles ci-dessous</a>.</div>
<?php endif; ?>
<div class="chips color-manager__tabs">
  <?php foreach ($colors as $key => $item): ?><a class="sbtn" href="<?= e(url('admin/couleurs-pot.php?produit=' . $productId . '&id=' . $key)) ?>"><span class="color-manager__dot" style="background:<?= e($item['hex']) ?>"></span><?= e($item['name']) ?></a><?php endforeach; ?>
  <a class="btn ghost" href="<?= e(url('admin/couleurs-pot.php?produit=' . $productId)) ?>">Ajouter une couleur</a>
</div>
<?php if ($error): ?><p role="alert"><?= e($error) ?></p><?php endif; ?>
<form class="fs color-manager" method="post" enctype="multipart/form-data" data-color-manager>
  <?= csrf_field() ?>
  <p class="color-manager__preview"><span data-color-dot style="background:<?= e($color['hex']) ?>"></span><strong data-color-name><?= e($color['name'] ?: 'Votre couleur') ?></strong></p>
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
  <?php require __DIR__ . '/../includes/pot-color-sizes-editor.php'; ?>
  <button class="btn" type="submit">Enregistrer couleur, photos et tailles</button>
</form>
<script src="<?= e(url('assets/js/color-manager.js')) ?>?v=1" defer></script>
<?php admin_footer();
