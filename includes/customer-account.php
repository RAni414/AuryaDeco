<?php
function customer_current(): ?array
{
    $id = (int)($_SESSION['customer_id'] ?? 0);
    if (!$id) return null;
    $query = db()->prepare('SELECT id,name,email,created_at FROM customers WHERE id=?');
    $query->execute([$id]);
    return $query->fetch() ?: null;
}

function customer_identity(array $input, bool $register): array
{
    foreach (['email', 'password'] as $key) if (!is_string($input[$key] ?? null)) throw new InvalidArgumentException('Vérifiez votre adresse e-mail et votre mot de passe.');
    $email = mb_strtolower(trim($input['email']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) throw new InvalidArgumentException('Indiquez une adresse e-mail valide.');
    $password = $input['password'];
    if ($password === '' || strlen($password) > 72) throw new InvalidArgumentException('Indiquez un mot de passe valide. S’il est très long, choisissez-en un plus court.');
    $name = '';
    if ($register) {
        $name = is_string($input['name'] ?? null) ? trim($input['name']) : '';
        if ($name === '' || mb_strlen($name) > 120) throw new InvalidArgumentException('Indiquez votre nom (120 caractères maximum).');
        if (mb_strlen($password) < 10) throw new InvalidArgumentException('Choisissez un mot de passe d’au moins 10 caractères.');
        if (!is_string($input['confirm'] ?? null) || $password !== $input['confirm']) throw new InvalidArgumentException('Les deux mots de passe doivent être identiques.');
    }
    return ['email' => $email, 'password' => $password, 'name' => $name];
}

function customer_create(PDO $pdo, array $values): int
{
    $pdo->prepare('INSERT INTO customers (name,email,password_hash) VALUES (?,?,?)')
        ->execute([$values['name'], $values['email'], password_hash($values['password'], PASSWORD_DEFAULT)]);
    return (int)$pdo->lastInsertId();
}

function customer_authenticate(PDO $pdo, string $email, string $password): ?int
{
    $query = $pdo->prepare('SELECT id,password_hash FROM customers WHERE email=?');
    $query->execute([$email]);
    $row = $query->fetch();
    $hash = $row['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    if (!password_verify($password, $hash) || !$row) return null;
    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) $pdo->prepare('UPDATE customers SET password_hash=? WHERE id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
    return (int)$row['id'];
}

function customer_auth_key(string $action): string
{
    return hash('sha256', 'customer:' . $action . ':' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

function customer_auth_limited(string $key): bool
{
    $query = db()->prepare('SELECT fails FROM login_attempts WHERE ip_hash=? AND last_at > NOW() - INTERVAL 15 MINUTE');
    $query->execute([$key]);
    return (int)$query->fetchColumn() >= 8;
}

function customer_auth_attempt(string $key): void
{
    db()->prepare('INSERT INTO login_attempts (ip_hash,fails,last_at) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE fails=IF(last_at < NOW() - INTERVAL 15 MINUTE,1,fails+1),last_at=NOW()')->execute([$key]);
}

function customer_start_session(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['customer_id'] = $id;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
