<?php
/** Editable presentation of existing catalogue navigation. Targets stay valid. */
function navigation_defaults(): array
{
    $plants = [];
    foreach (BANDS as $key => $label) $plants[$key] = ['label' => $label, 'hint' => BAND_HINT[$key], 'path' => 'boutique.php?cat=plante&taille=' . $key];
    $pots = [];
    foreach (MATS as $key => $label) $pots[$key] = ['label' => $label, 'hint' => '', 'path' => 'boutique.php?cat=pot&mat=' . $key];
    $items = [
        'plante' => ['label' => 'Plantes artificielles', 'path' => 'boutique.php?cat=plante', 'children' => $plants],
        'pot' => ['label' => 'Pots', 'path' => 'boutique.php?cat=pot', 'children' => $pots],
        'acc' => ['label' => 'Accessoires', 'path' => 'boutique.php?cat=acc'],
        'shop' => ['label' => 'Toute la boutique', 'path' => 'boutique.php'],
        'pro' => ['label' => 'Professionnels', 'path' => 'page.php?p=pro'],
        'promo' => ['label' => 'Promotions', 'path' => 'boutique.php?promo=1'],
    ];
    foreach ($items as $key => &$item) {
        $item += ['children' => [], 'visible' => true, 'order' => array_search($key, array_keys($items), true) + 1];
        $rank = 1;
        foreach ($item['children'] as &$child) $child += ['visible' => true, 'order' => $rank++];
        unset($child);
    }
    unset($item);
    return $items;
}

function navigation_merge(array $saved): array
{
    $items = navigation_defaults();
    foreach ($items as $key => &$item) {
        $edit = is_array($saved[$key] ?? null) ? $saved[$key] : [];
        foreach (['label', 'visible', 'order'] as $field) if (isset($edit[$field])) $item[$field] = $edit[$field];
        foreach ($item['children'] as $id => &$child) {
            $change = is_array($edit['children'][$id] ?? null) ? $edit['children'][$id] : [];
            foreach (['label', 'hint', 'visible', 'order'] as $field) if (isset($change[$field])) $child[$field] = $change[$field];
        }
        unset($child);
        uasort($item['children'], fn($a, $b) => (int)$a['order'] <=> (int)$b['order']);
    }
    unset($item);
    uasort($items, fn($a, $b) => (int)$a['order'] <=> (int)$b['order']);
    return $items;
}

function navigation_validate(array $input): array
{
    $output = [];
    $read = static function ($row, bool $child): array {
        if (!is_array($row) || !is_string($row['label'] ?? null)) throw new InvalidArgumentException('Le nom de chaque rubrique est obligatoire.');
        $label = trim($row['label']);
        $order = is_scalar($row['order'] ?? null) ? filter_var($row['order'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999]]) : false;
        if ($label === '' || mb_strlen($label) > 80 || $order === false) throw new InvalidArgumentException('Saisissez un nom de 1 à 80 caractères et un ordre de 0 à 999.');
        $result = ['label' => $label, 'order' => $order, 'visible' => !empty($row['visible'])];
        if ($child) {
            if (!is_string($row['hint'] ?? null) || mb_strlen($row['hint']) > 120) throw new InvalidArgumentException('La précision doit contenir au maximum 120 caractères.');
            $result['hint'] = trim($row['hint']);
        }
        return $result;
    };
    foreach (navigation_defaults() as $key => $item) {
        $output[$key] = $read($input[$key] ?? null, false);
        foreach ($item['children'] as $id => $child) $output[$key]['children'][$id] = $read($input[$key]['children'][$id] ?? null, true);
    }
    return $output;
}
