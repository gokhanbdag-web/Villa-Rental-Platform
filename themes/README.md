# Default tema ve override mimarisi

Bu projede tek ana tema `themes/default/` klasörüdür. Bütün genel tasarım çalışmaları önce bu klasörde yapılır. Tema seçme, ZIP tema yükleme, demo tema ve Page Builder/widget sistemi bulunmaz.

## Temel yapı

```text
themes/
  default/
    theme.json
    style.css
    templates/
      landing.php
      listing.php
      property.php
      destinations.php
      booking.php
      page.php
      account.php
    partials/
      # Ortak tema parçaları burada tutulur.
```

## Override kuralı

İleride yeni bir tema tasarımı gerektiğinde ana dosyalar kopyalanmaz. Yalnızca değiştirilmesi gereken dosya, aynı göreli yol ve dosya adıyla ilgili tema klasörüne eklenir.

Örnek:

```text
themes/
  default/
    partials/shared-topbar.php
    templates/landing.php
  coastal/
    partials/shared-topbar.php
```

Dosya çözümleyici önce aktif temadaki aynı isimli override dosyasını, bulamazsa `default` altındaki karşılığını kullanır. Bu sayede ortak davranış ve içerik tek merkezde kalır. Override dosyaları yalnızca gerçekten farklı tasarım gerektiğinde oluşturulur.

## Ortak kurallar

- Tema içeriği ve sayfa metinleri yönetim panelinden yönetilen CMS verilerinden gelir; tema dosyalarına müşteri içeriği sabit yazılmaz.
- Rezervasyon, kullanıcı, ilan, ödeme ve CMS iş mantığı tema dosyalarından ayrı tutulur.
- Bütün temalarda sayfa gövdesi beyaz zeminle başlar.
- Ortak üst bilgi alanı tek merkezden yönetilir. Tema farklılığı gerekiyorsa yalnızca tipografi gibi sunum değişkenleri override edilir.
- Yeni override dosyaları güvenli dosya çözümleyicisiyle yüklenmeli; kullanıcıdan gelen dosya yolu doğrudan `require` edilmemelidir.
- Tema varlıkları yalnızca `default` temasından sunulur. Yeni tema altyapısı gerektiğinde bu kural kontrollü biçimde genişletilir.

## Güvenlik ve veri

Tema dosyaları veritabanı şemasını değiştirmez. Kullanıcı, villa, rezervasyon, ödeme ve mevcut CMS verileri tema değişikliklerinden etkilenmez. Veritabanı şema değişiklikleri sürümlü migration dosyalarıyla yapılır.
