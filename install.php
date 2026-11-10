<?php
/**
 * Assistant d'installation AuryaDeco.
 * Supprimez ce fichier une fois l'installation terminée.
 */
declare(strict_types=1);
session_start();
$configFile = __DIR__ . '/config.php';
$done = false;
$errors = [];
$manualConfig = null;

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$v = [
    'db_host' => $_POST['db_host'] ?? 'localhost',
    'db_port' => $_POST['db_port'] ?? '3306',
    'db_name' => $_POST['db_name'] ?? 'auryadeco',
    'db_user' => $_POST['db_user'] ?? 'root',
    'db_pass' => $_POST['db_pass'] ?? '',
    'shop_name' => $_POST['shop_name'] ?? 'AuryaDeco',
    'admin_user' => $_POST['admin_user'] ?? 'admin',
    'seed' => isset($_POST['seed']) || $_SERVER['REQUEST_METHOD'] !== 'POST',
];

$already = is_file($configFile);

if (!$already && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = (string)($_POST['admin_pass'] ?? '');
    if (version_compare(PHP_VERSION, '7.4.0', '<')) $errors[] = 'PHP 7.4 ou plus récent est nécessaire (version actuelle : ' . PHP_VERSION . ').';
    if (!extension_loaded('pdo_mysql')) $errors[] = "L'extension PHP pdo_mysql n'est pas activée sur ce serveur.";
    if (trim($v['db_name']) === '' || trim($v['db_user']) === '') $errors[] = "Indiquez le nom de la base et l'utilisateur.";
    if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $v['admin_user'])) $errors[] = "L'identifiant administrateur doit faire 3 à 60 caractères (lettres, chiffres, point, tiret).";
    if (strlen($pass) < 8) $errors[] = 'Le mot de passe administrateur doit contenir au moins 8 caractères.';
    if ($pass !== ($_POST['admin_pass2'] ?? '')) $errors[] = 'Les deux mots de passe ne sont pas identiques.';

    if (!$errors) {
        try {
            $pdo = new PDO('mysql:host=' . $v['db_host'] . ';port=' . (int)$v['db_port'] . ';charset=utf8mb4', $v['db_user'], $v['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $dbq = str_replace('`', '', $v['db_name']);
            try {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbq` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (PDOException $ignored) {
                // Sur un hébergement, la base est créée depuis le panneau : on continue.
            }
            $pdo->exec("USE `$dbq`");
            foreach (array_filter(array_map('trim', explode(';', file_get_contents(__DIR__ . '/includes/schema.sql')))) as $sql) {
                $pdo->exec($sql);
            }
            $pdo->beginTransaction();
            $st = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)');
            $st->execute([$v['admin_user'], password_hash($pass, PASSWORD_DEFAULT)]);
            $st = $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)');
            $st->execute(['shop_name', trim($v['shop_name']) ?: 'AuryaDeco']);
            $st->execute(['db_version', '2']);

            if ($v['seed'] && (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() === 0) {
                $seed = require __DIR__ . '/includes/seed.php';
                $ip = $pdo->prepare('INSERT INTO products (slug, kind, name, type, shape, mat, color, open_min, open_max, tomb, featured, description, position) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $iv = $pdo->prepare('INSERT INTO variants (product_id, height, diameter, label, price, old_price, stock, position) VALUES (?,?,?,?,?,?,?,?)');
                foreach ($seed as $p) {
                    $ip->execute([$p['slug'], $p['kind'], $p['name'], $p['type'], $p['shape'], $p['mat'], $p['color'], $p['open_min'], $p['open_max'], $p['tomb'], $p['featured'], $p['description'], $p['position']]);
                    $pid = (int)$pdo->lastInsertId();
                    foreach ($p['variants'] as $i => $var) {
                        $iv->execute([$pid, $var['height'], $var['diameter'], $var['label'], $var['price'], $var['old_price'], $var['stock'], $i]);
                    }
                }
            }
            $pdo->commit();

            $config = [
                'db_host' => $v['db_host'],
                'db_port' => (int)$v['db_port'],
                'db_name' => $v['db_name'],
                'db_user' => $v['db_user'],
                'db_pass' => $v['db_pass'],
                'base_url' => '',
                'timezone' => 'Africa/Tunis',
                'debug' => false,
            ];
            $php = "<?php\n// Configuration AuryaDeco, générée par install.php le " . date('d/m/Y H:i') . "\nreturn " . var_export($config, true) . ";\n";
            if (@file_put_contents($configFile, $php) === false) {
                $manualConfig = $php;
            }
            if (!is_dir(__DIR__ . '/uploads')) @mkdir(__DIR__ . '/uploads', 0755, true);
            $done = true;
        } catch (Throwable $ex) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $msg = $ex->getMessage();
            if (stripos($msg, 'Access denied') !== false) $errors[] = "Connexion refusée : l'utilisateur ou le mot de passe de la base est incorrect.";
            elseif (stripos($msg, 'Unknown database') !== false) $errors[] = "La base « " . $v['db_name'] . " » n'existe pas. Créez-la d'abord depuis phpMyAdmin ou le panneau de votre hébergeur.";
            elseif (stripos($msg, 'getaddrinfo') !== false || stripos($msg, 'Connection refused') !== false || stripos($msg, "Can't connect") !== false) $errors[] = "Impossible de joindre le serveur MySQL « " . $v['db_host'] . " ». Vérifiez qu'il est démarré et que l'hôte est correct.";
            else $errors[] = 'Erreur : ' . $msg;
        }
    }
}
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Installation de la boutique</title>
<style>
body{margin:0;background:#F4F6F2;color:#1E2B23;font-family:"Segoe UI",system-ui,sans-serif;line-height:1.55}
.box{max-width:640px;margin:40px auto;background:#fff;border:1px solid #D8E0D5;border-radius:18px;padding:28px}
h1{font-family:Georgia,serif;font-weight:400;font-size:32px;margin:0 0 6px}
h2{font-size:17px;margin:24px 0 8px}
label{display:block;font-weight:600;font-size:14.5px;margin:12px 0 5px}
input[type=text],input[type=password],input[type=number]{width:100%;box-sizing:border-box;border:1px solid #D8E0D5;border-radius:10px;padding:11px 13px;font:inherit}
.row{display:grid;grid-template-columns:2fr 1fr;gap:12px}
.hint{color:#5B6A60;font-size:14px;margin:4px 0 0}
.btn{display:inline-block;background:#2E4A37;color:#fff;border:0;border-radius:999px;padding:13px 26px;font:inherit;font-weight:600;cursor:pointer;text-decoration:none;margin-top:20px}
.err{background:#fbeee9;border-left:3px solid #9C4A2F;color:#7a321c;padding:10px 14px;border-radius:8px;margin:14px 0}
.ok{background:#e9f1e6;border-left:3px solid #2E4A37;padding:12px 14px;border-radius:8px;margin:14px 0}
pre{background:#1E2B23;color:#eef;padding:14px;border-radius:10px;overflow:auto;font-size:13px}
</style>
</head>
<body>
<div class="box">
<?php if ($already && !$done): ?>
    <h1>Boutique déjà installée</h1>
    <p>Le fichier <code>config.php</code> existe déjà. Par sécurité, <strong>supprimez maintenant le fichier install.php</strong> du serveur.</p>
    <p>Pour réinstaller, supprimez d'abord <code>config.php</code>.</p>
    <a class="btn" href="index.php">Voir la boutique</a> <a class="btn" href="admin/">Administration</a>
<?php elseif ($done): ?>
    <h1>Installation terminée</h1>
    <div class="ok">La base de données est prête et votre compte administrateur est créé.</div>
    <?php if ($manualConfig): ?>
        <p>Le serveur n'a pas autorisé l'écriture du fichier de configuration. Créez à la racine du site un fichier nommé <code>config.php</code> avec exactement ce contenu :</p>
        <pre><?= h($manualConfig) ?></pre>
    <?php endif; ?>
    <p><strong>Important :</strong> supprimez maintenant le fichier <code>install.php</code> du serveur.</p>
    <a class="btn" href="admin/">Ouvrir l'administration</a> <a class="btn" href="index.php">Voir la boutique</a>
<?php else: ?>
    <h1>Installation de la boutique</h1>
    <p class="hint">Renseignez les informations de votre base MySQL. Sur votre PC avec Laragon ou XAMPP, l'utilisateur est <strong>root</strong> sans mot de passe. Chez un hébergeur, elles sont dans le panneau de contrôle, rubrique « Bases de données MySQL ».</p>
    <?php foreach ($errors as $err): ?><div class="err"><?= h($err) ?></div><?php endforeach; ?>
    <form method="post" autocomplete="off">
        <h2>Base de données</h2>
        <div class="row">
            <div><label for="db_host">Serveur MySQL</label><input type="text" id="db_host" name="db_host" value="<?= h($v['db_host']) ?>" required></div>
            <div><label for="db_port">Port</label><input type="number" id="db_port" name="db_port" value="<?= h($v['db_port']) ?>" required></div>
        </div>
        <label for="db_name">Nom de la base</label><input type="text" id="db_name" name="db_name" value="<?= h($v['db_name']) ?>" required>
        <label for="db_user">Utilisateur</label><input type="text" id="db_user" name="db_user" value="<?= h($v['db_user']) ?>" required>
        <label for="db_pass">Mot de passe de la base</label><input type="password" id="db_pass" name="db_pass" value="<?= h($v['db_pass']) ?>">

        <h2>Boutique et administrateur</h2>
        <label for="shop_name">Nom de la boutique</label><input type="text" id="shop_name" name="shop_name" value="<?= h($v['shop_name']) ?>" required>
        <label for="admin_user">Identifiant administrateur</label><input type="text" id="admin_user" name="admin_user" value="<?= h($v['admin_user']) ?>" required>
        <label for="admin_pass">Mot de passe administrateur</label><input type="password" id="admin_pass" name="admin_pass" minlength="8" required>
        <p class="hint">Au moins 8 caractères. Notez-le bien.</p>
        <label for="admin_pass2">Confirmez le mot de passe</label><input type="password" id="admin_pass2" name="admin_pass2" minlength="8" required>
        <label style="font-weight:500;display:flex;gap:8px;align-items:center;margin-top:16px"><input type="checkbox" name="seed" <?= $v['seed'] ? 'checked' : '' ?>> Ajouter les 21 produits d'exemple (vous pourrez les modifier ou les supprimer)</label>
        <button class="btn" type="submit">Installer la boutique</button>
    </form>
<?php endif; ?>
</div>
</body>
</html>
