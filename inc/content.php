<?php
// Content edited in the admin: site details, products, services, gallery and page texts.
// Each piece lives in its own JSON file in data/; until the admin saves it, the defaults from config.php are used.

$siteFile = $dataDir . DIRECTORY_SEPARATOR . 'site.json';
$productsFile = $dataDir . DIRECTORY_SEPARATOR . 'products.json';
$servicesFile = $dataDir . DIRECTORY_SEPARATOR . 'services.json';
$galleryFile = $dataDir . DIRECTORY_SEPARATOR . 'gallery.json';
$textsFile = $dataDir . DIRECTORY_SEPARATOR . 'texts.json';
$uploadsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';

$site = array_replace($site, readJsonFile($siteFile));
$site['location'] = trim($site['street'] . ' · ' . $site['city'] . ' · ' . $site['zip'], ' ·');

if (is_file($servicesFile)) $services = readJsonFile($servicesFile);
if (is_file($productsFile)) $products = readJsonFile($productsFile);
$allProducts = $products;
$products = array_filter($products, static fn (array $product): bool => empty($product['hidden']));

/**
 * Photos of a frames category that ship with the site in produkty/ramy/<key>/ (web paths, sorted by name).
 * Resized copies from the old site (-150x150, -720x380) are skipped when the original is there.
 */
function frameFolderPhotos(string $key): array
{
    $dir = dirname(__DIR__) . '/produkty/ramy/' . $key;
    $files = is_dir($dir) ? scandir($dir) : [];
    $photos = [];
    foreach ($files as $file) {
        if (!preg_match('/\.(webp|jpe?g|png|avif)$/i', $file)) continue;
        if (preg_match('/^(.+)-\d+x\d+(\.[a-z0-9]+)$/i', $file, $match) && (in_array($match[1] . $match[2], $files, true) || str_contains($file, '-150x150.'))) continue;
        $photos[] = ['src' => 'produkty/ramy/' . $key . '/' . $file];
    }
    return $photos;
}

// Frames and stands categories: [slug => ['title', 'text', 'photos' => [['src', 'thumb'?], ...], 'hidden']].
// Until the admin saves them, the defaults from inc/frames.php and the photos in produkty/ramy/ are used.
$framesFile = $dataDir . DIRECTORY_SEPARATOR . 'frames.json';
if (is_file($framesFile)) {
    $allFrames = readJsonFile($framesFile);
} else {
    $allFrames = [];
    foreach (require __DIR__ . '/frames.php' as $key => $category) {
        $allFrames[$key] = ['title' => $category['title'], 'text' => $category['text'], 'photos' => frameFolderPhotos($key), 'hidden' => false];
    }
}
$frames = array_filter($allFrames, static fn (array $category): bool => empty($category['hidden']));

if (is_file($galleryFile)) {
    $gallery = readJsonFile($galleryFile);
} else {
    $gallery = array_map(static fn (string $src): array => ['src' => $src, 'slider' => !in_array($src, $sliderSkip, true)], $galleryImages);
}
$galleryImages = array_column($gallery, 'src');
$sliderItems = array_values(array_filter($gallery, static fn (array $item): bool => !empty($item['slider'])));
$sliderImages = array_column($sliderItems, 'src');

/** Motorcycles that have photos in the gallery as slug => ['label', 'count'], in order of first appearance. */
function galleryBikes(array $gallery): array
{
    $bikes = [];
    foreach ($gallery as $item) {
        $label = trim((string) ($item['bike'] ?? ''));
        if ($label === '') continue;
        $slug = slugify($label);
        $bikes[$slug] ??= ['label' => $label, 'count' => 0];
        $bikes[$slug]['count']++;
    }
    uasort($bikes, static fn (array $a, array $b): int => strnatcasecmp($a['label'], $b['label']));
    return $bikes;
}

/** Company details as [label, value] rows; empty values are left out. */
function companyDetails(): array
{
    global $site;
    $rows = [
        ['Obchodné meno', $site['company_name'] ?? ''],
        ['Sídlo', $site['company_seat'] ?? ''],
        ['IČO', $site['company_ico'] ?? ''],
        ['DIČ', $site['company_dic'] ?? ''],
        ['DPH', $site['company_vat'] ?? ''],
        ['Zápis v registri', $site['company_registry'] ?? ''],
        ['Konateľ', $site['company_manager'] ?? ''],
    ];
    return array_values(array_filter($rows, static fn (array $row): bool => trim((string) $row[1]) !== ''));
}

