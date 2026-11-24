<?php
require __DIR__ . '/../includes/admin.php';
require_admin();
require_once __DIR__ . '/../includes/review-service.php';

$id = (int)($_GET['id'] ?? 0);
$r = ['id' => 0, 'name' => '', 'city' => '', 'rating' => 5, 'comment' => '', 'photo' => null, 'video' => null, 'active' => 1, 'position' => 0];
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
    $oldVideo = $r['video'] ?? null;
    $newVideo = null;
    if (!$errors) {
        try {
            $media = customer_review_upload_choice($_FILES);
            if ($media['photo']) {
                $newFile = customer_review_photo($media['photo']);
                $r['photo'] = $newFile;
                $r['video'] = null;
            } elseif ($media['video']) {
                $newVideo = customer_review_video($media['video']);
                $r['video'] = $newVideo;
                $r['photo'] = null;
            } else {
                if (!empty($_POST['del_photo'])) $r['photo'] = null;
                if (!empty($_POST['del_video'])) $r['video'] = null;
            }
            if ($id) {
                db()->prepare('UPDATE reviews SET name=?, city=?, rating=?, comment=?, photo=?, video=?, active=? WHERE id=?')
                    ->execute([$r['name'], $r['city'] ?: null, $r['rating'], $r['comment'], $r['photo'], $r['video'], $r['active'], $id]);
            } else {
                // Un nouvel avis s'affiche en premier.
                db()->exec('UPDATE reviews SET position = position + 1');
                db()->prepare('INSERT INTO reviews (name, city, rating, comment, photo, video, active, position) VALUES (?,?,?,?,?,?,?,0)')
                    ->execute([$r['name'], $r['city'] ?: null, $r['rating'], $r['comment'], $r['photo'], $r['video'], $r['active']]);
                $id = (int)db()->lastInsertId();
            }
            if ($oldFile && $oldFile !== $r['photo']) @unlink(UPLOAD_DIR . '/' . basename($oldFile));
            if ($oldVideo && $oldVideo !== $r['video']) @unlink(UPLOAD_DIR . '/' . basename($oldVideo));
            flash('Avis enregistré.');
            redirect('admin/avis.php');
        } catch (Throwable $ex) {
            if ($newFile) @unlink(UPLOAD_DIR . '/' . $newFile);
            if ($newVideo) @unlink(UPLOAD_DIR . '/' . $newVideo);
            $r['video'] = $oldVideo;
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
    <fieldset class="fs"><legend>Photo ou vidéo</legend>
      <?php if (!empty($r['video'])): ?>
        <video controls playsinline preload="metadata" style="width:100%;max-width:460px;max-height:320px" src="<?= e(url('uploads/' . $r['video'])) ?>"></video>
        <label class="check"><input type="checkbox" name="del_video" value="1"> Supprimer cette vidéo</label>
      <?php endif; ?>
      <label class="field">Ajouter ou remplacer la vidéo (MP4 ou WebM, 25 Mo maximum)<input type="file" name="video" accept="video/mp4,video/webm"></label>
      <p class="hint">Une seule photo ou vidéo par avis. Un nouveau média remplace le précédent.</p>
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
<?php admin_footer();
