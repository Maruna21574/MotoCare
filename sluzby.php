<?php
require_once __DIR__ . '/inc/config.php';
$pageTitle = 'Služby starostlivosti — MotoCare Pro';
$activePage = 'sluzby';
include __DIR__ . '/inc/header.php';
?>
<?php $hero = ['title' => t('services.hero_title'), 'text' => textRaw('services.hero_text'), 'crumbs' => [['Služby', null]], 'video' => '4922586']; include __DIR__ . '/inc/page-hero.php'; ?>

<section class="svc-list section-light">
    <div class="container">
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
<?php include __DIR__ . '/inc/footer.php'; ?>
