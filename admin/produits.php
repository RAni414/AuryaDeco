<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

if (is_post()) {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if ($act === 'order') {
        $input = $_POST['positions'] ?? [];
        $positions = [];
        $valid = is_array($input) && count($input) > 0;
        $existingIds = db()->query('SELECT id FROM products')->fetchAll(PDO::FETCH_COLUMN);
        if ($valid) foreach ($input as $productId => $value) {
            $rank = is_scalar($value) ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000]]) : false;
            if ($rank === false || !in_array((int)$productId, $existingIds)) { $valid = false; break; }
            $positions[(int)$productId] = $rank;
        }
        if (!$valid) {
            flash('Indiquez un ordre valide pour chaque produit affiché.', 'err');
        } else {
            $pdo = db();
            try {
                $pdo->beginTransaction();
                $update = $pdo->prepare('UPDATE products SET position = ? WHERE id = ?');
                foreach ($positions as $productId => $rank) $update->execute([$rank, $productId]);
                $pdo->commit();
                flash('L’ordre des produits affichés a été enregistré.');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Product ordering: ' . $e->getMessage());
                flash('Impossible d’enregistrer l’ordre. Réessayez.', 'err');
            }
        }
    } elseif ($act === 'feature') {
        db()->prepare('UPDATE products SET featured = 1 - featured WHERE id = ?')->execute([$id]);
    } elseif ($act === 'active') {
        db()->prepare('UPDATE products SET active = 1 - active WHERE id = ?')->execute([$id]);
        flash('Visibilité du produit modifiée.');
    } elseif ($act === 'delete') {
        $imgs = db()->prepare('SELECT file FROM product_images WHERE product_id = ?');
        $imgs->execute([$id]);
        foreach ($imgs->fetchAll(PDO::FETCH_COLUMN) as $f) {
            @unlink(UPLOAD_DIR . '/' . basename($f));
        }
        db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        flash('Produit supprimé.');
    }
    redirect('admin/produits.php' . (!empty($_POST['back']) ? '?' . $_POST['back'] : ''));
}

$q = trim((string)($_GET['q'] ?? ''));
$kind = $_GET['kind'] ?? '';
$where = ['1'];
$params = [];
if ($q !== '') { $where[] = 'p.name LIKE ?'; $params[] = '%' . $q . '%'; }
if (isset(KINDS[$kind])) { $where[] = 'p.kind = ?'; $params[] = $kind; }
if (($_GET['stock'] ?? '') === 'bas') { $where[] = 'EXISTS (SELECT 1 FROM variants v WHERE v.product_id = p.id AND v.stock IS NOT NULL AND v.stock <= 2)'; }
$sort = ($_GET['sort'] ?? '') === 'reverse' ? 'reverse' : '';
$list = load_products(implode(' AND ', $where), $params, $sort === 'reverse' ? 'p.position DESC, p.id ASC' : 'p.position ASC, p.id DESC');
$back = http_build_query(array_filter(['q' => $q, 'kind' => $kind, 'stock' => $_GET['stock'] ?? '', 'sort' => $sort]));

admin_header('Produits', 'produits');
?>
<div class="shead"><h1>Produits</h1><a class="btn" href="<?= e(url('admin/produit.php')) ?>">Ajouter un produit</a></div>
<form class="atool" method="get">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Rechercher un produit" aria-label="Rechercher un produit">
  <select name="kind" aria-label="Catégorie" data-autosubmit><option value="">Toutes les catégories</option><?php foreach (KINDS as $k => $l): ?><option value="<?= $k ?>" <?= $kind === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
  <select name="sort" aria-label="Trier par ordre" data-autosubmit><option value="">Ordre croissant</option><option value="reverse" <?= $sort === 'reverse' ? 'selected' : '' ?>>Ordre décroissant</option></select>
  <button class="btn ghost">Filtrer</button>
