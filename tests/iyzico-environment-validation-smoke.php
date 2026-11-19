<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
$invalidRejected=false;
try {
    \App\Core\IyzicoApiClient::post(['credentials'=>['api_key'=>'offline-test','secret_key'=>'offline-test'],'environment'=>'unexpected'], '/payment/bin/check', ['binNumber'=>'552608']);
} catch (InvalidArgumentException $e) {
    $invalidRejected=str_contains($e->getMessage(),'ortamı geçersiz');
}
if(!$invalidRejected)throw new RuntimeException('Invalid provider environment was not rejected before network I/O.');
echo "Invalid iyzico environment rejected without network request.\n";
