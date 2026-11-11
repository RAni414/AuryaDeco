<?php
/**
 * AuryaDeco : préparation de la base au démarrage du conteneur.
 *
 * - attend que MySQL/MariaDB réponde (DB_WAIT_SECONDS, 60 s par défaut) ;
 * - base vide : crée les tables et le compte administrateur (ADMIN_USER / ADMIN_PASSWORD) ;
 * - base existante : applique seulement les migrations. Les données ne sont jamais effacées.
 */
declare(strict_types=1);

$app = '/var/www/html';
require $app . '/includes/config-loader.php';
require $app . '/includes/installer.php';
require $app . '/includes/migrate.php';

function say(string $message): void
{
    fwrite(STDERR, '[auryadeco] ' . $message . PHP_EOL);
}

$config = app_env_config();
if ($config === null) {
    say('DB_HOST is not set. Configure the DB_* environment variables (see DEPLOYMENT.md).');
    exit(1);
}

$wait = max(0, (int)(getenv('DB_WAIT_SECONDS') ?: 60));
$deadline = time() + $wait;
while (true) {
    try {
        $pdo = app_pdo($config);
        break;
    } catch (PDOException $ex) {
        if (time() >= $deadline) {
            say('Database unreachable after ' . $wait . 's: ' . $ex->getMessage());
            exit(1);
        }
        say('Waiting for the database at ' . $config['db_host'] . ':' . $config['db_port'] . '...');
        sleep(3);
    }
}

if (!shop_is_installed($pdo)) {
    $user = trim((string)getenv('ADMIN_USER'));
    $pass = (string)getenv('ADMIN_PASSWORD');
    $errors = admin_credentials_errors($user, $pass);
    if ($errors) {
        say('Empty database: set ADMIN_USER and ADMIN_PASSWORD to create the first admin account. ' . implode(' ', $errors));
        exit(1);
    }
    install_shop($pdo, $user, $pass, (string)(getenv('SHOP_NAME') ?: 'AuryaDeco'), env_flag('SEED_DEMO_PRODUCTS'));
    say("Shop installed. Admin account '" . $user . "' created.");
}

run_migrations($pdo);
say('Database ready (schema version ' . DB_VERSION . ').');
