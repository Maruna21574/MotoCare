<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Bratislava');

$site = [
    'name' => 'MotoCare Pro',
    'email' => 'info@motocarepro.eu',
    'phone' => '+421 907 622 954',
    'facebook' => 'https://www.facebook.com/profile.php?id=61586104985065&locale=sk_SK',
    'instagram' => 'https://www.instagram.com/motocare_pro/',
    'street' => 'Kollárova',
    'zip' => '036 01',
    'city' => 'Martin',
    'country' => 'Slovensko',
    'tagline' => 'Ochrana a starostlivosť, ktorá nerieši len vzhľad.',
    'description' => 'MotoCare Pro Martin — profesionálna starostlivosť o motocykle, ochrana, korekcia laku a príprava na sezónu.',
    'map_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d5372.8868691143325!2d18.910572677987407!3d49.04974277135866!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4714fec04ad3fd23%3A0xd2e31076100f425c!2sKoll%C3%A1rova!5e1!3m2!1ssk!2ssk!4v1789848916878!5m2!1ssk!2ssk',
];
// Site details, services, products, gallery and page texts below are defaults; the admin saves its edits to data/*.json (see content.php).

$services = [
    'easy-care' => [
        'image' => 'img/web/pexels-kevin-bruhn-2160372194-36709685.webp',
        'name' => 'Základná starostlivosť', 'price' => 129, 'minutes' => 120,
        'label' => 'Perfektný základ', 'short' => 'Čistý a svieži základ pre každú ďalšiu jazdu.',
        'description' => 'Keď chceš mať motorku čistú, sviežu a pripravenú na ďalšiu jazdu. Bez skratiek, s dôrazom na detaily, ktoré bežné umytie vynechá.',
        'features' => ['Dôkladné umytie', 'Čistenie detailov', 'Ochrana povrchov', 'Svieži vzhľad', 'Čistenie kolies a diskov', 'Odporúčania na údržbu'],
        'details' => ['Predumytie a bezpečné odstránenie nečistôt', 'Čistenie diskov, reťaze, spojov a detailov', 'Ošetrenie plastov, pneumatík a exponovaných povrchov', 'Finálna kontrola a odporúčania pre domácu údržbu'],
    ],
    'protect' => [
        'image' => 'img/web/pexels-vanyaoboleninov-4513014.webp',
        'name' => 'Ochrana', 'price' => 349, 'minutes' => 240,
        'label' => 'Viac ochrany', 'short' => 'Kompletné čistenie a ochranné vrstvy pre pokojnejšie kilometre.',
        'description' => 'Kompletné čistenie a ochranné vrstvy pre motorku, ktorá jazdí v reálnych podmienkach a potrebuje viac než len pekný lesk.',
        'features' => ['Kompletné čistenie', 'Dekontaminácia', 'Ochranné vrstvy', 'Dlhšia životnosť povrchov', 'Ošetrenie plastov a gumy', 'Plán ďalšej starostlivosti'],
        'details' => ['Všetko zo služby Základná starostlivosť', 'Chemická a mechanická dekontaminácia povrchov', 'Ochrana laku, plastov, kovov a kolies', 'Odovzdanie s konkrétnym plánom ďalšej údržby'],
    ],
    'transform' => [
        'image' => 'img/web/max-itin-7GeprSfVqLQ-unsplash.webp',
        'name' => 'Premena', 'price' => 690, 'minutes' => 480,
        'label' => 'Najobľúbenejšia služba', 'badge' => 'Najžiadanejšie', 'short' => 'Viditeľná premena, korekcia laku a ochrana s dlhodobým zmyslom.',
        'description' => 'Služba pre motorku, pri ktorej chceš vidieť skutočný rozdiel. Korekcia laku, hĺbka, ochrana a jednoduchšia údržba.',
        'features' => ['Viackroková korekcia laku', 'Keramická ochrana', 'Ochrana všetkých povrchov', 'Jednoduchšia údržba', 'Hĺbkové čistenie interiéru', 'Finálne fotografie'],
        'details' => ['Všetko zo služby Ochrana', 'Viackroková korekcia podľa stavu laku', 'Keramická ochrana vybraných povrchov', 'Finálne fotografie a odporúčania pre ďalšie sezóny'],
    ],
    'showroom' => [
        'image' => 'BMW%20R1200%20GS/749714051_17881632771655565_6922466120028112296_n.jpg',
        'name' => 'Výstavná starostlivosť', 'price' => 1090, 'minutes' => 960,
        'label' => 'Pre tých, ktorí chcú najlepšie', 'short' => 'Prémiová starostlivosť od prvého detailu po výstavný vzhľad.',
        'description' => 'Najvyššia úroveň starostlivosti pre motorky, ktoré si zaslúžia maximálnu pozornosť, čas a individuálny prístup.',
        'features' => ['Všetko zo služby Premena', 'Prémiová starostlivosť o každý detail', '1:1 konzultácia', 'Osobné výhody', 'Kompletný plán údržby', 'Prioritný termín'],
        'details' => ['Kompletný rozsah služby Premena', 'Detailné čistenie a korekcia každého dostupného povrchu', 'Prémiové ochranné riešenie podľa používania motocykla', 'Osobná konzultácia a dlhodobý plán údržby'],
    ],
];

