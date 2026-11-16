# Ödeme altyapısı güvenlik notları

Uygulama PayTR iFrame için sunucudan token ister ve iyzico Checkout Form'u Sandbox ortamında başlatabilir. PayTR Production yalnızca yönetici tarafından açıkça etkinleştirilip canlı mağaza bilgileri girildiğinde çalışır. Gerçek tahsilat, yalnızca sağlayıcının barındırdığı ödeme formunda müşteri işlemi tamamladığında oluşur.

- Sağlayıcı kimlik bilgileri `PaymentSecretVault` ile AES-256-GCM kullanılarak şifrelenir.
- `PaymentGatewayConfig` yalnızca güvenilir sunucu tarafı kodunun kullanması için kimlik bilgilerini çözer. Çıktısı HTTP yanıtlarına veya loglara yazılmamalıdır.
- `PaymentSignatureVerifier` PayTR callback HMAC'ini ve iyzico `X-IYZ-SIGNATURE-V3` imzasını doğrular. PayTR callback ayrıca rezervasyon, sipariş referansı, tutar ve para birimiyle eşleştirilir.
- İmza doğrulaması tek başına ödemeyi onaylamaz. Gerçek callback işleyicisi; sağlayıcıya ait işlem sonucunu, rezervasyon referansını, tutarı ve para birimini eşleştirmeli, ardından `payment_webhook_events` benzersiz anahtarı ve işlem kilidi ile mükerrer bildirimleri etkisiz kılmalıdır.
- Ödeme aktif bayrağı yeni kurulumda `0` kalır. PayTR Production seçimi tek başına ödeme açmaz; PayTR'nin etkinleştirilmesi ve geçerli canlı mağaza bilgilerinin girilmesi gerekir. iyzico Production akışı bu aşamada kapalıdır.

## Entegrasyon sırası

1. Yerel imza doğrulama testleri.
2. Sağlayıcı sandbox testleri ve callback doğrulaması.
3. Rezervasyon/ödeme durum eşleştirmesi ve mükerrer callback testleri.
4. Yönetici kontrollü canlı geçişi; kullanıcı onayı ve gerçek sağlayıcı hesabı ile en son yapılır.

Resmî referanslar:
- iyzico Webhook V3: https://docs.iyzico.com/en/advanced/webhook
- PayTR iFrame callback: https://dev.paytr.com/en/iframe-api/iframe-api-2-adim
