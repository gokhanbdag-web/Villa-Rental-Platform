<?php
declare(strict_types=1);
namespace App\Core;
use PDO;
use JsonException;

final class PaymentWebhookInbox
{
    public function __construct(private readonly PDO $pdo) {}

    /** Store only a verified provider notification; raw payload data is not persisted. */
    public function recordVerified(string $provider,string $eventId,string $eventType,?string $transactionReference,array $payload): string
    {
        if(!in_array($provider,['iyzico','paytr'],true)||$eventId===''||$eventType==='')throw new \InvalidArgumentException('Ödeme bildirimi kimliği geçersiz.');
        try{$encoded=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);}
        catch(JsonException $e){throw new \InvalidArgumentException('Ödeme bildirimi özeti oluşturulamadı.',0,$e);}
        $hash=hash('sha256',$encoded);
        $stmt=$this->pdo->prepare("INSERT IGNORE INTO payment_webhook_events (provider,provider_event_id,event_type,transaction_reference,payload_hash,processing_status,received_at) VALUES(?,?,?,?,?,'received',NOW())");
        $stmt->execute([$provider,$eventId,$eventType,$transactionReference,$hash]);
        if($stmt->rowCount()===1)return 'recorded';
        $check=$this->pdo->prepare('SELECT payload_hash FROM payment_webhook_events WHERE provider=? AND provider_event_id=? LIMIT 1');
        $check->execute([$provider,$eventId]);$existing=$check->fetchColumn();
        if($existing===false)throw new \RuntimeException('Ödeme bildirimi kaydı doğrulanamadı.');
        return hash_equals((string)$existing,$hash)?'duplicate':'conflict';
    }

    /** Atomically claim an event; only one worker can process it at a time. */
    public function claim(string $provider,string $eventId): bool
    {
        $stmt=$this->pdo->prepare("UPDATE payment_webhook_events SET processing_status='processing' WHERE provider=? AND provider_event_id=? AND processing_status IN ('received','failed')");
        $stmt->execute([$provider,$eventId]);return $stmt->rowCount()===1;
    }

    public function markProcessed(string $provider,string $eventId): bool
    {
        $stmt=$this->pdo->prepare("UPDATE payment_webhook_events SET processing_status='processed',processed_at=NOW(),error_message=NULL WHERE provider=? AND provider_event_id=? AND processing_status='processing'");
        $stmt->execute([$provider,$eventId]);return $stmt->rowCount()===1;
    }

    public function markFailed(string $provider,string $eventId,string $reason): bool
    {
        $stmt=$this->pdo->prepare("UPDATE payment_webhook_events SET processing_status='failed',error_message=? WHERE provider=? AND provider_event_id=? AND processing_status='processing'");
        $stmt->execute([substr($reason,0,500),$provider,$eventId]);return $stmt->rowCount()===1;
    }
}
