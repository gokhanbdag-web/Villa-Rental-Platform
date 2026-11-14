<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';


use App\Core\PaymentTransactionGuard;
use App\Core\PaymentCallbackProcessor;

$transaction = ['id'=>51,'booking_id'=>9001,'provider'=>'paytr','provider_reference'=>'VILLA-ORDER-9001','amount'=>'1250.00','currency'=>'TRY','status'=>'pending'];
$callback = ['booking_id'=>9001,'provider'=>'paytr','provider_reference'=>'VILLA-ORDER-9001','amount'=>'1250','currency'=>'try'];
paymentAssert(PaymentTransactionGuard::matches($transaction,$callback), 'Matching payment callback was rejected.');
foreach ([
    ['amount'=>'1249.99'], ['currency'=>'EUR'], ['booking_id'=>9002],
    ['provider'=>'iyzico'], ['provider_reference'=>'OTHER-ORDER'], ['amount'=>'1e3'],
] as $change) {
    paymentAssert(!PaymentTransactionGuard::matches($transaction,array_merge($callback,$change)), 'Mismatched payment callback was accepted.');
}

paymentAssert(PaymentCallbackProcessor::targetStatus('success') === 'paid', 'Success callback status mapping failed.');
paymentAssert(PaymentCallbackProcessor::targetStatus('failure') === 'failed', 'Failure callback status mapping failed.');
paymentAssert(PaymentCallbackProcessor::targetStatus('cancelled') === 'cancelled', 'Cancelled callback status mapping failed.');
paymentAssert(PaymentCallbackProcessor::targetStatus('refunded') === 'refunded', 'Refund callback status mapping failed.');
paymentAssert(PaymentCallbackProcessor::targetStatus('unknown') === null, 'Unknown callback status was accepted.');

paymentAssert(PaymentTransactionGuard::canTransition('pending','processing'), 'Pending to processing transition was rejected.');
paymentAssert(PaymentTransactionGuard::canTransition('processing','paid'), 'Processing to paid transition was rejected.');
paymentAssert(PaymentTransactionGuard::canTransition('paid','refunded'), 'Paid to refunded transition was rejected.');
paymentAssert(!PaymentTransactionGuard::canTransition('paid','failed'), 'Paid payment was allowed to regress to failed.');
paymentAssert(!PaymentTransactionGuard::canTransition('failed','paid'), 'Failed payment was allowed to become paid without a new transaction.');
paymentAssert(!PaymentTransactionGuard::canTransition('refunded','paid'), 'Refunded payment was allowed to become paid.');


use App\Core\PaymentSecretVault;
use App\Core\PaymentSignatureVerifier;
use App\Core\PaymentSandboxSimulator;

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

foreach (['iyzico','paytr'] as $provider) {
    $success=PaymentSandboxSimulator::run($provider,'success');
    paymentAssert($success['signature_valid']&&$success['payment_success']&&$success['network_requests']===0&&!$success['real_charge'],$provider.' success simulation failed.');
    $failure=PaymentSandboxSimulator::run($provider,'failure');
    paymentAssert($failure['signature_valid']&&!$failure['payment_success'],$provider.' failure simulation failed.');
    $tampered=PaymentSandboxSimulator::run($provider,'tampered');
    paymentAssert(!$tampered['signature_valid']&&!$tampered['payment_success'],$provider.' tampered simulation was accepted.');
}
$requestSignature=PaymentSandboxSimulator::run('paytr','success');
paymentAssert($requestSignature['request_signature_valid']===true && $requestSignature['callback_accepted']===true, 'PayTR iFrame request token signature or callback signature failed.');
foreach (['amount_mismatch','wrong_order'] as $badScenario) {
    $rejected=PaymentSandboxSimulator::run('paytr',$badScenario);
    paymentAssert($rejected['signature_valid']===true && $rejected['callback_accepted']===false && $rejected['payment_success']===false, 'PayTR '.$badScenario.' callback was not rejected after matching checks.');
    paymentAssert($rejected['network_requests']===0 && $rejected['real_charge']===false && $rejected['booking_changed']===false, 'PayTR local simulator performed an external or persistent action.');
}
echo "Payment security smoke tests passed. No gateway API requests were made.\n";
