<?php
declare(strict_types=1);

$financialSummary = isset($financialSummary) && is_array($financialSummary) ? $financialSummary : [];
$payoutSummary = isset($payoutSummary) && is_array($payoutSummary) ? $payoutSummary : [];
$recentFinancials = isset($recentFinancials) && is_array($recentFinancials) ? $recentFinancials : [];
$recentTransactions = isset($recentTransactions) && is_array($recentTransactions) ? $recentTransactions : [];
$recentRefunds = isset($recentRefunds) && is_array($recentRefunds) ? $recentRefunds : [];
$money = static fn($amount, string $currency): string => number_format((float)$amount, 2, ',', '.') . ' ' . e($currency);
$payoutLabels = ['unpaid'=>'Ödeme bekliyor','requested'=>'Ödeme talep edildi','processing'=>'İşleniyor','paid'=>'Ödendi','failed'=>'Başarısız','cancelled'=>'İptal edildi'];
$paymentLabels = ['pending'=>'Bekliyor','paid'=>'Ödendi','failed'=>'Başarısız','cancelled'=>'İptal edildi','refunded'=>'İade edildi'];
$refundLabels = ['processing'=>'İşleniyor','succeeded'=>'Başarılı','failed'=>'Başarısız','unknown'=>'Sonuç belirsiz'];
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Finans ve Ödemeler</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f5f7f6;color:#26352d;font-family:system-ui,-apple-system,sans-serif}
.wrap{max-width:1200px;margin:28px auto;padding:0 18px}.heading{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap}
h1{font-size:27px;margin:0 0 6px}.muted{color:#718077;font-size:13px;line-height:1.6;margin:0}
.notice{margin:18px 0;padding:13px 15px;border:1px solid #f0dfb6;background:#fff9e9;color:#76591f;border-radius:11px;font-size:13px}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px;margin:18px 0}
.card,.panel{background:#fff;border:1px solid #e5ece7;border-radius:13px;padding:17px;min-width:0}
.card small{display:block;color:#718077;font-size:12px;font-weight:700}.card strong{display:block;font-size:22px;margin-top:9px;overflow-wrap:anywhere}
.panel{margin:14px 0}.panel h2{font-size:16px;margin:0 0 13px}.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;min-width:680px}th,td{text-align:left;padding:11px 9px;border-bottom:1px solid #edf1ee;font-size:12px;white-space:nowrap}
th{color:#829087;font-size:10px;text-transform:uppercase;letter-spacing:.5px}td strong{display:block;color:#34443a}td small{display:block;color:#89958e;margin-top:3px}
.pill{display:inline-block;padding:5px 8px;border-radius:20px;background:#f0f3f1;color:#58665e;font-size:10px;font-weight:800}
.empty{padding:20px;text-align:center;color:#829087;font-size:13px}
@media(max-width:760px){.grid{grid-template-columns:1fr}.wrap{margin:18px auto;padding:0 12px}.card{padding:14px}.heading h1{font-size:23px}}
</style>
</head>
<body><main class="wrap">
<header class="heading"><div><h1>Finans ve Ödemeler</h1><p class="muted">Rezervasyon gelirlerini, platform komisyonunu, ev sahibi alacaklarını ve ödeme hareketlerini görüntüleyin.</p></div><a href="/admin/payment-tests" style="display:inline-block;padding:11px 15px;background:#176b50;color:#fff;border-radius:9px;text-decoration:none;font-weight:700;font-size:13px">Ödeme test merkezi</a></header>
<?php if (!$paymentsEnabled || $paymentProvider === 'none'): ?>
<div class="notice"><strong>Online ödeme kapalı.</strong> Bu ekranda mevcut rezervasyon finans kayıtları ve varsa ödeme hareketleri listelenir. Kart tahsilatı yapılmaz; ödeme sağlayıcısı etkinleştirilmemiştir.</div>
<?php endif; ?>
<section class="grid">
<?php if (!$financialSummary): ?><article class="card"><small>Finansal kayıt</small><strong>Henüz kayıt yok</strong></article><?php endif; ?>
<?php foreach ($financialSummary as $row): ?>
<article class="card"><small><?= e($row['currency']) ?> · Brüt rezervasyon tutarı</small><strong><?= $money($row['gross_amount'],$row['currency']) ?></strong><p class="muted"><?= (int)$row['booking_count'] ?> rezervasyon</p></article>
<article class="card"><small><?= e($row['currency']) ?> · Platform komisyonu</small><strong><?= $money($row['platform_fee'],$row['currency']) ?></strong></article>
<article class="card"><small><?= e($row['currency']) ?> · Ev sahibi net tutarı</small><strong><?= $money($row['vendor_net_amount'],$row['currency']) ?></strong></article>
<?php endforeach; ?>
</section>
<section class="panel"><h2>Ev sahibi ödeme talepleri</h2>
<?php if (!empty($adminPayoutMessage)): ?><div class="notice" role="status"><?= e($adminPayoutMessage) ?></div><?php endif; ?>
<?php if (!$recentPayouts): ?><div class="empty">Henüz ödeme talebi oluşturulmadı.</div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Talep</th><th>Ev sahibi</th><th>Banka hesabı</th><th>Tutar</th><th>Durum</th><th>Banka referansı</th><th>İşlem</th></tr></thead><tbody>
<?php foreach ($recentPayouts as $payout): ?><tr><td><strong>#<?= (int)$payout['id'] ?></strong><small><?= e(date('d.m.Y H:i',strtotime($payout['requested_at']))) ?></small></td><td><strong><?= e($payout['business_name']) ?></strong><small><?= e($payout['vendor_email']) ?></small></td><td><strong><?= e($payout['payout_bank_name']??'—') ?></strong><small><?= e($payout['payout_account_holder']??'') ?></small><small><?= e($payout['payout_iban']??'IBAN eksik') ?></small></td><td><?= $money($payout['amount'],$payout['currency']) ?></td><td><span class="pill"><?= e($payoutLabels[$payout['status']]??$payout['status']) ?></span></td><td><?= e($payout['payout_reference']??'—') ?></td><td>
<?php if (in_array($payout['status'],['requested','processing'],true)): ?><form method="post" action="/admin/finance/payout/update" style="display:grid;gap:6px;min-width:190px"><?= csrf_field() ?><input type="hidden" name="payout_id" value="<?= (int)$payout['id'] ?>"><select name="status" required><option value="" selected disabled>Yeni durum seçin</option><option value="processing" <?= $payout['status']==='processing'?'disabled':'' ?>>İşleniyor</option><option value="paid">Ödendi</option><option value="failed">Başarısız</option><option value="cancelled">İptal et</option></select><input name="payout_reference" maxlength="190" placeholder="Banka işlem referansı (ödendiğinde zorunlu)"><input name="notes" maxlength="2000" placeholder="Yönetici notu"><button type="submit" style="border:0;border-radius:7px;background:#176b50;color:white;padding:9px;font-weight:700">Durumu kaydet</button></form><?php else: ?><span class="muted">Tamamlandı</span><?php endif; ?>
</td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
<?php if (!$payoutSummary): ?><div class="empty">Henüz ödeme talebi bulunmuyor.</div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Para birimi</th><th>Durum</th><th>Talep sayısı</th><th>Toplam tutar</th></tr></thead><tbody>
<?php foreach ($payoutSummary as $row): ?><tr><td><?= e($row['currency']) ?></td><td><span class="pill"><?= e($payoutLabels[$row['status']]??$row['status']) ?></span></td><td><?= (int)$row['payout_count'] ?></td><td><strong><?= $money($row['payout_amount'],$row['currency']) ?></strong></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<section class="panel"><h2>Son rezervasyon finans kayıtları</h2>
<?php if (!$recentFinancials): ?><div class="empty">Henüz finans kaydı bulunmuyor.</div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Rezervasyon</th><th>Konaklama</th><th>Brüt</th><th>Komisyon</th><th>Ev sahibi net</th><th>Ödeme durumu</th></tr></thead><tbody>
<?php foreach ($recentFinancials as $row): ?><tr><td><strong><?= e($row['booking_code']) ?></strong><small><?= e(date('d.m.Y',strtotime($row['created_at']))) ?></small></td><td><?= e($row['property_title']) ?></td><td><?= $money($row['gross_amount'],$row['currency']) ?></td><td><?= $money($row['platform_fee'],$row['currency']) ?></td><td><?= $money($row['vendor_net_amount'],$row['currency']) ?></td><td><span class="pill"><?= e($payoutLabels[$row['payout_status']]??$row['payout_status']) ?></span></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<section class="panel"><h2>Ödeme işlem geçmişi ve iadeler</h2><p><a href="/admin/calendar-sync">Takvim senkronizasyonu ve Cron ayarları →</a></p>
<?php if (!empty($adminRefundMessage)): ?><div class="notice" role="status"><?= e($adminRefundMessage) ?></div><?php endif; ?>
<p class="muted">iyzico iade formu gerçek sağlayıcıya para iadesi isteği gönderir. Yalnızca doğruladığınız taleplerde kullanın. Sonucu belirsiz iade kayıtlarını sağlayıcı panelinden kontrol etmeden tekrar göndermeyin.</p>
<?php if (!$recentTransactions): ?><div class="empty">Henüz online ödeme işlemi kaydedilmemiş.</div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Tarih</th><th>Rezervasyon</th><th>Sağlayıcı</th><th>Tutar</th><th>Durum</th><th>Referans</th><th>İade işlemi</th></tr></thead><tbody>
<?php foreach ($recentTransactions as $row): $refundRemaining=max(0,(float)$row['amount']-(float)($row['refunded_amount']??0)); ?><tr><td><?= e(date('d.m.Y H:i',strtotime($row['created_at']))) ?></td><td><strong><?= e($row['booking_code']) ?></strong><small><?= e($row['property_title']) ?></small></td><td><?= e($row['provider']) ?></td><td><?= $money($row['amount'],$row['currency']) ?><small>İade ayrılmış/tamamlanmış: <?= $money($row['refunded_amount']??0,$row['currency']) ?></small></td><td><span class="pill"><?= e($paymentLabels[$row['status']]??$row['status']) ?></span></td><td><?= e($row['provider_reference']??'—') ?></td><td>
<?php if ($row['provider']==='iyzico' && $row['status']==='paid' && $refundRemaining>0 && (int)($row['unresolved_refunds']??0)===0): ?><form method="post" action="/admin/finance/refund/iyzico" style="display:grid;gap:6px;min-width:230px"><?= csrf_field() ?><input type="hidden" name="payment_transaction_id" value="<?= (int)$row['id'] ?>"><label>İade tutarı<input type="number" name="amount" min="0.01" max="<?= number_format($refundRemaining,2,'.','') ?>" step="0.01" value="<?= number_format($refundRemaining,2,'.','') ?>" required style="display:block;width:100%;padding:8px;border:1px solid #dfe6e0;border-radius:7px"></label><input name="reason" maxlength="500" placeholder="İade nedeni (isteğe bağlı)"><label style="white-space:normal"><input type="checkbox" name="confirm_real_refund" value="1" required> Gerçek iyzico iadesi gönderileceğini onaylıyorum.</label><button type="submit" style="border:0;border-radius:7px;background:#9c3e36;color:white;padding:9px;font-weight:700">iyzico iadesi gönder</button></form>
<?php elseif ((int)($row['unresolved_refunds']??0)>0): ?><span class="muted">Sonuç belirsiz/işleniyor. Tekrar göndermeyin; sağlayıcı panelinden kontrol edin.</span><?php elseif ($row['provider']!=='iyzico'): ?><span class="muted">Bu sağlayıcı için otomatik iade desteklenmiyor.</span><?php else: ?><span class="muted">İade edilebilir bakiye yok.</span><?php endif; ?>
</td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<section class="panel"><h2>İade geçmişi</h2><?php if(!$recentRefunds): ?><div class="empty">Henüz iade kaydı bulunmuyor.</div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Tarih</th><th>Rezervasyon</th><th>Tutar</th><th>Durum</th><th>Sağlayıcı referansı</th><th>Not / hata</th></tr></thead><tbody><?php foreach($recentRefunds as $refund): ?><tr><td><?= e(date('d.m.Y H:i',strtotime($refund['created_at']))) ?></td><td><strong><?= e($refund['booking_code']) ?></strong><small><?= e($refund['property_title']) ?></small></td><td><?= $money($refund['amount'],$refund['currency']) ?></td><td><span class="pill"><?= e($refundLabels[$refund['status']]??$refund['status']) ?></span></td><td><?= e($refund['provider_reference']??$refund['conversation_id']) ?></td><td><?= e($refund['error_message']??$refund['reason']??'—') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
</main></body></html>
