<?php
/**
 * AuryaDeco : lecture de la configuration.
 *
 * 1. Variables d'environnement (Docker / EasyPanel) : utilisées dès que DB_HOST est défini.
 * 2. Sinon, fichier config.php créé par install.php (hébergement classique).
 */
declare(strict_types=1);

function env_flag(string $name, bool $default = false): bool
{
    $raw = getenv($name);
    if ($raw === false || trim($raw) === '') {
        return $default;
    }
    return in_array(strtolower(trim($raw)), ['1', 'true', 'yes', 'on'], true);
}

/** Configuration issue de l'environnement, ou null si DB_HOST n'est pas défini. */
function app_env_config(): ?array
{
    $host = trim((string)getenv('DB_HOST'));
    if ($host === '') {
        return null;
    }
    return [
        'db_host'  => $host,
        'db_port'  => (int)(getenv('DB_PORT') ?: 3306),
        'db_name'  => (string)(getenv('DB_NAME') ?: 'auryadeco'),
        'db_user'  => (string)(getenv('DB_USER') ?: 'auryadeco'),
        'db_pass'  => (string)(getenv('DB_PASSWORD') ?: ''),
        'base_url' => rtrim((string)(getenv('BASE_URL') ?: ''), '/'),
        'timezone' => (string)(getenv('APP_TIMEZONE') ?: 'Africa/Tunis'),
        'debug'    => env_flag('APP_DEBUG'),
        'from_env' => true,
    ];
}

/** Configuration complète, ou null si la boutique n'est pas encore configurée. */
function app_config(string $appDir): ?array
{
    $env = app_env_config();
    if ($env !== null) {
        return $env;
    }
    $file = $appDir . '/config.php';
    return is_file($file) ? require $file : null;
}

/** Connexion PDO à la base de la boutique. */
function app_pdo(array $config, bool $withDatabase = true): PDO
{
    $dsn = 'mysql:host=' . $config['db_host'] . ';port=' . (int)($config['db_port'] ?? 3306)
        . ($withDatabase ? ';dbname=' . $config['db_name'] : '') . ';charset=utf8mb4';
    return new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);
}
