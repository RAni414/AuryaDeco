<?php
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

