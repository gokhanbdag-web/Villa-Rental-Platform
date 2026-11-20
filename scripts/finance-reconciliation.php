<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
use App\Core\Database;
use App\Core\FinanceReconciliation;
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$limit=isset($argv[1])?(int)$argv[1]:500;
try{
 $result=FinanceReconciliation::scan(Database::connection(),$limit);
 echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
 exit($result['issue_count']>0?2:0);
}catch(Throwable $e){fwrite(STDERR,"Finance reconciliation failed: ".$e->getMessage()."\n");exit(1);}
