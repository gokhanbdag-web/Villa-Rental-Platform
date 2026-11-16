<?php
declare(strict_types=1);

require_once dirname(__DIR__).'/app/bootstrap.php';

use App\Core\BookingExtras;

function bookingExtrasAssert(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$property=[
    'early_checkin_enabled'=>1,'early_checkin_fee'=>125.50,
    'late_checkout_enabled'=>1,'late_checkout_fee'=>80.25,
];
$none=BookingExtras::calculate($property,false,false);
bookingExtrasAssert($none['fees']===0.0&&$none['early_checkin_requested']===0&&$none['late_checkout_requested']===0,'Unselected options must not add fees.');
$both=BookingExtras::calculate($property,true,true);
bookingExtrasAssert($both['fees']===205.75&&$both['early_checkin_fee']===125.5&&$both['late_checkout_fee']===80.25,'Selected options must calculate exact configured fees.');
$early=BookingExtras::calculate($property,true,false);
bookingExtrasAssert($early['fees']===125.5&&$early['late_checkout_fee']===0.0,'Only selected early check-in should be charged.');
$disabled=$property;$disabled['late_checkout_enabled']=0;
$rejected=false;try{BookingExtras::calculate($disabled,false,true);}catch(InvalidArgumentException){$rejected=true;}
bookingExtrasAssert($rejected,'A forged request for a disabled option must be rejected.');
$invalid=$property;$invalid['early_checkin_fee']=-1;
$rejected=false;try{BookingExtras::calculate($invalid,true,false);}catch(InvalidArgumentException){$rejected=true;}
bookingExtrasAssert($rejected,'Negative configured fees must be rejected.');
$zero=$property;$zero['early_checkin_fee']=0;$zero['late_checkout_fee']=0;
$free=BookingExtras::calculate($zero,true,true);
bookingExtrasAssert($free['fees']===0.0&&$free['early_checkin_requested']===1&&$free['late_checkout_requested']===1,'Free options must remain recorded as requested without adding charges.');
echo "Booking early check-in and late checkout fee tests passed.\n";
