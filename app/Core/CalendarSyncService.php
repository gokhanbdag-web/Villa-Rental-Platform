<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;
use Throwable;

final class CalendarSyncService
{
    public static function createImportFeed(PDO $pdo,int $propertyId,string $label,string $url): int
    {
        $target=CalendarFeedFetcher::validateUrl($url);
        $label=trim($label);
        if($propertyId<1||$label===''||strlen($label)>120) throw new \InvalidArgumentException('Takvim adı veya ilan bilgisi geçersiz.');
        $count=$pdo->prepare("SELECT COUNT(*) FROM property_calendar_feeds WHERE property_id=? AND direction='import'");
        $count->execute([$propertyId]);
        if((int)$count->fetchColumn()>=10) throw new RuntimeException('Bir ilana en fazla 10 dış takvim kaynağı eklenebilir.');
        $stmt=$pdo->prepare("INSERT INTO property_calendar_feeds(property_id,label,direction,feed_url_encrypted,enabled,created_at) VALUES(?,?,'import',?,1,NOW())");
        $stmt->execute([$propertyId,$label,PaymentSecretVault::encrypt($target['url'])]);
        return (int)$pdo->lastInsertId();
    }

    public static function createExportFeed(PDO $pdo,int $propertyId,string $label='VillaCMS takvimi'): array
    {
        $label=trim($label);
        if($propertyId<1||$label===''||strlen($label)>120) throw new \InvalidArgumentException('Dışa aktarma takvimi bilgisi geçersiz.');
        $token=bin2hex(random_bytes(32));
        $stmt=$pdo->prepare("INSERT INTO property_calendar_feeds(property_id,label,direction,export_token_hash,enabled,created_at) VALUES(?,?,'export',?,1,NOW())");
        $stmt->execute([$propertyId,$label,hash('sha256',$token)]);
        return ['id'=>(int)$pdo->lastInsertId(),'token'=>$token];
    }

    public static function cronToken(PDO $pdo): string
    {
        $stmt=$pdo->query('SELECT token_encrypted FROM calendar_sync_credentials WHERE singleton_id=1');
        $encrypted=$stmt->fetchColumn();
        if(is_string($encrypted)&&$encrypted!=='') return PaymentSecretVault::decrypt($encrypted);
        return self::rotateCronToken($pdo);
    }

    public static function rotateCronToken(PDO $pdo): string
    {
        $token=bin2hex(random_bytes(32));
        $stmt=$pdo->prepare('INSERT INTO calendar_sync_credentials(singleton_id,token_encrypted,updated_at) VALUES(1,?,NOW()) ON DUPLICATE KEY UPDATE token_encrypted=VALUES(token_encrypted),updated_at=NOW()');
        $stmt->execute([PaymentSecretVault::encrypt($token)]);
        return $token;
    }

    public static function verifyCronToken(PDO $pdo,string $token): bool
    {
        if(strlen($token)<32||strlen($token)>128) return false;
        try { return hash_equals(self::cronToken($pdo),$token); } catch(Throwable) { return false; }
    }

