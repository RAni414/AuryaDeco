<?php $checkoutCurrent = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'panier.php' ? 'cart' : 'checkout'; ?>
<ol class="checkout-progress" aria-label="Étapes de votre commande">
  <li <?= $checkoutCurrent === 'cart' ? 'aria-current="step"' : '' ?>><a href="<?= e(url('panier.php')) ?>">Votre panier</a></li>
  <li <?= $checkoutCurrent === 'checkout' ? 'aria-current="step"' : '' ?>>Vos coordonnées</li>
  <li>Confirmation</li>
</ol>
