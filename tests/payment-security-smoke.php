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
paymentAssert(\\App\\Core\\IyzicoPaymentReconciler::normalizeStatus(['paymentStatus'=>'SUCCESS','fraudStatus'=>1]) === 'paid', 'Verified iyzico success was not normalized as paid.');
paymentAssert(\\App\\Core\\IyzicoPaymentReconciler::normalizeStatus(['paymentStatus'=>'SUCCESS','fraudStatus'=>0]) === 'processing', 'iyzico fraud review was not normalized as processing.');
paymentAssert(\\App\\Core\\IyzicoPaymentReconciler::normalizeStatus(['paymentStatus'=>'FAILURE']) === 'failed', 'iyzico failure was not normalized as failed.');
paymentAssert(\\App\\Core\\IyzicoPaymentReconciler::normalizeStatus(['paymentStatus'=>'WAITING']) === null, 'Unknown iyzico status was treated as terminal.');
paymentAssert(PaymentCallbackProcessor::targetStatus('processing') === 'processing', 'iyzico fraud-review status mapping failed.');
paymentAssert(method_exists(\App\Core\IyzicoApiClient::class, 'initializeCheckoutForm'), 'iyzico Checkout Form initialize method is missing.');
paymentAssert(method_exists(\App\Core\IyzicoApiClient::class, 'retrieveCheckoutForm'), 'iyzico Checkout Form retrieve method is missing.');


paymentAssert(PaymentTransactionGuard::canTransition('pending','processing'), 'Pending to processing transition was rejected.');
paymentAssert(PaymentTransactionGuard::canTransition('processing','paid'), 'Processing to paid transition was rejected.');
paymentAssert(PaymentTransactionGuard::canTransition('expired','paid'), 'A verified provider success could not reconcile a legacy locally expired payment.');
paymentAssert(PaymentTransactionGuard::canTransition('expired','processing'), 'A verified provider processing state could not reconcile a legacy locally expired payment.');
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

$paytrTokenVector = [
    'merchant_id'=>'123456','user_ip'=>'203.0.113.1','merchant_oid'=>'TESTORDER001',
    'email'=>'test@example.com','payment_amount'=>'125000',
    'user_basket'=>'W1siVmlsbGEiLCIxMjUwLjAwIiwxXV0=',
    'no_installment'=>'0','max_installment'=>'0','currency'=>'TL','test_mode'=>'1',
    'paytr_token'=>'mZXU+gQEGZ8k+Xh+oVoQHDPWMD5Lb5mFBAxWc7tvStE=',
];
paymentAssert(PaymentSignatureVerifier::verifyPaytrIframeToken($paytrTokenVector,'test-merchant-key','test-salt'), 'Valid PayTR iFrame token signature was rejected.');
$paytrTokenVector['payment_amount']='1';
paymentAssert(!PaymentSignatureVerifier::verifyPaytrIframeToken($paytrTokenVector,'test-merchant-key','test-salt'), 'Tampered PayTR iFrame token request was accepted.');

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

$initializeResponse = ['conversationId'=>'VILLA-TEST-0002','token'=>'init-token'];
$initializeResponse['signature'] = hash_hmac('sha256','VILLA-TEST-0002:init-token',$secret);
paymentAssert(PaymentSignatureVerifier::verifyIyzicoCheckoutFormInitialize($initializeResponse,$secret), 'Valid iyzico Checkout Form initialize signature was rejected.');
$initializeResponse['token'] = 'tampered-token';
paymentAssert(!PaymentSignatureVerifier::verifyIyzicoCheckoutFormInitialize($initializeResponse,$secret), 'Tampered iyzico Checkout Form initialize response was accepted.');

$retrieveResponse = [
    'paymentStatus'=>'SUCCESS','paymentId'=>'99887766','currency'=>'TRY','basketId'=>'VILLA-TEST-0002',
    'conversationId'=>'VILLA-TEST-0002','paidPrice'=>'1250.00','price'=>'1250.00','token'=>'test-token',
];
$retrieveMessage = implode(':', [
    $retrieveResponse['paymentStatus'],$retrieveResponse['paymentId'],$retrieveResponse['currency'],
    $retrieveResponse['basketId'],$retrieveResponse['conversationId'],'1250','1250',$retrieveResponse['token'],
]);
$retrieveResponse['signature'] = hash_hmac('sha256',$retrieveMessage,$secret);
paymentAssert(PaymentSignatureVerifier::verifyIyzicoCheckoutFormRetrieve($retrieveResponse,$secret), 'Valid iyzico Checkout Form retrieve signature was rejected.');
$retrieveResponse['paidPrice'] = '1250.01';
paymentAssert(!PaymentSignatureVerifier::verifyIyzicoCheckoutFormRetrieve($retrieveResponse,$secret), 'Tampered iyzico retrieve amount was accepted.');

