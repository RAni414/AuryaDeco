<?php
require __DIR__ . '/../includes/admin.php';
require_admin();
$pdo = db();
$new = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'nouvelle'")->fetchColumn();
$toShip = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'confirmee'")->fetchColumn();
$month = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status NOT IN ('annulee','retour') AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
$monthCount = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status NOT IN ('annulee','retour') AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
$out = $pdo->query("SELECT p.id, p.name, v.height, v.diameter, v.label, p.kind, v.stock FROM variants v JOIN products p ON p.id = v.product_id WHERE p.active = 1 AND v.stock IS NOT NULL AND v.stock <= 2 ORDER BY v.stock ASC, p.name LIMIT 10")->fetchAll();
$last = $pdo->query('SELECT * FROM orders ORDER BY id DESC LIMIT 8')->fetchAll();
admin_header('Tableau de bord', 'index');
?>
<h1>Tableau de bord</h1>
<div class="kpis">
  <a class="kpi" href="<?= e(url('admin/commandes.php?statut=nouvelle')) ?>"><strong><?= $new ?></strong><span>Commande<?= $new > 1 ? 's' : '' ?> à confirmer</span></a>
  <a class="kpi" href="<?= e(url('admin/commandes.php?statut=confirmee')) ?>"><strong><?= $toShip ?></strong><span>À expédier</span></a>
  <div class="kpi"><strong><?= e(money($month)) ?></strong><span>Ventes du mois (<?= $monthCount ?> commande<?= $monthCount > 1 ? 's' : '' ?>)</span></div>
  <a class="kpi" href="<?= e(url('admin/produits.php?stock=bas')) ?>"><strong><?= count($out) ?></strong><span>Tailles en rupture de stock</span></a>
</div>
<div class="cols">
  <section class="card">
    <div class="shead"><h2>Dernières commandes</h2><a href="<?= e(url('admin/commandes.php')) ?>">Toutes les commandes</a></div>
    <?php if (!$last): ?><p class="muted">Aucune commande pour l'instant. Elles apparaîtront ici dès qu'un client commandera sur la boutique.</p><?php else: ?>
    <div class="atable-wrap"><table class="at"><thead><tr><th>Numéro</th><th>Date</th><th>Client</th><th>Total</th><th>Statut</th></tr></thead><tbody>
      <?php foreach ($last as $o): ?><tr><td><a href="<?= e(url('admin/commande.php?id=' . $o['id'])) ?>"><?= e($o['number']) ?></a></td><td><?= e(date('d/m H:i', strtotime($o['created_at']))) ?></td><td><?= e($o['customer_name']) ?><div class="sub"><?= e($o['governorate']) ?></div></td><td><?= e(money($o['total'])) ?></td><td><?= status_badge($o['status']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </section>
  <section class="card">
    <div class="shead"><h2>Rupture de stock</h2></div>
    <?php if (!$out): ?><p class="muted">Aucune taille en rupture de stock. Le stock n'est suivi que pour les tailles où vous avez indiqué une quantité.</p><?php else: ?>
      <ul class="plain"><?php foreach ($out as $r): $lbl = $r['kind'] === 'plante' ? 'H ' . $r['height'] . ' cm' : ($r['kind'] === 'pot' ? 'Ø ' . $r['diameter'] . ' cm' : $r['label']); ?>
        <li><a href="<?= e(url('admin/produit.php?id=' . $r['id'])) ?>"><?= e($r['name']) ?></a> <span class="muted"><?= e($lbl) ?></span> <?= (int)$r['stock'] <= 0 ? '<span class="st st-annulee">Épuisé</span>' : '<span class="st">Reste ' . (int)$r['stock'] . '</span>' ?></li>
      <?php endforeach; ?></ul>
    <?php endif; ?>
  </section>
</div>
<?php admin_footer();
