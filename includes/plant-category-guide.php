<?php
require_once __DIR__ . '/plant-category-content.php';
if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'boutique.php' || !plant_category_is_main($_GET)) return;
// Keep the advice on the main collection, not on search or filtered listings.
foreach (['min', 'max', 'color', 'pot_size', 'usage', 'available'] as $filter) if (!empty($_GET[$filter])) return;
?>
<section class="wrap plant-guide" aria-labelledby="plant-guide-title">
  <h2 id="plant-guide-title">Comment choisir votre plante artificielle ?</h2>
  <div class="plant-guide__grid">
    <div><h3>Prévoir sa place</h3><p>Mesurez la hauteur et la largeur disponibles avant de choisir. Consultez les dimensions de chaque modèle pour garder de l’espace autour du feuillage et dans les zones de passage.</p></div>
    <div><h3>Associer la plante à un cache-pot</h3><p>Vérifiez les dimensions du pot de la plante et l’ouverture du cache-pot. La fiche produit précise les éléments inclus : une photo de mise en situation ne suffit pas pour savoir si le cache-pot est vendu avec la plante.</p><a href="<?= e(url('boutique.php?cat=pot')) ?>">Découvrir nos pots et cache-pots</a></div>
    <div><h3>Vérifier l’emplacement conseillé</h3><p>Pour une terrasse ou un balcon, consultez les indications de la fiche produit ou demandez-nous conseil. Toutes les plantes artificielles ne sont pas adaptées au soleil et aux intempéries.</p><a href="<?= e(url('page.php?p=contact')) ?>">Demander conseil à AuryaDeco</a></div>
  </div>
</section>
