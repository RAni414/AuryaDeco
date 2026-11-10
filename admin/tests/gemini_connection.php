<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$curl = curl_init('https://generativelanguage.googleapis.com/');
curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>true]);
curl_exec($curl);
echo json_encode(['curl_errno'=>curl_errno($curl),'http_status'=>curl_getinfo($curl,CURLINFO_HTTP_CODE),'seconds'=>curl_getinfo($curl,CURLINFO_TOTAL_TIME),'error'=>curl_error($curl)]) . "\n";
curl_close($curl);
