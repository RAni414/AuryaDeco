<?php
session_save_path(sys_get_temp_dir());
require __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/shop-filters.php';
require_once __DIR__ . '/../../includes/product-information.php';
require_once __DIR__ . '/../../includes/review-date.php';
function verify_storefront(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS $message\n";
}
$values = shop_filter_values(['cat' => 'pot', 'min' => '30,5', 'color' => 'Noir', 'available' => 1, 'ignored' => 'value']);
verify_storefront($values['min'] === '30.5' && $values['available'] === '1' && !isset($values['ignored']), 'filter input normalized and unknown fields ignored');
verify_storefront(shop_filter_values(['cat' => 'plante', 'color' => 'Noir', 'pot_size' => 'Grand'])['color'] === '', 'pot filters removed from plant search');
foreach ([['min' => '-1'], ['max' => 'abc'], ['min' => 50, 'max' => 20]] as $bad) {
    $rejected = false; try { shop_filter_values($bad); } catch (InvalidArgumentException $e) { $rejected = true; }
    verify_storefront($rejected, 'invalid price filter rejected');
}
$plant = ['kind' => 'plante', 'type' => 'bamboo', 'variants' => [['height' => 150]], 'open_min' => 25, 'open_max' => 40];
$facts = product_information($plant);
verify_storefront(!isset($facts['Utilisation']) && !isset($facts['Matière']), 'unknown product specifications are not invented');
$facts = product_information(array_merge($plant, ['usage_place' => 'interieur', 'material_text' => 'Feuillage textile', 'care_text' => 'Chiffon doux']));
verify_storefront($facts['Utilisation'] === 'Intérieur' && $facts['Matière'] === 'Feuillage textile', 'configured specifications shown');
verify_storefront(str_contains(review_date_html(['created_at' => '2026-11-24 10:00:00']), '24 novembre 2026'), 'customer review date displayed in French');
verify_storefront(review_date_html(['created_at' => '2026-02-31 10:00:00']) === '', 'invalid review date omitted');
$pdo = db(); $pdo->beginTransaction();
try {
    $slug = 'filter-test-' . bin2hex(random_bytes(8));
    $pdo->prepare("INSERT INTO products (slug,kind,name,active,usage_place) VALUES (?,'pot','Test faceted pot',1,'interieur')")->execute([$slug]);
    $pid = (int)$pdo->lastInsertId();
    $colors = [];
    foreach (['Noir', 'Rose'] as $name) {
        $pdo->prepare('INSERT INTO pot_colors (product_id,name,hex) VALUES (?,?,?)')->execute([$pid, $name, '#000000']);
        $colors[$name] = (int)$pdo->lastInsertId();
    }
    $insert = $pdo->prepare('INSERT INTO variants (product_id,color_id,label,price,stock) VALUES (?,?,?,?,?)');
    $insert->execute([$pid, $colors['Noir'], 'Grand', 100, 0]);
    $insert->execute([$pid, $colors['Rose'], 'Petit', 20, 5]);
    $search = static function (array $query) use ($pid): bool {
        [$where, $params, $order] = shop_filter_query(shop_filter_values($query));
        $list = load_products($where . ' AND p.id=' . $pid, $params, $order);
        return count($list) === 1;
    };
    verify_storefront(!$search(['cat' => 'pot', 'color' => 'Noir', 'max' => 30]), 'price and color must match the same variant');
    verify_storefront(!$search(['cat' => 'pot', 'color' => 'Noir', 'available' => 1]), 'stock must belong to selected color');
    verify_storefront($search(['cat' => 'pot', 'color' => 'Rose', 'pot_size' => 'Petit', 'max' => 30, 'available' => 1]), 'matching size color price and stock found');
    verify_storefront(!$search(['cat' => 'pot', 'usage' => 'exterieur']), 'interior-only item excluded from outdoor filter');
    verify_storefront($search(['q' => 'Test faceted']), 'product search remains functional');
} finally { $pdo->rollBack(); }
