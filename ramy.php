<?php
require_once __DIR__ . '/inc/config.php';
$placeholder = 'produkty/fotka-coskoro.webp';

$key = cleanText($_GET['typ'] ?? '', 60);
$category = $frames[$key] ?? null;
$activePage = 'shop';
$pageTitle = ($category ? $category['title'] . ' na mieru' : 'Padacie rámy a stojany na motorky') . ' — MotoCare Pro';
$pageDescription = $category ? $category['text'] : 'Ručne vyrábané padacie rámy, nárazové tyče, stojany a stelaže na motorky. Vyrábame na mieru, na objednávku.';
include __DIR__ . '/inc/header.php';
$shopTab = 'frames';
?>
<?php if ($category): ?>
<?php $hero = ['title' => e($category['title']), 'text' => $category['text'], 'crumbs' => [['E-shop', 'shop'], ['Rámy a stojany', 'ramy'], [$category['title'], null]], 'video' => '5052771']; include __DIR__ . '/inc/page-hero.php'; ?>
<?php $photos = $category['photos'] ?? []; ?>

<section class="shop-list section-light">
    <div class="container">
        <?php include __DIR__ . '/inc/shop-switch.php'; ?>
        <?php if ($photos !== []): ?>
        <div class="frames-gallery">
            <?php foreach ($photos as $photo): ?><a href="<?= e($photo['src']) ?>" target="_blank" rel="noopener"><img src="<?= e($photo['thumb'] ?? $photo['src']) ?>" alt="<?= e($category['title']) ?>" loading="lazy"></a><?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="frames-empty"><?= t('frames.empty_text') ?></p>
        <?php endif; ?>

        <div class="frames-order">
            <h2><?= t('frames.order_title') ?></h2>
            <p><?= t('frames.order_text') ?></p>
            <a class="button button--orange" href="kontakt?model=<?= e(rawurlencode($category['title'])) ?>">Mám záujem</a>
        </div>
        <a class="shop-card-detail" href="ramy">← Všetky kategórie</a>
    </div>
</section>

<?php else: ?>
<?php $hero = ['title' => t('frames.hero_title'), 'text' => textRaw('frames.hero_text'), 'crumbs' => [['E-shop', 'shop'], ['Rámy a stojany', null]], 'video' => '5052771']; include __DIR__ . '/inc/page-hero.php'; ?>

<section class="shop-list section-light">
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

<section class="svc-cta section-orange">
    <div class="container svc-cta-inner">
        <div><p class="svc-cta-kicker"><?= t('frames.cta_kicker') ?></p><h2><?= t('frames.cta_title') ?></h2><p><?= t('frames.cta_text') ?></p></div>
        <div class="svc-cta-actions"><a class="button button--dark" href="kontakt">Napísať nám</a><a class="svc-cta-phone" href="<?= e(phoneHref()) ?>"><?= e($site['phone']) ?></a></div>
    </div>
</section>
<?php include __DIR__ . '/inc/footer.php'; ?>
