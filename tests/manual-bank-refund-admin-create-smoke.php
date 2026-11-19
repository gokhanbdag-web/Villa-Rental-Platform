<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$routes=(string)file_get_contents($root.'/routes/web.php');
$view=(string)file_get_contents($root.'/themes/default/templates/pages/admin/manual-payment-reports.php');
foreach(["'/admin/payments/manual/refunds/create'","Auth::requireRole(['super_admin','admin']);verify_csrf();","ManualBankRefundService::request","FILTER_VALIDATE_INT"] as $needle){if(!str_contains($routes,$needle))throw new RuntimeException('Missing admin refund safeguard: '.$needle);}
foreach(['csrf_field()','name="payment_id"','name="amount"','name="reason"','Yeni banka iadesi talebi'] as $needle){if(!str_contains($view,$needle))throw new RuntimeException('Missing refund form element: '.$needle);}
echo "Manual bank refund admin request checks passed.\n";
