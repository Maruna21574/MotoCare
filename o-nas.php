<?php
require_once __DIR__ . '/inc/config.php';
$pageTitle = 'O nás — MotoCare Pro';
$activePage = 'onas';
include __DIR__ . '/inc/header.php';
$tickerIcon = '<i class="ticker-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="m7.5 12.2 3 3 6-6"></path></svg></i>';
$icons = [
    '<path d="M4 16a8 8 0 1 1 16 0"></path><path d="m12 16 4.5-5"></path><circle cx="12" cy="16" r="1.2"></circle>',
    '<path d="M10 6h10M10 12h10M10 18h10"></path><path d="m3.5 6 1.5 1.5L7.5 5M3.5 12l1.5 1.5 2.5-2.5M3.5 18l1.5 1.5 2.5-2.5"></path>',
    '<path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6z"></path><path d="m9 12 2 2 4-4"></path>',
    '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"></path><circle cx="7" cy="17.5" r="1.8"></circle><circle cx="17" cy="17.5" r="1.8"></circle>',
    '<path d="M3 12V4h8l10 10-8 8z"></path><circle cx="7.5" cy="8.5" r="1.5"></circle>',
    '<path d="M4 5h16v11H9l-5 4z"></path><path d="M8 9.5h8M8 12.5h5"></path>',
];
$steps = tl('about.steps');
?>
<?php $hero = ['title' => t('about.hero_title'), 'text' => textRaw('about.hero_text'), 'crumbs' => [['O nás', null]], 'video' => '4922677']; include __DIR__ . '/inc/page-hero.php'; ?>
<div class="ticker" aria-hidden="true"><div class="ticker-track"><?php for ($copy = 0; $copy < 2; $copy++): ?><div class="ticker-group"<?= $copy === 1 ? ' aria-hidden="true"' : '' ?>><?php foreach (tl('about.ticker') as $word): ?><span><?= e(mb_strtoupper($word)) ?></span><?= $tickerIcon ?><?php endforeach; ?></div><?php endfor; ?></div></div>

<section class="about-intro section-light">
    <div class="container about-intro-grid">
        <div class="about-intro-copy reveal">
            <p class="overline"><?= t('about.intro_overline') ?></p>
            <h2><?= t('about.intro_title') ?></h2>
            <p class="about-lead"><?= t('about.intro_lead') ?></p>
            <?= t('about.intro_body') ?>
            <div class="about-intro-actions"><a class="button button--orange" href="rezervacia">Rezervovať termín</a><a class="svc-link" href="sluzby">Pozrieť služby <span aria-hidden="true">→</span></a></div>
        </div>
        <div class="about-collage reveal">
            <img class="about-collage-main" loading="lazy" src="<?= t('about.collage_main') ?>" alt="Motocykel po starostlivosti MotoCare Pro">
            <img class="about-collage-small" loading="lazy" src="<?= t('about.collage_small') ?>" alt="Detail motocykla">
            <p class="about-collage-note"><strong><?= t('about.collage_note_title') ?></strong><span><?= t('about.collage_note_text') ?></span></p>
        </div>
    </div>
</section>

<section class="about-benefits section-dark">
    <div class="container">
        <div class="svc-section-head"><p class="eyebrow"><span class="eyebrow-line"></span> <?= t('about.benefits_eyebrow') ?></p><h2><?= t('about.benefits_title') ?></h2><p><?= t('about.benefits_text') ?></p></div>
        <div class="about-benefit-grid">
            <?php foreach (tl('about.benefits') as $index => [$title, $text]): ?>
            <article class="about-benefit reveal"><span class="about-benefit-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><?= $icons[$index % count($icons)] ?></svg></span><h3><?= e($title) ?></h3><p><?= e($text) ?></p></article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="about-work section-light">
    <div class="container">
        <div class="svc-section-head"><p class="overline"><?= t('about.work_overline') ?></p><h2><?= t('about.work_title') ?></h2><p><?= t('about.work_text') ?></p></div>
        <div class="about-steps">
            <?php foreach ($steps as $index => [$title, $text]): $imageKey = 'about.step_image_' . min($index + 1, 4); ?>
            <article class="about-step<?= $index % 2 === 1 ? ' about-step--flip' : '' ?> reveal">
                <div class="about-step-media"><img loading="lazy" src="<?= t($imageKey) ?>" alt="<?= e($title) ?>"></div>
                <div class="about-step-copy"><h3><?= e($title) ?></h3><p><?= e($text) ?></p></div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="about-strip section-dark">
    <div class="about-strip-grid"><?php for ($i = 1; $i <= 4; $i++): ?><a href="galeria"><img loading="lazy" src="<?= t('about.strip_image_' . $i) ?>" alt="Ukážka z galérie MotoCare Pro"></a><?php endfor; ?></div>
    <div class="container about-strip-cta"><p><?= t('about.strip_text') ?></p><a class="button button--orange" href="galeria">Otvoriť galériu</a></div>
</section>

<section class="about-contact section-light">
    <div class="container">
        <div class="about-contact-card">
            <div><p class="overline"><?= t('about.contact_overline') ?></p><h2><?= t('about.contact_title') ?></h2><p><?= t('about.contact_text') ?></p></div>
            <div class="about-contact-actions"><a class="button button--orange" href="kontakt">Napísať správu</a><a class="about-contact-phone" href="<?= e(phoneHref()) ?>"><?= e($site['phone']) ?></a></div>
        </div>
    </div>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>
