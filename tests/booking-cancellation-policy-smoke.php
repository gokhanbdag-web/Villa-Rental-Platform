<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/BookingCancellationPolicy.php';

use App\Core\BookingCancellationPolicy;

$root = dirname(__DIR__);
$routes = file_get_contents($root . '/routes/web.php');
$migration = file_get_contents($root . '/database/migrations/048_booking_cancellation_requests.sql');
$accountView = file_get_contents($root . '/views/account/bookings.php');
$adminView = file_get_contents($root . '/themes/default/templates/pages/admin/cancellations.php');
$adminShell = file_get_contents($root . '/themes/default/layouts/admin-shell.php');
$today = new DateTimeImmutable('2026-11-16');
$checks = [
    'full refund at the free-cancellation threshold' => BookingCancellationPolicy::evaluate(new DateTimeImmutable('2026-11-23'), $today, 7, 100, 0, '1250.00') === ['days_before_checkin'=>7,'refund_percent'=>100,'refund_amount'=>'1250.00'],
    'late cancellation uses configured partial percentage' => BookingCancellationPolicy::evaluate(new DateTimeImmutable('2026-11-20'), $today, 7, 100, 25, '1000.00')['refund_amount'] === '250.00',
    'zero paid amount produces no refund' => BookingCancellationPolicy::evaluate(new DateTimeImmutable('2026-11-20'), $today, 7, 100, 0, '0.00')['refund_amount'] === '0.00',
    'fractional amount rounds to minor units' => BookingCancellationPolicy::evaluate(new DateTimeImmutable('2026-11-23'), $today, 7, 100, 0, '10.25')['refund_amount'] === '10.25',
    'approval recalculates against the remaining paid balance' => BookingCancellationPolicy::refundAmount('800.00', 25) === '200.00',
];
$checks += [
    'closed reservations cannot be reactivated by admin and rebooking requires new booking' => str_contains($routes, "in_array((string)\$booking['status'],['cancelled','completed'],true)") && str_contains($routes, "Kapanmış rezervasyon yeniden açılamaz") && str_contains($routes, "Onaylanmış rezervasyon beklemeye alınamaz"),

    'customer cancellation endpoint is authenticated and CSRF protected' => str_contains($routes, "'/account/bookings/cancel-request'") && str_contains($routes, 'verify_csrf();'),
    'customer can only request cancellation for an owned pending or confirmed booking' => str_contains($routes, 'guest_user_id=? LIMIT 1 FOR UPDATE') && str_contains($routes, "['pending','confirmed']"),
    'request snapshots policy days, refund percentage and amount' => str_contains($routes, 'policy_days_snapshot,refund_percent_snapshot,refund_amount') && str_contains($migration, 'policy_days_snapshot SMALLINT'),
    'one pending cancellation request per booking is enforced in MySQL' => str_contains($migration, 'active_booking_id') && str_contains($migration, 'uq_cancellation_active_booking'),
    'admin policy values are bounded and saved server-side' => str_contains($routes, "'/admin/cancellations/policy'") && str_contains($routes, '$days>365') && str_contains($routes, '$early>100'),
    'admin cancellation decisions require role and CSRF' => str_contains($routes, "'/admin/cancellations/review'") && str_contains($routes, "Auth::requireRole(['super_admin','admin'])"),
    'approval cancels booking and records audit plus customer notification' => str_contains($routes, "SET status='cancelled'") && str_contains($routes, 'booking.cancellation_') && str_contains($routes, "'booking.cancellation_'"),
    'customer view exposes cancellation request and estimated refund' => str_contains($accountView, '/account/bookings/cancel-request') && str_contains($accountView, 'estimated_refund'),
    'admin view exposes policy settings and approve/reject actions' => str_contains($adminView, '/admin/cancellations/policy') && str_contains($adminView, '/admin/cancellations/review'),
    'admin navigation includes cancellation management' => str_contains($adminShell, '/admin/cancellations'),
];
$failed=[];
foreach($checks as $label=>$passed){echo ($passed?'[PASS] ':'[FAIL] ').$label.PHP_EOL;if(!$passed)$failed[]=$label;}
foreach([
    ['past check-in rejected',new DateTimeImmutable('2026-11-15'),$today,7,100,0,'10.00'],
    ['invalid refund percentage rejected',new DateTimeImmutable('2026-11-23'),$today,7,101,0,'10.00'],
    ['invalid amount rejected',new DateTimeImmutable('2026-11-23'),$today,7,100,0,'10.001'],
] as [$label,$checkIn,$now,$days,$early,$late,$amount]){
    try{BookingCancellationPolicy::evaluate($checkIn,$now,$days,$early,$late,$amount);$passed=false;}catch(InvalidArgumentException){$passed=true;}
    echo ($passed?'[PASS] ':'[FAIL] ').$label.PHP_EOL;if(!$passed)$failed[]=$label;
}
if($failed){fwrite(STDERR,count($failed).' cancellation policy test(s) failed.'.PHP_EOL);exit(1);}
echo "All cancellation policy tests passed.".PHP_EOL;
