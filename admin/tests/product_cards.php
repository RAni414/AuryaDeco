<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$CONFIG = ['base_url'=>'/test'];
require __DIR__ . '/../../includes/helpers.php';
function verify($condition, string $name): void { if (!$condition) throw new RuntimeException($name); echo "PASS $name\n"; }
$p = ['id'=>1,'slug'=>'plante-test','kind'=>'plante','name'=>'Plante "test" <verte>','images'=>[],
    'variants'=>[['id'=>10,'height'=>100,'price'=>150,'old_price'=>200,'stock'=>null]]];
verify(strpos(product_card($p), 'data-art=') !== false, 'illustration retained without photos');
$p['images'] = [['file'=>'photo-reelle.webp']];
$html = product_card($p);
verify(substr_count($html, 'class="card-photo') === 1, 'single photo remains a single slide');
verify(strpos($html, 'has-hover-photo') === false, 'single photo never gets a hover override');
$p['images'][] = ['file'=>'autre-photo.jpg'];
$html = product_card($p);
verify(strpos($html, 'has-hover-photo') !== false, 'two-photo gallery enables CSS hover independently of JavaScript');
preg_match('/data-card-details="([^"]+)"/', $html, $match);
$details = json_decode(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'), true, 512, JSON_THROW_ON_ERROR);
verify($details['photos'] === ['/test/uploads/photo-reelle.webp','/test/uploads/autre-photo.jpg'], 'gallery order and stored filenames preserved');
verify($details['name'] === $p['name'] && strpos($html, '<verte>') === false, 'embedded JSON and product text escaped');
verify($details['variants'][0]['price'] === '150 TND' && $details['variants'][0]['available'], 'current price and unlimited stock');
$p['variants'][] = ['id'=>11,'height'=>180,'price'=>250,'old_price'=>null,'stock'=>0];
preg_match('/data-card-details="([^"]+)"/', product_card($p), $match);
$details = json_decode(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'), true);
verify(count($details['variants']) === 2 && !$details['variants'][1]['available'], 'all variants and sold-out state retained');
verify(strpos($html, 'data-favorite-id="1"') !== false && strpos($html, '/test/produit.php?p=plante-test') !== false, 'existing favorite control and product link retained');
