    </main>
    <footer class="site-foot">
        <div class="container site-foot-cta">
            <h2><?= t('footer.cta_title') ?></h2>
            <div class="site-foot-cta-actions"><a class="button button--orange" href="rezervacia.php">Rezervovať termín</a><a class="text-link" href="<?= e(phoneHref()) ?>">Zavolať</a></div>
        </div>
        <div class="container site-foot-main">
            <div class="site-foot-brand">
                <a href="index.php" class="brand" aria-label="Domov MotoCare Pro"><span class="brand-logo"><img src="auto_care_logo.png" alt="Logo MotoCare Pro"></span></a>
                <p><?= e($site['tagline']) ?></p>
                <div class="site-foot-socials"><a href="<?= e($site['instagram']) ?>" target="_blank" rel="noreferrer" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4.2"></circle><circle class="social-dot" cx="17.4" cy="6.6" r="1"></circle></svg></a><a href="<?= e($site['facebook']) ?>" target="_blank" rel="noreferrer" aria-label="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.4 21v-8h2.7l.4-3h-3.1V8.1c0-.9.3-1.5 1.6-1.5h1.7V4a22 22 0 0 0-2.4-.1c-2.4 0-4 1.5-4 4.1V10H8.6v3h2.7v8h3.1Z"></path></svg></a></div>
            </div>
            <nav class="site-foot-col" aria-label="Menu v pätičke">
                <span class="site-foot-title">Menu</span>
                <a href="sluzby.php">Služby</a><a href="o-nas.php">O nás</a><a href="galeria.php">Naša práca</a><a href="shop.php">Produkty</a><a href="rezervacia.php">Rezervácia</a><a href="kontakt.php">Kontakt</a>
            </nav>
            <div class="site-foot-col">
                <span class="site-foot-title">Kontakt</span>
                <a class="site-foot-contact" href="<?= e(phoneHref()) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 3.5 9 3l2 4.7-1.8 1.5a14.4 14.4 0 0 0 5.6 5.6l1.5-1.8 4.7 2-.5 2.4c-.3 1.4-1.6 2.4-3 2.3C10.2 19.1 4.9 13.8 4.3 6.5c-.1-1.4.9-2.7 2.3-3Z"></path></svg><span><?= e($site['phone']) ?></span></a>
                <a class="site-foot-contact" href="mailto:<?= e($site['email']) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="1.5"></rect><path d="m4 7 8 6 8-6"></path></svg><span><?= e($site['email']) ?></span></a>
                <a class="site-foot-contact" href="kontakt.php#kontaktna-mapa"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.1 7-12A7 7 0 0 0 5 9c0 5.9 7 12 7 12Z"></path><circle cx="12" cy="9" r="2.3"></circle></svg><span><?= e(siteAddress()) ?></span></a>
            </div>
            <div class="site-foot-col">
                <span class="site-foot-title">Otváracie hodiny</span>
                <?php foreach (openingHoursGroups() as $group): ?><p class="site-foot-hours"><span><?= e($group['label']) ?></span><b><?= e($group['value']) ?></b></p><?php endforeach; ?>
            </div>
        </div>
        <div class="container site-foot-bottom"><span>© <?= date('Y') ?> <?= e($site['name']) ?></span><nav aria-label="Právne informácie"><a href="gdpr.php">Ochrana údajov</a><a href="cookies.php">Cookies</a></nav></div>
    </footer>
    <a class="scroll-to-top" href="#top" aria-label="Späť hore"><span aria-hidden="true">↑</span></a>
    <div class="toast" id="toast" role="status"></div>
    <script>window.MOTOCARE = { today: '<?= date('Y-m-d') ?>', services: <?= json_encode(array_map(static fn (array $service): array => ['name' => $service['name'], 'price' => $service['price'], 'minutes' => $service['minutes']], $services), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>, products: <?= json_encode(array_map(static fn (array $product): array => ['name' => $product['name'], 'price' => $product['price'], 'image' => $product['image'], 'brand' => $product['brand'] ?? '', 'volume' => $product['volume'] ?? 0], $products), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> };</script>
    <script src="script.js?v=<?= (int) filemtime(__DIR__ . '/../script.js') ?>"></script>
</body>
</html>
