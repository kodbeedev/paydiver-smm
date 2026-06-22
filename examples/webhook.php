<?php

/**
 * Example: Paydiver webhook receiver that credits a panel user's balance.
 * Set this file's URL as `callback_url` when creating the payment.
 */

require __DIR__ . '/../src/PaydiverPayment.php';

$paydiver = new PaydiverPayment(
    getenv('PAYDIVER_API_KEY') ?: 'your_api_key',
    getenv('PAYDIVER_SECRET_KEY') ?: 'your_secret_key',
    getenv('PAYDIVER_BASE_URL') ?: 'https://pay.kodbee.com'
);

$raw = file_get_contents('php://input') ?: '';
$signature = isset($_SERVER['HTTP_X_PAYDIVER_SIGNATURE']) ? $_SERVER['HTTP_X_PAYDIVER_SIGNATURE'] : '';

try {
    $event = $paydiver->parseWebhook($raw, $signature);
} catch (RuntimeException $e) {
    http_response_code(400);
    echo 'invalid';
    exit;
}

if (($event['event'] ?? '') === 'payment.verified') {
    $invoiceId = $event['invoice_id'] ?? '';
    $amount = (float) ($event['amount'] ?? 0);

    // Look up the user you stored against $invoiceId, then credit their balance:
    //   $userId = lookup_user_by_invoice($invoiceId);
    //   add_balance($userId, $amount);  // make this idempotent on trx_id
}

http_response_code(200);
echo 'ok';
