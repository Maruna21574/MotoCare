<?php
require_once __DIR__ . '/inc/config.php';
$pageTitle = 'Galéria práce — MotoCare Pro';
$activePage = 'galeria';
include __DIR__ . '/inc/header.php';
?>
<?php $hero = ['title' => t('gallery.hero_title'), 'text' => textRaw('gallery.hero_text'), 'crumbs' => [['Galéria', null]], 'video' => '5052424']; include __DIR__ . '/inc/page-hero.php'; ?>
<section class="gallery-page section-light section-pad"><div class="container"><div class="gallery-grid gallery-grid--page"><?php foreach ($galleryImages as $index => $image): ?><figure class="gallery-item gallery-item--<?= ($index % 5) + 1 ?> reveal" data-gallery-index="<?= $index ?>" tabindex="0" role="button" aria-haspopup="dialog" aria-label="Otvoriť fotografiu <?= $index + 1 ?>"><img loading="lazy" src="<?= e($image) ?>" alt="MotoCare Pro — fotografia starostlivosti číslo <?= $index + 1 ?>"></figure><?php endforeach; ?></div></div></section>
<div class="gallery-lightbox" id="gallery-lightbox" aria-hidden="true">
    <div class="lb-backdrop" data-lightbox-close></div>
    <div class="lb-dialog" role="dialog" aria-modal="true" aria-label="Galéria fotografií">
        <div class="lb-top"><span class="lb-counter" id="gallery-lightbox-counter"></span><button class="lb-close" type="button" data-lightbox-close aria-label="Zavrieť galériu"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"></path></svg></button></div>
        <div class="lb-stage">
            <button class="lb-nav lb-nav--prev" type="button" data-lightbox-prev aria-label="Predchádzajúca fotografia"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"></path></svg></button>
            <img id="gallery-lightbox-image" src="" alt="">
            <button class="lb-nav lb-nav--next" type="button" data-lightbox-next aria-label="Ďalšia fotografia"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"></path></svg></button>
        </div>
        <div class="lb-thumbs" id="gallery-lightbox-thumbs" aria-label="Náhľady fotografií"><?php foreach ($galleryImages as $index => $image): ?><button type="button" data-lightbox-thumb="<?= $index ?>" aria-label="Zobraziť fotografiu <?= $index + 1 ?>"><img loading="lazy" src="<?= e($image) ?>" alt=""></button><?php endforeach; ?></div>
    </div>
</div>
<?php $comparisons = array_map(static fn (string $suffix): array => ['label' => t('gallery.compare_label' . $suffix), 'before' => t('gallery.compare_before' . $suffix), 'after' => t('gallery.compare_after' . $suffix), 'caption' => t('gallery.compare_caption' . $suffix)], ['', '_2', '_3']); ?>
<section class="before-after section-dark section-pad">
    <div class="container">
        <div class="section-heading section-heading--light"><div><p class="eyebrow"><span class="eyebrow-line"></span> <?= t('gallery.compare_eyebrow') ?></p><h2><?= t('gallery.compare_title') ?></h2></div><p class="section-heading-note"><?= t('gallery.compare_note') ?></p></div>
        <div class="compare-tabs" role="tablist" aria-label="Vyber porovnanie">
            <?php foreach ($comparisons as $index => $item): ?>
            <button class="compare-tab<?= $index === 0 ? ' is-active' : '' ?>" type="button" role="tab" id="compare-tab-<?= $index ?>" aria-controls="compare-<?= $index ?>" aria-selected="<?= $index === 0 ? 'true' : 'false' ?>" data-compare-tab="<?= $index ?>">
                <span class="compare-tab-thumb"><img src="<?= $item['after'] ?>" alt="" loading="lazy"></span>
                <span class="compare-tab-text"><small><?= sprintf('%02d', $index + 1) ?></small><?= $item['label'] ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <?php foreach ($comparisons as $index => $item): ?>
        <div class="compare-panel" id="compare-<?= $index ?>" role="tabpanel" aria-labelledby="compare-tab-<?= $index ?>"<?= $index === 0 ? '' : ' hidden' ?>>
            <div class="comparison"><div class="comparison-image comparison-image--before"><img src="<?= $item['before'] ?>" alt="Ukážka pred starostlivosťou"<?= $index === 0 ? '' : ' loading="lazy"' ?>><span>Pred</span></div><div class="comparison-image comparison-image--after"><img src="<?= $item['after'] ?>" alt="Ukážka po starostlivosti"<?= $index === 0 ? '' : ' loading="lazy"' ?>><span>Po</span></div><div class="comparison-handle"><i></i></div><input type="range" min="0" max="100" value="50" aria-label="Porovnať pred a po"></div>
            <div class="comparison-caption"><span><?= $item['caption'] ?></span><span class="caption-line"></span><span>Posuň pre porovnanie</span></div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include __DIR__ . '/inc/footer.php'; ?>
