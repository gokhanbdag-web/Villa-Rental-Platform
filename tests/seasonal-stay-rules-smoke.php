<?php
declare(strict_types=1);

require_once dirname(__DIR__).'/app/bootstrap.php';

use App\Core\BookingStayRules;
use DateTimeImmutable;
use InvalidArgumentException;

function stayRuleAssert(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
$season=[[
    'date_from'=>'2027-06-01',
    'date_to'=>'2027-06-20',
    'min_nights'=>5,
    'checkin_weekdays'=>48,  // Friday and Saturday
    'checkout_weekdays'=>65 // Monday and Sunday
]];
$valid=false;
try{BookingStayRules::validate(new DateTimeImmutable('2027-06-05'),new DateTimeImmutable('2027-06-14'),$season);$valid=true;}catch(InvalidArgumentException){}
stayRuleAssert($valid,'Saturday arrival and Monday departure should be allowed for a long enough stay.');
$short=false;
try{BookingStayRules::validate(new DateTimeImmutable('2027-06-05'),new DateTimeImmutable('2027-06-08'),$season);}catch(InvalidArgumentException $e){$short=str_contains($e->getMessage(),'minimum');}
stayRuleAssert($short,'Season minimum nights must be enforced.');
$wrongArrival=false;
try{BookingStayRules::validate(new DateTimeImmutable('2027-06-08'),new DateTimeImmutable('2027-06-14'),$season);}catch(InvalidArgumentException $e){$wrongArrival=str_contains($e->getMessage(),'giriş');}
stayRuleAssert($wrongArrival,'Disallowed check-in weekdays must be rejected.');
$wrongDeparture=false;
try{BookingStayRules::validate(new DateTimeImmutable('2027-06-05'),new DateTimeImmutable('2027-06-10'),$season);}catch(InvalidArgumentException $e){$wrongDeparture=str_contains($e->getMessage(),'çıkış');}
stayRuleAssert($wrongDeparture,'Disallowed check-out weekdays must be rejected.');
$outside=false;
try{BookingStayRules::validate(new DateTimeImmutable('2027-06-22'),new DateTimeImmutable('2027-06-23'),$season);$outside=true;}catch(InvalidArgumentException){}
stayRuleAssert($outside,'Season weekday restrictions must not affect dates outside the season.');
$overlapMinimum=false;
try{BookingStayRules::validate(new DateTimeImmutable('2027-05-30'),new DateTimeImmutable('2027-06-03'),$season);}catch(InvalidArgumentException $e){$overlapMinimum=str_contains($e->getMessage(),'minimum');}
stayRuleAssert($overlapMinimum,'Minimum nights must apply when the stay overlaps a season, even if arrival is earlier.');
$mask=BookingStayRules::weekdayMask(['1','3','7']);
stayRuleAssert($mask===69,'Weekday bitmask must use Monday=1 through Sunday=64.');
$empty=false;
try{BookingStayRules::weekdayMask([]);}catch(InvalidArgumentException){$empty=true;}
stayRuleAssert($empty,'At least one weekday must be selected.');
echo "Seasonal minimum-stay and check-in/check-out weekday tests passed.\n";
