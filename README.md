# Jomabee for SMM Panels

Drop-in PHP integration for the [Jomabee](https://kodbee.com) payment API by
**Kodbee** — built for SMM panels and other custom PHP apps that don't use
Composer. Single file, zero dependencies (cURL + JSON).

> Using Composer? Prefer [`kodbee/jomabee-php`](https://github.com/kodbeedev/jomabee-php).

## Install

Copy `src/JomabeePayment.php` into your project and `require` it.

```php
require __DIR__ . '/JomabeePayment.php';

$jomabee = new JomabeePayment('API_KEY', 'SECRET_KEY', 'https://pay.kodbee.com');
```

## Add Funds (deposit)

```php
$payment = $jomabee->createPayment([
    'amount'       => 100,
    'product_name' => 'Wallet top-up #'.$userId,
    'redirect_url' => 'https://your-panel.com/funds?status=done',
    'callback_url' => 'https://your-panel.com/jomabee-webhook.php',
]);

// store $payment['invoice_id'] -> $userId, then:
header('Location: '.$payment['payment_url']);
```

## Webhook

```php
$event = $jomabee->parseWebhook(
    file_get_contents('php://input'),
    $_SERVER['HTTP_X_JOMABEE_SIGNATURE'] ?? ''
); // throws on invalid signature

if ($event['event'] === 'payment.verified') {
    // credit the user mapped to $event['invoice_id'] by $event['amount']
    // (make it idempotent on $event['trx_id'])
}
```

See [`examples/`](examples/) for full deposit + webhook scripts.

## Test

```bash
php tests/signature_test.php
```

## License

MIT © [Kodbee](https://kodbee.com)