// Longer texts for service detail pages live in service-content.php.
foreach (require __DIR__ . '/service-content.php' as $serviceId => $serviceContent) {
    if (isset($services[$serviceId])) $services[$serviceId] += $serviceContent;
}

$products = [
    'kit' => ['brand' => 'S100', 'volume' => 750, 'form' => 'Sprej', 'name' => 'S100 čistič reťaze', 'price' => 39, 'tag' => 'Novinka', 'short' => 'Špeciálny čistič reťaze pre dôkladnú údržbu pohonu.', 'description' => 'Účinný čistič reťaze na odstránenie mastnoty a usadenín bez zbytočne agresívneho postupu.', 'features' => ['Čistenie reťaze a pohonu', 'Odstránenie mastnoty a nečistôt', 'Vhodné pre pravidelnú údržbu'], 'art' => 'kit', 'image' => 'produkty/Moto21403.webp'],
    'spray' => ['brand' => 'Meguiar\'s', 'volume' => 473, 'form' => 'Rozprašovač', 'name' => "Meguiar's čistič interiéru", 'price' => 24, 'tag' => 'Ochrana', 'short' => 'Rýchly čistič na plasty a citlivé povrchy.', 'description' => 'Praktický čistič na rýchle osvieženie plastov a povrchov, ktoré chceš po jazde udržať čisté a upravené.', 'features' => ['Rýchle čistenie povrchov', 'Svieži vzhľad bez mastného filmu', 'Jednoduchá aplikácia'], 'art' => 'spray', 'image' => 'produkty/AUPR4379.webp'],
    'microfiber' => ['brand' => 'SONAX', 'volume' => 300, 'form' => 'Aerosól', 'name' => 'SONAX Odstraňovač živice', 'price' => 12, 'tag' => 'Najobľúbenejšie', 'short' => 'Odstráni živicu, asfalt a odolné usadeniny z povrchov.', 'description' => 'Cielený čistič na živicu a ďalšie odolné nečistoty, ktoré bežné umytie často neodstráni.', 'features' => ['Odstránenie živice a asfaltu', 'Šetrné použitie na vybraných povrchoch', 'Praktické balenie do garáže'], 'art' => 'microfiber', 'image' => 'produkty/AUPR3744.webp'],
];

// Longer texts for product detail pages live in product-content.php.
foreach (require __DIR__ . "/product-content.php" as $productId => $productContent) {
    if (isset($products[$productId])) $products[$productId] += $productContent;
}

