<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal native-PHP iyzico API client. No SDK or Composer dependency.
 */
final class IyzicoApiClient
{
    private const SANDBOX_BASE_URL = 'https://sandbox-api.iyzipay.com';
    private const PRODUCTION_BASE_URL = 'https://api.iyzipay.com';

    public static function post(array $config, string $path, array $payload, ?string $environment = null): array
    {
        $apiKey = (string)($config['credentials']['api_key'] ?? '');
        $secretKey = (string)($config['credentials']['secret_key'] ?? '');
        if ($apiKey === '' || $secretKey === '') {
            throw new \RuntimeException('iyzico API anahtarları kayıtlı değil.');
        }
        if (!preg_match('#^/[a-zA-Z0-9/_-]+$#', $path)) {
            throw new \InvalidArgumentException('iyzico API yolu geçersiz.');
        }

        $environment ??= (string)($config['environment'] ?? 'sandbox');
        $baseUrl = $environment === 'production' ? self::PRODUCTION_BASE_URL : self::SANDBOX_BASE_URL;
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $randomKey = (string)round(microtime(true) * 1000) . bin2hex(random_bytes(8));
        $signature = hash_hmac('sha256', $randomKey . $path . $body, $secretKey);
        $authorizationPayload = 'apiKey:' . $apiKey . '&randomKey:' . $randomKey . '&signature:' . $signature;
        $headers = [
            'Authorization: IYZWSv2 ' . base64_encode($authorizationPayload),
            'x-iyzi-rnd: ' . $randomKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $url = $baseUrl . $path;
        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $raw = curl_exec($handle);
            $httpCode = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $curlError = curl_error($handle);
            curl_close($handle);
            if (!is_string($raw)) {
                throw new \RuntimeException('iyzico bağlantısı kurulamadı: ' . ($curlError !== '' ? $curlError : 'HTTP istemcisi yanıt vermedi.'));
            }
        } else {
            $context = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => 30,
                'ignore_errors' => true,
            ]]);
            $raw = @file_get_contents($url, false, $context);
            $httpCode = 0;
            foreach (($http_response_header ?? []) as $header) {
                if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $match)) $httpCode = (int)$match[1];
            }
            if (!is_string($raw)) {
                throw new \RuntimeException('iyzico bağlantısı kurulamadı. Sunucunun dış HTTPS bağlantısını kontrol edin.');
            }
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('iyzico geçerli JSON yanıtı döndürmedi (HTTP ' . $httpCode . ').');
        }
        if ($httpCode < 200 || $httpCode >= 300 || strtolower((string)($decoded['status'] ?? '')) !== 'success') {
            $message = trim((string)($decoded['errorMessage'] ?? ''));
            $code = trim((string)($decoded['errorCode'] ?? ''));
            throw new \RuntimeException('iyzico API testi başarısız' . ($code !== '' ? ' (' . $code . ')' : '') . ($message !== '' ? ': ' . $message : '.'));
        }
        return $decoded;
    }

    public static function initializeCheckoutForm(array $config, array $payload): array
    {
        return self::post($config, '/payment/iyzipos/checkoutform/initialize/auth/ecom', $payload);
    }

    public static function retrieveCheckoutForm(array $config, string $token, ?string $conversationId = null): array
    {
        if ($token === '' || strlen($token) > 128) {
            throw new \InvalidArgumentException('iyzico ödeme token değeri geçersiz.');
        }
        $conversationId = trim((string)$conversationId);
        if ($conversationId === '') {
            $conversationId = 'villa-retrieve-' . bin2hex(random_bytes(6));
        }
        if (strlen($conversationId) > 128) {
            throw new \InvalidArgumentException('iyzico işlem referansı geçersiz.');
        }
        return self::post($config, '/payment/iyzipos/checkoutform/auth/ecom/detail', [
            'locale' => 'tr',
            'conversationId' => $conversationId,
            'token' => $token,
        ]);
    }

    public static function testSandboxConnection(array $config): array
    {
        // BIN check validates API authentication without creating a payment or charging a card.
        return self::post($config, '/payment/bin/check', [
            'locale' => 'tr',
            'conversationId' => 'villa-sandbox-check-' . bin2hex(random_bytes(6)),
            'binNumber' => '535805',
        ], 'sandbox');
    }
}
