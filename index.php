<?php
require __DIR__ . '/includes/bootstrap.php';
$metaDesc = setting('hero_text');
$featured = load_products('p.active = 1 AND p.featured = 1', [], 'p.position ASC, p.id DESC', 8);
if (!$featured) {
    $featured = load_products('p.active = 1', [], 'p.id DESC', 8);
}
$pick = function (string $where) {
    $l = load_products('p.active = 1 AND ' . $where, [], 'p.featured DESC, p.position ASC', 1);
    return $l[0] ?? null;
};
$tiles = [
    ['cat=plante', 'Plantes artificielles', 'Petites, moyennes, grandes et géantes', $pick("p.kind = 'plante' AND p.tomb = 0"), ['kind' => 'plante', 'type' => 'kentia', 'slug' => 'kentia', 'name' => 'Plante']],
    ['cat=pot', 'Pots', 'Ciment, résine, pierre, terre cuite', $pick("p.kind = 'pot'"), ['kind' => 'pot', 'shape' => 'tall', 'color' => '#7F8F82', 'slug' => 'pot', 'name' => 'Pot']],
    ['cat=plante&taille=tombantes', 'Plantes tombantes', 'Pour étagères et suspensions', $pick("p.kind = 'plante' AND p.tomb = 1"), ['kind' => 'plante', 'type' => 'hanging', 'tomb' => 1, 'slug' => 'pothos', 'name' => 'Plante tombante']],
    ['cat=acc', 'Accessoires', 'Galets, mousse et finitions', $pick("p.kind = 'acc'"), ['kind' => 'acc', 'type' => 'pebbles', 'slug' => 'galets', 'name' => 'Accessoire']],
];
$reviews = load_reviews(9);
$homePage = true;
require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/home-hero.php'; ?>

<div class="promises"><div class="wrap">
<?php
$free = (float)setting('free_shipping');
$promises = [
    ['M3 7h11v9H3zM14 10h4l3 3v3h-7', 'Livraison en Tunisie', $free > 0 ? 'Offerte dès ' . money($free) : 'Dans les 24 gouvernorats'],
    ['M12 3v18M5 8l7-5 7 5', 'Sans entretien', 'Ni arrosage, ni lumière'],
    ['M4 12h16M4 6h16M4 18h10', 'Paiement à la livraison', 'En espèces, à réception'],
    ['M5 13l4 4L19 7', 'Conseil gratuit', 'Pour assortir plante et pot'],
];
foreach ($promises as [$d, $a, $b]): ?>
  <div class="promise"><svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="<?= e($d) ?>"/></svg><div><strong><?= e($a) ?></strong><span><?= e($b) ?></span></div></div>
<?php endforeach; ?>
</div></div>

<?php require __DIR__ . '/includes/home-universes.php'; ?>

<section class="block tight"><div class="wrap">
  <div class="shead"><div><h2>Les favoris de nos clients</h2><p>Les plantes et pots les plus choisis en ce moment.</p></div><a href="<?= e(url('boutique.php')) ?>">Toute la boutique</a></div>
  <?= product_grid($featured, 'Les produits apparaîtront ici dès que vous les aurez ajoutés depuis l\'administration.') ?>
</div></section>

<?php if ($reviews): $nr = count($reviews); ?>
<section class="reviews-band" id="avis" aria-labelledby="avisTitle"><div class="wrap">
  <div class="rhead">
    <div><h2 id="avisTitle">Ce que disent nos clients</h2><p>Les retours de celles et ceux qui ont déjà habillé leur intérieur avec nos plantes.</p></div>
    <div class="rctl" data-rctl hidden>
      <button type="button" class="rarrow" data-dir="-1" aria-label="Avis précédent"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 6-6 6 6 6"/></svg></button>
      <button type="button" class="rarrow" data-dir="1" aria-label="Avis suivant"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg></button>
    </div>
  </div>
  <div class="rcarousel" data-carousel role="region" aria-roledescription="carrousel" aria-label="Avis de nos clients">
    <div class="rtrack" tabindex="0">
      <?php foreach ($reviews as $i => $rv): ?><?= review_card($rv, $i + 1, $nr) ?><?php endforeach; ?>
    </div>
    <div class="rdots" data-rdots aria-hidden="true"></div>
  </div>
</div></section>
<?php endif; ?>

<section class="block tight"><div class="wrap">
  <div class="pairing">
    <div>
      <h2>Plantes et pots sont vendus séparément.</h2>
      <p>Chaque plante est livrée dans un pot de culture noir. Choisissez ensuite le pot décoratif qui vous plaît, en vous fiant à l'ouverture conseillée.</p>
      <a class="btn light" href="<?= e(wa_link('Bonjour, je souhaite un conseil pour choisir une plante et un pot.')) ?>" target="_blank" rel="noopener">Demander un conseil</a>
    </div>
    <table><thead><tr><th>Hauteur de la plante</th><th>Ouverture du pot</th></tr></thead><tbody>
      <tr><td>Jusqu'à 70 cm</td><td>Ø 15 à 30 cm</td></tr>
      <tr><td>70 à 160 cm</td><td>Ø 25 à 40 cm</td></tr>
      <tr><td>160 à 220 cm</td><td>Ø 35 à 55 cm</td></tr>
      <tr><td>220 cm et plus</td><td>Ø 45 à 60 cm</td></tr>
    </tbody></table>
  </div>
</div></section>
</div>
<?php require __DIR__ . '/includes/footer.php';
