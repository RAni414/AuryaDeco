<?php
require __DIR__ . '/../includes/admin.php';
$me = require_admin();
$errors = [];
if (is_post()) {
    csrf_check();
    $act = $_POST['action'] ?? '';
    $st = db()->prepare('SELECT password_hash FROM admins WHERE id = ?');
    $st->execute([$me['id']]);
    if (!password_verify((string)($_POST['current'] ?? ''), (string)$st->fetchColumn())) {
        $errors[] = 'Votre mot de passe actuel est incorrect.';
    } elseif ($act === 'password') {
        $n = (string)($_POST['new'] ?? '');
        if (strlen($n) < 8) $errors[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        elseif ($n !== ($_POST['new2'] ?? '')) $errors[] = 'Les deux nouveaux mots de passe ne sont pas identiques.';
        else {
            db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($n, PASSWORD_DEFAULT), $me['id']]);
            flash('Mot de passe modifié.');
            redirect('admin/compte.php');
        }
    } elseif ($act === 'add') {
        $u = trim((string)($_POST['username'] ?? ''));
        $pw = (string)($_POST['userpass'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $u)) $errors[] = "L'identifiant doit faire 3 à 60 caractères (lettres, chiffres, point, tiret).";
        elseif (strlen($pw) < 8) $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        else {
            try {
                db()->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)')->execute([$u, password_hash($pw, PASSWORD_DEFAULT)]);
                flash("Accès créé pour « $u ».");
                redirect('admin/compte.php');
            } catch (PDOException $ex) {
                $errors[] = 'Cet identifiant existe déjà.';
            }
        }
    }
}
$admins = db()->query('SELECT id, username, created_at FROM admins ORDER BY id')->fetchAll();
admin_header('Mon compte', 'compte');
?>
<h1>Mon compte</h1>
<?php foreach ($errors as $err): ?><div class="flash flash-err" role="alert"><?= e($err) ?></div><?php endforeach; ?>
<div class="cols">
  <form method="post" class="fs">
    <legend>Changer mon mot de passe</legend>
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <p class="muted">Connecté en tant que <strong><?= e($me['username']) ?></strong>.</p>
    <label class="field">Mot de passe actuel<input type="password" name="current" required autocomplete="current-password"></label>
    <label class="field">Nouveau mot de passe<input type="password" name="new" minlength="8" required autocomplete="new-password"></label>
    <label class="field">Confirmez le nouveau mot de passe<input type="password" name="new2" minlength="8" required autocomplete="new-password"></label>
    <button class="btn">Changer le mot de passe</button>
  </form>
  <form method="post" class="fs">
    <legend>Ajouter un accès administrateur</legend>
    <?= csrf_field() ?><input type="hidden" name="action" value="add">
    <p class="muted">Accès existants : <?= e(implode(', ', array_column($admins, 'username'))) ?></p>
    <label class="field">Identifiant<input name="username" required></label>
    <label class="field">Mot de passe (8 caractères minimum)<input type="password" name="userpass" minlength="8" required autocomplete="new-password"></label>
    <label class="field">Votre mot de passe actuel, pour confirmer<input type="password" name="current" required autocomplete="current-password"></label>
    <button class="btn">Créer l'accès</button>
  </form>
</div>
<?php admin_footer();
