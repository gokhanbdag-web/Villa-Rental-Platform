# PayTR gerçek test ortamı kabul prosedürü

Bu belge **yerel simülasyondan farklı** olarak gerçek PayTR test modunda sağlayıcıyla iletişim kurularak yürütülecek kabul testini tanımlar. Bu testler yalnızca yetkili operatör tarafından, etkin PayTR mağazası ve HTTPS üzerinden erişilebilen callback adresi ile yapılabilir.

1. Yönetim panelinde PayTR'yi etkinleştirin, ortamı **Sandbox / Test** seçin; mağaza kimliği, anahtarı ve salt değerini güvenli ayarlara kaydedin. Anahtarları hiçbir log, ekran görüntüsü, test çıktısı veya GitHub kaydına eklemeyin.
2. Her senaryo için ayrı müşteri rezervasyonu oluşturun; tutarı ve para birimini not edin. Ödeme sayfasında PayTR ile ödemeyi başlatın. İstek içinde `test_mode=1` gönderildiğini sağlayıcı panelinden doğrulayın. Uygulama test modunda da gerçek PayTR token servisine bağlanır.
3. PayTR tarafından izin verilen **test kartı/test yöntemi** ile başarılı işlem gerçekleştirin. Sağlayıcının sunucudan gönderdiği callback sonrasında `payment_transactions` kaydının başarılı, rezervasyonun onaylanmış olduğunu ve tutar/para biriminin eşleştiğini kontrol edin.
4. Yeni rezervasyonda başarısız test ödemesi yapın; rezervasyonun onaylanmadığını doğrulayın.
5. Aynı callback'i sağlayıcının güvenli test/yeniden gönderim mekanizmasıyla tekrarlayın; ikinci ödeme veya ikinci durum geçişi olmadığını doğrulayın. Geçersiz imza ve tutar uyuşmazlığı senaryolarını yalnızca yetkili izole test ortamında çalıştırın.
6. Her iki sağlayıcı için de test edilen rezervasyon kodu, sağlayıcı işlem referansı, zaman damgası ve sonuçları gizli bilgileri çıkartarak kayıt altına alın. Gerçek sağlayıcı işlem kanıtı olmadan kabul maddesini **tamamlandı** olarak işaretlemeyin.

**Beklenen sonuç:** PayTR ve iyzico için ayrı ayrı başarılı/başarısız gerçek sandbox ödeme, imza ve tekrar bildirim kontrolü. Yerel `PaymentSandboxSimulator` veya GitHub Actions testleri bu doğrulamanın yerine geçmez.
