<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/config.php';

function apiResponse(array $payload, int $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requestData(): array
{
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $decoded = json_decode(file_get_contents('php://input') ?: '{}', true);
        return is_array($decoded) ? $decoded : [];
    }
    return $_POST;
}

/** Stops spam: bots get a fake success so they do not retry, people get a message. */
function guardSpam(array $data, array $fakeSuccess): void
{
    $result = spamCheck($data);
    if ($result === 'bot') apiResponse(['ok' => true] + $fakeSuccess);
    if ($result !== null) apiResponse(['ok' => false, 'message' => $result], 422);
}

/** Validated contact-form photos as [[tmp path, extension], ...]; responds with an error on bad input. */
function contactPhotoUploads(): array
{
    $files = $_FILES['photos'] ?? null;
    if (!$files || !is_array($files['name'])) return [];
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/heic' => 'heic', 'image/heif' => 'heic'];
    $uploads = [];
    foreach ($files['name'] as $i => $originalName) {
        $error = $files['error'][$i];
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || $files['size'][$i] > 8 * 1024 * 1024) apiResponse(['ok' => false, 'message' => 'Fotka môže mať najviac 8 MB.'], 422);
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($files['tmp_name'][$i])) apiResponse(['ok' => false, 'message' => 'Fotku sa nepodarilo nahrať. Skús to prosím znova.'], 422);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($files['tmp_name'][$i]);
        if (!isset($types[$mime])) apiResponse(['ok' => false, 'message' => 'Nahrať môžeš iba fotky vo formáte JPG, PNG, WEBP alebo HEIC.'], 422);
        $uploads[] = [$files['tmp_name'][$i], $types[$mime]];
    }
    if (count($uploads) > 4) apiResponse(['ok' => false, 'message' => 'Nahrať môžeš najviac 4 fotky.'], 422);
    return $uploads;
}

/** Simple per-IP limit for public forms, e.g. at most 6 submissions per hour. */
function rateLimited(string $action, int $max, int $window): bool
{
    global $dataDir;
    $key = $action . ':' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return updateJsonFile($dataDir . DIRECTORY_SEPARATOR . 'ratelimit.json', static function (array &$hits) use ($key, $max, $window): bool {
        $now = time();
        foreach ($hits as $hitKey => $times) {
            $hits[$hitKey] = array_values(array_filter((array) $times, static fn ($time): bool => $time > $now - $window));
            if ($hits[$hitKey] === []) unset($hits[$hitKey]);
        }
        if (count($hits[$key] ?? []) >= $max) return true;
        $hits[$key][] = $now;
        return false;
    });
}

/** Stores the Stripe session on a booking or order so the payment can be matched later. */
function attachStripeSession(string $file, string $id, array $session): void
{
    updateJsonFile($file, static function (array &$records) use ($id, $session): void {
        foreach ($records as &$record) {
            if (($record['id'] ?? '') === $id) { $record['stripe_session'] = (string) $session['id']; return; }
        }
    });
}

/** Cancels an unpaid record when the payment page could not be created, so its slot is free again. */
function discardUnpaid(string $file, string $id): void
{
    updateJsonFile($file, static function (array &$records) use ($id): void {
        foreach ($records as &$record) {
            if (($record['id'] ?? '') === $id && ($record['status'] ?? '') === 'unpaid') $record['status'] = 'cancelled';
        }
    });
}

/** Answer for the browser after a payment session is created: the embedded form (client_secret) or a redirect address. */
function paymentResponse(array $session): void
{
    if (stripeEmbedded() && !empty($session['client_secret'])) {
        apiResponse(['ok' => true, 'message' => 'Platba pripravená.', 'client_secret' => $session['client_secret'], 'publishable_key' => stripeConfig()['publishable_key']]);
    }
    apiResponse(['ok' => true, 'message' => 'Presmerovávame na platbu.', 'checkout_url' => $session['url'] ?? '']);
}

$action = cleanText($_GET['action'] ?? '', 50);

if ($action === 'availability') {
    $date = cleanText($_GET['date'] ?? '', 10);
    $serviceId = cleanText($_GET['service'] ?? '', 50);
    if (!validDate($date) || !isset($services[$serviceId])) apiResponse(['ok' => false, 'message' => 'Vyber platný dátum a službu.'], 422);
    $slots = availableSlots($date, (int) $services[$serviceId]['minutes'], readJsonFile($bookingsFile), readJsonFile($blocksFile));
    $hours = dayHours($date);
    $wholeDay = $hours !== null && (int) $services[$serviceId]['minutes'] >= $hours[1] - $hours[0];
    apiResponse(['ok' => true, 'date' => $date, 'label' => formatDateSk($date), 'whole_day' => $wholeDay, 'slots' => $slots]);
}

