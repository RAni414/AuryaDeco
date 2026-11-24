<?php
$deliveryDelay = trim((string)setting('delivery_delay')) ?: 'Sous 48 h';
$freeDeliveryThreshold = (float)setting('free_shipping');
?>
<div class="purchase-reassurance">
  <p><strong>Livraison en Tunisie</strong><span><?= e(ship_text($p['shipping_fee'])) ?><?= $freeDeliveryThreshold > 0 ? '. Offerte dès ' . e(money($freeDeliveryThreshold)) . ' d’achat.' : '.' ?></span></p>
  <p><strong><?= e($deliveryDelay) ?></strong><span>Délai de livraison annoncé.</span></p>
  <p><strong>Paiement à la livraison</strong><span>Réglez votre commande à réception.</span></p>
</div>
<?php if ($p['kind'] === 'plante' || $p['kind'] === 'pot'): ?><p class="product-included"><?= e(($p['included_text'] ?? '') ?: ($p['kind'] === 'plante' ? 'Plante livrée avec un pot de culture noir. Cache-pot décoratif vendu séparément.' : 'Cache-pot vendu seul, sans plante.')) ?></p><?php endif; ?>
<a class="product-delivery-link" href="<?= e(url('page.php?p=livraison')) ?>">Livraison et retours</a>
