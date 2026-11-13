<?php declare(strict_types=1);
$githubConnected = !empty($githubUpdate['connected']);
$githubVersion = trim((string)($githubUpdate['version'] ?? ''));
$githubCommit = (string)($githubUpdate['commit'] ?? '');
$deployedCommit = (string)($githubUpdate['deployed_commit'] ?? '');
$updateAvailable = !empty($githubUpdate['available']);
$deploymentStatus = (string)($githubUpdate['deployment_status'] ?? 'unknown');
$deploymentBackup = (string)($githubUpdate['deployment_backup'] ?? '');
$releaseNotes = trim((string)($githubUpdate['release_notes'] ?? ''));
$changeSummary = $releaseNotes !== '' ? $releaseNotes : (string)($githubUpdate['commit_message'] ?? '');
$commitDate = !empty($githubUpdate['commit_date']) ? date('d.m.Y H:i', strtotime((string)$githubUpdate['commit_date'])) : '—';
?><!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Güncelleme Merkezi</title><style>
.vu-wrap{max-width:1180px;margin:0 auto;color:#26332b}.vu-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:22px}.vu-head h1{font-size:27px;margin:0 0 7px}.vu-head p,.vu-muted{color:#77827c;font-size:13px;line-height:1.6}.vu-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:13px;margin-bottom:18px}.vu-card,.vu-panel{background:#fff;border:1px solid #e7ece8;border-radius:13px;padding:19px;box-shadow:0 3px 14px rgba(25,45,35,.035)}.vu-label{display:block;color:#7b867f;font-size:12px}.vu-value{display:block;font-size:22px;font-weight:800;margin:11px 0 5px;overflow-wrap:anywhere}.vu-panel{margin:14px 0}.vu-panel h2{font-size:16px;margin:0 0 13px}.vu-row{display:flex;justify-content:space-between;gap:15px;padding:12px 0;border-bottom:1px solid #edf0ed;font-size:13px}.vu-row:last-child{border-bottom:0}.vu-pill{display:inline-block;padding:5px 9px;border-radius:30px;background:#eef5f0;color:#286b4b;font-size:11px;font-weight:750}.vu-pill.warn{background:#fff4df;color:#895d12}.vu-pill.muted{background:#f0f2f1;color:#66716a}.vu-note{padding:13px 15px;background:#f3f7f4;border:1px solid #e3ece5;border-radius:10px;color:#53665a;font-size:12px;line-height:1.65}.vu-log{font:12px/1.6 ui-monospace,monospace;overflow-wrap:anywhere;color:#526158}.vu-empty{padding:16px;background:#fafbf9;border-radius:9px;color:#78847c;font-size:13px;line-height:1.6}.vu-pre{white-space:pre-wrap;overflow-wrap:anywhere;font-size:13px;line-height:1.7;color:#526158}.vu-link{color:#286b4b;text-decoration:none;font-weight:700}.vu-link:hover{text-decoration:underline}@media(max-width:900px){.vu-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.vu-grid{gap:8px}.vu-card,.vu-panel{padding:13px}.vu-value{font-size:18px}.vu-head h1{font-size:22px}.vu-row{flex-direction:column;gap:5px}}
</style></head><body><main class="vu-wrap">
<header class="vu-head"><div><h1>Güncelleme Merkezi</h1><p>GitHub sürümünü, kurulu uygulamayı, veritabanı şemasını ve sunucu dağıtım kayıtlarını izleyin.</p></div><span class="vu-pill <?= $updateAvailable ? 'warn' : ($githubConnected ? '' : 'muted') ?>"><?= $updateAvailable ? 'Güncelleme mevcut' : ($githubConnected ? 'GitHub bağlantısı aktif' : 'GitHub erişilemiyor') ?></span></header>
<section class="vu-grid">
<article class="vu-card"><span class="vu-label">Kurulu uygulama sürümü</span><strong class="vu-value"><?= e($installedVersion) ?></strong><span class="vu-muted">Sunucudaki VERSION dosyası</span></article>
<article class="vu-card"><span class="vu-label">GitHub sürümü</span><strong class="vu-value"><?= $githubVersion !== '' ? e($githubVersion) : 'Kontrol edilemedi' ?></strong><span class="vu-muted"><?= $githubConnected ? 'main dalından okundu' : 'Sunucunun GitHub erişimini kontrol edin' ?></span></article>
<article class="vu-card"><span class="vu-label">Veritabanı migration</span><strong class="vu-value"><?= (int)$appliedMigrations ?> / <?= (int)$migrationTotal ?></strong><span class="vu-muted"><?= $migrationError !== '' ? e($migrationError) : 'Uygulanmış / mevcut migration' ?></span></article>
<article class="vu-card"><span class="vu-label">Dağıtım günlüğü</span><strong class="vu-value" style="font-size:18px"><?= $logReadable ? 'Erişilebilir' : 'Bağlı değil' ?></strong><span class="vu-muted"><?= $logReadable ? 'Sunucu günlüğü okunabildi' : 'Sunucu günlük yolu henüz tanımlı değil' ?></span></article>
</section>
<section class="vu-panel"><h2>GitHub güncelleme kontrolü</h2>
<?php if(!$githubConnected): ?><div class="vu-empty">GitHub deposuna şu anda erişilemiyor. Depo özel olduğu için sunucuda VILLA_GITHUB_TOKEN ortam değişkeniyle yalnızca okuma yetkili bir GitHub token tanımlanmalı; ayrıca dış HTTPS erişimi ve PHP cURL / allow_url_fopen ayarları açık olmalıdır. Token sayfada gösterilmez ve depoya yazma yetkisi gerekmez. Bu nedenle bağlantı kurulana kadar güncelleme durumu tahmin edilmiyor.</div>
<?php else: ?>
<div class="vu-row"><span>En son GitHub commit</span><b><?= e(substr($githubCommit,0,12)) ?></b></div>
<div class="vu-row"><span>Commit tarihi</span><b><?= e($commitDate) ?></b></div>
<div class="vu-row"><span>Sunucuya dağıtılan commit</span><b><?= $deployedCommit !== '' ? e(substr($deployedCommit,0,12)) : 'Henüz kayıtlı değil' ?></b></div>
<div class="vu-row"><span>Durum</span><b><?= $updateAvailable ? 'Dağıtılmamış yeni commit var' : ($deployedCommit !== '' ? 'Sunucu GitHub ile eşleşiyor' : 'Dağıtım commit kaydı bekleniyor') ?></b></div>
<div class="vu-row"><span>Son dağıtım yedeği</span><b><?= $deploymentBackup !== '' && $deploymentBackup !== '-' ? e($deploymentBackup) : 'Henüz kayıtlı değil' ?></b></div>
<?php if(!empty($githubUpdate['commit_url'])): ?><p><a class="vu-link" href="<?= e($githubUpdate['commit_url']) ?>" target="_blank" rel="noopener noreferrer">GitHub commit ayrıntısını aç</a></p><?php endif; ?>
<?php endif; ?>
<p class="vu-muted">Son kontrol: <?= e((string)($githubUpdate['checked_at'] ?? '—')) ?>. Sonuçlar sunucu yükünü azaltmak için 5 dakika önbelleğe alınır.</p></section>
<section class="vu-panel"><h2>Son sürüm notları</h2>
<?php if($changeSummary !== ''): ?><div class="vu-pre"><?= e($changeSummary) ?></div><?php if(!empty($githubUpdate['release_url'])): ?><p><a class="vu-link" href="<?= e($githubUpdate['release_url']) ?>" target="_blank" rel="noopener noreferrer">GitHub sürüm sayfasını aç</a></p><?php endif; ?>
<?php else: ?><div class="vu-empty">Henüz GitHub Release yayımlanmamış. Şu anda son commit mesajı gösterilir; ayrıntılı sürüm notları için GitHub'da Release yayımlanmalıdır.</div><?php endif; ?></section>
<section class="vu-panel"><h2>Dağıtım geçmişi</h2>
<?php if(!$logReadable): ?><div class="vu-empty">Sunucu dağıtım günlüğü henüz bu panele bağlanmadı. Günlük dosyasının gerçek yolu sunucu otomasyonunda VILLA_DEPLOY_LOG olarak tanımlandığında burada listelenir. Günlük dosyası web üzerinden herkese açılmaz.</div>
<?php elseif(!$logLines): ?><div class="vu-empty">Günlük okunabildi ancak tanınan dağıtım kaydı bulunamadı.</div>
<?php else: ?><?php foreach($logLines as $line): ?><div class="vu-row vu-log"><?= e($line) ?></div><?php endforeach; ?><?php endif; ?>
<?php if($lastSuccess): ?><p class="vu-muted">Son başarılı dağıtım kaydı: <?= e($lastSuccess) ?></p><?php endif; ?>
<?php if(!empty($deploymentHistory)): ?><h2 style="margin-top:18px">Yapılandırılmış dağıtım geçmişi</h2>
<?php foreach($deploymentHistory as $event): ?>
<div class="vu-row"><span><b><?= e((string)($event['status']??'unknown')) ?></b> · <?= e((string)($event['updated_at']??'')) ?><br><span class="vu-muted"><?= e((string)($event['message']??'')) ?></span></span><span class="vu-log"><?= e(substr((string)($event['commit']??''),0,12)) ?><?= !empty($event['backup']) ? ' · Yedek: '.e((string)$event['backup']) : '' ?></span></div>
<?php endforeach; ?>
<?php else: ?><div class="vu-empty">Yapılandırılmış dağıtım geçmişi henüz oluşmadı. Sunucu betiği scripts/deploy-status.php aracını çağırmaya başladığında burada listelenir.</div><?php endif; ?>
</section>
<section class="vu-panel"><h2>Dağıtım ve geri alma güvenliği</h2>
<div class="vu-row"><span>Sunucu otomasyon durumu</span><b><?= e($deploymentStatus==='unknown'?'Henüz durum bildirimi yok':$deploymentStatus) ?></b></div>
<div class="vu-note">GitHub main dalına gönderilen imzalı commit, sunucudaki webhook tarafından dağıtım yöneticisine aktarılır. Yönetici dağıtım kilidini alır, canlı dosyaları yedekler, PHP kontrollerini çalıştırır ve aktarım sonrasında HTTP sağlık kontrolü yapar. Dağıtım veya sağlık kontrolü başarısız olursa yeni oluşturulan dosya yedeği geri yüklenir ve eski sürüm yeniden kontrol edilir. Sonuç, commit ve yedek arşivi bu panelde listelenir. Config, storage ve kullanıcı yüklemeleri normal dosya aktarımında korunur. Veritabanı şema değişiklikleri dosya yedeğiyle geri alınmaz; migration işlemleri ayrıca güvenli ve geriye uyumlu tasarlanmalıdır.</div>
</section></main></body></html>