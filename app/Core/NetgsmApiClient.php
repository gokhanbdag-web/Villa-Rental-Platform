<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class NetgsmApiClient
{
    private const ENDPOINT='https://api.netgsm.com.tr/sms/rest/v2/send';

    public static function normalizePhone(string $phone): string
    {
        $raw=trim($phone);
        $digits=preg_replace('/\D+/','',$raw)??'';
        if(str_starts_with($raw,'+')&&strlen($digits)>10)$digits='00'.$digits;
        elseif(str_starts_with($digits,'90')&&strlen($digits)===12)$digits=substr($digits,2);
        elseif(str_starts_with($digits,'0')&&strlen($digits)===11)$digits=substr($digits,1);
        if(preg_match('/^[1-9][0-9]{9}$/',$digits)===1)return $digits;
        if(preg_match('/^00[1-9][0-9]{7,13}$/',$digits)===1)return $digits;
        throw new RuntimeException('SMS alıcı telefon numarası geçersiz.');
    }

    public static function send(array $config,string $phone,string $message):array
    {
        $credentials=(array)($config['credentials']??[]);
        $username=trim((string)($credentials['username']??''));
        $password=(string)($credentials['password']??'');
        $sender=strtoupper(trim((string)($config['sender']??'')));
        $encoding=(string)($config['encoding']??'TR');
        $iys=(string)($config['iys_filter']??'0');
        if($username===''||$password===''||preg_match('/^[A-Z0-9]{3,11}$/',$sender)!==1)throw new RuntimeException('Netgsm kullanıcı adı, parola veya gönderici başlığı eksik.');
        if(!in_array($encoding,['TR','UTF-8','UNICODE'],true)||!in_array($iys,['0','11','12'],true))throw new RuntimeException('Netgsm SMS ayarları geçersiz.');
        $message=trim($message);
        if($message===''||mb_strlen($message)>912||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u',$message))throw new RuntimeException('SMS metni boş, geçersiz veya 912 karakter sınırını aşıyor.');
        $number=self::normalizePhone($phone);
        if(!function_exists('curl_init'))throw new RuntimeException('Netgsm bağlantısı için PHP cURL eklentisi gereklidir.');
        $payload=[
            'msgheader'=>$sender,
            'messages'=>[['msg'=>$message,'no'=>$number]],
            'encoding'=>$encoding,
            'iysfilter'=>$iys,
            'appname'=>'VillaCMS',
            'referansID'=>'V'.strtoupper(bin2hex(random_bytes(12))),
        ];
        $body=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $ch=curl_init(self::ENDPOINT);
        if($ch===false)throw new RuntimeException('Netgsm bağlantısı başlatılamadı.');
        curl_setopt_array($ch,[
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>$body,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_CONNECTTIMEOUT=>10,
            CURLOPT_TIMEOUT=>30,
            CURLOPT_FOLLOWLOCATION=>false,
            CURLOPT_SSL_VERIFYPEER=>true,
            CURLOPT_SSL_VERIFYHOST=>2,
            CURLOPT_HTTPAUTH=>CURLAUTH_BASIC,
            CURLOPT_USERPWD=>$username.':'.$password,
            CURLOPT_HTTPHEADER=>['Accept: application/json','Content-Type: application/json; charset=utf-8'],
        ]);
        $response=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);
        if(!is_string($response)||$http!==200)throw new RuntimeException('Netgsm SMS servisine bağlanılamadı (HTTP '.$http.'). '.substr($error,0,120));
        $decoded=json_decode($response,true);
        if(!is_array($decoded)||($decoded['code']??'')!=='00'){
            $code=is_array($decoded)?trim((string)($decoded['code']??'')):'';
            $description=is_array($decoded)?trim((string)($decoded['description']??'')):'';
            throw new RuntimeException('Netgsm SMS gönderimi reddedildi'.($code!==''?' ('.$code.')':'').($description!==''?': '.mb_substr($description,0,180):'.'));
        }
        $jobId=trim((string)($decoded['jobid']??''));
        if($jobId==='')throw new RuntimeException('Netgsm başarılı yanıtında görev numarası bulunamadı.');
        return ['job_id'=>$jobId,'provider'=>'netgsm'];
    }
}
