<?php
declare(strict_types=1);

require_once dirname(__DIR__).'/app/bootstrap.php';

use App\Core\BookingRateCalculator;

function seasonalAssert(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$property=['base_nightly_price'=>100,'weekend_nightly_price'=>150];
$season=[['date_from'=>'2026-12-01','date_to'=>'2026-12-10','nightly_price'=>180]];
$calendar=[['date_from'=>'2026-12-05','date_to'=>'2026-12-06','status'=>'available','nightly_price'=>210]];
seasonalAssert(BookingRateCalculator::nightlyRate($property,'2026-12-02',$season)===180.0,'Season price did not override the base rate.');
seasonalAssert(BookingRateCalculator::nightlyRate($property,'2026-12-05',$season)===180.0,'Season price did not override the weekend rate.');
seasonalAssert(BookingRateCalculator::nightlyRate($property,'2026-12-05',$season,$calendar)===210.0,'Explicit calendar price must override a seasonal price.');
seasonalAssert(BookingRateCalculator::nightlyRate($property,'2026-12-12',$season)===150.0,'Weekend price was not retained outside a season.');
seasonalAssert(BookingRateCalculator::nightlyRate($property,'2026-12-14',$season)===100.0,'Weekday base rate was not retained outside a season.');
$subtotal=BookingRateCalculator::staySubtotal($property,new DateTimeImmutable('2026-12-04'),new DateTimeImmutable('2026-12-07'),$season);
seasonalAssert($subtotal===540.0,'Multi-night subtotal did not use the correct seasonal and weekend rates.');
$closed=false;
try{BookingRateCalculator::nightlyRate($property,'2026-12-05',$season,[['date_from'=>'2026-12-05','date_to'=>'2026-12-06','status'=>'blocked','nightly_price'=>null]]);}catch(RuntimeException){$closed=true;}
seasonalAssert($closed,'A blocked calendar date must never receive a price.');
$invalid=false;
try{BookingRateCalculator::nightlyRate($property,'2026-02-30');}catch(InvalidArgumentException){$invalid=true;}
seasonalAssert($invalid,'Invalid calendar dates must be rejected.');
echo "Seasonal pricing precedence and stay subtotal smoke tests passed.\n";
