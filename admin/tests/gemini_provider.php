<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (extension_loaded('curl')) { fwrite(STDERR,"Run with PHP -n (cURL is mocked).\n"); exit(1); }
foreach (['CURLOPT_POST','CURLOPT_RETURNTRANSFER','CURLOPT_CONNECTTIMEOUT','CURLOPT_TIMEOUT','CURLOPT_FOLLOWLOCATION','CURLOPT_SSL_VERIFYPEER','CURLOPT_HTTPHEADER','CURLOPT_POSTFIELDS','CURLOPT_WRITEFUNCTION','CURLINFO_HTTP_CODE','CURLOPT_CAINFO'] as $i=>$name) define($name,$i+1);
$status=200; $response=''; $options=[]; $calls=0; $url=''; $transport=true;
$transportError=0;
if (!extension_loaded('curl')) {
function curl_init($address){global $url,$calls;$url=$address;$calls++;return true;}
function curl_setopt_array($handle,$value){global $options;$options=array_replace($options,$value);return true;}
function curl_exec($handle){global $response,$options,$transport;if(!$transport)return false;return $options[CURLOPT_WRITEFUNCTION]($handle,$response)===strlen($response);}
function curl_getinfo($handle,$option){global $status;return $status;}
function curl_close($handle){}
function curl_errno($handle){global $transportError;return $transportError;}
}
require __DIR__.'/../../includes/deco-service.php';
require __DIR__.'/../../includes/gemini-service.php';
function check($value,string $label):void{if(!$value)throw new RuntimeException('FAIL '.$label);echo 'PASS MOCK '.$label."\n";}
function answer(string $text,array $ids=[7]):string{return json_encode(['candidates'=>[['finishReason'=>'STOP','content'=>['parts'=>[['text'=>json_encode(['advice'=>$text,'variant_ids'=>$ids])]]]]]]);}
putenv('GEMINI_API_KEY'); $_SESSION=[];
try{deco_reply([],'artificielle','Bonjour','','',null,false);throw new RuntimeException('Expected missing key');}
catch(DecoError $e){check($e->status===503&&$calls===0,'missing key: no API call and no fake reply');}
putenv('GEMINI_API_KEY=unit-test-placeholder-not-a-real-key');
$rows=[['variant_id'=>7,'name'=>'Plante test','height'=>150,'description'=>'Décoration de salon']];
$response=answer('Quel espace est disponible près de votre canapé ?',[999,7]);
$photo='data:image/jpeg;base64,'.base64_encode(file_get_contents(__DIR__.'/../../assets/images/hero-deco-poster.jpg'));
$reply=deco_reply([],'artificielle','Voici mon salon','','',$photo,true,[], $rows);
$payload=json_decode($options[CURLOPT_POSTFIELDS],true);
check(count($payload['contents'])===1 && isset($payload['contents'][0]['parts'][1]['inlineData']),'image sent as inline JPEG');
check($_SESSION['deco_recommended']===[7],'only real candidate IDs retained');
check(strpos($url,'?')===false && strpos($url,'generativelanguage.googleapis.com/v1beta/models/')!==false,'fixed Google endpoint, no key in URL');
check($options[CURLOPT_SSL_VERIFYPEER]&&!$options[CURLOPT_FOLLOWLOCATION]&&!isset($payload['tools']),'TLS and no paid tools');
check($payload['generationConfig']['maxOutputTokens']===1400,'bounded output');
check(strpos(json_encode($_SESSION),'base64')===false&&strpos(json_encode($_SESSION),'inlineData')===false&&strpos(json_encode($_SESSION),'unit-test-placeholder')===false,'no photo or API key in session');
$response=answer('Vous avez indiqué un salon et un canapé. Quelles sont leurs dimensions ?');
deco_reply([],'artificielle','Mon espace libre fait 80 cm','','',null,false,[], $rows);
$payload=json_decode($options[CURLOPT_POSTFIELDS],true);
check(count($payload['contents'])===3&&$payload['contents'][1]['role']==='model','second message receives previous turn');
$response=answer('Précisez également le diamètre du pot.');
deco_reply([],'artificielle','Que dois-je mesurer ?','','',null,false,[], $rows);
deco_reply([],'artificielle','Et la circulation ?','','',null,false,[], $rows);
check(count($_SESSION['gemini_history'])===6,'bounded multi-turn context');
$visitorA=$_SESSION; $_SESSION=[];
deco_reply([],'artificielle','Autre visiteur','','',null,false,[], $rows);
$payload=json_decode($options[CURLOPT_POSTFIELDS],true);
check(count($payload['contents'])===1,'separate visitor receives no other history');
$_SESSION=$visitorA;
foreach([[429,'{}',429],[403,'{}',503],[404,'{}',503],[500,'{}',502],[200,'{"promptFeedback":{"blockReason":"SAFETY"}}',422],[200,'{"candidates":[{"finishReason":"MAX_TOKENS"}]}',502],[200,'{"candidates":[{"finishReason":"STOP","content":{"parts":[{"text":"not JSON"}]}}]}',502]]as[$status,$response,$expected]){
 $before=$_SESSION['gemini_history'];
 try{deco_reply([],'artificielle','Question','','',null,false,[], $rows);throw new RuntimeException('Expected error');}
 catch(DecoError $e){check($e->status===$expected&&$_SESSION['gemini_history']===$before&&strpos($e->getMessage(),'unit-test-placeholder')===false,'safe provider error '.$status);}
}
$transport=false;
try{deco_reply([],'artificielle','Question','','',null,false,[], $rows);throw new RuntimeException('Expected timeout');}
catch(DecoError $e){check($e->status===502,'timeout handled without retry');}
foreach ([28=>[504,'75 secondes'],60=>[503,'certificats CA'],77=>[503,'certificats CA'],6=>[502,'DNS'],7=>[502,'pare-feu']] as $transportError=>$expected) {
 try{deco_reply([],'artificielle','Question','','',null,false,[], $rows);throw new RuntimeException('Expected transport error');}
 catch(DecoError $e){check($e->status===$expected[0]&&strpos($e->getMessage(),$expected[1])!==false&&strpos($e->getMessage(),'unit-test-placeholder')===false,'distinct safe transport error '.$transportError);}
}
putenv('GEMINI_MODEL=paid-or-image-model');
try{gemini_model();throw new RuntimeException('Expected unsupported model');}
catch(DecoError $e){check($e->status===503,'unsupported/paid model rejected');}
putenv('GEMINI_MODEL');putenv('GEMINI_API_KEY');
