<?php
require __DIR__ . '/includes/bootstrap.php';
$p = load_product_by('slug', (string)($_GET['p'] ?? ''));
if (!$p) {
    http_response_code(404);
    $pageTitle = 'Produit introuvable';
    require __DIR__ . '/includes/header.php';
    echo '<div class="wrap"><div class="empty" style="margin:40px 0">Ce produit n\'existe plus. <a href="' . e(url('boutique.php')) . '">Retour à la boutique</a></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$pageTitle = $p['name'];
$paras = array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', (string)$p['description']))));
$metaDesc = $paras[0] ?? '';

$sel = 0;
foreach ($p['variants'] as $i => $v) {
    if (in_stock($v)) { $sel = $i; break; }
}
$v = $p['variants'][$sel] ?? null;

$pair = [];
$pairTitle = '';
if ($p['kind'] === 'plante' && $p['open_min'] && $p['open_max']) {
    $pair = load_products("p.active = 1 AND p.kind = 'pot' AND EXISTS (SELECT 1 FROM variants v WHERE v.product_id = p.id AND v.diameter BETWEEN ? AND ?)", [$p['open_min'], $p['open_max']], 'p.position ASC', 8);
    $pairTitle = 'Les pots adaptés à cette plante';
} elseif ($p['kind'] === 'pot') {
    $ds = array_filter(array_map(fn($x) => (int)$x['diameter'], $p['variants']));
    if ($ds) {
        $conds = implode(' OR ', array_fill(0, count($ds), '(p.open_min <= ? AND p.open_max >= ?)'));
        $prm = [];
        foreach ($ds as $d) { $prm[] = $d; $prm[] = $d; }
        $pair = load_products("p.active = 1 AND p.kind = 'plante' AND ($conds)", $prm, 'p.position ASC', 8);
        $pairTitle = 'Les plantes qui vont avec ce pot';
    }
}
$similar = load_products('p.active = 1 AND p.kind = ? AND p.id <> ?', [$p['kind'], $p['id']], 'p.featured DESC, p.position ASC', 4);
$optLabel = $p['kind'] === 'plante' ? 'Hauteur' : ($p['kind'] === 'pot' ? 'Diamètre' : 'Format');
$crumb = $p['kind'] === 'plante' ? ['cat=plante', 'Plantes artificielles'] : ($p['kind'] === 'pot' ? ['cat=pot', 'Pots'] : ['cat=acc', 'Accessoires']);

require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
  <div class="crumbs"><a href="<?= e(url('index.php')) ?>">Accueil</a> / <a href="<?= e(url('boutique.php?' . $crumb[0])) ?>"><?= e($crumb[1]) ?></a> / <?= e($p['name']) ?></div>
  <div class="pp">
    <div>
      <div class="media" id="mainMedia"><?= media($p, true) ?></div>
      <?php if (count($p['images']) > 1): ?>
        <div class="thumbs">
          <?php foreach ($p['images'] as $i => $img): ?>
            <button type="button" data-src="<?= e(url('uploads/' . $img['file'])) ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>" aria-label="Photo <?= $i + 1 ?>"><img class="ph" src="<?= e(url('uploads/' . $img['file'])) ?>" alt=""></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div>
      <span class="muted"><?= e($p['kind'] === 'pot' ? 'Pot en ' . mb_strtolower(MATS[$p['mat']] ?? '') : sub_text($p)) ?></span>
      <h1><?= e($p['name']) ?></h1>
      <?php if ($v): ?>
      <form method="post" action="<?= e(url('panier.php')) ?>" id="buyForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <div class="pprice"><span id="price"><?= e(money($v['price'])) ?></span> <s class="muted old" id="oldPrice"<?= ($v['old_price'] !== null && (float)$v['old_price'] > (float)$v['price']) ? '' : ' hidden' ?>><?= $v['old_price'] !== null ? e(money($v['old_price'])) : '' ?></s></div>
        <p class="oos" id="oosMsg"<?= in_stock($v) ? ' hidden' : '' ?>>Cette taille est en rupture de stock.</p>
        <div class="opt-label"><?= e($optLabel) ?></div>
        <div class="opts" role="radiogroup" aria-label="<?= e($optLabel) ?>">
          <?php foreach ($p['variants'] as $i => $x): $ok = in_stock($x); ?>
            <label class="opt<?= $ok ? '' : ' out' ?>">
              <input type="radio" name="variant_id" value="<?= (int)$x['id'] ?>" <?= $i === $sel ? 'checked' : '' ?>
                data-price="<?= e(money($x['price'])) ?>"
                data-old="<?= ($x['old_price'] !== null && (float)$x['old_price'] > (float)$x['price']) ? e(money($x['old_price'])) : '' ?>"
                data-instock="<?= $ok ? '1' : '0' ?>">
              <span><?= e(variant_label($p, $x)) ?><?= $ok ? '' : ' (épuisé)' ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <div class="buy">
          <div class="qty"><button type="button" data-step="-1" aria-label="Diminuer la quantité">−</button><input type="number" name="qty" value="1" min="1" max="99" aria-label="Quantité"><button type="button" data-step="1" aria-label="Augmenter la quantité">+</button></div>
          <button class="btn" id="addBtn" type="submit"<?= in_stock($v) ? '' : ' disabled' ?>><?= in_stock($v) ? 'Ajouter au panier' : 'Rupture de stock' ?></button>
        </div>
      </form>
      <?php endif; ?>
      <p class="ship-line"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/></svg> <span>Livraison : <strong><?= e(ship_text($p['shipping_fee'])) ?></strong><?php if ((float)setting('free_shipping') > 0): ?>. Offerte dès <?= e(money(setting('free_shipping'))) ?> d'achat.<?php endif; ?></span></p>
      <?php if ($p['kind'] !== 'acc'): ?>
        <div class="note"><?= $p['kind'] === 'plante' ? 'La plante est livrée dans un pot de culture noir, sans pot décoratif. Retrouvez plus bas les pots de la bonne taille.' : 'Le pot est vendu sans plante. Retrouvez plus bas les plantes de la bonne taille.' ?></div>
      <?php endif; ?>
      <div class="desc"><?php foreach ($paras as $t): ?><p><?= nl2br(e($t)) ?></p><?php endforeach; ?></div>
      <dl class="specs">
        <?php if ($p['kind'] === 'plante'): ?>
          <div><dt>Hauteurs disponibles</dt><dd><?= e(sizes_text($p)) ?></dd></div>
          <?php if ($p['open_min'] && $p['open_max']): ?><div><dt>Ouverture de pot conseillée</dt><dd>Ø <?= (int)$p['open_min'] ?> à <?= (int)$p['open_max'] ?> cm</dd></div><?php endif; ?>
          <div><dt>Livrée dans</dt><dd>Pot de culture noir</dd></div>
          <div><dt>Entretien</dt><dd>Un coup de chiffon de temps en temps</dd></div>
        <?php elseif ($p['kind'] === 'pot'): ?>
          <div><dt>Diamètres d'ouverture</dt><dd><?= e(sizes_text($p)) ?></dd></div>
          <div><dt>Matière</dt><dd><?= e(MATS[$p['mat']] ?? '') ?></dd></div>
        <?php else: ?>
          <div><dt>Format</dt><dd><?= e(sizes_text($p)) ?></dd></div>
        <?php endif; ?>
      </dl>
    </div>
  </div>
  <?php if ($pair): ?>
    <section class="block" style="padding:30px 0"><div class="shead"><div><h2><?= e($pairTitle) ?></h2><?php if ($p['kind'] === 'plante'): ?><p>Ouverture entre <?= (int)$p['open_min'] ?> et <?= (int)$p['open_max'] ?> cm.</p><?php endif; ?></div></div><?= product_grid($pair) ?></section>
  <?php endif; ?>
  <?php if ($similar): ?>
    <section class="block" style="padding:20px 0 30px"><div class="shead"><div><h2>Vous aimerez aussi</h2></div></div><?= product_grid($similar) ?></section>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php';
