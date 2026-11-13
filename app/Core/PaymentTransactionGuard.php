<?php
declare(strict_types=1);

namespace App\Core;

final class PaymentTransactionGuard
{
    private const TRANSITIONS = [
        'pending' => ['processing', 'paid', 'failed', 'cancelled', 'expired'],
        'processing' => ['paid', 'failed', 'cancelled', 'expired'],
        'paid' => ['refunded'],
        'failed' => [],
        'cancelled' => [],
        'expired' => [],
        'refunded' => [],
    ];

    /** Validate normalized callback fields against the immutable local transaction. */
    public static function matches(array $transaction, array $callback): bool
    {
        foreach (['id', 'booking_id', 'provider', 'provider_reference', 'amount', 'currency'] as $field) {
            if (!array_key_exists($field, $transaction) || $transaction[$field] === null || $transaction[$field] === '') return false;
        }
        foreach (['booking_id', 'provider', 'provider_reference', 'amount', 'currency'] as $field) {
            if (!array_key_exists($field, $callback) || $callback[$field] === null || $callback[$field] === '') return false;
        }
        if (!in_array((string)$transaction['provider'], ['iyzico', 'paytr'], true)) return false;
        if (!hash_equals((string)$transaction['provider'], (string)$callback['provider'])) return false;
        if (!hash_equals((string)$transaction['provider_reference'], (string)$callback['provider_reference'])) return false;
        if ((int)$transaction['booking_id'] < 1 || (int)$callback['booking_id'] !== (int)$transaction['booking_id']) return false;
        if (strtoupper((string)$transaction['currency']) !== strtoupper((string)$callback['currency'])) return false;
        $expectedCents = self::toMinorUnits($transaction['amount']);
        $callbackCents = self::toMinorUnits($callback['amount']);
        return $expectedCents !== null && $callbackCents !== null && $expectedCents === $callbackCents;
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    private static function toMinorUnits(mixed $amount): ?int
    {
        $value = trim((string)$amount);
        if (!preg_match('/^(?:0|[1-9][0-9]{0,9})(?:\.([0-9]{1,2}))?$/', $value, $matches)) return null;
        [$whole] = explode('.', $value, 2);
        return ((int)$whole * 100) + (int)str_pad($matches[1] ?? '', 2, '0');
    }
}
