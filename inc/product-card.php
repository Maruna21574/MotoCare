<?php // Product card used on shop and in related products. Expects $id and $product. ?>
<article class="shop-card reveal">
    <a class="shop-card-media" href="produkt?slug=<?= e($id) ?>"><img src="<?= e($product['image']) ?>" alt="<?= e($product['name']) ?>" loading="lazy"><span class="shop-card-tag"><?= e($product['tag']) ?></span></a>
    <div class="shop-card-body">
        <p class="shop-card-brand"><?= e($product['brand'] ?? '') ?></p>
        <h3 class="shop-card-name"><a href="produkt?slug=<?= e($id) ?>"><?= e($product['name']) ?></a></h3>
        <p class="shop-card-text"><?= e($product['short']) ?></p>
        <ul class="shop-card-specs">
            <?php if (!empty($product['volume'])): ?><li><small>Objem</small><b><?= (int) $product['volume'] ?> ml</b></li><?php endif; ?>
            <?php if (!empty($product['form'])): ?><li><small>Balenie</small><b><?= e($product['form']) ?></b></li><?php endif; ?>
        </ul>
        <div class="shop-card-buy">
            <?php if (!productPriced($product)): ?>
            <div class="shop-card-price shop-card-price--soon"><strong>Cena čoskoro</strong></div>
            <a class="button button--dark" href="kontakt">Spýtať sa na cenu</a>
            <?php else: ?>
            <div class="shop-card-price"><strong><?= (int) $product['price'] ?> €</strong><?php if (productUnitPrice($product) !== ''): ?><small><?= e(productUnitPrice($product)) ?></small><?php endif; ?></div>
            <button class="button button--orange add-product" type="button" data-product-id="<?= e($id) ?>" data-product-name="<?= e($product['name']) ?>" data-product-price="<?= (int) $product['price'] ?>">Do košíka</button>
            <?php endif; ?>
        </div>
        <a class="shop-card-detail" href="produkt?slug=<?= e($id) ?>">Detail produktu <span aria-hidden="true">→</span></a>
    </div>
</article>
