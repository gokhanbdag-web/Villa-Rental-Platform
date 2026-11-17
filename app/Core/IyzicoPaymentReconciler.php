<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

/**
 * Reconciles an expired iyzico checkout token against the provider before a
 * second checkout is allowed or the associated booking hold can be released.
 */
final class IyzicoPaymentReconciler
{
    public static function normalizeStatus(array $result): ?string
    {
        $status = strtoupper(trim((string)($result['paymentStatus'] ?? '')));
        if ($status === 'SUCCESS') {
            $fraud = (int)($result['fraudStatus'] ?? -1);
            return $fraud === 1 ? 'paid' : ($fraud === 0 ? 'processing' : null);
        }
        return match ($status) {
            'FAILURE' => 'failed',
            'CANCELLED', 'CANCELED' => 'cancelled',
            'EXPIRED' => 'expired',
            default => null,
        };
    }

    public static function claimReconciliationAttempt(PDO $pdo, int $transactionId): bool
    {
        if ($transactionId < 1) {
            return false;
        }
        $claim = $pdo->prepare(
            "UPDATE payment_transactions
             SET reconciliation_checked_at=NOW()
             WHERE id=? AND status IN ('pending','expired')
               AND (reconciliation_checked_at IS NULL OR reconciliation_checked_at<=DATE_SUB(NOW(),INTERVAL 1 MINUTE))"
        );
        $claim->execute([$transactionId]);
        return $claim->rowCount() === 1;
    }

