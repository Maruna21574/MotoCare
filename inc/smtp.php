<?php
declare(strict_types=1);

// Minimal SMTP client (no library): TLS (port 465) or STARTTLS (port 587), AUTH LOGIN/PLAIN.
// Settings live in inc/local.php (not in git): 'smtp' => ['host' => ..., 'port' => 465, 'secure' => 'ssl'|'tls', 'user' => ..., 'password' => ...].

function smtpConfig(): array
{
    global $local;
    $config = is_array($local['smtp'] ?? null) ? $local['smtp'] : [];
    return [
        'host' => trim((string) ($config['host'] ?? '')),
        'port' => (int) ($config['port'] ?? 465),
        'secure' => (string) ($config['secure'] ?? 'ssl'),
        'user' => trim((string) ($config['user'] ?? '')),
        'password' => (string) ($config['password'] ?? ''),
        'from' => trim((string) ($config['from'] ?? '')),
    ];
}

function smtpConfigured(): bool
{
    $config = smtpConfig();
    return $config['host'] !== '' && $config['user'] !== '' && $config['password'] !== '';
}

/** Reads one (possibly multi-line) SMTP reply; returns [code, text]. */
function smtpRead($socket): array
{
    $text = '';
    while (($line = fgets($socket, 1024)) !== false) {
        $text .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') break;
    }
    return [(int) substr($text, 0, 3), trim($text)];
}

/** Sends a command and checks the reply code; throws RuntimeException on an unexpected answer. */
function smtpCommand($socket, string $command, array $expect, string $logLabel = ''): array
{
    fwrite($socket, $command . "\r\n");
    [$code, $text] = smtpRead($socket);
    if (!in_array($code, $expect, true)) throw new RuntimeException('SMTP ' . ($logLabel !== '' ? $logLabel : strtok($command, ' ')) . ': ' . $text);
    return [$code, $text];
}

/**
 * Sends a ready MIME message ($headers as "Name: value" lines, $body already encoded).
 * Throws RuntimeException with the server answer when something fails (the password is never included).
 */
function smtpSend(string $to, string $subject, string $body, array $headers): void
{
    $config = smtpConfig();
    $from = $config['from'] !== '' ? $config['from'] : $config['user'];
    $secure = $config['secure'] === 'ssl';
    $socket = @stream_socket_client(($secure ? 'ssl://' : 'tcp://') . $config['host'] . ':' . $config['port'], $errno, $error, 20, STREAM_CLIENT_CONNECT);
    if (!$socket) throw new RuntimeException('SMTP pripojenie zlyhalo: ' . $error);
    stream_set_timeout($socket, 20);
    try {
        [$code, $text] = smtpRead($socket);
        if ($code !== 220) throw new RuntimeException('SMTP uvítanie: ' . $text);
        $host = preg_replace('/[^a-z0-9.-]/i', '', (string) ($_SERVER['SERVER_NAME'] ?? 'localhost')) ?: 'localhost';
        smtpCommand($socket, 'EHLO ' . $host, [250]);
        if ($config['secure'] === 'tls') {
            smtpCommand($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('SMTP STARTTLS zlyhal.');
            smtpCommand($socket, 'EHLO ' . $host, [250]);
        }
        smtpCommand($socket, 'AUTH LOGIN', [334]);
        smtpCommand($socket, base64_encode($config['user']), [334], 'AUTH (meno)');
        smtpCommand($socket, base64_encode($config['password']), [235], 'AUTH (heslo)');
        smtpCommand($socket, 'MAIL FROM:<' . $from . '>', [250]);
        smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtpCommand($socket, 'DATA', [354]);
        $headerLines = '';
        foreach ($headers as $name => $value) $headerLines .= $name . ': ' . $value . "\r\n";
        $headerLines .= 'To: ' . $to . "\r\nSubject: " . $subject . "\r\nDate: " . date('r') . "\r\nMessage-ID: <" . bin2hex(random_bytes(10)) . '@' . (substr(strrchr($from, '@'), 1) ?: 'localhost') . ">\r\n";
        $data = preg_replace('/(?<!\r)\n/', "\r\n", $headerLines . "\r\n" . $body);
        $data = preg_replace('/^\./m', '..', $data); // dot-stuffing
        fwrite($socket, $data . "\r\n.\r\n");
        [$code, $text] = smtpRead($socket);
        if ($code !== 250) throw new RuntimeException('SMTP odoslanie: ' . $text);
        @fwrite($socket, "QUIT\r\n");
    } finally {
        fclose($socket);
    }
}
