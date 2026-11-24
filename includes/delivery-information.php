<h1 class="ptitle">Livraison et retours</h1>
<div class="desc delivery-information">
  <h2>Livraison en Tunisie</h2>
  <p>Nous livrons dans les 24 gouvernorats. Délai annoncé : <strong><?= e(trim((string)setting('delivery_delay')) ?: 'Sous 48 h') ?></strong>.</p>
  <p>Les frais sont indiqués sur chaque fiche produit et calculés dans votre panier selon les articles et quantités choisis.<?php if ((float)setting('free_shipping') > 0): ?> La livraison est offerte dès <strong><?= e(money(setting('free_shipping'))) ?></strong> d’achat.<?php endif; ?></p>
  <h2>Paiement à la livraison</h2>
  <p>Réglez votre commande à réception.</p>
  <h2>Un problème à la réception ?</h2>
  <p><?= nl2br(e(setting('returns_policy'))) ?></p>
  <p><a class="btn" href="<?= e(url('page.php?p=contact')) ?>">Nous contacter</a></p>
</div>
