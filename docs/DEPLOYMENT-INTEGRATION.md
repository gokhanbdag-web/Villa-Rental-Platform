# Sunucu dağıtım entegrasyonu

## Otomatik dağıtım akışı

GitHub `main` dalına gelen push, canlı dizindeki `deploy-hook.php` tarafından HMAC imzası ve depo/branch kontrolünden geçirilir. Hook, CLI üzerinden `scripts/deploy-runner.php` dosyasını arka planda başlatır.

Runner şu işlemleri tek dağıtım kilidi altında yürütür:

1. Dağıtım başlangıcını `storage/deploy-status.json` ve geçmiş dosyasına yazar.
2. Sunucudaki `/opt/villa-deploy/deploy.sh` betiğini çalıştırır.
3. Betiğin oluşturduğu yeni `/opt/villa-deploy/backups/villa-*.tar.gz` yedeğini tespit eder.
4. Dağıtım başarılıysa canlı URL'nin HTTP 2xx yanıtını doğrular ve `success` kaydeder.
5. Betik hata verirse veya sağlık kontrolü başarısız olursa, bu dağıtımda oluşturulan tam yedeği `rsync --delete` ile geri yükler.
6. Geri yüklenen eski sürüm de HTTP 2xx sağlık kontrolünden geçerse `rolled_back`; aksi halde `failed` kaydeder.

Runner kilidi, sağlık kontrolü ve olası geri yükleme tamamlanana kadar ikinci bir webhook dağıtımının başlamasını engeller. Sunucu betiğindeki kendi `deploy.lock` kilidi de korunur.

## Sunucu betiği için gerekli koşullar

Mevcut `/opt/villa-deploy/deploy.sh` betiği aşağıdaki sözleşmeye uymalıdır:

- Başarılı yedekleme, `/opt/villa-deploy/backups/villa-YYYYMMDD-HHMMSS.tar.gz` biçiminde yeni bir arşiv üretmelidir.
- Arşiv canlı dizinin tamamını içermelidir. Böylece kodla birlikte `config/`, `storage/` ve `public/uploads/` da geri yüklenebilir.
- Arşiv oluşturulduktan sonra betik hata verirse sıfırdan farklı çıkış kodu döndürmelidir.
- Betik `success` durumunu kendisi yazmamalıdır; bu kararı runner yalnızca canlı sağlık kontrolünden sonra verir.

Mevcut sunucu betiği bu arşiv biçimini ve tam canlı dizin yedeğini zaten üretmektedir. Bu nedenle sunucudaki `deploy.sh` dosyasını her uygulama güncellemesinde değiştirmeniz gerekmez.

## Kalıcı verilerin korunması

Normal dağıtım sırasında `config/`, `storage/` ve `public/uploads/` rsync dışında tutulur. Otomatik rollback sırasında ise dağıtım öncesi alınan tam arşiv geri yüklenir. Veritabanı dosya yedeğinin parçası değildir; veritabanı migration işlemleri ayrıca ve kontrollü yönetilmelidir. Otomatik rollback veritabanını geriye sarmaya çalışmaz.

## Güncelleme Merkezi

- `storage/deploy-status.json`: son durum ve commit SHA
- `storage/deploy-history.jsonl`: dağıtım olay geçmişi
- `VILLA_DEPLOY_LOG`: sunucu dağıtım günlüğü (opsiyonel)

Durum yazıcısı yalnızca CLI üzerinden çalışır. Durum ve geçmiş dosyaları web üzerinden yayımlanmamalıdır.

## GitHub erişimi

Depo özeldir. Yönetim panelindeki GitHub sürüm kontrolü için sunucu PHP sürecine `VILLA_GITHUB_TOKEN` ortam değişkeni tanımlanmalıdır. Token yalnızca bu depoda **Contents: Read-only** iznine sahip olmalıdır. Token kaynak koda, veritabanına veya web üzerinden erişilebilir dosyalara yazılmamalıdır.
