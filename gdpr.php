<?php
require_once __DIR__ . '/inc/config.php';
$pageTitle = 'Ochrana osobných údajov — MotoCare Pro';
include __DIR__ . '/inc/header.php';
?>
<?php $hero = ['title' => t('gdpr.hero_title'), 'text' => textRaw('gdpr.hero_text'), 'crumbs' => [['GDPR', null]], 'video' => '5052771']; include __DIR__ . '/inc/page-hero.php'; ?>
<section class="legal-page section-light section-pad"><div class="container legal-content"><p class="overline"><?= t('gdpr.overline') ?></p><h2><?= t('gdpr.title') ?></h2><?= t('gdpr.body') ?></div></section>
<?php include __DIR__ . '/inc/footer.php'; ?>
