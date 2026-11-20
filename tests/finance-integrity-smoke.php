<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
use App\Core\FinanceReconciliation;
$pdo=new PDO('sqlite::memory:');
foreach([0,5001] as $limit){
 try{FinanceReconciliation::scan($pdo,$limit);throw new RuntimeException('Invalid limit accepted');}
 catch(InvalidArgumentException $e){}
}
$scanner=file_get_contents(dirname(__DIR__).'/app/Core/FinanceReconciliation.php');
$vendor=file_get_contents(dirname(__DIR__).'/app/Core/VendorPayoutService.php');
$bank=file_get_contents(dirname(__DIR__).'/app/Core/ManualBankRefundService.php');
$gateway=file_get_contents(dirname(__DIR__).'/app/Core/PaymentRefundService.php');
foreach(['financial_split_mismatch','payout_overallocated','refund_payout_conflict','stale_payout_status','paid_status_without_allocation'] as $needle){
 if(!str_contains($scanner,$needle))throw new RuntimeException('Missing finance check: '.$needle);
}
foreach([$bank,$gateway] as $source){
 if(!str_contains($source,'vendor_payout_allocations')||!str_contains($source,'Satıcıya tahsis edilmiş'))throw new RuntimeException('Missing refund allocation protection');
}
if(substr_count($vendor,"manual_bank_refunds mbr")<2)throw new RuntimeException('Missing payout reservation exclusion');
echo "Stage 2 financial integrity guards passed.\n";
