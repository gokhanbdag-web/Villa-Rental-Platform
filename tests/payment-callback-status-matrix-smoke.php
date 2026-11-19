<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
use App\Core\PaymentCallbackProcessor;
use App\Core\PaymentTransactionGuard;

$cases=[
    ['success','paid'],['SUCCESS','paid'],['paid','paid'],
    ['failure','failed'],['FAILED','failed'],['processing','processing'],
    ['expired','expired'],['cancelled','cancelled'],['refunded','refunded'],
    ['unexpected',null],
];
foreach($cases as [$input,$expected]){
    if(PaymentCallbackProcessor::targetStatus($input)!==$expected)throw new RuntimeException('Incorrect provider callback status: '.$input);
}
$allowed=[
    ['pending','processing'],['processing','paid'],['processing','failed'],
    ['expired','paid'],['expired','failed'],['expired','processing'],
];
foreach($allowed as [$from,$to]){
    if(!PaymentTransactionGuard::canTransition($from,$to))throw new RuntimeException("Delayed callback transition rejected: $from -> $to");
}
$forbidden=[
    ['paid','failed'],['paid','processing'],['failed','paid'],
    ['refunded','paid'],['cancelled','paid'],['paid','paid'],
];
foreach($forbidden as [$from,$to]){
    if(PaymentTransactionGuard::canTransition($from,$to))throw new RuntimeException("Unsafe repeated/late callback transition allowed: $from -> $to");
}
echo "Successful, failed, delayed and repeated callback state matrix passed (offline).\n";
