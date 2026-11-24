<?php
session_save_path(sys_get_temp_dir());
require __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/customer-account.php';
function verify_account(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS $message\n";
}
$input = ['name' => ' Test Client ', 'email' => 'TEST@example.com', 'password' => 'Long-test-password-123', 'confirm' => 'Long-test-password-123'];
$values = customer_identity($input, true);
verify_account($values['email'] === 'test@example.com' && $values['name'] === 'Test Client', 'account identity normalized');
foreach ([['email' => 'invalid'], ['password' => 'short', 'confirm' => 'short'], ['confirm' => 'different'], ['password' => []], ['password' => str_repeat('a', 73)]] as $change) {
    $rejected = false;
    try { customer_identity(array_merge($input, $change), true); } catch (InvalidArgumentException $e) { $rejected = true; }
    verify_account($rejected, 'invalid registration rejected');
}
$pdo = db();
$oldCustomer = $_SESSION['customer_id'] ?? null;
$pdo->beginTransaction();
try {
    $values['email'] = 'account-test-' . bin2hex(random_bytes(8)) . '@example.com';
    $id = customer_create($pdo, $values);
    $query = $pdo->prepare('SELECT password_hash FROM customers WHERE id=?'); $query->execute([$id]);
    $hash = $query->fetchColumn();
    verify_account($hash !== $values['password'] && password_verify($values['password'], $hash), 'password hashed before storage');
    verify_account(customer_authenticate($pdo, $values['email'], $values['password']) === $id, 'registered customer can log in');
    verify_account(customer_authenticate($pdo, $values['email'], 'incorrect') === null, 'incorrect password rejected');
    verify_account(customer_authenticate($pdo, 'missing@example.com', 'incorrect') === null, 'unknown account rejected');
    $_SESSION['customer_id'] = $id;
    $profile = customer_current();
    verify_account((int)$profile['id'] === $id && !isset($profile['password_hash']), 'profile contains only signed in customer public fields');
    unset($_SESSION['customer_id']);
    verify_account(customer_current() === null, 'anonymous session has no profile');
    verify_account(customer_auth_key('login') !== customer_auth_key('register'), 'login and signup rate limits isolated');
} finally {
    $pdo->rollBack();
    if ($oldCustomer === null) unset($_SESSION['customer_id']); else $_SESSION['customer_id'] = $oldCustomer;
}
