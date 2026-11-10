<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require __DIR__ . '/deco-service.php';
require __DIR__ . '/deco-catalogue.php';
require __DIR__ . '/deco-simulation.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if (($_SESSION['deco_provider'] ?? '') !== 'gemini') {
    unset($_SESSION['deco_history'], $_SESSION['gemini_history'], $_SESSION['deco_ui'], $_SESSION['deco_last_ids'], $_SESSION['deco_needs'], $_SESSION['deco_recommended']);
    $_SESSION['deco_provider'] = 'gemini';
}
$status = 200;
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $catalogue = db()->query("SELECT p.id,p.name,v.id AS variant_id,v.height,v.label,v.stock FROM products p LEFT JOIN variants v ON v.product_id=p.id WHERE p.active=1 AND p.kind='plante' ORDER BY p.position,p.name,v.position,v.id")->fetchAll();
        $result = ['demo' => deco_demo(), 'provider' => 'gemini', 'configured' => !deco_demo(), 'simulation' => false, 'csrf' => csrf_token(), 'plants' => $catalogue,
            'messages' => $_SESSION['deco_ui'] ?? [], 'cards' => deco_current_cards(), 'needs' => $_SESSION['deco_needs'] ?? [], 'cart_url' => deco_url('panier.php')];
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 6 * 1024 * 1024) throw new DecoError('La demande est trop lourde. Photo : 5 Mo maximum.', 413);
        $token = deco_text('csrf', 100);
        if (empty($_SESSION['csrf']) || $token === '' || !hash_equals($_SESSION['csrf'], $token)) throw new DecoError('La session a expiré. Fermez puis rouvrez l’assistant.', 403);
        $action = deco_text('action', 20);
        if ($action === 'reset') {
            unset($_SESSION['deco_history'], $_SESSION['gemini_history'], $_SESSION['deco_ui'], $_SESSION['deco_last_ids'], $_SESSION['deco_needs'], $_SESSION['deco_recommended']);
            $result = ['ok' => true];
        } elseif ($action === 'cart') {
            $result = deco_cart_add();
        } elseif ($action === 'simulate') {
            if (deco_text('consent', 5) !== '1') throw new DecoError('Acceptez l’envoi de la photo et de la référence produit au service de simulation.');
            if (!deco_simulation_enabled()) throw new DecoError('Prévisualisation indisponible : le service de simulation n’est pas configuré.', 503);
            $row = deco_variant_row(deco_text('variant', 20));
            if (!$row || $row['kind'] !== 'plante') throw new DecoError('Cette plante n’est plus proposée.', 404);
            if (!deco_product_image($row['image_file'] ?? null)) throw new DecoError('Prévisualisation indisponible : ajoutez une vraie photo à ce produit.', 422);
            $placement = deco_text('placement', 500);
            if ($placement === '') throw new DecoError('Décrivez l’emplacement souhaité pour la simulation.');
            $photo = deco_photo(false);
            if ($photo === null) throw new DecoError('Joignez la photo de votre pièce pour créer la simulation.');
            deco_rate_limit('simulation');
            @set_time_limit(115);
            $result = ['image' => deco_simulation($row, $photo, $placement), 'label' => 'Simulation indicative : dimensions et rendu à confirmer'];
        } elseif ($action === '' || $action === 'analyse') {
            @set_time_limit(90);
            if (deco_demo()) throw new DecoError('Gemini n’est pas configuré : définissez GEMINI_API_KEY dans le processus PHP. Aucune analyse IA n’a été effectuée.', 503);
            if (deco_text('consent', 5) !== '1') throw new DecoError('Veuillez accepter le traitement de votre demande avant l’envoi.');
            $kind = 'artificielle';
            $id = deco_text('plant', 20); $variant = deco_text('variant', 20); $plant = [];
            if ($id !== '' || $variant !== '') {
                $plant = deco_variant_row($variant);
                if (!$plant || (string)$plant['id'] !== $id || $plant['kind'] !== 'plante') throw new DecoError('Choisissez une plante et une taille proposées dans le catalogue.');
            }
            $message = deco_text('message'); $dimensions = deco_text('dimensions', 300); $light = '';
            if ($message === '') throw new DecoError('Écrivez votre question avant de l’envoyer.');
            $needs = ['room' => deco_text('room', 100), 'style' => deco_text('style', 100), 'budget_tnd' => deco_number('budget'),
                'max_height_cm' => deco_number('max_height'), 'kind' => $kind, 'selection' => $id !== '' ? $id . ':' . $variant : '', 'dimensions' => $dimensions, 'light' => $light];
            $rows = deco_candidate_rows($needs['budget_tnd'], $needs['max_height_cm'], $needs['style'], $kind, $plant ?: null);
            $photo = deco_photo(false);
            $hasPhoto = isset($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
            deco_rate_limit();
            unset($_SESSION['deco_recommended']);
            $reply = deco_reply($plant ?: [], $kind, $message, $dimensions, $light, $photo, $hasPhoto, $needs, $rows);
            $ids = $_SESSION['deco_recommended'] ?? [];
            // Re-read after the network call: price/stock may have changed meanwhile.
            $freshRows = [];
            foreach ($ids as $candidateId) {
                $current = deco_variant_row((string)$candidateId);
                if (!$current || $current['kind'] !== 'plante' || ($current['stock'] !== null && (int)$current['stock'] <= 0)) continue;
                if ($needs['budget_tnd'] !== null && (float)$current['price'] > $needs['budget_tnd']) continue;
                if ($needs['max_height_cm'] !== null && ($current['height'] === null || (float)$current['height'] > $needs['max_height_cm'])) continue;
                $freshRows[] = $current;
            }
            $cards = deco_cards($freshRows, $ids, $needs['budget_tnd']);
            $catalogueNote = ($cards ? 'Plantes artificielles du catalogue correspondant aux critères chiffrés. Le style et l’encombrement restent à confirmer. Pots vendus séparément.'
                    : 'Aucun produit proposé avec ces critères. Essayez un autre budget, une autre hauteur ou laissez Aurya choisir la plante.');
            $_SESSION['deco_needs'] = $needs; $_SESSION['deco_last_ids'] = array_column($cards, 'variant_id');
            $ui = $_SESSION['deco_ui'] ?? [];
            $ui[] = ['role' => 'user', 'text' => $message . ($hasPhoto ? "\n[Photo jointe ; non conservée]" : '')];
            $ui[] = ['role' => 'assistant', 'text' => mb_substr($reply, 0, 6000), 'demo' => deco_demo()];
            $_SESSION['deco_ui'] = array_slice($ui, -8);
            $result = ['demo' => deco_demo(), 'reply' => $reply, 'cards' => $cards, 'catalogue_note' => $catalogueNote];
        } else { throw new DecoError('Action inconnue.'); }
    } else { header('Allow: GET, POST'); throw new DecoError('Méthode non autorisée.', 405); }
} catch (DecoError $e) { $status = $e->status; $result = ['error' => $e->getMessage()]; }
catch (Throwable $e) { $status = 500; $result = ['error' => 'L’assistant est temporairement indisponible. Réessayez plus tard.']; }
finally {
    foreach ($_FILES as $upload) {
        $tmp = $upload['tmp_name'] ?? null;
        if (is_string($tmp) && is_uploaded_file($tmp)) @unlink($tmp);
    }
}
http_response_code($status);
if ($status === 429) header('Retry-After: 3600');
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
