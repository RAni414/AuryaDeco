<?php
session_save_path(sys_get_temp_dir());
require __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/review-service.php';
function media_check($ok) { if (!$ok) throw new RuntimeException('Review media test failed'); }
$file = ['error' => UPLOAD_ERR_OK];
media_check(customer_review_upload_choice(['camera_photo'=>$file])['photo'] === $file);
try { customer_review_upload_choice(['photo'=>$file, 'video'=>$file]); throw new RuntimeException('Multiple media accepted'); } catch (InvalidArgumentException $e) {}
try { customer_review_video(['error'=>UPLOAD_ERR_INI_SIZE]); throw new RuntimeException('Oversized upload accepted'); } catch (InvalidArgumentException $e) {}
$pdo=db(); $pdo->beginTransaction();
try {
 $id=save_customer_review($pdo,['name'=>'Media test','city'=>'','rating'=>5,'comment'=>'Test media'],null,'review-test.mp4');
 $q=$pdo->prepare('SELECT * FROM reviews WHERE id=?');$q->execute([$id]);$r=$q->fetch();
 media_check(!$r['active'] && !$r['photo'] && $r['video']==='review-test.mp4');
 $html=review_card($r);media_check(str_contains($html,'<video') && str_contains($html,'controls') && !str_contains($html,'autoplay'));
 echo "PASS camera input, single attachment, size rejection, video moderation and player\n";
} finally { $pdo->rollBack(); }
