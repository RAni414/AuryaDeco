<?php
session_save_path(sys_get_temp_dir());
require __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/seo.php';
function verify_seo(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS $message\n";
}
$p = ['id' => 123, 'slug' => 'bambou-test', 'name' => 'Bambou artificiel', 'kind' => 'plante',
    'description' => 'Un bambou décoratif pour votre intérieur.', 'images' => [['file' => 'photo bambou.jpg']],
    'variants' => [['price' => 59, 'old_price' => 99, 'stock' => 0], ['price' => 89, 'old_price' => null, 'stock' => null]]];
$meta = seo_metadata('produit.php', ['p' => 'bambou-test', 'utm_source' => 'test'], $p, null, $p['name'], $p['description']);
verify_seo(str_contains($meta['title'], 'Bambou artificiel en Tunisie'), 'localized product title');
verify_seo($meta['canonical'] === seo_url('produit.php?p=bambou-test'), 'product canonical excludes tracking parameters');
$product = $meta['schema'][0];
verify_seo($product['offers']['lowPrice'] === '59.000' && $product['offers']['highPrice'] === '89.000' && $product['offers']['priceCurrency'] === 'TND', 'structured price range uses current prices in TND');
verify_seo(!isset($product['aggregateRating']), 'shop reviews are not presented as product reviews');
verify_seo(str_contains($product['image'][0], 'photo%20bambou.jpg'), 'image URL is absolute and encoded');
$p['variants'] = [$p['variants'][0]];
$offer = seo_product_schema($p)['offers'];
verify_seo($offer['availability'] === 'https://schema.org/OutOfStock' && $offer['price'] === '59.000', 'single offer preserves stock status and current price');
$search = seo_metadata('boutique.php', ['q' => 'bambou'], null, null, 'Recherche', '');
verify_seo(!$search['index'], 'internal search results excluded from index');
$category = seo_metadata('boutique.php', ['cat' => 'plante', 'taille' => 'grandes', 'tri' => 'desc'], null, null, 'Grandes plantes', '');
verify_seo($category['index'] && !str_contains($category['canonical'], 'tri='), 'catalogue category indexed with canonical filter URL');
foreach (['panier.php', 'commande.php', 'favoris.php', 'avis.php'] as $script) {
    verify_seo(!seo_metadata($script, [], null, null, '', '')['index'], "$script excluded from index");
}
$home = seo_metadata('index.php', [], null, null, '', '');
verify_seo($home['index'] && $home['canonical'] === seo_url(), 'homepage uses one canonical root');
verify_seo(mb_strlen(seo_text(str_repeat('é', 250))) <= 180, 'description truncation respects UTF-8');
$p['name'] = '</script><script>alert(1)</script>';
$json = json_encode(seo_product_schema($p), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
verify_seo(!str_contains($json, '<script>'), 'structured data safely encodes product text');
