<?php
// Service blocks with photo, features, price and actions. Used on the homepage and sluzby.php.
$checkIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"></path></svg>';
$clockIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>';
$number = 0;
foreach ($services as $id => $service): $number++; ?>
<article class="svc-item<?= $number % 2 === 0 ? ' svc-item--flip' : '' ?> reveal">
    <a class="svc-media" href="sluzba.php?slug=<?= e($id) ?>">
        <img loading="lazy" src="<?= e($service['image']) ?>" alt="<?= e($service['name']) ?> — MotoCare Pro">
        <span class="svc-num"><?= sprintf('%02d', $number) ?></span>
        <?php if (($service['badge'] ?? '') !== ''): ?><span class="svc-badge"><?= e($service['badge']) ?></span><?php endif; ?>
    </a>
    <div class="svc-body">
        <p class="svc-label"><?= e($service['label']) ?></p>
        <h3 class="svc-name"><?= e($service['name']) ?></h3>
        <p class="svc-desc"><?= e($service['description']) ?></p>
        <ul class="svc-features"><?php foreach ($service['features'] as $feature): ?><li><?= $checkIcon ?><?= e($feature) ?></li><?php endforeach; ?></ul>
        <div class="svc-footer">
            <div class="svc-price"><small>od</small><strong><?= (int) $service['price'] ?> €</strong><span class="svc-chip"><?= $clockIcon ?><?= e(durationLabel((int) $service['minutes'])) ?></span></div>
            <div class="svc-actions"><a class="button button--orange" href="rezervacia.php?service=<?= e($id) ?>">Rezervovať</a><a class="svc-link" href="sluzba.php?slug=<?= e($id) ?>">Detail služby <span aria-hidden="true">→</span></a></div>
        </div>
    </div>
</article>
<?php endforeach; ?>
