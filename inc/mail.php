<?php
declare(strict_types=1);

// E-mail notifications for bookings, shop orders and contact messages.
// Every e-mail is sent as HTML with a plain-text alternative via PHP mail();
// each attempt is written to data/mail.log so delivery problems are visible.

function siteUrl(string $path = ''): string
{
    global $local;
    // A configured base_url (inc/local.php) is trusted; the Host header comes from the visitor and could be forged.
    $configured = rtrim((string) ($local['base_url'] ?? ''), '/');
    if ($configured !== '') return $configured . '/' . ltrim($path, '/');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $host = preg_match('/^[a-z0-9.-]+(:\d{1,5})?$/i', (string) ($_SERVER['HTTP_HOST'] ?? '')) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $root = str_replace('\\', '/', (string) realpath(dirname(__DIR__)));
    $docRoot = rtrim(str_replace('\\', '/', (string) realpath($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $base = ($docRoot !== '' && stripos($root, $docRoot) === 0) ? substr($root, strlen($docRoot)) : '';
    return ($https ? 'https' : 'http') . '://' . $host . rtrim($base, '/') . '/' . ltrim($path, '/');
}

/**
 * A rendered e-mail: ['subject', 'html', 'text'].
 * $rows are [label, value] pairs shown as a details table; $button is [label, url] or null.
 */
function mailMessage(string $subject, string $title, string $greeting, string $intro, array $rows = [], string $note = '', ?array $button = null, string $outro = ''): array
{
    global $site;
    $rows = array_values(array_filter($rows, static fn (array $row): bool => (string) $row[1] !== ''));

    $text = ($greeting !== '' ? $greeting . "\n\n" : '') . $intro . "\n";
    if ($note !== '') $text .= "\n" . $note . "\n";
    if ($rows !== []) $text .= "\n" . implode("\n", array_map(static fn (array $row): string => $row[0] . ': ' . $row[1], $rows)) . "\n";
    if ($button !== null) $text .= "\n" . $button[0] . ': ' . $button[1] . "\n";
    if ($outro !== '') $text .= "\n" . $outro . "\n";
    $text .= "\n—\n{$site['name']}\n{$site['location']}\n{$site['phone']}\n{$site['email']}\n" . (companyMailLine() !== '' ? companyMailLine() . "\n" : '');

    $esc = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $rowsHtml = '';
    foreach ($rows as $index => [$label, $value]) {
        $border = $index === 0 ? '' : 'border-top:1px solid #ecebe6;';
        $rowsHtml .= '<tr><td style="padding:12px 0;' . $border . 'color:#7a7974;font-size:13px;width:38%;vertical-align:top;">' . $esc($label) . '</td>'
            . '<td style="padding:12px 0;' . $border . 'color:#0b0b0b;font-size:15px;font-weight:600;vertical-align:top;">' . nl2br($esc((string) $value)) . '</td></tr>';
    }
    $html = '<!doctype html><html lang="sk"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $esc($subject) . '</title></head>'
        . '<body style="margin:0;padding:0;background:#f1f0ec;font-family:Arial,Helvetica,sans-serif;color:#1c1c1a;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f0ec;"><tr><td align="center" style="padding:28px 12px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:10px;overflow:hidden;">'
        . '<tr><td style="background:#0b0b0b;padding:22px 28px;border-bottom:3px solid #ec5319;"><img src="' . $esc(siteUrl('auto_care_logo.png')) . '" alt="MotoCare Pro" width="150" style="display:block;border:0;width:150px;max-width:150px;height:auto;color:#ffffff;font-size:20px;font-weight:bold;"></td></tr>'
        . '<tr><td style="padding:32px 28px 8px;">'
        . '<h1 style="margin:0 0 18px;font-size:26px;line-height:1.2;color:#0b0b0b;text-transform:uppercase;letter-spacing:.5px;">' . $esc($title) . '</h1>'
        . ($greeting !== '' ? '<p style="margin:0 0 12px;font-size:16px;line-height:1.6;">' . $esc($greeting) . '</p>' : '')
        . '<p style="margin:0 0 18px;font-size:16px;line-height:1.6;">' . $esc($intro) . '</p>'
        . ($note !== '' ? '<p style="margin:0 0 18px;padding:14px 16px;background:#fff4ee;border-left:3px solid #ec5319;font-size:15px;line-height:1.6;">' . nl2br($esc($note)) . '</p>' : '')
        . ($rowsHtml !== '' ? '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 20px;border-top:1px solid #ecebe6;border-bottom:1px solid #ecebe6;">' . $rowsHtml . '</table>' : '')
        . ($button !== null ? '<p style="margin:24px 0;"><a href="' . $esc($button[1]) . '" style="display:inline-block;padding:14px 26px;background:#ec5319;color:#0b0b0b;border-radius:7px;font-size:14px;font-weight:bold;text-decoration:none;text-transform:uppercase;letter-spacing:.5px;">' . $esc($button[0]) . '</a></p>' : '')
        . ($outro !== '' ? '<p style="margin:0 0 18px;font-size:15px;line-height:1.6;color:#4f4e49;">' . $esc($outro) . '</p>' : '')
        . '</td></tr>'
        . '<tr><td style="padding:22px 28px;background:#f7f6f2;border-top:1px solid #ecebe6;font-size:13px;line-height:1.7;color:#6d6c67;">'
        . '<strong style="color:#0b0b0b;">' . $esc($site['name']) . '</strong><br>' . $esc($site['location']) . '<br>'
        . '<a href="tel:' . $esc(str_replace(' ', '', $site['phone'])) . '" style="color:#0b0b0b;">' . $esc($site['phone']) . '</a> · '
        . '<a href="mailto:' . $esc($site['email']) . '" style="color:#0b0b0b;">' . $esc($site['email']) . '</a>'
        . (companyMailLine() !== '' ? '<br><span style="font-size:12px;color:#8a8984;">' . $esc(companyMailLine()) . '</span>' : '')
        . '</td></tr></table></td></tr></table></body></html>';

    return ['subject' => $subject, 'html' => $html, 'text' => $text];
}

/** Company line for the e-mail footer, e.g. "NERION GROUP s. r. o. · Kollárova 85, 036 01 Martin · IČO 56225890". */
function companyMailLine(): string
{
    global $site;
    $parts = array_filter([trim((string) ($site['company_name'] ?? '')), trim((string) ($site['company_seat'] ?? '')), !empty($site['company_ico']) ? 'IČO ' . $site['company_ico'] : '']);
    return implode(' · ', $parts);
}

function sendMail(string $to, array $message, ?string $replyTo = null): bool
{
    global $site, $mailLogFile;
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    $from = $site['email'];
    $boundary = 'mc_' . bin2hex(random_bytes(12));
    $headers = [
        'From' => mb_encode_mimeheader($site['name'], 'UTF-8', 'B') . ' <' . $from . '>',
        'Reply-To' => $replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL) ? $replyTo : $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'multipart/alternative; boundary="' . $boundary . '"',
    ];
    $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($message['text']))
        . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($message['html']))
        . "--{$boundary}--\r\n";
    $sent = @mail($to, mb_encode_mimeheader($message['subject'], 'UTF-8', 'B'), $body, $headers, '-f' . $from);
    @file_put_contents($mailLogFile, sprintf("%s\t%s\t%s\t%s\n", date('c'), $sent ? 'OK' : 'FAIL', $to, $message['subject']), FILE_APPEND | LOCK_EX);
    return $sent;
}

