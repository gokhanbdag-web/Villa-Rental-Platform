<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';

function paytrCheckoutAssert(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
paytrCheckoutAssert(method_exists(\App\Core\PaytrApiClient::class,'getIframeToken'),'PayTR token API client is missing.');
paytrCheckoutAssert(method_exists(\App\Core\PaymentCheckoutGuard::class,'reservePaytr'),'Atomic PayTR checkout reservation is missing.');
$client=(string)file_get_contents(dirname(__DIR__).'/app/Core/PaytrApiClient.php');
$guard=(string)file_get_contents(dirname(__DIR__).'/app/Core/PaymentCheckoutGuard.php');
$routes=(string)file_get_contents(dirname(__DIR__).'/routes/web.php');
$checkout=(string)file_get_contents(dirname(__DIR__).'/themes/default/templates/pages/checkout.php');
paytrCheckoutAssert(str_contains($client,'https://www.paytr.com/odeme/api/get-token'),'PayTR HTTPS token endpoint is missing.');
paytrCheckoutAssert(str_contains($client,'CURLOPT_FOLLOWLOCATION=>false')&&str_contains($client,'CURLOPT_SSL_VERIFYPEER=>true'),'PayTR client does not enforce safe HTTPS transport.');
paytrCheckoutAssert(str_contains($routes,"/checkout/{bookingCode}/paytr/start")&&str_contains($routes,"/payments/paytr/callback"),'PayTR checkout or callback route is missing.');
paytrCheckoutAssert(str_contains($routes,'PaymentSignatureVerifier::verifyPaytr')&&str_contains($routes,'PaymentCallbackProcessor'),'PayTR callback signature or idempotent processor integration is missing.');
paytrCheckoutAssert(str_contains($routes,'total_amount')&&str_contains($routes,'expectedCents'),'PayTR callback amount comparison is missing.');
paytrCheckoutAssert(str_contains($routes,"payment_type")&&str_contains($routes,"no_installment'=>'1'"),'PayTR payment type validation or exact-total single-payment setting is missing.');
paytrCheckoutAssert(str_contains($guard,"provider='iyzico'")&&str_contains($guard,"provider='paytr'"),'Cross-provider parallel checkout lock is missing.');
paytrCheckoutAssert(str_contains($checkout,'$paytrIframeUrl')&&str_contains($checkout,'iframeResizer.min.js'),'Hosted PayTR iframe is not rendered.');
foreach(['success','failure','tampered','amount_mismatch','wrong_order'] as $scenario){
    $result=\App\Core\PaymentSandboxSimulator::run('paytr',$scenario);
    paytrCheckoutAssert($result['network_requests']===0&&!$result['real_charge']&&!$result['booking_changed'],'PayTR local simulator performed a real action.');
    if($scenario==='success')paytrCheckoutAssert($result['callback_accepted']&&$result['payment_success'],'Valid PayTR callback simulation failed.');
    else paytrCheckoutAssert(!$result['payment_success'],'Invalid or failed PayTR scenario was treated as a successful payment.');
}
echo "PayTR hosted checkout, callback guards and offline scenarios passed. No real charge or provider request was made.\n";
