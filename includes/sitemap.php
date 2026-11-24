<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/catalogue-categories.php';
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=300');
$paths = ['', 'boutique.php', 'boutique.php?cat=plante', 'boutique.php?cat=pot', 'boutique.php?cat=acc', 'page.php?p=pro', 'page.php?p=contact', 'page.php?p=livraison'];
foreach (BANDS as $key => $label) $paths[] = 'boutique.php?cat=plante&taille=' . $key;
foreach (MATS as $key => $label) $paths[] = 'boutique.php?cat=pot&mat=' . $key;
foreach (db()->query('SELECT slug FROM products WHERE active=1 ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) as $slug) $paths[] = 'produit.php?p=' . rawurlencode($slug);
$categories = catalogue_categories();
$visibleParents = [];
foreach ($categories as $entry) if ($entry['active'] && $entry['parent_key'] === null) $visibleParents['custom_' . $entry['id']] = true;
foreach ($categories as $entry) {
    if (!$entry['active']) continue;
    if (str_starts_with($entry['parent_key'] ?? '', 'custom_') && !isset($visibleParents[$entry['parent_key']])) continue;
    $paths[] = 'includes/categorie.php?id=' . (int)$entry['id'];
}
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach (array_unique($paths) as $path) echo '<url><loc>' . htmlspecialchars(seo_url($path), ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc></url>';
echo '</urlset>';
