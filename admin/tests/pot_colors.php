<?php
session_save_path(sys_get_temp_dir());
require __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/pot-colors.php';
function verify_color(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS $message\n";
}
verify_color(pot_color_values(['name' => ' Ivoire ', 'hex' => '#f0ead6', 'position' => 1]) === ['name' => 'Ivoire', 'hex' => '#F0EAD6', 'position' => 1], 'color data validated and normalized');
foreach ([['name' => '', 'hex' => '#ffffff', 'position' => 1], ['name' => 'Noir', 'hex' => 'red', 'position' => 1], ['name' => 'Noir', 'hex' => '#000000', 'position' => -1]] as $invalid) {
    $rejected = false;
    try { pot_color_values($invalid); } catch (InvalidArgumentException $e) { $rejected = true; }
    verify_color($rejected, 'invalid color rejected');
}
$pdo = db();
$oldCart = $_SESSION['cart'] ?? [];
$pdo->beginTransaction();
try {
    $slug = 'color-test-' . bin2hex(random_bytes(8));
    $pdo->prepare("INSERT INTO products (slug,kind,name,active) VALUES (?,'pot','Test pot',1)")->execute([$slug]);
    $pid = (int)$pdo->lastInsertId();
    $colors = [];
    $variants = [];
    foreach ([['Ivoire', '#F0EAD6', 'ivory.jpg', 59, 3], ['Noir', '#000000', 'black.jpg', 69, 0]] as $index => [$name, $hex, $file, $price, $stock]) {
        $pdo->prepare('INSERT INTO pot_colors (product_id,name,hex,position) VALUES (?,?,?,?)')->execute([$pid, $name, $hex, $index]);
        $cid = (int)$pdo->lastInsertId(); $colors[] = $cid;
        $pdo->prepare('INSERT INTO pot_color_photos (color_id,file,position) VALUES (?,?,0)')->execute([$cid, $file]);
        $pdo->prepare("INSERT INTO variants (product_id,label,price,stock,color_id) VALUES (?,'H 30 cm - D 25 cm',?,?,?)")->execute([$pid, $price, $stock, $cid]);
        $variants[] = (int)$pdo->lastInsertId();
    }
    $p = load_product_by('id', $pid);
    verify_color($p['variants'][0]['color_name'] === 'Ivoire' && $p['variants'][1]['color_name'] === 'Noir', 'size variants retain their color identity');
    verify_color(variant_label($p, $p['variants'][0]) === 'H 30 cm - D 25 cm · Ivoire', 'cart and order labels contain size and color');
    $palette = pot_colors($pid);
    verify_color($palette[$colors[0]]['photos'][0]['file'] === 'ivory.jpg' && $palette[$colors[1]]['photos'][0]['file'] === 'black.jpg', 'each color keeps its own photos');
    $_SESSION['cart'] = [$variants[0] => 1, $variants[1] => 2];
    $lines = cart_lines();
    verify_color(count($lines) === 2 && $lines[0]['variant']['id'] !== $lines[1]['variant']['id'], 'same size in different colors remains separate in cart');
    $byId = [];
    foreach ($lines as $line) $byId[(int)$line['variant']['id']] = $line;
    verify_color($byId[$variants[0]]['product']['images'][0]['file'] === 'ivory.jpg' && $byId[$variants[1]]['product']['images'][0]['file'] === 'black.jpg', 'cart photo follows selected color');
    verify_color(!in_stock($p['variants'][1]) && in_stock($p['variants'][0]), 'stock independent for each color');
    $pdo->prepare("INSERT INTO pot_colors (product_id,name,hex,position) VALUES (?,'Sans taille','#EEEEEE',3)")->execute([$pid]);
    $unassignedColor = (int)$pdo->lastInsertId();
    $sel = 0;
    ob_start(); require __DIR__ . '/../../includes/product-options.php'; $html = ob_get_clean();
    verify_color(str_contains($html, 'data-pot-palette=') && str_contains($html, 'data-color="' . $colors[1] . '"'), 'palette and size filter data rendered');
    verify_color(str_contains($html, 'data-size="H 30 cm - D 25 cm"'), 'size remains distinct from color label');
    verify_color(str_contains($html, 'data-pot-color="' . $unassignedColor . '"'), 'saved color remains visible before sizes are assigned');
    $newRows = pot_color_size_values([['id' => 0, 'label' => 'Grand', 'price' => '79,5', 'old_price' => '', 'stock' => '4']]);
    save_pot_color_sizes($pdo, $pid, $unassignedColor, $newRows);
    $updated = load_product_by('id', $pid);
    $created = array_values(array_filter($updated['variants'], fn($row) => (int)$row['color_id'] === $unassignedColor));
    verify_color(count($created) === 1 && (float)$created[0]['price'] === 79.5 && (int)$created[0]['stock'] === 4, 'size price and stock saved from color editor');
    $newId = (int)$created[0]['id'];
    $editRows = pot_color_size_values([['id' => $newId, 'label' => 'Grand', 'price' => '89', 'old_price' => '99', 'stock' => '2']]);
    save_pot_color_sizes($pdo, $pid, $unassignedColor, $editRows);
    $updated = load_product_by('id', $pid);
    $edited = array_values(array_filter($updated['variants'], fn($row) => (int)$row['id'] === $newId));
    verify_color((float)$edited[0]['price'] === 89.0 && (int)$edited[0]['stock'] === 2, 'editing size preserves variant ID');
    $foreignRejected = false;
    try { save_pot_color_sizes($pdo, $pid, $colors[0], $editRows); } catch (InvalidArgumentException $e) { $foreignRejected = true; }
    verify_color($foreignRejected, 'cannot change another color size');
    save_pot_color_sizes($pdo, $pid, $unassignedColor, pot_color_size_values([['id' => $newId, 'remove' => 1]]));
    $updated = load_product_by('id', $pid);
    verify_color(!in_array($newId, array_map('intval', array_column($updated['variants'], 'id')), true), 'explicit removal affects only selected size');
    verify_color(count(array_filter($updated['variants'], fn($row) => (int)$row['color_id'] === $colors[0])) === 1, 'other color sizes unchanged');
} finally {
    $pdo->rollBack();
    $_SESSION['cart'] = $oldCart;
}
