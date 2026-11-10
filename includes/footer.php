</main>

<footer class="site-foot">
  <div class="wrap">
    <div>
      <p class="fname"><?= e(setting('shop_name')) ?></p>
      <p><?= e(setting('about')) ?></p>
      <p>Paiement à la livraison.</p>
    </div>
    <div>
      <h4>Boutique</h4>
      <ul>
        <li><a href="<?= e(url('boutique.php?cat=plante')) ?>">Plantes artificielles</a></li>
        <li><a href="<?= e(url('boutique.php?cat=pot')) ?>">Pots</a></li>
        <li><a href="<?= e(url('boutique.php?cat=acc')) ?>">Accessoires</a></li>
        <li><a href="<?= e(url('boutique.php?promo=1')) ?>">Promotions</a></li>
      </ul>
    </div>
    <div>
      <h4>Informations</h4>
      <ul>
        <li><a href="<?= e(url('page.php?p=pro')) ?>">Professionnels</a></li>
        <li><a href="<?= e(url('page.php?p=contact')) ?>">Contact</a></li>
        <li><a href="<?= e(url('page.php?p=livraison')) ?>">Livraison et retours</a></li>
        <li><a href="<?= e(url('panier.php')) ?>">Mon panier</a></li>
      </ul>
    </div>
    <div>
      <h4>Nous contacter</h4>
      <?php if (public_contact_number() !== ''): ?><p><?= e(public_contact_number()) ?></p><?php endif; ?>
      <?php if (public_contact_number('whatsapp') !== ''): ?>
      <p><a class="footer-whatsapp" href="<?= e(wa_link('Bonjour ' . setting('shop_name') . ', ')) ?>" target="_blank" rel="noopener noreferrer">Nous écrire sur WhatsApp ↗</a></p>
      <?php endif; ?>
      <p><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p>
      <p><?= e(setting('address')) ?></p>
    </div>
  </div>
  <div class="copy">© <?= date('Y') ?> <?= e(setting('shop_name')) ?>. Tous droits réservés.</div>
</footer>

<div class="site-tools" aria-label="Aide et contact">
<?php require __DIR__ . '/deco-widget.php'; ?>
<?php if (public_contact_number('whatsapp') !== ''): ?>

<a class="wa-float" href="<?= e(wa_link('Bonjour ' . setting('shop_name') . ', ')) ?>" target="_blank" rel="noopener" aria-label="Nous écrire sur WhatsApp"><svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7.5-.1 1.5-.6 1.8-1.2.2-.6.2-1.1.1-1.2l-.4-.2z"/></svg></a>

<?php endif; ?>
</div>
<?php
$mobilePage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$mobileLinks = [
    ['index.php', 'Accueil', 'M3 10 12 3l9 7v11h-6v-7H9v7H3z'],
    ['boutique.php', 'Boutique', 'M3 9h18M5 9v12h14V9M3 9l2-6h14l2 6M9 21v-7h6v7'],
    ['includes/favoris.php', 'Favoris', 'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z'],
    ['panier.php', 'Panier', 'M5 7h14l2 14H3L5 7ZM9 8V6a3 3 0 0 1 6 0v2'],
];
?>
<nav class="mobile-dock" aria-label="Navigation mobile">
<?php foreach ($mobileLinks as [$path, $label, $icon]): ?>
  <a href="<?= e(url($path)) ?>"<?= $mobilePage === basename($path) ? ' aria-current="page"' : '' ?>><span class="mobile-dock-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= e($icon) ?>"/></svg><?php if ($label === 'Panier'): ?><span class="badge" data-n="<?= cart_count() ?>"><?= cart_count() ?></span><?php elseif ($label === 'Favoris'): ?><span class="fav-count" data-favorites-count hidden>0</span><?php endif; ?></span><span><?= e($label) ?></span></a>
<?php endforeach; ?>
</nav>
<script src="<?= e(url('assets/js/art.js')) ?>?v=1"></script>
<script src="<?= e(url('assets/js/shop.js')) ?>?v=1"></script>
<script src="<?= e(url('assets/js/product-cards.js')) ?>?v=3" data-card-script data-cart="<?= e(url('panier.php')) ?>" data-csrf="<?= e(csrf_token()) ?>" defer></script>
<?php if (!empty($homePage)): ?><script src="<?= e(url('assets/js/product-carousel.js')) ?>?v=1" defer></script><?php endif; ?>
<div class="fav-notice" role="status" aria-live="polite" data-favorites-notice hidden></div>
<script src="<?= e(url('assets/js/favorites.js')) ?>?v=1" data-favorites-script data-endpoint="<?= e(url('includes/favorites-api.php')) ?>" data-csrf="<?= e(csrf_token()) ?>" defer></script>
<script src="<?= e(url('assets/js/deco.js')) ?>?v=6" defer></script>
<script src="<?= e(url('assets/js/interface.js')) ?>?v=1" defer></script>
<?php if (!empty($homePage)): ?><script src="<?= e(url('assets/js/home.js')) ?>?v=1"></script><?php endif; ?>
</body>
</html>
