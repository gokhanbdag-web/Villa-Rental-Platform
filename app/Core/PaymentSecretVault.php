<?php
declare(strict_types=1);
namespace App\Core;

final class PaymentSecretVault
{
    private static function key(): string
    {
        $dir=dirname(__DIR__,2).'/storage';
        if(!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new \RuntimeException('Ödeme anahtarı klasörü oluşturulamadı.');
        $path=$dir.'/payment.key';
        if(!is_file($path)){
            $h=@fopen($path,'x+b');
            if($h!==false){try{$key=random_bytes(32);if(fwrite($h,$key)!==32||!fflush($h))throw new \RuntimeException('Ödeme anahtarı yazılamadı.');@chmod($path,0600);}finally{fclose($h);}}
        }
        if(!is_file($path)||!is_readable($path))throw new \RuntimeException('Ödeme anahtarı okunamıyor.');
        $key=file_get_contents($path);
        if($key===false||strlen($key)!==32)throw new \RuntimeException('Ödeme anahtarı geçersiz.');
        return $key;
    }
    public static function encrypt(string $plain): string
    {
        if($plain==='')return '';
        if(!function_exists('openssl_encrypt'))throw new \RuntimeException('Ödeme bilgilerini korumak için OpenSSL gereklidir.');
        $nonce=random_bytes(12);$tag='';
        $cipher=openssl_encrypt($plain,'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,$nonce,$tag);
        if($cipher===false||strlen($tag)!==16)throw new \RuntimeException('Ödeme bilgisi şifrelenemedi.');
        return 'enc:v1:'.base64_encode($nonce.$tag.$cipher);
    }
    public static function decrypt(string $stored): string
    {
        if(!str_starts_with($stored,'enc:v1:'))throw new \RuntimeException('Şifrelenmemiş ödeme bilgisi reddedildi.');
        $payload=base64_decode(substr($stored,7),true);
        if($payload===false||strlen($payload)<29)throw new \RuntimeException('Şifreli ödeme bilgisi bozuk.');
        $plain=openssl_decrypt(substr($payload,28),'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,substr($payload,0,12),substr($payload,12,16));
        if($plain===false)throw new \RuntimeException('Ödeme bilgisi çözülemedi; anahtar dosyasını kontrol edin.');
        return $plain;
    }
}
