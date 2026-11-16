<?php
declare(strict_types=1);

namespace App\\Core;

use InvalidArgumentException;

final class BookingExtras
{
    /**
     * Calculate only options explicitly requested by the guest and enabled by
     * the property. The returned fees are always derived from trusted DB data.
     */
    public static function calculate(array $property,bool $earlyCheckin,bool $lateCheckout): array
    {
        $earlyEnabled=(int)($property['early_checkin_enabled']??0)===1;
        $lateEnabled=(int)($property['late_checkout_enabled']??0)===1;
        if($earlyCheckin&&!$earlyEnabled) throw new InvalidArgumentException('Erken giriş bu konaklama için sunulmuyor.');
        if($lateCheckout&&!$lateEnabled) throw new InvalidArgumentException('Geç çıkış bu konaklama için sunulmuyor.');
        $earlyFee=$earlyCheckin?(float)($property['early_checkin_fee']??0):0.0;
        $lateFee=$lateCheckout?(float)($property['late_checkout_fee']??0):0.0;
        foreach([$earlyFee,$lateFee] as $fee) if(!is_finite($fee)||$fee<0||$fee>99999999.99) throw new InvalidArgumentException('Ek hizmet ücreti geçersiz.');
        return [
            'early_checkin_requested'=>$earlyCheckin?1:0,
            'early_checkin_fee'=>round($earlyFee,2),
            'late_checkout_requested'=>$lateCheckout?1:0,
            'late_checkout_fee'=>round($lateFee,2),
            'fees'=>round($earlyFee+$lateFee,2),
        ];
    }
}
