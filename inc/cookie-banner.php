<?php
// Cookie consent banner, settings dialog and the "revisit consent" button (logic in consent.js).
$cookieCategories = [
    'necessary' => ['Nevyhnutné', 'Potrebné pre základné fungovanie webu, napríklad zapamätanie vašich volieb a obsahu košíka. Tieto súbory nemožno vypnúť a neukladajú osobné identifikačné údaje.', true],
    'functional' => ['Funkčné', 'Umožňujú zobraziť obsah tretích strán, napríklad mapu Google na stránke Kontakt.', false],
    'analytics' => ['Analytika', 'Pomáhajú pochopiť, ako návštevníci web používajú. Momentálne ich nepoužívame.', false],
    'advertising' => ['Reklama', 'Slúžia na zobrazenie prispôsobených reklám. Momentálne ich nepoužívame.', false],
];
?>
<div class="cookie-banner" id="cookie-banner" role="dialog" aria-modal="false" aria-labelledby="cookie-banner-title" hidden>
    <div class="cookie-banner-text">
        <strong id="cookie-banner-title">Používame cookies</strong>
        <p>Tento web používa nevyhnutné súbory cookie, aby fungoval správne. S vaším súhlasom môžeme zobraziť aj obsah tretích strán, napríklad mapu. Svoj výber môžete kedykoľvek zmeniť. <a href="cookies">Viac o cookies</a></p>
    </div>
    <div class="cookie-banner-actions">
        <button type="button" class="cookie-btn cookie-btn--ghost" data-cookie-open>Nastavenia</button>
        <button type="button" class="cookie-btn cookie-btn--ghost" data-cookie-reject>Odmietnuť</button>
        <button type="button" class="cookie-btn cookie-btn--solid" data-cookie-accept>Prijať všetky</button>
    </div>
</div>
<div class="cookie-dialog" id="cookie-dialog" hidden>
    <div class="cookie-dialog-box" role="dialog" aria-modal="true" aria-labelledby="cookie-dialog-title">
        <div class="cookie-dialog-head"><h2 id="cookie-dialog-title">Nastavenia súhlasu</h2><button type="button" class="cookie-close" data-cookie-close aria-label="Zavrieť">×</button></div>
        <div class="cookie-dialog-body">
            <p>Cookies sú malé súbory ukladané vo vašom zariadení. Vyberte, ktoré kategórie povolíte. <a href="cookies">Podrobný zoznam cookies</a></p>
            <?php foreach ($cookieCategories as $key => [$label, $text, $locked]): ?>
            <div class="cookie-cat">
                <div class="cookie-cat-head"><strong><?= e($label) ?></strong><?php if ($locked): ?><span class="cookie-always">Vždy aktívne</span><?php else: ?><label class="cookie-switch"><input type="checkbox" data-cookie-toggle="<?= e($key) ?>"><span class="cookie-slider"></span><span class="cookie-sr"><?= e($label) ?></span></label><?php endif; ?></div>
                <p><?= e($text) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="cookie-dialog-foot">
            <button type="button" class="cookie-btn cookie-btn--ghost" data-cookie-reject>Odmietnuť všetky</button>
            <button type="button" class="cookie-btn cookie-btn--ghost" data-cookie-save>Uložiť môj výber</button>
            <button type="button" class="cookie-btn cookie-btn--solid" data-cookie-accept>Prijať všetky</button>
        </div>
    </div>
</div>
<button type="button" class="cookie-revisit" id="cookie-revisit" data-cookie-settings aria-label="Nastavenia súhlasu s cookies" title="Nastavenia súhlasu" hidden><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9 4 4 0 0 1-4-4 4 4 0 0 1-4-4 1 1 0 0 0-1-1Z"></path><circle cx="9" cy="11" r=".9"></circle><circle cx="14" cy="15" r=".9"></circle><circle cx="8.5" cy="15.5" r=".9"></circle></svg></button>
<script src="consent.js?v=<?= (int) filemtime(__DIR__ . '/../consent.js') ?>"></script>
