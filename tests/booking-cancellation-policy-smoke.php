<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/BookingCancellationPolicy.php';

use App\Core\BookingCancellationPolicy;

$today = new DateTimeImmutable('2026-11-16');
$checks = [
    'full refund at the free-cancellation threshold' => BookingCancellationPolicy::evaluate(new DateTimeImmutable('2026-11-23'), $today, 7, 100, 0, '1250.00') === ['days_before_checkin'=>7,'refund_percent'=>100,'refund_amount'=>'1250.00'],
    'late cancellation uses configured partial percentage' => BookingCancellationPolicy::evaluate(new DateTimeImmutable('2026-11-20'), $today, 7, 100, 25, '1000.00')['refund_amount'] === '250.00',
    'zero paid amount produces no refund' => BookingCancellationPolicy::evaluate(new DateTimeImmutable('2026-11-20'), $today, 7, 100, 0, '0.00')['refund_amount'] === '0.00',
    'fractional amount rounds to minor units' => BookingCancellationPolicy::evaluate(new DateTimeImmutable('2026-11-23'), $today, 7, 100, 0, '10.25')['refund_amount'] === '10.25',
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
