<?php
declare(strict_types=1);

// Adds the products from inc/catalog.php to the shop: php admin/import-products.php [--dry-run] [--refresh-texts]
// --refresh-texts also overwrites the texts (short, description, features, long, howto) of catalog products that
// are already in the shop, e.g. after the catalog texts were corrected. Prices, photos, tags and visibility stay as they are.
// Existing products are never changed or removed; a product that is already in the shop (same id) is skipped,
// so the script can be run again safely. New products get "Cena čoskoro" (price 0) until a price is set in the admin.
// A photo is taken from produkty/<id>.webp when it exists, otherwise the neutral placeholder is used.
// Disabled over the web.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/inc/config.php';

$dryRun = in_array('--dry-run', $argv, true);
$refresh = in_array('--refresh-texts', $argv, true);
$refreshed = 0;
$catalog = require dirname(__DIR__) . '/inc/catalog.php';
$root = dirname(__DIR__);
$placeholder = 'produkty/fotka-coskoro.webp';

$items = $allProducts;
$added = 0;
$skipped = 0;
$withoutPhoto = [];
foreach ($catalog as $id => $product) {
    if (isset($items[$id])) {
        if ($refresh) {
            foreach (['short', 'description', 'features', 'long', 'howto'] as $field) $items[$id][$field] = $product[$field];
            $refreshed++;
        }
        $skipped++;
        continue;
    }
    $photo = 'produkty/' . $id . '.webp';
    if (is_file($root . '/' . $photo)) {
        $product['image'] = $photo;
    } else {
        $product['image'] = $placeholder;
        $withoutPhoto[] = $id;
    }
    $items[$id] = $product;
    $added++;
}

if (($added > 0 || $refreshed > 0) && !$dryRun && !saveJsonFile($productsFile, $items)) {
    fwrite(STDERR, "Produkty sa nepodarilo uložiť (práva na priečinok data/).\n");
    exit(1);
}
echo ($dryRun ? "Skúšobný beh, nič sa nezapísalo.\n" : '') . "Pridané produkty: $added, preskočené (už v obchode): $skipped, texty obnovené: $refreshed, spolu v obchode: " . count($items) . "\n";
if ($withoutPhoto !== []) echo "Bez oficiálnej fotky (zástupná): " . count($withoutPhoto) . "\n";
