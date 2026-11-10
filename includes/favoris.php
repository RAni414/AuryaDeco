<?php
require __DIR__ . '/bootstrap.php';
if (empty($CONFIG['base_url'])) $GLOBALS['PUBLIC_BASE_OVERRIDE'] = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/includes/favoris.php'))), '/');
$pageTitle = 'Mes favoris';
require __DIR__ . '/header.php';
?>
<section class="wrap favorites-page" data-favorites-page aria-labelledby="favorites-title">
  <div class="crumbs"><a href="<?= e(url('index.php')) ?>">Accueil</a> / Mes favoris</div>
  <h1 id="favorites-title">Mes favoris</h1>
  <p class="favorites-intro">Retrouvez les plantes et les objets qui vous plaisent.</p>
  <p class="favorites-privacy">Vos favoris sont conservés dans ce navigateur, sans connexion. Ils ne sont pas synchronisés entre appareils. Les prix et disponibilités sont actualisés depuis notre catalogue.</p>
  <p class="favorites-warning" data-favorites-warning role="status" hidden></p>
  <p data-favorites-loading role="status">Chargement de vos favoris…</p>
  <div class="favorites-error" data-favorites-error role="status" hidden><p></p><button type="button" class="btn ghost" data-favorites-retry>Réessayer</button></div>
  <div class="empty favorites-empty" data-favorites-empty hidden>
    <p>Vous n’avez pas encore de favoris.</p>
    <a class="btn" href="<?= e(url('boutique.php')) ?>">Découvrir la boutique</a>
  </div>
  <div class="grid favorites-grid" data-favorites-list></div>
  <noscript><style>[data-favorites-loading]{display:none}</style><p>Activez JavaScript pour utiliser les favoris enregistrés dans votre navigateur. <a href="<?= e(url('boutique.php')) ?>">Découvrir la boutique</a></p></noscript>
</section>
<?php require __DIR__ . '/footer.php'; ?>
