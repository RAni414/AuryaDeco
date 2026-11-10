<?php
require __DIR__ . '/includes/bootstrap.php';

$cat = $_GET['cat'] ?? '';
$taille = $_GET['taille'] ?? '';
$mat = $_GET['mat'] ?? '';
$promo = !empty($_GET['promo']);
$q = trim((string)($_GET['q'] ?? ''));
$tri = $_GET['tri'] ?? '';

$where = ['p.active = 1'];
$params = [];
if (isset(KINDS[$cat])) { $where[] = 'p.kind = ?'; $params[] = $cat; }
if ($taille === 'tombantes') {
    $where[] = "p.kind = 'plante' AND p.tomb = 1";
} elseif (isset(BANDS[$taille])) {
    $ranges = ['petites' => [0, 70], 'moyennes' => [71, 159], 'grandes' => [160, 220], 'geantes' => [221, 10000]];
    [$a, $b] = $ranges[$taille];
    $where[] = "p.kind = 'plante' AND p.tomb = 0 AND EXISTS (SELECT 1 FROM variants v WHERE v.product_id = p.id AND v.height BETWEEN ? AND ?)";
    array_push($params, $a, $b);
}
if (isset(MATS[$mat])) { $where[] = 'p.mat = ?'; $params[] = $mat; }
if ($promo) { $where[] = 'EXISTS (SELECT 1 FROM variants v WHERE v.product_id = p.id AND v.old_price > v.price)'; }
if ($q !== '') { $where[] = '(p.name LIKE ? OR p.description LIKE ?)'; $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%'; array_push($params, $like, $like); }

$order = 'p.position ASC, p.id DESC';
$minPrice = '(SELECT MIN(v.price) FROM variants v WHERE v.product_id = p.id)';
if ($tri === 'asc') $order = "$minPrice ASC";
elseif ($tri === 'desc') $order = "$minPrice DESC";
elseif ($tri === 'nom') $order = 'p.name ASC';

$list = load_products(implode(' AND ', $where), $params, $order);

$title = $q !== '' ? 'Résultats pour « ' . $q . ' »' : ($promo ? 'Promotions' : (isset(BANDS[$taille]) ? BANDS[$taille] : (isset(MATS[$mat]) ? 'Pots en ' . mb_strtolower(MATS[$mat]) : ($cat === 'plante' ? 'Plantes artificielles' : ($cat === 'pot' ? 'Pots' : ($cat === 'acc' ? 'Accessoires' : 'Toute la boutique'))))));
$pageTitle = $title;

function shop_link(array $over): string
{
    $q = array_merge($_GET, $over);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null);
    return url('boutique.php' . ($q ? '?' . http_build_query($q) : ''));
}
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
  <div class="crumbs"><a href="<?= e(url('index.php')) ?>">Accueil</a> / <?= e($title) ?></div>
  <h1 class="ptitle"><?= e($title) ?></h1>
  <?php if ($cat === 'plante'): ?>
    <div class="chips">
      <a class="chip" href="<?= e(shop_link(['taille' => ''])) ?>" aria-current="<?= $taille === '' ? 'true' : 'false' ?>">Toutes</a>
      <?php foreach (BANDS as $k => $l): ?><a class="chip" href="<?= e(shop_link(['taille' => $k])) ?>" aria-current="<?= $taille === $k ? 'true' : 'false' ?>"><?= e($l) ?></a><?php endforeach; ?>
    </div>
  <?php elseif ($cat === 'pot'): ?>
    <div class="chips">
      <a class="chip" href="<?= e(shop_link(['mat' => ''])) ?>" aria-current="<?= $mat === '' ? 'true' : 'false' ?>">Toutes les matières</a>
      <?php foreach (MATS as $k => $l): ?><a class="chip" href="<?= e(shop_link(['mat' => $k])) ?>" aria-current="<?= $mat === $k ? 'true' : 'false' ?>"><?= e($l) ?></a><?php endforeach; ?>
    </div>
  <?php endif; ?>
  <form class="filters" method="get" action="<?= e(url('boutique.php')) ?>" data-autosubmit>
    <?php foreach (['taille' => $taille, 'mat' => $mat, 'q' => $q, 'promo' => $promo ? '1' : ''] as $k => $val): if ($val !== ''): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($val) ?>"><?php endif; endforeach; ?>
    <label>Catégorie<select name="cat" data-reset="taille,mat"><option value="">Tout</option><?php foreach (['plante' => 'Plantes artificielles', 'pot' => 'Pots', 'acc' => 'Accessoires'] as $k => $l): ?><option value="<?= $k ?>" <?= $cat === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label>Trier par<select name="tri"><option value="">Pertinence</option><option value="asc" <?= $tri === 'asc' ? 'selected' : '' ?>>Prix croissant</option><option value="desc" <?= $tri === 'desc' ? 'selected' : '' ?>>Prix décroissant</option><option value="nom" <?= $tri === 'nom' ? 'selected' : '' ?>>Nom</option></select></label>
    <noscript><button class="btn ghost">Filtrer</button></noscript>
    <span class="count"><?= count($list) ?> produit<?= count($list) > 1 ? 's' : '' ?></span>
  </form>
  <?= product_grid($list) ?>
</div>
<?php require __DIR__ . '/includes/footer.php';
