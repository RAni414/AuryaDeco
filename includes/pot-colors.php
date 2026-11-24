<?php
require_once __DIR__ . '/pot-dimensions.php';
function pot_colors(int $productId): array
{
    $query = db()->prepare('SELECT * FROM pot_colors WHERE product_id=? ORDER BY position,id');
    $query->execute([$productId]);
    $colors = [];
    foreach ($query as $color) { $color['photos'] = []; $colors[(int)$color['id']] = $color; }
    if ($colors) {
        foreach (db()->query('SELECT * FROM pot_color_photos WHERE color_id IN (' . implode(',', array_keys($colors)) . ') ORDER BY position,id') as $photo) $colors[(int)$photo['color_id']]['photos'][] = $photo;
    }
    return $colors;
}

function pot_color_values(array $input): array
{
    $name = is_string($input['name'] ?? null) ? trim($input['name']) : '';
    $hex = is_string($input['hex'] ?? null) ? $input['hex'] : '';
    $position = is_scalar($input['position'] ?? null) ? filter_var($input['position'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999]]) : false;
    if ($name === '' || mb_strlen($name) > 60 || !preg_match('/^#[0-9a-f]{6}$/iD', $hex) || $position === false) throw new InvalidArgumentException('Renseignez un nom de couleur, une teinte valide et un ordre entre 0 et 999.');
    return ['name' => $name, 'hex' => strtoupper($hex), 'position' => $position];
}

function pot_color_size_values(array $input): array
{
    if (count($input) > 40) throw new InvalidArgumentException('Ajoutez au maximum 40 tailles par couleur.');
    $rows = []; $ids = [];
    foreach ($input as $row) {
        if (!is_array($row)) throw new InvalidArgumentException('Une ligne de taille est invalide.');
        foreach (['id', 'label', 'price', 'old_price', 'stock'] as $key) if (!is_scalar($row[$key] ?? '')) throw new InvalidArgumentException('Vérifiez les tailles saisies.');
        $id = filter_var($row['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $label = trim((string)($row['label'] ?? ''));
        $price = str_replace(',', '.', trim((string)($row['price'] ?? '')));
        $old = str_replace(',', '.', trim((string)($row['old_price'] ?? '')));
        $stock = trim((string)($row['stock'] ?? ''));
        if ($id === false || ($id && isset($ids[$id]))) throw new InvalidArgumentException('Une taille est invalide ou présente deux fois.');
        if ($id) $ids[$id] = true;
        if (!empty($row['remove'])) { if ($id) $rows[] = ['id' => $id, 'remove' => true]; continue; }
        $label = pot_size_input_label($row);
        if (!$id && $label === '' && $price === '' && $old === '' && $stock === '') continue;
        if ($label === '' || mb_strlen($label) > 80 || !is_numeric($price) || (float)$price <= 0 || (float)$price > 9999999.999 || ($old !== '' && (!is_numeric($old) || (float)$old < 0 || (float)$old > 9999999.999))) throw new InvalidArgumentException('Chaque taille doit avoir un nom (80 caractères maximum) et un prix valide.');
        $stockValue = $stock === '' ? null : filter_var($stock, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]);
        if ($stockValue === false) throw new InvalidArgumentException('Le stock doit être vide, égal à 0 ou un nombre entier positif.');
        $rows[] = ['id' => $id, 'label' => $label, 'price' => $price, 'old_price' => $old === '' ? null : $old, 'stock' => $stockValue, 'remove' => false];
    }
    return $rows;
}

function save_pot_color_sizes(PDO $pdo, int $productId, int $colorId, array $rows): void
{
    $owner = $pdo->prepare('SELECT id FROM pot_colors WHERE id=? AND product_id=?');
    $owner->execute([$colorId, $productId]);
    if (!$owner->fetchColumn()) throw new InvalidArgumentException('La couleur ne correspond pas à ce pot.');
    $query = $pdo->prepare('SELECT id FROM variants WHERE product_id=? AND color_id=? FOR UPDATE');
    $query->execute([$productId, $colorId]);
    $owned = array_map('intval', $query->fetchAll(PDO::FETCH_COLUMN));
    foreach ($rows as $row) if ($row['id'] && !in_array($row['id'], $owned, true)) throw new InvalidArgumentException('Une taille ne correspond pas à cette couleur. Rechargez la page.');
    $update = $pdo->prepare('UPDATE variants SET label=?,price=?,old_price=?,stock=?,position=? WHERE id=? AND product_id=? AND color_id=?');
    $insert = $pdo->prepare('INSERT INTO variants (product_id,color_id,label,price,old_price,stock,position) VALUES (?,?,?,?,?,?,?)');
    $delete = $pdo->prepare('DELETE FROM variants WHERE id=? AND product_id=? AND color_id=?');
    foreach ($rows as $position => $row) {
        if ($row['remove']) { $delete->execute([$row['id'], $productId, $colorId]); continue; }
        if ($row['id']) $update->execute([$row['label'], $row['price'], $row['old_price'], $row['stock'], $position, $row['id'], $productId, $colorId]);
        else $insert->execute([$productId, $colorId, $row['label'], $row['price'], $row['old_price'], $row['stock'], $position]);
    }
}
