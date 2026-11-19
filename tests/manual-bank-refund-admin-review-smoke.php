<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$routes=(string)file_get_contents($root.'/routes/web.php');
$view=(string)file_get_contents($root.'/themes/default/templates/pages/admin/manual-payment-reports.php');
foreach(["'/admin/payments/manual/refunds/review'","ManualBankRefundService::review","['processing','failed','cancelled']","verify_csrf()"] as $guard){if(!str_contains($routes,$guard))throw new RuntimeException('Missing review route guard: '.$guard);}
foreach(['name="refund_id"','name="bank_reference"','value="processing"','value="failed"','value="cancelled"','csrf_field()'] as $guard){if(!str_contains($view,$guard))throw new RuntimeException('Missing review form: '.$guard);}
echo "Bank refund admin review controls passed.\n";
