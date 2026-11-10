<?php
require __DIR__ . '/includes/bootstrap.php';
$number = $_SESSION['last_order'] ?? null;
$order = null;
$items = [];
if ($number) {
    $st = db()->prepare('SELECT * FROM orders WHERE number = ?');
    $st->execute([$number]);
    $order = $st->fetch() ?: null;
    if ($order) {
        $st = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $st->execute([$order['id']]);
        $items = $st->fetchAll();
    }
}
if (!$order) {
    redirect('index.php');
}
$msg = 'Bonjour ' . setting('shop_name') . ", je viens de passer la commande {$order['number']} :\n";
foreach ($items as $it) {
    $msg .= '- ' . $it['name'] . ' (' . $it['variant_label'] . ') x' . $it['qty'] . "\n";
}
$msg .= 'Total : ' . money($order['total']);
$pageTitle = 'Merci pour votre commande';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
  <div class="thanks card">
    <h1 class="ptitle">Merci <?= e(explode(' ', $order['customer_name'])[0]) ?> !</h1>
    <p>Votre commande <strong><?= e($order['number']) ?></strong> est enregistrée. Nous vous appelons au <?= e($order['phone']) ?> pour confirmer la livraison.</p>
    <div class="sums">
      <?php foreach ($items as $it): ?><div class="sum"><span><?= e($it['name']) ?> (<?= e($it['variant_label']) ?>) x<?= (int)$it['qty'] ?></span><span><?= e(money($it['price'] * $it['qty'])) ?></span></div><?php endforeach; ?>
      <div class="sum"><span>Livraison</span><span><?= (float)$order['shipping'] > 0 ? e(money($order['shipping'])) : 'Offerte' ?></span></div>
      <div class="sum total"><span>Total à payer à la livraison</span><span><?= e(money($order['total'])) ?></span></div>
    </div>
    <div class="ctas">
      <a class="btn" href="<?= e(wa_link($msg)) ?>" target="_blank" rel="noopener">Confirmer plus vite sur WhatsApp</a>
      <a class="btn ghost" href="<?= e(url('index.php')) ?>">Retour à l'accueil</a>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php';