if ($action === 'month') {
    $month = cleanText($_GET['month'] ?? '', 7);
    $serviceId = cleanText($_GET['service'] ?? '', 50);
    if (!preg_match('/^\d{4}-\d{2}$/', $month) || !validDate($month . '-01') || !isset($services[$serviceId])) apiResponse(['ok' => false, 'message' => 'Neplatný mesiac alebo služba.'], 422);
    apiResponse(['ok' => true, 'month' => $month, 'days' => monthAvailability($month, (int) $services[$serviceId]['minutes'], readJsonFile($bookingsFile), readJsonFile($blocksFile))]);
}

if ($action === 'book' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = requestData();
    guardSpam($data, ['message' => 'Termín je rezervovaný.', 'booking' => ['id' => newId('MC')]]);
    $serviceId = cleanText($data['service'] ?? '', 50);
    $date = cleanText($data['date'] ?? '', 10);
    $time = cleanText($data['time'] ?? '', 5);
    $name = cleanText($data['name'] ?? '', 100);
    $phone = cleanText($data['phone'] ?? '', 50);
    $email = cleanText($data['email'] ?? '', 120);
    $bike = cleanText($data['bike'] ?? '', 120);
    $note = cleanText($data['note'] ?? '', 800);
    $pickup = !empty($data['pickup']);
    $pickupAddress = $pickup ? cleanText($data['pickup_address'] ?? '', 240) : '';
    if ($pickup && $pickupAddress === '') apiResponse(['ok' => false, 'message' => 'Zadaj prosím adresu, kde máme motorku vyzdvihnúť.'], 422);
    if (!isset($services[$serviceId]) || !validDate($date) || !preg_match('/^\d{2}:\d{2}$/', $time) || $name === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) apiResponse(['ok' => false, 'message' => 'Skontroluj prosím všetky povinné údaje.'], 422);
    if (empty($data['consent'])) apiResponse(['ok' => false, 'message' => 'Na odoslanie rezervácie potrebujeme tvoj súhlas so spracovaním údajov.'], 422);
    $payment = ($data['payment'] ?? '') === 'onsite' ? 'onsite' : 'online';
    if ($payment === 'online' && !stripeConfigured()) apiResponse(['ok' => false, 'message' => 'Platba kartou momentálne nie je dostupná. Vyber platbu na mieste alebo nám zavolaj.'], 503);
    if (rateLimited('book', 6, 3600)) apiResponse(['ok' => false, 'message' => 'Z tvojho pripojenia prišlo priveľa rezervácií. Zavolaj nám prosím.'], 429);

    $holdUntil = time() + 1900; // Stripe needs a session of at least 30 minutes; the slot is held for that long
    $service = $services[$serviceId];
    $blocks = readJsonFile($blocksFile);
    try {
        $booking = updateJsonFile($bookingsFile, static function (array &$bookings) use ($service, $serviceId, $holdUntil, $payment, $date, $time, $name, $phone, $email, $bike, $note, $pickup, $pickupAddress, $blocks): ?array {
            $slots = availableSlots($date, (int) $service['minutes'], $bookings, $blocks);
            $selected = array_values(array_filter($slots, static fn (array $slot): bool => $slot['time'] === $time && $slot['available']));
            if ($selected === []) return null;
            $booking = [
                'id' => newId('MC'), 'created_at' => date('c'), 'status' => $payment === 'online' ? 'unpaid' : 'pending', 'source' => 'web', 'payment' => $payment,
                'service' => $serviceId, 'service_name' => $service['name'], 'price' => $service['price'],
                'duration' => occupiedMinutes($date, (int) $service['minutes']), 'date' => $date, 'time' => $time, 'end_time' => $selected[0]['end'],
                'name' => $name, 'phone' => $phone, 'email' => $email, 'bike' => $bike, 'note' => $note,
                'pickup' => $pickup, 'pickup_address' => $pickupAddress,
            ] + ($payment === 'online' ? ['hold_until' => date('c', $holdUntil)] : []);
            $bookings[] = $booking;
            return $booking;
        });
    } catch (RuntimeException $exception) {
        apiResponse(['ok' => false, 'message' => 'Rezerváciu sa nepodarilo uložiť. Skús nám prosím zavolať.'], 500);
    }
    if ($booking === null) apiResponse(['ok' => false, 'message' => 'Tento termín už nie je voľný. Vyber prosím iný.'], 409);
    if ($payment === 'onsite') {
        sendBookingMail($booking, 'received');
        sendBookingOwnerMail($booking);
        apiResponse(['ok' => true, 'message' => 'Termín je rezervovaný.', 'booking' => ['id' => $booking['id'], 'date' => $booking['date'], 'label' => formatDateSk($booking['date']), 'time' => $booking['time'], 'end_time' => $booking['end_time'], 'service_name' => $booking['service_name'], 'email' => $booking['email']]]);
    }
    try {
        $session = stripeCreateCheckout(
            'booking', $booking['id'], $booking['email'],
            [[$booking['service_name'] . ' — ' . formatDateSk($booking['date'], false) . ' ' . $booking['time'], $booking['price'], 1]],
            siteUrl('rezervacia?platba_id={CHECKOUT_SESSION_ID}'),
            siteUrl('rezervacia?service=' . rawurlencode($serviceId) . '&platba=zrusena'),
            $holdUntil
        );
        attachStripeSession($bookingsFile, $booking['id'], $session);
    } catch (RuntimeException $exception) {
        discardUnpaid($bookingsFile, $booking['id']);
        apiResponse(['ok' => false, 'message' => 'Platobnú bránu sa nepodarilo otvoriť. Skús to prosím znova alebo nám zavolaj.'], 502);
    }
    paymentResponse($session);
}

