<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$CONFIG = ['base_url' => '/test']; $_SESSION = ['csrf' => 'test-csrf'];
require __DIR__ . '/../../includes/helpers.php';
require __DIR__ . '/../../includes/favorites-service.php';
function check($value, string $label): void { if (!$value) throw new RuntimeException('FAIL ' . $label); echo 'PASS ' . $label . "\n"; }
check(favorite_ids([4, 4, 9]) === [4, 9], 'duplicate IDs removed');
check(favorite_ids([]) === [], 'empty list');
foreach ([[0], [-1], ['1'], [4294967296], array_fill(0,201,1), null] as $invalid) {
    try { favorite_ids($invalid); throw new RuntimeException('Invalid IDs accepted'); }
    catch (InvalidArgumentException $e) { echo "PASS invalid IDs rejected\n"; }
}
$p = ['id'=>12,'slug'=>'test','kind'=>'plante','name'=>'<img src=x onerror=alert(1)>', 'type'=>'palm','images'=>[],
    'variants'=>[['id'=>18,'height'=>120,'diameter'=>null,'label'=>null,'price'=>'125.500','old_price'=>null,'stock'=>0]]];
$html = favorite_product_card($p);
check(strpos($html, 'Actuellement indisponible') !== false && strpos($html, 'class="fav-buy"') === false, 'unavailable product cannot be ordered from favorites');
check(strpos($html, '&lt;img') !== false && strpos($html, '<img src=x') === false, 'catalogue text escaped');
check(strpos($html, 'data-favorite-id="12"') !== false, 'unavailable product can still be removed');
$p['variants'][0]['stock'] = null;
$html = favorite_product_card($p);
check(strpos($html, 'action="/test/panier.php"') !== false && strpos($html, 'name="variant_id" value="18"') !== false, 'single variant uses existing cart');
$p['variants'][] = ['id'=>19,'height'=>180,'diameter'=>null,'label'=>null,'price'=>'200.000','old_price'=>null,'stock'=>0];
$html = favorite_product_card($p);
check(strpos($html, 'select name="variant_id" required') !== false && strpos($html, 'value="19" disabled') !== false, 'variant selection and unavailable option');
