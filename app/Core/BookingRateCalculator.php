<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

final class BookingRateCalculator
{
    /**
     * Pricing precedence: base weekday rate, weekend rate, matching season,
     * then an explicit availability-calendar nightly price.
     * Date ranges use an exclusive checkout/end date throughout the app.
     */
    public static function nightlyRate(array $property,string $day,array $seasons=[],array $calendarRules=[]): float
    {
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$day);
        if(!$date||$date->format('Y-m-d')!==$day) throw new InvalidArgumentException('Invalid pricing date.');
        $rate=(float)($property['base_nightly_price']??0);
        if((int)$date->format('N')>=6&&(float)($property['weekend_nightly_price']??0)>0) $rate=(float)$property['weekend_nightly_price'];
        foreach($seasons as $season){
            if($day >= (string)$season['date_from'] && $day < (string)$season['date_to']){
                $rate=(float)$season['nightly_price'];
                break;
            }
        }
        foreach($calendarRules as $rule){
            if($day >= (string)$rule['date_from'] && $day < (string)$rule['date_to']){
                if((string)$rule['status']!=='available') throw new RuntimeException('Selected date is unavailable.');
                if($rule['nightly_price']!==null) $rate=(float)$rule['nightly_price'];
            }
        }
        if(!is_finite($rate)||$rate<0) throw new RuntimeException('Invalid nightly rate.');
        return round($rate,2);
    }

    public static function staySubtotal(array $property,DateTimeImmutable $checkIn,DateTimeImmutable $checkOut,array $seasons=[],array $calendarRules=[]): float
    {
        if($checkOut<=$checkIn) throw new InvalidArgumentException('Checkout must be after check-in.');
        $subtotal=0.0;
        for($night=$checkIn;$night<$checkOut;$night=$night->modify('+1 day')){
            $subtotal+=self::nightlyRate($property,$night->format('Y-m-d'),$seasons,$calendarRules);
        }
        return round($subtotal,2);
    }
}
