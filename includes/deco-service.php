<?php
declare(strict_types=1);

final class DecoError extends RuntimeException
{
    public $status;
    public function __construct(string $message, int $status = 400) { parent::__construct($message); $this->status = $status; }
}

function deco_demo(): bool { return trim((string)getenv('GEMINI_API_KEY')) === ''; }

function deco_text(string $key, int $max = 1800): string
{
    if (isset($_POST[$key]) && !is_string($_POST[$key])) throw new DecoError('Champ de formulaire invalide.');
    $text = trim((string)($_POST[$key] ?? ''));
    if (mb_strlen($text) > $max) throw new DecoError('Votre message est trop long.');
    return $text;
}

function deco_rate_limit(string $action = 'analysis'): void
{
    // A shared, atomic counter: changing cookies does not reset the IP budget.
    $dir = rtrim(sys_get_temp_dir(), '/\\') . '/auryadeco-deco-' . substr(hash('sha256', APP_DIR), 0, 16);
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) throw new DecoError('Assistant temporairement indisponible.', 503);
    try {
        $db = new PDO('sqlite:' . $dir . '/limits.sqlite', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db->exec('PRAGMA busy_timeout=3000');
        $db->exec('CREATE TABLE IF NOT EXISTS limits (k TEXT PRIMARY KEY, n INTEGER NOT NULL, expires INTEGER NOT NULL)');
        $db->exec('BEGIN IMMEDIATE');
        $now = time();
        $db->prepare('DELETE FROM limits WHERE expires < ?')->execute([$now]);
        $keys = $action === 'simulation' ? [
            ['image-ip:' . hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown')), 4, 3600],
            ['image-session:' . hash('sha256', session_id()), 2, 3600],
            ['image-site', 10, 3600],
        ] : [
            ['ip:' . hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown')), 12, 3600],
            ['session:' . hash('sha256', session_id()), 6, 600],
            ['site', 120, 3600],
            ['gemini-minute', 3, 60],
            ['gemini-day', 20, 86400],
        ];
        foreach ($keys as [$key, $max, $window]) {
            $q = $db->prepare('SELECT n FROM limits WHERE k=?'); $q->execute([$key]);
            if ((int)$q->fetchColumn() >= $max) {
                $db->exec('ROLLBACK');
                throw new DecoError('Limite locale de demandes atteinte. Réessayez plus tard ; le plafond journalier est de 20 demandes pour la boutique.', 429);
            }
        }
        foreach ($keys as [$key, $max, $window]) {
            $db->prepare('INSERT INTO limits(k,n,expires) VALUES(?,1,?) ON CONFLICT(k) DO UPDATE SET n=n+1')->execute([$key, $now + $window]);
        }
        $db->exec('COMMIT');
    } catch (DecoError $e) { throw $e; }
    catch (Throwable $e) { throw new DecoError('Le compteur de demandes est indisponible. Réessayez plus tard.', 503); }
}

function deco_photo(bool $demo): ?string
{
    if (empty($_FILES['photo'])) return null;
    $file = $_FILES['photo'];
    if (!is_array($file) || is_array($file['error'] ?? null)) throw new DecoError('Photo invalide.');
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) throw new DecoError('Envoi impossible : choisissez une image de moins de 5 Mo.');
    $path = $file['tmp_name'];
    if (!is_uploaded_file($path) || filesize($path) > 5 * 1024 * 1024 || filesize($path) < 16) throw new DecoError('La photo doit faire moins de 5 Mo.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    $info = @getimagesize($path);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || !$info || ($info['mime'] ?? '') !== $mime) {
        throw new DecoError('Le fichier doit être une vraie image JPEG, PNG ou WebP.');
    }
    if ($info[0] < 100 || $info[1] < 100 || $info[0] > 6000 || $info[1] > 6000 || $info[0] * $info[1] > 12000000) {
        throw new DecoError('Choisissez une photo entre 100 et 6 000 pixels par côté, de 12 mégapixels maximum.');
    }
    if (!function_exists('imagecreatefromstring')) throw new DecoError('La vérification des photos nécessite l’extension PHP GD. Contactez la boutique.', 503);
    $image = @imagecreatefromstring((string)file_get_contents($path));
    if (!$image) throw new DecoError('Cette image est endommagée ou illisible.');
    try {
        // Decode and re-encode: strip metadata and never send the original file.
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $orientation = (int)($exif['Orientation'] ?? 1);
            if (in_array($orientation, [2, 4, 5, 7], true)) imageflip($image, IMG_FLIP_HORIZONTAL);
            $angle = [3 => 180, 4 => 180, 5 => -90, 6 => -90, 7 => 90, 8 => 90][$orientation] ?? 0;
            if ($angle) { $rotated = imagerotate($image, $angle, 0); if ($rotated) { imagedestroy($image); $image = $rotated; } }
        }
        $ratio = min(1, 1600 / max(imagesx($image), imagesy($image)));
        $w = max(1, (int)(imagesx($image) * $ratio)); $h = max(1, (int)(imagesy($image) * $ratio));
        $small = imagecreatetruecolor($w, $h);
        try {
            imagefill($small, 0, 0, imagecolorallocate($small, 255, 255, 255));
            imagecopyresampled($small, $image, 0, 0, 0, 0, $w, $h, imagesx($image), imagesy($image));
            ob_start();
            try { imagejpeg($small, null, 82); $bytes = ob_get_contents(); } finally { ob_end_clean(); }
            return $demo ? null : 'data:image/jpeg;base64,' . base64_encode($bytes);
        } finally { imagedestroy($small); }
    } finally { imagedestroy($image); }
}

function deco_reply(array $plant, string $kind, string $message, string $dimensions, string $light, ?string $photo, bool $hasPhoto, array $needs = [], array $candidates = []): string
{
    require_once __DIR__ . '/gemini-service.php';
    return gemini_deco_reply($plant, $kind, $message, $dimensions, $light, $photo, $hasPhoto, $needs, $candidates);
}