// ---------------------------------------------------------------- Bookings

function bookingRows(array $booking): array
{
    return [
        ['Kód rezervácie', $booking['id']],
        ['Služba', $booking['service_name']],
        ['Termín', ucfirst(formatDateSk($booking['date'])) . ', ' . $booking['time'] . ' – ' . $booking['end_time']],
        ['Cena od', (int) $booking['price'] . ' €'],
        ['Motorka', $booking['bike'] ?? ''],
        ['Vyzdvihnutie na adrese', !empty($booking['pickup']) ? (string) $booking['pickup_address'] : ''],
        ['Poznámka', $booking['note'] ?? ''],
    ];
}

/** Customer e-mail types for bookings, one per status plus "received" and "changed". */
function bookingMailTypes(): array
{
    return [
        'received' => 'Rezervácia prijatá (nová rezervácia z webu)',
        'pending' => 'Rezervácia čaká na potvrdenie (stav vrátený späť)',
        'confirmed' => 'Rezervácia potvrdená',
        'changed' => 'Rezervácia upravená (zmena termínu alebo služby)',
        'cancelled' => 'Rezervácia zrušená',
        'done' => 'Rezervácia dokončená (poďakovanie)',
    ];
}

function bookingMail(array $booking, string $type, string $note = ''): ?array
{
    global $site;
    $id = $booking['id'];
    $greeting = 'Ahoj ' . $booking['name'] . ',';
    $rows = bookingRows($booking);
    $call = 'Ak sa ti termín nehodí, stačí odpovedať na tento e-mail alebo zavolať na ' . $site['phone'] . '.';
    switch ($type) {
        case 'received':
            return mailMessage('Prijali sme tvoju rezerváciu ' . $id, 'Rezervácia prijatá', $greeting, 'ďakujeme za rezerváciu. Termín sme si zapísali a čoskoro ti ho potvrdíme e-mailom.', $rows, $note, null, $call);
        case 'pending':
            return mailMessage('Rezervácia ' . $id . ' čaká na potvrdenie', 'Čaká na potvrdenie', $greeting, 'tvoja rezervácia opäť čaká na potvrdenie. Ozveme sa ti čo najskôr.', $rows, $note, null, $call);
        case 'confirmed':
            return mailMessage('Rezervácia ' . $id . ' je potvrdená', 'Termín je potvrdený', $greeting, 'tvoj termín je potvrdený. Tešíme sa na teba aj na tvoju motorku.', $rows, $note, null, $call);
        case 'changed':
            return mailMessage('Zmena rezervácie ' . $id, 'Rezervácia upravená', $greeting, 'upravili sme tvoju rezerváciu. Aktuálne údaje nájdeš nižšie.', $rows, $note, null, $call);
        case 'cancelled':
            return mailMessage('Rezervácia ' . $id . ' bola zrušená', 'Rezervácia zrušená', $greeting, 'tvoja rezervácia bola zrušená. Ak si chceš vybrať iný termín, urobíš to online za minútu.', $rows, $note, ['Vybrať nový termín', siteUrl('rezervacia')]);
        case 'done':
            return mailMessage('Ďakujeme za návštevu MotoCare Pro', 'Hotovo, ďakujeme', $greeting, 'ďakujeme, že si nám zveril svoju motorku. Ak budeš mať otázky k údržbe, kedykoľvek sa ozvi. Radi ťa uvidíme znova.', [['Služba', $booking['service_name']], ['Termín', ucfirst(formatDateSk($booking['date']))]], $note, ['Rezervovať ďalší termín', siteUrl('rezervacia')]);
    }
    return null;
}

