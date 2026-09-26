<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/inc/config.php';

// Admin session, authentication, CSRF protection and flash messages.

const ADMIN_IDLE_TIMEOUT = 8 * 3600;
const ADMIN_MAX_ATTEMPTS = 5;
const ADMIN_LOCK_SECONDS = 15 * 60;

session_name('motocare_admin');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

function isLoggedIn(): bool
{
    if (empty($_SESSION['admin'])) return false;
    if (time() - (int) ($_SESSION['admin_seen'] ?? 0) > ADMIN_IDLE_TIMEOUT) { $_SESSION = []; return false; }
    $_SESSION['admin_seen'] = time();
    return true;
}

function adminPasswordHash(): string
{
    global $adminFile;
    return (string) (readJsonFile($adminFile)['password_hash'] ?? '');
}

function setAdminPassword(string $password): void
{
    global $adminFile;
    updateJsonFile($adminFile, static function (array &$admin) use ($password): void {
        $admin['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        $admin['updated_at'] = date('c');
    });
}

/** Seconds until the current IP may try again, or 0 when not locked. */
function loginLockRemaining(): int
{
    global $dataDir;
    $attempts = readJsonFile($dataDir . DIRECTORY_SEPARATOR . 'login-attempts.json')[clientKey()] ?? [];
    $recent = array_filter((array) $attempts, static fn ($time): bool => $time > time() - ADMIN_LOCK_SECONDS);
    return count($recent) >= ADMIN_MAX_ATTEMPTS ? max(1, (int) min($recent) + ADMIN_LOCK_SECONDS - time()) : 0;
}

function recordLoginAttempt(bool $success): void
{
    global $dataDir;
    $key = clientKey();
    updateJsonFile($dataDir . DIRECTORY_SEPARATOR . 'login-attempts.json', static function (array &$attempts) use ($key, $success): void {
        foreach ($attempts as $ip => $times) {
            $attempts[$ip] = array_values(array_filter((array) $times, static fn ($time): bool => $time > time() - ADMIN_LOCK_SECONDS));
            if ($attempts[$ip] === []) unset($attempts[$ip]);
        }
        if ($success) unset($attempts[$key]);
        else $attempts[$key][] = time();
    });
}

function clientKey(): string
{
    return hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    if (!hash_equals(csrfToken(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Neplatný formulár. Obnov stránku a skús to znova.');
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function takeFlashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function redirect(string $query = ''): void
{
    header('Location: index.php' . ($query !== '' ? '?' . $query : ''));
    exit;
}

function post(string $key, int $maxLength = 255): string
{
    return cleanText($_POST[$key] ?? '', $maxLength);
}

function statusBadge(string $status, array $labels): string
{
    return '<span class="badge badge--' . e($status) . '">' . e($labels[$status] ?? $status) . '</span>';
}

function adminCounts(): array
{
    global $bookingsFile, $ordersFile, $contactMessagesFile;
    $today = date('Y-m-d');
    return [
        'pending' => count(array_filter(readJsonFile($bookingsFile), static fn (array $b): bool => bookingStatus($b) === 'pending' && $b['date'] >= $today)),
        'orders' => count(array_filter(readJsonFile($ordersFile), static fn (array $o): bool => orderStatus($o) === 'new')),
        'messages' => count(array_filter(readJsonFile($contactMessagesFile), static fn (array $m): bool => empty($m['read']))),
    ];
}

function renderHeader(string $title, string $active = ''): void
{
    $counts = isLoggedIn() ? adminCounts() : null;
    $nav = [
        'bookings' => ['Rezervácie', $counts['pending'] ?? 0],
        'availability' => ['Dostupnosť', 0],
        'orders' => ['Objednávky', $counts['orders'] ?? 0],
        'messages' => ['Správy', $counts['messages'] ?? 0],
        'products' => ['Produkty', 0],
        'services' => ['Služby', 0],
        'gallery' => ['Galéria', 0],
        'texts' => ['Texty', 0],
        'emails' => ['E-maily', 0],
        'settings' => ['Nastavenia', 0],
    ];
    ?>
<!doctype html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> — MotoCare Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/favicon-32.png">
    <link rel="stylesheet" href="admin.css?v=<?= (int) filemtime(__DIR__ . '/admin.css') ?>">
</head>
<body>
<?php if ($counts !== null): ?>
    <header class="topbar">
        <a class="topbar-brand" href="index.php"><img src="../auto_care_logo.png" alt="MotoCare Pro"><span>Admin</span></a>
        <nav class="topbar-nav">
            <?php foreach ($nav as $page => [$label, $count]): ?>
            <a class="<?= $active === $page ? 'is-active' : '' ?>" href="index.php?p=<?= e($page) ?>"><?= e($label) ?><?php if ($count > 0): ?><b><?= (int) $count ?></b><?php endif; ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="topbar-actions"><a href="../index.php" target="_blank">Web ↗</a><form method="post" action="index.php"><?= csrfField() ?><input type="hidden" name="action" value="logout"><button type="submit">Odhlásiť</button></form></div>
    </header>
<?php endif; ?>
    <main class="page">
        <?php foreach (takeFlashes() as $flash): ?><div class="flash flash--<?= e($flash['type']) ?>"><?= nl2br(e($flash['message'])) ?></div><?php endforeach; ?>
<?php
}

function renderFooter(): void
{
    ?>
    </main>
    <script src="admin.js?v=<?= (int) filemtime(__DIR__ . '/admin.js') ?>"></script>
</body>
</html>
<?php
}
