<?php
require_once __DIR__ . '/navigation.php';
require_once __DIR__ . '/catalogue-categories.php';
$menuSaved = json_decode(settings()['navigation_menu'] ?? '{}', true);
$menuItems = catalogue_menu(navigation_merge(is_array($menuSaved) ? $menuSaved : []), catalogue_categories());
?>
<div class="menu-drawer-header">
  <form role="search" action="<?= e(url('boutique.php')) ?>" method="get">
    <input type="search" name="q" placeholder="Rechercher des produits" aria-label="Rechercher des produits">
    <button type="submit" aria-label="Lancer la recherche"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></button>
  </form>
  <button type="button" data-menu-close class="menu-drawer-close" aria-label="Fermer le menu">×</button>
</div>
<ul>
  <?php foreach ($menuItems as $menuKey => $menuItem): if (!$menuItem['visible'] || in_array($menuKey, ['account', 'cart', 'checkout'], true)) continue; ?>
    <?php
      $menuChildren = array_filter($menuItem['children'], fn($child) => $child['visible']);
      $menuChildId = 'nav-children-' . $menuKey;
    ?>
    <li class="<?= $menuChildren ? 'has-subcategories' : '' ?>"><a<?= $menuKey === 'promo' ? ' class="outlet"' : '' ?> href="<?= e(url($menuItem['path'])) ?>"><?= e($menuItem['label']) ?></a>
      <?php if ($menuChildren): ?>
        <button type="button" class="menu-child-toggle" data-menu-children aria-expanded="false" aria-controls="<?= e($menuChildId) ?>" aria-label="<?= e('Afficher les sous-catégories de ' . $menuItem['label']) ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg></button>
        <ul class="drop" id="<?= e($menuChildId) ?>">
          <?php foreach ($menuChildren as $menuChild): ?>
            <li><a href="<?= e(url($menuChild['path'])) ?>"><?= e($menuChild['label']) ?><?php if ($menuChild['hint'] !== ''): ?> <small><?= e($menuChild['hint']) ?></small><?php endif; ?></a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
</ul>
