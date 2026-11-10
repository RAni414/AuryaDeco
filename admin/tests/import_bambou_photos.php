<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('session.save_path', sys_get_temp_dir());
require __DIR__ . '/../../includes/bootstrap.php';
$files = ['bambou-geant-fond-blanc.png', 'bambou-geant-salon.png'];
foreach ($files as $file) {
    if (!is_file(UPLOAD_DIR . '/' . $file) || !getimagesize(UPLOAD_DIR . '/' . $file)) throw new RuntimeException('Photo manquante ou invalide : ' . $file);
}
$pdo = db();
$pdo->beginTransaction();
try {
    $query = $pdo->prepare("SELECT id FROM products WHERE slug='bambou' AND kind='plante' FOR UPDATE");
    $query->execute();
    $id = $query->fetchColumn();
    if (!$id) throw new RuntimeException('Bambou géant introuvable.');
    $query = $pdo->prepare('SELECT id,file FROM product_images WHERE product_id=? ORDER BY position,id');
    $query->execute([$id]); $existing = $query->fetchAll();
    $insert = $pdo->prepare('INSERT INTO product_images(product_id,file,position) VALUES(?,?,?)');
    $update = $pdo->prepare('UPDATE product_images SET position=? WHERE id=? AND product_id=?');
    foreach ($files as $position => $file) {
        $match = array_values(array_filter($existing, static fn($image) => $image['file'] === $file));
        if ($match) $update->execute([$position,$match[0]['id'],$id]);
        else $insert->execute([$id,$file,$position]);
    }
    $position = 2;
    foreach ($existing as $image) if (!in_array($image['file'],$files,true)) $update->execute([$position++,$image['id'],$id]);
    $pdo->commit();
    echo json_encode(['product_id'=>(int)$id,'slug'=>'bambou','photos'=>$files],JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $error) { $pdo->rollBack(); throw $error; }
