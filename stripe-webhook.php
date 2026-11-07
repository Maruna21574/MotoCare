<?php
declare(strict_types=1);

// Stripe webhook endpoint. In the Stripe dashboard add the endpoint https://<domain>/stripe-webhook.php
// for the events checkout.session.completed and checkout.session.async_payment_succeeded.
require_once __DIR__ . '/inc/config.php';

header('Content-Type: text/plain; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }

$payload = (string) file_get_contents('php://input');
if (!stripeVerifySignature($payload, (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''), stripeConfig()['webhook_secret'])) {
    http_response_code(400);
    exit('Invalid signature');
}

$event = json_decode($payload, true);
$type = (string) ($event['type'] ?? '');
if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true) && is_array($event['data']['object'] ?? null)) {
    stripeApplyPaidSession($event['data']['object']);
}
http_response_code(200);
echo 'ok';
