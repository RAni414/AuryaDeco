<?php
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

function save_customer_review(PDO $pdo, array $review): int
{
    // Public submissions always wait for the administrator's publication.
    $pdo->prepare('INSERT INTO reviews (name, city, rating, comment, photo, active, position) VALUES (?,?,?,?,NULL,0,0)')
        ->execute([$review['name'], $review['city'] ?: null, $review['rating'], $review['comment']]);
    return (int)$pdo->lastInsertId();
}
