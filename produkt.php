<?php
require_once __DIR__ . '/inc/config.php';
$slug = cleanText($_GET['slug'] ?? '', 40);
if (!isset($products[$slug])) { header('Location: shop.php'); exit; }
$product = $products[$slug];
$pageTitle = $product['name'] . ' — MotoCare produkty';
$activePage = 'shop';
$bodyClass = 'page-plain';
include __DIR__ . '/inc/header.php';
$checkIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"></path></svg>';
$perkIcons = [
    '<rect x="3" y="6" width="18" height="12" rx="2"></rect><path d="M3 10h18M7 15h3"></path>',
    '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"></path><circle cx="7" cy="17.5" r="1.8"></circle><circle cx="17" cy="17.5" r="1.8"></circle>',
    '<path d="M4 5h16v11H9l-5 4z"></path><path d="M8 9.5h8M8 12.5h5"></path>',
];
?>
<section class="pd section-light">
    <div class="container">
        <nav class="pd-crumbs" aria-label="Omrvinková navigácia"><a href="index.php">Domov</a><span aria-hidden="true">/</span><a href="shop.php">Produkty</a><span aria-hidden="true">/</span><span aria-current="page"><?= e($product['name']) ?></span></nav>
        <div class="pd-grid">
            <div class="pd-media">
                <img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>">
                <span class="shop-card-tag"><?= e($product['tag']) ?></span>
            </div>
            <div class="pd-buy">
                <p class="shop-card-brand"><?= e($product['brand'] ?? '') ?></p>
                <h1><?= e($product['name']) ?></h1>
                <p class="pd-lead"><?= e($product['description']) ?></p>
                <div class="pd-price"><strong><?= (int) $product['price'] ?> €</strong><?php if (productUnitPrice($product) !== ''): ?><span><?= e(productUnitPrice($product)) ?></span><?php endif; ?></div>
                <ul class="shop-card-specs pd-specs">
                    <?php if (!empty($product['brand'])): ?><li><small>Značka</small><b><?= e($product['brand']) ?></b></li><?php endif; ?>
                    <?php if (!empty($product['volume'])): ?><li><small>Objem</small><b><?= (int) $product['volume'] ?> ml</b></li><?php endif; ?>
                    <?php if (!empty($product['form'])): ?><li><small>Balenie</small><b><?= e($product['form']) ?></b></li><?php endif; ?>
                </ul>
                <div class="pd-add">
                    <div class="pd-qty"><button type="button" data-quantity="minus" aria-label="Menej kusov">−</button><span id="product-quantity">1</span><button type="button" data-quantity="plus" aria-label="Viac kusov">+</button></div>
                    <button class="button button--orange add-product pd-add-button" type="button" data-product-id="<?= e($slug) ?>" data-product-name="<?= e($product['name']) ?>" data-product-price="<?= (int) $product['price'] ?>" data-quantity-target="product-quantity">Pridať do košíka</button>
                </div>
                <a class="shop-card-detail" href="kosik.php">Zobraziť košík <span aria-hidden="true">→</span></a>
                <ul class="pd-perks">
                    <?php foreach (tl('product.perks') as $index => [$title, $text]): $icon = $perkIcons[$index % count($perkIcons)]; ?><li><span><svg viewBox="0 0 24 24" aria-hidden="true"><?= $icon ?></svg></span><div><b><?= e($title) ?></b><small><?= e($text) ?></small></div></li><?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="pd-info section-light">
    <div class="container pd-info-grid">
        <div class="pd-about">
            <p class="overline"><?= t('product.about_overline') ?></p>
            <h2><?= t('product.about_title') ?></h2>
            <?php foreach ($product['long'] ?? [] as $paragraph): ?><p><?= e($paragraph) ?></p><?php endforeach; ?>
            <ul class="pd-features"><?php foreach ($product['features'] as $feature): ?><li><?= $checkIcon ?><?= e($feature) ?></li><?php endforeach; ?></ul>
        </div>
        <?php if (!empty($product['howto'])): ?>
        <aside class="pd-howto">
            <h3><?= t('product.howto_title') ?></h3>
            <ol><?php foreach ($product['howto'] ?? [] as $step): ?><li><?= e($step) ?></li><?php endforeach; ?></ol>
            <p class="pd-howto-note"><?= t('product.howto_note') ?></p>
        </aside>
        <?php endif; ?>
    </div>
</section>

<?php $related = array_filter($products, static fn (string $id): bool => $id !== $slug, ARRAY_FILTER_USE_KEY); ?>
<?php if ($related !== []): ?>
<section class="pd-related section-light">
    <div class="container">
        <div class="section-heading"><div><p class="overline"><?= t('product.related_overline') ?></p><h2><?= t('product.related_title') ?></h2></div><a class="underlined-link" href="shop.php">Všetky produkty</a></div>
        <div class="shop-cards">
            <?php foreach ($related as $id => $product) include __DIR__ . '/inc/product-card.php'; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<?php include __DIR__ . '/inc/footer.php'; ?>
