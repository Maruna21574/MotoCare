<?php
require_once __DIR__ . '/inc/config.php';
$pageTitle = 'Košík — MotoCare produkty';
$activePage = 'shop';
$bodyClass = 'page-plain';
include __DIR__ . '/inc/header.php';
?>
<section class="co section-light">
    <div class="container">
        <nav class="pd-crumbs" aria-label="Omrvinková navigácia"><a href="./">Domov</a><span aria-hidden="true">/</span><a href="shop">E-shop</a><span aria-hidden="true">/</span><span aria-current="page">Košík</span></nav>
        <div class="co-head">
            <div><h1><?= t('cart.cart_title') ?></h1><p><?= t('cart.cart_text') ?></p></div>
            <ol class="co-progress" aria-label="Postup objednávky"><li class="is-active"><span>1</span>Košík</li><li><span>2</span>Údaje</li><li><span>3</span>Hotovo</li></ol>
        </div>

        <div class="co-grid" id="cart-layout">
            <div class="cart-list" id="cart-page-items"></div>
            <aside class="co-summary">
                <div class="co-summary-head"><h2>Súhrn</h2></div>
                <dl class="co-totals">
                    <div><dt>Produkty</dt><dd id="cart-page-count">0 ks</dd></div>
                    <div><dt>Doprava</dt><dd>Dohodou</dd></div>
                    <div><dt>Platba</dt><dd>Na dobierku</dd></div>
                    <div class="co-total"><dt>Spolu</dt><dd id="cart-page-total">0 €</dd></div>
                </dl>
                <a class="button button--orange cart-checkout" id="cart-checkout-link" href="objednavka">Pokračovať k objednávke</a>
                <a class="cart-continue" href="shop"><span aria-hidden="true">←</span> Pokračovať v nákupe</a>
                <ul class="co-perks">
                    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"></path></svg>Zaplatíš až pri prevzatí</li>
                    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"></path></svg>Potvrdenie objednávky e-mailom</li>
                    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"></path></svg>Radi poradíme s výberom</li>
                </ul>
            </aside>
        </div>

        <div class="co-card cart-empty" id="cart-empty" hidden>
            <span class="cart-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3.5 4.5h2l1.7 10.2a2 2 0 0 0 2 1.7h7.7a2 2 0 0 0 1.9-1.5L20.5 8H6.4"></path><circle cx="9.5" cy="20" r="1.2"></circle><circle cx="17.2" cy="20" r="1.2"></circle></svg></span>
            <h2><?= t('cart.empty_title') ?></h2>
            <p><?= t('cart.empty_text') ?></p>
            <a class="button button--orange" href="shop">Pozrieť produkty</a>
        </div>
    </div>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>
