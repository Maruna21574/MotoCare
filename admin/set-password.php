<?php
declare(strict_types=1);

// Sets the admin password from the command line: php admin/set-password.php "new password"
// Use it for the first setup or when the password is forgotten. Disabled over the web.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/inc/config.php';

$password = (string) ($argv[1] ?? '');
if (mb_strlen($password) < 10) {
    fwrite(STDERR, "Použitie: php admin/set-password.php \"nové heslo\" (aspoň 10 znakov)\n");
    exit(1);
}

updateJsonFile($adminFile, static function (array &$admin) use ($password): void {
    $admin['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    $admin['updated_at'] = date('c');
});
echo "Heslo administrácie je nastavené.\n";