function sendBookingMail(array $booking, string $type, string $note = ''): bool
{
    $message = bookingMail($booking, $type, $note);
    return $message !== null && sendMail($booking['email'], $message);
}

function bookingOwnerMail(array $booking): array
{
    $rows = array_merge(bookingRows($booking), [['Zákazník', $booking['name']], ['Telefón', $booking['phone']], ['E-mail', $booking['email']]]);
    return mailMessage(
        'Nová rezervácia ' . $booking['id'] . ' – ' . formatDateSk($booking['date'], false) . ' ' . $booking['time'],
        'Nová rezervácia', '', 'Z webu prišla nová rezervácia. Čaká na tvoje potvrdenie.',
        $rows, '', ['Otvoriť v administrácii', siteUrl('admin/index.php?p=booking&id=' . rawurlencode($booking['id']))]
    );
}

function sendBookingOwnerMail(array $booking): bool
{
    return sendMail(notifyEmail(), bookingOwnerMail($booking), $booking['email']);
}

// ---------------------------------------------------------------- Orders

function orderRows(array $order): array
{
    $rows = [['Číslo objednávky', $order['id']]];
    foreach ($order['items'] as $item) $rows[] = [(int) $item['quantity'] . '× ' . $item['name'], (int) $item['price'] * (int) $item['quantity'] . ' €'];
    $rows[] = ['Spolu', (int) $order['total'] . ' € (platba na dobierku)'];
    $rows[] = ['Doručenie', $order['customer']['address']];
    return $rows;
}

