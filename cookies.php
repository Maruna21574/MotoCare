<?php
require_once __DIR__ . '/inc/config.php';
$pageTitle = 'Cookies — MotoCare Pro';
include __DIR__ . '/inc/header.php';
?>
<?php $hero = ['title' => t('cookies.hero_title'), 'text' => textRaw('cookies.hero_text'), 'crumbs' => [['Cookies', null]], 'video' => '5052771']; include __DIR__ . '/inc/page-hero.php'; ?>
<section class="legal-page section-light section-pad"><div class="container legal-content"><p class="overline"><?= t('cookies.overline') ?></p><h2><?= t('cookies.title') ?></h2><?= t('cookies.body') ?></div></section>
<?php include __DIR__ . '/inc/footer.php'; ?>
