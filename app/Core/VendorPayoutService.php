<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class VendorPayoutService
{
    public static function savePayoutProfile(PDO $pdo, int $userId, string $bankName, string $accountHolder, string $iban): void
    {
        $bankName=trim($bankName);
        $accountHolder=trim($accountHolder);
        $iban=strtoupper(preg_replace('/\\s+/', '', $iban) ?? '');
        if($userId<1||$bankName===''||strlen($bankName)>120||$accountHolder===''||strlen($accountHolder)>190||!self::validIban($iban)) {
            throw new \InvalidArgumentException('Banka adı, hesap sahibi ve geçerli IBAN bilgilerini kontrol edin.');
        }
        $vendor=$pdo->prepare('SELECT id FROM vendors WHERE user_id=? LIMIT 1');
        $vendor->execute([$userId]);$vendorId=(int)($vendor->fetchColumn()?:0);
        if($vendorId<1) throw new \RuntimeException('Ev sahibi hesabı bulunamadı.');
        $stmt=$pdo->prepare('INSERT INTO vendor_payout_profiles(vendor_id,bank_name,account_holder,iban_encrypted,created_at,updated_at) VALUES(?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE bank_name=VALUES(bank_name),account_holder=VALUES(account_holder),iban_encrypted=VALUES(iban_encrypted),updated_at=NOW()');
        $stmt->execute([$vendorId,$bankName,$accountHolder,PaymentSecretVault::encrypt($iban)]);
        }
    }

    public static function validIban(string $iban): bool
    {
        $iban=strtoupper(preg_replace('/\\s+/', '', $iban) ?? '');
        if(strlen($iban)<15||strlen($iban)>34||!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/',$iban)) return false;
        $rearranged=substr($iban,4).substr($iban,0,4);
        $remainder=0;
        foreach(str_split($rearranged) as $char) {
            $value=ctype_alpha($char)?(string)(ord($char)-55):$char;
            foreach(str_split($value) as $digit) $remainder=($remainder*10+(int)$digit)%97;
        }
        return $remainder===1;
    }

    public static function availableBalances(PDO $pdo, int $vendorId): array
    {
        $stmt = $pdo->prepare("SELECT bf.currency,
            SUM(GREATEST(0, bf.vendor_net_amount - COALESCE((
                SELECT SUM(vpa.amount)
                FROM vendor_payout_allocations vpa
                JOIN vendor_payouts vp ON vp.id=vpa.payout_id
                WHERE vpa.booking_id=bf.booking_id AND vp.status IN ('requested','processing','paid')
            ),0))) AS available_amount
            FROM booking_financials bf
            JOIN bookings b ON b.id=bf.booking_id
            JOIN properties p ON p.id=b.property_id
            WHERE p.vendor_id=? AND b.status IN ('confirmed','completed') AND b.check_out<=CURDATE()
              AND EXISTS (SELECT 1 FROM payment_transactions pt WHERE pt.booking_id=b.id AND pt.status='paid')
            GROUP BY bf.currency
            HAVING available_amount > 0
            ORDER BY bf.currency");
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function request(PDO $pdo, int $userId, string $currency): array
    {
        $currency = strtoupper(trim($currency));
        if ($userId < 1 || !preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException('Ödeme talebi bilgileri geçersiz.');
        }

        $pdo->beginTransaction();
        try {
            $vendorStmt = $pdo->prepare('SELECT id,status FROM vendors WHERE user_id=? LIMIT 1 FOR UPDATE');
            $vendorStmt->execute([$userId]);
            $vendor = $vendorStmt->fetch(PDO::FETCH_ASSOC);
            if (!$vendor || (string)$vendor['status'] !== 'approved') {
                throw new \RuntimeException('Ödeme talebi için onaylı ev sahibi hesabı gereklidir.');
            }
            $vendorId = (int)$vendor['id'];
            $profileStmt=$pdo->prepare('SELECT bank_name,account_holder,iban_encrypted FROM vendor_payout_profiles WHERE vendor_id=? LIMIT 1 FOR UPDATE');
            $profileStmt->execute([$vendorId]);$profile=$profileStmt->fetch(PDO::FETCH_ASSOC);
            if(!$profile||trim((string)$profile['bank_name'])===''||trim((string)$profile['account_holder'])===''||!self::validIban(PaymentSecretVault::decrypt((string)$profile['iban_encrypted']))) {
                throw new \RuntimeException('Ödeme talebinden önce banka adı, hesap sahibi ve doğrulanmış IBAN bilgilerinizi kaydedin.');
            }

            $rowsStmt = $pdo->prepare("SELECT bf.booking_id,bf.vendor_net_amount,bf.currency,
                GREATEST(0, bf.vendor_net_amount - COALESCE((
                    SELECT SUM(vpa.amount)
                    FROM vendor_payout_allocations vpa
                    JOIN vendor_payouts vp ON vp.id=vpa.payout_id
                    WHERE vpa.booking_id=bf.booking_id AND vp.status IN ('requested','processing','paid')
                ),0)) AS available_amount
                FROM booking_financials bf
                JOIN bookings b ON b.id=bf.booking_id
                JOIN properties p ON p.id=b.property_id
                WHERE p.vendor_id=? AND bf.currency=? AND b.status IN ('confirmed','completed') AND b.check_out<=CURDATE()
                  AND EXISTS (SELECT 1 FROM payment_transactions pt WHERE pt.booking_id=b.id AND pt.status='paid')
                ORDER BY bf.booking_id
                FOR UPDATE");
            $rowsStmt->execute([$vendorId, $currency]);
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

            $allocations = [];
            $totalCents = 0;
            foreach ($rows as $row) {
                $amount = (string)$row['available_amount'];
                if (!preg_match('/^(?:0|[1-9][0-9]{0,9})(?:\.([0-9]{1,2}))?$/', $amount, $match)) {
                    throw new \RuntimeException('Ev sahibi bakiyesi güvenli biçimde hesaplanamadı.');
                }
                [$whole] = explode('.', $amount, 2);
                $cents = ((int)$whole * 100) + (int)str_pad($match[1] ?? '', 2, '0');
                if ($cents < 1) continue;
                $allocations[] = ['booking_id'=>(int)$row['booking_id'], 'cents'=>$cents];
                $totalCents += $cents;
            }
            if ($totalCents < 1) {
                throw new \RuntimeException('Ödeme talebine uygun, tamamlanmış ve tahsil edilmiş rezervasyon bakiyesi bulunamadı.');
            }

            $amount = intdiv($totalCents, 100) . '.' . str_pad((string)($totalCents % 100), 2, '0', STR_PAD_LEFT);
            $insert = $pdo->prepare("INSERT INTO vendor_payouts(vendor_id,amount,currency,status,requested_at) VALUES(?,?,?,'requested',NOW())");
            $insert->execute([$vendorId, $amount, $currency]);
            $payoutId = (int)$pdo->lastInsertId();

            $allocationInsert = $pdo->prepare('INSERT INTO vendor_payout_allocations(payout_id,booking_id,amount,currency,created_at) VALUES(?,?,?,?,NOW())');
            foreach ($allocations as $allocation) {
                $itemAmount = intdiv($allocation['cents'], 100) . '.' . str_pad((string)($allocation['cents'] % 100), 2, '0', STR_PAD_LEFT);
                $allocationInsert->execute([$payoutId, $allocation['booking_id'], $itemAmount, $currency]);
            }
            $markReserved = $pdo->prepare("UPDATE booking_financials SET payout_status='processing',updated_at=NOW() WHERE booking_id IN (SELECT booking_id FROM vendor_payout_allocations WHERE payout_id=?)");
            $markReserved->execute([$payoutId]);
            $event = $pdo->prepare("INSERT INTO vendor_payout_events(payout_id,actor_user_id,from_status,to_status,notes,created_at) VALUES(?,?,NULL,'requested','Ev sahibi ödeme talebi oluşturdu.',NOW())");
            $event->execute([$payoutId, $userId]);
            $pdo->commit();

            return ['id'=>$payoutId, 'amount'=>$amount, 'currency'=>$currency, 'allocation_count'=>count($allocations)];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public static function transition(PDO $pdo, int $actorUserId, int $payoutId, string $toStatus, string $reference = '', string $notes = ''): array
    {
        $allowedTargets = ['processing','paid','failed','cancelled'];
        if ($actorUserId < 1 || $payoutId < 1 || !in_array($toStatus, $allowedTargets, true)
            || strlen($reference) > 190 || preg_match('/[\r\n]/', $reference)
            || strlen($notes) > 2000) {
            throw new \InvalidArgumentException('Ödeme durumu veya açıklama bilgileri geçersiz.');
        }
        if ($toStatus === 'paid' && trim($reference) === '') {
            throw new \InvalidArgumentException('Ödeme tamamlandı olarak işaretlenirken banka işlem referansı girilmelidir.');
        }

        $pdo->beginTransaction();
        try {
            $lookup = $pdo->prepare('SELECT vendor_id FROM vendor_payouts WHERE id=? LIMIT 1');
            $lookup->execute([$payoutId]);
            $vendorId = (int)($lookup->fetchColumn() ?: 0);
            if ($vendorId < 1) throw new \RuntimeException('Ödeme talebi bulunamadı.');

            // Every payout operation locks the vendor before the payout row.
            $vendorLock = $pdo->prepare('SELECT id FROM vendors WHERE id=? LIMIT 1 FOR UPDATE');
            $vendorLock->execute([$vendorId]);
            if (!$vendorLock->fetchColumn()) throw new \RuntimeException('Ev sahibi hesabı bulunamadı.');

            $payoutStmt = $pdo->prepare('SELECT id,vendor_id,amount,currency,status FROM vendor_payouts WHERE id=? AND vendor_id=? LIMIT 1 FOR UPDATE');
            $payoutStmt->execute([$payoutId, $vendorId]);
            $payout = $payoutStmt->fetch(PDO::FETCH_ASSOC);
            if (!$payout) throw new \RuntimeException('Ödeme talebi bulunamadı.');

            $fromStatus = (string)$payout['status'];
            $transitions = [
                'requested' => ['processing','paid','failed','cancelled'],
                'processing' => ['paid','failed','cancelled'],
            ];
            if (!in_array($toStatus, $transitions[$fromStatus] ?? [], true)) {
                throw new \RuntimeException('Bu ödeme talebi için istenen durum geçişine izin verilmiyor.');
            }

            $update = $pdo->prepare('UPDATE vendor_payouts SET status=?,payout_reference=CASE WHEN ?<>\'\' THEN ? ELSE payout_reference END,notes=?,processed_at=CASE WHEN ? IN (\'paid\',\'failed\',\'cancelled\') THEN NOW() ELSE NULL END WHERE id=? AND status=?');
            $update->execute([$toStatus, trim($reference), trim($reference), trim($notes), $toStatus, $payoutId, $fromStatus]);
            if ($update->rowCount() !== 1) throw new \RuntimeException('Ödeme talebi eşzamanlı olarak değiştirilmiş; sayfayı yenileyin.');

            $event = $pdo->prepare('INSERT INTO vendor_payout_events(payout_id,actor_user_id,from_status,to_status,payout_reference,notes,created_at) VALUES(?,?,?,?,?,?,NOW())');
            $event->execute([$payoutId, $actorUserId, $fromStatus, $toStatus, $reference !== '' ? $reference : null, $notes !== '' ? $notes : null]);

            $recalculate = $pdo->prepare("UPDATE booking_financials bf
                SET payout_status = CASE
                    WHEN COALESCE((SELECT SUM(vpa.amount) FROM vendor_payout_allocations vpa JOIN vendor_payouts vp ON vp.id=vpa.payout_id WHERE vpa.booking_id=bf.booking_id AND vp.status='paid'),0) >= bf.vendor_net_amount THEN 'paid'
                    WHEN COALESCE((SELECT SUM(vpa.amount) FROM vendor_payout_allocations vpa JOIN vendor_payouts vp ON vp.id=vpa.payout_id WHERE vpa.booking_id=bf.booking_id AND vp.status IN ('requested','processing')),0) > 0 THEN 'processing'
                    ELSE 'unpaid'
                END
                WHERE bf.booking_id IN (SELECT booking_id FROM vendor_payout_allocations WHERE payout_id=?)");
            $recalculate->execute([$payoutId]);

            $pdo->commit();
            return ['id'=>$payoutId, 'vendor_id'=>$vendorId, 'from_status'=>$fromStatus, 'status'=>$toStatus, 'amount'=>(string)$payout['amount'], 'currency'=>(string)$payout['currency']];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
