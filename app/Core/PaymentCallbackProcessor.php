<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/**
 * Applies a callback only after the provider-specific signature has been verified.
 * The caller must pass normalized, verified callback fields; this class never calls a gateway.
 */
final class PaymentCallbackProcessor
{
    public function __construct(private readonly PDO $pdo) {}

    public function processVerified(
        string $provider,
        string $eventId,
        string $eventType,
        string $transactionReference,
        array $payloadForHash,
        array $callback
    ): array {
        if (!in_array($provider, ['iyzico', 'paytr'], true)) {
            throw new \InvalidArgumentException('Desteklenmeyen ödeme sağlayıcısı.');
        }
        if ($eventId === '' || $eventType === '' || $transactionReference === '') {
            throw new \InvalidArgumentException('Ödeme bildirimi referansları eksik.');
        }
        if (($callback['provider'] ?? null) !== $provider
            || ($callback['provider_reference'] ?? null) !== $transactionReference) {
            throw new \InvalidArgumentException('Doğrulanmış bildirim referansları eşleşmiyor.');
        }

        $targetStatus = self::targetStatus((string)($callback['status'] ?? ''));
        if ($targetStatus === null) {
            throw new \InvalidArgumentException('Desteklenmeyen sağlayıcı ödeme durumu.');
        }

        $this->pdo->beginTransaction();
        try {
            $inbox = new PaymentWebhookInbox($this->pdo);
            $record = $inbox->recordVerified($provider, $eventId, $eventType, $transactionReference, $payloadForHash);
            if ($record === 'conflict') {
                $this->pdo->commit();
                return ['result' => 'conflict', 'payment_updated' => false];
            }

            if ($record === 'duplicate') {
                $event = $this->pdo->prepare('SELECT processing_status FROM payment_webhook_events WHERE provider=? AND provider_event_id=? FOR UPDATE');
                $event->execute([$provider, $eventId]);
                $state = (string)$event->fetchColumn();
                if ($state === 'processed') {
                    $this->pdo->commit();
                    return ['result' => 'duplicate', 'payment_updated' => false];
                }
                if ($state === 'processing') {
                    $this->pdo->commit();
                    return ['result' => 'in_progress', 'payment_updated' => false];
                }
            }

            if (!$inbox->claim($provider, $eventId)) {
                $this->pdo->commit();
                return ['result' => 'in_progress', 'payment_updated' => false];
            }

            // Resolve immutable ownership without locking, then follow the same
            // global lock order as booking creation and admin actions:
            // property -> booking -> payment transaction(s).
            $lookup = $this->pdo->prepare(
                'SELECT pt.id,pt.booking_id,b.property_id FROM payment_transactions pt JOIN bookings b ON b.id=pt.booking_id WHERE pt.provider=? AND pt.provider_reference=? LIMIT 1'
            );
            $lookup->execute([$provider, $transactionReference]);
            $paymentIdentity = $lookup->fetch(PDO::FETCH_ASSOC);
            if (!$paymentIdentity) {
                $inbox->markFailed($provider, $eventId, 'Ödeme kaydı, rezervasyon, tutar veya para birimi eşleşmedi.');
                $this->pdo->commit();
                return ['result' => 'rejected', 'payment_updated' => false];
            }

            $propertyLock = $this->pdo->prepare('SELECT id FROM properties WHERE id=? LIMIT 1 FOR UPDATE');
            $propertyLock->execute([(int)$paymentIdentity['property_id']]);
            if (!$propertyLock->fetchColumn()) {
                $inbox->markFailed($provider, $eventId, 'Ödemeye bağlı konaklama bulunamadı.');
                $this->pdo->commit();
                return ['result' => 'rejected_property', 'payment_updated' => false];
            }

            $bookingLock = $this->pdo->prepare('SELECT id,property_id,status,check_in,check_out FROM bookings WHERE id=? LIMIT 1 FOR UPDATE');
            $bookingLock->execute([(int)$paymentIdentity['booking_id']]);
            $booking = $bookingLock->fetch(PDO::FETCH_ASSOC);
            if (!$booking) {
                $inbox->markFailed($provider, $eventId, 'Ödemeye bağlı rezervasyon bulunamadı.');
                $this->pdo->commit();
                return ['result' => 'rejected_booking', 'payment_updated' => false];
            }

            $select = $this->pdo->prepare(
                'SELECT id,booking_id,provider,provider_reference,amount,currency,status FROM payment_transactions WHERE provider=? AND provider_reference=? LIMIT 1 FOR UPDATE'
            );
            $select->execute([$provider, $transactionReference]);
            $transaction = $select->fetch(PDO::FETCH_ASSOC);
            if (!$transaction
                || (int)$transaction['booking_id'] !== (int)$booking['id']
                || !PaymentTransactionGuard::matches($transaction, $callback)) {
                $inbox->markFailed($provider, $eventId, 'Ödeme kaydı, rezervasyon, tutar veya para birimi eşleşmedi.');
                $this->pdo->commit();
                return ['result' => 'rejected', 'payment_updated' => false];
            }
            if (!$booking) {
                $inbox->markFailed($provider, $eventId, 'Ödemeye bağlı rezervasyon bulunamadı.');
                $this->pdo->commit();
                return ['result' => 'rejected_booking', 'payment_updated' => false];
            }
            if ($targetStatus === 'paid' && !in_array((string)$booking['status'], ['pending','confirmed','cancelled'], true)) {
                $inbox->markFailed($provider, $eventId, 'Tamamlanmış rezervasyon için yeni ödeme kabul edilmedi.');
                $this->pdo->commit();
                return ['result' => 'rejected_booking_status', 'payment_updated' => false];
            }

            // The booking row is locked above, serializing all provider callbacks
            // for this booking. Never accept a second paid transaction from a
            // different provider (or a separately initialized checkout).
            if ($targetStatus === 'paid') {
                $otherPaid = $this->pdo->prepare(
                    "SELECT id FROM payment_transactions WHERE booking_id=? AND status='paid' AND id<>? LIMIT 1 FOR UPDATE"
                );
                $otherPaid->execute([(int)$booking['id'], (int)$transaction['id']]);
                if ($otherPaid->fetchColumn() !== false) {
                    $inbox->markFailed($provider, $eventId, 'Bu rezervasyon için başka bir ödeme zaten tahsil edilmiş.');
                    $this->pdo->commit();
                    return ['result' => 'rejected_duplicate_booking_payment', 'payment_updated' => false];
                }
            }

            $currentStatus = (string)$transaction['status'];
            if ($currentStatus === $targetStatus) {
                if (!$inbox->markProcessed($provider, $eventId)) {
                    throw new \RuntimeException('Tekrarlanan ödeme bildirimi tamamlandı olarak işaretlenemedi.');
                }
                $needsReview = $targetStatus === 'paid' && in_array((string)$booking['status'], ['pending','cancelled'], true);
                $this->pdo->commit();
                return [
                    'result' => $needsReview ? 'paid_booking_conflict' : 'already_applied',
                    'payment_updated' => false,
                    'payment_status' => $currentStatus,
                    'booking_confirmed' => (string)$booking['status'] === 'confirmed',
                    'manual_review_required' => $needsReview,
                ];
            }
            if (!PaymentTransactionGuard::canTransition($currentStatus, $targetStatus)) {
                $inbox->markFailed($provider, $eventId, 'İzin verilmeyen ödeme durum geçişi.');
                $this->pdo->commit();
                return ['result' => 'rejected_transition', 'payment_updated' => false];
            }

            $update = $this->pdo->prepare(
                "UPDATE payment_transactions SET status=?,paid_at=CASE WHEN ?='paid' THEN COALESCE(paid_at,NOW()) ELSE paid_at END,updated_at=NOW() WHERE id=? AND status=?"
            );
            $update->execute([$targetStatus, $targetStatus, (int)$transaction['id'], $currentStatus]);
            if ($update->rowCount() !== 1) {
                throw new \RuntimeException('Ödeme kaydı eşzamanlı olarak değişti; bildirim tekrar denenmeli.');
            }
            $bookingConflict = false;
            $bookingCancelled = $targetStatus === 'paid' && (string)$booking['status'] === 'cancelled';
            if ($targetStatus === 'paid' && (string)$booking['status'] === 'pending') {
                // Pending reservations also hold dates. A paid callback must not
                // confirm this booking if another active request now overlaps it.
                $overlap = $this->pdo->prepare(
                    "SELECT id FROM bookings WHERE property_id=? AND id<>? AND status IN ('pending','confirmed') AND check_in < ? AND check_out > ? LIMIT 1 FOR UPDATE"
                );
                $overlap->execute([(int)$booking['property_id'], (int)$booking['id'], (string)$booking['check_out'], (string)$booking['check_in']]);
                $bookingConflict = $overlap->fetchColumn() !== false;

                if (!$bookingConflict) {
                    $confirm = $this->pdo->prepare("UPDATE bookings SET status='confirmed' WHERE id=? AND status='pending'");
                    $confirm->execute([(int)$booking['id']]);
                    if ($confirm->rowCount() !== 1) {
                        throw new \RuntimeException('Ödeme sonrası rezervasyon onaylanamadı; bildirim tekrar denenmeli.');
                    }
                }
            }
            if (!$inbox->markProcessed($provider, $eventId)) {
                throw new \RuntimeException('Ödeme bildirimi tamamlandı olarak işaretlenemedi.');
            }

            $this->pdo->commit();
            if ($bookingConflict || $bookingCancelled) {
                return [
                    'result' => 'paid_booking_conflict',
                    'payment_updated' => true,
                    'payment_status' => $targetStatus,
                    'booking_confirmed' => false,
                    'manual_review_required' => true,
                ];
            }
            return ['result' => 'processed', 'payment_updated' => true, 'payment_status' => $targetStatus];
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $error;
        }
    }

    public static function targetStatus(string $providerStatus): ?string
    {
        return [
            'success' => 'paid',
            'paid' => 'paid',
            'failure' => 'failed',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
            'canceled' => 'cancelled',
            'expired' => 'expired',
            'processing' => 'processing',
            'refunded' => 'refunded',
        ][strtolower(trim($providerStatus))] ?? null;
    }
}
