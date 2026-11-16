<?php
declare(strict_types=1);

require_once dirname(__DIR__).'/app/bootstrap.php';

function paytrCheckoutAssert(bool $ok,string $message):void { if(!$ok)throw new RuntimeException($message); }
paytrCheckoutAssert(method_exists(\\App\\Core\\PaytrIframeClient::class,'requestToken'),'PayTR native iFrame client is missing.');
paytrCheckoutAssert(method_exists(\\App\\Core\\PaymentSignatureVerifier::class,'verifyPaytr'),'PayTR callback signature verifier is missing.');
$routes=(string)file_get_contents(dirname(__DIR__).'/routes/web.php');
foreach([
    "'/checkout/{bookingCode}/paytr/start'",
    "'/payments/paytr/frame/{merchantOid}'",
    "'/payments/paytr/callback'",
    "'/payments/paytr/return/{merchantOid}'",
    'PaymentCallbackProcessor($pdo))->processVerified',
    'verifyPaytr($payload',
] as $needle) paytrCheckoutAssert(str_contains($routes,$needle),'Missing PayTR integration route or verification: '.$needle);
$view=(string)file_get_contents(dirname(__DIR__).'/themes/default/templates/pages/paytr-iframe.php');
paytrCheckoutAssert(str_contains($view,'https://www.paytr.com/odeme/guvenli/'),'Hosted PayTR iFrame URL missing.');
paytrCheckoutAssert(str_contains($view,'id="paytriframe"'),'PayTR iframe element missing.');
$checkout=(string)file_get_contents(dirname(__DIR__).'/themes/default/templates/pages/checkout.php');
paytrCheckoutAssert(str_contains($checkout,"'/paytr/start'))"),'Customer checkout does not expose PayTR.');
echo "PayTR checkout smoke tests passed. No gateway API requests were made.\\n";
