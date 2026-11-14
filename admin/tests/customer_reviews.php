<?php
session_save_path(sys_get_temp_dir());
require __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/review-service.php';
function verify_review(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS $message\n";
}
$input = ['name' => ' Test Client ', 'city' => ' Tunis ', 'rating' => '4', 'comment' => 'Très belle plante <script>test</script>', 'active' => 1];
$values = customer_review_values($input);
verify_review($values['name'] === 'Test Client' && $values['city'] === 'Tunis' && $values['rating'] === 4, 'customer input trimmed and rating validated');
foreach ([['rating' => 6], ['rating' => []], ['comment' => ''], ['comment' => str_repeat('a', 801)], ['name' => []]] as $change) {
    $rejected = false;
    try { customer_review_values(array_merge($input, $change)); } catch (InvalidArgumentException $e) { $rejected = true; }
    verify_review($rejected, 'invalid review rejected');
}
$pdo = db();
$pdo->beginTransaction();
try {
    $id = save_customer_review($pdo, $values);
    $query = $pdo->prepare('SELECT * FROM reviews WHERE id=?');
    $query->execute([$id]);
    $review = $query->fetch();
    verify_review($review && (int)$review['active'] === 0, 'public submission saved for moderation despite active input');
    verify_review(!in_array($id, array_map('intval', array_column(load_reviews(), 'id')), true), 'pending review excluded from public reviews');
    $pdo->prepare('UPDATE reviews SET active=1 WHERE id=?')->execute([$id]);
    verify_review(in_array($id, array_map('intval', array_column(load_reviews(), 'id')), true), 'published review becomes visible');
    $card = review_card($review);
    verify_review(!str_contains($card, '<script>') && str_contains($card, '&lt;script&gt;'), 'review content escaped when rendered');
    $pdo->prepare('DELETE FROM reviews WHERE id=?')->execute([$id]);
    $query->execute([$id]);
    verify_review(!$query->fetch(), 'administrator can delete a submitted review');
} finally {
    $pdo->rollBack();
}
