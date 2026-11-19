<?php
declare(strict_types=1);
$sql=(string)file_get_contents(dirname(__DIR__).'/database/migrations/052_manual_bank_refund_audit.sql');
foreach(['manual_bank_refunds','booking_id','payment_transaction_id','bank_reference','reviewed_by_user_id','completed_at','amount > 0',"'pending','processing','succeeded','failed','cancelled'","ON DELETE RESTRICT"] as $required){
 if(!str_contains($sql,$required))throw new RuntimeException('Missing bank refund audit safeguard: '.$required);
}
echo "Manual bank refund audit schema checks passed.\n";
