<?php
require_once __DIR__ . '/inc/config.php';
$pageTitle = 'Obchodné podmienky — MotoCare Pro';
include __DIR__ . '/inc/header.php';
?>
<?php $hero = ['title' => t('vop.hero_title'), 'text' => textRaw('vop.hero_text'), 'crumbs' => [['Obchodné podmienky', null]], 'video' => '5052771']; include __DIR__ . '/inc/page-hero.php'; ?>
<section class="legal-page section-light section-pad"><div class="container legal-content"><p class="overline"><?= t('vop.overline') ?></p><h2><?= t('vop.title') ?></h2><?= t('vop.body') ?></div></section>
<?php include __DIR__ . '/inc/footer.php'; ?>
