<?php
require __DIR__ . '/../includes/admin.php';
require_admin();
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM orders WHERE id = ?');
$st->execute([$id]);
$o = $st->fetch();
if (!$o) { flash('Commande introuvable.', 'err'); redirect('admin/commandes.php'); }

if (is_post()) {
    csrf_check();
    $new = $_POST['status'] ?? $o['status'];
    if (!isset(ORDER_STATUS[$new])) $new = $o['status'];
    $released = ['annulee', 'retour'];
    $pdo = db();
    $pdo->beginTransaction();
    if (!in_array($o['status'], $released, true) && in_array($new, $released, true)) {
        apply_stock($id, +1);
    } elseif (in_array($o['status'], $released, true) && !in_array($new, $released, true)) {
        apply_stock($id, -1);
    }
    $pdo->prepare('UPDATE orders SET status = ?, admin_note = ? WHERE id = ?')->execute([$new, trim((string)($_POST['admin_note'] ?? '')), $id]);
    $pdo->commit();
    flash('Commande mise à jour.' . ($new !== $o['status'] && in_array($new, $released, true) ? ' Les articles ont été remis en stock.' : ''));
    redirect('admin/commande.php?id=' . $id);
}

$it = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$it->execute([$id]);
$items = $it->fetchAll();
$phone = preg_replace('/\D/', '', $o['phone']);
if (strlen($phone) === 8) $phone = '216' . $phone;
$msg = 'Bonjour ' . explode(' ', $o['customer_name'])[0] . ', ici ' . setting('shop_name') . '. Nous confirmons votre commande ' . $o['number'] . ' d\'un total de ' . money($o['total']) . ', livrée à ' . $o['city'] . '. Merci !';
admin_header('Commande ' . $o['number'], 'commandes');
?>
<div class="crumbs noprint"><a href="<?= e(url('admin/commandes.php')) ?>">Commandes</a> / <?= e($o['number']) ?></div>
<div class="shead"><h1>Commande <?= e($o['number']) ?></h1><div class="acts noprint"><button class="sbtn" onclick="window.print()">Imprimer le bon</button></div></div>
<p class="muted">Passée le <?= e(date('d/m/Y à H:i', strtotime($o['created_at']))) ?> · <?= status_badge($o['status']) ?></p>
<div class="cols">
  <section class="card">
    <h2>Articles</h2>
    <div class="atable-wrap plain"><table class="at">
      <thead><tr><th>Produit</th><th>Taille</th><th>Prix</th><th>Qté</th><th class="r">Total</th></tr></thead>
      <tbody>
      <?php foreach ($items as $i): ?>
        <tr><td><?= $i['product_id'] ? '<a href="' . e(url('admin/produit.php?id=' . $i['product_id'])) . '">' . e($i['name']) . '</a>' : e($i['name']) ?></td><td><?= e($i['variant_label']) ?></td><td><?= e(money($i['price'])) ?></td><td><?= (int)$i['qty'] ?></td><td class="r"><?= e(money($i['price'] * $i['qty'])) ?></td></tr>
      <?php endforeach; ?>
      <tr><td colspan="4">Livraison</td><td class="r"><?= (float)$o['shipping'] > 0 ? e(money($o['shipping'])) : 'Offerte' ?></td></tr>
      <tr class="tot"><td colspan="4">Total à encaisser</td><td class="r"><?= e(money($o['total'])) ?></td></tr>
      </tbody>
    </table></div>
  </section>
  <section class="card">
    <h2>Client</h2>
    <p><strong><?= e($o['customer_name']) ?></strong><br><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $o['phone'])) ?>"><?= e($o['phone']) ?></a><br><?= e($o['address']) ?><br><?= e($o['city']) ?>, <?= e($o['governorate']) ?></p>
    <?php if ($o['note']): ?><p class="note"><strong>Remarque du client :</strong> <?= nl2br(e($o['note'])) ?></p><?php endif; ?>
    <a class="btn noprint" href="<?= e(wa_link($msg, $phone)) ?>" target="_blank" rel="noopener">Confirmer par WhatsApp</a>
    <form method="post" class="noprint" style="margin-top:20px">
      <?= csrf_field() ?>
      <label class="field">Statut<select name="status"><?php foreach (ORDER_STATUS as $k => $l): ?><option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
      <label class="field">Note interne<textarea name="admin_note" rows="3" placeholder="Visible seulement dans l'administration"><?= e($o['admin_note']) ?></textarea></label>
      <p class="hint">Passer une commande en « Annulée » ou « Retournée » remet ses articles en stock.</p>
      <button class="btn">Enregistrer</button>
    </form>
  </section>
</div>
<?php admin_footer();
