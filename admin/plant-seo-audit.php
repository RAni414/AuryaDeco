<?php
require_once __DIR__ . '/../includes/plant-seo-audit.php';
$plantAuditProducts = load_products("p.active=1 AND p.kind='plante'", [], 'p.position ASC, p.id DESC');
$plantAuditRows = [];
$plantDescriptionCounts = [];
foreach ($plantAuditProducts as $plantAuditProduct) {
    $descriptionKey = mb_strtolower(preg_replace('/\s+/u', ' ', trim(strip_tags((string)$plantAuditProduct['description']))));
    if ($descriptionKey !== '') $plantDescriptionCounts[$descriptionKey] = ($plantDescriptionCounts[$descriptionKey] ?? 0) + 1;
}
foreach ($plantAuditProducts as $plantAuditProduct) {
    $issues = plant_seo_audit($plantAuditProduct);
    $descriptionKey = mb_strtolower(preg_replace('/\s+/u', ' ', trim(strip_tags((string)$plantAuditProduct['description']))));
    if (($plantDescriptionCounts[$descriptionKey] ?? 0) > 1) $issues[] = 'Cette description est identique à celle d’une autre plante : personnalisez-la.';
    if ($issues) $plantAuditRows[] = ['product'=>$plantAuditProduct, 'issues'=>$issues];
}
?>
<section class="fs" aria-labelledby="plant-audit-title">
  <h2 id="plant-audit-title">Fiches plantes à compléter</h2>
  <p class="hint">Ces conseils repèrent des informations manquantes ou peu détaillées. Ils ne constituent pas une note Google. Renseignez uniquement les caractéristiques vérifiées du produit.</p>
  <?php if (!$plantAuditRows): ?><p>Aucune information manquante détectée sur les critères vérifiés. Vous pouvez toujours préciser les conseils d’utilisation et les éléments inclus dans chaque fiche.</p>
  <?php else: ?>
  <div class="atable-wrap"><table class="at"><thead><tr><th>Plante</th><th>À améliorer</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($plantAuditRows as $row): $plant = $row['product']; ?>
    <tr><td><?= e($plant['name']) ?></td><td><ul><?php foreach ($row['issues'] as $issue): ?><li><?= e($issue) ?></li><?php endforeach; ?></ul></td><td><a class="sbtn" href="<?= e(url('admin/produit.php?id=' . (int)$plant['id'])) ?>">Compléter la fiche</a></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>
</section>
