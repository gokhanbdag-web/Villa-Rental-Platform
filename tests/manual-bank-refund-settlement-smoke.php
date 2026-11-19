<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
use App\Core\ManualBankRefundService;
$pdo=new PDO('sqlite::memory:');
foreach(['','  '] as $reference){
 try{ManualBankRefundService::settleVerified($pdo,1,1,$reference);throw new RuntimeException('Empty bank reference accepted');}
 catch(InvalidArgumentException $e){}
}
$source=(string)file_get_contents(dirname(__DIR__).'/app/Core/ManualBankRefundService.php');
foreach(["status='processing'","status='succeeded'","booking_financials","platform_fee","vendor_net_amount","completed_at=NOW()","beginTransaction()","rollBack()"] as $needle){
 if(!str_contains($source,$needle))throw new RuntimeException('Missing settlement guard: '.$needle);
}
echo "Verified bank refund settlement guards passed.\n";
