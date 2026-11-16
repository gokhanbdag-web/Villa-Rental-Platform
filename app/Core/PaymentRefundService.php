<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class PaymentRefundService
{
    public static function storeVerifiedPaymentDetails(PDO $pdo, int $paymentTransactionId, string $existingPayload, array $verified): void
    {
        $paymentId=trim((string)($verified['paymentId']??''));
        if($paymentTransactionId<1||$paymentId==='') return;
        $payload=json_decode($existingPayload,true);
        if(!is_array($payload)) $payload=[];
        $payload['verified_checkout_form']=[
            'paymentId'=>$paymentId,
            'price'=>$verified['price']??null,
            'paidPrice'=>$verified['paidPrice']??null,
            'currency'=>$verified['currency']??null,
            'conversationId'=>$verified['conversationId']??null,
            'itemTransactions'=>is_array($verified['itemTransactions']??null)?$verified['itemTransactions']:[],
        ];
        $stmt=$pdo->prepare("UPDATE payment_transactions SET response_payload=?,updated_at=NOW() WHERE id=? AND status='paid'");
        $stmt->execute([json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),$paymentTransactionId]);
    }

    public static function refundIyzico(PDO $pdo, int $actorUserId, int $paymentTransactionId, string $amount, string $reason): array
    {
        if ($actorUserId < 1 || $paymentTransactionId < 1 || strlen($reason) > 500) {
            throw new \InvalidArgumentException('İade talebi bilgileri geçersiz.');
        }
        $amountCents = self::toMinorUnits($amount);
        if ($amountCents < 1) throw new \InvalidArgumentException('İade tutarı sıfırdan büyük ve en fazla iki ondalık basamaklı olmalıdır.');

        $reserved = self::reserve($pdo, $actorUserId, $paymentTransactionId, $amountCents, trim($reason));
        $refundId = (int)$reserved['id'];

        try {
            $config = PaymentGatewayConfig::forProvider('iyzico');
            if (empty($config['configured'])) {
                self::setStatus($pdo, $refundId, 'failed', 'iyzico üretici bağlantı bilgileri yapılandırılmamış.');
                throw new \RuntimeException('iyzico bağlantı bilgileri yapılandırılmamış.');
            }
            $environment = (string)$reserved['environment'];
            if (!in_array($environment, ['sandbox','production'], true)) {
                self::setStatus($pdo, $refundId, 'failed', 'Ödeme işleminin kayıtlı ortamı geçersiz.');
                throw new \RuntimeException('Ödeme işleminin kayıtlı Sandbox/Production ortamı geçersiz.');
            }
            $config['environment'] = $environment;
            $secretKey = (string)($config['credentials']['secret_key'] ?? '');
            $conversationId = (string)$reserved['conversation_id'];
            $price = self::formatCents($amountCents);

            try {
                $response = IyzicoApiClient::refundV2($config, [
                    'locale' => 'tr',
                    'conversationId' => $conversationId,
                    'paymentId' => (string)$reserved['payment_id'],
                    'price' => $price,
                    'currency' => (string)$reserved['currency'],
                    'ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
                ], $environment);
            } catch (Throwable $e) {
                self::setStatus($pdo, $refundId, 'unknown', 'iyzico isteğinin sonucu doğrulanamadı; sağlayıcı panelinden kontrol edilmeden tekrar gönderilmemelidir.');
                throw new \RuntimeException('iyzico iade isteğinin sonucu belirsiz. Çift iade riskini önlemek için talep beklemede tutuldu; iyzico panelinden kontrol edin.', 0, $e);
            }

            if (strtolower((string)($response['status'] ?? '')) !== 'success') {
                $message = trim((string)($response['errorMessage'] ?? 'iyzico iade isteğini reddetti.'));
                self::setStatus($pdo, $refundId, 'failed', $message);
                throw new \RuntimeException($message);
            }

            if (!PaymentSignatureVerifier::verifyIyzicoRefund($response, $secretKey)
                || !hash_equals((string)$reserved['payment_id'], (string)($response['paymentId'] ?? ''))
                || !hash_equals($conversationId, (string)($response['conversationId'] ?? ''))
                || strtoupper((string)($response['currency'] ?? '')) !== strtoupper((string)$reserved['currency'])
                || self::toMinorUnits((string)($response['price'] ?? '')) !== $amountCents) {
                self::setStatus($pdo, $refundId, 'unknown', 'iyzico iade yanıtı imza veya işlem bilgisi doğrulamasından geçmedi.');
                throw new \RuntimeException('iyzico iade yanıtı doğrulanamadı. Sağlayıcı panelinden kontrol edilmeden tekrar gönderilmemelidir.');
            }

            return self::applyVerifiedRefund($pdo, $refundId, $actorUserId, $response, $secretKey);
        } catch (Throwable $e) {
            // A reserved refund remains blocked if the provider outcome is ambiguous.
            throw $e;
        }
    }

    public static function applyVerifiedRefund(PDO $pdo, int $refundId, int $actorUserId, array $response, string $secretKey): array
    {
        if ($refundId < 1 || $actorUserId < 1 || !PaymentSignatureVerifier::verifyIyzicoRefund($response, $secretKey)) {
            throw new \InvalidArgumentException('Doğrulanmış iyzico iade yanıtı gereklidir.');
        }

        $identity = $pdo->prepare('SELECT payment_transaction_id,booking_id FROM payment_refunds WHERE id=? LIMIT 1');
        $identity->execute([$refundId]);
        $refundIdentity = $identity->fetch(PDO::FETCH_ASSOC);
        if (!$refundIdentity) throw new \RuntimeException('İade kaydı bulunamadı.');

        $pdo->beginTransaction();
        try {
            // Keep the global payment lock order: booking -> payment transaction -> refund.
            $bookingLock = $pdo->prepare('SELECT id,guest_user_id FROM bookings WHERE id=? LIMIT 1 FOR UPDATE');
            $bookingLock->execute([(int)$refundIdentity['booking_id']]);
            $booking = $bookingLock->fetch(PDO::FETCH_ASSOC);
            if (!$booking) throw new \RuntimeException('İadeye bağlı rezervasyon bulunamadı.');

            $paymentLock = $pdo->prepare('SELECT id,booking_id,amount,currency,status,response_payload FROM payment_transactions WHERE id=? AND booking_id=? LIMIT 1 FOR UPDATE');
            $paymentLock->execute([(int)$refundIdentity['payment_transaction_id'], (int)$booking['id']]);
            $payment = $paymentLock->fetch(PDO::FETCH_ASSOC);
            $paymentPayload=json_decode((string)$payment['response_payload'],true);
            $expectedPaymentId=is_array($paymentPayload)?trim((string)($paymentPayload['verified_checkout_form']['paymentId']??'')):'';
            if($expectedPaymentId===''||!hash_equals($expectedPaymentId,(string)($response['paymentId']??''))) throw new \\RuntimeException('İade yanıtındaki ödeme kimliği yerel ödeme kaydıyla eşleşmedi.');
            if (!$payment || (string)$payment['status'] !== 'paid') throw new \RuntimeException('İade için tahsil edilmiş ödeme kaydı bulunamadı.');

            $financialLock = $pdo->prepare('SELECT gross_amount,platform_fee,vendor_net_amount,payout_status FROM booking_financials WHERE booking_id=? LIMIT 1 FOR UPDATE');
            $financialLock->execute([(int)$booking['id']]);
            $financial = $financialLock->fetch(PDO::FETCH_ASSOC);
            if (!$financial) throw new \RuntimeException('Rezervasyon finans kaydı bulunamadı.');

            $refundLock = $pdo->prepare("SELECT id,amount,currency,status,conversation_id FROM payment_refunds WHERE id=? AND payment_transaction_id=? LIMIT 1 FOR UPDATE");
            $refundLock->execute([$refundId, (int)$payment['id']]);
            $refund = $refundLock->fetch(PDO::FETCH_ASSOC);
            if (!$refund || (string)$refund['status'] !== 'processing') throw new \RuntimeException('İade kaydı artık işlenebilir durumda değil.');

            $amountCents = self::toMinorUnits((string)$refund['amount']);
            if (!hash_equals((string)$refund['conversation_id'], (string)($response['conversationId'] ?? ''))
                || strtoupper((string)$refund['currency']) !== strtoupper((string)($response['currency'] ?? ''))
                || self::toMinorUnits((string)($response['price'] ?? '')) !== $amountCents) {
                throw new \RuntimeException('İade yanıtı ayrılmış iade tutarıyla eşleşmiyor.');
            }

            $grossCents = self::toMinorUnits((string)$financial['gross_amount']);
            $platformCents = self::toMinorUnits((string)$financial['platform_fee']);
            $vendorCents = self::toMinorUnits((string)$financial['vendor_net_amount']);
            if ($amountCents > $grossCents) throw new \RuntimeException('İade tutarı kalan rezervasyon finans tutarını aşıyor.');

            if ($amountCents === $grossCents) {
                $vendorRefundCents = $vendorCents;
                $platformRefundCents = $platformCents;
            } else {
                $vendorRefundCents = (int)round($amountCents * $vendorCents / max(1, $grossCents));
                $platformRefundCents = $amountCents - $vendorRefundCents;
                if ($platformRefundCents > $platformCents) {
                    $platformRefundCents = $platformCents;
                    $vendorRefundCents = $amountCents - $platformRefundCents;
                }
                if ($vendorRefundCents > $vendorCents) {
                    $vendorRefundCents = $vendorCents;
                    $platformRefundCents = $amountCents - $vendorRefundCents;
                }
            }

            $newGross = self::formatCents($grossCents - $amountCents);
            $newPlatform = self::formatCents(max(0, $platformCents - $platformRefundCents));
            $newVendor = self::formatCents(max(0, $vendorCents - $vendorRefundCents));
            $updateFinancial = $pdo->prepare('UPDATE booking_financials SET gross_amount=?,platform_fee=?,vendor_net_amount=?,updated_at=NOW() WHERE booking_id=?');
            $updateFinancial->execute([$newGross, $newPlatform, $newVendor, (int)$booking['id']]);

            $responseJson = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $providerReference = trim((string)($response['refundHostReference'] ?? ''));
            $updateRefund = $pdo->prepare("UPDATE payment_refunds SET status='succeeded',provider_reference=?,response_payload=?,error_message=NULL,completed_at=NOW(),updated_at=NOW() WHERE id=? AND status='processing'");
            $updateRefund->execute([$providerReference !== '' ? $providerReference : null, $responseJson, $refundId]);
            if ($updateRefund->rowCount() !== 1) throw new \RuntimeException('İade kaydı eşzamanlı olarak değişti.');

            $sumRefunds = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payment_refunds WHERE payment_transaction_id=? AND status='succeeded'");
            $sumRefunds->execute([(int)$payment['id']]);
            $totalRefundedCents = self::toMinorUnits((string)$sumRefunds->fetchColumn());
            $paymentOriginalCents = self::toMinorUnits((string)$payment['amount']);
            if ($totalRefundedCents >= $paymentOriginalCents && PaymentTransactionGuard::canTransition((string)$payment['status'], 'refunded')) {
                $updatePayment = $pdo->prepare("UPDATE payment_transactions SET status='refunded',updated_at=NOW() WHERE id=? AND status='paid'");
                $updatePayment->execute([(int)$payment['id']]);
            }

            if ((int)$booking['guest_user_id'] > 0) {
                $notice = $pdo->prepare("INSERT INTO notifications(user_id,notification_type,title,body,created_at) VALUES(?,'payment.refunded','Ödeme iadesi tamamlandı',?,NOW())");
                $notice->execute([(int)$booking['guest_user_id'], 'İade tutarı: ' . self::formatCents($amountCents) . ' ' . (string)$refund['currency']]);
            }

            $pdo->commit();
            return ['id'=>$refundId, 'amount'=>self::formatCents($amountCents), 'currency'=>(string)$refund['currency'], 'status'=>'succeeded', 'provider_reference'=>$providerReference];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    private static function reserve(PDO $pdo, int $actorUserId, int $paymentTransactionId, int $amountCents, string $reason): array
    {
        $identity = $pdo->prepare('SELECT booking_id FROM payment_transactions WHERE id=? LIMIT 1');
        $identity->execute([$paymentTransactionId]);
        $bookingId = (int)($identity->fetchColumn() ?: 0);
        if ($bookingId < 1) throw new \RuntimeException('Ödeme kaydı bulunamadı.');

        $pdo->beginTransaction();
        try {
            $bookingLock = $pdo->prepare('SELECT id FROM bookings WHERE id=? LIMIT 1 FOR UPDATE');
            $bookingLock->execute([$bookingId]);
            if (!$bookingLock->fetchColumn()) throw new \RuntimeException('Rezervasyon bulunamadı.');

            $paymentLock = $pdo->prepare('SELECT id,booking_id,provider,amount,currency,status,request_payload,response_payload FROM payment_transactions WHERE id=? AND booking_id=? LIMIT 1 FOR UPDATE');
            $paymentLock->execute([$paymentTransactionId, $bookingId]);
            $payment = $paymentLock->fetch(PDO::FETCH_ASSOC);
            if (!$payment || (string)$payment['provider'] !== 'iyzico' || (string)$payment['status'] !== 'paid') {
                throw new \RuntimeException('Yalnızca iyzico üzerinden tahsil edilmiş ödemeler iade edilebilir.');
            }

            $financialLock = $pdo->prepare('SELECT gross_amount FROM booking_financials WHERE booking_id=? LIMIT 1 FOR UPDATE');
            $financialLock->execute([$bookingId]);
            $financial = $financialLock->fetch(PDO::FETCH_ASSOC);
            if (!$financial) throw new \RuntimeException('Rezervasyon finans kaydı bulunamadı.');

            $payoutCheck = $pdo->prepare("SELECT 1 FROM vendor_payout_allocations vpa JOIN vendor_payouts vp ON vp.id=vpa.payout_id WHERE vpa.booking_id=? AND vp.status IN ('requested','processing','paid') LIMIT 1");
            $payoutCheck->execute([$bookingId]);
            if ($payoutCheck->fetchColumn()) throw new \RuntimeException('Satıcıya ödeme talebi ayrıldığı için bu rezervasyonun iadesi şu anda yapılamaz.');

            $pendingRefund = $pdo->prepare("SELECT COUNT(*) FROM payment_refunds WHERE payment_transaction_id=? AND status IN ('processing','unknown')");
            $pendingRefund->execute([$paymentTransactionId]);
            if ((int)$pendingRefund->fetchColumn() > 0) throw new \RuntimeException('Bu ödeme için sonucu henüz kesinleşmemiş başka bir iade talebi var.');

            $payload = json_decode((string)$payment['response_payload'], true);
            $paymentId = is_array($payload) ? trim((string)($payload['verified_checkout_form']['paymentId'] ?? '')) : '';
            if ($paymentId === '') throw new \RuntimeException('iyzico ödeme kimliği bulunamadı. Ödeme sonucu ayrıntısı kayıtlı değil.');

            $requestPayload = json_decode((string)$payment['request_payload'], true);
            $environment = is_array($requestPayload) ? (string)($requestPayload['environment'] ?? 'sandbox') : 'sandbox';
            if (!in_array($environment, ['sandbox','production'], true)) throw new \RuntimeException('Ödeme ortamı kaydı geçersiz.');

            $sum = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payment_refunds WHERE payment_transaction_id=? AND status IN ('succeeded','processing','unknown')");
            $sum->execute([$paymentTransactionId]);
            $reservedCents = self::toMinorUnits((string)$sum->fetchColumn());
            $paymentCents = self::toMinorUnits((string)$payment['amount']);
            $remainingGrossCents = self::toMinorUnits((string)$financial['gross_amount']);
            $remainingCents = min($paymentCents - $reservedCents, $remainingGrossCents);
            if ($amountCents > $remainingCents) throw new \RuntimeException('İade tutarı kalan tahsilat tutarını aşıyor.');

            $conversationId = 'villa-refund-' . bin2hex(random_bytes(10));
            $insert = $pdo->prepare("INSERT INTO payment_refunds(payment_transaction_id,booking_id,provider,amount,currency,status,conversation_id,reason,actor_user_id,created_at,updated_at) VALUES(?,?,'iyzico',?,?,'processing',?,?,?,NOW(),NOW())");
            $insert->execute([$paymentTransactionId,$bookingId,self::formatCents($amountCents),(string)$payment['currency'],$conversationId,$reason !== '' ? $reason : null,$actorUserId]);
            $refundId = (int)$pdo->lastInsertId();

            $pdo->commit();
            return ['id'=>$refundId,'payment_id'=>$paymentId,'currency'=>(string)$payment['currency'],'conversation_id'=>$conversationId,'environment'=>$environment];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    private static function setStatus(PDO $pdo, int $refundId, string $status, string $error): void
    {
        $stmt = $pdo->prepare("UPDATE payment_refunds SET status=?,error_message=?,updated_at=NOW() WHERE id=? AND status='processing'");
        $stmt->execute([$status, mb_substr(preg_replace('/[\r\n]+/', ' ', $error) ?? 'Refund error', 0, 450), $refundId]);
    }

    private static function toMinorUnits(string $amount): int
    {
        $amount = trim($amount);
        if (!preg_match('/^(?:0|[1-9][0-9]{0,9})(?:\.([0-9]{1,2}))?$/', $amount, $match)) return 0;
        [$whole] = explode('.', $amount, 2);
        return ((int)$whole * 100) + (int)str_pad($match[1] ?? '', 2, '0');
    }

    private static function formatCents(int $cents): string
    {
        return intdiv(max(0, $cents), 100) . '.' . str_pad((string)(max(0, $cents) % 100), 2, '0', STR_PAD_LEFT);
    }
}
