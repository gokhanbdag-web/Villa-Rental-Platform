# iyzico Sandbox ödeme testi

Bu rehber yalnızca Sandbox ortamı içindir. Sandbox ortamında gerçek kartlarla ödeme yapılamaz; yalnızca iyzico test kartları kullanılmalıdır.

## 1. Yönetim paneli

1. `/admin/settings` sayfasını açın.
2. iyzico ödemelerini etkinleştirin.
3. Ortamı **Sandbox / Test** olarak seçin.
4. Sandbox API Key ve Secret Key alanlarının kayıtlı olduğunu doğrulayın.
5. Ayarları kaydedin.

API anahtarlarını destek taleplerine, ekran görüntülerine veya sohbet mesajlarına eklemeyin.

## 2. Webhook

Yönetim panelindeki iyzico Webhook V3 adresini iyzico hesabınızın webhook ayarına ekleyin. Hesabınızda V3 imza özelliği etkin olmalıdır. Bildirimler sunucuda imza doğrulamasından ve Checkout Form Retrieve API kontrolünden geçirilir.

## 3. Başarılı ödeme testi

1. Müşteri hesabıyla bir villa rezervasyonu oluşturun.
2. Ödeme ekranında aşağıdaki Sandbox test kartını kullanın:
   - Kart numarası: `5526080000000006`
   - Son kullanma tarihi: Bugünden ileri bir tarih
   - CVV: Kartın gerektirdiği biçimde geçerli test değeri
3. Ödeme tamamlandığında iyzico dönüşü ve Retrieve API sonucu doğrulanır.
4. Ödeme onaylanırsa ödeme kaydı `paid`, rezervasyon `confirmed` olmalıdır.

## 4. Başarısız ödeme testi

Ayrı bir bekleyen rezervasyonda aşağıdaki hata kartını kullanın:

- Kart numarası: `4111111111111129`
- Beklenen sonuç: Yetersiz bakiye / başarısız ödeme

Beklenen durumda ödeme kaydı `failed` kalır, rezervasyon otomatik onaylanmaz ve müşteri yeniden ödeme deneyebilir.

## 5. Kontrol listesi

- [ ] Başarılı Sandbox ödemesi rezervasyonu onaylıyor.
- [ ] Başarısız ödeme rezervasyonu onaylamıyor.
- [ ] Aynı webhook tekrar gönderildiğinde ikinci kez işlem yapılmıyor.
- [ ] İmzası geçersiz webhook reddediliyor.
- [ ] Tutar, para birimi, token, sepet ve conversation referansları eşleşmeden ödeme onaylanmıyor.
- [ ] Gerçek kart bilgileri uygulama sunucusunda tutulmuyor.

Kart numaraları ve Sandbox kuralları iyzico'nun resmi test kartları dokümantasyonundan alınmıştır: https://docs.iyzico.com/ek-bilgiler/test-kartlari
