<?php
require __DIR__ . '/../includes/admin.php';
require_admin();
require_once __DIR__ . '/../includes/navigation.php';
$saved = json_decode(settings()['navigation_menu'] ?? '{}', true);
$items = navigation_merge(is_array($saved) ? $saved : []);
$error = '';
if (is_post()) {
    csrf_check();
    try {
        if (!is_array($_POST['menu'] ?? null)) throw new InvalidArgumentException('Le formulaire est incomplet. Rechargez la page.');
        $values = navigation_validate($_POST['menu']);
        db()->prepare('INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v = VALUES(v)')
            ->execute(['navigation_menu', json_encode($values, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
        flash('Le menu de la boutique a été enregistré.');
        redirect('admin/categories.php');
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
}
admin_header('Catégories et sous-catégories', 'categories');
?>
<div class="shead"><h1>Catégories et sous-catégories</h1><a class="btn" href="<?= e(url('admin/categorie.php')) ?>">Ajouter une catégorie</a></div>
<?php require __DIR__ . '/../includes/admin-categories-list.php'; ?>
<h2>Rubriques du menu existant</h2>
<p class="hint">Personnalisez les noms, la visibilité et l’ordre du menu sur mobile et ordinateur. Les plus petits numéros apparaissent en premier. Les sous-catégories restent dans leur catégorie. Masquer une rubrique du menu ne masque pas ses produits dans la boutique.</p>
<p class="hint">Les destinations restent liées aux catégories existantes. Modifier les textes des tailles change leur présentation dans le menu ; les filtres de hauteur conservent leurs limites actuelles.</p>
<?php if ($error): ?><p role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post">
  <?= csrf_field() ?>
  <?php foreach ($items as $key => $item): ?>
    <fieldset class="fs"><legend><?= e($item['label']) ?></legend>
      <div class="row">
        <label class="field">Nom de la catégorie<input name="menu[<?= e($key) ?>][label]" value="<?= e($item['label']) ?>" maxlength="80" required></label>
        <label class="field">Ordre<input type="number" name="menu[<?= e($key) ?>][order]" value="<?= (int)$item['order'] ?>" min="0" max="999" required></label>
      </div>
      <label class="check"><input type="checkbox" name="menu[<?= e($key) ?>][visible]" value="1" <?= $item['visible'] ? 'checked' : '' ?>> Visible dans le menu</label>
      <?php if ($item['children']): ?>
        <div class="atable-wrap"><table class="at">
          <thead><tr><th>Sous-catégorie</th><th>Précision affichée</th><th>Ordre</th><th>Visible</th></tr></thead>
          <tbody>
          <?php foreach ($item['children'] as $id => $child): $prefix = 'menu[' . $key . '][children][' . $id . ']'; ?>
            <tr>
              <td><input name="<?= e($prefix) ?>[label]" value="<?= e($child['label']) ?>" maxlength="80" required aria-label="<?= e('Nom : ' . $child['label']) ?>"></td>
              <td><input name="<?= e($prefix) ?>[hint]" value="<?= e($child['hint']) ?>" maxlength="120" aria-label="<?= e('Précision : ' . $child['label']) ?>"></td>
              <td><input type="number" name="<?= e($prefix) ?>[order]" value="<?= (int)$child['order'] ?>" min="0" max="999" required aria-label="<?= e('Ordre : ' . $child['label']) ?>" style="width:90px"></td>
              <td><input type="checkbox" name="<?= e($prefix) ?>[visible]" value="1" <?= $child['visible'] ? 'checked' : '' ?> aria-label="<?= e('Visible : ' . $child['label']) ?>"></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php endif; ?>
    </fieldset>
  <?php endforeach; ?>
  <p><button class="btn" type="submit">Enregistrer le menu</button></p>
</form>
<?php admin_footer();
