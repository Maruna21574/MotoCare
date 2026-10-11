<?php
// sitemap.xml (served through a rewrite rule in .htaccess): public pages, services and products.
require_once __DIR__ . '/inc/config.php';

$paths = ['index.php', 'sluzby', 'galeria', 'o-nas', 'kontakt', 'rezervacia', 'shop', 'ramy'];
foreach (array_keys($frames) as $id) $paths[] = 'ramy?typ=' . rawurlencode((string) $id);
foreach (array_keys($services) as $id) $paths[] = 'sluzba?slug=' . rawurlencode((string) $id);
foreach (array_keys($products) as $id) $paths[] = 'produkt?slug=' . rawurlencode((string) $id);
foreach (array_keys(galleryBikes($gallery)) as $slug) $paths[] = 'galeria?motorka=' . rawurlencode($slug);

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($paths as $path) echo '  <url><loc>' . e(siteUrl($path === 'index.php' ? '' : $path)) . '</loc></url>' . "\n";
echo '</urlset>' . "\n";
