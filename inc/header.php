<?php
require_once __DIR__ . '/config.php';
$pageTitle = $pageTitle ?? 'MotoCare Pro — Jazdi s hrdosťou';
$activePage = $activePage ?? '';
?>
<!doctype html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($pageDescription ?? $site['description']) ?>">
    <meta name="theme-color" content="#060606">
    <link rel="icon" type="image/png" sizes="32x32" href="img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="img/favicon/icon-192.png">
    <link rel="apple-touch-icon" href="img/favicon/apple-touch-icon.png">
    <title><?= e($pageTitle) ?></title>
    <?php $pageUrl = siteUrl(basename($_SERVER['SCRIPT_NAME'] ?? 'index.php') . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '')); ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($site['name']) ?>">
    <meta property="og:locale" content="sk_SK">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription ?? $site['description']) ?>">
    <meta property="og:url" content="<?= e($pageUrl) ?>">
    <meta property="og:image" content="<?= e(siteUrl('img/og_clean.jpg')) ?>">
    <meta property="og:image:width" content="2048">
    <meta property="og:image:height" content="762">
    <meta property="og:image:alt" content="<?= e($site['name']) ?> — starostlivosť o motocykle">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= (int) filemtime(__DIR__ . '/../style.css') ?>">
    <link rel="stylesheet" href="compact.css?v=<?= (int) filemtime(__DIR__ . '/../compact.css') ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
    <div class="announcement contact-bar"><div class="contact-bar-side"><a class="contact-bar-link" href="<?= e(phoneHref()) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 3.5 9 3l2 4.7-1.8 1.5a14.4 14.4 0 0 0 5.6 5.6l1.5-1.8 4.7 2-.5 2.4c-.3 1.4-1.6 2.4-3 2.3C10.2 19.1 4.9 13.8 4.3 6.5c-.1-1.4.9-2.7 2.3-3Z"></path></svg><span><?= e($site['phone']) ?></span></a><a class="contact-bar-link" href="mailto:<?= e($site['email']) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="1.5"></rect><path d="m4 7 8 6 8-6"></path></svg><span><?= e($site['email']) ?></span></a></div><div class="contact-bar-socials"><a class="contact-bar-social" href="<?= e($site['facebook']) ?>" target="_blank" rel="noreferrer" aria-label="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.4 21v-8h2.7l.4-3h-3.1V8.1c0-.9.3-1.5 1.6-1.5h1.7V4a22 22 0 0 0-2.4-.1c-2.4 0-4 1.5-4 4.1V10H8.6v3h2.7v8h3.1Z"></path></svg></a><a class="contact-bar-social" href="<?= e($site['instagram']) ?>" target="_blank" rel="noreferrer" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4.2"></circle><circle class="social-dot" cx="17.4" cy="6.6" r="1"></circle></svg></a></div></div>
    <header class="site-header" id="top">
        <div class="container nav-wrap">
            <a href="index.php" class="brand" aria-label="Domov MotoCare Pro"><span class="brand-logo"><img src="auto_care_logo.png" alt="Logo MotoCare Pro"></span></a>
            <nav class="desktop-nav" aria-label="Hlavná navigácia">
                <a class="<?= $activePage === 'sluzby' ? 'is-active' : '' ?>" href="sluzby.php">Služby</a>
                <a class="<?= $activePage === 'onas' ? 'is-active' : '' ?>" href="o-nas.php">O nás</a>
                <a class="<?= $activePage === 'galeria' ? 'is-active' : '' ?>" href="galeria.php">Práca</a>
                <a class="<?= $activePage === 'shop' ? 'is-active' : '' ?>" href="shop.php">Produkty</a>
                <a class="<?= $activePage === 'kontakt' ? 'is-active' : '' ?>" href="kontakt.php">Kontakt</a>
            </nav>
            <div class="nav-actions"><a class="cart-button" href="kosik.php" aria-label="Otvoriť košík" title="Košík"><svg class="cart-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 4.5h2l1.7 10.2a2 2 0 0 0 2 1.7h7.7a2 2 0 0 0 1.9-1.5L20.5 8H6.4"></path><circle cx="9.5" cy="20" r="1.2"></circle><circle cx="17.2" cy="20" r="1.2"></circle></svg><b id="cart-count">0</b></a><a class="button button--small button--orange" href="rezervacia.php">Rezervovať</a><button class="menu-toggle" id="menu-toggle" type="button" aria-label="Otvoriť menu" aria-controls="mobile-nav" aria-expanded="false"><i></i><i></i></button></div>
        </div>
        <div class="mobile-nav" id="mobile-nav"><a href="sluzby.php">Služby</a><a href="o-nas.php">O nás</a><a href="galeria.php">Práca</a><a href="shop.php">Produkty</a><a href="kontakt.php">Kontakt</a><a href="rezervacia.php">Rezervácia</a></div>
    </header>
    <main>
