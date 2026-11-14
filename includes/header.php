<?php
/** En-tête commun de la boutique. Variables attendues : $pageTitle (facultatif). */
$shopName = setting('shop_name');
$logoHtml = e($shopName);
if (preg_match('/^(.+?)(\p{Lu}[^\p{Lu}]*)$/u', $shopName, $m)) {
    $logoHtml = e($m[1]) . '<b>' . e($m[2]) . '</b>';
}
$q = $_GET['q'] ?? '';
$publicPhone = public_contact_number();
$freeDelivery = (float)setting('free_shipping');
$commercePage = in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), ['panier.php', 'commande.php'], true);
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e(isset($pageTitle) ? $pageTitle . ' · ' . $shopName : $shopName . ' · Plantes artificielles et pots de décoration') ?></title>
<?php if (!empty($metaDesc)): ?><meta name="description" content="<?= e($metaDesc) ?>"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Marcellus&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=1">
<link rel="stylesheet" href="<?= e(url('assets/css/deco.css')) ?>?v=3">
<link rel="stylesheet" href="<?= e(url('assets/css/interface.css')) ?>?v=3">
<link rel="stylesheet" href="<?= e(url('assets/css/favorites.css')) ?>?v=1">
<link rel="stylesheet" href="<?= e(url('assets/css/product-cards.css')) ?>?v=2">
<?php if (!empty($homePage)): ?><link rel="stylesheet" href="<?= e(url('assets/css/product-carousel.css')) ?>?v=2"><?php endif; ?>
<?php if (!empty($homePage)): ?><link rel="stylesheet" href="<?= e(url('assets/css/home.css')) ?>?v=6"><?php endif; ?>
<link rel="stylesheet" href="<?= e(url('assets/css/mobile.css')) ?>?v=3">
<link rel="stylesheet" href="<?= e(url('assets/css/reviews.css')) ?>?v=1">
<link rel="icon" type="image/png" href="<?= e(url('assets/images/aurya-icon.png')) ?>?v=1">
<link rel="apple-touch-icon" href="<?= e(url('assets/images/aurya-icon.png')) ?>?v=1">
</head>
<body<?= $commercePage ? ' class="commerce-page"' : '' ?>>
<a class="skip" href="#app">Aller au contenu</a>
<div class="topbar"><div class="wrap">
  <span>Livraison partout en Tunisie<?= $freeDelivery > 0 ? ' · offerte dès ' . e(money($freeDelivery)) : '' ?></span>
  <?php if ($publicPhone !== ''): ?><a href="tel:<?= e(preg_replace('/[^+0-9]/', '', $publicPhone)) ?>">Commandes : <?= e($publicPhone) ?></a><?php endif; ?>
</div></div>

<header class="site has-favorites">
  <div class="wrap hrow">
    <button class="ibtn menu-toggle" id="menuBtn" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="mainNav">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
    </button>
    <a class="logo" href="<?= e(url('index.php')) ?>" aria-label="Accueil">
      <img class="aurya-brand-image" src="<?= e(url('assets/images/aurya-logo.png')) ?>" width="1774" height="887" alt="Aurya Déco" fetchpriority="high">
    </a>
    <form class="search" role="search" action="<?= e(url('boutique.php')) ?>" method="get">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Rechercher une plante, un pot…" aria-label="Rechercher">
      <button aria-label="Lancer la recherche"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></button>
    </form>
    <div class="icons">
      <a class="ibtn favorites-link" href="<?= e(url('includes/favoris.php')) ?>" aria-label="Mes favoris, 0 produit">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg>
        <span class="fav-count" data-favorites-count hidden>0</span>
      </a>
      <a class="ibtn" href="<?= e(url('panier.php')) ?>" aria-label="Voir le panier">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 8h14l-1.2 11.2a1 1 0 0 1-1 .8H7.2a1 1 0 0 1-1-.8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
        <span class="badge" data-n="<?= cart_count() ?>"><?= cart_count() ?></span>
      </a>
    </div>
  </div>
  <div class="msearch">
    <form class="search" role="search" action="<?= e(url('boutique.php')) ?>" method="get">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Rechercher une plante, un pot…" aria-label="Rechercher">
      <button aria-label="Lancer la recherche"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></button>
    </form>
  </div>
  <nav class="main" id="mainNav" aria-label="Catégories">
    <?php require __DIR__ . '/navigation-menu.php'; ?>
  </nav>
</header>

<main id="app">
<?= flash() ?>
