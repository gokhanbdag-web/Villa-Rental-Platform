<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Rezervasyon ve ödeme · <?= e($booking['booking_code']) ?></title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f6f8f5;color:#26352c;font-family:var(--villa-font-body)}.checkout-wrap{width:min(900px,calc(100% - 32px));margin:42px auto}.checkout-card{background:#fff;border:1px solid #e3eae3;border-radius:20px;padding:28px;box-shadow:0 14px 38px #1b30200a}.eyebrow{font-size:13px;font-weight:800;color:#477c59;text-transform:uppercase;letter-spacing:.08em}.checkout-card h1{font-size:clamp(25px,4vw,36px);margin:8px 0 6px}.muted{color:#6d7b70;line-height:1.65}.booking-summary{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:24px 0;padding:18px;background:#f6f8f5;border-radius:14px}.summary-item span{display:block;font-size:12px;color:#6d7b70;margin-bottom:5px}.summary-item strong{font-size:15px}.total{border-top:1px solid #dfe7df;padding-top:16px;margin-top:8px;display:flex;justify-content:space-between;align-items:center;grid-column:1/-1}.total strong{font-size:24px}.notice{padding:15px 17px;border-radius:12px;background:#fff8e8;color:#6e5420;line-height:1.6;margin:14px 0}.notice.success{background:#eaf5ed;color:#176b50}.notice.error{background:#fff0ed;color:#8b3325}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:22px}.button{display:inline-flex;justify-content:center;align-items:center;padding:13px 18px;border:0;border-radius:11px;background:#477c59;color:#fff;text-decoration:none;font-weight:800;cursor:pointer;font:inherit}.button.secondary{background:#eef3ed;color:#314438}.payment-panel{margin-top:22px;padding:20px;border:1px solid #e2eae2;border-radius:14px}.payment-panel h2{margin:0 0 6px;font-size:20px}.fields{display:grid;grid-template-columns:1fr 1fr;gap:12px}.field{display:block;font-size:13px;font-weight:700;margin:10px 0}.field input{display:block;width:100%;padding:12px;margin-top:6px;border:1px solid #dce5de;border-radius:9px;font:inherit}.field.full{grid-column:1/-1}.fine{font-size:12px;color:#718077;line-height:1.6}.pay{width:100%;margin-top:12px;background:#176b50}@media(max-width:560px){.checkout-wrap{margin:22px auto}.checkout-card{padding:20px}.booking-summary{grid-template-columns:1fr}.total{grid-column:1}.fields{grid-template-columns:1fr}.field.full{grid-column:auto}}
</style>
<?php require APP_ROOT . '/views/site/partials/account-theme.php'; ?>
</head>
<body><main class="checkout-wrap"><section class="checkout-card">
<p class="eyebrow">Rezervasyonunuz oluşturuldu</p>
<h1>Ödeme adımına hoş geldiniz</h1>
<p class="muted">Rezervasyonunuz kaydedildi. Ödeme tamamlanana kadar rezervasyonunuz beklemede kalacaktır.</p>
<div class="booking-summary">
<div class="summary-item"><span>Rezervasyon kodu</span><strong><?= e($booking['booking_code']) ?></strong></div>
<div class="summary-item"><span>Konaklama</span><strong><?= e($booking['property_title']) ?></strong></div>
<div class="summary-item"><span>Giriş</span><strong><?= e(date('d.m.Y',strtotime($booking['check_in']))) ?></strong></div>
<div class="summary-item"><span>Çıkış</span><strong><?= e(date('d.m.Y',strtotime($booking['check_out']))) ?></strong></div>
<div class="summary-item"><span>Misafir sayısı</span><strong><?= (int)$booking['guests'] ?></strong></div>
<div class="summary-item"><span>Rezervasyon durumu</span><strong><?= e($booking['status']==='pending'?'Ödeme bekleniyor':$booking['status']) ?></strong></div>
<div class="total"><span>Ödenecek toplam</span><strong><?= e(number_format((float)$booking['total'],2,',','.')) ?> <?= e($booking['currency']) ?></strong></div>
</div>
<?php if(($paymentStatus??'')==='paid'||$booking['status']==='confirmed'): ?>
<div class="notice success"><strong>Ödemeniz doğrulandı.</strong> Rezervasyonunuz onaylandı. Rezervasyon kodunuz: <?= e($booking['booking_code']) ?></div>
<?php elseif(($paymentStatus??'')==='review'): ?>
<div class="notice">Ödemeniz iyzico tarafından ek güvenlik incelemesinde. İnceleme tamamlanmadan rezervasyon onaylanmayacaktır.</div>
<?php elseif(in_array(($paymentStatus??''),['failed','verify_error'],true)): ?>
<div class="notice error">Ödeme tamamlanamadı veya doğrulanamadı. Rezervasyonunuz beklemede kaldı. Bilgilerinizi kontrol ederek yeniden deneyebilirsiniz.</div>
<?php endif; ?>

<?php if(!empty($iyzicoPaymentAvailable)&&$booking['status']==='pending'&&($paymentStatus??'')!=='review'): ?>
<section class="payment-panel">
<h2>iyzico ile güvenli ödeme</h2>
<p class="muted">Kart bilgileriniz bu siteye kaydedilmez. Ödeme, iyzico'nun güvenli ödeme sayfasında tamamlanır.</p>
<form method="post" action="<?= e(app_url('/checkout/'.rawurlencode((string)$booking['booking_code']).'/iyzico/start')) ?>">
<?= csrf_field() ?>
<div class="fields">
<label class="field">T.C. kimlik numarası
<input name="identity_number" inputmode="numeric" autocomplete="off" pattern="[0-9]{11}" minlength="11" maxlength="11" required placeholder="11 haneli numara">
</label>
<label class="field">Şehir
<input name="billing_city" autocomplete="address-level2" maxlength="100" required placeholder="Örn. Antalya">
</label>
<label class="field full">Fatura adresi
<input name="billing_address" autocomplete="street-address" maxlength="500" required placeholder="Mahalle, cadde, sokak ve kapı numarası">
</label>
</div>
<p class="fine">Bu bilgiler iyzico'nun ödeme işlemini gerçekleştirmesi için aktarılır. Kart bilgileri uygulamamızın sunucusundan geçmez.</p>
<button class="button pay" type="submit">iyzico ile <?= e(number_format((float)$booking['total'],2,',','.')) ?> <?= e($booking['currency']) ?> öde</button>
</form>
</section>
<?php else: ?>
<div class="notice">Online ödeme altyapısı şu anda etkin değil. Rezervasyonunuz beklemede kalır; ödeme tamamlanmadan onaylanmaz.</div>
<?php endif; ?>
<div class="actions"><a class="button secondary" href="<?= e(app_url('/account/bookings')) ?>">Rezervasyonlarımı Görüntüle</a><a class="button secondary" href="<?= e(app_url('/villa/'.rawurlencode((string)$booking['property_slug']))) ?>">Villaya dön</a></div>
</section></main></body></html>
