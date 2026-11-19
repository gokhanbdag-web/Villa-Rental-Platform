<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
use App\Core\ManualBankRefundService;
$pdo=new PDO('sqlite::memory:');
foreach([['0.00','zero'],['-1','negative'],['1e3','scientific'],['1.001','precision']] as [$amount,$label]){
 try { ManualBankRefundService::request($pdo,1,1,$amount,'test');throw new RuntimeException('Accepted '.$label); }
 catch(InvalidArgumentException $e) {}
}
$source=(string)file_get_contents(dirname(__DIR__).'/app/Core/ManualBankRefundService.php');
foreach(["status IN ('pending','processing')","status IN ('succeeded','pending','processing')","provider'] !== 'manual'","FOR UPDATE","beginTransaction()"] as $guard){
 if(!str_contains($source,$guard))throw new RuntimeException('Missing guard: '.$guard);
}
echo "Manual bank refund input and reservation guards passed.\n";
