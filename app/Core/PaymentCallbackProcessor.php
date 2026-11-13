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

            $select = $this->pdo->prepare(
                'SELECT id,booking_id,provider,provider_reference,amount,currency,status FROM payment_transactions WHERE provider=? AND provider_reference=? LIMIT 1 FOR UPDATE'
            );
            $select->execute([$provider, $transactionReference]);
            $transaction = $select->fetch(PDO::FETCH_ASSOC);

            if (!$transaction || !PaymentTransactionGuard::matches($transaction, $callback)) {
                $inbox->markFailed($provider, $eventId, 'Ödeme kaydı, rezervasyon, tutar veya para birimi eşleşmedi.');
                $this->pdo->commit();
                return ['result' => 'rejected', 'payment_updated' => false];
            }

            $currentStatus = (string)$transaction['status'];
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
            if (!$inbox->markProcessed($provider, $eventId)) {
                throw new \RuntimeException('Ödeme bildirimi tamamlandı olarak işaretlenemedi.');
            }

            $this->pdo->commit();
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
