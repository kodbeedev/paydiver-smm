<?php

/**
 * Jomabee payment helper for SMM panels and other custom PHP apps — by Kodbee.
 *
 * Single-file, zero-dependency (cURL + JSON). Drop it into any PHP project and
 * `require` it — no Composer or namespace required. If your panel uses Composer,
 * prefer the `kodbee/jomabee-php` package instead.
 *
 * @see https://kodbee.com
 */
class JomabeePayment
{
    /** @var string */
    private $baseUrl;
    /** @var string */
    private $apiKey;
    /** @var string */
    private $secretKey;
    /** @var int */
    private $timeout;

    public function __construct($apiKey, $secretKey, $baseUrl = 'https://pay.kodbee.com', $timeout = 30)
    {
        if ($apiKey === '') {
            throw new InvalidArgumentException('API key is required.');
        }
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = (int) $timeout;
    }

    /**
     * Create a deposit/payment and get a hosted payment URL.
     *
     * @param array $params amount (required), product_name (required),
     *                      customer_name, customer_email, redirect_url, callback_url
     * @return array invoice_id, payment_url, qr_code, amount, expires_at
     * @throws RuntimeException
     */
    public function createPayment(array $params)
    {
        $ch = curl_init($this->baseUrl . '/api/v1/payment/create');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'X-API-Key: ' . $this->apiKey,
                'X-Secret-Key: ' . $this->secretKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($params),
        ]);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Jomabee request failed: ' . $err);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode((string) $raw, true);
        if (! is_array($body) || $status >= 400 || (isset($body['success']) && $body['success'] === false)) {
            $message = isset($body['error']['message']) ? $body['error']['message'] : 'Jomabee API error.';
            throw new RuntimeException($message);
        }

        return isset($body['data']) ? $body['data'] : [];
    }

    /**
     * Verify a webhook signature against the raw request body.
     */
    public function verifySignature($rawBody, $signature)
    {
        if ($signature === '' || $this->secretKey === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $this->secretKey), (string) $signature);
    }

    /**
     * Verify and decode a webhook. Throws on invalid signature/body.
     *
     * @return array
     * @throws RuntimeException
     */
    public function parseWebhook($rawBody, $signature)
    {
        if (! $this->verifySignature($rawBody, $signature)) {
            throw new RuntimeException('Invalid webhook signature.');
        }
        $event = json_decode((string) $rawBody, true);
        if (! is_array($event)) {
            throw new RuntimeException('Invalid webhook body.');
        }

        return $event;
    }
}
