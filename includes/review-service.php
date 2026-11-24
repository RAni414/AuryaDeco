<?php
require_once __DIR__ . '/review-media.php';
function customer_review_values(array $input): array
{
    $values = [];
    foreach (['name', 'city', 'comment'] as $key) {
        if (!is_string($input[$key] ?? '')) throw new InvalidArgumentException('Vérifiez les informations saisies.');
        $values[$key] = trim($input[$key] ?? '');
    }
    $rating = $input['rating'] ?? null;
    $values['rating'] = is_scalar($rating) ? filter_var($rating, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]) : false;
    if ($values['name'] === '' || mb_strlen($values['name']) > 120) throw new InvalidArgumentException('Indiquez votre nom (120 caractères maximum).');
    if (mb_strlen($values['city']) > 80) throw new InvalidArgumentException('La ville doit contenir au maximum 80 caractères.');
    if ($values['comment'] === '' || mb_strlen($values['comment']) > 800) throw new InvalidArgumentException('Écrivez votre avis (800 caractères maximum).');
    if ($values['rating'] === false) throw new InvalidArgumentException('Choisissez une note de 1 à 5.');
    return $values;
}

function customer_review_photo(?array $file): ?string
{
    if ($file === null) return null;
    if (!is_int($file['error'] ?? null)) throw new InvalidArgumentException('La photo envoyée est invalide.');
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) throw new InvalidArgumentException('La photo est trop lourde. Choisissez une image de 5 Mo maximum.');
    if ($file['error'] !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_string($file['name'] ?? null) || !is_uploaded_file($file['tmp_name'])) {
        throw new InvalidArgumentException('La photo n’a pas pu être envoyée. Sélectionnez-la à nouveau.');
    }
    $size = filesize($file['tmp_name']);
    if ($size === false || $size <= 0 || $size > 5 * 1024 * 1024) throw new InvalidArgumentException('La photo doit peser au maximum 5 Mo.');
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) throw new InvalidArgumentException('Choisissez une photo JPG, PNG ou WebP.');
    if ($info[0] * $info[1] > 25000000) throw new InvalidArgumentException('Cette photo est trop grande. Choisissez une image de 25 mégapixels maximum.');
    require_once __DIR__ . '/uploads.php';
    $file['size'] = $size;
    return save_upload($file);
}

function save_customer_review(PDO $pdo, array $review, ?string $photo = null, ?string $video = null): int
{
    // Public submissions always wait for the administrator's publication.
    if ($photo !== null && $video !== null) throw new InvalidArgumentException('Choisissez une photo ou une vidéo pour cet avis.');
    $pdo->prepare('INSERT INTO reviews (name, city, rating, comment, photo, video, active, position) VALUES (?,?,?,?,?,?,0,0)')
        ->execute([$review['name'], $review['city'] ?: null, $review['rating'], $review['comment'], $photo, $video]);
    return (int)$pdo->lastInsertId();
}
