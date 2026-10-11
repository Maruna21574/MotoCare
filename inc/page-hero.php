<?php
// Subpage hero. Expects $hero: title (trusted HTML), text, crumbs [[label, href|null], ...], video (file id from vid/).
$heroVideo = $hero['video'] ?? null;
?>
<section class="sub-hero section-dark">
    <?php include __DIR__ . '/hero-video.php'; ?>
    <div class="container sub-hero-inner">
        <nav class="sub-hero-crumbs" aria-label="Omrvinková navigácia"><a href="./">Domov</a><?php foreach ($hero['crumbs'] as [$label, $href]): ?><span aria-hidden="true">/</span><?php if ($href): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php else: ?><span aria-current="page"><?= e($label) ?></span><?php endif; ?><?php endforeach; ?></nav>
        <div class="sub-hero-body"><h1><?= $hero['title'] ?></h1><?php if (!empty($hero['text'])): ?><p class="sub-hero-text"><?= e($hero['text']) ?></p><?php endif; ?></div>
    </div>
</section>
