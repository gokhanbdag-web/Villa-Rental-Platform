<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

/** Atomically reserves one iyzico checkout initialization per booking. */
final class PaymentCheckoutGuard
{
    /** Atomically reserves one PayTR hosted checkout per booking. */
    public static function reservePaytr(PDO $pdo,int $bookingId,string $merchantOid,string $amount,string $currency,array $requestPayload):array
    {
        if($bookingId<1||$merchantOid===''||$amount===''||$currency==='')throw new \InvalidArgumentException('PayTR ödeme oturumu bilgileri eksik.');
        $pdo->beginTransaction();
        try{
            $lock=$pdo->prepare('SELECT id,status FROM bookings WHERE id=? LIMIT 1 FOR UPDATE');
            $lock->execute([$bookingId]);$booking=$lock->fetch(PDO::FETCH_ASSOC);
            if(!$booking){$pdo->commit();return ['result'=>'booking_not_found'];}
            if((string)$booking['status']!=='pending'){$pdo->commit();return ['result'=>'booking_not_pending'];}
            $paid=$pdo->prepare("SELECT id FROM payment_transactions WHERE booking_id=? AND status='paid' LIMIT 1 FOR UPDATE");
            $paid->execute([$bookingId]);if($paid->fetchColumn()!==false){$pdo->commit();return ['result'=>'already_paid'];}
                        $cross=$pdo->prepare("SELECT id,provider,provider_reference,status,expires_at,(expires_at IS NOT NULL AND expires_at<=NOW()) AS expires_now FROM payment_transactions WHERE booking_id=? AND provider='iyzico' AND status IN ('pending','processing','expired') ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $cross->execute([$bookingId]);$crossRow=$cross->fetch(PDO::FETCH_ASSOC);
            if($crossRow){
                $crossState=(string)$crossRow['status'];$crossRef=(string)$crossRow['provider_reference'];
                if($crossState==='expired'&&str_starts_with($crossRef,'init:')){
                    // Expired initialization placeholders never reached the provider and are safe to ignore.
                }elseif($crossState==='pending'&&str_starts_with($crossRef,'init:')&&(int)($crossRow['expires_now']??0)===1){
                    $pdo->prepare("UPDATE payment_transactions SET status='expired',updated_at=NOW() WHERE id=? AND status='pending'")->execute([(int)$crossRow['id']]);
                }elseif($crossState==='pending'&&str_starts_with($crossRef,'init:')){
                    $pdo->commit();return ['result'=>'initializing'];
                }elseif($crossState==='processing'){
                    $pdo->commit();return ['result'=>'processing'];
                }else{
                    $pdo->commit();return ['result'=>'verification_required','payment'=>$crossRow];
                }
            }
$active=$pdo->prepare("SELECT id,booking_id,provider,provider_reference,amount,currency,request_payload,response_payload,status,expires_at,(expires_at IS NOT NULL AND expires_at<=NOW()) AS expires_now FROM payment_transactions WHERE booking_id=? AND provider='paytr' AND status IN ('pending','processing','expired') ORDER BY id ASC LIMIT 1 FOR UPDATE");
            $active->execute([$bookingId]);$row=$active->fetch(PDO::FETCH_ASSOC);
            if($row&&(string)$row['status']==='processing'){$pdo->commit();return ['result'=>'processing'];}
            if($row&&(string)$row['status']==='expired'&&!str_starts_with((string)$row['provider_reference'],'init:')){$pdo->commit();return ['result'=>'verification_required','payment'=>$row];}
            if($row&&(string)$row['status']==='pending'){
                $unexpired=(int)($row['expires_now']??0)===0&&!empty($row['expires_at']);
                if($unexpired&&str_starts_with((string)$row['provider_reference'],'init:')){$pdo->commit();return ['result'=>'initializing'];}
                if($unexpired){$pdo->commit();return ['result'=>'existing','payment'=>$row];}
                if(!str_starts_with((string)$row['provider_reference'],'init:')){$pdo->commit();return ['result'=>'verification_required','payment'=>$row];}
                $expire=$pdo->prepare("UPDATE payment_transactions SET status='expired',updated_at=NOW() WHERE id=? AND status='pending'");
                $expire->execute([(int)$row['id']]);
            }
            $placeholder='init:'.$merchantOid;
            $key=hash('sha256','paytr:booking:'.$bookingId.':'.$merchantOid);
            $insert=$pdo->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,idempotency_key,amount,currency,status,request_payload,expires_at,created_at,updated_at) VALUES(?,'paytr',?,?,?,?, 'pending',?,DATE_ADD(NOW(),INTERVAL 5 MINUTE),NOW(),NOW())");
            $insert->execute([$bookingId,$placeholder,$key,$amount,$currency,json_encode($requestPayload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
            $id=(int)$pdo->lastInsertId();
            $hold=$pdo->prepare("UPDATE bookings SET hold_expires_at=DATE_ADD(NOW(),INTERVAL 30 MINUTE) WHERE id=? AND status='pending'");
            $hold->execute([$bookingId]);$pdo->commit();
            return ['result'=>'reserved','transaction_id'=>$id,'provider_reference'=>$placeholder];
        }catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();throw $error;}
    }

    public static function reserveIyzico(PDO $pdo, int $bookingId, string $conversationId, string $amount, string $currency, array $requestPayload): array
    {
        if ($bookingId < 1 || $conversationId === '' || $amount === '' || $currency === '') {
            throw new \InvalidArgumentException('Ödeme oturumu rezervasyon bilgileri eksik.');
        }
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT id,status FROM bookings WHERE id=? LIMIT 1 FOR UPDATE');
            $lock->execute([$bookingId]);
            $booking = $lock->fetch(PDO::FETCH_ASSOC);
            if (!$booking) { $pdo->commit(); return ['result'=>'booking_not_found']; }
            if ((string)$booking['status'] !== 'pending') { $pdo->commit(); return ['result'=>'booking_not_pending']; }

            $paid = $pdo->prepare("SELECT id FROM payment_transactions WHERE booking_id=? AND status='paid' LIMIT 1 FOR UPDATE");
            $paid->execute([$bookingId]);
            if ($paid->fetchColumn() !== false) { $pdo->commit(); return ['result'=>'already_paid']; }

                        $cross=$pdo->prepare("SELECT id,provider,provider_reference,status,expires_at,(expires_at IS NOT NULL AND expires_at<=NOW()) AS expires_now FROM payment_transactions WHERE booking_id=? AND provider='paytr' AND status IN ('pending','processing','expired') ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $cross->execute([$bookingId]);$crossRow=$cross->fetch(PDO::FETCH_ASSOC);
            if($crossRow){
                $crossState=(string)$crossRow['status'];$crossRef=(string)$crossRow['provider_reference'];
                if($crossState==='expired'&&str_starts_with($crossRef,'init:')){
                    // Expired initialization placeholders never reached the provider and are safe to ignore.
                }elseif($crossState==='pending'&&str_starts_with($crossRef,'init:')&&(int)($crossRow['expires_now']??0)===1){
                    $pdo->prepare("UPDATE payment_transactions SET status='expired',updated_at=NOW() WHERE id=? AND status='pending'")->execute([(int)$crossRow['id']]);
                }elseif($crossState==='pending'&&str_starts_with($crossRef,'init:')){
                    $pdo->commit();return ['result'=>'initializing'];
                }elseif($crossState==='processing'){
                    $pdo->commit();return ['result'=>'processing'];
                }else{
                    $pdo->commit();return ['result'=>'verification_required','payment'=>$crossRow];
                }
            }
$active = $pdo->prepare("SELECT id,booking_id,provider,provider_reference,amount,currency,request_payload,response_payload,status,expires_at,(expires_at IS NOT NULL AND expires_at<=NOW()) AS expires_now FROM payment_transactions WHERE booking_id=? AND provider='iyzico' AND status IN ('pending','processing','expired') ORDER BY CASE WHEN provider_reference NOT LIKE 'init:%' THEN 0 ELSE 1 END,id ASC LIMIT 1 FOR UPDATE");
            $active->execute([$bookingId]);
            $row = $active->fetch(PDO::FETCH_ASSOC);
            if ($row && (string)$row['status'] === 'processing') { $pdo->commit(); return ['result'=>'processing']; }
            if ($row && (string)$row['status'] === 'expired' && !str_starts_with((string)$row['provider_reference'], 'init:')) {
                $pdo->commit();
                return ['result'=>'verification_required','payment'=>$row];
            }
            if ($row && (string)$row['status'] === 'pending') {
                $unexpired = (int)($row['expires_now'] ?? 0) === 0 && !empty($row['expires_at']);
                if ($unexpired && str_starts_with((string)$row['provider_reference'], 'init:')) {
                    $pdo->commit(); return ['result'=>'initializing'];
                }
                if ($unexpired) { $pdo->commit(); return ['result'=>'existing','payment'=>$row]; }
                // A real provider token may have been paid just before expiry while
                // its callback is delayed. Never expire it locally or create a
                // second checkout until the provider outcome has been reconciled.
                if (!str_starts_with((string)$row['provider_reference'], 'init:')) {
                    $pdo->commit();
                    return ['result'=>'verification_required','payment'=>$row];
                }
                // An init placeholder has no provider token and was never exposed
                // as a usable checkout session, so an abandoned initialization can
                // safely be retired and retried.
                $expire = $pdo->prepare("UPDATE payment_transactions SET status='expired',updated_at=NOW() WHERE id=? AND status='pending'");
                $expire->execute([(int)$row['id']]);
            }

            $placeholder = 'init:' . $conversationId;
            $key = hash('sha256', 'iyzico:booking:' . $bookingId . ':' . $conversationId);
            $insert = $pdo->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,idempotency_key,amount,currency,status,request_payload,expires_at,created_at,updated_at) VALUES(?,'iyzico',?,?,?,?, 'pending',?,DATE_ADD(NOW(),INTERVAL 5 MINUTE),NOW(),NOW())");
            $insert->execute([$bookingId,$placeholder,$key,$amount,$currency,json_encode($requestPayload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
            $id = (int)$pdo->lastInsertId();
            $extendHold = $pdo->prepare("UPDATE bookings SET hold_expires_at=DATE_ADD(NOW(),INTERVAL 30 MINUTE) WHERE id=? AND status='pending'");
            $extendHold->execute([$bookingId]);
            $pdo->commit();
            return ['result'=>'reserved','transaction_id'=>$id,'provider_reference'=>$placeholder];
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }
}
