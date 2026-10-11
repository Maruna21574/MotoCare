<?php
require_once __DIR__ . '/inc/config.php';
$pageTitle = 'MotoCare produkty — vybavenie pre údržbu';
$activePage = 'shop';
include __DIR__ . '/inc/header.php';
$benefitIcons = [
    '<path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"></path><path d="m9 12 2 2 4-4"></path>',
    '<path d="M9 6h11M9 12h11M9 18h11"></path><path d="m3.5 6 1.5 1.5L7.5 5M3.5 12l1.5 1.5 2.5-2.5M3.5 18l1.5 1.5 2.5-2.5"></path>',
    '<rect x="3" y="6" width="18" height="12" rx="2"></rect><path d="M3 10h18M7 15h3"></path>',
    '<path d="M4 5h16v11H9l-5 4z"></path><path d="M8 9.5h8M8 12.5h5"></path>',
];
?>
<?php $hero = ['title' => t('shop.hero_title'), 'text' => textRaw('shop.hero_text'), 'crumbs' => [['E-shop', null]], 'video' => '5052771']; include __DIR__ . '/inc/page-hero.php'; ?>

<section class="shop-list section-light">
    <div class="container">
        <?php $shopTab = "care"; include __DIR__ . "/inc/shop-switch.php"; ?>
        <div class="svc-intro">
            <p class="overline"><?= t('shop.intro_overline') ?></p>
            <p><?= t('shop.intro_text') ?></p>
        </div>
        <div class="shop-cards">
            <?php foreach ($products as $id => $product) include __DIR__ . '/inc/product-card.php'; ?>
        </div>
    </div>
</section>


<section class="shop-benefits-v2 section-light">
    <div class="container">
        <div class="shop-benefit-row">
            <?php foreach (tl('shop.benefits') as $index => [$title, $text]): $icon = $benefitIcons[$index % count($benefitIcons)]; ?>
            <div class="shop-benefit"><span class="shop-benefit-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg></span><div><h3><?= e($title) ?></h3><p><?= e($text) ?></p></div></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>
