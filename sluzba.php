<?php
require_once __DIR__ . '/inc/config.php';
$slug = cleanText($_GET['slug'] ?? '', 80);
if (!isset($services[$slug])) { header('Location: sluzby'); exit; }
$service = $services[$slug];
$selectedService = $slug;
$pageTitle = $service['name'] . ' — MotoCare Pro';
$activePage = 'sluzby';
include __DIR__ . '/inc/header.php';
// Photos of finished work (best photos from the gallery), a different selection for every service.
$workByBike = [];
foreach ($sliderItems as $item) if (!empty($item['bike'])) $workByBike[$item['bike']][] = $item;
$work = [];
if (count($workByBike) >= 3) {
    // One photo from each of up to six different motorcycles; every service starts at another one.
    $bikeNames = array_keys($workByBike);
    $serviceIndex = (int) array_search($slug, array_keys($services), true);
    for ($i = 0; $i < min(6, count($bikeNames)); $i++) {
        $photos = $workByBike[$bikeNames[($serviceIndex + $i) % count($bikeNames)]];
        $work[] = $photos[$serviceIndex % count($photos)];
    }
}
?>
<section class="svc-hero section-dark">
    <div class="svc-hero-bg" style="background-image: url('<?= e($service['image']) ?>')" aria-hidden="true"></div>
    <div class="container svc-hero-grid">
        <div class="svc-hero-copy">
            <nav class="sub-hero-crumbs" aria-label="Omrvinková navigácia"><a href="./">Domov</a><span aria-hidden="true">/</span><a href="sluzby">Služby</a><span aria-hidden="true">/</span><span aria-current="page"><?= e($service['name']) ?></span></nav>
            <p class="svc-label"><?= e($service['label']) ?></p>
            <h1><?= e($service['name']) ?></h1>
            <p class="svc-hero-text"><?= e($service['description']) ?></p>
            <ul class="svc-hero-meta">
                <li><small>Cena od</small><strong><?= (int) $service['price'] ?> €</strong></li>
                <li><small>Trvanie</small><strong><?= e(durationLabel((int) $service['minutes'])) ?></strong></li>
                <li><small>Vyzdvihnutie</small><strong><?= t('service.pickup') ?></strong></li>
            </ul>
            <div class="svc-actions"><a class="button button--orange" href="#rezervacia">Rezervovať termín</a><a class="text-link" href="kontakt">Chcem sa poradiť</a></div>
        </div>
        <figure class="svc-hero-media"><img src="<?= e($service['image']) ?>" alt="<?= e($service['name']) ?> — MotoCare Pro"></figure>
    </div>
</section>

<?php $intro = $service['intro'] ?? []; $lead = array_shift($intro); ?>
<section class="svc-about section-light">
    <div class="container svc-about-grid">
        <div class="svc-about-copy reveal">
            <p class="overline"><?= t('service.about_overline') ?></p>
            <?php if ($lead !== null): ?><p class="svc-about-lead"><?= e($lead) ?></p><?php endif; ?>
            <details class="svc-more">
                <summary>Viac o službe</summary>
                <div class="svc-more-body">
                    <?php foreach ($intro as $paragraph): ?><p><?= e($paragraph) ?></p><?php endforeach; ?>
                    <?php if (!empty($service['ideal'])): ?><h3>Pre koho je vhodná</h3><p class="svc-ideal"><?= e(implode(' · ', $service['ideal'])) ?></p><?php endif; ?>
                    <?php if (!empty($service['why'])): ?>
                    <h3>Čo ti <?= e($service['name']) ?> prinesie</h3>
                    <dl class="svc-benefits">
                        <?php foreach ($service['why'] as [$title, $text]): ?><div><dt><?= e($title) ?></dt><dd><?= e($text) ?></dd></div><?php endforeach; ?>
                    </dl>
                    <?php endif; ?>
                </div>
            </details>
        </div>
        <aside class="svc-facts reveal">
            <h3>Čo je v cene</h3>
            <ul class="svc-lines"><?php foreach ($service['features'] as $feature): ?><li><?= e($feature) ?></li><?php endforeach; ?></ul>
            <div class="svc-facts-price"><p><small>od</small><strong><?= (int) $service['price'] ?> €</strong></p><a class="button button--orange" href="#rezervacia">Rezervovať termín</a></div>
        </aside>
    </div>
</section>

<section class="svc-process section-light">
    <div class="container">
        <p class="overline"><?= t('service.steps_overline') ?></p>
        <h2><?= t('service.steps_title') ?></h2>
        <ol class="svc-process-row">
            <?php foreach ($service['details'] as $stepIndex => $detail): ?>
            <li class="reveal"><span aria-hidden="true"><?= sprintf('%02d', $stepIndex + 1) ?></span><p><?= e($detail) ?></p></li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<?php if (count($work) >= 5): ?>
<section class="svc-work section-dark">
    <div class="container">
        <div class="svc-work-head">
            <div><p class="eyebrow"><span class="eyebrow-line"></span> <?= t('service.work_overline') ?></p><h2><?= t('service.work_title') ?></h2></div>
            <a class="svc-work-all" href="galeria">Celá galéria <span aria-hidden="true">→</span></a>
        </div>
        <div class="svc-bento">
            <?php foreach (array_slice($work, 0, 5) as $index => $item): $bikeName = (string) $item['bike']; ?>
            <a class="svc-bento-item svc-bento-item--<?= $index + 1 ?>" href="galeria?motorka=<?= e(slugify($bikeName)) ?>">
                <img loading="lazy" decoding="async" src="<?= e($index === 0 ? $item['src'] : ($item['thumb'] ?? $item['src'])) ?>" alt="<?= e($bikeName) ?> — MotoCare Pro">
                <span class="svc-bento-num" aria-hidden="true"><?= sprintf('%02d', $index + 1) ?></span>
                <span class="svc-bento-arrow" aria-hidden="true">↗</span>
                <span class="svc-bento-label"><?= e($bikeName) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="svc-booking book section-light" id="rezervacia">
    <div class="container book-wrap">
        <div class="book-head">
            <p class="overline">Online rezervácia</p>
            <h2 class="svc-booking-title"><?= t('service.booking_title') ?></h2>
            <p>Služba <strong><?= e($service['name']) ?></strong> je už vybraná. Vyber si deň a čas, vyplň kontakt a termín ti potvrdíme e-mailom.</p>
        </div>
        <?php include __DIR__ . '/inc/booking-form.php'; ?>
    </div>
</section>

<section class="svc-related section-light">
    <div class="container">
        <div class="section-heading"><div><p class="overline"><?= t('service.related_overline') ?></p><h2><?= t('service.related_title') ?></h2></div><a class="underlined-link" href="sluzby">Všetky služby</a></div>
        <div class="svc-related-grid">
            <?php foreach ($services as $id => $item): if ($id === $slug) continue; ?>
            <a class="svc-related-card" href="sluzba?slug=<?= e($id) ?>">
                <span class="svc-related-media"><img loading="lazy" src="<?= e($item['image']) ?>" alt=""></span>
                <span class="svc-related-body"><small><?= e($item['label']) ?></small><strong><?= e($item['name']) ?></strong><span><?= e($item['short']) ?></span><b>od <?= (int) $item['price'] ?> € <i aria-hidden="true">→</i></b></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>
