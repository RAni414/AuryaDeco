<?php
require __DIR__ . '/includes/bootstrap.php';
$p = $_GET['p'] ?? '';
$titles = ['contact' => 'Nous contacter', 'livraison' => 'Livraison et retours', 'pro' => 'Professionnels'];
if (!isset($titles[$p])) {
    redirect('index.php');
}
$pageTitle = $titles[$p];
require __DIR__ . '/includes/header.php';
?>
<div class="wrap">
  <div class="crumbs"><a href="<?= e(url('index.php')) ?>">Accueil</a> / <?= e($pageTitle) ?></div>
<?php if ($p === 'contact'): ?>
  <h1 class="ptitle">Nous contacter</h1>
  <div class="co">
    <div class="card">
      <h2 class="h2s">Un conseil, une question ?</h2>
      <p class="muted">Envoyez-nous une photo de votre pièce : nous vous proposons la plante et le pot adaptés.</p>
      <p><strong>Téléphone :</strong> <?= e(setting('phone')) ?><br><strong>E-mail :</strong> <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a><br><strong>Adresse :</strong> <?= e(setting('address')) ?></p>
      <a class="btn" target="_blank" rel="noopener" href="<?= e(wa_link()) ?>">Écrire sur WhatsApp</a>
    </div>
    <div class="card"><h2 class="h2s">Horaires</h2><p><?= e(setting('hours')) ?></p></div>
  </div>
<?php elseif ($p === 'livraison'): ?>
  <h1 class="ptitle">Livraison et retours</h1>
  <div class="desc">
    <p>Nous livrons dans les 24 gouvernorats. Comptez 2 à 4 jours ouvrables selon votre ville.</p>
    <p>Les frais de livraison dépendent du produit : ils sont indiqués sur chaque fiche et additionnés dans le panier.<?php if ((float)setting('free_shipping') > 0): ?> La livraison est offerte dès <?= e(money(setting('free_shipping'))) ?> d'achat.<?php endif; ?></p>
    <p>Vérifiez votre colis devant le livreur. Si un article arrive abîmé, refusez-le ou contactez-nous sous 48 heures avec une photo : nous le remplaçons.</p>
  </div>
<?php else: ?>
  <section class="block" style="padding-top:10px"><div class="pro">
    <div>
      <h1 class="ptitle">Vous aménagez un espace professionnel ?</h1>
      <p class="muted" style="font-size:17px">Hôtels, restaurants, cafés, bureaux, cliniques et vitrines : nous composons des ensembles plantes et pots adaptés à vos volumes et à votre budget.</p>
      <ul><li>Étude de votre espace à partir de photos</li><li>Tarifs dégressifs selon les quantités</li><li>Livraison et installation sur place</li><li>Murs végétaux et compositions sur mesure</li></ul>
      <a class="btn" target="_blank" rel="noopener" href="<?= e(wa_link('Bonjour ' . setting('shop_name') . ', je souhaite un devis pour un espace professionnel.')) ?>">Demander un devis</a>
    </div>
    <div class="pro-art"><?= art_tag(['slug' => 'olivier', 'kind' => 'plante', 'type' => 'olive', 'name' => 'Olivier']) ?><?= art_tag(['slug' => 'bambou', 'kind' => 'plante', 'type' => 'bamboo', 'name' => 'Bambou']) ?></div>
  </div></section>
<?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php';
