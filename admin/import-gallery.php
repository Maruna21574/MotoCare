<?php
declare(strict_types=1);

// Imports sorted photos into the gallery: php admin/import-gallery.php galeria-import [--dry-run]
// Every sub-folder of the given folder is one motorcycle (see $bikes). Files are copied to uploads/
// and appended to data/gallery.json with the motorcycle name, so the gallery can be filtered by it.
// Safe to run again: photos that are already in the gallery are skipped. Disabled over the web.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/inc/config.php';

// Folder name => name shown in the gallery. Folders that are not listed here are not imported.
$bikes = [
    'bmw-r1200gs' => 'BMW R1200 GS',
    'bmw-r1300gs' => 'BMW R1300 GS',
    'honda-transalp' => 'Honda Transalp',
    'honda-varadero' => 'Honda Varadero',
    'ktm-1290-super-adventure' => 'KTM 1290 Super Adventure',
    'ktm-1190-adventure-oranzova' => 'KTM 1190 Adventure',
    'suzuki-bandit' => 'Suzuki Bandit',
];

$source = rtrim((string) ($argv[1] ?? ''), '/\\');
$dryRun = in_array('--dry-run', $argv, true);
if ($source === '' || !is_dir($source)) {
    fwrite(STDERR, "Použitie: php admin/import-gallery.php <priečinok s podpriečinkami motoriek> [--dry-run]\n");
    exit(1);
}
if (!$dryRun && !is_dir($uploadsDir) && !mkdir($uploadsDir, 0775, true)) {
    fwrite(STDERR, "Priečinok uploads/ sa nepodarilo vytvoriť.\n");
    exit(1);
}

$items = $gallery;
$known = array_column($items, 'src');
// The photos that shipped with the site in "BMW R1200 GS/" belong to that motorcycle.
foreach ($items as &$item) {
    if (empty($item['bike']) && str_starts_with($item['src'], 'BMW%20R1200%20GS/') && !in_array($item['src'], $sliderSkip, true)) $item['bike'] = $bikes['bmw-r1200gs'];
}
unset($item);

$added = 0;
$skipped = 0;
foreach ($bikes as $folder => $label) {
    $files = glob($source . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . '*.webp') ?: [];
    sort($files, SORT_NATURAL);
    $count = 0;
    foreach ($files as $file) {
        $name = 'galeria-' . basename($file);
        $src = 'uploads/' . $name;
        if (in_array($src, $known, true)) { $skipped++; continue; }
        if (!$dryRun && !copy($file, $uploadsDir . DIRECTORY_SEPARATOR . $name)) { fwrite(STDERR, "Nepodarilo sa skopírovať $file\n"); continue; }
        $thumb = $dryRun ? null : makeGalleryThumb($src);
        $items[] = ['src' => $src, 'slider' => false, 'bike' => $label] + ($thumb !== null ? ['thumb' => $thumb] : []);
        $count++;
    }
    $added += $count;
    echo str_pad($label, 28) . $count . " nových\n";
}

// Photos imported earlier without a preview get one now.
foreach ($items as &$item) {
    if ($dryRun || !empty($item['thumb']) || !str_starts_with($item['src'], 'uploads/')) continue;
    $thumb = makeGalleryThumb($item['src']);
    if ($thumb !== null) $item['thumb'] = $thumb;
}
unset($item);

if (!$dryRun && !saveJsonFile($galleryFile, array_values($items))) {
    fwrite(STDERR, "Galériu sa nepodarilo uložiť (práva na priečinok data/).\n");
    exit(1);
}
echo ($dryRun ? "Skúšobný beh, nič sa nezapísalo. " : '') . "Pridané: $added, preskočené (už v galérii): $skipped, spolu v galérii: " . count($items) . "\n";
