<?php
/**
 * AuryaDeco : vérification de santé (Docker HEALTHCHECK, EasyPanel).
 * GET /health -> 200 {"status":"ok"} si PHP et la base répondent, sinon 503.
 */
declare(strict_types=1);
require __DIR__ . '/includes/config-loader.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$config = app_config(__DIR__);
if ($config === null) {
    http_response_code(503);
    echo '{"status":"not_configured"}';
    exit;
}
try {
    app_pdo($config)->query('SELECT 1');
    echo '{"status":"ok"}';
} catch (Throwable $ex) {
    error_log('AuryaDeco health: database unavailable: ' . $ex->getMessage());
    http_response_code(503);
    echo '{"status":"database_unavailable"}';
}
