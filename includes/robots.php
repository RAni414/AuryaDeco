<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/seo.php';
header('Content-Type: text/plain; charset=utf-8');
echo "User-agent: *\nAllow: /\nDisallow: /admin/\n\nSitemap: " . seo_url('sitemap.xml') . "\n";
