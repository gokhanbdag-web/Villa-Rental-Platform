<?php
declare(strict_types=1);
$service=(string)file_get_contents(dirname(__DIR__).'/app/Core/ManualBankRefundService.php');
foreach(["bank_reference=? AND status='succeeded' AND id<>?",'\$referenceCheck->fetchColumn()',"Banka işlem referansı","FOR UPDATE"] as $guard){
 if(!str_contains($service,$guard))throw new RuntimeException('Missing duplicate bank reference guard: '.$guard);
}
echo "Bank refund reference reuse checks passed.\n";
