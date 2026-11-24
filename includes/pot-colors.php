<?php
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
