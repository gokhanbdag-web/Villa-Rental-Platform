<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use InvalidArgumentException;

final class BookingStayRules
{
    public const ALL_WEEKDAYS = 127;

    /**
     * Weekday bit positions: Monday=1, Tuesday=2, ... Sunday=64.
     * A season applies its minimum stay to every overlapping night. Arrival
     * rules are evaluated against the check-in date; departure rules against
     * the final occupied night (checkout is exclusive).
     */
    public static function validate(DateTimeImmutable $checkIn,DateTimeImmutable $checkOut,array $seasons): void
    {
        if($checkOut<=$checkIn) throw new InvalidArgumentException('Çıkış tarihi giriş tarihinden sonra olmalıdır.');
        $nights=(int)$checkIn->diff($checkOut)->days;
        $requiredNights=1;
        $arrivalMask=self::ALL_WEEKDAYS;
        $departureMask=self::ALL_WEEKDAYS;
        $arrivalDate=$checkIn->format('Y-m-d');
        $lastNight=$checkOut->modify('-1 day')->format('Y-m-d');

        foreach($seasons as $season){
            $from=(string)($season['date_from']??'');
            $to=(string)($season['date_to']??'');
            if($from===''||$to===''||$to<=$from) continue;
            if($arrivalDate >= $from && $arrivalDate < $to) {
                $arrivalMask=(int)($season['checkin_weekdays']??self::ALL_WEEKDAYS);
            }
            if($lastNight >= $from && $lastNight < $to) {
                $departureMask=(int)($season['checkout_weekdays']??self::ALL_WEEKDAYS);
            }
            if($from < $checkOut->format('Y-m-d') && $to > $arrivalDate) {
                $requiredNights=max($requiredNights,(int)($season['min_nights']??1));
            }
        }

        if($nights<$requiredNights) throw new InvalidArgumentException('Bu dönem için minimum konaklama '.$requiredNights.' gecedir.');
        $arrivalBit=1 << ((int)$checkIn->format('N')-1);
        if(($arrivalMask & $arrivalBit)===0) throw new InvalidArgumentException('Bu sezon seçtiğiniz giriş gününde check-in kabul edilmiyor.');
        $departureBit=1 << ((int)$checkOut->format('N')-1);
        if(($departureMask & $departureBit)===0) throw new InvalidArgumentException('Bu sezon seçtiğiniz çıkış gününde check-out kabul edilmiyor.');
    }

    public static function weekdayMask(array $days): int
    {
        $mask=0;
        foreach($days as $day){
            $value=(int)$day;
            if($value<1||$value>7) throw new InvalidArgumentException('Hafta günü seçimi geçersiz.');
            $mask|=1 << ($value-1);
        }
        if($mask===0) throw new InvalidArgumentException('En az bir giriş ve çıkış günü seçilmelidir.');
        return $mask;
    }
}
