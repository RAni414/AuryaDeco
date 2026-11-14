<?php
require __DIR__ . '/../includes/admin.php';
require_admin();
require_once __DIR__ . '/../includes/navigation.php';
require_once __DIR__ . '/../includes/catalogue-categories.php';
$id = (int)($_GET['id'] ?? 0);
$all = catalogue_categories();
$category = ['id' => 0, 'name' => '', 'parent_key' => null, 'position' => 1, 'active' => 1];
if ($id) {
    $found = array_values(array_filter($all, fn($c) => (int)$c['id'] === $id));
    if (!$found) { flash('Catégorie introuvable.', 'err'); redirect('admin/categories.php'); }
    $category = $found[0];
}
$saved = json_decode(settings()['navigation_menu'] ?? '{}', true);
$base = navigation_merge(is_array($saved) ? $saved : []);
$parents = catalogue_parent_options($all, $base, $id);
$error = '';
if (is_post()) {
    csrf_check();
    $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $parent = is_string($_POST['parent'] ?? null) ? $_POST['parent'] : '';
    $rank = is_scalar($_POST['position'] ?? null) ? filter_var($_POST['position'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 999]]) : false;
    $category = array_merge($category, ['name' => $name, 'parent_key' => $parent ?: null, 'position' => $rank === false ? 1 : $rank, 'active' => !empty($_POST['active']) ? 1 : 0]);
    if ($name === '' || mb_strlen($name) > 80 || $rank === false || ($parent !== '' && !isset($parents[$parent]))) {
        $error = 'Vérifiez le nom, l’ordre et la catégorie parente. Une sous-catégorie ne peut pas contenir une autre sous-catégorie.';
    } else {
        if ($id) db()->prepare('UPDATE catalogue_categories SET name=?, parent_key=?, position=?, active=? WHERE id=?')->execute([$name, $parent ?: null, $rank, $category['active'], $id]);
        else db()->prepare('INSERT INTO catalogue_categories (name,parent_key,position,active) VALUES (?,?,?,?)')->execute([$name, $parent ?: null, $rank, $category['active']]);
        flash('Catégorie enregistrée. Affectez-lui des produits depuis leurs fiches.');
        redirect('admin/categories.php');
    }
}
admin_header($id ? 'Modifier une catégorie' : 'Ajouter une catégorie', 'categories');
?>
<h1><?= $id ? 'Modifier une catégorie' : 'Ajouter une catégorie ou sous-catégorie' ?></h1>
<?php if ($error): ?><p role="alert"><?= e($error) ?></p><?php endif; ?>
<form method="post" class="fs narrow">
  <?= csrf_field() ?>
  <label class="field">Nom<input name="name" value="<?= e($category['name']) ?>" maxlength="80" required></label>
  <label class="field">Emplacement<select name="parent"><option value="">Nouvelle catégorie principale</option><?php foreach ($parents as $key => $label): ?><option value="<?= e($key) ?>" <?= $category['parent_key'] === $key ? 'selected' : '' ?>>Sous-catégorie de <?= e($label) ?></option><?php endforeach; ?></select></label>
  <?php if (!$parents): ?><p class="hint">Cette catégorie contient des sous-catégories et doit rester principale.</p><?php endif; ?>
  <label class="field">Ordre dans le menu<input type="number" name="position" min="0" max="999" value="<?= (int)$category['position'] ?>" required></label>
  <label class="check"><input type="checkbox" name="active" value="1" <?= $category['active'] ? 'checked' : '' ?>> Visible sur le site</label>
  <p class="hint">Ajoutez ensuite des produits dans cette catégorie depuis Admin → Produits → Modifier. Un produit peut appartenir à plusieurs catégories.</p>
  <button class="btn" type="submit">Enregistrer la catégorie</button>
  <a class="sbtn" href="<?= e(url('admin/categories.php')) ?>">Retour</a>
</form>
<?php admin_footer();
