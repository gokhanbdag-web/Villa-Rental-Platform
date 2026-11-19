<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
$rejected=false;
try {
    \App\Core\PaytrApiClient::getIframeToken(['environment'=>'invalid','credentials'=>['merchant_id'=>'offline','merchant_key'=>'offline','merchant_salt'=>'offline']],[]);
} catch (InvalidArgumentException $e) {
    $rejected=str_contains($e->getMessage(),'ortamı geçersiz');
}
if(!$rejected)throw new RuntimeException('Invalid PayTR environment must be rejected before token request.');
echo "Invalid PayTR environment rejected without network request.\n";
