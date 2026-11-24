<?php
require __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/pot-dimensions.php';
function verify_dimensions(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS $message\n";
}
foreach ([
    '40cm H-28cm D' => 'H 40 cm · D 28 cm',
    'H 40 cm - D 28 cm' => 'H 40 cm · D 28 cm',
    'H 25.5 cm / D 20,5 cm' => 'H 25,5 cm · D 20,5 cm',
    '40cm H-28cm D · Noire' => 'H 40 cm · D 28 cm',
    'L 30 cm H 40 cm D 28 cm' => 'H 40 cm · D 28 cm · L 30 cm',
] as $raw => $expected) verify_dimensions(pot_size_label(['label' => $raw]) === $expected, 'dimension notation normalized: ' . $raw);
verify_dimensions(pot_size_label(['label' => null, 'diameter' => 42]) === 'D 42 cm', 'missing height is not invented');
verify_dimensions(pot_size_label(['label' => 'Petit · Noir', 'color_name' => 'Noir']) === 'Petit', 'color suffix removed from nonnumeric size');
$p = ['kind' => 'pot', 'variants' => [
    ['label' => '40cm H-28cm D', 'color_name' => 'Noir'],
    ['label' => 'H 40 cm · D 28 cm', 'color_name' => 'Rose'],
    ['label' => '25cm H-20cm D', 'color_name' => 'Noir'],
    ['label' => 'H 25 cm - D 20 cm', 'color_name' => 'Rose'],
]];
$summary = sizes_text($p);
verify_dimensions($summary === 'H 40 cm · D 28 cm / H 25 cm · D 20 cm', 'sizes listed once independently of colors');
verify_dimensions(product_card_pot_options($p) === $summary && !str_contains($summary, 'couleur'), 'cards show dimensions instead of option counts');
verify_dimensions(variant_label($p, $p['variants'][0]) === 'H 40 cm · D 28 cm · Noir', 'cart still records the chosen color');
verify_dimensions(pot_size_input_label(['dimension_h' => '40', 'dimension_d' => '28,5', 'dimension_l' => '', 'label' => 'Ancien texte']) === 'H 40 cm · D 28,5 cm', 'structured admin dimensions take precedence');
$rejected = false;
try { pot_size_input_label(['dimension_h' => '-1']); } catch (InvalidArgumentException $e) { $rejected = true; }
verify_dimensions($rejected, 'negative dimension rejected');