// The customer left the payment form: expire the Stripe session and free the unpaid booking / order.
if ($action === 'payment_cancel' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = requestData();
    $sessionId = cleanText($data['session_id'] ?? '', 200);
    if (!preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId)) apiResponse(['ok' => false, 'message' => 'Neplatná platba.'], 422);
    $sessionId = explode('_secret_', $sessionId)[0];
    foreach ([$bookingsFile, $ordersFile] as $file) {
        updateJsonFile($file, static function (array &$records) use ($sessionId): void {
            foreach ($records as &$record) {
                if (($record['stripe_session'] ?? '') === $sessionId && ($record['status'] ?? '') === 'unpaid' && empty($record['paid_at'])) $record['status'] = 'cancelled';
            }
        });
    }
    try { stripeRequest('POST', 'checkout/sessions/' . $sessionId . '/expire'); } catch (RuntimeException $exception) { /* already expired or paid */ }
    apiResponse(['ok' => true]);
}

if ($action === 'payment_confirm') {
    $sessionId = cleanText($_GET['session_id'] ?? '', 200);
    if (!preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId)) apiResponse(['ok' => false, 'message' => 'Neplatná platba.'], 422);
    try {
        $session = stripeRequest('GET', 'checkout/sessions/' . $sessionId);
    } catch (RuntimeException $exception) {
        apiResponse(['ok' => false, 'message' => 'Platbu sa nepodarilo overiť. Ak ti z účtu odišli peniaze, napíš nám prosím.'], 502);
    }
    $record = stripeApplyPaidSession($session);
    if ($record === null) apiResponse(['ok' => true, 'paid' => false, 'message' => 'Platba zatiaľ nebola dokončená.']);
    $kind = (string) ($session['metadata']['kind'] ?? '');
    if ($kind === 'booking') {
        apiResponse(['ok' => true, 'paid' => true, 'kind' => 'booking', 'booking' => ['id' => $record['id'], 'label' => formatDateSk($record['date']), 'time' => $record['time'], 'end_time' => $record['end_time'], 'service_name' => $record['service_name'], 'email' => $record['email'], 'price' => $record['price']]]);
    }
    apiResponse(['ok' => true, 'paid' => true, 'kind' => 'order', 'order' => ['id' => $record['id'], 'total' => $record['total'], 'email' => $record['customer']['email']]]);
}
if ($action === 'shop_order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = requestData();
    $customer = is_array($data['customer'] ?? null) ? $data['customer'] : [];
    $cart = is_array($data['cart'] ?? null) ? $data['cart'] : [];
    guardSpam($customer, ['message' => 'Objednávka prijatá.', 'order' => ['id' => newId('OBJ'), 'total' => 0]]);
    $name = cleanText($customer['name'] ?? '', 100);
    $phone = cleanText($customer['phone'] ?? '', 50);
    $email = cleanText($customer['email'] ?? '', 120);
    $address = cleanText($customer['address'] ?? '', 240);
    $ico = cleanText($customer['ico'] ?? '', 40);
    $dic = cleanText($customer['dic'] ?? '', 40);
    $note = cleanText($customer['note'] ?? '', 500);
    if ($name === '' || $phone === '' || $address === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $cart === []) apiResponse(['ok' => false, 'message' => 'Vyplň prosím kontaktné údaje a aspoň jeden produkt.'], 422);
    if (empty($customer['consent'])) apiResponse(['ok' => false, 'message' => 'Na odoslanie objednávky potrebujeme tvoj súhlas so spracovaním údajov.'], 422);
    $payment = ($customer['payment'] ?? '') === 'dobierka' ? 'dobierka' : 'online';
    if ($payment === 'online' && !stripeConfigured()) apiResponse(['ok' => false, 'message' => 'Platba kartou momentálne nie je dostupná. Vyber platbu na dobierku alebo nám zavolaj.'], 503);
    if (rateLimited('order', 6, 3600)) apiResponse(['ok' => false, 'message' => 'Z tvojho pripojenia prišlo priveľa objednávok. Zavolaj nám prosím.'], 429);
    $items = [];
    $total = 0;
    foreach ($cart as $item) {
        $id = cleanText($item['id'] ?? '', 80);
        $quantity = max(1, min(10, (int) ($item['quantity'] ?? 1)));
        if (!isset($products[$id]) || !productPriced($products[$id])) continue;
        $items[] = ['id' => $id, 'name' => $products[$id]['name'], 'quantity' => $quantity, 'price' => $products[$id]['price']];
        $total += $products[$id]['price'] * $quantity;
    }
    if ($items === []) apiResponse(['ok' => false, 'message' => 'Košík je prázdny.'], 422);
    $order = ['id' => newId('OBJ'), 'created_at' => date('c'), 'status' => $payment === 'online' ? 'unpaid' : 'new', 'payment' => $payment, 'customer' => ['name' => $name, 'phone' => $phone, 'email' => $email, 'address' => $address, 'ico' => $ico, 'dic' => $dic, 'note' => $note], 'items' => $items, 'total' => $total];
    try {
        updateJsonFile($ordersFile, static function (array &$orders) use ($order): void { $orders[] = $order; });
    } catch (RuntimeException $exception) {
        apiResponse(['ok' => false, 'message' => 'Objednávku sa nepodarilo uložiť.'], 500);
    }
    if ($payment === 'dobierka') {
        sendOrderMail($order, 'received');
        sendOrderOwnerMail($order);
        apiResponse(['ok' => true, 'message' => 'Objednávka prijatá.', 'order' => ['id' => $order['id'], 'total' => $total]]);
    }
    try {
        $session = stripeCreateCheckout(
            'order', $order['id'], $email,
            array_map(static fn (array $item): array => [$item['name'], $item['price'], $item['quantity']], $items),
            siteUrl('objednavka?platba_id={CHECKOUT_SESSION_ID}'),
            siteUrl('objednavka?platba=zrusena'),
            time() + 86000
        );
        attachStripeSession($ordersFile, $order['id'], $session);
    } catch (RuntimeException $exception) {
        discardUnpaid($ordersFile, $order['id']);
        apiResponse(['ok' => false, 'message' => 'Platobnú bránu sa nepodarilo otvoriť. Skús to prosím znova alebo nám zavolaj.'], 502);
    }
    paymentResponse($session);
}

