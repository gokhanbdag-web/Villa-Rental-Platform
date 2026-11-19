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
    /** A reviewer records the transfer lifecycle; success requires a bank reference. */
    public static function review(PDO $pdo, int $reviewerId, int $refundId, string $decision, string $bankReference = ''): void
    {
        if ($reviewerId < 1 || $refundId < 1 || !in_array($decision, ['processing','failed','cancelled'], true)) {
            throw new \InvalidArgumentException('Banka iadesi inceleme bilgileri geçersiz.');
        }
        $bankReference = trim($bankReference);
        if (mb_strlen($bankReference) > 190) throw new \InvalidArgumentException('Banka referansı çok uzun.');
        $pdo->beginTransaction();
        try {
            $identity = $pdo->prepare('SELECT booking_id,payment_transaction_id FROM manual_bank_refunds WHERE id=?');
            $identity->execute([$refundId]);
            $record = $identity->fetch(PDO::FETCH_ASSOC);
            if (!$record) throw new \RuntimeException('Banka iadesi bulunamadı.');
            $booking = $pdo->prepare('SELECT id FROM bookings WHERE id=? FOR UPDATE');
            $booking->execute([(int)$record['booking_id']]);
            if (!$booking->fetchColumn()) throw new \RuntimeException('Rezervasyon bulunamadı.');
            $payment = $pdo->prepare('SELECT id FROM payment_transactions WHERE id=? AND booking_id=? FOR UPDATE');
            $payment->execute([(int)$record['payment_transaction_id'],(int)$record['booking_id']]);
            if (!$payment->fetchColumn()) throw new \RuntimeException('Ödeme bulunamadı.');
            $refund = $pdo->prepare('SELECT status FROM manual_bank_refunds WHERE id=? FOR UPDATE');
            $refund->execute([$refundId]);
            $status = $refund->fetchColumn();
            if ($status !== 'pending' && $status !== 'processing') throw new \RuntimeException('Banka iadesi daha önce sonuçlandırılmış.');
            if ($status === 'processing' && $decision === 'processing') throw new \RuntimeException('Banka iadesi zaten işleniyor.');
            $update = $pdo->prepare("UPDATE manual_bank_refunds SET status=?,bank_reference=?,reviewed_by_user_id=?,updated_at=NOW() WHERE id=? AND status=?");
            $update->execute([$decision,$bankReference !== '' ? $bankReference : null,$reviewerId,$refundId,$status]);
            if ($update->rowCount() !== 1) throw new \RuntimeException('Banka iadesi eşzamanlı değiştirildi.');
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /** Record a bank-confirmed transfer and atomically post the refund to booking financials. */
    public static function settleVerified(PDO $pdo, int $reviewerId, int $refundId, string $bankReference): void
    {
        $bankReference=trim($bankReference);
        if($reviewerId<1||$refundId<1||$bankReference===''||mb_strlen($bankReference)>190){
            throw new \InvalidArgumentException('Doğrulanmış banka dekont referansı gereklidir.');
        }
        $identity=$pdo->prepare('SELECT booking_id,payment_transaction_id FROM manual_bank_refunds WHERE id=?');
        $identity->execute([$refundId]);$record=$identity->fetch(PDO::FETCH_ASSOC);
        if(!$record)throw new \RuntimeException('Banka iadesi bulunamadı.');
        $pdo->beginTransaction();
        try{
            $booking=$pdo->prepare('SELECT id,guest_user_id FROM bookings WHERE id=? FOR UPDATE');
            $booking->execute([(int)$record['booking_id']]);$bookingRow=$booking->fetch(PDO::FETCH_ASSOC);
            if(!$bookingRow)throw new \RuntimeException('Rezervasyon bulunamadı.');
            $payment=$pdo->prepare('SELECT id,provider,status,amount,currency FROM payment_transactions WHERE id=? AND booking_id=? FOR UPDATE');
            $payment->execute([(int)$record['payment_transaction_id'],(int)$record['booking_id']]);$paid=$payment->fetch(PDO::FETCH_ASSOC);
            if(!$paid||$paid['provider']!=='manual'||!in_array($paid['status'],['paid','refunded'],true))throw new \RuntimeException('Manuel ödeme bulunamadı.');
            $financial=$pdo->prepare('SELECT gross_amount,platform_fee,vendor_net_amount FROM booking_financials WHERE booking_id=? FOR UPDATE');
            $financial->execute([(int)$record['booking_id']]);$money=$financial->fetch(PDO::FETCH_ASSOC);
            if(!$money)throw new \RuntimeException('Finans kaydı bulunamadı.');
            $refund=$pdo->prepare('SELECT amount,currency,status FROM manual_bank_refunds WHERE id=? AND payment_transaction_id=? FOR UPDATE');
            $refund->execute([$refundId,(int)$paid['id']]);$row=$refund->fetch(PDO::FETCH_ASSOC);
            if(!$row||$row['status']!=='processing')throw new \RuntimeException('Yalnızca işleme alınmış iadeler sonuçlandırılabilir.');
            if(strtoupper((string)$row['currency'])!==strtoupper((string)$paid['currency']))throw new \RuntimeException('İade para birimi uyuşmuyor.');
            $cents=static fn($v):(int)round((float)$v*100);
            $amount=$cents($row['amount']);$gross=$cents($money['gross_amount']);$fee=$cents($money['platform_fee']);$vendor=$cents($money['vendor_net_amount']);
            if($amount<1||$amount>$gross||$gross!==$fee+$vendor)throw new \RuntimeException('İade finans dağılımı tutarsız.');
            $vendorRefund=$amount===$gross?$vendor:(int)round($amount*$vendor/$gross);
            $feeRefund=$amount-$vendorRefund;
            if($feeRefund>$fee){$feeRefund=$fee;$vendorRefund=$amount-$feeRefund;}
            if($vendorRefund>$vendor){$vendorRefund=$vendor;$feeRefund=$amount-$vendorRefund;}
            $format=static fn(int $value):string=>number_format($value/100,2,'.','');
            $save=$pdo->prepare('UPDATE booking_financials SET gross_amount=?,platform_fee=?,vendor_net_amount=?,updated_at=NOW() WHERE booking_id=?');
            $save->execute([$format($gross-$amount),$format($fee-$feeRefund),$format($vendor-$vendorRefund),(int)$record['booking_id']]);
            $done=$pdo->prepare("UPDATE manual_bank_refunds SET status='succeeded',bank_reference=?,reviewed_by_user_id=?,completed_at=NOW(),updated_at=NOW() WHERE id=? AND status='processing'");
            $done->execute([$bankReference,$reviewerId,$refundId]);
            if($done->rowCount()!==1)throw new \RuntimeException('İade eşzamanlı olarak değişti.');
            $sum=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM manual_bank_refunds WHERE payment_transaction_id=? AND status='succeeded'");
            $sum->execute([(int)$paid['id']]);
            if($cents($sum->fetchColumn()) >= $cents($paid['amount']) && $paid['status']==='paid'){
                $pdo->prepare("UPDATE payment_transactions SET status='refunded',updated_at=NOW() WHERE id=? AND status='paid'")->execute([(int)$paid['id']]);
            }
            if((int)$bookingRow['guest_user_id']>0){
                $pdo->prepare("INSERT INTO notifications(user_id,notification_type,title,body,created_at) VALUES(?,'payment.refunded','Banka iadesi tamamlandı',?,NOW())")
                    ->execute([(int)$bookingRow['guest_user_id'],$format($amount).' '.(string)$row['currency']]);
            }
            $pdo->commit();
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }

}
