<?php
require __DIR__ . '/../../includes/admin.php';

function check_order(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
    echo "PASS $message\n";
}

check_order(collection_ordered_ids([10, 20], [10 => 2, 20 => 1]) === [20, 10], 'selection follows section ranks');
check_order(collection_ordered_ids([], []) === [], 'empty section remains empty');
foreach ([[10 => 1, 20 => 1], [10 => 0, 20 => 2], [10 => 1], [10 => [], 20 => 2]] as $invalid) {
    $rejected = false;
    try { collection_ordered_ids([10, 20], $invalid); }
    catch (InvalidArgumentException $e) { $rejected = true; }
    check_order($rejected, 'invalid or duplicate selected ranks rejected');
}

$pdo = db();
$pdo->beginTransaction();
try {
    $collections = [
        ['home_pot_collection_ids', "kind IN ('plante','pot')", 'pot-collection-title'],
        ['home_accessory_collection_ids', "kind = 'acc'", 'accessories-title'],
    ];
    $expected = [];
    $save = $pdo->prepare('INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v = VALUES(v)');
    foreach ($collections as [$key, $where, $title]) {
        $ids = array_map('intval', $pdo->query("SELECT id FROM products WHERE active = 1 AND $where ORDER BY position ASC, id DESC LIMIT 2")->fetchAll(PDO::FETCH_COLUMN));
        $expected[$title] = array_reverse($ids);
        $save->execute([$key, json_encode($expected[$title])]);
    }
    $homePage = true;
    ob_start();
    require __DIR__ . '/../../includes/home-pots.php';
    $html = ob_get_clean();
    foreach ($expected as $title => $ids) {
        $start = strpos($html, 'id="' . $title . '"');
        $end = strpos($html, '</section>', $start);
        preg_match_all('/data-product-id="(\d+)"/', substr($html, $start, $end - $start), $matches);
        check_order(array_map('intval', $matches[1]) === $ids, "$title renders its own saved order");
    }
} finally {
    $pdo->rollBack();
}
