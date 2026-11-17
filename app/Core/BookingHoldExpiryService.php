<?php
declare(strict_types=1);

namespace App\\Core;

use PDO;
use RuntimeException;

/**
 * Releases only expired booking holds whose payment state is known to be terminal.
 * The caller must hold the property's row lock inside an active transaction.
 */
final class BookingHoldExpiryService
{
    public static function expireSafePendingForProperty(PDO $pdo, int $propertyId, int $limit = 50): int
    {
        if ($propertyId < 1 || !$pdo->inTransaction()) {
            throw new RuntimeException('Rezervasyon süresi kontrolü için kilitli işlem gereklidir.');
        }
        $limit = max(1, min(200, $limit));
        $bookings = $pdo->prepare(
            "SELECT id FROM bookings
             WHERE property_id=? AND status='pending'
               AND hold_expires_at IS NOT NULL AND hold_expires_at<=NOW()
             ORDER BY hold_expires_at,id LIMIT {$limit} FOR UPDATE"
        );
        $bookings->execute([$propertyId]);
        $ids = $bookings->fetchAll(PDO::FETCH_COLUMN);
        $expired = 0;

        $manual = $pdo->prepare("SELECT id FROM manual_payment_reports WHERE booking_id=? AND status='pending' LIMIT 1 FOR UPDATE");
        $payments = $pdo->prepare('SELECT id,provider,provider_reference,status,expires_at FROM payment_transactions WHERE booking_id=? ORDER BY id FOR UPDATE');
        $expirePlaceholder = $pdo->prepare(
            "UPDATE payment_transactions SET status='expired',updated_at=NOW()
             WHERE id=? AND provider='iyzico' AND status='pending' AND provider_reference LIKE 'init:%'
               AND expires_at IS NOT NULL AND expires_at<=NOW()"
        );
        $cancel = $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND status='pending' AND hold_expires_at<=NOW()");

        foreach ($ids as $bookingId) {
            $bookingId = (int)$bookingId;
            $manual->execute([$bookingId]);
            if ($manual->fetchColumn() !== false) {
                continue;
            }

            $payments->execute([$bookingId]);
            $rows = $payments->fetchAll(PDO::FETCH_ASSOC);
            $safeToRelease = true;
            foreach ($rows as $payment) {
                $status = (string)$payment['status'];
                if ($status === 'pending'
                    && (string)$payment['provider'] === 'iyzico'
                    && str_starts_with((string)$payment['provider_reference'], 'init:')
                    && !empty($payment['expires_at'])
                    && strtotime((string)$payment['expires_at']) <= time()) {
                    $expirePlaceholder->execute([(int)$payment['id']]);
                    $status = 'expired';
                }
                if (!in_array($status, ['failed','cancelled','expired','refunded'], true)) {
                    // Paid, processing, unresolved provider tokens and unknown states
                    // must never cause a booking to be released automatically.
                    $safeToRelease = false;
                    break;
                }
            }
            if (!$safeToRelease) {
                continue;
            }
            $cancel->execute([$bookingId]);
            if ($cancel->rowCount() === 1) {
                $expired++;
            }
        }
        return $expired;
    }
}
