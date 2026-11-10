<?php
require __DIR__ . '/includes/bootstrap.php';

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $cart = cart();
    if ($action === 'add') {
        $vid = (int)($_POST['variant_id'] ?? 0);
        $qty = max(1, min(99, (int)($_POST['qty'] ?? 1)));
        $st = db()->prepare('SELECT v.stock, p.active, p.name FROM variants v JOIN products p ON p.id = v.product_id WHERE v.id = ?');
        $st->execute([$vid]);
        $row = $st->fetch();
        if (!$row || !$row['active']) {
            flash("Ce produit n'est plus disponible.", 'err');
        } else {
            $want = ($cart[$vid] ?? 0) + $qty;
            if ($row['stock'] !== null && $want > (int)$row['stock']) {
                $want = (int)$row['stock'];
                flash($want > 0 ? "Il ne reste que $want exemplaire(s) de cette taille : la quantité a été ajustée." : 'Cette taille est en rupture de stock.', 'err');
            } else {
                flash('« ' . $row['name'] . ' » a été ajouté au panier.');
            }
            if ($want > 0) $cart[$vid] = $want;
        }
    } elseif ($action === 'update') {
        foreach ((array)($_POST['qty'] ?? []) as $vid => $q) {
            $q = (int)$q;
            if ($q <= 0) unset($cart[(int)$vid]);
            else $cart[(int)$vid] = min(99, $q);
        }
        if (isset($_POST['remove'])) {
            unset($cart[(int)$_POST['remove']]);
        }
    } elseif ($action === 'remove') {
        unset($cart[(int)($_POST['variant_id'] ?? 0)]);
    }
    $_SESSION['cart'] = $cart;
    redirect('panier.php');
}

$lines = cart_lines();
// Nettoyage : retire du panier ce qui n'existe plus.
$valid = [];
foreach ($lines as $l) $valid[(int)$l['variant']['id']] = $l['qty'];
$_SESSION['cart'] = $valid;

$subtotal = array_sum(array_column($lines, 'total'));
$shipping = shipping_for($subtotal, $lines);
$free = (float)setting('free_shipping');
$pageTitle = 'Panier';
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
  <div class="crumbs"><a href="<?= e(url('index.php')) ?>">Accueil</a> / Panier</div>
  <h1 class="ptitle">Votre panier</h1>
  <?php if (!$lines): ?>
    <div class="empty">Votre panier est vide.<br><br><a class="btn" href="<?= e(url('boutique.php')) ?>">Parcourir la boutique</a></div>
  <?php else: ?>
  <div class="co">
    <form method="post" class="card cart-list">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="update">
      <?php foreach ($lines as $l): $v = $l['variant']; $p = $l['product']; $out = $v['stock'] !== null && (int)$v['stock'] < $l['qty']; ?>
        <div class="line">
          <a class="thumb" href="<?= e(url('produit.php?p=' . rawurlencode($p['slug']))) ?>"><?= media($p) ?></a>
          <div>
            <a class="nm" href="<?= e(url('produit.php?p=' . rawurlencode($p['slug']))) ?>"><?= e($p['name']) ?></a>
            <div class="vr"><?= e($l['label']) ?>, <?= e(money($v['price'])) ?></div>
            <div class="vr">Livraison : <?= $l['ship'] > 0 ? e(money($l['ship'])) . ' par article' : 'offerte' ?></div>
            <?php if ($out): ?><div class="oos">Stock insuffisant : il en reste <?= max(0, (int)$v['stock']) ?>.</div><?php endif; ?>
            <div class="qty sm"><button type="button" data-step="-1" aria-label="Diminuer">−</button><input type="number" name="qty[<?= (int)$v['id'] ?>]" value="<?= (int)$l['qty'] ?>" min="0" max="99" aria-label="Quantité" data-autosave><button type="button" data-step="1" aria-label="Augmenter">+</button></div>
          </div>
          <div class="lt">
            <strong><?= e(money($l['total'])) ?></strong>
            <button class="rm" name="remove" value="<?= (int)$v['id'] ?>" formaction="<?= e(url('panier.php')) ?>" data-remove>Retirer</button>
          </div>
        </div>
      <?php endforeach; ?>
      <button class="btn ghost" type="submit" style="margin-top:14px">Mettre à jour le panier</button>
    </form>
    <aside class="card">
      <h2 style="font-size:26px;margin-bottom:12px">Total</h2>
      <?php if ($free > 0 && $subtotal < $free): ?><div class="ship-hint">Plus que <?= e(money($free - $subtotal)) ?> pour la livraison offerte.</div><?php elseif ($free > 0): ?><div class="ship-hint">La livraison vous est offerte.</div><?php endif; ?>
      <div class="sums">
        <div class="sum"><span>Sous-total</span><span><?= e(money($subtotal)) ?></span></div>
        <div class="sum"><span>Livraison</span><span><?= $shipping > 0 ? e(money($shipping)) : 'Offerte' ?></span></div>
        <div class="sum total"><span>Total</span><span><?= e(money($subtotal + $shipping)) ?></span></div>
      </div>
      <a class="btn block" href="<?= e(url('commande.php')) ?>">Passer la commande</a>
      <a class="btn ghost block" style="margin-top:8px" href="<?= e(url('boutique.php')) ?>">Continuer mes achats</a>
    </aside>
  </div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php';
