<?php
declare(strict_types=1);

// Stripe Checkout (hosted payment page) without an SDK: plain cURL calls to the Stripe API.
// Keys live in inc/local.php (not in git): 'stripe' => ['secret_key' => 'sk_...', 'webhook_secret' => 'whsec_...'].

function stripeConfig(): array
{
    global $local;
    $config = is_array($local['stripe'] ?? null) ? $local['stripe'] : [];
    return [
        'secret_key' => trim((string) ($config['secret_key'] ?? getenv('STRIPE_SECRET_KEY') ?: '')),
        'webhook_secret' => trim((string) ($config['webhook_secret'] ?? getenv('STRIPE_WEBHOOK_SECRET') ?: '')),
    ];
}

function stripeConfigured(): bool
{
    return str_starts_with(stripeConfig()['secret_key'], 'sk_');
}

/** Calls the Stripe API. Returns the decoded response or throws RuntimeException with a safe message. */
function stripeRequest(string $method, string $path, array $params = []): array
{
    if (!stripeConfigured()) throw new RuntimeException('Platba nie je nastavená.');
    $curl = curl_init('https://api.stripe.com/v1/' . ltrim($path, '/'));
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_USERPWD => stripeConfig()['secret_key'] . ':',
        CURLOPT_CUSTOMREQUEST => $method,
    ];
    if ($method === 'POST') $options[CURLOPT_POSTFIELDS] = http_build_query($params);
    curl_setopt_array($curl, $options);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $data = is_string($body) ? json_decode($body, true) : null;
    if (!is_array($data) || $status >= 400) {
        error_log('Stripe error ' . $status . ': ' . (is_string($body) ? substr($body, 0, 500) : 'no response'));
        throw new RuntimeException('Platobnú bránu sa nepodarilo kontaktovať.');
    }
    return $data;
}

/**
 * Creates a Checkout Session for one payment. $lines are [name, amount in EUR, quantity].
 * $kind is "order" or "booking" and $id the record id; both come back in the webhook and on the success page.
 */
function stripeCreateCheckout(string $kind, string $id, string $email, array $lines, string $successUrl, string $cancelUrl, int $expiresAt): array
{
    $params = [
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'customer_email' => $email,
        'client_reference_id' => $id,
        'locale' => 'sk',
        'expires_at' => $expiresAt,
        'metadata' => ['kind' => $kind, 'id' => $id],
        'payment_intent_data' => ['metadata' => ['kind' => $kind, 'id' => $id], 'description' => ($kind === 'booking' ? 'Rezervácia ' : 'Objednávka ') . $id],
    ];
    foreach (array_values($lines) as $index => [$name, $amount, $quantity]) {
        $params['line_items'][$index] = [
            'quantity' => max(1, (int) $quantity),
            'price_data' => ['currency' => 'eur', 'unit_amount' => (int) round((float) $amount * 100), 'product_data' => ['name' => mb_substr((string) $name, 0, 200)]],
        ];
    }
    return stripeRequest('POST', 'checkout/sessions', $params);
}

/** Verifies the Stripe-Signature header of a webhook (HMAC-SHA256 of "timestamp.payload", 5 minute tolerance). */
function stripeVerifySignature(string $payload, string $header, string $secret, int $tolerance = 300): bool
{
    if ($secret === '' || $header === '') return false;
    $timestamp = 0;
    $signatures = [];
    foreach (explode(',', $header) as $part) {
        [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($key === 't') $timestamp = (int) $value;
        if ($key === 'v1') $signatures[] = $value;
    }
    if ($timestamp === 0 || abs(time() - $timestamp) > $tolerance) return false;
    $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    foreach ($signatures as $signature) {
        if (hash_equals($expected, $signature)) return true;
    }
    return false;
}

/**
 * Marks the order or booking behind a PAID Checkout Session as paid and sends the confirmation e-mails once.
 * Safe to call repeatedly (the webhook and the success page both call it). Returns the record or null.
 */
function stripeApplyPaidSession(array $session): ?array
{
    global $ordersFile, $bookingsFile;
    if (($session['payment_status'] ?? '') !== 'paid') return null;
    $kind = (string) ($session['metadata']['kind'] ?? '');
    $id = (string) ($session['metadata']['id'] ?? '');
    $sessionId = (string) ($session['id'] ?? '');
    $intent = (string) ($session['payment_intent'] ?? '');
    $paidAt = date('c');
    $file = $kind === 'order' ? $ordersFile : ($kind === 'booking' ? $bookingsFile : '');
    if ($file === '' || $id === '') return null;
    $result = updateJsonFile($file, static function (array &$records) use ($id, $kind, $sessionId, $intent, $paidAt): ?array {
        foreach ($records as &$record) {
            if (($record['id'] ?? '') !== $id) continue;
            if (($record['stripe_session'] ?? '') !== $sessionId) return null; // a payment of another session, ignore it
            $first = empty($record['paid_at']);
            if ($first) {
                $record['paid_at'] = $paidAt;
                $record['payment_intent'] = $intent;
                if (($record['status'] ?? '') === 'unpaid') $record['status'] = $kind === 'order' ? 'new' : 'pending';
                unset($record['hold_until']);
            }
            return ['record' => $record, 'first' => $first];
        }
        return null;
    });
    if ($result === null) return null;
    if ($result['first']) {
        if ($kind === 'order') {
            sendOrderMail($result['record'], 'received');
            sendOrderOwnerMail($result['record']);
        } else {
            sendBookingMail($result['record'], 'received');
            sendBookingOwnerMail($result['record']);
        }
    }
    return $result['record'];
}
