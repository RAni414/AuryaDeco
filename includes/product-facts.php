<?php require_once __DIR__ . '/product-information.php'; ?>
<section class="product-facts" aria-labelledby="product-facts-title"><h2 id="product-facts-title">Les détails qui comptent</h2>
<dl class="specs"><?php foreach (product_information($p) as $label => $value): ?><div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div><?php endforeach; ?></dl>
</section>
