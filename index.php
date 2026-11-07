<?php
require_once __DIR__ . '/inc/config.php';
$pageTitle = 'MotoCare Pro — Prémiová starostlivosť pre tvoju motorku.';
$activePage = 'home';
$bodyClass = 'home-page';
include __DIR__ . '/inc/header.php';
?>

<section class="home-hero section-dark" id="hero"><video class="home-hero-video" autoplay muted loop playsinline preload="metadata" poster="vid/posters/4922669.jpg" aria-hidden="true"><source src="vid/4922669-hd_1920_1080_30fps.mp4" type="video/mp4"></video><div class="container home-hero-inner"><div class="home-hero-copy"><h1><?= t('home.hero_title') ?></h1><p class="home-hero-text"><?= t('home.hero_text') ?></p><div class="hero-actions"><a class="button button--orange" href="rezervacia">Rezervovať termín</a><a href="sluzby" class="text-link">Pozrieť služby</a></div></div></div></section>

<section class="svc-list svc-list--home svc-list--dark section-dark" id="sluzby"><div class="container"><div class="svc-intro svc-intro--home reveal"><div><p class="eyebrow"><span class="eyebrow-line"></span> <?= t('home.services_eyebrow') ?></p><h2><?= t('home.services_title') ?></h2></div><p><?= t('home.services_text') ?></p></div>
<?php include __DIR__ . '/inc/services-list.php'; ?>
</div></section>

<section class="home-result section-light section-pad"><div class="container"><div class="section-heading reveal"><div><p class="overline"><?= t('home.result_overline') ?></p><h2><?= t('home.result_title') ?></h2></div><a class="underlined-link home-more-top" href="galeria">Pozrieť galériu</a></div></div><?php include __DIR__ . '/inc/gallery-slider.php'; ?><div class="container home-more-bottom"><a class="underlined-link" href="galeria">Pozrieť galériu</a></div></section>

<section class="home-shop section-dark section-pad"><div class="container"><div class="section-heading section-heading--light reveal"><div><p class="eyebrow"><span class="eyebrow-line"></span> <?= t('home.shop_eyebrow') ?></p><h2><?= t('home.shop_title') ?></h2></div><a class="underlined-link underlined-link--light home-more-top" href="shop">Pozrieť všetky produkty</a></div><div class="shop-cards home-shop-cards"><?php foreach (array_slice($products, 0, 3, true) as $id => $product) include __DIR__ . '/inc/product-card.php'; ?></div><div class="home-more-bottom"><a class="underlined-link underlined-link--light" href="shop">Pozrieť všetky produkty</a></div></div></section>

<section class="home-cta section-dark section-pad"><div class="container"><div class="final-cta-wrap"><div class="final-cta-card"><div class="final-cta-copy"><p class="final-cta-kicker"><?= t('home.cta_kicker') ?></p><h2><?= t('home.cta_title') ?></h2><p class="final-cta-text"><?= t('home.cta_text') ?></p><div class="final-cta-actions"><a class="final-cta-phone" href="tel:<?= e(str_replace(' ', '', $site['phone'])) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 3.5 9 3l2 4.7-1.8 1.5a14.4 14.4 0 0 0 5.6 5.6l1.5-1.8 4.7 2-.5 2.4c-.3 1.4-1.6 2.4-3 2.3C10.2 19.1 4.9 13.8 4.3 6.5c-.1-1.4.9-2.7 2.3-3Z"></path></svg><span><?= e($site['phone']) ?></span></a><a class="final-cta-email" href="mailto:<?= e($site['email']) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="1.5"></rect><path d="m4 7 8 6 8-6"></path></svg><span>Napísať správu</span></a></div></div></div></div></div></section>

<?php include __DIR__ . '/inc/footer.php'; ?>
