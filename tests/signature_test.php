<?php

/**
 * Plain PHP test (no framework). Run: php tests/signature_test.php
 */

require __DIR__ . '/../src/JomabeePayment.php';

$secret = 'whsec_test';
$j = new JomabeePayment('key', $secret, 'https://pay.kodbee.com');

$payload = ['event' => 'payment.verified', 'invoice_id' => 'JOMB-1', 'amount' => 500.0];
$body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$sig = hash_hmac('sha256', $body, $secret);

$pass = 0;
$fail = 0;
$check = function ($name, $cond) use (&$pass, &$fail) {
    if ($cond) {
        $pass++;
        echo "PASS $name\n";
    } else {
        $fail++;
        echo "FAIL $name\n";
    }
};

$check('valid signature', $j->verifySignature($body, $sig) === true);
$check('tampered body rejected', $j->verifySignature('{"amount":999}', $sig) === false);
$check('empty signature rejected', $j->verifySignature($body, '') === false);
$check('parseWebhook returns event', $j->parseWebhook($body, $sig)['invoice_id'] === 'JOMB-1');

$threw = false;
try {
    $j->parseWebhook('{"x":1}', 'deadbeef');
} catch (RuntimeException $e) {
    $threw = true;
}
$check('parseWebhook throws on bad signature', $threw);

echo "\n$pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
