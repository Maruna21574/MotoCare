<?php
require_once __DIR__ . '/inc/config.php';
$placeholder = 'produkty/fotka-coskoro.webp';

$key = cleanText($_GET['typ'] ?? '', 60);
$category = $frames[$key] ?? null;
$activePage = 'shop';
// Produktová časť kategórie (nastavuje sa v administrácii pri kategórii). Čo nie je vyplnené, doplnia všeobecné texty,
// takže každá kategória má rovnaké rozloženie: výhody, galéria a tri boxy.
$detail = $category && !empty($category['detail']['name']) ? $category['detail'] : null;
$label = $category ? ($detail ? trim(($detail['heading'] ?? '') . ' ' . $detail['name']) : $category['title']) : '';
$pageTitle = ($category ? ($detail ? $label : $category['title'] . ' na mieru') : 'Padacie rámy a stojany na motorky') . ' — MotoCare Pro';
$pageDescription = $detail && ($detail['lead'] ?? '') !== '' ? $detail['lead'] : ($category ? $category['text'] : 'Ručne vyrábané padacie rámy, nárazové tyče, stojany a stelaže na motorky. Vyrábame na mieru, na objednávku.');
include __DIR__ . '/inc/header.php';
$shopTab = 'frames';
?>
<?php if ($category): ?>
<?php
$photos = array_values($category['photos'] ?? []);
$lead = trim((string) ($detail['lead'] ?? '')) ?: $category['text'];
$specs = array_filter([
    'Značka' => $detail['brand'] ?? '',
    'Model' => $detail['model'] ?? '',
    'Modelové rady' => $detail['series'] ?? '',
    'Roky výroby' => $detail['years'] ?? '',
], static fn ($value): bool => trim((string) $value) !== '');
if ($specs === []) $specs = ['Typ produktu' => $category['title'], 'Model motorky' => 'na mieru podľa modelu', 'Výroba' => 'ručne, na objednávku'];
$note = trim((string) ($detail['note'] ?? '')) ?: 'Každý kus vyrábame ručne podľa konkrétneho modelu motorky.';
$notice = trim((string) ($detail['notice'] ?? '')) ?: 'Pri objednávke prosím uveďte značku, model a rok výroby vašej motorky.';
$features = array_slice(array_values(array_filter(array_map('trim', (array) ($detail['features'] ?? [])))), 0, 4) ?: ['Výroba na mieru', 'Odolná konštrukcia', 'Presné uchytenie', 'Ručná práca'];
$icons = [
    '<path d="M12 3 4.5 6v5.5c0 4.6 3.1 8.1 7.5 9.5 4.4-1.4 7.5-4.9 7.5-9.5V6L12 3Z"/>',
    '<circle cx="12" cy="12" r="3.2"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M18.7 5.3l-2.1 2.1M7.4 16.6l-2.1 2.1"/>',
    '<path d="M6.5 4h11L21 9l-9 11L3 9l3.5-5Z"/><path d="M3 9h18M9.5 4 8 9l4 11 4-11-1.5-5"/>',
    '<path d="M14.5 6.5a4 4 0 0 0 5 5L10 21a2.1 2.1 0 0 1-3-3l9.5-9.5"/><path d="M13 5.5 18.5 11"/>',
];
$hero = [
    'title' => $detail ? e($detail['heading'] ?? '') . '<br><em>' . e($detail['name']) . '</em>' : e($category['title']),
    'text' => $detail && ($detail['series'] ?? '') !== '' ? 'Modelové rady ' . $detail['series'] : '',
    'crumbs' => [['E-shop', 'shop'], ['Rámy a stojany', 'ramy'], [$category['title'], null]],
    'video' => '5052771',
];
include __DIR__ . '/inc/page-hero.php';
$visible = 5; // koľko fotiek je vidieť v mriežke, ostatné sú v prehliadači fotiek
?>

