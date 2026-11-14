<?php
require __DIR__ . '/../../includes/helpers.php';
function verify_size(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS $message\n";
}
foreach (['3' => '3 KG', '10' => '10 KG', '20' => '20 KG', '3kg' => '3 KG', '2,5' => '2,5 KG', 'Sac de 2 kg' => 'Sac de 2 kg'] as $label => $expected) {
    verify_size(variant_label(['kind' => 'acc', 'type' => 'pebbles'], ['label' => (string)$label]) === $expected, 'pebble weight ' . $label);
}
verify_size(variant_label(['kind' => 'acc', 'type' => 'moss'], ['label' => '250g']) === '250g', 'other accessory units preserved');
$p = ['kind' => 'pot', 'variants' => [
    ['id' => 1, 'label' => 'H 25,5 cm - D 25,5 cm', 'diameter' => null, 'price' => 59, 'old_price' => null, 'stock' => 3],
    ['id' => 2, 'label' => 'Grand <test>', 'diameter' => 42, 'price' => 98, 'old_price' => 109, 'stock' => 0],
]];
verify_size(variant_label($p, $p['variants'][0]) === 'H 25,5 cm - D 25,5 cm', 'custom decimal size preserved');
verify_size(variant_label($p, ['diameter' => 42, 'label' => null]) === 'D 42 cm', 'existing diameter remains readable');
verify_size(sizes_text($p) === 'H 25,5 cm - D 25,5 cm / Grand <test>', 'size summary uses configured labels');
$sel = 0;
ob_start();
require __DIR__ . '/../../includes/product-options.php';
$html = ob_get_clean();
verify_size(str_contains($html, 'name="variant_id"') && str_contains($html, '<select'), 'dropdown submits variant ID to existing cart');
verify_size(str_contains($html, 'Grand &lt;test&gt;') && str_contains($html, 'data-instock="0"'), 'labels escaped and unavailable size identified');
verify_size(str_contains($html, 'data-price="98 TND"') && str_contains($html, 'data-old="109 TND"'), 'price and promotion data retained');
