<?php
require_once __DIR__ . '/shop-filters.php';
$filterError = '';
try { $filters = shop_filter_values($_GET); }
catch (InvalidArgumentException $e) { $filterError = $e->getMessage(); $filters = shop_filter_values(array_diff_key($_GET, ['min' => true, 'max' => true])); http_response_code(400); }
[$where, $params, $order] = shop_filter_query($filters);
$list = $filterError ? [] : load_products($where, $params, $order);
$cat = $filters['cat'];
$pageTitle = $filters['q'] !== '' ? 'Résultats pour « ' . $filters['q'] . ' »' : ($filters['promo'] ? 'Promotions' : ($cat ? KINDS[$cat] : 'Toute la boutique'));
if ($filters['taille']) $pageTitle = BANDS[$filters['taille']];
if ($filters['mat']) $pageTitle = 'Pots en ' . mb_strtolower(MATS[$filters['mat']]);
$colorOptions = db()->query("SELECT DISTINCT c.name FROM pot_colors c JOIN products p ON p.id=c.product_id WHERE p.active=1 AND p.kind='pot' ORDER BY c.name")->fetchAll(PDO::FETCH_COLUMN);
$sizeOptions = db()->query("SELECT DISTINCT COALESCE(NULLIF(TRIM(v.label),''),CONCAT('D ',v.diameter,' cm')) AS size_label FROM variants v JOIN products p ON p.id=v.product_id WHERE p.active=1 AND p.kind='pot' ORDER BY size_label")->fetchAll(PDO::FETCH_COLUMN);
require __DIR__ . '/header.php';
?>
<div class="wrap shop-page">
  <div class="crumbs"><a href="<?= e(url('index.php')) ?>">Accueil</a> / <?= e($pageTitle) ?></div>
  <div class="shop-heading"><div><h1 class="ptitle"><?= e($pageTitle) ?></h1><p>Choisissez la touche déco qui vous ressemble.</p></div><span><?= count($list) ?> produit<?= count($list) > 1 ? 's' : '' ?></span></div>
  <details class="shop-filter-box" open data-shop-filters>
    <summary>Filtrer les produits <span>Prix, tailles et disponibilités</span></summary>
    <form method="get" action="<?= e(url('boutique.php')) ?>" class="shop-filter-form">
      <?php if ($filters['q'] !== ''): ?><input type="hidden" name="q" value="<?= e($filters['q']) ?>"><?php endif; ?>
      <input type="hidden" name="tri" value="<?= e($filters['tri']) ?>">
      <label>Catégorie<select name="cat"><option value="">Toutes</option><?php foreach (KINDS as $key => $label): ?><option value="<?= e($key) ?>" <?= $cat === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
      <div class="shop-price-range"><label>Prix minimum (TND)<input name="min" type="number" min="0" step="0.001" value="<?= e($filters['min']) ?>" placeholder="0"></label><label>Prix maximum (TND)<input name="max" type="number" min="0" step="0.001" value="<?= e($filters['max']) ?>" placeholder="Sans limite"></label></div>
      <?php if ($cat === 'plante'): ?><label>Hauteur<select name="taille"><option value="">Toutes les hauteurs</option><?php foreach (BANDS as $key => $label): ?><option value="<?= e($key) ?>" <?= $filters['taille'] === $key ? 'selected' : '' ?>><?= e($label . (BAND_HINT[$key] ? ' · ' . BAND_HINT[$key] : '')) ?></option><?php endforeach; ?></select></label><?php endif; ?>
      <?php if ($cat === 'pot'): ?>
        <label>Matière<select name="mat"><option value="">Toutes les matières</option><?php foreach (MATS as $key => $label): ?><option value="<?= e($key) ?>" <?= $filters['mat'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
        <label>Couleur<select name="color"><option value="">Toutes les couleurs</option><?php foreach ($colorOptions as $label): ?><option value="<?= e($label) ?>" <?= $filters['color'] === $label ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
        <label>Taille du pot<select name="pot_size"><option value="">Toutes les tailles</option><?php foreach ($sizeOptions as $label): ?><option value="<?= e($label) ?>" <?= $filters['pot_size'] === $label ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
      <?php endif; ?>
      <label>Utilisation<select name="usage"><option value="">Tous les usages</option><?php foreach (['interieur' => 'Intérieur', 'exterieur' => 'Extérieur', 'mixte' => 'Intérieur et extérieur'] as $key => $label): ?><option value="<?= $key ?>" <?= $filters['usage'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
      <div class="shop-filter-checks"><label><input type="checkbox" name="available" value="1" <?= $filters['available'] ? 'checked' : '' ?>>En stock uniquement</label><label><input type="checkbox" name="promo" value="1" <?= $filters['promo'] ? 'checked' : '' ?>>En promotion</label></div>
      <div class="shop-filter-actions"><button class="btn" type="submit">Appliquer les filtres</button><a href="<?= e(url('boutique.php' . ($cat ? '?cat=' . $cat : ''))) ?>">Réinitialiser</a></div>
    </form>
  </details>
  <?php if ($filterError): ?><p role="alert" class="flash flash-err"><?= e($filterError) ?></p><?php endif; ?>
  <div class="shop-results-bar">
    <div class="shop-active-filters">
      <?php foreach (['q' => 'Recherche', 'min' => 'Prix min.', 'max' => 'Prix max.', 'color' => 'Couleur', 'pot_size' => 'Taille', 'taille' => 'Hauteur', 'mat' => 'Matière', 'usage' => 'Usage', 'available' => 'En stock', 'promo' => 'Promotion'] as $key => $label): if ($filters[$key] === '') continue; ?>
        <a href="<?= e(shop_filter_link($filters, [$key => ''])) ?>" class="shop-filter-chip" aria-label="<?= e('Retirer le filtre ' . $label) ?>"><?= e($label) ?><?= in_array($key, ['available','promo'], true) ? '' : ' : ' . e(shop_filter_display($key, $filters[$key])) ?> <span aria-hidden="true">×</span></a>
      <?php endforeach; ?>
    </div>
    <form method="get" data-autosubmit><?php foreach ($filters as $key => $value): if ($key === 'tri' || $value === '') continue; ?><input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>"><?php endforeach; ?><label>Trier par<select name="tri"><?php foreach (['' => 'Notre sélection', 'asc' => 'Prix croissant', 'desc' => 'Prix décroissant', 'nom' => 'Nom'] as $key => $label): ?><option value="<?= e($key) ?>" <?= $filters['tri'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label><noscript><button class="btn" type="submit">Trier</button></noscript></form>
  </div>
  <?= product_grid($list, 'Aucun produit ne correspond à ces critères. Essayez de retirer un filtre pour élargir la sélection.') ?>
</div>
<?php require __DIR__ . '/footer.php';