$galleryImages = [
    'BMW%20R1200%20GS/749714051_17881632771655565_6922466120028112296_n.jpg',
    'BMW%20R1200%20GS/749681235_17881632810655565_6863675666493123432_n.jpg',
    'BMW%20R1200%20GS/749119708_17881632801655565_638114461267837540_n.jpg',
    'BMW%20R1200%20GS/749108092_17881632822655565_283341050703064590_n.jpg',
    'BMW%20R1200%20GS/748499315_17881632867655565_227027196547307606_n.jpg',
    'BMW%20R1200%20GS/747753362_17881632783655565_7651361316339772478_n.jpg',
    'BMW%20R1200%20GS/747625309_17881632792655565_1785390746768443155_n.jpg',
    'BMW%20R1200%20GS/747613080_17881632852655565_691724319489751977_n.jpg',
    'img/web/karthegan-padmanaban-JNrqiBkxQ7Q-unsplash.webp',
    'img/web/max-itin-7GeprSfVqLQ-unsplash.webp',
    'img/web/pexels-emir-anik-45418356-12775165.webp',
    'img/web/pexels-entero-28887411.webp',
    'img/web/pexels-kevin-bruhn-2160372194-36709685.webp',
    'img/web/pexels-macxconverge-19960839.webp',
    'img/web/pexels-valeriya-13157926.webp',
    'img/web/pexels-vanyaoboleninov-4513014.webp',
    'img/web/pexels-vita-leonis-33843971-7061390.webp',
    'BMW%20R1200%20GS/748044221_17881632879655565_7110101881806191312_n.jpg',
];

// Photos from $galleryImages that should not appear in the homepage slider (e.g. infographics).
$sliderSkip = [
    'BMW%20R1200%20GS/748044221_17881632879655565_7110101881806191312_n.jpg',
];

$dataDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
$bookingsFile = $dataDir . DIRECTORY_SEPARATOR . 'bookings.json';
$ordersFile = $dataDir . DIRECTORY_SEPARATOR . 'orders.json';
$contactMessagesFile = $dataDir . DIRECTORY_SEPARATOR . 'contact-messages.json';
$contactPhotosDir = $dataDir . DIRECTORY_SEPARATOR . 'contact-photos';
$settingsFile = $dataDir . DIRECTORY_SEPARATOR . 'settings.json';
$blocksFile = $dataDir . DIRECTORY_SEPARATOR . 'blocks.json';
$adminFile = $dataDir . DIRECTORY_SEPARATOR . 'admin.json';
$mailLogFile = $dataDir . DIRECTORY_SEPARATOR . 'mail.log';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function readJsonFile(string $file): array
{
    if (!is_file($file)) return [];
    $decoded = json_decode(file_get_contents($file) ?: '[]', true);
    return is_array($decoded) ? $decoded : [];
}

function saveJsonFile(string $file, array $data): bool
{
    $directory = dirname($file);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) return false;
    $handle = fopen($file, 'c+');
    if ($handle === false) return false;
    $saved = false;
    if (flock($handle, LOCK_EX)) {
        ftruncate($handle, 0);
        rewind($handle);
        $saved = fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false;
        fflush($handle);
        flock($handle, LOCK_UN);
    }
    fclose($handle);
    return $saved;
}

function cleanText($value, int $maxLength = 255): string
{
    $value = strip_tags(trim((string) $value));
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}

function validDate(string $date): bool
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

function minutesFromTime(string $time): int
{
    [$hours, $minutes] = array_map('intval', explode(':', $time));
    return ($hours * 60) + $minutes;
}

function formatTime(int $minutes): string
{
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

function bookingOverlap(int $start, int $duration, int $otherStart, int $otherDuration): bool
{
    return $start < ($otherStart + $otherDuration) && ($start + $duration) > $otherStart;
}

/**
 * Read-modify-write a JSON file under one exclusive lock, so two parallel requests
 * (e.g. two customers booking the same slot) cannot overwrite each other.
 * $callback receives the current data by reference and may return any result value.
 */
function updateJsonFile(string $file, callable $callback)
{
    $directory = dirname($file);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('Data directory is not writable.');
    $handle = fopen($file, 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) throw new RuntimeException('Data file is not writable.');
    try {
        $decoded = json_decode(stream_get_contents($handle) ?: '[]', true);
        $data = is_array($decoded) ? $decoded : [];
        $result = $callback($data);
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        fflush($handle);
        return $result;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function newId(string $prefix): string
{
    return $prefix . '-' . strtoupper(bin2hex(random_bytes(4)));
}

/** Price per 100 ml, e.g. "5,20 € / 100 ml"; empty when the product has no volume. */
function productUnitPrice(array $product): string
{
    $volume = (int) ($product['volume'] ?? 0);
    return $volume > 0 ? number_format($product['price'] / $volume * 100, 2, ',', ' ') . ' € / 100 ml' : '';
}

require_once __DIR__ . '/booking.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/spam.php';