/** Customer e-mail types for orders, one per status. */
function orderMailTypes(): array
{
    return [
        'received' => 'Objednávka prijatá (nová objednávka / stav Nová)',
        'confirmed' => 'Objednávka potvrdená',
        'shipped' => 'Objednávka odoslaná',
        'done' => 'Objednávka vybavená (poďakovanie)',
        'cancelled' => 'Objednávka zrušená',
    ];
}

function orderMail(array $order, string $type, string $note = ''): ?array
{
    $id = $order['id'];
    $greeting = 'Ahoj ' . $order['customer']['name'] . ',';
    $rows = orderRows($order);
    switch ($type) {
        case 'received':
            return mailMessage('Prijali sme tvoju objednávku ' . $id, 'Objednávka prijatá', $greeting, 'ďakujeme za objednávku. Skontrolujeme ju a ozveme sa ti kvôli doručeniu.', $rows, $note);
        case 'confirmed':
            return mailMessage('Objednávka ' . $id . ' je potvrdená', 'Objednávka potvrdená', $greeting, 'tvoju objednávku sme potvrdili a pripravujeme ju na odoslanie.', $rows, $note);
        case 'shipped':
            return mailMessage('Objednávka ' . $id . ' je na ceste', 'Objednávka odoslaná', $greeting, 'tvoja objednávka je odoslaná. Zaplatíš pri prevzatí.', $rows, $note);
        case 'done':
            return mailMessage('Ďakujeme za nákup – ' . $id, 'Objednávka vybavená', $greeting, 'tvoja objednávka je vybavená. Ďakujeme za nákup a nech ti produkty dobre slúžia.', $rows, $note, ['Pozrieť ďalšie produkty', siteUrl('shop')]);
        case 'cancelled':
            return mailMessage('Objednávka ' . $id . ' bola zrušená', 'Objednávka zrušená', $greeting, 'tvoja objednávka bola zrušená. Ak ide o omyl, stačí odpovedať na tento e-mail.', $rows, $note);
    }
    return null;
}

function sendOrderMail(array $order, string $type, string $note = ''): bool
{
    $message = orderMail($order, $type, $note);
    return $message !== null && sendMail($order['customer']['email'], $message);
}

function orderOwnerMail(array $order): array
{
    $customer = $order['customer'];
    $rows = array_merge(orderRows($order), [
        ['Zákazník', $customer['name']], ['Telefón', $customer['phone']], ['E-mail', $customer['email']],
        ['IČO', $customer['ico'] ?? ''], ['DIČ', $customer['dic'] ?? ''], ['Poznámka', $customer['note'] ?? ''],
    ]);
    return mailMessage('Nová objednávka ' . $order['id'] . ' – ' . (int) $order['total'] . ' €', 'Nová objednávka', '', 'Z e-shopu prišla nová objednávka na dobierku.', $rows, '', ['Otvoriť v administrácii', siteUrl('admin/index.php?p=orders')]);
}

function sendOrderOwnerMail(array $order): bool
{
    return sendMail(notifyEmail(), orderOwnerMail($order), $order['customer']['email']);
}

// ---------------------------------------------------------------- Contact form

function contactOwnerMail(array $message): array
{
    return mailMessage('Správa z webu – ' . $message['name'], 'Nová správa z webu', '', 'Cez kontaktný formulár prišla nová správa. Odpovedať môžeš priamo na tento e-mail.', [['Meno', $message['name']], ['E-mail', $message['email']], ['Telefón', $message['phone']], ['Správa', $message['message']], ['Fotky motorky', !empty($message['photos']) ? count($message['photos']) . ' ks – nájdeš ich v administrácii' : '']], '', ['Otvoriť v administrácii', siteUrl('admin/index.php?p=messages')]);
}

function contactCustomerMail(array $message): array
{
    return mailMessage('Prijali sme tvoju správu', 'Správa prijatá', 'Ahoj ' . $message['name'] . ',', 'ďakujeme za správu. Ozveme sa ti čo najskôr, zvyčajne do 24 hodín.', [['Tvoja správa', $message['message']]]);
}

function sendContactMails(array $message): void
{
    sendMail(notifyEmail(), contactOwnerMail($message), $message['email']);
    sendMail($message['email'], contactCustomerMail($message));
}
