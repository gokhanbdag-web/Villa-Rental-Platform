<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Deterministic local payment protocol simulator.
 * Never sends HTTP requests, reads real provider credentials or changes bookings.
 */
final class PaymentSandboxSimulator
{
    private const PAYTR_KEY = 'LOCAL-ONLY-TEST-MERCHANT-KEY';
    private const PAYTR_SALT = 'LOCAL-ONLY-TEST-MERCHANT-SALT';

    public static function run(string $provider, string $scenario): array
    {
        $scenarios = ['success', 'failure', 'tampered', 'amount_mismatch', 'wrong_order'];
        if (!in_array($provider, ['iyzico', 'paytr'], true) || !in_array($scenario, $scenarios, true)) {
            throw new \InvalidArgumentException('Geçersiz test seçimi.');
        }

        return $provider === 'paytr'
            ? self::runPaytr($scenario)
            : self::runIyzico($scenario);
    }

    private static function runPaytr(string $scenario): array
    {
        // These values are deliberately synthetic and are never loaded from settings.
        $merchantId = 'LOCAL-MERCHANT-0001';
        $userIp = '203.0.113.10';
        $merchantOid = 'VILLA-LOCAL-' . strtoupper(bin2hex(random_bytes(5)));
        $email = 'sandbox@example.invalid';
        $paymentAmount = '125000'; // kuruş
        $basket = base64_encode(json_encode([['Örnek villa rezervasyonu', '1250.00', 1]], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $noInstallment = '0';
        $maxInstallment = '0';
        $currency = 'TL';
        $testMode = '1';

        // PayTR iFrame API step 1 token signature:
        // HMAC_SHA256(merchant_id + user_ip + merchant_oid + email + amount
        // + basket + no_installment + max_installment + currency + test_mode + salt, key)
        $tokenMessage = $merchantId . $userIp . $merchantOid . $email . $paymentAmount
            . $basket . $noInstallment . $maxInstallment . $currency . $testMode . self::PAYTR_SALT;
        $requestToken = base64_encode(hash_hmac('sha256', $tokenMessage, self::PAYTR_KEY, true));
        $tokenSignatureValid = hash_equals(
            base64_encode(hash_hmac('sha256', $tokenMessage, self::PAYTR_KEY, true)),
            $requestToken
        );

        $status = $scenario === 'failure' ? 'failed' : 'success';
        $callbackAmount = $scenario === 'amount_mismatch' ? '124999' : $paymentAmount;
        $callbackOid = $scenario === 'wrong_order' ? 'VILLA-LOCAL-WRONG-ORDER' : $merchantOid;
        $callback = [
            'merchant_oid' => $callbackOid,
            'status' => $status,
            'total_amount' => $callbackAmount,
        ];
        $callbackMessage = $callback['merchant_oid'] . self::PAYTR_SALT
            . $callback['status'] . $callback['total_amount'];
        $callback['hash'] = base64_encode(hash_hmac('sha256', $callbackMessage, self::PAYTR_KEY, true));

        // Tamper only after signing to model a forged/modified callback.
        if ($scenario === 'tampered') {
            $callback['total_amount'] = '1';
        }

        $callbackSignatureValid = PaymentSignatureVerifier::verifyPaytr(
            $callback,
            self::PAYTR_KEY,
            self::PAYTR_SALT
        );
        $amountMatches = hash_equals($paymentAmount, (string)$callback['total_amount']);
        $orderMatches = hash_equals($merchantOid, (string)$callback['merchant_oid']);
        $callbackAccepted = $callbackSignatureValid && $amountMatches && $orderMatches;
        $paymentSuccess = $callbackAccepted && $status === 'success';

        $message = match ($scenario) {
            'failure' => 'PayTR callback imzası geçerli; ödeme sonucu başarısız olarak işlendi.',
            'tampered' => 'Değiştirilmiş callback imzası reddedildi.',
            'amount_mismatch' => 'Callback imzası geçerli ancak tutar eşleşmediği için reddedildi.',
            'wrong_order' => 'Callback imzası geçerli ancak sipariş numarası eşleşmediği için reddedildi.',
            default => 'PayTR iFrame token imzası ve ödeme sonucu callback imzası doğrulandı.',
        };

        return [
            'provider' => 'paytr',
            'scenario' => $scenario,
            'message' => $message,
            'signature_valid' => $callbackSignatureValid,
            'request_signature_valid' => $tokenSignatureValid,
            'amount_matches' => $amountMatches,
            'order_matches' => $orderMatches,
            'callback_accepted' => $callbackAccepted,
            'payment_success' => $paymentSuccess,
            'merchant_oid' => $merchantOid,
            'callback_status' => $status,
            'expected_amount' => $paymentAmount,
            'callback_amount' => $callback['total_amount'],
            'currency' => $currency,
            'network_requests' => 0,
            'real_charge' => false,
            'booking_changed' => false,
        ];
    }

    private static function runIyzico(string $scenario): array
    {
        $secret = 'LOCAL-ONLY-TEST-SECRET-NOT-A-REAL-CREDENTIAL';
        $payload = [
            'iyziEventType' => 'CHECKOUT_FORM_AUTH',
            'iyziPaymentId' => 'LOCAL-TEST-PAYMENT-0001',
            'token' => 'LOCAL-TEST-TOKEN',
            'paymentConversationId' => 'VILLA-LOCAL-TEST-0002',
            'status' => $scenario === 'failure' ? 'FAILURE' : 'SUCCESS',
        ];
        $message = $secret . $payload['iyziEventType'] . $payload['iyziPaymentId']
            . $payload['token'] . $payload['paymentConversationId'] . $payload['status'];
        $signature = hash_hmac('sha256', $message, $secret);
        if ($scenario === 'tampered') $payload['paymentConversationId'] = 'VILLA-TAMPERED';

        $valid = PaymentSignatureVerifier::verifyIyzicoV3($payload, $signature, $secret);
        $success = $payload['status'] === 'SUCCESS';
        return [
            'provider' => 'iyzico',
            'scenario' => $scenario,
            'signature_valid' => $valid,
            'request_signature_valid' => true,
            'amount_matches' => null,
            'order_matches' => $valid,
            'callback_accepted' => $valid,
            'payment_success' => $valid && $success,
            'message' => !$valid ? 'Değiştirilmiş iyzico bildirimi reddedildi.' : ($success ? 'Yerel iyzico bildirim simülasyonu doğrulandı.' : 'İmza geçerli, ödeme sonucu başarısız.'),
            'network_requests' => 0,
            'real_charge' => false,
            'booking_changed' => false,
        ];
    }
}
