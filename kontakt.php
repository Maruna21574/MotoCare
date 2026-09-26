<?php
require_once __DIR__ . '/inc/config.php';
$pageTitle = 'Kontakt — MotoCare Pro';
$activePage = 'kontakt';
include __DIR__ . '/inc/header.php';
$phoneHref = phoneHref();
?>
<?php $hero = ['title' => t('contact.hero_title'), 'text' => textRaw('contact.hero_text'), 'crumbs' => [['Kontakt', null]], 'video' => '4922588']; include __DIR__ . '/inc/page-hero.php'; ?>

<section class="ct section-light">
    <div class="container">
        <div class="ct-quick">
            <a class="ct-quick-card" href="<?= e($phoneHref) ?>">
                <span class="ct-quick-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 3.5 9 3l2 4.7-1.8 1.5a14.4 14.4 0 0 0 5.6 5.6l1.5-1.8 4.7 2-.5 2.4c-.3 1.4-1.6 2.4-3 2.3C10.2 19.1 4.9 13.8 4.3 6.5c-.1-1.4.9-2.7 2.3-3Z"></path></svg></span>
                <small>Zavolaj</small><strong><?= e($site['phone']) ?></strong><span class="ct-quick-arrow" aria-hidden="true">→</span>
            </a>
            <a class="ct-quick-card" href="mailto:<?= e($site['email']) ?>">
                <span class="ct-quick-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="1.5"></rect><path d="m4 7 8 6 8-6"></path></svg></span>
                <small>Napíš e-mail</small><strong><?= e($site['email']) ?></strong><span class="ct-quick-arrow" aria-hidden="true">→</span>
            </a>
            <a class="ct-quick-card ct-quick-card--accent" href="rezervacia.php">
                <span class="ct-quick-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"></rect><path d="M8 3v4M16 3v4M4 9h16M8 13h3M8 16h5"></path></svg></span>
                <small>Rezervácia</small><strong>Vybrať termín online</strong><span class="ct-quick-arrow" aria-hidden="true">→</span>
            </a>
        </div>

        <div class="ct-grid">
            <div class="ct-card">
                <div id="contact-form-wrap">
                    <div class="ct-card-head">
                        <p class="overline"><?= t('contact.form_overline') ?></p>
                        <h2><?= t('contact.form_title') ?></h2>
                        <p><?= t('contact.form_text') ?></p>
                    </div>
                    <form id="contact-form" class="ct-form" novalidate>
                        <?= spamFields() ?>
                        <div class="book-fields">
                            <label>Meno a priezvisko<input type="text" name="name" autocomplete="name" required></label>
                            <label><span class="book-label">Telefón <small>nepovinné</small></span><input type="tel" name="phone" autocomplete="tel" placeholder="+421 ..."></label>
                            <label class="book-field-wide">E-mail<input type="email" name="email" autocomplete="email" required></label>
                            <label class="book-field-wide">Správa<textarea name="message" rows="5" maxlength="1200" placeholder="Napríklad model motorky, stav laku alebo čo by si chcel riešiť" required></textarea></label>
                            <div class="ct-upload book-field-wide" data-photo-upload>
                                <span class="book-label">Fotky motorky <small>nepovinné, najviac 4</small></span>
                                <label class="ct-drop">
                                    <input type="file" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" multiple data-photo-input>
                                    <span class="ct-drop-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h1.8l1.4-2h4.6l1.4 2h1.8A2.5 2.5 0 0 1 20 8.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5Z"></path><circle cx="12" cy="13" r="3.5"></circle></svg></span>
                                    <span class="ct-drop-text"><strong>Pridaj fotky</strong> alebo ich sem presuň<small>JPG, PNG, WEBP alebo HEIC · max. 8 MB na fotku</small></span>
                                </label>
                                <ul class="ct-previews" data-photo-previews></ul>
                            </div>
                            <label class="book-option book-consent book-field-wide">
                                <input type="checkbox" name="consent" value="1" required>
                                <span class="book-option-box" aria-hidden="true"></span>
                                <span class="book-option-text">Súhlasím so <a href="gdpr.php" target="_blank">spracovaním osobných údajov</a> za účelom vybavenia mojej správy.</span>
                            </label>
                        </div>
                        <div class="ct-submit">
                            <button class="button button--orange" type="submit">Odoslať správu</button>
                            <p id="contact-message" class="form-message" role="status" aria-live="polite"></p>
                        </div>
                    </form>
                </div>

                <div class="ct-done" id="contact-done" hidden>
                    <span class="co-done-badge"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 12.5 4 4 8-9"></path></svg>Správa odoslaná</span>
                    <h2><?= t('contact.done_title') ?></h2>
                    <p id="contact-done-text">Potvrdenie sme ti poslali e-mailom. Zvyčajne odpovedáme do 24 hodín.</p>
                    <div class="ct-done-actions"><button class="button button--orange" type="button" id="contact-again">Napísať ďalšiu správu</button><a class="book-back" href="index.php">Späť na úvod</a></div>
                </div>
            </div>

            <aside class="ct-aside">
                <div class="ct-aside-block">
                    <p class="ct-aside-label">Štúdio</p>
                    <h3><?= e($site['name']) ?></h3>
                    <p class="ct-aside-address"><?= e($site['street']) ?><br><?= e(trim($site['zip'] . ' ' . $site['city'] . ($site['country'] !== '' ? ', ' . $site['country'] : ''))) ?></p>
                    <a class="ct-aside-link" href="#kontaktna-mapa">Zobraziť na mape <span aria-hidden="true">↓</span></a>
                </div>
                <div class="ct-aside-block">
                    <p class="ct-aside-label">Priamy kontakt</p>
                    <a class="ct-aside-row" href="<?= e($phoneHref) ?>"><small>Telefón</small><strong><?= e($site['phone']) ?></strong></a>
                    <a class="ct-aside-row" href="mailto:<?= e($site['email']) ?>"><small>E-mail</small><strong><?= e($site['email']) ?></strong></a>
                </div>
                <div class="ct-aside-block">
                    <p class="ct-aside-label">Sleduj nás</p>
                    <div class="ct-socials">
                        <a href="<?= e($site['instagram']) ?>" target="_blank" rel="noopener">Instagram <span aria-hidden="true">↗</span></a>
                        <a href="<?= e($site['facebook']) ?>" target="_blank" rel="noopener">Facebook <span aria-hidden="true">↗</span></a>
                    </div>
                </div>
                <p class="ct-aside-tip"><strong>Tip:</strong> <?= t('contact.aside_tip') ?></p>
            </aside>
        </div>
    </div>
</section>

<section id="kontaktna-mapa" class="ct-map section-dark">
    <div class="container">
        <div class="ct-map-frame">
            <iframe src="<?= e($site['map_embed']) ?>" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" title="MotoCare Pro na mape"></iframe>
            <div class="ct-map-card">
                <p class="eyebrow"><span class="eyebrow-line"></span> <?= t('contact.map_eyebrow') ?></p>
                <h2><?= t('contact.map_title') ?></h2>
                <p><?= t('contact.map_text') ?></p>
                <a class="button button--orange" href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= e(rawurlencode(siteAddress())) ?>" target="_blank" rel="noopener">Navigovať</a>
            </div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>
