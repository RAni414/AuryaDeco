<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('session.save_path', sys_get_temp_dir());
require __DIR__ . '/../../includes/bootstrap.php';
$counts = db()->query('SELECT COUNT(*) AS products, SUM((SELECT COUNT(*) FROM product_images i WHERE i.product_id=p.id)>=2) AS with_two FROM products p WHERE p.active=1')->fetch();
echo json_encode(['catalogue'=>$counts], JSON_UNESCAPED_UNICODE) . "\n";
$rows = db()->query('SELECT p.id,p.slug,p.name,COUNT(i.id) AS photos FROM products p LEFT JOIN product_images i ON i.product_id=p.id WHERE p.active=1 GROUP BY p.id ORDER BY photos DESC,p.id LIMIT 3')->fetchAll();
foreach ($rows as $row) {
    $p = load_product_by('id', $row['id']);
    $html = product_card($p);
    $photos = [];
    foreach ($p['images'] as $image) $photos[] = ['file'=>$image['file'],'exists'=>is_file(UPLOAD_DIR . '/' . $image['file'])];
    echo json_encode(['product'=>$row,'loaded_photos'=>count($p['images']),'html_images'=>substr_count($html,'class="card-photo'),'files'=>$photos], JSON_UNESCAPED_UNICODE) . "\n";
}
