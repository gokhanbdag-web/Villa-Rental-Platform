<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$routes=(string)file_get_contents($root.'/routes/web.php');
$view=(string)file_get_contents($root.'/themes/default/templates/pages/admin/manual-payment-reports.php');
foreach(['FROM manual_bank_refunds r','reviewed_by_user_id','ORDER BY r.created_at DESC','LIMIT 100'] as $needle){if(!str_contains($routes,$needle))throw new RuntimeException('Missing audit query guard: '.$needle);}
foreach(['Banka iadesi kayıtları','bankRefunds','bank_reference','completed_at','e($refund['] as $needle){if(!str_contains($view,$needle))throw new RuntimeException('Missing escaped bank refund field: '.$needle);}
echo "Manual bank refund admin audit list guards passed.\n";