    /**
     * Reconcile a small batch of due real iyzico tokens before releasing holds.
     * This is called opportunistically from public availability/booking requests,
     * so shared hosting does not require SSH or a separate worker.
     */
    public static function reconcileExpiredForProperty(PDO $pdo, int $propertyId, int $limit = 3): int
    {
        if ($propertyId < 1) {
            return 0;
        }
        $limit = max(1, min(10, $limit));
        $stmt = $pdo->prepare(
            "SELECT pt.id,pt.booking_id,pt.provider,pt.provider_reference,pt.amount,pt.currency,
                    pt.request_payload,pt.response_payload,pt.status,pt.expires_at
             FROM payment_transactions pt
             JOIN bookings b ON b.id=pt.booking_id
             WHERE b.property_id=? AND b.status='pending'
               AND b.hold_expires_at IS NOT NULL AND b.hold_expires_at<=NOW()
               AND pt.provider='iyzico' AND pt.status IN ('pending','expired')
               AND pt.provider_reference NOT LIKE 'init:%'
               AND pt.expires_at IS NOT NULL AND pt.expires_at<=NOW()
               AND (pt.reconciliation_checked_at IS NULL OR pt.reconciliation_checked_at<=DATE_SUB(NOW(),INTERVAL 1 MINUTE))
             ORDER BY pt.expires_at,pt.id LIMIT {$limit}"
        );
        $stmt->execute([$propertyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $attempted = 0;
        foreach ($rows as $row) {
            try {
                $result = self::reconcile($pdo, $row);
                if (($result['result'] ?? '') !== 'throttled') {
                    $attempted++;
                }
            } catch (\Throwable $error) {
                error_log('iyzico expired session reconciliation deferred.');
                $attempted++;
            }
        }
        return $attempted;
    }

    public static function reconcile(PDO $pdo, array $transaction): array
    {
        if ((string)($transaction['provider'] ?? 'iyzico') !== 'iyzico'
            || !in_array((string)($transaction['status'] ?? ''), ['pending','expired'], true)) {
            return ['result' => 'not_reconcilable'];
        }

        $token = trim((string)($transaction['provider_reference'] ?? ''));
        if ($token === '' || str_starts_with($token, 'init:')) {
            return ['result' => 'not_reconcilable'];
        }
        $transactionId = (int)($transaction['id'] ?? 0);
        if (!self::claimReconciliationAttempt($pdo, $transactionId)) {
            $state = $pdo->prepare('SELECT status FROM payment_transactions WHERE id=? LIMIT 1');
            $state->execute([$transactionId]);
            $currentStatus = (string)($state->fetchColumn() ?: '');
            if (in_array($currentStatus, ['paid','processing','failed','cancelled','refunded'], true)) {
                return ['result'=>'already_applied','payment_status'=>$currentStatus];
            }
            return ['result'=>'throttled','payment_status'=>$currentStatus];
        }
        $request = json_decode((string)($transaction['request_payload'] ?? ''), true);
        if (!is_array($request)) {
            throw new RuntimeException('Önceki ödeme oturumunun yerel doğrulama bilgileri bulunamadı.');
        }
        $conversationId = trim((string)($request['conversationId'] ?? ''));
        $basketId = trim((string)($request['basketId'] ?? ''));
        $environment = trim((string)($request['environment'] ?? ''));
        if ($conversationId === '' || $basketId === '' || !in_array($environment, ['sandbox','production'], true)) {
            throw new RuntimeException('Önceki ödeme oturumunun yerel referansları geçersiz.');
        }

        $config = PaymentGatewayConfig::forProvider('iyzico');
        if (empty($config['configured'])) {
            throw new RuntimeException('iyzico bağlantı bilgileri yapılandırılmamış.');
        }
        $config['environment'] = $environment;
        $result = IyzicoApiClient::retrieveCheckoutForm($config, $token, $conversationId);
        $secret = (string)($config['credentials']['secret_key'] ?? '');
        if (strtolower((string)($result['status'] ?? '')) !== 'success'
            || !PaymentSignatureVerifier::verifyIyzicoCheckoutFormRetrieve($result, $secret)
            || !hash_equals($conversationId, (string)($result['conversationId'] ?? ''))
            || !hash_equals($basketId, (string)($result['basketId'] ?? ''))) {
            throw new RuntimeException('iyzico ödeme sonucu imza veya rezervasyon referansı doğrulamasından geçmedi.');
        }

        $targetStatus = self::normalizeStatus($result);
        if ($targetStatus === null) {
            return ['result' => 'unresolved', 'payment_status' => 'pending'];
        }

        $amount = (string)($transaction['amount'] ?? '');
        $currency = strtoupper((string)($transaction['currency'] ?? ''));
        if (in_array($targetStatus, ['paid','processing'], true)) {
            $paidPrice = $result['paidPrice'] ?? null;
            $providerPrice = $result['price'] ?? null;
            if (!is_numeric($paidPrice) || !is_finite((float)$paidPrice)
                || !is_numeric($providerPrice) || !is_finite((float)$providerPrice)
                || number_format((float)$providerPrice, 2, '.', '') !== number_format((float)($transaction['amount'] ?? 0), 2, '.', '')) {
                throw new RuntimeException('iyzico ödeme tutarı doğrulanamadı; yeni ödeme başlatılmadı.');
            }
            $amount = number_format((float)$paidPrice, 2, '.', '');
            $currency = strtoupper((string)($result['currency'] ?? ''));
        }
        if ($amount === '' || $currency === '') {
            throw new RuntimeException('Ödeme tutarı veya para birimi yerel kayıtta eksik.');
        }

        $paymentId = trim((string)($result['paymentId'] ?? ''));
        $fraudStatus = (string)($result['fraudStatus'] ?? '');
        $eventId = 'reconcile-' . substr(hash('sha256', $token.'|'.$paymentId.'|'.$targetStatus.'|'.$fraudStatus), 0, 48);
        $callback = [
            'provider' => 'iyzico',
            'provider_reference' => $token,
            'booking_id' => (int)($transaction['booking_id'] ?? 0),
            'amount' => $amount,
            'currency' => $currency,
            'status' => $targetStatus,
        ];
        $payload = [
            'token' => $token,
            'paymentId' => $paymentId,
            'paymentStatus' => (string)($result['paymentStatus'] ?? ''),
            'fraudStatus' => $fraudStatus,
            'paidPrice' => $amount,
            'currency' => $currency,
            'source' => 'expired_checkout_reconciliation',
        ];
        $processed = (new PaymentCallbackProcessor($pdo))->processVerified(
            'iyzico',
            $eventId,
            'CHECKOUT_FORM_RECONCILIATION',
            $token,
            $payload,
            $callback
        );
        $processed['payment_status'] = $targetStatus;

        if (in_array($targetStatus, ['failed','cancelled','expired'], true)
            && in_array((string)($processed['result'] ?? ''), ['processed','already_applied','duplicate'], true)) {
            self::releaseDueHold($pdo, (int)($transaction['booking_id'] ?? 0));
        }

        return $processed;
    }

    private static function releaseDueHold(PDO $pdo, int $bookingId): void
    {
        if ($bookingId < 1) {
            return;
        }
        $lookup = $pdo->prepare('SELECT property_id FROM bookings WHERE id=? LIMIT 1');
        $lookup->execute([$bookingId]);
        $propertyId = (int)($lookup->fetchColumn() ?: 0);
        if ($propertyId < 1) {
            return;
        }
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT id FROM properties WHERE id=? LIMIT 1 FOR UPDATE');
            $lock->execute([$propertyId]);
            if ($lock->fetchColumn()) {
                BookingHoldExpiryService::expireSafePendingForProperty($pdo, $propertyId);
            }
            $pdo->commit();
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }
}
