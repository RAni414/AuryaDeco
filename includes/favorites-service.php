<?php
declare(strict_types=1);

function favorite_ids($input): array
{
    if (!is_array($input) || count($input) > 200) throw new InvalidArgumentException('La liste doit contenir au maximum 200 produits.');
    foreach ($input as $id) {
        if (!is_int($id) || $id < 1 || $id > 4294967295) throw new InvalidArgumentException('La liste contient un identifiant produit invalide.');
    }
    return array_values(array_unique($input));
}

function favorite_products(array $ids): array
{
    if (!$ids) return [];
    $products = load_products('p.active=1 AND p.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids);
    $byId = [];
    foreach ($products as $product) $byId[(int)$product['id']] = $product;
    $ordered = [];
    foreach ($ids as $id) if (isset($byId[$id])) $ordered[] = $byId[$id];
    return $ordered;
}

function favorite_product_card(array $p): string
{
    $available = array_values(array_filter($p['variants'], 'in_stock'));
    $html = '<p class="fav-availability">' . ($available ? 'Commandable' : 'Actuellement indisponible') . '</p>';
    if ($available) {
        $html .= '<form class="fav-buy" method="post" action="' . e(url('panier.php')) . '">' . csrf_field()
            . '<input type="hidden" name="action" value="add">';
        if (count($p['variants']) > 1) {
            $html .= '<label>Choisir une variante<select name="variant_id" required><option value="">Choisir une taille ou un format</option>';
            foreach ($p['variants'] as $variant) {
                $html .= '<option value="' . (int)$variant['id'] . '"' . (in_stock($variant) ? '' : ' disabled') . '>'
                    . e(variant_label($p, $variant) . ' · ' . money($variant['price']) . (in_stock($variant) ? '' : ' · indisponible')) . '</option>';
            }
            $html .= '</select></label>';
        } else {
            $html .= '<input type="hidden" name="variant_id" value="' . (int)$available[0]['id'] . '">';
        }
        $html .= '<label>Quantité<input type="number" name="qty" value="1" min="1" max="99" required></label>'
            . '<button type="submit" class="btn">Ajouter au panier</button></form>';
    } else {
        $html .= '<p class="fav-unavailable">Ce produit reste dans vos favoris. Consultez sa fiche pour les détails.</p>';
    }
    return product_card($p, $html);
}
