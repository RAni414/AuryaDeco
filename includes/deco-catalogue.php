<?php
declare(strict_types=1);

function deco_url(string $path): string
{
    global $CONFIG;
    $base = base_url();
    if (empty($CONFIG['base_url'])) $base = preg_replace('#/includes$#', '', $base);
    return $base . '/' . ltrim($path, '/');
}

function deco_number(string $key): ?float
{
    $text = deco_text($key, 20);
    if ($text === '') return null;
    $text = str_replace(',', '.', $text);
    if (!is_numeric($text) || !is_finite((float)$text) || (float)$text <= 0 || (float)$text > 1000000) throw new DecoError('Budget ou dimension invalide. Utilisez un nombre positif.');
    return (float)$text;
}

function deco_product_image(?string $file): ?string
{
    if (!$file) return null;
    $root = realpath(UPLOAD_DIR);
    $path = realpath(UPLOAD_DIR . '/' . $file);
    if (!$root || !$path || strpos(str_replace('\\', '/', $path), str_replace('\\', '/', $root) . '/') !== 0 || !is_file($path)) return null;
    return $path;
}

function deco_candidate_rows(?float $budget, ?float $height, string $style, string $kind, ?array $selected): array
{
    // The existing shop sells artificial plants. Never sell them as natural.
    if ($kind === 'naturelle') return [];
    $sql = "SELECT p.id,p.slug,p.kind,p.name,p.description,p.type,p.open_min,p.open_max,p.color,p.shape,p.tomb,
        v.id AS variant_id,v.height,v.diameter,v.label,v.price,v.stock,
        (SELECT file FROM product_images i WHERE i.product_id=p.id ORDER BY i.position,i.id LIMIT 1) AS image_file
        FROM products p JOIN variants v ON v.product_id=p.id
        WHERE p.active=1 AND p.kind='plante' AND (v.stock IS NULL OR v.stock>0)";
    $params = [];
    if ($budget !== null) { $sql .= ' AND v.price <= ?'; $params[] = $budget; }
    if ($height !== null) { $sql .= ' AND v.height IS NOT NULL AND v.height <= ?'; $params[] = $height; }
    if ($selected) { $sql .= ' AND v.id=?'; $params[] = $selected['variant_id']; }
    $sql .= ' ORDER BY p.featured DESC,p.position,v.price,v.id';
    $q = db()->prepare($sql); $q->execute($params); $rows = $q->fetchAll();
    // Style is a soft preference from actual descriptive text, never a made-up tag.
    if ($style !== '' && !$selected) {
        $word = mb_strtolower($style);
        usort($rows, function ($a, $b) use ($word) {
            $sa = mb_strpos(mb_strtolower($a['description'] ?? ''), $word) !== false;
            $sb = mb_strpos(mb_strtolower($b['description'] ?? ''), $word) !== false;
            return ($sb <=> $sa) ?: ((float)$a['price'] <=> (float)$b['price']);
        });
    }
    // Keep the model context bounded, but choose the shortlist from the full shop.
    $unique = [];
    foreach ($rows as $row) { if (!isset($unique[$row['id']])) $unique[$row['id']] = $row; if (count($unique) >= 15) break; }
    return array_values($unique);
}

function deco_card(array $row): array
{
    $image = deco_product_image($row['image_file'] ?? null);
    return ['id' => (int)$row['id'], 'variant_id' => (int)$row['variant_id'], 'kind' => $row['kind'], 'name' => $row['name'],
        'height' => $row['height'] === null ? null : (int)$row['height'], 'diameter' => $row['diameter'] === null ? null : (int)$row['diameter'],
        'price' => (float)$row['price'], 'price_label' => money($row['price']),
        'stock' => $row['stock'] === null ? null : (int)$row['stock'],
        'availability' => $row['stock'] === null ? 'Commandable · quantité non suivie' : ((int)$row['stock'] > 0 ? 'En stock' : 'Rupture de stock'),
        'image' => $image ? deco_url('uploads/' . implode('/', array_map('rawurlencode', explode('/', str_replace('\\', '/', $row['image_file']))))) : null,
        'href' => deco_url('produit.php?p=' . rawurlencode($row['slug'])),
        'art' => ['id' => $row['slug'], 'name' => $row['name'], 'kind' => $row['kind'], 'type' => $row['type'] ?? '',
            'color' => $row['color'] ?? '', 'shape' => $row['shape'] ?? '', 'tomb' => !empty($row['tomb'])]];
}