/** Company in running text, e.g. "NERION GROUP s. r. o., so sídlom Kollárova 85, 036 01 Martin, IČO: 56225890". */
function companyIntro(): string
{
    global $site;
    $name = trim((string) ($site['company_name'] ?? ''));
    if ($name === '') return (string) $site['name'];
    $parts = [$name];
    if (trim((string) ($site['company_seat'] ?? '')) !== '') $parts[] = 'so sídlom ' . $site['company_seat'];
    if (trim((string) ($site['company_ico'] ?? '')) !== '') $parts[] = 'IČO: ' . $site['company_ico'];
    if (trim((string) ($site['company_registry'] ?? '')) !== '') $parts[] = 'zapísaná v Obchodnom registri (' . $site['company_registry'] . ')';
    return implode(', ', $parts);
}

/** One-line address, e.g. "Kollárova 85, 036 01 Martin". */
function siteAddress(): string
{
    global $site;
    return trim($site['street'] . ', ' . trim($site['zip'] . ' ' . $site['city']), ', ');
}

function phoneHref(): string
{
    global $site;
    return 'tel:' . str_replace(' ', '', $site['phone']);
}

// ---------------------------------------------------------------- Page texts

function textRegistry(): array
{
    static $registry = null;
    return $registry ??= require __DIR__ . '/texts.php';
}

/** Field definition [label, type, default] for a key like "home.hero_title". */
function textField(string $key): array
{
    [$page, $field] = explode('.', $key, 2) + [1 => ''];
    $definition = textRegistry()[$page]['fields'][$field] ?? null;
    if ($definition === null) throw new InvalidArgumentException('Unknown text key ' . $key);
    return $definition;
}

/** Raw text: the admin override, or the default when the override is empty. */
function textRaw(string $key): string
{
    static $overrides = null;
    global $textsFile;
    $overrides ??= readJsonFile($textsFile);
    $value = trim((string) ($overrides[$key] ?? ''));
    return $value !== '' ? $value : (string) textField($key)[2];
}

/** Placeholders usable in any text: {phone}, {email}, {address}. */
function textPlaceholders(string $html): string
{
    global $site;
    return strtr($html, [
        '{phone}' => '<a href="' . e(phoneHref()) . '">' . e($site['phone']) . '</a>',
        '{email}' => '<a href="mailto:' . e($site['email']) . '">' . e($site['email']) . '</a>',
        '{address}' => e(siteAddress()),
        '{company}' => e(companyIntro()),
        '{name}' => e($site['name']),
    ]);
}

/**
 * Safe HTML for a text. Titles turn *word* into an accent and new lines into line breaks,
 * paragraphs become <p> blocks and "## Heading" lines in rich text become subheadings.
 */
function t(string $key): string
{
    $type = textField($key)[1];
    $value = textRaw($key);
    if ($type === 'image') return e($value);
    if ($type === 'title') return nl2br(preg_replace('/\*(.+?)\*/su', '<em>$1</em>', e($value)), false);
    if ($type === 'text') return textPlaceholders(nl2br(e($value), false));
    if ($type === 'paragraphs' || $type === 'rich') {
        $html = '';
        foreach (preg_split('/\R\s*\R/u', $value) as $block) {
            $block = trim($block);
            if ($block === '') continue;
            if ($type === 'rich' && str_starts_with($block, '## ')) {
                $lines = preg_split('/\R/u', $block);
                $html .= '<h3>' . e(substr(array_shift($lines), 3)) . '</h3>';
                $block = trim(implode("\n", $lines));
                if ($block === '') continue;
            }
            $html .= '<p>' . nl2br(e($block), false) . '</p>';
        }
        return textPlaceholders($html);
    }
    return textPlaceholders(e($value));
}

/** List texts: one item per line; "pairs" split each line at the first "|" into [title, text]. */
function tl(string $key): array
{
    $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', textRaw($key))), static fn (string $line): bool => $line !== ''));
    if (textField($key)[1] !== 'pairs') return $lines;
    return array_map(static fn (string $line): array => array_map('trim', explode('|', $line, 2)) + [1 => ''], $lines);
}

// ---------------------------------------------------------------- Helpers for the admin

function slugify(string $text): string
{
    $map = ['á' => 'a', 'ä' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e', 'í' => 'i', 'ĺ' => 'l', 'ľ' => 'l', 'ň' => 'n', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'ŕ' => 'r', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ú' => 'u', 'ů' => 'u', 'ü' => 'u', 'ý' => 'y', 'ž' => 'z'];
    $slug = strtr(mb_strtolower($text), $map);
    $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', $slug), '-');
    return substr($slug, 0, 50) ?: 'polozka';
}

