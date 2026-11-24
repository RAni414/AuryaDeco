<?php
function product_information_values(array $input): array
{
    $usage = is_string($input['usage_place'] ?? null) ? $input['usage_place'] : '';
    if (!in_array($usage, ['', 'interieur', 'exterieur', 'mixte'], true)) throw new InvalidArgumentException('Choisissez une utilisation valide.');
    $values = ['usage_place' => $usage ?: null];
    foreach (['material_text' => 120, 'care_text' => 200, 'included_text' => 200] as $key => $limit) {
        if (!is_string($input[$key] ?? '')) throw new InvalidArgumentException('Vérifiez les caractéristiques du produit.');
        $text = trim($input[$key] ?? '');
        if (mb_strlen($text) > $limit) throw new InvalidArgumentException('Une caractéristique dépasse la longueur autorisée (' . $limit . ' caractères).');
        $values[$key] = $text ?: null;
    }
    return $values;
}

function product_information(array $product): array
{
    $facts = [];
    if ($product['kind'] === 'plante') $facts['Hauteurs disponibles'] = sizes_text($product);
    elseif ($product['kind'] === 'pot') {
        $facts['Tailles disponibles'] = implode(' / ', array_unique(array_map('pot_size_label', $product['variants'])));
        $colors = array_unique(array_filter(array_column($product['variants'], 'color_name')));
        if ($colors) $facts['Couleurs'] = implode(', ', $colors);
    } else $facts[($product['type'] ?? '') === 'pebbles' ? 'Poids disponibles' : 'Formats disponibles'] = sizes_text($product);
    $material = trim((string)($product['material_text'] ?? ''));
    if ($material === '' && $product['kind'] === 'pot') $material = MATS[$product['mat'] ?? ''] ?? '';
    if ($material !== '') $facts['Matière'] = $material;
    $uses = ['interieur' => 'Intérieur', 'exterieur' => 'Extérieur', 'mixte' => 'Intérieur et extérieur'];
    if (isset($uses[$product['usage_place'] ?? ''])) $facts['Utilisation'] = $uses[$product['usage_place']];
    $included = trim((string)($product['included_text'] ?? ''));
    if ($included === '' && $product['kind'] === 'plante') $included = 'Plante avec pot de culture noir, sans cache-pot décoratif.';
    if ($included === '' && $product['kind'] === 'pot') $included = 'Cache-pot vendu seul, sans plante.';
    if ($included !== '') $facts['Contenu livré'] = $included;
    if (!empty($product['care_text'])) $facts['Entretien'] = $product['care_text'];
    if ($product['kind'] === 'plante' && !empty($product['open_min']) && !empty($product['open_max'])) $facts['Ouverture de cache-pot conseillée'] = 'De ' . (int)$product['open_min'] . ' à ' . (int)$product['open_max'] . ' cm';
    return $facts;
}
