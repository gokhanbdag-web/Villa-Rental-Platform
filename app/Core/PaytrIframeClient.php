<?php
declare(strict_types=1);

namespace App\\Core;

/** Native PHP PayTR iFrame API client; no SDK or Composer dependency. */
final class PaytrIframeClient
{
    private const TOKEN_URL = 'https://www.paytr.com/odeme/api/get-token';

    public static function requestToken(array $config, array $order): array
    {
        $credentials=$config['credentials']??[];
        $merchantId=trim((string)($credentials['merchant_id']??''));
        $merchantKey=(string)($credentials['merchant_key']??'');
        $merchantSalt=(string)($credentials['merchant_salt']??'');
        if($merchantId===''||$merchantKey===''||$merchantSalt==='') throw new \\RuntimeException('PayTR mağaza bilgileri eksik.');
        foreach(['user_ip','merchant_oid','email','payment_amount','user_basket','user_name','user_address','user_phone','merchant_ok_url','merchant_fail_url'] as $field) {
            if(!isset($order[$field])||!is_scalar($order[$field])||(string)$order[$field]==='') throw new \\InvalidArgumentException('PayTR ödeme alanı eksik: '.$field);
        }
        if(strlen((string)$order['merchant_oid'])>64||strlen((string)$order['email'])>100||strlen((string)$order['user_ip'])>39) throw new \\InvalidArgumentException('PayTR ödeme alanı uzunluğu geçersiz.');
        $currency=strtoupper((string)($order['currency']??'TL'));
        if(!in_array($currency,['TL','TRY','USD','EUR','GBP','RUB'],true)) throw new \\InvalidArgumentException('PayTR para birimi desteklenmiyor.');
        $testMode=($config['environment']??'sandbox')==='production'?'0':'1';
        $fields=array_merge($order,[
            'merchant_id'=>$merchantId,'currency'=>$currency,'test_mode'=>$testMode,
            'no_installment'=>'1','max_installment'=>'0','timeout_limit'=>'30','debug_on'=>'0','lang'=>'tr',
        ]);
        $hash=(string)$fields['merchant_id'].(string)$fields['user_ip'].(string)$fields['merchant_oid']
            .(string)$fields['email'].(string)$fields['payment_amount'].(string)$fields['user_basket']
            .$fields['no_installment'].$fields['max_installment'].$currency.$testMode.$merchantSalt;
        $fields['paytr_token']=base64_encode(hash_hmac('sha256',$hash,$merchantKey,true));
        $body=http_build_query($fields,'','&',PHP_QUERY_RFC3986);
        if(function_exists('curl_init')) {
            $ch=curl_init(self::TOKEN_URL);
            curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$body,CURLOPT_RETURNTRANSFER=>true,
                CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded'],CURLOPT_CONNECTTIMEOUT=>12,
                CURLOPT_TIMEOUT=>25,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
            $raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);
            if(!is_string($raw)) throw new \\RuntimeException('PayTR bağlantı hatası: '.($error?:'Yanıt alınamadı.'));
        } else {
            $ctx=stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Type: application/x-www-form-urlencoded\\r\\n",'content'=>$body,'timeout'=>25,'ignore_errors'=>true]]);
            $raw=@file_get_contents(self::TOKEN_URL,false,$ctx);$status=0;
            foreach(($http_response_header??[]) as $header) if(preg_match('/^HTTP\\/\\S+\\s+(\\d+)/',$header,$m))$status=(int)$m[1];
            if(!is_string($raw)) throw new \\RuntimeException('PayTR bağlantısı kurulamadı.');
        }
        $result=json_decode($raw,true);
        if(!is_array($result)||$status<200||$status>=300||($result['status']??'')!=='success'
            ||!preg_match('/^[A-Za-z0-9_-]{16,512}$/',(string)($result['token']??''))) {
            $reason=trim((string)($result['reason']??''));
            throw new \\RuntimeException('PayTR ödeme formu oluşturulamadı.'.($reason!==''?' '.$reason:''));
        }
        return ['token'=>(string)$result['token'],'test_mode'=>$testMode];
    }
}
