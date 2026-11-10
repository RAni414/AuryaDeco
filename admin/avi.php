<?php
require __DIR__ . '/../includes/admin.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$r = ['id' => 0, 'name' => '', 'city' => '', 'rating' => 5, 'comment' => '', 'photo' => null, 'active' => 1, 'position' => 0];
if ($id) {
    $st = db()->prepare('SELECT * FROM reviews WHERE id = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    if (!$r) { flash("Cet avis n'existe pas.", 'err'); redirect('admin/avis.php'); }
}
$errors = [];

if (is_post()) {
    csrf_check();
    $r['name'] = trim((string)($_POST['name'] ?? ''));
    $r['city'] = trim((string)($_POST['city'] ?? ''));
    $r['rating'] = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $r['comment'] = trim((string)($_POST['comment'] ?? ''));
    $r['active'] = !empty($_POST['active']) ? 1 : 0;
    if ($r['name'] === '') $errors[] = 'Indiquez le nom du client.';
    if (mb_strlen($r['name']) > 120 || mb_strlen($r['city']) > 80) $errors[] = 'Le nom ou la ville est trop long.';
    if ($r['comment'] === '') $errors[] = 'Écrivez le commentaire du client.';
    if (mb_strlen($r['comment']) > 800) $errors[] = 'Le commentaire est trop long (800 caractères maximum).';

    $newFile = null;
    $oldFile = $r['photo'];
    if (!$errors) {
        try {
            if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $newFile = save_upload(['name' => $_FILES['photo']['name'], 'tmp_name' => $_FILES['photo']['tmp_name'], 'error' => $_FILES['photo']['error'], 'size' => $_FILES['photo']['size']]);
                $r['photo'] = $newFile;
            } elseif (!empty($_POST['del_photo'])) {
                $r['photo'] = null;
            }
            if ($id) {
                db()->prepare('UPDATE reviews SET name=?, city=?, rating=?, comment=?, photo=?, active=? WHERE id=?')
                    ->execute([$r['name'], $r['city'] ?: null, $r['rating'], $r['comment'], $r['photo'], $r['active'], $id]);
            } else {
                // Un nouvel avis s'affiche en premier.
                db()->exec('UPDATE reviews SET position = position + 1');
                db()->prepare('INSERT INTO reviews (name, city, rating, comment, photo, active, position) VALUES (?,?,?,?,?,?,0)')
                    ->execute([$r['name'], $r['city'] ?: null, $r['rating'], $r['comment'], $r['photo'], $r['active']]);
                $id = (int)db()->lastInsertId();
            }
            if ($oldFile && $oldFile !== $r['photo']) @unlink(UPLOAD_DIR . '/' . basename($oldFile));
            flash('Avis enregistré.');
            redirect('admin/avis.php');
        } catch (Throwable $ex) {
            if ($newFile) @unlink(UPLOAD_DIR . '/' . $newFile);
            $r['photo'] = $oldFile;
            $errors[] = $ex instanceof RuntimeException ? $ex->getMessage() : "L'enregistrement a échoué : " . $ex->getMessage();
        }
    }
}
admin_header($r['id'] ? "Modifier l'avis" : 'Nouvel avis', 'avis');
?>
<div class="crumbs"><a href="<?= e(url('admin/avis.php')) ?>">Avis clients</a> / <?= $id ? e($r['name']) : 'Nouvel avis' ?></div>
<h1><?= $id ? "Modifier l'avis" : 'Ajouter un avis' ?></h1>
<?php foreach ($errors as $err): ?><div class="flash flash-err" role="alert"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="editor">
  <?= csrf_field() ?>
  <div>
    <fieldset class="fs"><legend>Le client</legend>
      <div class="row">
        <label class="field">Nom du client<input name="name" value="<?= e($r['name']) ?>" required maxlength="120" placeholder="ex. Sarra B."></label>
        <label class="field">Ville (facultatif)<input name="city" value="<?= e($r['city']) ?>" maxlength="80" placeholder="ex. Sousse"></label>
        <label class="field">Note<select name="rating"><?php for ($n = 5; $n >= 1; $n--): ?><option value="<?= $n ?>" <?= (int)$r['rating'] === $n ? 'selected' : '' ?>><?= $n ?> sur 5 <?= str_repeat('★', $n) ?></option><?php endfor; ?></select></label>
      </div>
    </fieldset>
    <fieldset class="fs"><legend>Commentaire</legend>
      <label class="field">Ce que dit le client<textarea name="comment" id="comment" rows="6" maxlength="800" required placeholder="Recopiez le message du client tel qu'il vous l'a envoyé."><?= e($r['comment']) ?></textarea></label>
      <p class="hint"><span id="count"><?= mb_strlen($r['comment']) ?></span> / 800 caractères. Ajoutez uniquement de vrais avis de vos clients.</p>
    </fieldset>
    <fieldset class="fs"><legend>Photo</legend>
      <?php if ($r['photo']): ?>
        <div class="photos" style="max-width:260px"><div class="photo"><img src="<?= e(url('uploads/' . $r['photo'])) ?>" alt=""><div class="pa"><label class="check sm danger"><input type="checkbox" name="del_photo" value="1"> Supprimer cette photo</label></div></div></div>
      <?php endif; ?>
      <label class="field"><?= $r['photo'] ? 'Remplacer la photo' : 'Ajouter une photo' ?><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" id="photoInput"></label>
      <div class="photos" id="photoPreview" style="max-width:260px"></div>
      <p class="hint">La photo du client avec sa plante, ou une capture de son message. JPG, PNG ou WebP, réduite automatiquement. Sans photo, la première lettre du nom s'affiche.</p>
    </fieldset>
  </div>
  <aside>
    <div class="fs">
      <label class="check"><input type="checkbox" name="active" value="1" <?= $r['active'] ? 'checked' : '' ?>> Afficher sur la page d'accueil</label>
      <button class="btn block" type="submit">Enregistrer l'avis</button>
      <a class="btn ghost block" style="margin-top:8px" href="<?= e(url('admin/avis.php')) ?>">Retour à la liste</a>
    </div>
  </aside>
</form>
<script>
(function(){
  var c=document.getElementById('comment'),n=document.getElementById('count');
  if(c)c.addEventListener('input',function(){n.textContent=c.value.length});
  var i=document.getElementById('photoInput'),p=document.getElementById('photoPreview');
  if(i)i.addEventListener('change',function(){p.innerHTML='';var f=i.files[0];if(!f||!/^image\//.test(f.type))return;var d=document.createElement('div');d.className='photo';var m=document.createElement('img');m.src=URL.createObjectURL(f);m.alt='';d.appendChild(m);p.appendChild(d);});
})();
</script>
<?php admin_footer();
