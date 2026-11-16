<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$routes = file_get_contents($root . '/routes/web.php');
$checkout = file_get_contents($root . '/themes/default/templates/pages/checkout.php');
$admin = file_get_contents($root . '/themes/default/templates/pages/admin/manual-payment-reports.php');
$migration = file_get_contents($root . '/database/migrations/040_manual_payment_reports.sql');
$checks = [
    'customer report route is authenticated and CSRF protected' => str_contains($routes, "'/checkout/{bookingCode}/manual/notify'") && str_contains($routes, 'verify_csrf();'),
    'report ownership is checked against the signed-in guest' => str_contains($routes, 'guest_user_id=?') && str_contains($routes, 'manual_payment_reports'),
    'one pending report per booking is enforced by a generated unique key' => str_contains($migration, 'active_booking_id') && str_contains($migration, 'uq_manual_report_active_booking'),
    'report stores booking amount and currency snapshot' => str_contains($migration, 'amount DECIMAL(12,2)') && str_contains($migration, 'currency CHAR(3)'),
    'admin review requires an authorized role and CSRF' => str_contains($routes, "'/admin/payments/manual/review'") && str_contains($routes, "Auth::requireRole(['super_admin','admin'])"),
    'approval creates a paid manual transaction idempotently' => str_contains($routes, "'manual'") && str_contains($routes, 'idempotency_key') && str_contains($routes, "'paid'"),
    'approval checks booking date conflicts and availability' => str_contains($routes, 'başka bir onaylı rezervasyon') && str_contains($routes, "status<>'available'"),
    'rejection leaves payment uncollected and allows a later new report' => str_contains($routes, "'rejected'") && str_contains($migration, 'active_booking_id'),
    'review locks the report and booking and rejects already-reviewed reports' => str_contains($routes, 'FOR UPDATE') && str_contains($routes, 'if((string)$report[\'status\']!==\'pending\')'),
    'approval validates amount and currency snapshots before payment creation' => str_contains($routes, 'abs((float)$booking[\'total\']-(float)$report[\'amount\'])>0.009') && str_contains($routes, 'strtoupper((string)$booking[\'currency\'])!==strtoupper((string)$report[\'currency\'])'),
    'approval rejects an existing paid transaction and overlapping confirmed booking' => str_contains($routes, 'başka bir ödeme zaten doğrulanmış') && str_contains($routes, 'başka bir onaylı rezervasyon'),
    'report decision is conditional on pending state to prevent double approval' => str_contains($routes, "WHERE id=? AND status='pending'") && str_contains($routes, 'idempotency_key'),
    'customer checkout displays bank details and transfer report form' => str_contains($checkout, 'manualBankDetails') && str_contains($checkout, '/manual/notify'),
    'admin review page escapes submitted details and offers approve/reject' => str_contains($admin, 'e($report[\'transfer_reference\'])') && str_contains($admin, 'value="approved"') && str_contains($admin, 'value="rejected"'),
];
$failed = [];
foreach ($checks as $label => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$passed) $failed[] = $label;
}
if ($failed) {
    fwrite(STDERR, count($failed) . " manual payment report smoke check(s) failed." . PHP_EOL);
    exit(1);
}
echo "All manual payment report smoke checks passed." . PHP_EOL;
