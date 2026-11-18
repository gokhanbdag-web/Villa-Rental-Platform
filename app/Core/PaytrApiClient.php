<?php
declare(strict_types=1);
namespace App\Core;
use RuntimeException;

final class PaytrApiClient
{
    private const TOKEN_ENDPOINT='https://www.paytr.com/odeme/api/get-token';

    public static function getIframeToken(array $config,array $payload):array
    {
        $credentials=(array)($config['credentials']??[]);
        $merchantId=trim((string)($credentials['merchant_id']??''));
        $key=(string)($credentials['merchant_key']??'');
        $salt=(string)($credentials['merchant_salt']??'');
        if($merchantId===''||$key===''||$salt==='')throw new RuntimeException('PayTR mağaza bilgileri eksik.');
        foreach(['user_ip','merchant_oid','email','payment_amount','user_basket','no_installment','max_installment','currency','test_mode'] as $field){
            if(!isset($payload[$field])||!is_scalar($payload[$field])||(string)$payload[$field]==='')throw new RuntimeException('PayTR ödeme isteği alanı eksik: '.$field);
        }
        if(!preg_match('/^[A-Za-z0-9]{1,64}$/',(string)$payload['merchant_oid']))throw new RuntimeException('PayTR sipariş numarası geçersiz.');
        if(!preg_match('/^[0-9]+$/',(string)$payload['payment_amount'])||(int)$payload['payment_amount']<1)throw new RuntimeException('PayTR ödeme tutarı geçersiz.');
        if(strlen((string)$payload['email'])>100||preg_match('/^[\\x20-\\x7E]+$/D',(string)$payload['email'])!==1||!filter_var((string)$payload['email'],FILTER_VALIDATE_EMAIL))throw new RuntimeException('PayTR e-posta adresi geçersiz.');
        $currency=strtoupper((string)$payload['currency']);
        if(!in_array($currency,['TL','USD','EUR','GBP'],true))throw new RuntimeException('PayTR para birimi desteklenmiyor.');
        $payload['merchant_id']=$merchantId;
        $message=$merchantId.(string)$payload['user_ip'].(string)$payload['merchant_oid'].(string)$payload['email'].
            (string)$payload['payment_amount'].(string)$payload['user_basket'].(string)$payload['no_installment'].
            (string)$payload['max_installment'].$currency.(string)$payload['test_mode'].$salt;
        $payload['paytr_token']=base64_encode(hash_hmac('sha256',$message,$key,true));
        if(!PaymentSignatureVerifier::verifyPaytrIframeToken($payload,$key,$salt))throw new RuntimeException('PayTR istek imzası doğrulanamadı.');
        if(!function_exists('curl_init'))throw new RuntimeException('PayTR bağlantısı için PHP cURL eklentisi gereklidir.');
        $ch=curl_init(self::TOKEN_ENDPOINT);
        if($ch===false)throw new RuntimeException('PayTR bağlantısı başlatılamadı.');
        curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($payload,'','&',PHP_QUERY_RFC3986),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>30,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_HTTPHEADER=>['Accept: application/json']]);
        $body=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);
        if(!is_string($body)||$http!==200)throw new RuntimeException('PayTR token servisine bağlanılamadı (HTTP '.$http.'). '.substr($error,0,120));
        $response=json_decode($body,true);
        if(!is_array($response)||($response['status']??'')!=='success'){$reason=is_array($response)?trim((string)($response['reason']??'')):'';throw new RuntimeException('PayTR oturumu oluşturulamadı: '.mb_substr($reason?:'Sağlayıcı yanıtı geçersiz.',0,160));}
        $token=trim((string)($response['token']??''));
        if($token===''||strlen($token)>2048||preg_match('/^[A-Za-z0-9._-]+$/',$token)!==1)throw new RuntimeException('PayTR ödeme belirteci geçersiz.');
        return ['token'=>$token,'iframe_url'=>'https://www.paytr.com/odeme/guvenli/'.rawurlencode($token)];
    }
}