/** Lines of a textarea as a clean list. */
function linesToList(string $text): array
{
    return array_values(array_filter(array_map(static fn (string $line): string => trim($line), preg_split('/\R/u', $text)), static fn (string $line): bool => $line !== ''));
}

/** Paragraphs of a textarea (separated by an empty line). */
function textToParagraphs(string $text): array
{
    return array_values(array_filter(array_map(static fn (string $block): string => trim(preg_replace('/\s*\R\s*/u', ' ', $block)), preg_split('/\R\s*\R/u', $text)), static fn (string $block): bool => $block !== ''));
}

/**
 * Saves an uploaded image to uploads/ and returns its web path, e.g. "uploads/produkt-3fa2c1.webp".
 * Throws RuntimeException with a message for the admin when the file is not a usable image.
 */
function storeUploadedImage(array $file, string $prefix): string
{
    global $uploadsDir;
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException(in_array($file['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) ? 'Obrázok je príliš veľký.' : 'Obrázok sa nepodarilo nahrať.');
    }
    if ($file['size'] > 12 * 1024 * 1024) throw new RuntimeException('Obrázok môže mať najviac 12 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($types[$mime]) || @getimagesize($file['tmp_name']) === false) throw new RuntimeException('Nahrať môžeš iba obrázok JPG, PNG, WEBP alebo GIF.');
    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) throw new RuntimeException('Priečinok na obrázky sa nepodarilo vytvoriť.');
    $name = slugify($prefix) . '-' . bin2hex(random_bytes(4)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], $uploadsDir . DIRECTORY_SEPARATOR . $name)) throw new RuntimeException('Obrázok sa nepodarilo uložiť.');
    return 'uploads/' . $name;
}

/** $_FILES entry with multiple files as a list of single-file arrays. */
function uploadedFiles(string $field): array
{
    $files = $_FILES[$field] ?? null;
    if (!$files) return [];
    if (!is_array($files['name'])) return $files['error'] === UPLOAD_ERR_NO_FILE ? [] : [$files];
    $list = [];
    foreach ($files['name'] as $i => $name) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        $list[] = ['name' => $name, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i], 'error' => $files['error'][$i], 'size' => $files['size'][$i]];
    }
    return $list;
}

/** Deletes a file from uploads/ when nothing references it any more (images shipped with the site are never deleted). */
function deleteUploadIfUnused(string $path): void
{
    global $uploadsDir, $productsFile, $servicesFile, $galleryFile, $textsFile, $framesFile;
    if (!preg_match('#^uploads/[a-z0-9-]+\.(jpg|png|webp|gif)$#', $path)) return;
    foreach ([$productsFile, $servicesFile, $galleryFile, $textsFile, $framesFile] as $file) {
        if (is_file($file) && str_contains((string) file_get_contents($file), $path)) return;
    }
    @unlink($uploadsDir . DIRECTORY_SEPARATOR . basename($path));
}

/**
 * Creates a small preview of an uploaded gallery image in uploads/t/ and returns its web path
 * (e.g. "uploads/t/galeria-abc.webp"), or null when the image cannot be processed.
 * The gallery grid shows these previews; the lightbox loads the full image.
 */
function makeGalleryThumb(string $webPath, int $width = 640): ?string
{
    global $uploadsDir;
    if (!preg_match('#^uploads/([a-z0-9-]+)\.(jpg|png|webp|gif)$#', $webPath, $match) || !extension_loaded('gd') || !function_exists('imagewebp')) return null;
    $file = $uploadsDir . DIRECTORY_SEPARATOR . basename($webPath);
    $loader = ['jpg' => 'imagecreatefromjpeg', 'png' => 'imagecreatefrompng', 'webp' => 'imagecreatefromwebp', 'gif' => 'imagecreatefromgif'][$match[2]];
    if (!is_file($file) || !function_exists($loader)) return null;
    $image = @$loader($file);
    if ($image === false) return null;
    $thumbDir = $uploadsDir . DIRECTORY_SEPARATOR . 't';
    $thumbName = $match[1] . '.webp';
    $ok = false;
    if (is_dir($thumbDir) || mkdir($thumbDir, 0775, true) || is_dir($thumbDir)) {
        if (imagesx($image) > $width) $image = imagescale($image, $width, -1, IMG_BICUBIC) ?: $image;
        $ok = imagewebp($image, $thumbDir . DIRECTORY_SEPARATOR . $thumbName, 68);
    }
    return $ok ? 'uploads/t/' . $thumbName : null;
}
