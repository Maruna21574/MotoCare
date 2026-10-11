<?php // Prepínač kategórií e-shopu. Očakáva $shopTab: 'care' alebo 'frames'. ?>
<nav class="shop-switch" aria-label="Kategórie e-shopu">
    <a class="<?= $shopTab === 'care' ? 'is-active' : '' ?>" href="shop"<?= $shopTab === 'care' ? ' aria-current="page"' : '' ?>>Starostlivosť o motorku</a>
    <a class="<?= $shopTab === 'frames' ? 'is-active' : '' ?>" href="ramy"<?= $shopTab === 'frames' ? ' aria-current="page"' : '' ?>>Padacie rámy a stojany</a>
</nav>
