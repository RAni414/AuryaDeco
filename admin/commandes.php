<?php
require __DIR__ . '/../includes/admin.php';
require_admin();
$statut = $_GET['statut'] ?? '';
$q = trim((string)($_GET['q'] ?? ''));
$where = ['1'];
$params = [];
if (isset(ORDER_STATUS[$statut])) { $where[] = 'status = ?'; $params[] = $statut; }
if ($q !== '') { $where[] = '(number LIKE ? OR customer_name LIKE ? OR phone LIKE ?)'; $l = '%' . $q . '%'; array_push($params, $l, $l, $l); }
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 30;
$cnt = db()->prepare('SELECT COUNT(*) FROM orders WHERE ' . implode(' AND ', $where));
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$st = db()->prepare('SELECT o.*, (SELECT SUM(qty) FROM order_items i WHERE i.order_id = o.id) AS nb FROM orders o WHERE ' . implode(' AND ', $where) . ' ORDER BY o.id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per));
$st->execute($params);
$orders = $st->fetchAll();
$counts = [];
foreach (db()->query('SELECT status, COUNT(*) c FROM orders GROUP BY status') as $r) { $counts[$r['status']] = (int)$r['c']; }
admin_header('Commandes', 'commandes');
?>
<h1>Commandes</h1>
<div class="chips">
  <a class="chip" href="<?= e(url('admin/commandes.php')) ?>" aria-current="<?= $statut === '' ? 'true' : 'false' ?>">Toutes (<?= array_sum($counts) ?>)</a>
  <?php foreach (ORDER_STATUS as $k => $l): ?><a class="chip" href="<?= e(url('admin/commandes.php?statut=' . $k)) ?>" aria-current="<?= $statut === $k ? 'true' : 'false' ?>"><?= e($l) ?> (<?= $counts[$k] ?? 0 ?>)</a><?php endforeach; ?>
</div>
<form class="atool" method="get">
  <?php if ($statut): ?><input type="hidden" name="statut" value="<?= e($statut) ?>"><?php endif; ?>
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Numéro, nom ou téléphone" aria-label="Rechercher une commande">
  <button class="btn ghost">Rechercher</button>
</form>
<div class="atable-wrap"><table class="at">
  <thead><tr><th>Numéro</th><th>Date</th><th>Client</th><th>Téléphone</th><th>Gouvernorat</th><th>Articles</th><th>Total</th><th>Statut</th></tr></thead>
  <tbody>
  <?php if (!$orders): ?><tr><td colspan="8" class="empty-row">Aucune commande.</td></tr><?php endif; ?>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td><a class="nm" href="<?= e(url('admin/commande.php?id=' . $o['id'])) ?>"><?= e($o['number']) ?></a></td>
      <td><?= e(date('d/m/Y H:i', strtotime($o['created_at']))) ?></td>
      <td><?= e($o['customer_name']) ?></td>
      <td><a href="tel:<?= e(preg_replace('/[^\d+]/', '', $o['phone'])) ?>"><?= e($o['phone']) ?></a></td>
      <td><?= e($o['governorate']) ?></td>
      <td><?= (int)$o['nb'] ?></td>
      <td><?= e(money($o['total'])) ?></td>
      <td><?= status_badge($o['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php if ($total > $per): $pages = (int)ceil($total / $per); ?>
  <nav class="pager" aria-label="Pages">
    <?php for ($i = 1; $i <= $pages; $i++): ?><a class="chip" href="<?= e(url('admin/commandes.php?' . http_build_query(array_filter(['statut' => $statut, 'q' => $q, 'page' => $i])))) ?>" aria-current="<?= $i === $page ? 'true' : 'false' ?>"><?= $i ?></a><?php endfor; ?>
  </nav>
<?php endif; ?>
<?php admin_footer();
