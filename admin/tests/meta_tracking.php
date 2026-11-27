<?php
require __DIR__ . '/../../includes/meta-tracking.php';
function check_meta($ok) { if (!$ok) throw new RuntimeException('Meta test failed'); }
$_SERVER['SCRIPT_NAME']='/produit.php';
$events=meta_tracking_events(['p'=>['id'=>42,'name'=>'Test']]);
check_meta(count($events)===2 && $events[1]['event_name']==='ViewContent');
$_SERVER['SCRIPT_NAME']='/merci.php';$_SESSION['last_order']='TEST-123';
$events=meta_tracking_events(['order'=>['number'=>'TEST-123','total'=>'99.50']]);
$again=meta_tracking_events(['order'=>['number'=>'TEST-123','total'=>'99.50']]);
check_meta($events[1]['event_id']===$again[1]['event_id'] && $events[1]['custom_data']['currency']==='TND');
check_meta(count(meta_tracking_events(['order'=>['number'=>'OTHER','total'=>10]]))===1);
putenv('META_CAPI_TOKEN');check_meta(meta_send_events($events)===false);
echo "PASS product event, purchase identity, order ownership, missing token safely disabled\n";
