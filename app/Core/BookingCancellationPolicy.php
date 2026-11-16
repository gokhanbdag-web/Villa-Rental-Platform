<?php
declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use InvalidArgumentException;

final class BookingCancellationPolicy
{
    public static function evaluate(
        DateTimeImmutable $checkIn,
        DateTimeImmutable $today,
        int $freeCancellationDays,
        int $earlyRefundPercent,
        int $lateRefundPercent,
        string $paidAmount
    ): array {
        if ($freeCancellationDays < 0 || $freeCancellationDays > 365
            || $earlyRefundPercent < 0 || $earlyRefundPercent > 100
            || $lateRefundPercent < 0 || $lateRefundPercent > 100
            || !preg_match('/^(?:0|[1-9][0-9]{0,9})(?:\.([0-9]{1,2}))?$/', trim($paidAmount))) {
            throw new InvalidArgumentException('İptal politikası veya ödenen tutar geçersiz.');
        }

        $days = (int)$today->setTime(0, 0)->diff($checkIn->setTime(0, 0))->format('%r%a');
        if ($days < 0) {
            throw new InvalidArgumentException('Giriş tarihi geçmiş rezervasyon için çevrimiçi iptal talebi oluşturulamaz.');
        }

        $percent = $days >= $freeCancellationDays ? $earlyRefundPercent : $lateRefundPercent;
        $cents = self::toMinorUnits($paidAmount);
        $refundCents = intdiv(($cents * $percent) + 50, 100);

        return [
            'days_before_checkin' => $days,
            'refund_percent' => $percent,
            'refund_amount' => number_format($refundCents / 100, 2, '.', ''),
        ];
    }

    private static function toMinorUnits(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        return ((int)$whole * 100) + (int)str_pad($fraction, 2, '0');
    }
}
