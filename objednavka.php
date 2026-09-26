<?php
require_once __DIR__ . '/inc/config.php';
$pageTitle = 'Objednávka na dobierku — MotoCare produkty';
$activePage = 'shop';
$bodyClass = 'page-plain';
include __DIR__ . '/inc/header.php';
$icon = static fn (string $paths): string => '<span class="co-section-icon"><svg viewBox="0 0 24 24" aria-hidden="true">' . $paths . '</svg></span>';
?>
<section class="co section-light">
    <div class="container">
        <nav class="pd-crumbs" aria-label="Omrvinková navigácia"><a href="index.php">Domov</a><span aria-hidden="true">/</span><a href="shop.php">Produkty</a><span aria-hidden="true">/</span><a href="kosik.php">Košík</a><span aria-hidden="true">/</span><span aria-current="page">Objednávka</span></nav>
        <div class="co-head">
            <div><h1><?= t('cart.checkout_title') ?></h1><p><?= t('cart.checkout_text') ?></p></div>
            <ol class="co-progress" aria-label="Postup objednávky"><li class="is-done"><span>✓</span>Košík</li><li class="is-active" id="co-step-details"><span>2</span>Údaje</li><li id="co-step-done"><span>3</span>Hotovo</li></ol>
        </div>

        <div class="co-grid">
            <div class="co-main">
                <form id="checkout-form" class="co-card">
                    <?= spamFields() ?>
                    <fieldset class="co-section">
                        <legend><?= $icon('<circle cx="12" cy="8" r="4"></circle><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"></path>') ?>Kontakt</legend>
                        <div class="book-fields">
                            <label class="book-field-wide">Meno a priezvisko<input type="text" name="name" autocomplete="name" required></label>
                            <label>Telefón<input type="tel" name="phone" autocomplete="tel" placeholder="+421 ..." required></label>
                            <label>E-mail<input type="email" name="email" autocomplete="email" required></label>
                        </div>
                    </fieldset>

                    <fieldset class="co-section">
                        <legend><?= $icon('<path d="M12 21s7-6.1 7-12A7 7 0 0 0 5 9c0 5.9 7 12 7 12Z"></path><circle cx="12" cy="9" r="2.3"></circle>') ?>Doručenie</legend>
                        <div class="book-fields">
                            <label class="book-field-wide">Adresa doručenia<input type="text" name="address" autocomplete="street-address" placeholder="Ulica a číslo, PSČ, mesto" required></label>
                        </div>
                        <p class="co-hint">Spôsob a cenu dopravy ti potvrdíme po prijatí objednávky.</p>
                    </fieldset>

                    <fieldset class="co-section">
                        <legend><?= $icon('<rect x="4" y="3" width="16" height="18" rx="2"></rect><path d="M8 8h8M8 12h8M8 16h5"></path>') ?>Fakturácia a poznámka</legend>
                        <div class="book-fields">
                            <label class="book-option book-field-wide">
                                <input type="checkbox" data-company-toggle>
                                <span class="book-option-box" aria-hidden="true"></span>
                                <span class="book-option-text"><strong>Nakupujem na firmu</strong><small>Doplníš IČO a DIČ pre faktúru.</small></span>
                            </label>
                            <label data-company-field hidden>IČO<input type="text" name="ico" autocomplete="organization"></label>
                            <label data-company-field hidden>DIČ<input type="text" name="dic"></label>
                            <label class="book-field-wide"><span class="book-label">Poznámka k objednávke <small>nepovinné</small></span><textarea name="note" rows="3" placeholder="Napríklad kedy ťa zastihne kuriér"></textarea></label>
                        </div>
                    </fieldset>

                    <div class="co-submit">
                        <label class="book-option book-consent">
                            <input type="checkbox" name="consent" value="1" required>
                            <span class="book-option-box" aria-hidden="true"></span>
                            <span class="book-option-text">Súhlasím so <a href="gdpr.php" target="_blank">spracovaním osobných údajov</a> za účelom vybavenia objednávky.</span>
                        </label>
                        <button class="button button--orange co-submit-button" type="submit">Odoslať objednávku na dobierku</button>
                        <p class="form-message" id="checkout-message" role="status" aria-live="polite"></p>
                        <a class="shop-card-detail" href="kosik.php"><span aria-hidden="true">←</span> Späť do košíka</a>
                    </div>
                </form>

                <div class="co-card co-done" id="checkout-done" hidden>
                    <div class="co-done-top">
                        <span class="co-done-badge"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 12.5 4 4 8-9"></path></svg>Objednávka prijatá</span>
                        <h2><?= t('cart.done_title') ?></h2>
                        <p id="checkout-done-text"></p>
                    </div>
                    <div class="co-done-ticket">
                        <div class="co-done-ticket-main"><small>Číslo objednávky</small><strong id="checkout-done-id">—</strong></div>
                        <dl>
                            <div><dt>Suma</dt><dd id="checkout-done-total">—</dd></div>
                            <div><dt>Platba</dt><dd>Na dobierku</dd></div>
                            <div><dt>Doprava</dt><dd>Dohodou</dd></div>
                        </dl>
                    </div>
                    <h3 class="co-done-subtitle">Čo bude nasledovať</h3>
                    <ol class="co-done-steps">
                        <li><span>01</span><strong>Potvrdenie e-mailom</strong><small>Rekapituláciu nájdeš vo svojej schránke.</small></li>
                        <li><span>02</span><strong>Ozveme sa ti</strong><small>Dohodneme spôsob a cenu doručenia.</small></li>
                        <li><span>03</span><strong>Zaplatíš pri prevzatí</strong><small>Priamo kuriérovi pri doručení.</small></li>
                    </ol>
                    <div class="co-done-actions"><a class="button button--orange" href="shop.php">Pokračovať v nákupe</a><a class="book-back" href="index.php">Späť na úvod</a></div>
                </div>
            </div>

            <aside class="co-summary">
                <div class="co-summary-head"><h2>Tvoja objednávka</h2><a href="kosik.php">Upraviť</a></div>
                <div id="checkout-items" class="co-items"></div>
                <dl class="co-totals">
                    <div><dt>Doprava</dt><dd>Dohodou</dd></div>
                    <div><dt>Platba</dt><dd>Na dobierku</dd></div>
                    <div class="co-total"><dt>Spolu</dt><dd id="checkout-total">0 €</dd></div>
                </dl>
                <ul class="co-perks">
                    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"></path></svg>Zaplatíš až pri prevzatí</li>
                    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"></path></svg>Potvrdenie objednávky e-mailom</li>
                    <li><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"></path></svg>Radi poradíme s použitím</li>
                </ul>
            </aside>
        </div>
    </div>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>
