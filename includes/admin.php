<?php
/** Outils communs de l'administration. */
require __DIR__ . '/bootstrap.php';

/** Order only the selected products; each section stores its own ordered IDs. */
function collection_ordered_ids(array $ids, array $ranks): array
{
    $ordered = [];
    foreach ($ids as $id) {
        $raw = $ranks[$id] ?? null;
        $rank = is_scalar($raw) ? filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 8]]) : false;
        if ($rank === false || isset($ordered[$rank])) {
            throw new InvalidArgumentException('Donnez à chaque produit coché un ordre différent, de 1 à 8.');
        }
        $ordered[$rank] = (int)$id;
    }
    ksort($ordered, SORT_NUMERIC);
    return array_values($ordered);
}

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $a = null;
    if ($a === null) {
        $st = db()->prepare('SELECT id, username FROM admins WHERE id = ?');
        $st->execute([$_SESSION['admin_id']]);
        $a = $st->fetch() ?: null;
    }
    return $a;
}

/* ---------- Limite des tentatives de connexion par IP ---------- */
const LOGIN_MAX_FAILS = 10;
const LOGIN_BLOCK_SECONDS = 900;

function login_throttle_key(): string
{
    // Derrière Traefik, mod_remoteip remplace REMOTE_ADDR par l'IP réelle du visiteur.
    return hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

function login_ip_blocked(): bool
{
    // Comparaison faite par MySQL (même horloge que NOW() à l'écriture), indépendante du fuseau PHP.
    $st = db()->prepare('SELECT fails FROM login_attempts WHERE ip_hash = ? AND last_at > NOW() - INTERVAL ? SECOND');
    $st->execute([login_throttle_key(), LOGIN_BLOCK_SECONDS]);
    return (int)$st->fetchColumn() >= LOGIN_MAX_FAILS;
}

function login_ip_failed(): void
{
    // Le compteur repart de 1 quand la dernière erreur date de plus de LOGIN_BLOCK_SECONDS.
    db()->prepare('INSERT INTO login_attempts (ip_hash, fails, last_at) VALUES (?, 1, NOW())
        ON DUPLICATE KEY UPDATE fails = IF(last_at < NOW() - INTERVAL ? SECOND, 1, fails + 1), last_at = NOW()')
        ->execute([login_throttle_key(), LOGIN_BLOCK_SECONDS]);
    // Ménage : les anciennes entrées ne servent plus.
    db()->exec('DELETE FROM login_attempts WHERE last_at < NOW() - INTERVAL 1 DAY');
}

function login_ip_clear(): void
{
    db()->prepare('DELETE FROM login_attempts WHERE ip_hash = ?')->execute([login_throttle_key()]);
}

function require_admin(): array
{
    $a = current_admin();
    if (!$a) {
        redirect('admin/login.php');
    }
    header('X-Frame-Options: SAMEORIGIN');
    header('Cache-Control: no-store');
    return $a;
}

function admin_header(string $title, string $active = ''): void
{
    $a = current_admin();
    $new = (int)db()->query("SELECT COUNT(*) FROM orders WHERE status = 'nouvelle'")->fetchColumn();
    $nav = [
        'index' => ['admin/index.php', 'Tableau de bord'],
        'commandes' => ['admin/commandes.php', 'Commandes' . ($new ? ' <span class="pill">' . $new . '</span>' : '')],
        'produits' => ['admin/produits.php', 'Produits'],
        'categories' => ['admin/categories.php', 'Catégories et menu'],
        'accessoires' => ['admin/accessoires.php', 'Accessoires déco'],
        'collection' => ['admin/collection.php', 'Nouvelle collection'],
        'avis' => ['admin/avis.php', 'Avis clients'],
        'reglages' => ['admin/reglages.php', 'Réglages'],
        'compte' => ['admin/compte.php', 'Mon compte'],
    ];
    ?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> · Administration</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Marcellus&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=1">
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>?v=2">
</head>
<body class="admin-body">
<header class="abar">
  <div class="wrap abar-in">
    <a class="logo" href="<?= e(url('admin/index.php')) ?>"><span><?= e(setting('shop_name')) ?></span> <small>Administration</small></a>
    <?php if ($a): ?>
    <nav class="anav" aria-label="Administration">
      <?php foreach ($nav as $k => [$href, $label]): ?>
        <a href="<?= e(url($href)) ?>"<?= $k === $active ? ' aria-current="page"' : '' ?>><?= $label ?></a>
      <?php endforeach; ?>
      <a href="<?= e(url('index.php')) ?>" target="_blank" rel="noopener">Voir la boutique</a>
      <form method="post" action="<?= e(url('admin/logout.php')) ?>"><?= csrf_field() ?><button class="linkbtn">Se déconnecter</button></form>
    </nav>
    <?php endif; ?>
  </div>
</header>
<main class="wrap admin">
<?= flash() ?>
<?php
}

function admin_footer(): void
{
    ?>
</main>
<script src="<?= e(url('assets/js/art.js')) ?>?v=1"></script>
<script src="<?= e(url('assets/js/admin.js')) ?>?v=4"></script>
</body>
</html>
<?php
}

function status_badge(string $s): string
{
    return '<span class="st st-' . e($s) . '">' . e(ORDER_STATUS[$s] ?? $s) . '</span>';
}

/** Enregistre une photo envoyée : vérifie le type, la redimensionne si possible. Renvoie le nom du fichier. */
function save_upload(array $f): string
{
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE ? 'La photo « ' . $f['name'] . ' » est trop lourde.' : "L'envoi de « " . $f['name'] . ' » a échoué.');
    }
    if ($f['size'] > 10 * 1024 * 1024) {
        throw new RuntimeException('La photo « ' . $f['name'] . ' » dépasse 10 Mo.');
    }
    $info = @getimagesize($f['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) {
        throw new RuntimeException('« ' . $f['name'] . ' » n\'est pas une image JPG, PNG ou WebP.');
    }
    if (!is_dir(UPLOAD_DIR) && !@mkdir(UPLOAD_DIR, 0755, true)) {
        throw new RuntimeException("Le dossier uploads n'existe pas et ne peut pas être créé.");
    }
    $name = date('Ym') . '-' . bin2hex(random_bytes(8));
    $max = 1400;
    if (function_exists('imagecreatetruecolor')) {
        $src = $info[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($f['tmp_name']) : ($info[2] === IMAGETYPE_PNG ? @imagecreatefrompng($f['tmp_name']) : (function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($f['tmp_name']) : false));
        if ($src) {
            $w = imagesx($src);
            $h = imagesy($src);
            $sc = min(1, $max / max($w, $h));
            $nw = (int)round($w * $sc);
            $nh = (int)round($h * $sc);
            $dst = imagecreatetruecolor($nw, $nh);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            $file = $name . '.jpg';
            if (!imagejpeg($dst, UPLOAD_DIR . '/' . $file, 84)) {
                throw new RuntimeException("La photo n'a pas pu être enregistrée. Vérifiez les droits d'écriture du dossier uploads.");
            }
            imagedestroy($src);
            imagedestroy($dst);
            return $file;
        }
    }
    $file = $name . '.' . $types[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . '/' . $file)) {
        throw new RuntimeException("La photo n'a pas pu être enregistrée. Vérifiez les droits d'écriture du dossier uploads.");
    }
    return $file;
}

/** Remet en stock (+1) ou retire du stock (-1) les articles d'une commande. */
function apply_stock(int $orderId, int $sign): void
{
    $st = db()->prepare('SELECT variant_id, qty FROM order_items WHERE order_id = ? AND variant_id IS NOT NULL');
    $st->execute([$orderId]);
    $up = db()->prepare('UPDATE variants SET stock = stock + ? WHERE id = ? AND stock IS NOT NULL');
    foreach ($st as $r) {
        $up->execute([$sign * (int)$r['qty'], $r['variant_id']]);
    }
}
