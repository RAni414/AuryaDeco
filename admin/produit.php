<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$copyOf = (int)($_GET['copie'] ?? 0);
$p = null;
if ($id) {
    $p = load_product_by('id', $id, false);
    if (!$p) { flash("Ce produit n'existe pas.", 'err'); redirect('admin/produits.php'); }
} elseif ($copyOf) {
    $p = load_product_by('id', $copyOf, false);
    if ($p) { $p['id'] = 0; $p['name'] .= ' (copie)'; $p['images'] = []; foreach ($p['variants'] as &$vv) { $vv['id'] = 0; } unset($vv); }
}
if (!$p) {
    $p = ['id' => 0, 'kind' => $_GET['kind'] ?? 'plante', 'name' => '', 'type' => 'palm', 'shape' => 'cyl', 'mat' => 'ciment', 'color' => '#B6B2A9', 'open_min' => 30, 'open_max' => 40, 'tomb' => 0, 'featured' => 0, 'active' => 1, 'description' => '', 'position' => 0, 'shipping_fee' => null, 'slug' => '',
          'variants' => [['id' => 0, 'height' => 150, 'diameter' => null, 'label' => '', 'price' => '', 'old_price' => null, 'stock' => null]], 'images' => []];
}
$errors = [];

if (is_post()) {
    csrf_check();
    $in = $_POST;
    $kind = isset(KINDS[$in['kind'] ?? '']) ? $in['kind'] : 'plante';
    $name = trim((string)($in['name'] ?? ''));
    $p = array_merge($p, [
        'kind' => $kind,
        'name' => $name,
        'type' => $kind === 'plante' ? (isset(PLANT_TYPES[$in['type'] ?? '']) ? $in['type'] : 'palm') : ($kind === 'acc' ? (isset(ACC_TYPES[$in['acc_type'] ?? '']) ? $in['acc_type'] : 'pebbles') : null),
        'shape' => $kind === 'pot' ? (isset(POT_SHAPES[$in['shape'] ?? '']) ? $in['shape'] : 'cyl') : null,
        'mat' => $kind === 'pot' ? (isset(MATS[$in['mat'] ?? '']) ? $in['mat'] : 'ciment') : null,
        'color' => $kind === 'pot' && preg_match('/^#[0-9a-fA-F]{6}$/', $in['color'] ?? '') ? $in['color'] : null,
        'open_min' => $kind === 'plante' && ($in['open_min'] ?? '') !== '' ? max(1, (int)$in['open_min']) : null,
        'open_max' => $kind === 'plante' && ($in['open_max'] ?? '') !== '' ? max(1, (int)$in['open_max']) : null,
        'tomb' => $kind === 'plante' && !empty($in['tomb']) ? 1 : 0,
        'featured' => !empty($in['featured']) ? 1 : 0,
        'active' => !empty($in['active']) ? 1 : 0,
        'description' => trim((string)($in['description'] ?? '')),
        'position' => (int)($in['position'] ?? 0),
        'shipping_fee' => ($sf = str_replace(',', '.', trim((string)($in['shipping_fee'] ?? '')))) === '' ? null : $sf,
    ]);
    if ($p['shipping_fee'] !== null && (!is_numeric($p['shipping_fee']) || (float)$p['shipping_fee'] < 0)) $errors[] = 'Les frais de livraison doivent être un nombre positif (ou 0 pour une livraison offerte).';
    if ($name === '') $errors[] = 'Indiquez le nom du produit.';
    if ($p['open_min'] && $p['open_max'] && $p['open_min'] > $p['open_max']) $errors[] = "L'ouverture minimale doit être inférieure à l'ouverture maximale.";

    $variants = [];
    $vin = $in['v'] ?? [];
    foreach (($vin['price'] ?? []) as $i => $price) {
        $row = [
            'id' => (int)($vin['id'][$i] ?? 0),
            'height' => $kind === 'plante' ? (int)($vin['size'][$i] ?? 0) : null,
            'diameter' => $kind === 'pot' ? (int)($vin['size'][$i] ?? 0) : null,
            'label' => $kind === 'acc' ? trim((string)($vin['label'][$i] ?? '')) : null,
            'price' => str_replace(',', '.', trim((string)$price)),
            'old_price' => ($o = str_replace(',', '.', trim((string)($vin['old'][$i] ?? '')))) === '' ? null : $o,
            'stock' => ($s = trim((string)($vin['stock'][$i] ?? ''))) === '' ? null : (int)$s,
        ];
        $empty = $row['price'] === '' && !$row['height'] && !$row['diameter'] && !$row['label'];
        if ($empty) continue;
        $n = count($variants) + 1;
        if (!is_numeric($row['price']) || (float)$row['price'] <= 0) $errors[] = "Indiquez un prix valide pour la taille $n.";
        if ($row['old_price'] !== null && !is_numeric($row['old_price'])) $errors[] = "L'ancien prix de la taille $n n'est pas un nombre.";
        if ($kind === 'plante' && $row['height'] <= 0) $errors[] = "Indiquez la hauteur de la taille $n.";
        if ($kind === 'pot' && $row['diameter'] <= 0) $errors[] = "Indiquez le diamètre de la taille $n.";
        if ($kind === 'acc' && $row['label'] === '') $errors[] = "Indiquez le format de la ligne $n.";
        if ($row['stock'] !== null && $row['stock'] < 0) $errors[] = "Le stock de la taille $n ne peut pas être négatif.";
        $variants[] = $row;
    }
    if (!$variants) $errors[] = 'Ajoutez au moins une taille avec son prix.';
    $p['variants'] = $variants ?: $p['variants'];

    if (!$errors) {
        $pdo = db();
        $newFiles = [];
        try {
            // Photos envoyées
            foreach (['main_photo', 'secondary_photo'] as $role) {
                if (isset($_FILES[$role]) && ($_FILES[$role]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $newFiles[$role] = save_upload($_FILES[$role]);
                }
            }
            $pdo->beginTransaction();
            $cols = [$p['kind'], $p['name'], $p['type'], $p['shape'], $p['mat'], $p['color'], $p['open_min'], $p['open_max'], $p['tomb'], $p['featured'], $p['active'], $p['description'], $p['position'], $p['shipping_fee']];
            if ($p['id']) {
                $pdo->prepare('UPDATE products SET kind=?, name=?, type=?, shape=?, mat=?, color=?, open_min=?, open_max=?, tomb=?, featured=?, active=?, description=?, position=?, shipping_fee=? WHERE id=?')
                    ->execute(array_merge($cols, [$p['id']]));
            } else {
                $slug = slugify($p['name']);
                $chk = $pdo->prepare('SELECT COUNT(*) FROM products WHERE slug = ?');
                $base = $slug;
                for ($n = 2; ; $n++) { $chk->execute([$slug]); if (!$chk->fetchColumn()) break; $slug = $base . '-' . $n; }
                $pdo->prepare('INSERT INTO products (slug, kind, name, type, shape, mat, color, open_min, open_max, tomb, featured, active, description, position, shipping_fee) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')
                    ->execute(array_merge([$slug], $cols));
                $p['id'] = (int)$pdo->lastInsertId();
                $p['slug'] = $slug;
            }
            // Tailles : mise à jour, ajout, suppression
            $keep = [];
            $upd = $pdo->prepare('UPDATE variants SET height=?, diameter=?, label=?, price=?, old_price=?, stock=?, position=? WHERE id=? AND product_id=?');
            $ins = $pdo->prepare('INSERT INTO variants (product_id, height, diameter, label, price, old_price, stock, position) VALUES (?,?,?,?,?,?,?,?)');
            foreach ($variants as $i => $v) {
                $vals = [$v['height'], $v['diameter'], $v['label'], $v['price'], $v['old_price'], $v['stock'], $i];
                if ($v['id']) {
                    $upd->execute(array_merge($vals, [$v['id'], $p['id']]));
                    $keep[] = $v['id'];
                } else {
                    $ins->execute(array_merge([$p['id']], $vals));
                    $keep[] = (int)$pdo->lastInsertId();
                }
            }
            $pdo->prepare('DELETE FROM variants WHERE product_id = ? AND id NOT IN (' . implode(',', array_map('intval', $keep)) . ')')->execute([$p['id']]);

            // Photos : suppression, photo principale, ajout
            $existing = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY position, id');
            $existing->execute([$p['id']]);
            $imgs = $existing->fetchAll();
            $del = array_map('intval', (array)($in['del_img'] ?? []));
            $toUnlink = [];
            foreach ($imgs as $k => $img) {
                if (in_array((int)$img['id'], $del, true)) {
                    $pdo->prepare('DELETE FROM product_images WHERE id = ?')->execute([$img['id']]);
                    $toUnlink[] = $img['file'];
                    unset($imgs[$k]);
                }
            }
            $main = (int)($in['main_img'] ?? 0);
            $secondary = (int)($in['secondary_img'] ?? 0);
            $ii = $pdo->prepare('INSERT INTO product_images (product_id, file, position) VALUES (?,?,?)');
            foreach ($newFiles as $role => $file) {
                $ii->execute([$p['id'], $file, count($imgs)]);
                $imageId = (int)$pdo->lastInsertId();
                $imgs[] = ['id' => $imageId, 'file' => $file, 'position' => count($imgs)];
                if ($role === 'main_photo') $main = $imageId;
                else $secondary = $imageId;
            }
            if ($main > 0 && $main === $secondary) throw new RuntimeException('Choisissez deux photos différentes pour la principale et la secondaire.');
            usort($imgs, function ($a, $b) use ($main, $secondary) {
                $rank = static fn($image) => (int)$image['id'] === $main ? 0 : ((int)$image['id'] === $secondary ? 1 : 2);
                return ($rank($a) <=> $rank($b)) ?: ((int)$a['position'] <=> (int)$b['position']) ?: ((int)$a['id'] <=> (int)$b['id']);
            });
            $pos = 0;
            $sp = $pdo->prepare('UPDATE product_images SET position = ? WHERE id = ?');
            foreach ($imgs as $img) { $sp->execute([$pos++, $img['id']]); }
            $pdo->commit();
            foreach ($toUnlink as $f) { @unlink(UPLOAD_DIR . '/' . basename($f)); }
            flash($id ? 'Produit enregistré.' : 'Produit ajouté à la boutique.');
            redirect('admin/produit.php?id=' . $p['id']);
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            foreach ($newFiles as $f) { @unlink(UPLOAD_DIR . '/' . $f); }
            $errors[] = $ex instanceof RuntimeException ? $ex->getMessage() : "L'enregistrement a échoué : " . $ex->getMessage();
        }
    }
}

$k = $p['kind'];
$sizeHead = $k === 'plante' ? 'Hauteur (cm)' : ($k === 'pot' ? "Diamètre d'ouverture (cm)" : 'Format (ex. Sac de 2 kg)');
$opt = function (array $arr, $cur) { $h = ''; foreach ($arr as $kk => $l) { $h .= '<option value="' . e($kk) . '"' . ((string)$kk === (string)$cur ? ' selected' : '') . '>' . e($l) . '</option>'; } return $h; };
admin_header($p['id'] ? 'Modifier ' . $p['name'] : 'Nouveau produit', 'produits');
?>
<div class="crumbs"><a href="<?= e(url('admin/produits.php')) ?>">Produits</a> / <?= $p['id'] ? e($p['name']) : 'Nouveau produit' ?></div>
<div class="shead"><h1><?= $p['id'] ? 'Modifier le produit' : 'Ajouter un produit' ?></h1><?php if ($p['id'] && $p['slug']): ?><a class="sbtn" href="<?= e(url('produit.php?p=' . rawurlencode($p['slug']))) ?>" target="_blank" rel="noopener">Voir dans la boutique</a><?php endif; ?></div>
<?php foreach ($errors as $err): ?><div class="flash flash-err" role="alert"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="editor" id="edForm" data-kind="<?= e($k) ?>">
  <?= csrf_field() ?>
  <div>
    <fieldset class="fs"><legend>Informations</legend>
      <div class="row">
        <label class="field">Nom du produit<input name="name" value="<?= e($p['name']) ?>" required maxlength="160"></label>
        <label class="field">Catégorie<select name="kind" id="kindSel"><?= $opt(KINDS, $k) ?></select></label>
      </div>
      <div class="row only-plante">
        <label class="field">Illustration (si pas de photo)<select name="type"><?= $opt(PLANT_TYPES, $k === 'plante' ? $p['type'] : 'palm') ?></select></label>
        <label class="field">Ouverture de pot conseillée, min (cm)<input type="number" name="open_min" min="1" value="<?= e($p['open_min']) ?>"></label>
        <label class="field">Ouverture de pot conseillée, max (cm)<input type="number" name="open_max" min="1" value="<?= e($p['open_max']) ?>"></label>
      </div>
      <div class="row only-plante"><label class="check"><input type="checkbox" name="tomb" value="1" <?= $p['tomb'] ? 'checked' : '' ?>> Plante tombante</label></div>
      <div class="row only-pot">
        <label class="field">Matière<select name="mat"><?= $opt(MATS, $p['mat'] ?? 'ciment') ?></select></label>
        <label class="field">Forme (illustration)<select name="shape"><?= $opt(POT_SHAPES, $p['shape'] ?? 'cyl') ?></select></label>
        <label class="field">Couleur (illustration)<input type="color" name="color" value="<?= e($p['color'] ?: '#B6B2A9') ?>"></label>
      </div>
      <div class="row only-acc"><label class="field">Illustration (si pas de photo)<select name="acc_type"><?= $opt(ACC_TYPES, $k === 'acc' ? $p['type'] : 'pebbles') ?></select></label></div>
    </fieldset>

    <fieldset class="fs"><legend>Tailles, prix et stock</legend>
      <p class="hint">Remplissez l'ancien prix pour afficher une promotion barrée. Laissez le stock vide pour ne pas le suivre ; à 0, la taille s'affiche « épuisée ». Le stock baisse automatiquement à chaque commande.</p>
      <div class="atable-wrap plain"><table class="vt" id="vTable">
        <thead><tr><th id="sizeHead"><?= e($sizeHead) ?></th><th>Prix (TND)</th><th>Ancien prix</th><th>Stock</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($p['variants'] as $v): ?>
          <tr>
            <td><input type="hidden" name="v[id][]" value="<?= (int)$v['id'] ?>">
              <input class="sz-num" type="number" min="1" name="v[size][]" value="<?= e($k === 'plante' ? $v['height'] : ($k === 'pot' ? $v['diameter'] : '')) ?>" aria-label="Taille">
              <input class="sz-txt" type="text" name="v[label][]" value="<?= e($v['label']) ?>" aria-label="Format"></td>
            <td><input type="text" inputmode="decimal" name="v[price][]" value="<?= e($v['price'] === '' ? '' : rtrim(rtrim((string)$v['price'], '0'), '.')) ?>" aria-label="Prix"></td>
            <td><input type="text" inputmode="decimal" name="v[old][]" value="<?= e($v['old_price'] === null ? '' : rtrim(rtrim((string)$v['old_price'], '0'), '.')) ?>" placeholder="facultatif" aria-label="Ancien prix"></td>
            <td><input type="number" name="v[stock][]" min="0" value="<?= e($v['stock']) ?>" placeholder="illimité" aria-label="Stock"></td>
            <td><button type="button" class="sbtn danger" data-vrm>Retirer</button></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <button type="button" class="sbtn" id="vAdd" style="margin-top:10px">Ajouter une taille</button>
    </fieldset>

    <fieldset class="fs"><legend>Livraison</legend>
      <label class="field">Frais de livraison de ce produit (TND, par article)
        <input type="text" inputmode="decimal" name="shipping_fee" value="<?= e($p['shipping_fee'] === null ? '' : rtrim(rtrim((string)$p['shipping_fee'], '0'), '.')) ?>" placeholder="Tarif par défaut : <?= e(money(setting('shipping_fee'))) ?>">
      </label>
      <p class="hint">Laissez vide pour appliquer le tarif par défaut (<?= e(money(setting('shipping_fee'))) ?>, modifiable dans Réglages). Mettez <strong>0</strong> pour une livraison offerte sur ce produit. Dans le panier, les frais de chaque article sont additionnés, sauf si le total atteint le seuil de livraison offerte (<?= (float)setting('free_shipping') > 0 ? e(money(setting('free_shipping'))) : 'désactivé' ?>).</p>
    </fieldset>

    <fieldset class="fs"><legend>Description</legend>
      <label class="field">Texte de la fiche produit<textarea name="description" rows="7" placeholder="Laissez une ligne vide entre deux paragraphes."><?= e($p['description']) ?></textarea></label>
      <p class="hint">Le premier paragraphe sert aussi de résumé pour Google.</p>
    </fieldset>

    <fieldset class="fs"><legend>Photos</legend>
      <?php $photoCount = count($p['images']); ?>
      <p class="hint"><?= $photoCount ?> photo(s) enregistrée(s).
      <?php if ($photoCount < 2): ?>Le changement au survol nécessite deux photos distinctes de ce même produit. Ajoutez <?= $photoCount === 0 ? 'une photo principale et une deuxième vue' : 'une deuxième vue' ?> ci-dessous, puis enregistrez le produit.
      <?php else: ?>La première photo dans l’ordre est la principale ; la deuxième apparaît au survol. Choisissez deux vues différentes du même produit.
      <?php endif; ?></p>
      <?php if ($p['images']): ?>
        <div class="photos">
          <?php foreach ($p['images'] as $i => $img): ?>
            <div class="photo">
              <img src="<?= e(url('uploads/' . $img['file'])) ?>" alt="">
              <div class="pa">
                <label class="check sm"><input type="radio" name="main_img" value="<?= (int)$img['id'] ?>" <?= $i === 0 ? 'checked' : '' ?>> Photo principale</label>
                <label class="check sm"><input type="radio" name="secondary_img" value="<?= (int)$img['id'] ?>" <?= $i === 1 ? 'checked' : '' ?>> Photo secondaire (survol)</label>
                <label class="check sm danger"><input type="checkbox" name="del_img[]" value="<?= (int)$img['id'] ?>"> Supprimer</label>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <label class="field" style="margin-top:12px">Importer la photo principale<input type="file" name="main_photo" accept="image/jpeg,image/png,image/webp" data-photo-input="mainPhotoPreview"></label>
      <div class="photos" id="mainPhotoPreview"></div>
      <label class="field" style="margin-top:12px">Importer la photo secondaire (survol)<input type="file" name="secondary_photo" accept="image/jpeg,image/png,image/webp" data-photo-input="secondaryPhotoPreview"></label>
      <div class="photos" id="secondaryPhotoPreview"></div>
      <p class="hint">Sélectionnez les deux photos du produit, puis cliquez sur « Enregistrer le produit ». Chaque nouvelle photo prend directement le rôle indiqué. Un champ laissé vide conserve le choix existant. Vous pouvez aussi choisir parmi les photos déjà enregistrées ci-dessus. Les anciennes photos restent dans la galerie. JPG, PNG ou WebP.</p>
    </fieldset>
  </div>

  <aside>
    <div class="fs">
      <label class="check"><input type="checkbox" name="active" value="1" <?= $p['active'] ? 'checked' : '' ?>> Visible dans la boutique</label>
      <label class="check"><input type="checkbox" name="featured" value="1" <?= $p['featured'] ? 'checked' : '' ?>> Mettre en avant sur l'accueil</label>
      <label class="field">Ordre d'affichage<input type="number" name="position" value="<?= (int)$p['position'] ?>"></label>
      <p class="hint">Les plus petits nombres s'affichent en premier.</p>
      <button class="btn block" type="submit">Enregistrer le produit</button>
      <a class="btn ghost block" style="margin-top:8px" href="<?= e(url('admin/produits.php')) ?>">Retour à la liste</a>
    </div>
    <?php if (!$p['images']): ?><div class="fs prev"><p class="hint" style="margin-top:0">Illustration actuelle</p><?= art_tag($p) ?></div><?php endif; ?>
  </aside>
</form>
<?php admin_footer();
