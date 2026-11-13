<?php
$designPots = load_products("p.active = 1 AND p.kind IN ('plante', 'pot')", [], 'p.featured DESC, p.position ASC, p.id DESC', 8);
$collectionSelection = settings()['home_pot_collection_ids'] ?? null;
if ($collectionSelection !== null) {
    $collectionIds = array_values(array_filter(array_map('intval', (array)json_decode($collectionSelection, true)), fn($id) => $id > 0));
    $designPots = $collectionIds ? load_products("p.active = 1 AND p.kind IN ('plante', 'pot') AND p.id IN (" . implode(',', $collectionIds) . ')', [], 'p.featured DESC, p.position ASC, p.id DESC', 8) : [];
}
?>
<section class="home-pot-collection" aria-labelledby="pot-collection-title">
  <div class="wrap">
    <header class="home-pot-collection__heading">
      <h2 id="pot-collection-title">Découvrez notre nouvelle collection de plantes et cache-pots design pour sublimer vos espaces.</h2>
      <p>Des formes, des textures et des couleurs pour tous les goûts.</p>
    </header>
    <div class="home-pot-collection__grid">
      <?php foreach ($designPots as $designPot): ?>
        <?= product_card($designPot) ?>
      <?php endforeach; ?>
    </div>
    <div class="home-pot-collection__more"><a class="btn" href="<?= e(url('boutique.php')) ?>">Voir toutes les plantes et pots</a></div>
  </div>
</section>

<?php require __DIR__ . '/home-accessories.php'; ?>
