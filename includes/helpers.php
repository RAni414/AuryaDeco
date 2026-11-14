<?php
declare(strict_types=1);

/* ---------- Texte et format ---------- */
function e($s): string
{
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money($n): string
{
    $s = number_format((float)$n, 3, ',', ' ');
    $s = rtrim(rtrim($s, '0'), ',');
    return $s . ' TND';
}

function slugify(string $s): string
{
    $s = mb_strtolower(trim($s), 'UTF-8');
    $map = ['à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ç'=>'c','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i','í'=>'i','ô'=>'o','ö'=>'o','ó'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ú'=>'u','œ'=>'oe','æ'=>'ae','ñ'=>'n'];
    $s = strtr($s, $map);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim((string)$s, '-');
    return substr($s !== '' ? $s : 'produit', 0, 80);
}

/* ---------- URL ---------- */
function base_url(): string
{
    static $base = null;
    if ($base === null) {
        global $CONFIG;
        if (isset($GLOBALS['PUBLIC_BASE_OVERRIDE'])) {
            $base = rtrim($GLOBALS['PUBLIC_BASE_OVERRIDE'], '/');
        } elseif (!empty($CONFIG['base_url'])) {
            $base = rtrim($CONFIG['base_url'], '/');
        } else {
            $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
            $dir = preg_replace('#/admin$#', '', $dir);
            $base = rtrim((string)$dir, '/');
        }
    }
    return $base;
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/* ---------- Sécurité ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if (!is_post() || !hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('La session a expiré. Revenez en arrière, rechargez la page et réessayez.');
    }
}

function flash(?string $msg = null, string $type = 'ok')
{
    if ($msg !== null) {
        $_SESSION['flash'][] = [$type, $msg];
        return null;
    }
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    $html = '';
    foreach ($all as [$t, $m]) {
        $html .= '<div class="flash flash-' . e($t) . '" role="status">' . e($m) . '</div>';
    }
    return $html;
}

/* ---------- Réglages ---------- */
function settings(): array
{
    static $s = null;
    if ($s === null) {
        $s = default_settings();
        foreach (db()->query('SELECT k, v FROM settings') as $r) {
            $s[$r['k']] = $r['v'];
        }
    }
    return $s;
}

function setting(string $k): string
{
    return (string)(settings()[$k] ?? '');
}

function default_settings(): array
{
    return [
        'shop_name'     => 'AuryaDeco',
        'topbar'        => 'Livraison partout en Tunisie, offerte dès 300 TND',
        'phone'         => '+216 00 000 000',
        'whatsapp'      => '21600000000',
        'email'         => 'contact@auryadeco.tn',
        'notify_email'  => '',
        'address'       => 'Sousse, Tunisie',
        'hero_title'    => 'Des plantes qui restent belles, sans arrosage.',
        'hero_text'     => 'Plantes artificielles réalistes et pots de décoration, de 50 cm à plus de 2 mètres. Livrés partout en Tunisie.',
        'about'         => 'Plantes artificielles réalistes et pots de décoration pour la maison, le bureau et les espaces professionnels.',
        'free_shipping' => '300',
        'shipping_fee'  => '8',
        'hours'         => 'Lundi au samedi, 9 h à 19 h',
    ];
}

function wa_link(string $text = '', ?string $number = null): string
{
    $num = preg_replace('/\D/', '', $number ?? setting('whatsapp'));
    return 'https://wa.me/' . $num . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

/** Hide empty/example contact numbers without changing the saved settings. */
function public_contact_number(string $key = 'phone'): string
{
    $value = trim(setting($key));
    $digits = preg_replace('/\D/', '', $value);
    $local = strpos($digits, '216') === 0 ? substr($digits, 3) : $digits;
    return strlen($digits) >= 8 && strlen($digits) <= 15 && !preg_match('/^0*$/', $local) ? $value : '';
}

/* ---------- Catalogue ---------- */
const KINDS = ['plante' => 'Plante artificielle', 'pot' => 'Pot', 'acc' => 'Accessoire'];
const MATS = ['ciment' => 'Ciment', 'resine' => 'Résine', 'leger' => 'Ultra-léger', 'pierre' => 'Pierre', 'terre' => 'Terre cuite', 'tresse' => 'Fibres tressées'];
const BANDS = ['petites' => 'Petites tailles', 'moyennes' => 'Moyennes tailles', 'grandes' => 'Grandes tailles', 'geantes' => 'Plantes géantes', 'tombantes' => 'Plantes tombantes'];
const BAND_HINT = ['petites' => '0 à 70 cm', 'moyennes' => '70 à 160 cm', 'grandes' => '160 à 220 cm', 'geantes' => '220 cm et plus', 'tombantes' => ''];
const PLANT_TYPES = ['palm' => 'Palmier en éventail', 'kentia' => 'Palmier à palmes', 'tree' => 'Arbre feuillu', 'olive' => 'Olivier', 'monstera' => 'Grandes feuilles', 'snake' => 'Feuilles droites', 'strelitzia' => 'Feuilles en pagaie', 'yucca' => 'Yucca', 'agave' => 'Rosette (agave)', 'bamboo' => 'Bambou', 'hanging' => 'Tombante à lianes', 'hanging2' => 'Tombante type fougère'];
const POT_SHAPES = ['cyl' => 'Cylindre', 'taper' => 'Conique', 'tall' => 'Haut élancé', 'bowl' => 'Coupe basse', 'stone' => 'Arrondi', 'flare' => 'Évasé', 'basket' => 'Panier tressé'];
const ACC_TYPES = ['pebbles' => 'Galets', 'moss' => 'Mousse'];
const ORDER_STATUS = ['nouvelle' => 'Nouvelle', 'confirmee' => 'Confirmée', 'expediee' => 'Expédiée', 'livree' => 'Livrée', 'annulee' => 'Annulée', 'retour' => 'Retournée'];
const GOVERNORATES = ['Ariana', 'Béja', 'Ben Arous', 'Bizerte', 'Gabès', 'Gafsa', 'Jendouba', 'Kairouan', 'Kasserine', 'Kébili', 'Le Kef', 'Mahdia', 'La Manouba', 'Médenine', 'Monastir', 'Nabeul', 'Sfax', 'Sidi Bouzid', 'Siliana', 'Sousse', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan'];

function band_of(int $h): string
{
    return $h <= 70 ? 'petites' : ($h < 160 ? 'moyennes' : ($h <= 220 ? 'grandes' : 'geantes'));
}

/** Charge des produits avec leurs tailles et photos. */
function load_products(string $where = '1', array $params = [], string $order = 'p.position ASC, p.id DESC', int $limit = 0): array
{
    $sql = "SELECT p.* FROM products p WHERE $where ORDER BY $order" . ($limit ? ' LIMIT ' . (int)$limit : '');
    $st = db()->prepare($sql);
    $st->execute($params);
    $products = [];
    foreach ($st as $r) {
        $r['variants'] = [];
        $r['images'] = [];
        $products[(int)$r['id']] = $r;
    }
    if (!$products) {
        return [];
    }
    $ids = implode(',', array_keys($products));
    foreach (db()->query("SELECT * FROM variants WHERE product_id IN ($ids) ORDER BY position ASC, id ASC") as $v) {
        $products[(int)$v['product_id']]['variants'][] = $v;
    }
    foreach (db()->query("SELECT * FROM product_images WHERE product_id IN ($ids) ORDER BY position ASC, id ASC") as $i) {
        $products[(int)$i['product_id']]['images'][] = $i;
    }
    return array_values($products);
}

function load_product_by(string $field, $value, bool $onlyActive = true): ?array
{
    $field = $field === 'id' ? 'p.id' : 'p.slug';
    $list = load_products($field . ' = ?' . ($onlyActive ? ' AND p.active = 1' : ''), [$value]);
    return $list[0] ?? null;
}

function variant_label(array $p, array $v): string
{
    if ($p['kind'] === 'plante') {
        return 'H ' . (int)$v['height'] . ' cm';
    }
    if ($p['kind'] === 'pot') {
        return trim((string)($v['label'] ?? '')) ?: ('D ' . (int)$v['diameter'] . ' cm');
    }
    $label = trim((string)$v['label']);
    if (($p['type'] ?? '') === 'pebbles' && preg_match('/^(\d+(?:[.,]\d+)?)\s*(?:kg)?$/i', $label, $weight)) {
        return $weight[1] . ' KG';
    }
    return $label;
}

function in_stock(array $v): bool
{
    return $v['stock'] === null || (int)$v['stock'] > 0;
}

function all_out(array $p): bool
{
    foreach ($p['variants'] as $v) {
        if (in_stock($v)) {
            return false;
        }
    }
    return true;
}

function is_promo(array $p): bool
{
    foreach ($p['variants'] as $v) {
        if ($v['old_price'] !== null && (float)$v['old_price'] > (float)$v['price']) {
            return true;
        }
    }
    return false;
}

function price_range(array $p): string
{
    $ps = [];
    foreach ($p['variants'] as $v) {
        if (in_stock($v)) {
            $ps[] = (float)$v['price'];
        }
    }
    if (!$ps) {
        $ps = array_map(fn($v) => (float)$v['price'], $p['variants']);
    }
    if (!$ps) {
        return '';
    }
    $a = min($ps);
    $b = max($ps);
    return $a === $b ? money($a) : money($a) . ' à ' . money($b);
}

function sizes_text(array $p): string
{
    if ($p['kind'] === 'acc') return implode(' / ', array_map(fn($v) => variant_label($p, $v), $p['variants']));
    if ($p['kind'] === 'pot') return implode(' / ', array_map(fn($v) => variant_label($p, $v), $p['variants']));
    $vals = [];
    foreach ($p['variants'] as $v) {
        $vals[] = $p['kind'] === 'plante' ? (int)$v['height'] : ($p['kind'] === 'pot' ? (int)$v['diameter'] : $v['label']);
    }
    if ($p['kind'] === 'plante') {
        return 'H ' . implode(' / ', $vals) . ' cm';
    }
    if ($p['kind'] === 'pot') {
        return 'Ø ' . implode(' / ', $vals) . ' cm';
    }
    return implode(' / ', $vals);
}

function sub_text(array $p): string
{
    if ($p['kind'] === 'pot') {
        return MATS[$p['mat']] ?? 'Pot';
    }
    return !empty($p['tomb']) ? 'Plante tombante' : KINDS[$p['kind']];
}

/** Photo du produit, ou illustration dessinée par assets/js/art.js. */
function media(array $p, bool $big = false): string
{
    if ($big) {
        return '<div class="favorite-media">' . media($p, false) . favorite_button((int)($p['id'] ?? 0)) . '</div>';
    }
    if (!empty($p['images'])) {
        return '<img class="ph" src="' . e(url('uploads/' . $p['images'][0]['file'])) . '" alt="' . e($p['name']) . '"' . ($big ? '' : ' loading="lazy"') . '>';
    }
    return art_tag($p);
}

function art_tag(array $p): string
{
    $d = [
        'id' => $p['slug'] ?? ('p' . ($p['id'] ?? '')),
        'kind' => $p['kind'],
        'type' => $p['type'] ?? '',
        'shape' => $p['shape'] ?? '',
        'color' => $p['color'] ?? '',
        'tomb' => !empty($p['tomb']),
        'name' => $p['name'] ?? '',
    ];
    return '<div class="art-box" data-art="' . e(json_encode($d, JSON_UNESCAPED_UNICODE)) . '" role="img" aria-label="' . e($p['name'] ?? '') . '"></div>';
}

function favorite_button(int $id): string
{
    if ($id < 1) return '';
    return '<button type="button" class="fav-toggle" data-favorite-id="' . $id . '" aria-pressed="false" aria-label="Ajouter aux favoris" hidden>'
        . '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/></svg></button>';
}

function product_card(array $p, string $extra = ''): string
{
    $href = e(url('produit.php?p=' . rawurlencode($p['slug'])));
    $photos = array_map(fn($i) => url('uploads/' . $i['file']), $p['images']);
    $slides = '';
    foreach ($photos as $n => $src) {
        $slides .= '<img class="card-photo' . ($n === 0 ? ' is-active' : '') . '" src="' . e($src) . '" alt="' . e($p['name'] . ' — photo ' . ($n + 1)) . '" loading="lazy">';
    }
    $variants = array_map(fn($v) => ['id' => (int)$v['id'], 'label' => variant_label($p, $v), 'price' => money($v['price']), 'available' => in_stock($v)], $p['variants']);
    $details = ['name' => $p['name'], 'photos' => $photos, 'variants' => $variants, 'price' => price_range($p), 'href' => url('produit.php?p=' . rawurlencode($p['slug']))];
    $tag = all_out($p) ? '<span class="tagx sale">Rupture de stock</span>' : (is_promo($p) ? '<span class="tagx sale">Promo</span>' : '');
    return '<article class="pcard" data-product-id="' . (int)$p['id'] . '">'
        . '<div class="card-visual" data-card-details="' . e(json_encode($details, JSON_UNESCAPED_UNICODE)) . '"><a class="media card-gallery' . (count($photos) > 1 ? ' has-hover-photo' : '') . '" href="' . $href . '" aria-label="' . e($p['name']) . '">' . ($slides ?: media($p)) . '</a>'
        . '<div class="card-actions"><button type="button" data-card-cart aria-label="Ajouter au panier" title="Ajouter au panier" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2 3h3l3 13h11l3-9H6M9 21h.01M18 21h.01"/></svg></button><button type="button" data-card-preview aria-label="Aperçu rapide" title="Aperçu rapide" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button>' . favorite_button((int)$p['id']) . '</div><div class="card-dots" aria-label="Photos du produit"></div></div>' . $tag
        . '<div class="body"><span class="kind">' . e(sub_text($p)) . '</span>'
        . '<h3><a href="' . $href . '">' . e($p['name']) . '</a></h3>'
        . ($p['kind'] !== 'plante' ? '<span class="sizes">' . e(sizes_text($p)) . '</span>' : '')
        . '<span class="price">' . e(price_range($p)) . '</span>' . $extra . '</div></article>';
}

function product_grid(array $list, string $empty = ''): string
{
    if (!$list) {
        return '<div class="empty">' . ($empty ?: 'Aucun produit ne correspond à ces filtres. <a href="' . e(url('boutique.php')) . '">Voir toute la boutique</a>') . '</div>';
    }
    if (!empty($GLOBALS['homePage'])) return product_carousel($list);
    return '<div class="grid">' . implode('', array_map('product_card', $list)) . '</div>';
}

function product_carousel(array $list): string
{
    static $number = 0;
    $id = 'product-track-' . ++$number;
    return '<section class="product-carousel" data-product-carousel role="region" aria-roledescription="carrousel" aria-label="Sélection de produits">'
        . '<div class="product-carousel-row"><button class="product-arrow" type="button" data-product-prev aria-label="Produits précédents" aria-controls="' . $id . '" hidden>←</button>'
        . '<div class="product-track" id="' . $id . '" tabindex="0" aria-label="Produits : utilisez les flèches gauche et droite">' . implode('', array_map('product_card', $list)) . '</div>'
        . '<button class="product-arrow" type="button" data-product-next aria-label="Produits suivants" aria-controls="' . $id . '" hidden>→</button></div>'
        . '<div class="product-pagination" aria-label="Pages de produits"></div><p class="product-carousel-status" aria-live="polite" aria-atomic="true"></p></section>';
}

/* ---------- Panier ---------- */
function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(cart());
}

/** Lignes du panier avec les données à jour de la base. */
function cart_lines(): array
{
    $cart = cart();
    if (!$cart) {
        return [];
    }
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $rows = db()->query("SELECT v.*, p.name, p.slug, p.kind, p.type, p.shape, p.color, p.tomb, p.active, p.shipping_fee, p.id AS pid
                         FROM variants v JOIN products p ON p.id = v.product_id WHERE v.id IN ($ids)")->fetchAll();
    $lines = [];
    foreach ($rows as $r) {
        if (!$r['active']) {
            continue;
        }
        $p = ['id' => $r['pid'], 'name' => $r['name'], 'slug' => $r['slug'], 'kind' => $r['kind'], 'type' => $r['type'], 'shape' => $r['shape'], 'color' => $r['color'], 'tomb' => $r['tomb']];
        $img = db()->prepare('SELECT file FROM product_images WHERE product_id = ? ORDER BY position, id LIMIT 1');
        $img->execute([$r['pid']]);
        $f = $img->fetchColumn();
        $p['images'] = $f ? [['file' => $f]] : [];
        $qty = (int)$cart[$r['id']];
        $lines[] = ['variant' => $r, 'product' => $p, 'qty' => $qty, 'label' => variant_label($p, $r), 'total' => (float)$r['price'] * $qty, 'ship' => product_ship_fee($r['shipping_fee'])];
    }
    return $lines;
}

/** Frais de livraison d'un produit (par article). Vide = tarif par défaut des réglages. */
function product_ship_fee($fee): float
{
    return ($fee === null || $fee === '') ? (float)setting('shipping_fee') : max(0.0, (float)$fee);
}

/** Texte « Livraison : 25 TND par article » ou « Livraison offerte ». */
function ship_text($fee): string
{
    $f = product_ship_fee($fee);
    return $f > 0 ? money($f) . ' par article' : 'offerte sur cet article';
}

/**
 * Total de livraison d'une commande : somme (frais du produit x quantité),
 * ou 0 si le sous-total atteint le seuil de livraison offerte des réglages.
 */
function shipping_total(float $subtotal, float $raw): float
{
    if ($subtotal <= 0) {
        return 0;
    }
    $free = (float)setting('free_shipping');
    return ($free > 0 && $subtotal >= $free) ? 0 : $raw;
}

/** Livraison calculée à partir des lignes du panier. */
function shipping_for(float $subtotal, array $lines): float
{
    $raw = 0.0;
    foreach ($lines as $l) {
        $raw += $l['ship'] * $l['qty'];
    }
    return shipping_total($subtotal, $raw);
}

/* ---------- Avis clients ---------- */
function stars_html(int $n): string
{
    $n = max(0, min(5, $n));
    return '<span class="stars" role="img" aria-label="Note : ' . $n . ' sur 5">' . str_repeat('<span class="on">★</span>', $n) . str_repeat('<span>★</span>', 5 - $n) . '</span>';
}

function review_card(array $r, int $i = 1, int $n = 1): string
{
    $who = '<div class="rwho"><span class="avatar" aria-hidden="true">' . e(mb_strtoupper(mb_substr(trim($r['name']), 0, 1))) . '</span><span><strong>' . e($r['name']) . '</strong>'
        . ($r['city'] ? '<small>' . e($r['city']) . '</small>' : '') . '</span></div>';
    $quote = '<blockquote>' . nl2br(e($r['comment'])) . '</blockquote>';
    $attrs = ' role="group" aria-roledescription="diapositive" aria-label="Avis ' . $i . ' sur ' . $n . '"';
    if ($r['photo']) {
        $src = e(url('uploads/' . $r['photo']));
        return '<figure class="rcard"' . $attrs . '>'
            . '<div class="rmedia"><button type="button" class="rphoto" data-zoom="' . $src . '" aria-label="Agrandir la photo de ' . e($r['name']) . '"><img src="' . $src . '" alt="Photo envoyée par ' . e($r['name']) . '" loading="lazy" draggable="false"></button>'
            . '<span class="rpill">' . stars_html((int)$r['rating']) . '</span></div>'
            . '<figcaption class="rbody">' . $quote . $who . '</figcaption></figure>';
    }
    return '<figure class="rcard quote"' . $attrs . '><figcaption class="rbody"><span class="qmark" aria-hidden="true">“</span>'
        . stars_html((int)$r['rating']) . $quote . $who . '</figcaption></figure>';
}

function load_reviews(int $limit = 0): array
{
    $sql = 'SELECT * FROM reviews WHERE active = 1 ORDER BY position ASC, id DESC' . ($limit ? ' LIMIT ' . $limit : '');
    return db()->query($sql)->fetchAll();
}
