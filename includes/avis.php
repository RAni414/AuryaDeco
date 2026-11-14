<?php
require __DIR__ . '/bootstrap.php';
if (empty($CONFIG['base_url'])) $GLOBALS['PUBLIC_BASE_OVERRIDE'] = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/includes/avis.php'))), '/');
require_once __DIR__ . '/review-service.php';
$reviewValues = ['name' => '', 'city' => '', 'rating' => '', 'comment' => ''];
$reviewError = '';
if (is_post()) {
    if (empty($_SESSION['csrf']) || !is_string($_POST['csrf'] ?? null)) {
        http_response_code(400);
        exit('La session a expiré. Rechargez le formulaire avant de réessayer.');
    }
    csrf_check();
    try {
        foreach ($reviewValues as $key => $value) if (is_string($_POST[$key] ?? null)) $reviewValues[$key] = $_POST[$key];
        $review = customer_review_values($_POST);
        if (!is_string($_POST['website'] ?? '') || trim($_POST['website'] ?? '') !== '') throw new InvalidArgumentException('Impossible d’envoyer cet avis. Réessayez.');
        if (time() - (int)($_SESSION['last_review_at'] ?? 0) < 60) {
            http_response_code(429);
            throw new InvalidArgumentException('Votre avis a déjà été envoyé. Patientez une minute avant un nouvel envoi.');
        }
        save_customer_review(db(), $review);
        $_SESSION['last_review_at'] = time();
        $_SESSION['review_success'] = 'Merci ! Votre avis a été envoyé et sera publié après validation par notre équipe.';
        header('Location: ' . url('index.php#laisser-avis'), true, 303);
        exit;
    } catch (InvalidArgumentException $e) {
        $reviewError = $e->getMessage();
    } catch (Throwable $e) {
        error_log('Customer review submission: ' . $e->getMessage());
        http_response_code(503);
        $reviewError = 'L’envoi est momentanément indisponible. Votre texte est conservé ci-dessous ; réessayez plus tard.';
    }
}
$pageTitle = 'Donner mon avis';
require __DIR__ . '/header.php';
require __DIR__ . '/review-form.php';
require __DIR__ . '/footer.php';
