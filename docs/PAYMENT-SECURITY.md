# Ödeme altyapısı ve üretime hazırlık notları

Bu belge mevcut kodun durumunu açıklar. Entegrasyonların kodda bulunması, gerçek bir işyeri hesabında üretim onayı alındığı veya canlı tahsilatın test edildiği anlamına gelmez.

## Uygulamadaki entegrasyonlar

- **iyzico Checkout Form:** Yönetim panelinde açıkça seçilen Sandbox veya Production ortamında ödeme oturumu başlatma ve sağlayıcı sonucunu geri alma akışı bulunur. Her işlem başlangıç ortamını saklar; callback/webhook sonucu aynı ortamdan sorgulanır.
- **PayTR iFrame:** Barındırılan ödeme formu için token alma, HMAC imzalama, callback imza doğrulama, sipariş/tutar/para birimi kontrolleri ve idempotent işlem kaydı bulunur.
- **iyzico iadeleri:** Kısmi/tam iade isteği, imzalı yanıt doğrulaması ve finansal kayıt mutabakatı uygulanır. Belirsiz ağ sonucunda ayrılan iade tutarı operatör sağlayıcı panelinden sonucu doğrulayana kadar yeniden gönderilmemelidir.
- **Manuel havale/EFT:** Müşteri transfer bildirimi oluşturabilir. Rezervasyon ancak yetkili yönetici transferi doğrulayıp onayladıktan sonra ödenmiş sayılır. Otomatik banka ekstresi eşleştirmesi veya otomatik banka transferi yapılmaz.
- **Ödeme varsayılanı:** `payments.enabled` varsayılan olarak `0` değerindedir. Sağlayıcı kimlik bilgileri girilmeden ve yöntem yönetim panelinden açıkça etkinleştirilmeden kartlı ödeme başlatılmamalıdır.

## Güvenlik kontrolleri

- Sağlayıcı kimlik bilgileri `PaymentSecretVault` ile AES-256-GCM kullanılarak şifrelenir.
- `PaymentGatewayConfig` kimlik bilgilerini yalnızca sunucu tarafı entegrasyonlarında çözer. Çözülen sırlar HTTP yanıtlarına, hata mesajlarına veya loglara yazılmamalıdır.
- `PaymentSignatureVerifier` PayTR callback HMAC'ini ve iyzico `X-IYZ-SIGNATURE-V3` imzasını doğrular.
- İmza doğrulaması tek başına ödeme onayı değildir. İşlem; sağlayıcı sonucu, sipariş/rezervasyon referansı, tutar, para birimi ve idempotency kontrollerinden geçmelidir.
- Başarılı tarayıcı yönlendirmesi veya kullanıcı tarafından gönderilen bir parametre tek başına rezervasyonu ödenmiş yapmamalıdır.
- Mükerrer callback'ler, gecikmiş callback'ler, iptal edilmiş/süresi dolmuş rezervasyonlar ve aynı tarihlerdeki çakışan rezervasyonlar ayrıca kontrol edilmelidir.

## Canlıya geçiş öncesi zorunlu doğrulamalar

1. iyzico Sandbox ve PayTR test ortamında başarılı, başarısız, iptal edilmiş ve zaman aşımına uğramış ödeme senaryolarını çalıştırın.
2. Yanlış tutar/para birimi, geçersiz imza, tekrar gönderilen callback ve geç gelen başarılı callback senaryolarını doğrulayın.
3. Eş zamanlı rezervasyon ve aynı rezervasyonda iki ödeme sağlayıcısı oturumu açılması senaryolarını test edin.
4. iyzico kısmi/tam iade ve belirsiz ağ sonucu senaryolarını işyeri paneliyle karşılaştırın.
5. Üretim kimlik bilgileri, PayTR Notification URL, iyzico mağaza ayarları ve HTTPS yapılandırmasını gerçek işyeri hesabında doğrulayın.
6. Gerçek tarayıcıda masaüstü/mobil uçtan uca testleri ve bağımsız güvenlik incelemesini tamamlayın.
7. Tüm kontroller tamamlanmadan gerçek müşteri ödemesi kabul etmeyin.

## Operasyonel sınırlar

- PayTR ve iyzico entegrasyonlarının gerçek işyeri hesaplarında sağlayıcı onayı ve canlı uçtan uca testi bu depo CI kontrolleriyle kanıtlanamaz.
- Otomatik iadeler şu anda iyzico ile sınırlıdır. Manuel havale iadeleri platform dışında yapılır ve operatör tarafından kaydedilir.
- Manuel satıcı ödemeleri yönetici tarafından banka transferiyle yapılır; sistem talep, tahsis, referans ve denetim geçmişini tutar.
- Üretim geçişi öncesinde yedek alınmalı, geri yükleme provası yapılmalı ve gerçek hosting üzerinde kurulum/güncelleme testi tamamlanmalıdır.

Resmî referanslar:
- iyzico Webhook V3: https://docs.iyzico.com/en/advanced/webhook
- PayTR iFrame callback: https://dev.paytr.com/en/iframe-api/iframe-api-2-adim
