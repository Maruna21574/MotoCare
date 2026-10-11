<?php
// robots.txt (served through a rewrite rule in .htaccess).
require_once __DIR__ . '/inc/config.php';
header('Content-Type: text/plain; charset=utf-8');
?>
User-agent: *
Disallow: /admin/
Disallow: /api.php
Disallow: /data/
Disallow: /kosik
Disallow: /objednavka

Sitemap: <?= siteUrl('sitemap.xml') ?>

