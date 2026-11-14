<?php
function catalogue_categories(): array
{
    return db()->query('SELECT * FROM catalogue_categories ORDER BY position, id')->fetchAll();
}

function catalogue_category_products(array $ids): array
{
    $ids = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));
    if (!$ids) return [];
    return load_products('p.active = 1 AND EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id=p.id AND pc.category_id IN (' . implode(',', $ids) . '))', [], 'p.position ASC, p.id DESC');
}

function catalogue_menu_item(array $category): array
{
    return ['label' => $category['name'], 'hint' => '', 'path' => 'includes/categorie.php?id=' . (int)$category['id'],
        'order' => (int)$category['position'], 'visible' => (bool)$category['active'], 'children' => []];
}

function catalogue_menu(array $menu, array $categories): array
{
    foreach ($categories as $category) {
        if ($category['parent_key'] === null) $menu['custom_' . $category['id']] = catalogue_menu_item($category);
    }
    foreach ($categories as $category) {
        $parent = $category['parent_key'];
        if ($parent !== null && isset($menu[$parent])) $menu[$parent]['children']['custom_' . $category['id']] = catalogue_menu_item($category);
    }
    foreach ($menu as &$item) uasort($item['children'], fn($a, $b) => $a['order'] <=> $b['order']);
    unset($item);
    uasort($menu, fn($a, $b) => $a['order'] <=> $b['order']);
    return $menu;
}

function catalogue_parent_options(array $categories, array $base, int $id = 0): array
{
    $options = [];
    foreach (['plante', 'pot', 'acc'] as $key) $options[$key] = $base[$key]['label'];
    $hasChildren = false;
    foreach ($categories as $category) if ($category['parent_key'] === 'custom_' . $id) $hasChildren = true;
    if ($hasChildren) return []; // a parent cannot be moved beneath another category
    foreach ($categories as $category) {
        if ($category['parent_key'] === null && (int)$category['id'] !== $id) $options['custom_' . $category['id']] = $category['name'];
    }
    return $options;
}
