<?php
require_once __DIR__ . '/inc/config.php';
$pageTitle = 'Služby starostlivosti — MotoCare Pro';
$activePage = 'sluzby';
include __DIR__ . '/inc/header.php';
?>
<?php $hero = ['title' => t('services.hero_title'), 'text' => textRaw('services.hero_text'), 'crumbs' => [['Služby', null]], 'video' => '4922586']; include __DIR__ . '/inc/page-hero.php'; ?>

<section class="svc-list section-light">
    <div class="container">
        <div class="svc-intro">
            <p class="overline"><?= t('services.intro_overline') ?></p>
            <p><?= t('services.intro_text') ?></p>
        </div>
        <?php include __DIR__ . '/inc/services-list.php'; ?>
    </div>
</section>

<section class="svc-process section-dark">
    <div class="container">
        <div class="svc-section-head"><p class="eyebrow"><span class="eyebrow-line"></span> <?= t('services.process_eyebrow') ?></p><h2><?= t('services.process_title') ?></h2></div>
        <ol class="svc-process-steps">
            <?php foreach (tl('services.process_steps') as $index => [$title, $text]): ?>
            <li><span><?= sprintf('%02d', $index + 1) ?></span><h3><?= e($title) ?></h3><p><?= e($text) ?></p></li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<section class="svc-cta section-orange">
    <div class="container svc-cta-inner">
        <div><p class="svc-cta-kicker"><?= t('services.cta_kicker') ?></p><h2><?= t('services.cta_title') ?></h2><p><?= t('services.cta_text') ?></p></div>
        <div class="svc-cta-actions"><a class="button button--dark" href="kontakt">Napísať nám</a><a class="svc-cta-phone" href="<?= e(phoneHref()) ?>"><?= e($site['phone']) ?></a></div>
    </div>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>
