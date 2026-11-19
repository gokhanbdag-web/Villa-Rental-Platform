<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$routes=(string)file_get_contents($root.'/routes/web.php');
$view=(string)file_get_contents($root.'/themes/default/templates/pages/admin/manual-payment-reports.php');
foreach(["'/admin/payments/manual/refunds/settle'","ManualBankRefundService::settleVerified","bank_transfer_verified","verify_csrf()","Auth::requireRole(['super_admin','admin'])"] as $guard){if(!str_contains($routes,$guard))throw new RuntimeException('Missing settlement route guard: '.$guard);}
foreach(['name="bank_transfer_verified"','type="checkbox"','name="bank_reference"','name="refund_id"','csrf_field()',"status']==='processing'"] as $guard){if(!str_contains($view,$guard))throw new RuntimeException('Missing settlement UI guard: '.$guard);}
echo "Verified bank refund settlement admin controls passed.\n";