if ($action === 'contact' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = requestData();
    $name = cleanText($data['name'] ?? '', 100);
    $email = cleanText($data['email'] ?? '', 120);
    $phone = cleanText($data['phone'] ?? '', 50);
    $message = cleanText($data['message'] ?? '', 1200);
    guardSpam($data, ['message' => 'Správa bola odoslaná.']);
    if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) apiResponse(['ok' => false, 'message' => 'Vyplň prosím meno, platný e-mail a správu.'], 422);
    if (empty($data['consent'])) apiResponse(['ok' => false, 'message' => 'Na odoslanie potrebujeme tvoj súhlas so spracovaním údajov.'], 422);
    if (looksLikeLinkSpam($message, $name)) apiResponse(['ok' => false, 'message' => 'Správa obsahuje priveľa odkazov. Pošli ju prosím bez nich.'], 422);
    $uploads = contactPhotoUploads();
    if (rateLimited('contact', 6, 3600)) apiResponse(['ok' => false, 'message' => 'Z tvojho pripojenia prišlo priveľa správ. Zavolaj nám prosím.'], 429);
    $id = newId('SPR');
    $photos = [];
    foreach ($uploads as $index => [$tmp, $ext]) {
        if (!is_dir($contactPhotosDir) && !mkdir($contactPhotosDir, 0775, true) && !is_dir($contactPhotosDir)) break;
        $file = $id . '-' . ($index + 1) . '.' . $ext;
        if (move_uploaded_file($tmp, $contactPhotosDir . DIRECTORY_SEPARATOR . $file)) $photos[] = $file;
    }
    $contactMessage = ['id' => $id, 'created_at' => date('c'), 'read' => false, 'name' => $name, 'email' => $email, 'phone' => $phone, 'message' => $message, 'photos' => $photos, 'consent' => true];
    try {
        updateJsonFile($contactMessagesFile, static function (array &$messages) use ($contactMessage): void { $messages[] = $contactMessage; });
    } catch (RuntimeException $exception) {
        foreach ($photos as $file) @unlink($contactPhotosDir . DIRECTORY_SEPARATOR . $file);
        apiResponse(['ok' => false, 'message' => 'Správu sa nepodarilo uložiť. Skús nám prosím zavolať.'], 500);
    }
    sendContactMails($contactMessage);
    apiResponse(['ok' => true, 'message' => 'Správa bola odoslaná.']);
}

apiResponse(['ok' => false, 'message' => 'Neznáma požiadavka.'], 404);
