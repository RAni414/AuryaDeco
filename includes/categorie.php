<?php
require __DIR__ . '/bootstrap.php';
if (empty($CONFIG['base_url'])) $GLOBALS['PUBLIC_BASE_OVERRIDE'] = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/includes/categorie.php'))), '/');
require_once __DIR__ . '/catalogue-categories.php';
$id = (int)($_GET['id'] ?? 0);
$allCategories = catalogue_categories();
$visibleCategories = [];
foreach ($allCategories as $category) if ($category['active']) $visibleCategories[(int)$category['id']] = $category;
$category = $visibleCategories[$id] ?? null;
if ($category && str_starts_with($category['parent_key'] ?? '', 'custom_') && !isset($visibleCategories[(int)substr($category['parent_key'], 7)])) $category = null;
if (!$category) {
    http_response_code(404);
    $pageTitle = 'Catégorie introuvable';
    require __DIR__ . '/header.php';
    echo '<div class="wrap"><h1>Catégorie introuvable</h1><p>Cette catégorie n’est pas disponible.</p></div>';
    require __DIR__ . '/footer.php';
    exit;
}
$children = array_filter($visibleCategories, fn($child) => $child['parent_key'] === 'custom_' . $id);
$categoryIds = array_merge([$id], array_map('intval', array_keys($children)));
$list = catalogue_category_products($categoryIds);
$pageTitle = $category['name'];
require __DIR__ . '/header.php';
?>
<div class="wrap">
  <div class="crumbs"><a href="<?= e(url('index.php')) ?>">Accueil</a> / <?= e($category['name']) ?></div>
  <h1 class="ptitle"><?= e($category['name']) ?></h1>
  <?php if ($children): ?><div class="chips"><?php foreach ($children as $child): ?><a class="chip" href="<?= e(url('includes/categorie.php?id=' . (int)$child['id'])) ?>"><?= e($child['name']) ?></a><?php endforeach; ?></div><?php endif; ?>
  <?= product_grid($list, 'Les produits de cette catégorie arrivent bientôt.') ?>
</div>
<?php require __DIR__ . '/footer.php';
