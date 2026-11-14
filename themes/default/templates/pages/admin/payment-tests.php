<?php
declare(strict_types=1);
$result = isset($simulationResult) && is_array($simulationResult) ? $simulationResult : null;
$provider = (string)($result['provider'] ?? 'paytr');
$scenario = (string)($result['scenario'] ?? 'success');
?>
<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Ödeme test merkezi</title><style>
*{box-sizing:border-box}body{margin:0;background:#f5f7f6;color:#26352d;font-family:system-ui,-apple-system,sans-serif}.wrap{max-width:900px;margin:28px auto;padding:0 16px}h1{font-size:26px}.muted{color:#718077;font-size:13px;line-height:1.6}.panel{background:#fff;border:1px solid #e4ebe5;border-radius:13px;padding:20px;margin:16px 0}label{display:block;font-weight:700;font-size:13px;margin:13px 0}select{display:block;width:100%;padding:12px;border:1px solid #dce5de;border-radius:8px;margin-top:7px;background:white;font:inherit}button,.back{display:inline-block;background:#176b50;color:#fff;border:0;border-radius:8px;padding:12px 17px;font-weight:700;text-decoration:none;cursor:pointer}.warning{padding:13px;background:#fff5e4;color:#76551c;border:1px solid #f0dfb6;border-radius:9px;font-size:13px}.result{padding:15px;border-radius:9px;background:#eef6f0;margin-top:16px}.result.fail{background:#fff0ed;color:#8b3325}.result strong{display:block;margin-bottom:6px}.meta{font-size:13px;line-height:1.9}.meta code{overflow-wrap:anywhere}.pill{display:inline-block;border-radius:20px;padding:3px 9px;background:#e7f2ea;color:#176b50;font-size:11px;font-weight:800}.grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 18px;margin-top:12px}.grid div{padding:8px 0;border-bottom:1px solid #e7ece8}@media(max-width:600px){.panel{padding:15px}.wrap{margin:18px auto}.grid{grid-template-columns:1fr}}
</style></head><body><main class="wrap"><a class="back" href="/admin/finance">← Finans ekranına dön</a><h1>Ödeme test merkezi</h1><p class="muted">PayTR için mağaza hesabı gerektirmeyen yerel protokol simülasyonu; iyzico için ayrı yerel imza testi.</p>
<div class="warning"><strong>Yerel test güvenliği:</strong> Bu ekran gerçek PayTR veya iyzico sunucularına bağlanmaz. Kart bilgisi almaz, rezervasyon oluşturmaz/değiştirmez ve tahsilat yapmaz. Test anahtarları yalnızca bu simülatör içinde üretilir; yönetim panelindeki gerçek API anahtarları kullanılmaz.</div>
<section class="panel"><h2>Simülasyon çalıştır</h2><form method="post" action="/admin/payment-tests"><?= csrf_field() ?>
<label>Sağlayıcı<select name="provider"><option value="paytr" <?= $provider==='paytr'?'selected':'' ?>>PayTR – Yerel iFrame simülatörü</option><option value="iyzico" <?= $provider==='iyzico'?'selected':'' ?>>iyzico – Yerel callback imza testi</option></select></label>
<label>Senaryo<select name="scenario">
<option value="success" <?= $scenario==='success'?'selected':'' ?>>Başarılı ödeme bildirimi</option>
<option value="failure" <?= $scenario==='failure'?'selected':'' ?>>Başarısız ödeme bildirimi</option>
<option value="tampered" <?= $scenario==='tampered'?'selected':'' ?>>İmzası değiştirilmiş bildirim</option>
<option value="amount_mismatch" <?= $scenario==='amount_mismatch'?'selected':'' ?>>Tutar uyuşmazlığı</option>
<option value="wrong_order" <?= $scenario==='wrong_order'?'selected':'' ?>>Sipariş numarası uyuşmazlığı</option>
</select></label><button type="submit">Testi çalıştır</button></form>
<?php if($result): $failed=!empty($result['error'])||empty($result['callback_accepted']); ?>
<div class="result <?= $failed?'fail':'' ?>"><strong><?= e($result['message']??$result['error']??'Test tamamlandı') ?></strong><span class="pill"><?= !empty($result['callback_accepted'])?'BİLDİRİM KABUL':'BİLDİRİM REDDEDİLDİ' ?></span>
<div class="grid">
<div>İstek imzası <b><?= !empty($result['request_signature_valid'])?'Geçerli':'Geçersiz' ?></b></div>
<div>Callback imzası <b><?= !empty($result['signature_valid'])?'Geçerli':'Geçersiz' ?></b></div>
<div>Tutar eşleşmesi <b><?= !array_key_exists('amount_matches',$result)||$result['amount_matches']===null?'Uygulanmadı':(!empty($result['amount_matches'])?'Evet':'Hayır') ?></b></div>
<div>Sipariş eşleşmesi <b><?= !array_key_exists('order_matches',$result)||$result['order_matches']===null?'Uygulanmadı':(!empty($result['order_matches'])?'Evet':'Hayır') ?></b></div>
<div>Ödeme başarılı <b><?= !empty($result['payment_success'])?'Evet (simülasyon)':'Hayır' ?></b></div>
<div>Rezervasyon değişti <b><?= !empty($result['booking_changed'])?'Evet':'Hayır' ?></b></div>
<div>Harici API isteği <b><?= (int)($result['network_requests']??0) ?></b></div>
<div>Gerçek tahsilat <b><?= !empty($result['real_charge'])?'Evet':'Hayır' ?></b></div>
<?php if(isset($result['merchant_oid'])): ?><div>Yerel sipariş no <code><?= e($result['merchant_oid']) ?></code></div><div>Beklenen tutar <b><?= e($result['expected_amount']) ?> kuruş</b></div><div>Callback tutarı <b><?= e($result['callback_amount']) ?> kuruş</b></div><?php endif; ?>
</div></div><?php endif; ?>
</section>
<section class="panel"><h2>Gerçek Sandbox bağlantıları</h2><p class="muted">Bu simülatör gerçek ödeme bağlantısı değildir. iyzico Sandbox anahtarları yönetim ayarlarında kayıtlıysa gerçek Checkout Form entegrasyonu ayrıca kullanılabilir. PayTR gerçek iFrame testi için PayTR tarafından verilmiş mağaza bilgileri gerekir.</p></section>
</main></body></html>
