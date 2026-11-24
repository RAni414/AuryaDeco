<?php
function seo_base(): string
{
    return rtrim(settings()['seo_site_url'] ?? 'https://auryadeco.tn', '/');
}

function seo_url(string $path = ''): string
{
    return seo_base() . '/' . ltrim($path, '/');
}

function seo_text(string $text, int $length = 180): string
{
    $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    return mb_strlen($text) > $length ? rtrim(mb_substr($text, 0, $length - 1)) . '…' : $text;
}

function seo_product_schema(array $product): array
{
    $canonical = seo_url('produit.php?p=' . rawurlencode($product['slug']));
    $schema = ['@type' => 'Product', '@id' => $canonical . '#product', 'url' => $canonical,
        'name' => $product['name'], 'description' => seo_text((string)$product['description'], 1000), 'sku' => (string)$product['id']];
    $images = array_map(fn($image) => seo_url('uploads/' . implode('/', array_map('rawurlencode', explode('/', $image['file'])))), $product['images']);
    if ($images) $schema['image'] = $images;
    $variants = array_values(array_filter($product['variants'], fn($v) => (float)$v['price'] > 0));
    if (count($variants) === 1) {
        $variant = $variants[0];
        $schema['offers'] = ['@type' => 'Offer', 'url' => $canonical, 'priceCurrency' => 'TND',
            'price' => number_format((float)$variant['price'], 3, '.', ''),
            'availability' => in_stock($variant) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'seller' => ['@type' => 'Organization', 'name' => setting('shop_name')]];
    } elseif ($variants) {
        $prices = array_column($variants, 'price');
        $schema['offers'] = ['@type' => 'AggregateOffer', 'url' => $canonical, 'priceCurrency' => 'TND',
            'lowPrice' => number_format((float)min($prices), 3, '.', ''),
            'highPrice' => number_format((float)max($prices), 3, '.', ''), 'offerCount' => count($variants)];
    }
    return $schema;
}

function seo_metadata(string $script, array $query, ?array $product, ?array $category, string $pageTitle, string $description): array
{
    $name = setting('shop_name');
    $meta = ['title' => $pageTitle !== '' ? $pageTitle . ' | ' . $name : $name,
        'description' => seo_text($description), 'canonical' => seo_url(), 'index' => true, 'schema' => [],
        'image' => seo_url('assets/images/aurya-logo.png'), 'type' => 'website'];
    if ($script === 'index.php') {
        $meta['title'] = settings()['seo_home_title'] ?? 'Plantes artificielles et pots déco en Tunisie | ' . $name;
        $meta['description'] = settings()['seo_home_description'] ?? 'Découvrez les plantes artificielles, cache-pots et accessoires déco AuryaDeco. Prix en TND, livraison en Tunisie et paiement à la livraison.';
        $meta['schema'] = [
            ['@type' => 'Organization', '@id' => seo_url('#organization'), 'name' => $name, 'url' => seo_url(), 'logo' => seo_url('assets/images/aurya-logo.png')],
            ['@type' => 'WebSite', '@id' => seo_url('#website'), 'name' => $name, 'url' => seo_url(), 'inLanguage' => 'fr-TN'],
        ];
    } elseif ($script === 'produit.php' && $product) {
        $meta['title'] = $product['name'] . ' en Tunisie | ' . $name;
        $meta['canonical'] = seo_url('produit.php?p=' . rawurlencode($product['slug']));
        if ($meta['description'] === '') $meta['description'] = $product['name'] . ' : ' . price_range($product) . '. Découvrez les tailles disponibles. Livraison en Tunisie avec ' . $name . '.';
        $meta['schema'][] = seo_product_schema($product);
        $meta['type'] = 'product';
        if (!empty($product['images'])) $meta['image'] = seo_url('uploads/' . implode('/', array_map('rawurlencode', explode('/', $product['images'][0]['file']))));
        $cat = $product['kind'];
        $meta['schema'][] = ['@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => seo_url()],
            ['@type' => 'ListItem', 'position' => 2, 'name' => KINDS[$cat], 'item' => seo_url('boutique.php?cat=' . rawurlencode($cat))],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $product['name'], 'item' => $meta['canonical']],
        ]];
    } elseif ($script === 'boutique.php') {
        $filters = [];
        if (isset(KINDS[$query['cat'] ?? ''])) $filters['cat'] = $query['cat'];
        if (($filters['cat'] ?? '') === 'plante' && isset(BANDS[$query['taille'] ?? ''])) $filters['taille'] = $query['taille'];
        if (($filters['cat'] ?? '') === 'pot' && isset(MATS[$query['mat'] ?? ''])) $filters['mat'] = $query['mat'];
        if (!empty($query['promo'])) $filters['promo'] = '1';
        $meta['canonical'] = seo_url('boutique.php' . ($filters ? '?' . http_build_query($filters) : ''));
        $meta['title'] = ($pageTitle ?: 'Plantes, pots et accessoires déco') . ' en Tunisie | ' . $name;
        if ($meta['description'] === '') $meta['description'] = 'Découvrez ' . mb_strtolower($pageTitle ?: 'notre collection de plantes, pots et accessoires déco') . ' chez ' . $name . '. Consultez les prix en TND et commandez avec livraison en Tunisie.';
        if (trim((string)($query['q'] ?? '')) !== '') $meta['index'] = false;
    } elseif ($script === 'categorie.php' && $category) {
        $meta['canonical'] = seo_url('includes/categorie.php?id=' . (int)$category['id']);
        $meta['title'] = $category['name'] . ' en Tunisie | ' . $name;
        $meta['description'] = 'Découvrez notre sélection ' . $category['name'] . ' : prix en TND et livraison en Tunisie avec ' . $name . '.';
    } elseif ($script === 'page.php' && in_array($query['p'] ?? '', ['pro', 'contact', 'livraison'], true)) {
        $meta['canonical'] = seo_url('page.php?p=' . $query['p']);
        if ($meta['description'] === '') $meta['description'] = $pageTitle . ' : retrouvez les informations et conseils AuryaDeco pour vos commandes en Tunisie.';
    } else {
        $meta['index'] = false;
    }
    if (http_response_code() >= 400) { $meta['index'] = false; $meta['schema'] = []; }
    return $meta;
}
