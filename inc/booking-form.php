<?php
// Multistep booking form (service, date & time, contact, summary). Expects $selectedService (service id or '').
?>
<ol class="book-steps" id="book-steps" aria-label="Kroky rezervácie">
    <li data-step="1"><span>1</span>Služba</li>
    <li data-step="2"><span>2</span>Termín</li>
    <li data-step="3"><span>3</span>Údaje</li>
    <li data-step="4"><span>4</span>Zhrnutie</li>
</ol>

<form id="book-form" class="book-card" data-start-step="<?= $selectedService !== '' ? 2 : 1 ?>" novalidate>
    <input type="hidden" name="date" id="book-date">
    <input type="hidden" name="time" id="book-time">
    <?= spamFields() ?>

    <div class="book-panel" data-panel="1">
        <h2 class="book-title">Aká služba?</h2>
        <div class="book-services">
            <?php foreach ($services as $id => $service): ?>
            <label class="book-service">
                <input type="radio" name="service" value="<?= e($id) ?>" <?= $selectedService === $id ? 'checked' : '' ?>>
                <span class="book-service-body">
                    <small><?= e($service['label']) ?></small>
                    <strong><?= e($service['name']) ?></strong>
                    <span class="book-service-text"><?= e($service['short']) ?></span>
                    <span class="book-service-meta"><b>od <?= (int) $service['price'] ?> €</b><span><?= e(durationLabel((int) $service['minutes'])) ?></span></span>
                </span>
            </label>
            <?php endforeach; ?>
        </div>
        <div class="book-nav"><a class="book-back" href="sluzby">Porovnať služby</a><button class="button button--orange" type="button" data-next>Pokračovať</button></div>
    </div>

    <div class="book-panel" data-panel="2" hidden>
        <h2 class="book-title">Kedy ti to vyhovuje?</h2>
        <p class="book-picked" id="book-picked-service"></p>
        <div class="book-date-grid">
            <div class="book-calendar">
                <div class="book-cal-head"><button type="button" data-month="-1" aria-label="Predchádzajúci mesiac">‹</button><strong id="book-month-label"></strong><button type="button" data-month="1" aria-label="Nasledujúci mesiac">›</button></div>
                <div class="book-cal-week"><span>Po</span><span>Ut</span><span>St</span><span>Št</span><span>Pi</span><span>So</span><span>Ne</span></div>
                <div class="book-cal-days" id="book-days"></div>
                <div class="book-legend"><span><i class="is-available"></i>Voľné</span><span><i class="is-full"></i>Obsadené</span><span><i class="is-closed"></i>Zatvorené</span></div>
            </div>
            <div class="book-times">
                <h3 id="book-times-title">Vyber deň v kalendári</h3>
                <p class="book-times-note" id="book-times-note"></p>
                <div class="book-slots" id="book-slots"></div>
            </div>
        </div>
        <div class="book-nav"><button class="book-back" type="button" data-prev>Späť</button><button class="button button--orange" type="button" data-next>Pokračovať</button></div>
    </div>

    <div class="book-panel" data-panel="3" hidden>
        <h2 class="book-title">Kontakt na teba</h2>
        <div class="book-fields">
            <label>Meno a priezvisko<input type="text" name="name" autocomplete="name" required></label>
            <label>Telefón<input type="tel" name="phone" autocomplete="tel" placeholder="+421 ..." required></label>
            <label>E-mail<input type="email" name="email" autocomplete="email" required></label>
            <label><span class="book-label">Motorka <small>nepovinné</small></span><input type="text" name="bike" placeholder="napr. BMW R1200 GS"></label>
            <label class="book-option book-field-wide">
                <input type="checkbox" name="pickup" value="1" data-pickup>
                <span class="book-option-box" aria-hidden="true"></span>
                <span class="book-option-text"><strong>Mám záujem o vyzdvihnutie motorky</strong><small>Motorku vyzdvihneme na tvojej adrese. Čas a cenu dopravy ti potvrdíme spolu s termínom.</small></span>
            </label>
            <label class="book-field-wide" data-pickup-address hidden>Adresa vyzdvihnutia<input type="text" name="pickup_address" autocomplete="street-address" placeholder="Ulica, číslo, mesto"></label>
            <label class="book-field-wide"><span class="book-label">Poznámka <small>nepovinné</small></span><textarea name="note" rows="3" placeholder="Stav motorky, špeciálne požiadavky…"></textarea></label>
        </div>
        <div class="book-nav"><button class="book-back" type="button" data-prev>Späť</button><button class="button button--orange" type="button" data-next>Pokračovať</button></div>
    </div>

    <div class="book-panel" data-panel="4" hidden>
        <h2 class="book-title">Skontroluj rezerváciu</h2>
        <dl class="book-summary" id="book-summary"></dl>
        <label class="book-option book-consent">
            <input type="checkbox" name="consent" value="1" required>
            <span class="book-option-box" aria-hidden="true"></span>
            <span class="book-option-text">Súhlasím so <a href="gdpr" target="_blank">spracovaním osobných údajov</a> za účelom vybavenia rezervácie.</span>
        </label>
        <p class="form-message" id="book-message" role="status" aria-live="polite"></p>
        <div class="book-nav"><button class="book-back" type="button" data-prev>Späť</button><button class="button button--orange" type="submit">Odoslať rezerváciu</button></div>
    </div>

    <div class="book-panel book-done" data-panel="done" hidden>
        <span class="book-done-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m6 12.5 4 4 8-9"></path></svg></span>
        <h2 class="book-title">Ďakujeme, máme to.</h2>
        <p id="book-done-text"></p>
        <div class="book-done-actions"><a class="button button--orange" href="./">Späť na úvod</a><a class="book-back" href="sluzby">Pozrieť služby</a></div>
    </div>
</form>
