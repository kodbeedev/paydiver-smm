<?php

/**
 * Example: start an "Add Funds" deposit from an SMM panel.
 * Adapt the user/order lookup to your panel's database.
 */

require __DIR__ . '/../src/PaydiverPayment.php';

$paydiver = new PaydiverPayment(
    getenv('PAYDIVER_API_KEY') ?: 'your_api_key',
    getenv('PAYDIVER_SECRET_KEY') ?: 'your_secret_key',
    getenv('PAYDIVER_BASE_URL') ?: 'https://pay.kodbee.com'
);

// Amount the user wants to deposit (validate/server-side in real code).
$amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 100.0;
$userId = 12345; // current panel user id

try {
    $payment = $paydiver->createPayment([
        'amount' => $amount,
        'product_name' => 'Wallet top-up #' . $userId,
        'redirect_url' => 'https://your-panel.com/funds?status=done',
        // Encode the user id in your webhook handler via the invoice mapping you store.
        'callback_url' => 'https://your-panel.com/paydiver-webhook.php',
    ]);

    // Persist invoice_id -> userId so the webhook can credit the right account.
    // e.g. INSERT INTO paydiver_deposits (invoice_id, user_id, amount) VALUES (...)

    header('Location: ' . $payment['payment_url']);
    exit;
} catch (RuntimeException $e) {
    http_response_code(502);
    echo 'Payment could not be started: ' . htmlspecialchars($e->getMessage());
}
