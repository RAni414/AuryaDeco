<fieldset class="fs only-pot"><legend>Palette de couleurs et photos</legend>
  <?php if ($p['id']): ?>
    <p class="hint">Créez les couleurs et ajoutez leurs photos, puis associez chaque taille à une couleur dans le tableau ci-dessous. Une ligne représente une taille et une couleur, avec son prix et son stock. Enregistrez vos modifications avant d’ouvrir la gestion des couleurs.</p>
    <a class="sbtn" href="<?= e(url('admin/couleurs-pot.php?produit=' . (int)$p['id'])) ?>">Gérer les couleurs et leurs photos</a>
  <?php else: ?>
    <p class="hint">Enregistrez d’abord ce pot, puis ouvrez « Gérer les couleurs et leurs photos » pour créer sa palette.</p>
  <?php endif; ?>
</fieldset>
