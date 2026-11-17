<?php
declare(strict_types=1);

namespace App\Core;

final class BookingStatusPolicy
{
    /**
     * Vendors may confirm/cancel a pending request or cancel/complete a
     * confirmed booking. Closed bookings cannot be reactivated by a vendor.
     */
    public static function canVendorTransition(string $current, string $target): bool
    {
        if ($current === $target) {
            return true;
        }

        return match ($current) {
            'pending' => in_array($target, ['confirmed', 'cancelled'], true),
            'confirmed' => in_array($target, ['cancelled', 'completed'], true),
            default => false,
        };
    }
}
