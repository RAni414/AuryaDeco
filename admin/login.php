<?php
require __DIR__ . '/../includes/admin.php';
if (current_admin()) {
    redirect('admin/index.php');
}
$error = '';
if (is_post()) {
    csrf_check();
    $_SESSION['login_fails'] = $_SESSION['login_fails'] ?? 0;
    if ($_SESSION['login_fails'] >= 5 && time() - ($_SESSION['login_last'] ?? 0) < 300) {
        $error = 'Trop de tentatives. Attendez 5 minutes avant de réessayer.';
    } else {
        $st = db()->prepare('SELECT * FROM admins WHERE username = ?');
        $st->execute([trim((string)($_POST['username'] ?? ''))]);
        $a = $st->fetch();
        if ($a && password_verify((string)($_POST['password'] ?? ''), $a['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$a['id'];
            unset($_SESSION['login_fails']);
            if (password_needs_rehash($a['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash((string)$_POST['password'], PASSWORD_DEFAULT), $a['id']]);
            }
            redirect('admin/index.php');
        }
        $_SESSION['login_fails']++;
        $_SESSION['login_last'] = time();
        usleep(600000);
        $error = 'Identifiant ou mot de passe incorrect.';
    }
}
admin_header('Connexion');
?>
<div class="login card">
  <h1>Connexion</h1>
  <?php if ($error): ?><div class="flash flash-err" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label class="field">Identifiant<input name="username" required autocomplete="username" autofocus></label>
    <label class="field">Mot de passe<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn block" type="submit">Se connecter</button>
  </form>
</div>
<?php admin_footer();
