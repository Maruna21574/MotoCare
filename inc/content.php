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

if (is_file($galleryFile)) {
    $gallery = readJsonFile($galleryFile);
} else {
    $gallery = array_map(static fn (string $src): array => ['src' => $src, 'slider' => !in_array($src, $sliderSkip, true)], $galleryImages);
}
$galleryImages = array_column($gallery, 'src');
$sliderImages = array_column(array_filter($gallery, static fn (array $item): bool => !empty($item['slider'])), 'src');

/** One-line address, e.g. "Kollárova, 036 01 Martin". */
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
    global $uploadsDir, $productsFile, $servicesFile, $galleryFile, $textsFile;
    if (!preg_match('#^uploads/[a-z0-9-]+\.(jpg|png|webp|gif)$#', $path)) return;
    foreach ([$productsFile, $servicesFile, $galleryFile, $textsFile] as $file) {
        if (is_file($file) && str_contains((string) file_get_contents($file), $path)) return;
    }
    @unlink($uploadsDir . DIRECTORY_SEPARATOR . basename($path));
}
