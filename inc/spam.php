<?php
// Spam protection shared by the contact, booking and order forms:
// a hidden honeypot field and a signed timestamp of when the form was shown.
// The API additionally limits submissions per IP (rateLimited in api.php).

const SPAM_MIN_SECONDS = 3;
const SPAM_MAX_AGE = 12 * 3600;

function spamSecret(): string
{
    global $dataDir;
    $file = $dataDir . DIRECTORY_SEPARATOR . 'secret.json';
    $secret = (string) (readJsonFile($file)['form_secret'] ?? '');
    if ($secret === '') {
        $secret = bin2hex(random_bytes(32));
        updateJsonFile($file, static function (array &$data) use (&$secret): void {
            if (!empty($data['form_secret'])) $secret = (string) $data['form_secret'];
            else $data['form_secret'] = $secret;
        });
    }
    return $secret;
}

/** Hidden fields to put inside every public form. */
function spamFields(): string
{
    $time = (string) time();
    $token = $time . '.' . hash_hmac('sha256', $time, spamSecret());
    return '<div class="ct-trap" aria-hidden="true"><label>Nevypĺňaj<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>'
        . '<input type="hidden" name="form_ts" value="' . e($token) . '">';
}

/**
 * Checks submitted form data. Returns null when it looks human, 'bot' for a silent fake success,
 * or a message for the visitor when the form timing is off.
 */
function spamCheck(array $data): ?string
{
    if (trim((string) ($data['website'] ?? '')) !== '') return 'bot';
    [$time, $signature] = explode('.', (string) ($data['form_ts'] ?? ''), 2) + [1 => ''];
    if (!ctype_digit($time) || !hash_equals(hash_hmac('sha256', $time, spamSecret()), $signature)) return 'Formulár sa nepodarilo overiť. Obnov prosím stránku a skús to znova.';
    $age = time() - (int) $time;
    if ($age < SPAM_MIN_SECONDS) return 'bot';
    if ($age > SPAM_MAX_AGE) return 'Stránka bola otvorená príliš dlho. Obnov ju prosím a odošli formulár znova.';
    return null;
}

/** Messages full of links are almost always spam. */
function looksLikeLinkSpam(string ...$texts): bool
{
    return preg_match_all('#https?://|www\.#i', implode(' ', $texts)) > 2;
}
