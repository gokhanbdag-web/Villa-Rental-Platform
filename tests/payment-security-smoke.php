<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\PaymentSecretVault;
use App\Core\PaymentSignatureVerifier;

function paymentAssert(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}

$secret = 'local-test-secret-2026';
$sealed = PaymentSecretVault::encrypt($secret);
paymentAssert($sealed !== $secret && str_starts_with($sealed, 'enc:v1:'), 'Secret was not encrypted.');
paymentAssert(PaymentSecretVault::decrypt($sealed) === $secret, 'Encrypted secret did not round-trip.');

$paytrPayload = [
    'merchant_oid' => 'VILLA-TEST-0001',
    'status' => 'success',
    'total_amount' => '125000',
];
$paytrMessage = $paytrPayload['merchant_oid'] . 'test-salt' . $paytrPayload['status'] . $paytrPayload['total_amount'];
$paytrPayload['hash'] = base64_encode(hash_hmac('sha256', $paytrMessage, 'test-merchant-key', true));
paymentAssert(PaymentSignatureVerifier::verifyPaytr($paytrPayload, 'test-merchant-key', 'test-salt'), 'Valid PayTR callback signature was rejected.');
$paytrPayload['total_amount'] = '1';
paymentAssert(!PaymentSignatureVerifier::verifyPaytr($paytrPayload, 'test-merchant-key', 'test-salt'), 'Tampered PayTR callback was accepted.');

$iyzicoPayload = [
    'iyziEventType' => 'CHECKOUT_FORM_AUTH',
    'iyziPaymentId' => '123456789',
    'token' => 'test-token',
    'paymentConversationId' => 'VILLA-TEST-0002',
    'status' => 'SUCCESS',
];
$iyzicoMessage = $secret . $iyzicoPayload['iyziEventType'] . $iyzicoPayload['iyziPaymentId']
    . $iyzicoPayload['token'] . $iyzicoPayload['paymentConversationId'] . $iyzicoPayload['status'];
$signature = hash_hmac('sha256', $iyzicoMessage, $secret);
paymentAssert(PaymentSignatureVerifier::verifyIyzicoV3($iyzicoPayload, $signature, $secret), 'Valid iyzico V3 signature was rejected.');
$iyzicoPayload['status'] = 'FAILURE';
paymentAssert(!PaymentSignatureVerifier::verifyIyzicoV3($iyzicoPayload, $signature, $secret), 'Tampered iyzico callback was accepted.');

echo "Payment security smoke tests passed. No gateway API requests were made.\n";
