# Sunucu dağıtım entegrasyonu

Güncelleme Merkezi, canlı sunucudaki dağıtım bilgisini şu dosyalardan okur:

- `storage/deploy-status.json`: son dağıtımın durumu ve commit SHA bilgisi
- `storage/deploy-history.jsonl`: dağıtım olay geçmişi
- `VILLA_DEPLOY_LOG`: mevcut dağıtım betiğinin metin günlüğü (opsiyonel)

Bu dosyalar web üzerinden erişime kapalı olan `storage/` dizininde tutulur. Durum yazıcısı yalnızca CLI üzerinden çalışır; HTTP isteğiyle çağrılamaz.

## Mevcut deploy betiğine bağlama

Sunucu tarafındaki dağıtım betiği, hedef commit SHA değerini aldıktan sonra aşağıdaki çağrıları yapmalıdır. PHP CLI yolunu sunucuda kurulu PHP binary yoluyla değiştirin.

```sh
PHP_BIN="/usr/local/lsws/lsphp85/bin/php"
STATUS_WRITER="/home/kodva7153/public_html/villa/scripts/deploy-status.php"

"$PHP_BIN" "$STATUS_WRITER" deploying "$TARGET_COMMIT" "$BACKUP_ID" "Dağıtım başladı"

# Dosyalar yedekten korunarak aktarılır ve PHP/health kontrolleri yapılır.

# Tüm kontroller başarılıysa:
"$PHP_BIN" "$STATUS_WRITER" success "$TARGET_COMMIT" "$BACKUP_ID" "Dağıtım ve sağlık kontrolleri başarılı"

# Dağıtım başarısızsa, önce doğrulanmış yedek geri yüklenir.
# Geri yükleme başarılıysa:
"$PHP_BIN" "$STATUS_WRITER" rolled_back "$TARGET_COMMIT" "$BACKUP_ID" "Dağıtım başarısız; önceki sürüm geri yüklendi"

# Geri yükleme de başarısızsa:
"$PHP_BIN" "$STATUS_WRITER" failed "$TARGET_COMMIT" "$BACKUP_ID" "Dağıtım ve geri yükleme başarısız"
```

Betik, durum yazıcısının başarısız dönüş kodunu da kontrol etmelidir. `success` kaydı yalnızca dosya aktarımı, PHP lint ve uygulama sağlık kontrolü tamamlandıktan sonra yazılmalıdır. `rolled_back` yalnızca önceki dosyalar gerçekten geri yüklenip sağlık kontrolü geçtikten sonra yazılmalıdır.

## GitHub erişimi

Depo özeldir. Yönetim panelindeki GitHub sürüm kontrolü için sunucu PHP sürecine `VILLA_GITHUB_TOKEN` ortam değişkeni tanımlanmalıdır. Token yalnızca bu depoda **Contents: Read-only** iznine sahip olmalıdır. Token kaynak koda, veritabanına veya web üzerinden erişilebilir dosyalara yazılmamalıdır.

## Güvenlik ve veri koruma

- `config/`, `storage/`, veritabanı ve kullanıcı yüklemeleri kod senkronizasyonunun dışında tutulmalıdır.
- Geri alma yalnızca mevcut ve doğrulanmış yedekle yapılmalıdır.
- Veritabanı migration işlemleri kod geri almasından bağımsız değerlendirilmelidir; yıkıcı migration otomatik olarak tersine çevrilmemelidir.
- Dağıtım geçmişi ve durum dosyaları web üzerinden yayımlanmamalıdır.

**Not:** Bu belge ve CLI durum yazıcısı depoya eklidir. Sunucunun dışında duran mevcut `/opt/villa-deploy/deploy.sh` dosyasına henüz bağlanmış değildir; entegrasyon yapılmadan panelde gerçek dağıtım/geri alma sonucu görünmez.
