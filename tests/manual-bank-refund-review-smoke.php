<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
use App\Core\ManualBankRefundService;
$pdo=new PDO('sqlite::memory:');
foreach(['succeeded','unknown','invalid'] as $status){
 try{ManualBankRefundService::review($pdo,1,1,$status);throw new RuntimeException('Unsafe status accepted: '.$status);}
 catch(InvalidArgumentException $e){}
}
$source=(string)file_get_contents(dirname(__DIR__).'/app/Core/ManualBankRefundService.php');
foreach(['reviewed_by_user_id','FOR UPDATE','status=?','rowCount() !== 1'] as $guard){
 if(!str_contains($source,$guard))throw new RuntimeException('Missing review guard: '.$guard);
}
echo "Bank refund review guards passed; settlement cannot be self-certified.\n";
