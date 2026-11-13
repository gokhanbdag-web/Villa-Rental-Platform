<?php
declare(strict_types=1);
namespace App\Core;
final class PaymentSandboxSimulator
{
    /** Local-only simulation. This class has no HTTP client and cannot contact a provider. */
    public static function run(string $provider,string $scenario): array
    {
        if(!in_array($provider,['iyzico','paytr'],true)||!in_array($scenario,['success','failure','tampered'],true))throw new \InvalidArgumentException('Geçersiz test seçimi.');
        $secret='LOCAL-ONLY-TEST-SECRET-NOT-A-REAL-CREDENTIAL';
        if($provider==='paytr'){
            $p=['merchant_oid'=>'VILLA-LOCAL-TEST-0001','status'=>$scenario==='failure'?'failed':'success','total_amount'=>'125000'];
            $message=$p['merchant_oid'].'LOCAL-TEST-SALT'.$p['status'].$p['total_amount'];
            $p['hash']=base64_encode(hash_hmac('sha256',$message,'LOCAL-TEST-MERCHANT-KEY',true));
            if($scenario==='tampered')$p['total_amount']='1';
            $valid=PaymentSignatureVerifier::verifyPaytr($p,'LOCAL-TEST-MERCHANT-KEY','LOCAL-TEST-SALT');
            $success=$p['status']==='success';
        }else{
            $p=['iyziEventType'=>'CHECKOUT_FORM_AUTH','iyziPaymentId'=>'LOCAL-TEST-PAYMENT-0001','token'=>'LOCAL-TEST-TOKEN','paymentConversationId'=>'VILLA-LOCAL-TEST-0002','status'=>$scenario==='failure'?'FAILURE':'SUCCESS'];
            $message=$secret.$p['iyziEventType'].$p['iyziPaymentId'].$p['token'].$p['paymentConversationId'].$p['status'];
            $signature=hash_hmac('sha256',$message,$secret);
            if($scenario==='tampered')$p['paymentConversationId']='VILLA-TAMPERED';
            $valid=PaymentSignatureVerifier::verifyIyzicoV3($p,$signature,$secret);
            $success=$p['status']==='SUCCESS';
        }
        return ['provider'=>$provider,'scenario'=>$scenario,'signature_valid'=>$valid,'payment_success'=>$valid&&$success,'message'=>!$valid?'İmza doğrulaması reddedildi.':($success?'Test bildirimi doğrulandı; tahsilat yapılmadı.':'İmza geçerli, ancak ödeme sonucu başarısız.'),'network_requests'=>0,'real_charge'=>false];
    }
}
