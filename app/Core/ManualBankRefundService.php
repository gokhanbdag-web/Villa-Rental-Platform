<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/** Records a bank refund request without claiming that funds have been transferred. */
final class ManualBankRefundService
{
    public static function request(PDO $pdo, int $actorId, int $paymentId, string $amount, string $reason): int
    {
        if ($actorId < 1 || $paymentId < 1 || !preg_match('/^(?:0|[1-9][0-9]{0,9})(?:\.[0-9]{1,2})?$/', $amount)
            || (float)$amount <= 0 || mb_strlen($reason) > 500) {
            throw new \InvalidArgumentException('Banka iadesi talebi geçersiz.');
        }
        $pdo->beginTransaction();
        try {
            $identity = $pdo->prepare('SELECT booking_id FROM payment_transactions WHERE id=?');
            $identity->execute([$paymentId]);
            $bookingId = (int)($identity->fetchColumn() ?: 0);
            if ($bookingId < 1) throw new \RuntimeException('Ödeme bulunamadı.');
            $bookingLock = $pdo->prepare('SELECT id FROM bookings WHERE id=? FOR UPDATE');
            $bookingLock->execute([$bookingId]);
            if (!$bookingLock->fetchColumn()) throw new \RuntimeException('Rezervasyon bulunamadı.');
            $paymentLock = $pdo->prepare('SELECT provider,status,amount,currency FROM payment_transactions WHERE id=? AND booking_id=? FOR UPDATE');
            $paymentLock->execute([$paymentId,$bookingId]);
            $payment = $paymentLock->fetch(PDO::FETCH_ASSOC);
            if (!$payment || $payment['provider'] !== 'manual' || $payment['status'] !== 'paid') {
                throw new \RuntimeException('Yalnızca tahsil edilmiş manuel havaleler için banka iadesi oluşturulabilir.');
            }
            $pending = $pdo->prepare("SELECT COUNT(*) FROM manual_bank_refunds WHERE payment_transaction_id=? AND status IN ('pending','processing')");
            $pending->execute([$paymentId]);
            if ((int)$pending->fetchColumn() > 0) throw new \RuntimeException('Bu ödeme için bekleyen banka iadesi var.');
            $sum = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM manual_bank_refunds WHERE payment_transaction_id=? AND status IN ('succeeded','pending','processing')");
            $sum->execute([$paymentId]);
            $reserved = (int)round((float)$sum->fetchColumn() * 100);
            $original = (int)round((float)$payment['amount'] * 100);
            $requested = (int)round((float)$amount * 100);
            if ($requested < 1 || $requested > $original - $reserved) throw new \RuntimeException('Banka iadesi kalan tahsilatı aşıyor.');
            $insert = $pdo->prepare("INSERT INTO manual_bank_refunds(booking_id,payment_transaction_id,amount,currency,status,reason,actor_user_id) VALUES(?,?,?,?,'pending',?,?)");
            $insert->execute([$bookingId,$paymentId,number_format($requested/100,2,'.',''),$payment['currency'],trim($reason) ?: null,$actorId]);
            $id = (int)$pdo->lastInsertId();
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