</form>
<p class="hint"><?= count($list) ?> produit<?= count($list) > 1 ? 's' : '' ?>. L'étoile met un produit en avant sur la page d'accueil. Un produit masqué n'apparaît plus dans la boutique.</p>
<p class="hint">Filtrez par catégorie, puis modifiez l’ordre directement ci-dessous : les plus petits nombres passent en premier (en cas d’égalité, le produit le plus récent passe avant). L’enregistrement concerne uniquement les produits affichés. La nouvelle collection et les accessoires déco ont leur propre ordre dans leurs onglets.</p>
<form method="post" id="product-order-form">
  <?= csrf_field() ?><input type="hidden" name="action" value="order"><input type="hidden" name="back" value="<?= e($back) ?>">
  <?php if ($list): ?><p><button class="btn" type="submit">Enregistrer l’ordre affiché</button></p><?php endif; ?>
</form>
<div class="atable-wrap"><table class="at">
  <thead><tr><th>Ordre</th><th></th><th>Produit</th><th>Tailles</th><th>Prix</th><th>Stock</th><th>Livraison</th><th>En avant</th><th>Visible</th><th class="r">Actions</th></tr></thead>
  <tbody>
  <?php if (!$list): ?><tr><td colspan="10" class="empty-row">Aucun produit trouvé.</td></tr><?php endif; ?>
  <?php foreach ($list as $p):
      $tracked = array_filter($p['variants'], fn($v) => $v['stock'] !== null);
      $outN = count(array_filter($p['variants'], fn($v) => !in_stock($v)));
      $stockTxt = !$tracked ? '<span class="st">Illimité</span>' : ($outN === count($p['variants']) ? '<span class="st st-annulee">Épuisé</span>' : ($outN ? '<span class="st st-annulee">Partiel</span>' : '<span class="st">' . array_sum(array_map(fn($v) => (int)$v['stock'], $tracked)) . ' en stock</span>'));
  ?>
    <tr class="<?= $p['active'] ? '' : 'inactive' ?>">
      <td><input form="product-order-form" type="number" name="positions[<?= (int)$p['id'] ?>]" value="<?= (int)$p['position'] ?>" min="0" max="1000000" required aria-label="<?= e('Ordre de ' . $p['name']) ?>" style="width:90px"></td>
      <td><div class="th"><?= media($p) ?></div></td>
      <td><a class="nm" href="<?= e(url('admin/produit.php?id=' . $p['id'])) ?>"><?= e($p['name']) ?></a><div class="sub"><?= e(sub_text($p)) ?></div></td>
      <td><?= e(sizes_text($p)) ?></td>
      <td><?= e(price_range($p)) ?><?= is_promo($p) ? ' <span class="st st-promo">Promo</span>' : '' ?></td>
      <td><?= $stockTxt ?></td>
      <td><?= $p['shipping_fee'] === null ? '<span class="muted" title="Tarif par défaut">' . e(money(setting('shipping_fee'))) . '</span>' : ((float)$p['shipping_fee'] > 0 ? e(money($p['shipping_fee'])) : '<span class="st">Offerte</span>') ?></td>
      <td><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>"><button class="star" name="action" value="feature" aria-pressed="<?= $p['featured'] ? 'true' : 'false' ?>" aria-label="Mettre en avant">★</button></form></td>
      <td><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>"><button class="sbtn" name="action" value="active"><?= $p['active'] ? 'Visible' : 'Masqué' ?></button></form></td>
      <td class="r"><div class="acts">
        <a class="sbtn" href="<?= e(url('admin/produit.php?id=' . $p['id'])) ?>">Modifier</a>
        <a class="sbtn" href="<?= e(url('admin/produit.php?copie=' . $p['id'])) ?>">Dupliquer</a>
        <form method="post" data-confirm="Supprimer définitivement « <?= e($p['name']) ?> » ?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>"><button class="sbtn danger" name="action" value="delete">Supprimer</button></form>
      </div></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php admin_footer();
