# VillaStay tema paketi geliştirme

Tema sistemi Composer veya SSH gerektirmez. Her tema `themes/{slug}/` altında bağımsız bir pakettir.

## Paket yapısı

ZIP dosyasının kökünde şu yapı bulunmalıdır:

```text
theme.json
style.css
templates/
  landing.php
  listing.php
  property.php
  destinations.php
  booking.php
  page.php
assets/
  logo.svg
  hero.webp
```

`theme.json` örneği:

```json
{
  "slug": "my-resort",
  "name": "My Resort",
  "version": "1.0.0",
  "author": "Theme Studio",
  "description": "Açıklama",
  "templates": {
    "landing.php": "templates/landing.php",
    "listing.php": "templates/listing.php",
    "property.php": "templates/property.php",
    "destinations.php": "templates/destinations.php",
    "booking.php": "templates/booking.php",
    "page.php": "templates/page.php"
  }
}
```

- `slug` küçük harf, rakam, tire ve alt çizgi içermeli; benzersiz olmalıdır.
- `style.css` zorunludur. Diğer statik dosyalar `assets/` içine konur.
- `landing.php`, `listing.php`, `property.php`, `destinations.php`, `booking.php` ve `page.php` için ayrı şablonlar ekleyebilirsiniz. Şablonlar mevcut sayfa görünümünü tamamen değiştirebilir; ortak uygulama verisini ve işlemlerini korumalıdır. Bir sayfa için şablon pakette yoksa sistem mevcut `views/site/` şablonunu kullanır.
- Tema şablonları HTML iskeletini, başlığı, navigasyonu, arama formu yerleşimini, kartları ve mobil menüyü kendi tasarımına göre oluşturabilir. Giriş/çıkış bağlantıları ve alt menüler için ortak `views/site/partials/navigation-items.php` parçasını kullanmanız önerilir.
- Tema şablonları mevcut sayfa değişkenlerini kullanır: örneğin `$content`, `$navigation`, `$properties`, `$property`, `$user`, `$locale`. Yeni veri sorgularını tema dosyalarına değil uygulama rotalarına/ortak iş mantığına ekleyin.
- Statik dosyalar `/theme-assets/{slug}/{filename}` adresinden sunulur. Dosyaları `assets/` altında tutun ve CSS/şablonlarda bu yolu kullanın.
- Tema ZIP dosyası en fazla 12 MB ve 150 dosya olabilir. Desteklenen dosyalar: `theme.json`, `style.css`, isteğe bağlı `demo.json`, `templates/*.php` ve `assets/` altındaki CSS/JS/görsel/font dosyaları.
- Yönetim paneli ZIP yükleme özelliği için sunucuda PHP `ZipArchive` eklentisi etkin olmalıdır. Etkin değilse hosting sağlayıcısından açılmasını isteyin.
- Yönetim panelinde her tema için ana renk, vurgu rengi, sayfa zemini, panel rengi, yazı rengi ve kart köşe yuvarlaklığı ayrı ayrı ayarlanabilir. Bu tercihler mevcut `settings` tablosunda tema kimliğine göre saklanır; ek SQL migration gerekmez.
- Tema kartındaki küçük görünüm, seçilen renklerle oluşturulan hızlı tasarım önizlemesidir. **Önizle** düğmesi ise seçilen temayı yönetici oturumunda gerçek site ana sayfasında açar; bu görünüm ziyaretçilere uygulanmaz. Önizleme çubuğundan önizleme kapatılabilir.

## Güvenlik

PHP tema şablonları uygulama kullanıcısının yetkileriyle çalışır. Yalnızca kaynağına güvendiğiniz tema paketlerini yükleyin. Tema paketleri veritabanı tablolarını değiştirmemeli ve kullanıcı/rezervasyon verilerini silmemelidir.


## Yerleşik tasarım koleksiyonu

Sistemle birlikte gelen 10 tema, tema yöneticisinden etkinleştirilebilir ve canlı önizlenebilir:

- **Akdeniz:** zeytin, taş ve kırık beyaz; Akdeniz editoryal stili.
- **Modern:** çağdaş ve minimal.
- **Prestige:** rafine premium.
- **Boutique:** yumuşak butik.
- **Riviera:** sahil evi, kum ve deniz tonları.
- **Alpine:** dağ evi, çam ve bakır.
- **Nocturne:** koyu sinematik lüks.
- **Kyoto:** Japon minimalizmi ve wabi-sabi.
- **Loft:** mimari grid ve elektrik mavisi.
- **Bohem:** terracotta, zeytin ve keten.

Altı yeni tema kendi `theme.json` ve `style.css` dosyalarına sahiptir. Her tema ortak rezervasyon, kullanıcı ve içerik işlemlerini koruyarak kendi görsel dilini uygular. Yerleşik temalar panelden yanlışlıkla silinemez.

## Tema başına demo içeriği

Yerleşik temaların her birinde `demo.json` dosyası bulunur. Paket, ilgili temanın `home`, `hakkimizda`, `iletisim` ve `sikca-sorulan-sorular` sayfaları için ayrı görsel sayfa oluşturucu düzenleri taşır. Demo içeriği Türkçe hazırlanır ve sayfa düzenleri `page_builder_layouts` tablosunda tema kimliğiyle ayrı saklanır.

- Yönetim paneli → Görünüm → Temalar bölümünden **Demo içeriğini yükle** seçilir.
- İçe aktarma mevcut sayfa düzenlerini asla ezmez; dolu olan sayfaları atlar.
- İlanlar, kullanıcılar, ev sahipleri, rezervasyonlar, ödeme kayıtları ve ortak CMS metinleri değiştirilmez.
- **Demo içeriğini güvenle sıfırla** yalnızca içe aktarma sırasında oluşturulan ve sonradan değiştirilmemiş sayfa düzenlerini siler. Yönetici tarafından düzenlenen sayfalar korunur.
- Her tema paketi ZIP içinde isteğe bağlı `demo.json` taşıyabilir. Demo JSON içeriği mevcut PageBuilder widget allowlist ve ayar doğrulamasından geçirilir.
- Demo içeriği, temanın aktif edilmesiyle otomatik yüklenmez; yönetici açıkça tek tıklamayla başlatır.
