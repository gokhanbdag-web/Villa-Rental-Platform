-- Demo content pages and frontend navigation for first install and upgrades.
INSERT IGNORE INTO cms_pages (language_code,title,slug,content,status,meta_title,meta_description) VALUES
('tr','Hakkımızda','hakkimizda','VillaStay, tatil planlarınızı daha kolay ve güvenli hale getirmek için farklı ihtiyaçlara uygun villa ve özel konaklama seçeneklerini bir araya getirir.

Amacımız; ailelerin, çiftlerin ve arkadaş gruplarının kendilerine uygun konaklama yerini kolayca bulmasını, seçenekleri karşılaştırmasını ve rezervasyon sürecini şeffaf biçimde tamamlamasını sağlamaktır.

Portföyümüzde özel havuzlu villalardan doğa içindeki sakin evlere, deniz manzaralı konaklamalardan kalabalık ailelere uygun geniş seçeneklere kadar farklı alternatifler bulunabilir.

Her konaklama ilanında kapasite, özellikler, konum, fiyat ve rezervasyon koşulları açıkça sunulmaya çalışılır. Sorularınız için İletişim sayfamızdan bize ulaşabilirsiniz.

Not: Bu sayfa örnek demo içeriğidir. Yayına almadan önce işletmenizin gerçek unvanı, adresi ve kurumsal bilgileriyle güncelleyin.','published','Hakkımızda | VillaStay','VillaStay hakkında bilgi edinin; villa kiralama ve konaklama yaklaşımımızı keşfedin.'),
('tr','İletişim','iletisim','Tatil planınız, ilanlarımız veya rezervasyon süreciniz hakkında bize ulaşabilirsiniz.

Müşteri destek ekibimiz, talebinizi inceleyerek size mümkün olan en kısa sürede dönüş yapar.

E-posta: info@example.com
Telefon: +90 000 000 00 00
Çalışma saatleri: Pazartesi – Cumartesi, 09.00 – 18.00

İşletme adresi ve güncel iletişim bilgileri site yönetimi tarafından bu sayfada yayımlanmalıdır.

Not: Yukarıdaki iletişim bilgileri demo amaçlıdır; yayından önce gerçek bilgilerinizle değiştirin.','published','İletişim | VillaStay','VillaStay ekibine ulaşın. Rezervasyon ve konaklama sorularınız için iletişim bilgilerimiz.'),
('tr','Nasıl Çalışır?','nasil-calisir','1. Konaklamanızı keşfedin
Ülke, şehir, ilçe, semt, kişi sayısı ve fiyat aralığı gibi filtrelerle size uygun villaları bulun.

2. İlanı inceleyin
Fotoğrafları, konaklama kapasitesini, yatak odalarını, olanakları, konumu ve ev kurallarını dikkatlice inceleyin.

3. Tarihlerinizi seçin
Giriş ve çıkış tarihlerinizi belirleyerek uygunluk durumunu kontrol edin. İlanın rezervasyon koşullarını okuyun.

4. Rezervasyon talebi gönderin
İstenen bilgileri doldurup talebinizi iletin. Rezervasyonun kesinleşmesi için ekranda belirtilen onay ve ödeme adımlarını takip edin.

5. Tatilinizin keyfini çıkarın
Rezervasyon detaylarınızı hesabınızdan takip edin. Konaklama öncesinde ev sahibi veya destek ekibiyle gerekli iletişimi kurun.

Rezervasyonun kesinleşme yöntemi, ödeme ve iptal koşulları ilgili ilanda belirtilen kurallara tabidir.','published','Nasıl Çalışır? | VillaStay','VillaStay üzerinden villa arama, ilan inceleme ve rezervasyon adımlarını öğrenin.'),
('tr','Sıkça Sorulan Sorular','sikca-sorulan-sorular','Rezervasyonum ne zaman kesinleşir?
Rezervasyon talebinizin durumu, ilgili ilanın rezervasyon yöntemine göre değişir. Onay veya ödeme gerektiren adımları hesabınızdan takip edin.

Fiyatlara temizlik veya ek hizmet ücretleri dahil mi?
İlanın fiyat ve ücret detayları rezervasyon öncesinde gösterilir. Ek ücretleri ödeme adımından önce kontrol edin.

Giriş ve çıkış saatlerini nereden öğrenebilirim?
Saatler ilan açıklamasında veya rezervasyon onayında belirtilir. Özel bir talebiniz varsa ev sahibiyle önceden görüşün.

Rezervasyonumu iptal edebilir miyim?
İptal hakkı ve iade tutarı, rezervasyon sırasında kabul edilen iptal politikasına bağlıdır.

Evcil hayvan kabul ediliyor mu?
Bu bilgi her ilanın olanakları ve ev kuralları bölümünde belirtilir.

Çocuklar için uygun mu?
Maksimum misafir kapasitesini, yatak düzenini ve güvenlik bilgilerini ilan detaylarından kontrol edin.

Ödeme güvenli mi?
Yalnızca platformda sunulan resmi ödeme adımlarını kullanın. Kart veya hesap bilgilerinizi mesaj yoluyla paylaşmayın.

Başka sorularım varsa ne yapmalıyım?
İletişim sayfamızdaki kanallardan destek ekibimize ulaşabilirsiniz.','published','Sıkça Sorulan Sorular | VillaStay','Rezervasyon, ödeme, iptal ve konaklama hakkında sıkça sorulan soruların yanıtları.'),
('tr','Kiralama Koşulları','kiralama-kosullari','Bu sayfa demo kiralama koşullarını içerir. İşletmenin hukuki danışmanı tarafından gözden geçirilmeden bağlayıcı sözleşme olarak kullanılmamalıdır.

Rezervasyon
Misafir, rezervasyon talebi oluşturarak seçtiği konaklama yeri ve tarihler için başvuru yapar. Rezervasyonun kesinleşmesi, ilanda belirtilen onay ve ödeme adımlarının tamamlanmasına bağlıdır.

Misafir bilgileri
Rezervasyonda bildirilen kişi sayısı, konaklama kapasitesini aşmamalıdır. Konaklayacak kişilere ilişkin bilgiler doğru ve eksiksiz verilmelidir.

Konaklama kuralları
Misafirler, ilanda belirtilen ev kurallarına, komşuluk düzenine ve yerel mevzuata uymayı kabul eder. İzin verilmeyen etkinlikler ve kapasite aşımı yapılamaz.

Hasar ve sorumluluk
Konaklama sırasında oluşan hasarlar ve ek masraflar, yürürlükteki mevzuat ve rezervasyon koşulları çerçevesinde değerlendirilir.

Giriş ve çıkış
Giriş-çıkış saatleri ilan veya rezervasyon onayında belirtilir. Erken giriş ve geç çıkış talepleri önceden onaylanmalıdır.

Güncel koşullar
Her rezervasyon için rezervasyon anında gösterilen özel koşullar geçerlidir.','published','Kiralama Koşulları | VillaStay','Villa kiralama ve konaklama süreçlerine ilişkin örnek koşullar.'),
('tr','Gizlilik Politikası','gizlilik-politikasi','Bu metin demo gizlilik politikası taslağıdır. Yayına alınmadan önce veri sorumlusunun unvanı, iletişim bilgileri, veri işleme amaçları, hukuki sebepleri, alıcı grupları ve saklama süreleri doğrulanmalıdır.

İşlenen bilgiler
Hesap oluşturma, rezervasyon, destek talebi ve platform güvenliği süreçlerinde kullanıcı tarafından sağlanan kimlik ve iletişim bilgileri ile işlem kayıtları işlenebilir.

İşleme amaçları
Bilgiler; hesap yönetimi, rezervasyonların yürütülmesi, müşteri desteği, güvenlik, yasal yükümlülüklerin yerine getirilmesi ve hizmet kalitesinin geliştirilmesi amaçlarıyla kullanılabilir.

Paylaşım
Bilgiler, rezervasyonun yerine getirilmesi için gerekli olduğu ölçüde ilgili konaklama işletmesi ve hizmet sağlayıcılarla veya kanunen yetkili kurumlarla paylaşılabilir.

Güvenlik ve saklama
Kişisel verilere yetkisiz erişimi önlemek için makul teknik ve idari tedbirler uygulanır. Veriler, ilgili amaç ve yasal saklama süreleriyle sınırlı olarak tutulur.

Başvuru
Kişisel verilerinize ilişkin taleplerinizi, işletmenin doğrulanmış iletişim kanallarından iletebilirsiniz.

Önemli: Bu taslak hukuki danışmanlık değildir; KVKK ve ilgili mevzuata göre işletmenize özel hale getirilmelidir.','published','Gizlilik Politikası | VillaStay','VillaStay gizlilik politikası ve kişisel verilerin işlenmesine ilişkin demo bilgilendirme metni.'),
('tr','İptal ve İade Koşulları','iptal-ve-iade-kosullari','Bu sayfa örnek iptal ve iade bilgilendirmesidir. Kesin koşullar, rezervasyon sırasında kullanıcıya gösterilen ilan politikası ve ödeme sağlayıcısı kurallarına göre belirlenmelidir.

İptal talebi
Misafir, iptal talebini hesabındaki rezervasyon detaylarından veya destek kanallarından iletebilir.

İade tutarı
İade tutarı; iptal zamanı, ilanın seçili iptal politikası, tahsil edilen ücretler ve yürürlükteki mevzuata göre hesaplanır.

İade süresi
Onaylanan iadelerin ödeme aracına yansıma süresi bankaya ve ödeme kuruluşuna göre değişebilir.

Ev sahibi tarafından iptal
Ev sahibi veya işletme kaynaklı iptallerde misafire uygulanacak seçenekler ve iade süreci, rezervasyon koşulları ve mevzuata göre yürütülür.

Destek
İptal veya iade talebinizin durumunu öğrenmek için rezervasyon kodunuzla destek ekibine başvurun.

Yayından önce işletmenizin gerçek iptal politikası, istisnaları ve yasal yükümlülükleri bu sayfaya eklenmelidir.','published','İptal ve İade Koşulları | VillaStay','Rezervasyon iptali ve iade sürecine ilişkin örnek bilgilendirme.'),
('tr','Ev Sahipleri İçin','ev-sahipleri-icin','Villanızı daha fazla misafire ulaştırmak ve rezervasyon taleplerinizi tek panelden yönetmek ister misiniz?

İlanınızı oluşturun
Konaklama başlığı, açıklama, fotoğraflar, konum, kapasite, olanaklar ve gecelik fiyat bilgilerini eksiksiz girin.

Takviminizi güncel tutun
Müsaitlik takvimini ve özel dönem fiyatlarını düzenli olarak güncelleyin. Böylece misafirler doğru bilgiyle rezervasyon talebi oluşturabilir.

Talepleri yönetin
Gelen rezervasyon taleplerini panelinizden görüntüleyin, gerekli durumlarda misafirlerle iletişim kurun ve rezervasyon durumunu takip edin.

Misafir deneyimine önem verin
Temiz, güvenli ve açıklamayla uyumlu bir konaklama sunmak olumlu değerlendirmelere katkı sağlar.

Başvuru
Çoklu ev sahibi modelinde başvuru ve hesap onay süreci platform yöneticisi tarafından yürütülür. Başlamak için iletişim sayfamızdan bize ulaşın.

Bu sayfa demo tanıtım içeriğidir; komisyon ve ödeme koşulları işletme modelinize göre eklenmelidir.','published','Ev Sahipleri İçin | VillaStay','Villa ilanınızı yayınlayın, takviminizi ve rezervasyon taleplerinizi yönetin.'),
('tr','Güvenli Rezervasyon','guvenli-rezervasyon','Rezervasyon yaparken aşağıdaki adımlara dikkat ederek daha güvenli bir deneyim yaşayabilirsiniz.

İlan bilgilerini inceleyin
Fotoğrafları, açıklamayı, konumu, kapasiteyi, olanakları ve ev kurallarını rezervasyon öncesinde okuyun.

Platform dışı ödemelerden kaçının
Size sunulan resmi ödeme akışı dışında banka havalesi veya farklı kanallardan ödeme yapmadan önce platform desteğiyle doğrulama yapın.

Kişisel bilgilerinizi koruyun
Şifre, kart numarası, doğrulama kodu ve benzeri gizli bilgileri mesajla veya telefonla paylaşmayın.

Rezervasyon kayıtlarını saklayın
Rezervasyon kodunuzu, onay mesajlarını ve ödeme belgelerinizi muhafaza edin.

Şüpheli bir durum bildirin
İlan veya iletişim sürecinde şüpheli bir durumla karşılaşırsanız işlemi durdurun ve destek ekibine bildirin.

Bu bilgilendirme genel niteliktedir; platformun gerçek güvenlik ve ödeme prosedürleriyle uyumlu hale getirilmelidir.','published','Güvenli Rezervasyon | VillaStay','VillaStay üzerinden rezervasyon yaparken dikkat edilmesi gereken güvenlik önerileri.'),
('tr','Yardım Merkezi','yardim-merkezi','Villa arama, rezervasyon ve hesap işlemleriyle ilgili yardıma mı ihtiyacınız var?

Rezervasyon desteği
Rezervasyon kodunuzu ve talebinizin kısa açıklamasını ileterek rezervasyon süreciniz hakkında destek alabilirsiniz.

İlan ve konaklama desteği
İlan açıklaması, konum, olanaklar veya giriş-çıkış saatleriyle ilgili sorularınızı ilgili ev sahibine veya destek ekibine yöneltebilirsiniz.

Hesap desteği
Giriş yapma, hesap bilgileri ve şifre yenileme işlemleri için hesap ekranındaki seçenekleri kullanın.

Ev sahibi desteği
İlan yayınlama, fiyat, müsaitlik takvimi ve rezervasyon yönetimi hakkında yardım almak için bizimle iletişime geçin.

İletişim
E-posta: destek@example.com
Telefon: +90 000 000 00 00

Not: İletişim bilgileri demo amaçlıdır. Gerçek destek adreslerinizi eklemeyi unutmayın.','published','Yardım Merkezi | VillaStay','Rezervasyon, hesap ve ev sahibi işlemleri için destek merkezi.');
INSERT IGNORE INTO navigation_items (location,language_code,label,url,target,sort_order,is_active) VALUES
('header','tr','Hakkımızda','/page/hakkimizda','_self',30,1),
('header','tr','Nasıl Çalışır?','/page/nasil-calisir','_self',40,1),
('header','tr','Yardım','/page/yardim-merkezi','_self',50,1),
('footer','tr','Hakkımızda','/page/hakkimizda','_self',10,1),
('footer','tr','İletişim','/page/iletisim','_self',20,1),
('footer','tr','Sıkça Sorulan Sorular','/page/sikca-sorulan-sorular','_self',30,1),
('footer','tr','Kiralama Koşulları','/page/kiralama-kosullari','_self',40,1),
('footer','tr','Gizlilik Politikası','/page/gizlilik-politikasi','_self',50,1),
('footer','tr','İptal ve İade Koşulları','/page/iptal-ve-iade-kosullari','_self',60,1),
('footer','tr','Ev Sahipleri İçin','/page/ev-sahipleri-icin','_self',70,1),
('footer','tr','Güvenli Rezervasyon','/page/guvenli-rezervasyon','_self',80,1),
('footer','tr','Yardım Merkezi','/page/yardim-merkezi','_self',90,1);