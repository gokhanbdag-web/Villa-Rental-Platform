<?php
declare(strict_types=1);

namespace App\Core;

final class PaymentSignatureVerifier
{
    public static function verifyPaytrIframeToken(array $payload, string $merchantKey, string $merchantSalt): bool
    {
        $required = ['merchant_id','user_ip','merchant_oid','email','payment_amount','user_basket','no_installment','max_installment','currency','test_mode','paytr_token'];
        foreach ($required as $field) {
            if (!isset($payload[$field]) || !is_scalar($payload[$field]) || (string)$payload[$field] === '') return false;
        }
        if ($merchantKey === '' || $merchantSalt === '') return false;
        $message = (string)$payload['merchant_id'] . (string)$payload['user_ip']
            . (string)$payload['merchant_oid'] . (string)$payload['email']
            . (string)$payload['payment_amount'] . (string)$payload['user_basket']
            . (string)$payload['no_installment'] . (string)$payload['max_installment']
            . (string)$payload['currency'] . (string)$payload['test_mode'] . $merchantSalt;
        $expected = base64_encode(hash_hmac('sha256', $message, $merchantKey, true));
        return hash_equals($expected, (string)$payload['paytr_token']);
    }

    public static function verifyPaytr(array $payload, string $merchantKey, string $merchantSalt): bool
    {
        foreach (['merchant_oid', 'status', 'total_amount', 'hash'] as $field) {
            if (!isset($payload[$field]) || !is_scalar($payload[$field]) || (string)$payload[$field] === '') {
                return false;
            }
        }
        if ($merchantKey === '' || $merchantSalt === '') return false;

        $message = (string)$payload['merchant_oid'] . $merchantSalt
            . (string)$payload['status'] . (string)$payload['total_amount'];
        $expected = base64_encode(hash_hmac('sha256', $message, $merchantKey, true));
        return hash_equals($expected, (string)$payload['hash']);
    }

    public static function verifyIyzicoCheckoutFormRetrieve(array $response, string $secretKey): bool
    {
        if ($secretKey === '') return false;
        $fields = ['paymentStatus','paymentId','currency','basketId','conversationId','paidPrice','price','token','signature'];
        foreach ($fields as $field) {
            if (!isset($response[$field]) || !is_scalar($response[$field]) || (string)$response[$field] === '') return false;
        }
        $normalizeAmount = static function (string $amount): string {
            $amount = trim($amount);
            if (!preg_match('/^[0-9]+(?:\\.[0-9]+)?$/', $amount)) return '';
            if (str_contains($amount, '.')) $amount = rtrim(rtrim($amount, '0'), '.');
            return $amount;
        };
        $paidPrice = $normalizeAmount((string)$response['paidPrice']);
        $price = $normalizeAmount((string)$response['price']);
        if ($paidPrice === '' || $price === '') return false;
        $message = implode(':', [
            (string)$response['paymentStatus'],
            (string)$response['paymentId'],
            (string)$response['currency'],
            (string)$response['basketId'],
            (string)$response['conversationId'],
            $paidPrice,
            $price,
            (string)$response['token'],
        ]);
        $expected = hash_hmac('sha256', $message, $secretKey);
        return hash_equals(strtolower($expected), strtolower((string)$response['signature']));
    }

    public static function verifyIyzicoV3(array $payload, string $signature, string $secretKey): bool
    {
        if ($signature === '' || $secretKey === '') return false;
        foreach (['iyziEventType', 'paymentConversationId', 'status'] as $field) {
            if (!isset($payload[$field]) || !is_scalar($payload[$field]) || (string)$payload[$field] === '') {
                return false;
            }
        }

        $eventType = (string)$payload['iyziEventType'];
        $conversationId = (string)$payload['paymentConversationId'];
        $status = (string)$payload['status'];
        if (in_array($eventType, ['CHECKOUT_FORM_AUTH', 'BANK_TRANSFER_AUTH', 'BKM_AUTH'], true)) {
            foreach (['iyziPaymentId', 'token'] as $field) {
                if (!isset($payload[$field]) || !is_scalar($payload[$field]) || (string)$payload[$field] === '') return false;
            }
            $message = $secretKey . $eventType . (string)$payload['iyziPaymentId']
                . (string)$payload['token'] . $conversationId . $status;
        } else {
            $paymentId = (string)($payload['paymentId'] ?? $payload['iyziPaymentId'] ?? '');
            if ($paymentId === '') return false;
            $message = $secretKey . $eventType . $paymentId . $conversationId . $status;
        }

        $expected = hash_hmac('sha256', $message, $secretKey);
        return hash_equals(strtolower($expected), strtolower($signature));
    }
}
