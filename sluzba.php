<?php
require_once __DIR__ . '/inc/config.php';
$slug = cleanText($_GET['slug'] ?? '', 40);
if (!isset($services[$slug])) { header('Location: sluzby.php'); exit; }
$service = $services[$slug];
$selectedService = $slug;
$pageTitle = $service['name'] . ' — MotoCare Pro';
$activePage = 'sluzby';
include __DIR__ . '/inc/header.php';
$checkIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"></path></svg>';
$sparkIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M18.4 5.6l-2.8 2.8M8.4 15.6l-2.8 2.8"></path></svg>';
?>
<section class="svc-hero section-dark">
    <div class="svc-hero-bg" style="background-image: url('<?= e($service['image']) ?>')" aria-hidden="true"></div>
    <div class="container svc-hero-grid">
        <div class="svc-hero-copy">
            <nav class="sub-hero-crumbs" aria-label="Omrvinková navigácia"><a href="index.php">Domov</a><span aria-hidden="true">/</span><a href="sluzby.php">Služby</a><span aria-hidden="true">/</span><span aria-current="page"><?= e($service['name']) ?></span></nav>
            <p class="svc-label"><?= e($service['label']) ?></p>
            <h1><?= e($service['name']) ?></h1>
            <p class="svc-hero-text"><?= e($service['description']) ?></p>
            <ul class="svc-hero-meta">
                <li><small>Cena od</small><strong><?= (int) $service['price'] ?> €</strong></li>
                <li><small>Trvanie</small><strong><?= e(durationLabel((int) $service['minutes'])) ?></strong></li>
                <li><small>Vyzdvihnutie</small><strong><?= t('service.pickup') ?></strong></li>
            </ul>
            <div class="svc-actions"><a class="button button--orange" href="#rezervacia">Rezervovať termín</a><a class="text-link" href="kontakt.php">Chcem sa poradiť</a></div>
        </div>
        <figure class="svc-hero-media"><img src="<?= e($service['image']) ?>" alt="<?= e($service['name']) ?> — MotoCare Pro"></figure>
    </div>
</section>

<section class="svc-about section-light">
    <div class="container svc-about-grid">
        <div class="svc-about-copy reveal">
            <p class="overline"><?= t('service.about_overline') ?></p>
            <h2><?= e($service['name']) ?><br><em><?= e(mb_strtolower($service['label'])) ?>.</em></h2>
            <?php foreach ($service['intro'] ?? [] as $index => $paragraph): ?><p<?= $index === 0 ? ' class="svc-about-lead"' : '' ?>><?= e($paragraph) ?></p><?php endforeach; ?>
        </div>
        <aside class="svc-about-aside reveal">
            <div class="svc-about-box">
                <h3>Pre koho je vhodná</h3>
                <ul><?php foreach ($service['ideal'] ?? [] as $item): ?><li><?= $checkIcon ?><?= e($item) ?></li><?php endforeach; ?></ul>
            </div>
            <div class="svc-about-box svc-about-box--dark">
                <h3>Čo je v cene</h3>
                <ul><?php foreach ($service['features'] as $feature): ?><li><?= $checkIcon ?><?= e($feature) ?></li><?php endforeach; ?></ul>
                <p class="svc-about-price"><small>od</small><strong><?= (int) $service['price'] ?> €</strong></p>
            </div>
        </aside>
    </div>
</section>

<section class="svc-why section-dark">
    <div class="container">
        <div class="svc-section-head"><p class="eyebrow"><span class="eyebrow-line"></span> <?= t('service.why_eyebrow') ?></p><h2>Čo ti <?= e($service['name']) ?><br><em>prinesie.</em></h2></div>
        <div class="svc-why-grid">
            <?php foreach ($service['why'] ?? [] as [$title, $text]): ?>
            <article class="svc-why-card reveal"><span class="svc-why-icon"><?= $sparkIcon ?></span><h3><?= e($title) ?></h3><p><?= e($text) ?></p></article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="svc-steps section-light">
    <div class="container">
        <div class="svc-section-head"><p class="overline"><?= t('service.steps_overline') ?></p><h2><?= t('service.steps_title') ?></h2><p><?= t('service.steps_text') ?></p></div>
        <ol class="svc-step-grid">
            <?php foreach ($service['details'] as $detail): ?>
            <li class="reveal"><span class="svc-step-mark" aria-hidden="true"></span><p><?= e($detail) ?></p></li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

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
        <div class="section-heading"><div><p class="overline"><?= t('service.related_overline') ?></p><h2><?= t('service.related_title') ?></h2></div><a class="underlined-link" href="sluzby.php">Všetky služby</a></div>
        <div class="svc-related-grid">
            <?php foreach ($services as $id => $item): if ($id === $slug) continue; ?>
            <a class="svc-related-card" href="sluzba.php?slug=<?= e($id) ?>">
                <span class="svc-related-media"><img loading="lazy" src="<?= e($item['image']) ?>" alt=""></span>
                <span class="svc-related-body"><small><?= e($item['label']) ?></small><strong><?= e($item['name']) ?></strong><span><?= e($item['short']) ?></span><b>od <?= (int) $item['price'] ?> € <i aria-hidden="true">→</i></b></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>
