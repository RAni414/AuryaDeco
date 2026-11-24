<?php
function shop_filter_values(array $input): array
{
    $keys = ['cat', 'taille', 'mat', 'q', 'tri', 'min', 'max', 'color', 'pot_size', 'usage', 'available', 'promo'];
    $values = [];
    foreach ($keys as $key) $values[$key] = is_scalar($input[$key] ?? '') ? trim((string)($input[$key] ?? '')) : '';
    $values['cat'] = isset(KINDS[$values['cat']]) ? $values['cat'] : '';
    $values['taille'] = $values['cat'] === 'plante' && isset(BANDS[$values['taille']]) ? $values['taille'] : '';
    $values['mat'] = $values['cat'] === 'pot' && isset(MATS[$values['mat']]) ? $values['mat'] : '';
    if ($values['cat'] !== 'pot') $values['color'] = $values['pot_size'] = '';
    $values['usage'] = in_array($values['usage'], ['interieur', 'exterieur', 'mixte'], true) ? $values['usage'] : '';
    $values['tri'] = in_array($values['tri'], ['asc', 'desc', 'nom'], true) ? $values['tri'] : '';
    $values['q'] = mb_substr($values['q'], 0, 120);
    $values['color'] = mb_substr($values['color'], 0, 60);
    $values['pot_size'] = mb_substr($values['pot_size'], 0, 80);
    foreach (['promo', 'available'] as $key) $values[$key] = $values[$key] !== '' && $values[$key] !== '0' ? '1' : '';
    foreach (['min', 'max'] as $key) {
        $values[$key] = str_replace(',', '.', $values[$key]);
        if ($values[$key] !== '' && (!is_numeric($values[$key]) || !is_finite((float)$values[$key]) || (float)$values[$key] < 0 || (float)$values[$key] > 9999999.999)) throw new InvalidArgumentException('Indiquez un prix minimum et maximum valides.');
    }
    if ($values['min'] !== '' && $values['max'] !== '' && (float)$values['min'] > (float)$values['max']) throw new InvalidArgumentException('Le prix minimum doit être inférieur au prix maximum.');
    return $values;
}

function shop_filter_query(array $f): array
{
    $where = ['p.active=1']; $params = []; $variant = ['v.product_id=p.id']; $vparams = [];
    if ($f['cat']) { $where[] = 'p.kind=?'; $params[] = $f['cat']; }
    if ($f['mat']) { $where[] = 'p.mat=?'; $params[] = $f['mat']; }
    if ($f['taille'] === 'tombantes') $where[] = 'p.tomb=1';
    elseif ($f['taille']) {
        $ranges = ['petites' => [0,70], 'moyennes' => [71,159], 'grandes' => [160,220], 'geantes' => [221,65535]];
        $where[] = 'p.tomb=0'; $variant[] = 'v.height BETWEEN ? AND ?';
        array_push($vparams, ...$ranges[$f['taille']]);
    }
    if ($f['q'] !== '') {
        $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
        $like = '%' . str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $f['q']) . '%';
        array_push($params, $like, $like);
    }
    if ($f['usage']) {
        $where[] = $f['usage'] === 'mixte' ? "p.usage_place='mixte'" : "p.usage_place IN (?, 'mixte')";
        if ($f['usage'] !== 'mixte') $params[] = $f['usage'];
    }
    foreach (['min' => '>=', 'max' => '<='] as $key => $operator) if ($f[$key] !== '') { $variant[] = 'v.price ' . $operator . ' ?'; $vparams[] = $f[$key]; }
    if ($f['color']) { $variant[] = 'c.name=?'; $vparams[] = $f['color']; }
    if ($f['pot_size']) { $variant[] = "COALESCE(NULLIF(TRIM(v.label),''),CONCAT('D ',v.diameter,' cm'))=?"; $vparams[] = $f['pot_size']; }
    if ($f['available']) $variant[] = '(v.stock IS NULL OR v.stock>0)';
    if ($f['promo']) $variant[] = 'v.old_price>v.price';
    // A single variant must satisfy every size/color/price/stock condition.
    $where[] = 'EXISTS (SELECT 1 FROM variants v LEFT JOIN pot_colors c ON c.id=v.color_id AND c.product_id=v.product_id WHERE ' . implode(' AND ', $variant) . ')';
    array_push($params, ...$vparams);
    $minPrice = '(SELECT MIN(vp.price) FROM variants vp WHERE vp.product_id=p.id)';
    $order = match ($f['tri']) { 'asc' => $minPrice . ' ASC,p.id DESC', 'desc' => $minPrice . ' DESC,p.id DESC', 'nom' => 'p.name ASC,p.id DESC', default => 'p.position ASC,p.id DESC' };
    return [implode(' AND ', $where), $params, $order];
}

function shop_filter_link(array $f, array $changes): string
{
    $query = array_filter(array_merge($f, $changes), fn($v) => $v !== '');
    return url('boutique.php' . ($query ? '?' . http_build_query($query) : ''));
}

function shop_filter_display(string $key, string $value): string
{
    return match ($key) {
        'taille' => BANDS[$value] ?? $value,
        'mat' => MATS[$value] ?? $value,
        'usage' => ['interieur' => 'Intérieur', 'exterieur' => 'Extérieur', 'mixte' => 'Intérieur et extérieur'][$value] ?? $value,
        'min', 'max' => money((float)$value),
        default => $value,
    };
}
