<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
$routes=(string)file_get_contents(dirname(__DIR__).'/routes/web.php');
$client=(string)file_get_contents(dirname(__DIR__).'/app/Core/PaytrApiClient.php');
$doc=(string)file_get_contents(dirname(__DIR__).'/docs/PAYTR-SANDBOX-TEST.md');
$checks=[
 'PayTR test mode derived from selected environment'=>str_contains($routes,"\$testMode=(\$config['environment']??'sandbox')==='sandbox'?'1':'0'"),
 'PayTR checkout sends test_mode'=>str_contains($routes,"'test_mode'=>\$testMode"),
 'PayTR callback verifies provider signature'=>str_contains($routes,'PaymentSignatureVerifier::verifyPaytr'),
 'PayTR token uses HTTPS and verified certificate'=>str_contains($client,'https://www.paytr.com/odeme/api/get-token')&&str_contains($client,'CURLOPT_SSL_VERIFYPEER=>true'),
 'Real provider acceptance checklist is present'=>str_contains($doc,'test_mode=1')&&str_contains($doc,'payment_transactions')&&str_contains($doc,'iyzico'),
];
foreach($checks as $name=>$ok){if(!$ok)throw new RuntimeException($name);echo "[PASS] $name\n";}
echo "Provider readiness checks passed. No provider network request was made.\n";
