<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/favorites-service.php';
if (empty($CONFIG['base_url'])) $GLOBALS['PUBLIC_BASE_OVERRIDE'] = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/includes/favorites-api.php'))), '/');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); http_response_code(405); throw new RuntimeException('Méthode non autorisée.'); }
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 8192) { http_response_code(413); throw new RuntimeException('La liste est trop volumineuse.'); }
    $data = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($data)) throw new InvalidArgumentException('Demande invalide.');
    if (empty($_SESSION['csrf']) || !is_string($data['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $data['csrf'])) {
        http_response_code(403); throw new RuntimeException('Votre session a expiré. Rechargez la page.');
    }
    $ids = favorite_ids($data['ids'] ?? null);
    $products = favorite_products($ids);
    $valid = array_map('intval', array_column($products, 'id'));
    echo json_encode(['ids' => $valid, 'removed' => count($ids) - count($valid),
        'html' => !empty($data['render']) ? implode('', array_map('favorite_product_card', $products)) : '',
        'csrf' => csrf_token()], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (InvalidArgumentException $e) {
    http_response_code(400); echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (RuntimeException $e) {
    if (http_response_code() < 400) http_response_code(500);
    echo json_encode(['error' => http_response_code() === 500 ? 'Les favoris sont temporairement indisponibles. Réessayez.' : $e->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['error' => 'Les favoris sont temporairement indisponibles. Réessayez.'], JSON_UNESCAPED_UNICODE);
}