function deco_cards(array $rows, array $ids, ?float $budget): array
{
    $cards = []; $seen = [];
    foreach ($ids as $id) foreach ($rows as $row) {
        if ((int)$row['variant_id'] !== (int)$id || isset($seen[$row['id']])) continue;
        $seen[$row['id']] = true;
        $card = deco_card($row); $card['pot'] = null;
        if (!empty($row['open_min']) && !empty($row['open_max'])) {
            $q = db()->prepare("SELECT p.id,p.slug,p.name,p.kind,p.type,p.shape,p.color,p.tomb,v.id AS variant_id,v.height,v.diameter,v.price,v.stock,
                (SELECT file FROM product_images i WHERE i.product_id=p.id ORDER BY i.position,i.id LIMIT 1) AS image_file
                FROM products p JOIN variants v ON v.product_id=p.id WHERE p.active=1 AND p.kind='pot'
                AND (v.stock IS NULL OR v.stock>0) AND v.diameter BETWEEN ? AND ? AND v.price<=? ORDER BY v.price,v.id LIMIT 1");
            $q->execute([$row['open_min'], $row['open_max'], $budget === null ? 100000000 : max(0, $budget - (float)$row['price'])]);
            $pot = $q->fetch();
            if ($pot) $card['pot'] = deco_card($pot);
        }
        $cards[] = $card;
        if (count($cards) >= 3) return $cards;
    }
    return $cards;
}

function deco_cart_add(): array
{
    $variant = deco_text('variant', 20); $qty = deco_text('qty', 3);
    if (!ctype_digit($variant) || !ctype_digit($qty) || (int)$qty < 1 || (int)$qty > 99) throw new DecoError('Choisissez une variante et une quantité de 1 à 99.');
    // The price is always read from the database, never from the browser or AI.
    $q = db()->prepare('SELECT p.name,p.active,v.id,v.price,v.stock FROM variants v JOIN products p ON p.id=v.product_id WHERE v.id=?');
    $q->execute([$variant]); $row = $q->fetch();
    if (!$row || !$row['active']) throw new DecoError('Ce produit n’est plus disponible.', 409);
    $cart = cart(); $total = ($cart[(int)$variant] ?? 0) + (int)$qty;
    if ($total > 99 || ($row['stock'] !== null && $total > (int)$row['stock'])) throw new DecoError('La quantité demandée dépasse le stock disponible ou la limite du panier.', 409);
    $cart[(int)$variant] = $total; $_SESSION['cart'] = $cart;
    return ['ok' => true, 'count' => cart_count(), 'unit_price' => money($row['price']),
        'message' => '« ' . $row['name'] . ' » ajouté au panier. Prix unitaire : ' . money($row['price']), 'cart_url' => deco_url('panier.php')];
}

function deco_variant_row(string $variant): ?array
{
    if (!ctype_digit($variant)) return null;
    $q = db()->prepare("SELECT p.id,p.slug,p.kind,p.name,p.description,p.type,p.open_min,p.open_max,p.color,p.shape,p.tomb,
        v.id AS variant_id,v.height,v.diameter,v.label,v.price,v.stock,
        (SELECT file FROM product_images i WHERE i.product_id=p.id ORDER BY i.position,i.id LIMIT 1) AS image_file
        FROM products p JOIN variants v ON v.product_id=p.id WHERE p.active=1 AND v.id=?");
    $q->execute([$variant]); return $q->fetch() ?: null;
}

function deco_current_cards(): array
{
    $rows = [];
    foreach ($_SESSION['deco_last_ids'] ?? [] as $id) { $row = deco_variant_row((string)$id); if ($row) $rows[] = $row; }
    return deco_cards($rows, array_column($rows, 'variant_id'), $_SESSION['deco_needs']['budget_tnd'] ?? null);
}
