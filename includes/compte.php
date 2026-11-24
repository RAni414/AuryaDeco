<?php
require __DIR__ . '/bootstrap.php';
if (empty($CONFIG['base_url'])) $GLOBALS['PUBLIC_BASE_OVERRIDE'] = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/includes/compte.php'))), '/');
require_once __DIR__ . '/customer-account.php';
header('Cache-Control: no-store, private');
$tab = ($_GET['action'] ?? '') === 'inscription' ? 'register' : 'login';
$error = '';
$accountEmail = ''; $accountName = '';
$customer = customer_current();
if (is_post()) {
    if (empty($_SESSION['csrf']) || !is_string($_POST['csrf'] ?? null)) { http_response_code(400); exit('Rechargez la page avant de réessayer.'); }
    csrf_check();
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    if ($action === 'logout') {
        unset($_SESSION['customer_id']);
        session_regenerate_id(true);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        flash('Vous êtes déconnecté. Votre panier est conservé.');
        redirect('includes/compte.php');
    }
    if ($customer) redirect('includes/compte.php');
    $tab = $action === 'register' ? 'register' : 'login';
    $accountEmail = is_string($_POST['email'] ?? null) ? $_POST['email'] : '';
    $accountName = is_string($_POST['name'] ?? null) ? $_POST['name'] : '';
    $key = customer_auth_key($tab);
    try {
        if (!in_array($action, ['register', 'login'], true)) throw new InvalidArgumentException('Action inconnue.');
        if (customer_auth_limited($key)) { http_response_code(429); throw new InvalidArgumentException('Trop de tentatives. Réessayez dans 15 minutes.'); }
        customer_auth_attempt($key);
        if (!is_string($_POST['website'] ?? '') || trim($_POST['website'] ?? '') !== '') throw new InvalidArgumentException('Envoi refusé.');
        $values = customer_identity($_POST, $tab === 'register');
        if ($tab === 'register') $customerId = customer_create(db(), $values);
        else {
            $customerId = customer_authenticate(db(), $values['email'], $values['password']);
            if (!$customerId) throw new InvalidArgumentException('Adresse e-mail ou mot de passe incorrect.');
            db()->prepare('DELETE FROM login_attempts WHERE ip_hash=?')->execute([$key]);
        }
        customer_start_session($customerId);
        flash($tab === 'register' ? 'Bienvenue ! Votre compte a été créé.' : 'Vous êtes connecté.');
        redirect('includes/compte.php');
    } catch (InvalidArgumentException $e) { $error = $e->getMessage(); }
    catch (PDOException $e) {
        error_log('Customer account: ' . $e->getMessage());
        $error = $e->getCode() === '23000' ? 'Cette adresse possède déjà un compte. Connectez-vous.' : 'Le service est momentanément indisponible. Réessayez.';
    }
}
$pageTitle = 'Mon compte';
require __DIR__ . '/header.php';
?>
<section class="account-banner"><div class="wrap"><span>AURYADECO</span><h1>Mon compte</h1><p>Votre espace, vos envies déco.</p></div></section>
<section class="wrap account-content">
<?php if ($customer): ?>
  <div class="account-welcome"><h2>Bonjour <?= e($customer['name']) ?>.</h2><p><?= e($customer['email']) ?></p><p>Retrouvez votre panier et passez votre commande en quelques clics.</p>
    <div class="account-shortcuts"><a class="btn" href="<?= e(url('panier.php')) ?>">Mon panier</a><a class="btn ghost" href="<?= e(url('commande.php')) ?>">Passer commande</a><a class="btn ghost" href="<?= e(url('boutique.php')) ?>">Continuer mes achats</a></div>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button class="sbtn" type="submit">Se déconnecter</button></form>
  </div>
<?php else: ?>
  <div class="account-switch"><a href="<?= e(url('includes/compte.php')) ?>" <?= $tab === 'login' ? 'aria-current="page"' : '' ?>>Se connecter</a><a href="<?= e(url('includes/compte.php?action=inscription')) ?>" <?= $tab === 'register' ? 'aria-current="page"' : '' ?>>Créer un compte</a></div>
  <form method="post" class="account-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="<?= $tab ?>">
    <h2><?= $tab === 'register' ? 'Bienvenue chez AuryaDeco' : 'Heureux de vous retrouver' ?></h2>
    <?php if ($error): ?><p class="flash flash-err" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($tab === 'register'): ?><label>Votre nom<input name="name" value="<?= e($accountName) ?>" maxlength="120" autocomplete="name" required></label><?php endif; ?>
    <label>Adresse e-mail<input type="email" name="email" value="<?= e($accountEmail) ?>" maxlength="190" autocomplete="email" required></label>
    <label>Mot de passe<div class="account-password"><input type="password" name="password" autocomplete="<?= $tab === 'register' ? 'new-password' : 'current-password' ?>" <?= $tab === 'register' ? 'minlength="10"' : '' ?> required><button type="button" data-show-password aria-pressed="false">Afficher</button></div></label>
    <?php if ($tab === 'register'): ?><p class="account-hint">Au moins 10 caractères. Choisissez un mot de passe unique.</p><label>Confirmer le mot de passe<input type="password" name="confirm" autocomplete="new-password" minlength="10" required></label><p class="account-hint">Votre nom et votre e-mail sont utilisés pour gérer votre compte.</p><?php endif; ?>
    <div class="customer-review__trap" aria-hidden="true"><label>Site internet<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <button class="btn" type="submit"><?= $tab === 'register' ? 'Créer mon compte' : 'Se connecter' ?></button>
  </form>
<?php endif; ?>
</section>
<script src="<?= e(url('assets/js/customer-account.js')) ?>?v=1" defer></script>
<?php require __DIR__ . '/footer.php';
