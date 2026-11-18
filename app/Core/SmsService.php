<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;
use RuntimeException;

final class SmsService
{
    public static function config(?PDO $pdo=null):array
    {
        $pdo??=Database::connection();
        $keys=['sms.enabled','sms.provider','sms.netgsm.username','sms.netgsm.password','sms.netgsm.sender','sms.netgsm.encoding','sms.netgsm.iys_filter'];
        $marks=implode(',',array_fill(0,count($keys),'?'));
        $stmt=$pdo->prepare("SELECT setting_key,setting_value FROM settings WHERE setting_key IN ($marks)");
        $stmt->execute($keys);$stored=$stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $password=(string)($stored['sms.netgsm.password']??'');
        if($password!=='')$password=PaymentSecretVault::decrypt($password);
        $username=trim((string)($stored['sms.netgsm.username']??''));
        $sender=strtoupper(trim((string)($stored['sms.netgsm.sender']??'')));
        $provider=(string)($stored['sms.provider']??'netgsm');
        return [
            'enabled'=>(string)($stored['sms.enabled']??'0')==='1',
            'provider'=>$provider,
            'configured'=>$provider==='netgsm'&&$username!==''&&$password!==''&&preg_match('/^[A-Z0-9]{3,11}$/',$sender)===1,
            'sender'=>$sender,
            'encoding'=>(string)($stored['sms.netgsm.encoding']??'TR'),
            'iys_filter'=>(string)($stored['sms.netgsm.iys_filter']??'0'),
            'credentials'=>['username'=>$username,'password'=>$password],
        ];
    }

    public static function cronToken(?PDO $pdo=null):string
    {
        $pdo??=Database::connection();
        $stmt=$pdo->prepare("SELECT setting_value FROM settings WHERE setting_key='sms.cron_token' LIMIT 1");
        $stmt->execute();$stored=(string)($stmt->fetchColumn()?:'');
        return $stored===''?'':PaymentSecretVault::decrypt($stored);
    }

    public static function enqueue(PDO $pdo,string $phone,string $message,?string $eventKey=null):bool
    {
        $config=self::config($pdo);
        if(!$config['enabled']||!$config['configured'])return false;
        $number=NetgsmApiClient::normalizePhone($phone);
        $message=trim($message);
        if($message===''||mb_strlen($message)>912)throw new RuntimeException('SMS metni 1-912 karakter arasında olmalıdır.');
        if($eventKey!==null&&($eventKey===''||strlen($eventKey)>190))throw new RuntimeException('SMS olay anahtarı geçersiz.');
        $stmt=$pdo->prepare("INSERT IGNORE INTO sms_queue(recipient_phone,message_body,event_key,status,attempts,available_at,created_at) VALUES(?,?,?,'pending',0,NOW(),NOW())");
        $stmt->execute([$number,$message,$eventKey]);
        return $stmt->rowCount()===1;
    }

    public static function sendTest(PDO $pdo,string $recipient):array
    {
        $config=self::config($pdo);
        if(!$config['enabled']||!$config['configured'])throw new RuntimeException('Önce Netgsm bilgilerini kaydedin ve SMS gönderimini etkinleştirin.');
        return NetgsmApiClient::send($config,$recipient,Translator::get('sms.test.message'));
    }

    public static function processPending(PDO $pdo,int $limit=10):array
    {
        $limit=max(1,min(25,$limit));$config=self::config($pdo);
        if(!$config['enabled']||!$config['configured'])return ['processed'=>0,'sent'=>0,'failed'=>0,'skipped'=>'disabled_or_unconfigured'];
        $pdo->exec("UPDATE sms_queue SET status='pending',locked_at=NULL WHERE status='sending' AND locked_at<DATE_SUB(NOW(),INTERVAL 10 MINUTE)");
        $stmt=$pdo->prepare("SELECT id,recipient_phone,message_body,attempts FROM sms_queue WHERE status='pending' AND available_at<=NOW() ORDER BY id ASC LIMIT {$limit}");
        $stmt->execute();$items=$stmt->fetchAll(PDO::FETCH_ASSOC);$result=['processed'=>0,'sent'=>0,'failed'=>0];
        foreach($items as $item){
            $claim=$pdo->prepare("UPDATE sms_queue SET status='sending',locked_at=NOW(),updated_at=NOW() WHERE id=? AND status='pending'");
            $claim->execute([(int)$item['id']]);if($claim->rowCount()!==1)continue;$result['processed']++;
            try{
                $response=NetgsmApiClient::send($config,(string)$item['recipient_phone'],(string)$item['message_body']);
                $done=$pdo->prepare("UPDATE sms_queue SET status='sent',provider_job_id=?,sent_at=NOW(),locked_at=NULL,last_error=NULL,updated_at=NOW() WHERE id=? AND status='sending'");
                $done->execute([(string)$response['job_id'],(int)$item['id']]);$result['sent']++;
            }catch(Throwable $e){
                $attempts=(int)$item['attempts']+1;$status=$attempts>=5?'failed':'pending';$delay=min(120,2**$attempts*2);
                $error=mb_substr(preg_replace('/[\r\n]+/',' ',$e->getMessage())??'SMS delivery failed',0,450);
                $fail=$pdo->prepare("UPDATE sms_queue SET status=?,attempts=?,available_at=DATE_ADD(NOW(),INTERVAL {$delay} MINUTE),locked_at=NULL,last_error=?,updated_at=NOW() WHERE id=? AND status='sending'");
                $fail->execute([$status,$attempts,$error,(int)$item['id']]);$result['failed']++;
            }
        }
        return $result;
    }
}
