<?php
require_once __DIR__ . '/seo.php';
$seoMeta = seo_metadata(basename($_SERVER['SCRIPT_NAME'] ?? 'index.php'), $_GET,
    isset($p) && is_array($p) && isset($p['variants']) ? $p : null,
    isset($category) && is_array($category) ? $category : null,
    (string)($pageTitle ?? ''), (string)($metaDesc ?? ''));
?>
<title><?= e($seoMeta['title']) ?></title>
<meta name="description" content="<?= e($seoMeta['description']) ?>">
<link rel="canonical" href="<?= e($seoMeta['canonical']) ?>">
<meta name="robots" content="<?= $seoMeta['index'] ? 'index,follow,max-image-preview:large' : 'noindex,follow' ?>">
<meta property="og:locale" content="fr_TN">
<meta property="og:site_name" content="<?= e($shopName) ?>">
<meta property="og:type" content="<?= e($seoMeta['type']) ?>">
<meta property="og:title" content="<?= e($seoMeta['title']) ?>">
<meta property="og:description" content="<?= e($seoMeta['description']) ?>">
<meta property="og:url" content="<?= e($seoMeta['canonical']) ?>">
<meta property="og:image" content="<?= e($seoMeta['image']) ?>">
<?php if (!empty(settings()['seo_google_verification'])): ?><meta name="google-site-verification" content="<?= e(settings()['seo_google_verification']) ?>"><?php endif; ?>
<?php if ($seoMeta['index'] && $seoMeta['schema']): ?>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@graph' => $seoMeta['schema']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?></script>
<?php endif; ?>
