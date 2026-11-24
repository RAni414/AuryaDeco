<?php
function customer_review_upload_choice(array $files): array
{
    $choice = ['photo' => null, 'video' => null]; $count = 0;
    foreach (['photo', 'camera_photo', 'video'] as $key) {
        if (!isset($files[$key])) continue;
        $file = $files[$key];
        if (!is_array($file) || !is_int($file['error'] ?? null)) throw new InvalidArgumentException('Le média envoyé est invalide.');
        if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;
        $choice[$key === 'video' ? 'video' : 'photo'] = $file;
        $count++;
    }
    if ($count > 1) throw new InvalidArgumentException('Joignez une seule photo ou une seule vidéo à votre avis.');
    return $choice;
}

function customer_review_video(?array $file): ?string
{
    if ($file === null) return null;
    if (!is_int($file['error'] ?? null)) throw new InvalidArgumentException('La vidéo envoyée est invalide.');
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) throw new InvalidArgumentException('La vidéo est trop lourde. Choisissez un fichier de 25 Mo maximum.');
    if ($file['error'] !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) throw new InvalidArgumentException('La vidéo n’a pas pu être envoyée. Sélectionnez-la à nouveau.');
    $size = filesize($file['tmp_name']);
    if ($size === false || $size < 16 || $size > 25 * 1024 * 1024) throw new InvalidArgumentException('La vidéo doit peser au maximum 25 Mo.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];
    if (!isset($extensions[$mime])) throw new InvalidArgumentException('Choisissez une vidéo MP4 ou WebM. Convertissez les fichiers MOV ou HEIC avant l’envoi.');
    $head = file_get_contents($file['tmp_name'], false, null, 0, 32);
    if ($head === false || ($mime === 'video/mp4' && substr($head, 4, 4) !== 'ftyp') || ($mime === 'video/webm' && substr($head, 0, 4) !== "\x1A\x45\xDF\xA3")) throw new InvalidArgumentException('Le fichier ne correspond pas à une vidéo prise en charge.');
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) throw new RuntimeException('Le dossier des médias est indisponible.');
    $name = 'review-' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) throw new RuntimeException('Impossible d’enregistrer la vidéo.');
    return $name;
}
