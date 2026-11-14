<?php
require __DIR__ . '/../../includes/helpers.php';
require __DIR__ . '/../../includes/navigation.php';
function check_navigation(bool $ok, string $label): void {
    if (!$ok) throw new RuntimeException($label);
    echo "PASS $label\n";
}
$input = navigation_defaults();
$input['pot']['order'] = 0;
$input['pot']['label'] = 'Nos cache-pots';
$input['pot']['path'] = 'https://untrusted.invalid';
$input['plante']['children']['geantes']['order'] = 0;
$input['plante']['children']['petites']['visible'] = false;
$input['pro']['visible'] = false;
$saved = navigation_validate($input);
$menu = navigation_merge($saved);
check_navigation(array_key_first($menu) === 'pot' && $menu['pot']['label'] === 'Nos cache-pots', 'category name and order saved');
check_navigation($menu['pot']['path'] === 'boutique.php?cat=pot', 'category target cannot be replaced by form data');
check_navigation(array_key_first($menu['plante']['children']) === 'geantes', 'subcategory order saved');
check_navigation(!$menu['pro']['visible'] && !$menu['plante']['children']['petites']['visible'], 'category and child visibility saved');
check_navigation(navigation_merge([]) === navigation_defaults(), 'existing navigation preserved without configuration');
foreach (['blank', 'order', 'missing'] as $case) {
    $invalid = $input;
    if ($case === 'blank') $invalid['pot']['label'] = ' ';
    if ($case === 'order') $invalid['pot']['order'] = -1;
    if ($case === 'missing') unset($invalid['plante']['children']['petites']);
    $rejected = false;
    try { navigation_validate($invalid); } catch (InvalidArgumentException $e) { $rejected = true; }
    check_navigation($rejected, "$case form rejected before saving");
}
