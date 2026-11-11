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
    "property.php": "templates/property.php"
  }
}
```

- `slug` küçük harf, rakam, tire ve alt çizgi içermeli; benzersiz olmalıdır.
- `style.css` zorunludur. Diğer statik dosyalar `assets/` içine konur.
- Şablonlar isteğe bağlıdır. Bir sayfa için şablon pakette yoksa sistem mevcut `views/site/` şablonunu kullanır.
- Tema şablonları mevcut sayfa değişkenlerini kullanır: örneğin `$content`, `$navigation`, `$properties`, `$property`, `$user`, `$locale`. Yeni veri sorgularını tema dosyalarına değil uygulama rotalarına/ortak iş mantığına ekleyin.
- Statik dosyalar `/theme-assets/{slug}/{filename}` adresinden sunulur.
- Tema ZIP dosyası en fazla 12 MB ve 150 dosya olabilir. Desteklenen dosyalar: `theme.json`, `style.css`, `templates/*.php` ve `assets/` altındaki CSS/JS/görsel/font dosyaları.
- Yönetim paneli ZIP yükleme özelliği için sunucuda PHP `ZipArchive` eklentisi etkin olmalıdır. Etkin değilse hosting sağlayıcısından açılmasını isteyin.

## Güvenlik

PHP tema şablonları uygulama kullanıcısının yetkileriyle çalışır. Yalnızca kaynağına güvendiğiniz tema paketlerini yükleyin. Tema paketleri veritabanı tablolarını değiştirmemeli ve kullanıcı/rezervasyon verilerini silmemelidir.
