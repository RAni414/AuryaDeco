<?php $storefrontScript = basename($_SERVER['SCRIPT_NAME'] ?? ''); ?>
<?php if ($storefrontScript === 'produit.php' && isset($p) && is_array($p) && !empty($p['variants'])): ?>
<aside class="mobile-purchase" data-mobile-purchase hidden aria-label="Acheter ce produit">
  <div><span data-mobile-price><?= e(money($p['variants'][$sel ?? 0]['price'])) ?></span><small><?= e($p['name']) ?></small></div>
  <button class="btn" type="submit" form="buyForm" data-mobile-add>Ajouter au panier</button>
</aside>
<?php elseif ($storefrontScript === 'panier.php' && !empty($lines)): ?>
<aside class="mobile-purchase mobile-cartbar" aria-label="Total du panier et commande">
  <div><small>Total, livraison comprise</small><strong><?= e(money($subtotal + $shipping)) ?></strong></div>
  <a class="btn" href="<?= e(url('commande.php')) ?>">Commander</a>
</aside>
<?php endif; ?>