<section class="shop-list fm section-dark">
    <div class="container">
        <?php include __DIR__ . '/inc/shop-switch.php'; ?>
        <p class="fm-lead"><?= e($lead) ?></p>
        <ul class="fm-features">
            <?php foreach ($features as $index => $feature): ?><li><svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icons[$index % 4] ?></svg><span><?= e($feature) ?></span></li><?php endforeach; ?>
        </ul>

        <?php if ($photos !== []): $extra = count($photos) - $visible; ?>
        <div class="fm-gallery fm-gallery--<?= min(count($photos), $visible) ?>">
            <?php foreach ($photos as $index => $photo): ?><figure class="gallery-item fm-photo<?= $index >= $visible ? ' fm-photo--extra' : '' ?>" data-gallery-index="<?= $index ?>" data-full="<?= e($photo['src']) ?>" tabindex="0" role="button" aria-haspopup="dialog" aria-label="Otvoriť fotografiu <?= $index + 1 ?>"><img loading="lazy" decoding="async" src="<?= e($photo['thumb'] ?? $photo['src']) ?>" alt="<?= e($label) ?>, fotografia <?= $index + 1 ?>"><?php if ($extra > 0 && $index === $visible - 1): ?><span class="fm-photo-more">+<?= $extra ?> fotiek</span><?php endif; ?></figure><?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="frames-empty"><?= t('frames.empty_text') ?></p>
        <?php endif; ?>

        <div class="fm-cards">
            <div class="fm-card">
                <h2><?= t('frames.detail_compat_title') ?></h2>
                <dl class="fm-specs">
                    <?php foreach ($specs as $name => $value): ?><div><dt><?= e($name) ?></dt><dd><?= e($value) ?></dd></div><?php endforeach; ?>
                </dl>
            </div>
            <div class="fm-card">
                <h2><?= t('frames.detail_important_title') ?></h2>
                <p><?= e($note) ?></p>
                <p class="fm-notice"><?= e($notice) ?></p>
                <p class="fm-help"><?= t('frames.detail_help_text') ?></p>
            </div>
            <div class="fm-card fm-card--order">
                <h2><?= t('frames.detail_order_title') ?></h2>
                <p><?= t('frames.detail_order_text') ?></p>
                <a class="button button--orange" href="kontakt?model=<?= e(rawurlencode($label)) ?>#contact-form-wrap"><?= t('frames.detail_order_button') ?> <span aria-hidden="true">→</span></a>
            </div>
        </div>
        <a class="fm-back" href="ramy">← Všetky kategórie</a>
    </div>
</section>
<?php if ($photos !== []): ?>
<div class="gallery-lightbox" id="gallery-lightbox" aria-hidden="true">
    <div class="lb-backdrop" data-lightbox-close></div>
    <div class="lb-dialog" role="dialog" aria-modal="true" aria-label="Galéria fotografií">
        <div class="lb-top"><span class="lb-counter" id="gallery-lightbox-counter"></span><button class="lb-close" type="button" data-lightbox-close aria-label="Zavrieť galériu"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"></path></svg></button></div>
        <div class="lb-stage">
            <button class="lb-nav lb-nav--prev" type="button" data-lightbox-prev aria-label="Predchádzajúca fotografia"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"></path></svg></button>
            <img id="gallery-lightbox-image" src="" alt="">
            <button class="lb-nav lb-nav--next" type="button" data-lightbox-next aria-label="Ďalšia fotografia"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"></path></svg></button>
        </div>
        <div class="lb-thumbs" id="gallery-lightbox-thumbs" aria-label="Náhľady fotografií"><?php foreach ($photos as $index => $photo): ?><button type="button" data-lightbox-thumb="<?= $index ?>" aria-label="Zobraziť fotografiu <?= $index + 1 ?>"><img loading="lazy" src="<?= e($photo['thumb'] ?? $photo['src']) ?>" alt=""></button><?php endforeach; ?></div>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<?php $hero = ['title' => t('frames.hero_title'), 'text' => textRaw('frames.hero_text'), 'crumbs' => [['E-shop', 'shop'], ['Rámy a stojany', null]], 'video' => '5052771']; include __DIR__ . '/inc/page-hero.php'; ?>

<section class="shop-list fm section-dark">
    <div class="container">
        <?php include __DIR__ . '/inc/shop-switch.php'; ?>
        <div class="svc-intro">
            <p class="overline"><?= t('frames.intro_overline') ?></p>
            <p><?= t('frames.intro_text') ?></p>
        </div>
        <div class="shop-cards">
            <?php foreach ($frames as $id => $cat): $first = $cat['photos'][0] ?? null; $cover = $first ? ($first['thumb'] ?? $first['src']) : $placeholder; ?>
            <article class="shop-card reveal">
                <a class="shop-card-media" href="ramy?typ=<?= e($id) ?>"><img src="<?= e($cover) ?>" alt="<?= e($cat['title']) ?>" loading="lazy"></a>
                <div class="shop-card-body">
                    <h3 class="shop-card-name"><a href="ramy?typ=<?= e($id) ?>"><?= e($cat['title']) ?></a></h3>
                    <p class="shop-card-text"><?= e($cat['text']) ?></p>
                    <a class="shop-card-detail" href="ramy?typ=<?= e($id) ?>">Zobraziť <span aria-hidden="true">→</span></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>
