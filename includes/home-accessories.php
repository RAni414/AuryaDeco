<?php
$accessorySelection = settings()['home_accessory_collection_ids'] ?? null;
$accessoryWhere = "p.active = 1 AND p.kind = 'acc'";
if ($accessorySelection !== null) {
    $accessoryIds = array_values(array_filter(array_map('intval', (array)json_decode($accessorySelection, true)), fn($id) => $id > 0));
    $accessoryWhere .= $accessoryIds ? ' AND p.id IN (' . implode(',', $accessoryIds) . ')' : ' AND 1 = 0';
}
$accessoryOrder = !empty($accessoryIds) ? 'FIELD(p.id,' . implode(',', $accessoryIds) . ')' : 'p.position ASC, p.id DESC';
$decorAccessories = load_products($accessoryWhere, [], $accessoryOrder, 8);
?>
<section class="home-pot-collection home-accessories" aria-labelledby="accessories-title">
  <div class="wrap">
    <header class="home-pot-collection__heading">
      <h2 id="accessories-title">Sublimez votre intérieur et votre extérieur avec des accessoires déco pleins de charme.</h2>
    </header>
    <?= product_grid($decorAccessories, 'Nos accessoires déco arrivent bientôt.') ?>
    <div class="home-pot-collection__more"><a class="btn" href="<?= e(url('boutique.php?cat=acc')) ?>">Découvrir tous les accessoires</a></div>
  </div>
</section>

<?php require __DIR__ . '/home-spirit.php'; ?>
