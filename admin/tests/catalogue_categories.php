<?php
session_save_path(sys_get_temp_dir());
require __DIR__ . '/../../includes/admin.php';
require_once __DIR__ . '/../../includes/navigation.php';
require_once __DIR__ . '/../../includes/catalogue-categories.php';
function verify_category(bool $ok, string $label): void {
    if (!$ok) throw new RuntimeException($label);
    echo "PASS $label\n";
}
$pdo = db();
$pdo->beginTransaction();
try {
    $pdo->exec("INSERT INTO catalogue_categories (name,position) VALUES ('Test principal',0)");
    $parentId = (int)$pdo->lastInsertId();
    $insert = $pdo->prepare('INSERT INTO catalogue_categories (name,parent_key,position) VALUES (?,?,?)');
    $insert->execute(['Test enfant', 'custom_' . $parentId, 2]);
    $childId = (int)$pdo->lastInsertId();
    $insert->execute(['Test sous pots', 'pot', 0]);
    $potChildId = (int)$pdo->lastInsertId();
    $all = catalogue_categories();
    $base = navigation_defaults();
    $menu = catalogue_menu($base, $all);
    verify_category(array_key_first($menu) === 'custom_' . $parentId, 'new category follows configured menu order');
    verify_category(isset($menu['custom_' . $parentId]['children']['custom_' . $childId]), 'custom subcategory appears under its parent');
    verify_category(isset($menu['pot']['children']['custom_' . $potChildId]), 'subcategory can be added under existing Pots category');
    verify_category(catalogue_parent_options($all, $base, $parentId) === [], 'parent with children cannot become a subcategory');
    verify_category(!isset(catalogue_parent_options($all, $base, $childId)['custom_' . $childId]), 'category cannot become its own parent');
    $base['pot']['visible'] = false;
    verify_category(!catalogue_menu($base, $all)['pot']['visible'], 'hidden parent remains hidden with custom children');
    $productId = (int)$pdo->query('SELECT id FROM products WHERE active=1 LIMIT 1')->fetchColumn();
    if ($productId) {
        $assign = $pdo->prepare('INSERT INTO product_categories (product_id,category_id) VALUES (?,?)');
        $assign->execute([$productId, $parentId]);
        $assign->execute([$productId, $childId]);
        $products = catalogue_category_products([$parentId, $childId]);
        verify_category(count($products) === 1 && (int)$products[0]['id'] === $productId, 'parent includes child products without duplicates');
        verify_category(count(catalogue_category_products([$childId])) === 1, 'subcategory displays assigned product');
        verify_category(catalogue_category_products([$potChildId]) === [], 'unassigned products excluded');
    }
} finally {
    $pdo->rollBack();
}
