<?php
function plant_seo_audit(array $product): array
{
    $issues = [];
    $description = trim(strip_tags((string)($product['description'] ?? '')));
    if ($description === '') $issues[] = 'Ajouter une description propre à cette plante.';
    elseif (mb_strlen($description) < 120) $issues[] = 'Compléter la description : aspect, emplacement et éléments inclus.';
    $images = $product['images'] ?? [];
    if (!$images) $issues[] = 'Ajouter une photo du produit.';
    elseif (count($images) < 2) $issues[] = 'Ajouter une photo complémentaire : détail ou mise en situation.';
    $variants = $product['variants'] ?? [];
    if (!$variants) $issues[] = 'Renseigner au moins une taille avec son prix.';
    elseif (array_filter($variants, fn($v) => empty($v['height']) || (float)($v['price'] ?? 0) <= 0)) $issues[] = 'Vérifier la hauteur et le prix de chaque taille.';
    return $issues;
}
