<?php
/**
 * AuryaDeco : démarrage commun à toutes les pages.
 */
declare(strict_types=1);

define('APP_DIR', dirname(__DIR__));
define('UPLOAD_DIR', APP_DIR . '/uploads');

$configFile = APP_DIR . '/config.php';
if (!is_file($configFile)) {
    $installUrl = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false ? '../' : '') . 'install.php';
    header('Location: ' . $installUrl);
    exit;
}
$CONFIG = require $configFile;

date_default_timezone_set($CONFIG['timezone'] ?? 'Africa/Tunis');
if (!empty($CONFIG['debug'])) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
// XAMPP's global session directory may be unavailable to the local PHP server.
// Keep local sessions outside the public site and isolated per installation.
if (PHP_SAPI === 'cli-server' && ini_get('session.save_handler') === 'files') {
    $sessionDir = trim((string)getenv('AURYA_SESSION_DIR'));
    if ($sessionDir === '') $sessionDir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR
        . 'auryadeco-sessions-' . substr(hash('sha256', APP_DIR), 0, 16);
    if (!is_dir($sessionDir) && !@mkdir($sessionDir, 0700, true) && !is_dir($sessionDir)) {
        throw new RuntimeException('Impossible de créer le dossier temporaire des sessions.');
    }
    if (!is_writable($sessionDir)) {
        throw new RuntimeException('Le dossier temporaire des sessions doit être accessible en écriture.');
    }
    session_save_path($sessionDir);
}
session_name('auryadeco');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $secure]);
session_start();

require __DIR__ . '/helpers.php';

try {
    $pdo = new PDO(
        'mysql:host=' . $CONFIG['db_host'] . ';port=' . ($CONFIG['db_port'] ?? 3306) . ';dbname=' . $CONFIG['db_name'] . ';charset=utf8mb4',
        $CONFIG['db_user'],
        $CONFIG['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
} catch (PDOException $ex) {
    http_response_code(500);
    echo '<!doctype html><meta charset="utf-8"><title>Base de données indisponible</title><body style="font-family:sans-serif;max-width:600px;margin:60px auto">';
    echo '<h1>Connexion à la base de données impossible</h1><p>Vérifiez les informations de connexion dans le fichier <code>config.php</code> (hôte, nom de la base, utilisateur, mot de passe) et que le serveur MySQL est démarré.</p>';
    if (!empty($CONFIG['debug'])) {
        echo '<pre>' . htmlspecialchars($ex->getMessage()) . '</pre>';
    }
    exit;
}

require __DIR__ . '/migrate.php';
run_migrations($pdo);

function db(): PDO
{
    global $pdo;
    return $pdo;
}
