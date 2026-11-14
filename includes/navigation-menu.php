<?php
require_once __DIR__ . '/navigation.php';
require_once __DIR__ . '/catalogue-categories.php';
$menuSaved = json_decode(settings()['navigation_menu'] ?? '{}', true);
$menuItems = catalogue_menu(navigation_merge(is_array($menuSaved) ? $menuSaved : []), catalogue_categories());
?>
<ul>
  <?php foreach ($menuItems as $menuKey => $menuItem): if (!$menuItem['visible']) continue; ?>
    <li><a<?= $menuKey === 'promo' ? ' class="outlet"' : '' ?> href="<?= e(url($menuItem['path'])) ?>"><?= e($menuItem['label']) ?></a>
      <?php $menuChildren = array_filter($menuItem['children'], fn($child) => $child['visible']); ?>
      <?php if ($menuChildren): ?>
        <ul class="drop">
          <?php foreach ($menuChildren as $menuChild): ?>
            <li><a href="<?= e(url($menuChild['path'])) ?>"><?= e($menuChild['label']) ?><?php if ($menuChild['hint'] !== ''): ?> <small><?= e($menuChild['hint']) ?></small><?php endif; ?></a></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
</ul>