paymentAssert(method_exists(\App\Core\IyzicoApiClient::class,'refundV2'), 'iyzico Refund V2 method is missing.');
$refundResponse=['status'=>'success','paymentId'=>'99887766','price'=>'25.00','currency'=>'TRY','conversationId'=>'VILLA-REFUND-0001'];
$refundResponse['signature']=hash_hmac('sha256','99887766:25:TRY:VILLA-REFUND-0001',$secret);
paymentAssert(PaymentSignatureVerifier::verifyIyzicoRefund($refundResponse,$secret), 'Valid iyzico refund signature was rejected.');
$refundResponse['price']='25.01';
paymentAssert(!PaymentSignatureVerifier::verifyIyzicoRefund($refundResponse,$secret), 'Tampered iyzico refund amount was accepted.');


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
$callbackSource = file_get_contents(dirname(__DIR__) . '/app/Core/PaymentCallbackProcessor.php');
$routesSource = file_get_contents(dirname(__DIR__) . '/routes/web.php');
$adminBookingsSource = file_get_contents(dirname(__DIR__) . '/views/admin/bookings.php');
paymentAssert(is_string($callbackSource) && strpos($callbackSource, "SELECT id FROM properties WHERE id=? LIMIT 1 FOR UPDATE") !== false, 'Payment callbacks do not lock the property before the booking.');
paymentAssert(strpos($callbackSource, "status IN ('pending','confirmed') AND check_in < ? AND check_out > ?") !== false, 'Paid callback overlap guard is missing.');
paymentAssert(strpos($callbackSource, "'paid_booking_conflict'") !== false && strpos($callbackSource, "'manual_review_required' => true") !== false, 'Paid conflicting bookings are not routed to manual review.');
paymentAssert(is_string($routesSource) && strpos($routesSource, "status IN ('pending','confirmed') AND check_in < ? AND check_out > ?") !== false, 'Admin confirmation does not block overlapping pending bookings.');
paymentAssert(is_string($adminBookingsSource) && strpos($adminBookingsSource, 'has_paid_payment') !== false && strpos($adminBookingsSource, 'Ödeme alındı · inceleme gerekli') !== false, 'Admin booking list does not flag paid bookings requiring review.');
$checkoutGuardSource = file_get_contents(dirname(__DIR__) . '/app/Core/PaymentCheckoutGuard.php');
paymentAssert(is_string($checkoutGuardSource) && strpos($checkoutGuardSource, "'verification_required'") !== false && strpos($checkoutGuardSource, 'Never expire it locally or create a') !== false, 'Expired real checkout tokens are not held for provider reconciliation.');
paymentAssert(strpos($routesSource, "if(\$reservationResult==='verification_required')") !== false, 'Checkout route does not stop a second payment while the old provider token is unresolved.');
$paymentResultRoute = strpos($routesSource, "if(\$paymentResultType==='review'");
$paymentResultView = file_get_contents(dirname(__DIR__) . '/themes/default/templates/pages/payment-result.php');
paymentAssert($paymentResultRoute !== false && strpos($routesSource, "'paid_booking_conflict'?'review'") !== false, 'Paid booking conflicts are incorrectly sent to the success result screen.');
paymentAssert(is_string($paymentResultView) && strpos($paymentResultView, 'Ödemeniz alındı, rezervasyon inceleniyor') !== false, 'Paid but unconfirmed booking review is not explained to the guest.');

$holdExpirySource = file_get_contents(dirname(__DIR__) . '/app/Core/BookingHoldExpiryService.php');
paymentAssert(is_string($holdExpirySource) && strpos($holdExpirySource, 'manual_payment_reports') !== false && strpos($holdExpirySource, "in_array(\$status, ['failed','cancelled','expired','refunded'], true)") !== false, 'Booking hold expiry does not protect unresolved or paid payments and pending manual reports.');
paymentAssert(strpos($routesSource, 'hold_expires_at') !== false && strpos($routesSource, 'BookingHoldExpiryService::expireSafePendingForProperty') !== false, 'Booking creation does not assign and process pending hold deadlines.');
paymentAssert(strpos($routesSource, "status IN ('pending','confirmed') AND check_in < ? AND check_out > ?") !== false, 'Manual payment approval does not block overlapping pending bookings.');
$reconcilerSource = file_get_contents(dirname(__DIR__) . '/app/Core/IyzicoPaymentReconciler.php');
paymentAssert(is_string($reconcilerSource) && strpos($reconcilerSource, 'verifyIyzicoCheckoutFormRetrieve') !== false && strpos($reconcilerSource, 'PaymentCallbackProcessor') !== false, 'Expired iyzico sessions are not verified and applied through the shared callback processor.');
paymentAssert(strpos($routesSource, 'IyzicoPaymentReconciler::reconcile') !== false, 'Checkout retry does not reconcile an expired provider token before creating another payment.');
echo "Payment security smoke tests passed. No gateway API requests were made.\n";
