<?php
/**
 * AuryaDeco : création des tables, du compte administrateur et des produits d'exemple.
 * Utilisé par install.php (navigateur) et docker/install-cli.php (conteneur).
 */
declare(strict_types=1);

/**
 * Vrai si la boutique est installée : tables présentes ET au moins un administrateur.
 * (Une première installation interrompue sera reprise au démarrage suivant.)
 */
function shop_is_installed(PDO $pdo): bool
{
    $n = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admins'")->fetchColumn();
    if ((int)$n === 0) {
        return false;
    }
    return (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
}

/** Erreurs de validation de l'identifiant et du mot de passe administrateur. */
function admin_credentials_errors(string $user, string $pass): array
{
    $errors = [];
    if (!preg_match('/^[A-Za-z0-9_.-]{3,60}$/', $user)) {
        $errors[] = "L'identifiant administrateur doit faire 3 à 60 caractères (lettres, chiffres, point, tiret).";
    }
    if (strlen($pass) < 8) {
        $errors[] = 'Le mot de passe administrateur doit contenir au moins 8 caractères.';
    }
    return $errors;
}

/** Crée le schéma, l'administrateur, les réglages et (option) les produits d'exemple. */
function install_shop(PDO $pdo, string $adminUser, string $adminPass, string $shopName, bool $seed): void
{
    $schema = (string)file_get_contents(__DIR__ . '/schema.sql');
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $sql) {
        $pdo->exec($sql);
    }
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)');
        $st->execute([$adminUser, password_hash($adminPass, PASSWORD_DEFAULT)]);
        $st = $pdo->prepare('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)');
        $st->execute(['shop_name', trim($shopName) ?: 'AuryaDeco']);
        $st->execute(['db_version', '2']);
        if ($seed && (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn() === 0) {
            install_seed_products($pdo);
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $ex;
    }
}

function install_seed_products(PDO $pdo): void
{
    $seed = require __DIR__ . '/seed.php';
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