    public static function syncFeed(PDO $pdo,int $feedId): array
    {
        $stmt=$pdo->prepare("SELECT id,property_id,feed_url_encrypted FROM property_calendar_feeds WHERE id=? AND direction='import' AND enabled=1 LIMIT 1");
        $stmt->execute([$feedId]);$feed=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$feed) throw new RuntimeException('Etkin içe aktarma takvimi bulunamadı.');
        try {
            $url=PaymentSecretVault::decrypt((string)$feed['feed_url_encrypted']);
            $calendar=CalendarFeedFetcher::fetch($url);
            $events=IcalParser::parse($calendar);
            $result=self::applyEvents($pdo,$feedId,$events);
            $update=$pdo->prepare("UPDATE property_calendar_feeds SET last_sync_status='success',last_sync_message=?,last_synced_at=NOW(),updated_at=NOW() WHERE id=?");
            $update->execute([count($events).' etkinlik işlendi; '.$result['blocked'].' tarih aralığı engellendi.',$feedId]);
            return $result;
        } catch(Throwable $e) {
            $update=$pdo->prepare("UPDATE property_calendar_feeds SET last_sync_status='error',last_sync_message=?,last_synced_at=NOW(),updated_at=NOW() WHERE id=?");
            $update->execute([mb_substr(preg_replace('/[\r\n]+/',' ',$e->getMessage())??'Takvim hatası',0,450),$feedId]);
            throw $e;
        }
    }

    public static function applyEvents(PDO $pdo,int $feedId,array $events): array
    {
        $feedStmt=$pdo->prepare("SELECT id,property_id FROM property_calendar_feeds WHERE id=? AND direction='import' AND enabled=1 LIMIT 1 FOR UPDATE");
        $pdo->beginTransaction();
        try {
            $feedStmt->execute([$feedId]);$feed=$feedStmt->fetch(PDO::FETCH_ASSOC);
            if(!$feed) throw new RuntimeException('İçe aktarma takvimi bulunamadı.');
            $propertyId=(int)$feed['property_id'];
            $syncStartedAt=date('Y-m-d H:i:s');
            $blocked=0;$cancelled=0;$conflicts=0;
            foreach($events as $event) {
                $uid=trim((string)($event['uid']??''));
                $from=(string)($event['date_from']??'');$to=(string)($event['date_to']??'');
                if($uid===''||strlen($uid)>255||!self::validDateRange($from,$to)) continue;
                $existingStmt=$pdo->prepare('SELECT id,availability_id FROM calendar_feed_events WHERE feed_id=? AND external_uid=? LIMIT 1 FOR UPDATE');
                $existingStmt->execute([$feedId,$uid]);$existing=$existingStmt->fetch(PDO::FETCH_ASSOC);
                if(!empty($event['cancelled'])) {
                    if($existing&&$existing['availability_id']) {
                        $delete=$pdo->prepare('DELETE FROM availability WHERE id=? AND property_id=?');$delete->execute([(int)$existing['availability_id'],$propertyId]);
                    }
                    if($existing) {
                        $update=$pdo->prepare("UPDATE calendar_feed_events SET availability_id=NULL,date_from=?,date_to=?,event_status='cancelled',missed_syncs=0,last_seen_at=?,updated_at=NOW() WHERE id=?");
                        $update->execute([$from,$to,$syncStartedAt,(int)$existing['id']]);
                    } else {
                        $insert=$pdo->prepare("INSERT INTO calendar_feed_events(feed_id,property_id,external_uid,date_from,date_to,event_status,missed_syncs,last_seen_at,created_at) VALUES(?,?,?, ?,?,'cancelled',0,?,NOW())");
                        $insert->execute([$feedId,$propertyId,$uid,$from,$to,$syncStartedAt]);
                    }
                    $cancelled++;continue;
                }

                $booking=$pdo->prepare("SELECT id FROM bookings WHERE property_id=? AND status IN ('confirmed','completed') AND check_in < ? AND check_out > ? LIMIT 1");
                $booking->execute([$propertyId,$to,$from]);
                if($booking->fetchColumn()) {
                    if($existing&&$existing['availability_id']) {
                        $delete=$pdo->prepare('DELETE FROM availability WHERE id=? AND property_id=?');$delete->execute([(int)$existing['availability_id'],$propertyId]);
                    }
                    if($existing) {
                        $update=$pdo->prepare("UPDATE calendar_feed_events SET availability_id=NULL,date_from=?,date_to=?,event_status='conflict',missed_syncs=0,last_seen_at=?,updated_at=NOW() WHERE id=?");
                        $update->execute([$from,$to,$syncStartedAt,(int)$existing['id']]);
                    } else {
                        $insert=$pdo->prepare("INSERT INTO calendar_feed_events(feed_id,property_id,external_uid,date_from,date_to,event_status,missed_syncs,last_seen_at,created_at) VALUES(?,?,?, ?,?,'conflict',0,?,NOW())");
                        $insert->execute([$feedId,$propertyId,$uid,$from,$to,$syncStartedAt]);
                    }
                    $conflicts++;continue;
                }

                if($existing&&$existing['availability_id']) {
                    $updateAvailability=$pdo->prepare("UPDATE availability SET date_from=?,date_to=?,status='blocked',nightly_price=NULL WHERE id=? AND property_id=?");
                    $updateAvailability->execute([$from,$to,(int)$existing['availability_id'],$propertyId]);
                    $availabilityId=(int)$existing['availability_id'];
                } else {
                    $insertAvailability=$pdo->prepare("INSERT INTO availability(property_id,date_from,date_to,status,nightly_price) VALUES(?,?,?,'blocked',NULL)");
                    $insertAvailability->execute([$propertyId,$from,$to]);$availabilityId=(int)$pdo->lastInsertId();
                }
                if($existing) {
                    $update=$pdo->prepare("UPDATE calendar_feed_events SET availability_id=?,date_from=?,date_to=?,event_status='blocked',missed_syncs=0,last_seen_at=?,updated_at=NOW() WHERE id=?");
                    $update->execute([$availabilityId,$from,$to,$syncStartedAt,(int)$existing['id']]);
                } else {
                    $insert=$pdo->prepare("INSERT INTO calendar_feed_events(feed_id,property_id,external_uid,availability_id,date_from,date_to,event_status,missed_syncs,last_seen_at,created_at) VALUES(?,?,?,?,?,?,'blocked',0,?,NOW())");
                    $insert->execute([$feedId,$propertyId,$uid,$availabilityId,$from,$to,$syncStartedAt]);
                }
                $blocked++;
            }

            $stale=$pdo->prepare("UPDATE calendar_feed_events SET missed_syncs=LEAST(missed_syncs+1,255),updated_at=NOW() WHERE feed_id=? AND last_seen_at<? AND event_status NOT IN ('cancelled','conflict','stale')");
            $stale->execute([$feedId,$syncStartedAt]);
            $remove=$pdo->prepare("SELECT id,availability_id FROM calendar_feed_events WHERE feed_id=? AND missed_syncs>=2 AND event_status<>'stale'");
            $remove->execute([$feedId]);
            foreach($remove->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if($row['availability_id']) {$delete=$pdo->prepare('DELETE FROM availability WHERE id=? AND property_id=?');$delete->execute([(int)$row['availability_id'],$propertyId]);}
                $mark=$pdo->prepare("UPDATE calendar_feed_events SET availability_id=NULL,event_status='stale',updated_at=NOW() WHERE id=?");$mark->execute([(int)$row['id']]);
            }
            $pdo->commit();
            return ['blocked'=>$blocked,'cancelled'=>$cancelled,'conflicts'=>$conflicts,'events'=>count($events)];
        } catch(Throwable $e) {
            if($pdo->inTransaction())$pdo->rollBack();
            throw $e;
        }
    }

    public static function cronSync(PDO $pdo): array
    {
        $feeds=$pdo->query("SELECT id FROM property_calendar_feeds WHERE direction='import' AND enabled=1 ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        $success=0;$failed=0;$results=[];
        foreach($feeds as $id) {
            try {$results[]=self::syncFeed($pdo,(int)$id);$success++;}
            catch(Throwable $e){$failed++;$results[]=['feed_id'=>(int)$id,'error'=>mb_substr($e->getMessage(),0,250)];}
        }
        return ['success'=>$success,'failed'=>$failed,'total'=>count($feeds),'results'=>$results];
    }

    public static function deleteFeed(PDO $pdo,int $feedId,int $propertyId): void
    {
        $pdo->beginTransaction();
        try {
            $stmt=$pdo->prepare('SELECT availability_id FROM calendar_feed_events WHERE feed_id=? AND property_id=? FOR UPDATE');
            $stmt->execute([$feedId,$propertyId]);
            foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $availabilityId) {
                if($availabilityId){$delete=$pdo->prepare('DELETE FROM availability WHERE id=? AND property_id=?');$delete->execute([(int)$availabilityId,$propertyId]);}
            }
            $deleteFeed=$pdo->prepare('DELETE FROM property_calendar_feeds WHERE id=? AND property_id=?');
            $deleteFeed->execute([$feedId,$propertyId]);
            $pdo->commit();
        } catch(Throwable $e) {if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }

    public static function exportFeed(PDO $pdo,string $token): ?array
    {
        if(strlen($token)<32||strlen($token)>128)return null;
        $stmt=$pdo->prepare("SELECT id,property_id,label FROM property_calendar_feeds WHERE direction='export' AND enabled=1 AND export_token_hash=? LIMIT 1");
        $stmt->execute([hash('sha256',$token)]);
        $feed=$stmt->fetch(PDO::FETCH_ASSOC);
        return $feed?:null;
    }

    public static function exportIcs(PDO $pdo,int $propertyId): string
    {
        $property=$pdo->prepare('SELECT title FROM properties WHERE id=?');$property->execute([$propertyId]);$title=(string)($property->fetchColumn()?:'Villa');
        $events=[];
        $bookings=$pdo->prepare("SELECT id,check_in,check_out FROM bookings WHERE property_id=? AND status IN ('confirmed','completed') AND check_out>CURDATE()");
        $bookings->execute([$propertyId]);
        foreach($bookings->fetchAll(PDO::FETCH_ASSOC) as $row)$events[]=['uid'=>'booking-'.$row['id'].'@vill acms','from'=>$row['check_in'],'to'=>$row['check_out'],'summary'=>'Reserved'];
        $availability=$pdo->prepare("SELECT a.id,a.date_from,a.date_to FROM availability a LEFT JOIN calendar_feed_events e ON e.availability_id=a.id WHERE a.property_id=? AND a.status='blocked' AND a.date_to>CURDATE() AND e.id IS NULL");
        $availability->execute([$propertyId]);
        foreach($availability->fetchAll(PDO::FETCH_ASSOC) as $row)$events[]=['uid'=>'availability-'.$row['id'].'@vill acms','from'=>$row['date_from'],'to'=>$row['date_to'],'summary'=>'Unavailable'];
        $escape=static function(string $value):string{return str_replace(["\\",";",",","\r","\n"],["\\\\","\\;","\\,",'','\\n'],$value);};
        $lines=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//VillaCMS//Calendar Sync 1.0//TR','CALSCALE:GREGORIAN','METHOD:PUBLISH','X-WR-CALNAME:'.$escape($title)];
        foreach($events as $event) {
            $lines[]='BEGIN:VEVENT';$lines[]='UID:'.$event['uid'];$lines[]='DTSTAMP:'.gmdate('Ymd\THis\Z');
            $lines[]='DTSTART;VALUE=DATE:'.str_replace('-','',$event['from']);$lines[]='DTEND;VALUE=DATE:'.str_replace('-','',$event['to']);
            $lines[]='SUMMARY:'.$escape($event['summary']);$lines[]='END:VEVENT';
        }
        $lines[]='END:VCALENDAR';
        return implode("\r\n",$lines)."\r\n";
    }

    private static function validDateRange(string $from,string $to): bool
    {
        $a=DateTimeImmutable::createFromFormat('!Y-m-d',$from);$b=DateTimeImmutable::createFromFormat('!Y-m-d',$to);
        return $a!==false&&$b!==false&&$a->format('Y-m-d')===$from&&$b->format('Y-m-d')===$to&&$b>$a&&$a>=new DateTimeImmutable('today');
    }
}
