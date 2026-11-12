<?php
require __DIR__ . '/includes/bootstrap.php';

$lines = cart_lines();
if (!$lines) {
    redirect('panier.php');
}
$errors = [];
$f = ['nom' => '', 'tel' => '', 'gov' => '', 'ville' => '', 'adr' => '', 'note' => ''];

if (is_post()) {
    csrf_check();
    foreach ($f as $k => $_) {
        $f[$k] = trim((string)($_POST[$k] ?? ''));
    }
    if ($f['nom'] === '' || mb_strlen($f['nom']) > 120) $errors[] = 'Indiquez votre nom et prénom.';
    $digits = preg_replace('/\D/', '', $f['tel']);
    if (strlen($digits) < 8 || strlen($digits) > 15) $errors[] = 'Le numéro de téléphone doit contenir au moins 8 chiffres.';
    if (!in_array($f['gov'], GOVERNORATES, true)) $errors[] = 'Choisissez votre gouvernorat.';
    if ($f['ville'] === '') $errors[] = 'Indiquez votre ville.';
    
    if (!empty($_POST['website'])) $errors[] = 'Envoi refusé.'; // piège anti-robots

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $subtotal = 0.0;
            $rawShip = 0.0;
            $items = [];
            foreach ($lines as $l) {
                $st = $pdo->prepare('SELECT v.*, p.name, p.kind, p.active, p.shipping_fee FROM variants v JOIN products p ON p.id = v.product_id WHERE v.id = ? FOR UPDATE');
                $st->execute([$l['variant']['id']]);
                $v = $st->fetch();
                if (!$v || !$v['active']) {
                    throw new RuntimeException("Un produit de votre panier n'est plus disponible. Vérifiez votre panier.");
                }
                if ($v['stock'] !== null && (int)$v['stock'] < $l['qty']) {
                    throw new RuntimeException('Stock insuffisant pour « ' . $v['name'] . ' » (' . $l['label'] . '). Il en reste ' . max(0, (int)$v['stock']) . '.');
                }
                $subtotal += (float)$v['price'] * $l['qty'];
                $rawShip += product_ship_fee($v['shipping_fee']) * $l['qty'];
                $items[] = [$v, $l];
            }
            $shipping = shipping_total($subtotal, $rawShip);
            $pdo->prepare('INSERT INTO orders (customer_name, phone, governorate, city, address, note, subtotal, shipping, total) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([$f['nom'], $f['tel'], $f['gov'], $f['ville'], $f['adr'], $f['note'], $subtotal, $shipping, $subtotal + $shipping]);
            $oid = (int)$pdo->lastInsertId();
            $number = 'AD' . date('ymd') . '-' . str_pad((string)$oid, 4, '0', STR_PAD_LEFT);
            $pdo->prepare('UPDATE orders SET number = ? WHERE id = ?')->execute([$number, $oid]);
            $ii = $pdo->prepare('INSERT INTO order_items (order_id, product_id, variant_id, name, variant_label, price, qty) VALUES (?,?,?,?,?,?,?)');
            $us = $pdo->prepare('UPDATE variants SET stock = stock - ? WHERE id = ? AND stock IS NOT NULL');
            foreach ($items as [$v, $l]) {
                $ii->execute([$oid, $v['product_id'], $v['id'], $v['name'], $l['label'], $v['price'], $l['qty']]);
                $us->execute([$l['qty'], $v['id']]);
            }
            $pdo->commit();

            $_SESSION['cart'] = [];
            $_SESSION['last_order'] = $number;

            $to = setting('notify_email');
            if ($to !== '' && function_exists('mail')) {
                $body = "Nouvelle commande $number\n\n";
                foreach ($items as [$v, $l]) {
                    $body .= '- ' . $v['name'] . ' (' . $l['label'] . ') x' . $l['qty'] . ' : ' . money($v['price'] * $l['qty']) . "\n";
                }
                $body .= "\nTotal : " . money($subtotal + $shipping) . "\n\n" . $f['nom'] . "\n" . $f['tel'] . "\n" . $f['adr'] . ', ' . $f['ville'] . ', ' . $f['gov'] . "\n" . $f['note'];
                @mail($to, '=?UTF-8?B?' . base64_encode('Nouvelle commande ' . $number) . '?=', $body, "Content-Type: text/plain; charset=UTF-8\r\n");
            }
            redirect('merci.php');
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $ex instanceof RuntimeException ? $ex->getMessage() : "La commande n'a pas pu être enregistrée. Réessayez dans un instant.";
            $lines = cart_lines();
        }
    }
}

$subtotal = array_sum(array_column($lines, 'total'));
$shipping = shipping_for($subtotal, $lines);
$pageTitle = 'Commande';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
  <div class="crumbs"><a href="<?= e(url('index.php')) ?>">Accueil</a> / <a href="<?= e(url('panier.php')) ?>">Panier</a> / Commande</div>
  <h1 class="ptitle">Finaliser la commande</h1>
  <div class="co">
    <form method="post" class="coform" novalidate>
      <?= csrf_field() ?>
      <?php foreach ($errors as $err): ?><div class="flash flash-err" role="alert"><?= e($err) ?></div><?php endforeach; ?>
      <div class="two">
        <label class="field">Nom et prénom<input name="nom" required autocomplete="name" value="<?= e($f['nom']) ?>"></label>
        <label class="field">Téléphone<input name="tel" required inputmode="tel" autocomplete="tel" placeholder="ex. 20 123 456" value="<?= e($f['tel']) ?>"></label>
      </div>
      <div class="two">
        <label class="field">Gouvernorat<select name="gov" required><option value="">Choisir</option><?php foreach (GOVERNORATES as $g): ?><option <?= $f['gov'] === $g ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?></select></label>
        <label class="field">Ville<input name="ville" required value="<?= e($f['ville']) ?>"></label>
      </div>
      
      <label class="field">Remarque (facultatif)<textarea name="note" placeholder="Étage, horaires de livraison, choix de couleur…"><?= e($f['note']) ?></textarea></label>
      <div class="hp" aria-hidden="true"><label>Ne pas remplir<input name="website" tabindex="-1" autocomplete="off"></label></div>
      <div class="card" style="padding:14px 16px"><strong>Paiement à la livraison</strong><div class="muted" style="font-size:14.5px">Vous réglez en espèces à la réception de votre commande.</div></div>
      <button class="btn block" type="submit">Confirmer ma commande</button>
      <p class="muted small">Nous vous appelons pour confirmer la livraison.</p>
    </form>
    <aside class="card">
      <h2 style="font-size:26px;margin-bottom:8px">Récapitulatif</h2>
      <?php foreach ($lines as $l): ?>
        <div class="line compact"><div class="thumb"><?= media($l['product']) ?></div><div><div class="nm"><?= e($l['product']['name']) ?></div><div class="vr"><?= e($l['label']) ?>, quantité <?= (int)$l['qty'] ?></div></div><strong><?= e(money($l['total'])) ?></strong></div>
      <?php endforeach; ?>
      <div class="sums">
        <div class="sum"><span>Sous-total</span><span><?= e(money($subtotal)) ?></span></div>
        <div class="sum"><span>Livraison</span><span><?= $shipping > 0 ? e(money($shipping)) : 'Offerte' ?></span></div>
        <div class="sum total"><span>Total</span><span><?= e(money($subtotal + $shipping)) ?></span></div>
      </div>
    </aside>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php';
