<?php declare(strict_types=1);
$navigation = is_array($navigation ?? null) ? $navigation : [];
?>
<!doctype html>
<html lang="<?= e($locale ?? 'tr') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(villa_ui('Ödeme ve rezervasyon ·')) ?> <?= e($booking['booking_code']) ?></title>
<style>
.checkout-page{background:var(--villa-background,#f7f8f6);color:var(--villa-text,#20332c);min-height:65vh;padding:clamp(28px,5vw,64px) 0 72px}
.checkout-container{width:min(1160px,calc(100% - 40px));margin:0 auto}
.checkout-breadcrumb{display:flex;align-items:center;gap:9px;margin:0 0 22px;color:var(--villa-muted,#718078);font-size:13px}
.checkout-breadcrumb a{color:inherit;text-decoration:none}
.checkout-breadcrumb a:hover{color:var(--villa-primary)}
.checkout-heading{margin-bottom:28px}
.checkout-kicker{display:inline-flex;align-items:center;gap:8px;color:var(--villa-primary);font-size:12px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}
.checkout-kicker:before{content:"";width:22px;height:2px;background:currentColor}
.checkout-heading h1{margin:10px 0 8px;font-size:clamp(28px,4vw,42px);line-height:1.12;letter-spacing:-.045em}
.checkout-heading p{margin:0;max-width:680px;color:var(--villa-muted);line-height:1.7}
.checkout-layout{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(300px,.75fr);gap:24px;align-items:start}
.checkout-main,.checkout-summary{background:var(--villa-surface,#fff);border:1px solid var(--villa-border,#e5ebe6);border-radius:22px;box-shadow:0 14px 38px rgba(24,45,34,.055)}
.checkout-main{padding:clamp(20px,3vw,32px);min-width:0}
.checkout-summary{padding:24px;position:sticky;top:22px}
.checkout-section-title{display:flex;align-items:flex-start;gap:13px;margin:0 0 8px}
.checkout-step{display:grid;place-items:center;flex:0 0 34px;height:34px;border-radius:11px;background:color-mix(in srgb,var(--villa-primary,#176b50) 11%,white);color:var(--villa-primary,#176b50);font-weight:800}
.checkout-section-title h2{margin:1px 0 5px;font-size:20px;letter-spacing:-.025em}
.checkout-description{margin:0 0 22px 47px;color:var(--villa-muted);font-size:14px;line-height:1.7}
.checkout-booking-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:20px;background:var(--villa-background,#f7f8f6);border-radius:16px}
.checkout-data{min-width:0}
.checkout-data span{display:block;margin-bottom:5px;color:var(--villa-muted);font-size:12px}
.checkout-data strong{display:block;overflow-wrap:anywhere;font-size:14px;font-weight:700}
.checkout-total{display:flex;align-items:center;justify-content:space-between;gap:14px;padding-top:18px;margin-top:4px;border-top:1px solid var(--villa-border);grid-column:1/-1}
.checkout-total span{font-weight:700}
.checkout-total strong{font-size:25px;letter-spacing:-.03em;color:var(--villa-primary)}
.checkout-payment{margin-top:28px;padding-top:26px;border-top:1px solid var(--villa-border)}
.checkout-payment .checkout-section-title{margin-bottom:8px}
.checkout-payment-copy{margin:0 0 20px 47px;color:var(--villa-muted);font-size:14px;line-height:1.7}
.checkout-notice{display:flex;gap:11px;align-items:flex-start;margin-top:16px;padding:14px 16px;border-radius:13px;background:color-mix(in srgb,var(--villa-primary,#176b50) 8%,white);color:var(--villa-text);font-size:13px;line-height:1.65}
.checkout-notice strong{display:block;margin-bottom:2px}
.checkout-notice.success{background:#eaf5ed;color:#176b50}
.checkout-notice.error{background:#fff0ed;color:#8b3325}
.checkout-notice-icon{flex:0 0 auto;font-size:17px;line-height:1.35}
.checkout-fields{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.checkout-field{display:block;min-width:0;font-size:13px;font-weight:700}
.checkout-field.full{grid-column:1/-1}
.checkout-field input{display:block;width:100%;height:48px;margin-top:7px;padding:0 13px;border:1px solid var(--villa-border,#dce5de);border-radius:11px;background:var(--villa-surface,#fff);color:var(--villa-text);font:inherit;outline:none}
.checkout-field input:focus{border-color:var(--villa-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--villa-primary) 14%,transparent)}
.checkout-pay-button{display:flex;justify-content:center;align-items:center;width:100%;min-height:52px;margin-top:16px;padding:13px 18px;border:0;border-radius:12px;background:var(--villa-primary,#176b50);color:#fff;font:inherit;font-weight:800;cursor:pointer;transition:filter .2s,transform .2s}
.checkout-pay-button:hover{filter:brightness(.94);transform:translateY(-1px)}
.checkout-pay-button:focus-visible,.checkout-action:focus-visible{outline:3px solid color-mix(in srgb,var(--villa-primary) 35%,transparent);outline-offset:3px}
.checkout-summary-label{margin:0 0 6px;color:var(--villa-muted);font-size:12px;font-weight:800;letter-spacing:.09em;text-transform:uppercase}
.checkout-summary h2{margin:0 0 5px;font-size:21px;line-height:1.3}
.checkout-summary-location{margin:0 0 20px;color:var(--villa-muted);font-size:13px}
.checkout-summary-code{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 13px;border-radius:12px;background:var(--villa-background);font-size:12px}
.checkout-summary-code strong{font-size:13px;letter-spacing:.04em}
.checkout-summary-details{display:grid;gap:15px;margin:20px 0}
.checkout-summary-detail{display:flex;justify-content:space-between;gap:14px;font-size:13px}
.checkout-summary-detail span{color:var(--villa-muted)}
.checkout-summary-detail strong{text-align:right}
.checkout-summary-amount{display:flex;justify-content:space-between;align-items:center;gap:12px;padding-top:18px;border-top:1px solid var(--villa-border)}
.checkout-summary-amount span{font-size:14px;font-weight:700}
.checkout-summary-amount strong{font-size:23px;color:var(--villa-primary);letter-spacing:-.035em}
.checkout-secure{display:flex;align-items:center;gap:9px;margin-top:18px;padding:13px;border-radius:12px;background:var(--villa-background);color:var(--villa-muted);font-size:12px;line-height:1.55}
.checkout-secure svg{flex:0 0 auto;color:var(--villa-primary)}
.checkout-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:24px}
.checkout-action{display:inline-flex;align-items:center;justify-content:center;min-height:43px;padding:10px 15px;border:1px solid var(--villa-border);border-radius:11px;color:var(--villa-text);text-decoration:none;font-size:13px;font-weight:700;transition:background .2s,border-color .2s}
.checkout-action:hover{background:var(--villa-background);border-color:var(--villa-primary)}
@media(max-width:820px){.checkout-layout{grid-template-columns:1fr}.checkout-summary{position:static;grid-row:1}.checkout-summary-details{grid-template-columns:1fr 1fr}}
@media(max-width:560px){.checkout-container{width:calc(100% - 28px)}.checkout-page{padding:24px 0 42px}.checkout-heading{margin-bottom:20px}.checkout-layout{gap:15px}.checkout-main,.checkout-summary{border-radius:17px}.checkout-summary{padding:19px}.checkout-booking-grid{padding:15px;gap:15px 12px}.checkout-total{align-items:flex-start;flex-direction:column;gap:5px}.checkout-fields{grid-template-columns:1fr}.checkout-field.full{grid-column:auto}.checkout-payment-copy{margin-left:0}.checkout-description{margin-left:0}.checkout-form-shell{padding:8px}.checkout-summary-details{grid-template-columns:1fr}.checkout-actions{display:grid;grid-template-columns:1fr 1fr}}
</style>
</head>
<body>
<?php \App\Core\ThemeManager::renderPartial('partials/header.php', get_defined_vars()); ?>
<main class="checkout-page">
<div class="checkout-container">
<nav class="checkout-breadcrumb" aria-label="Sayfa konumu"><a href="<?= e(app_url('/')) ?>"><?= e(villa_ui('Ana sayfa')) ?></a><span>›</span><span><?= e(villa_ui('Güvenli ödeme')) ?></span></nav>
<header class="checkout-heading">
<span class="checkout-kicker"><?= e(villa_ui('Rezervasyonunuz hazır')) ?></span>
<h1><?= e(villa_ui('Konaklamanızı tamamlayın')) ?></h1>
<p><?= e(villa_ui('Rezervasyon bilgilerinizi kontrol edin ve güvenli ödeme adımını tamamlayın. Ödeme tamamlandığında kullanabileceğiniz ödeme yöntemlerini ve rezervasyon durumunuzu burada görüntüleyebilirsiniz.')) ?></p>
</header>
<div class="checkout-layout">
<section class="checkout-main">
<div class="checkout-section-title"><span class="checkout-step">1</span><div><h2><?= e(villa_ui('Rezervasyon bilgileri')) ?></h2></div></div>
<p class="checkout-description"><?= e(villa_ui('Ödeme öncesinde konaklama detaylarınızı ve toplam tutarı kontrol edin.')) ?></p>
<div class="checkout-booking-grid">
<div class="checkout-data"><span><?= e(villa_ui('Konaklama yeri')) ?></span><strong><?= e($booking['property_title']) ?></strong></div>
<div class="checkout-data"><span><?= e(villa_ui('Rezervasyon kodu')) ?></span><strong><?= e($booking['booking_code']) ?></strong></div>
<div class="checkout-data"><span><?= e(villa_ui('Giriş tarihi')) ?></span><strong><?= e(date('d.m.Y',strtotime($booking['check_in']))) ?></strong></div>
<div class="checkout-data"><span><?= e(villa_ui('Çıkış tarihi')) ?></span><strong><?= e(date('d.m.Y',strtotime($booking['check_out']))) ?></strong></div>
<?php if(!empty($booking['early_checkin_requested'])): ?><div class="checkout-data"><span><?= e(villa_ui('Ek hizmet')) ?></span><strong><?= e(villa_ui('Erken giriş ·')) ?> <?= e(number_format((float)$booking['early_checkin_fee'],2,',','.')) ?> <?= e($booking['currency']) ?></strong></div><?php endif; ?>
<?php if(!empty($booking['late_checkout_requested'])): ?><div class="checkout-data"><span><?= e(villa_ui('Ek hizmet')) ?></span><strong><?= e(villa_ui('Geç çıkış ·')) ?> <?= e(number_format((float)$booking['late_checkout_fee'],2,',','.')) ?> <?= e($booking['currency']) ?></strong></div><?php endif; ?>
<div class="checkout-data"><span><?= e(villa_ui('Misafir sayısı')) ?></span><strong><?= (int)$booking['guests'] ?> <?= e(villa_ui('kişi')) ?></strong></div>
<div class="checkout-data"><span><?= e(villa_ui('Rezervasyon durumu')) ?></span><strong><?= e($booking['status']==='pending'?villa_ui('Ödeme bekleniyor'):$booking['status']) ?></strong></div>
<div class="checkout-total"><span><?= e(villa_ui('Ödenecek toplam')) ?></span><strong><?= e(number_format((float)$booking['total'],2,',','.')) ?> <?= e($booking['currency']) ?></strong></div>
</div>
<?php if(($paymentStatus??'')==='paid'||$booking['status']==='confirmed'): ?>
<div class="checkout-notice success"><span class="checkout-notice-icon">✓</span><div><strong><?= e(villa_ui('Ödemeniz doğrulandı')) ?></strong><?= e(villa_ui('Rezervasyonunuz onaylandı. Rezervasyon kodunuz:')) ?> <?= e($booking['booking_code']) ?></div></div>
<?php elseif(($paymentStatus??'')==='review'): ?>
<div class="checkout-notice"><span class="checkout-notice-icon">◷</span><div><strong><?= e(villa_ui('Ödeme güvenlik incelemesinde')) ?></strong><?= e(villa_ui('Ödeme sonucu alındı ve rezervasyon durumu kontrol ediliyor. Gerekli durumlarda yönetici incelemesi tamamlanmadan rezervasyon onaylanmaz.')) ?></div></div>
<?php elseif(in_array(($paymentStatus??''),['failed','verify_error','init_error'],true)): ?>
<div class="checkout-notice error"><span class="checkout-notice-icon">!</span><div><strong><?= e(villa_ui('Ödeme formu başlatılamadı')) ?></strong><?= e(villa_ui('Rezervasyonunuz beklemede kaldı. Lütfen tekrar deneyin.')) ?><?php if(!empty($iyzicoInitializationError)): ?><small style="display:block;margin-top:8px"><?= e(villa_ui('Teknik açıklama:')) ?> <?= e($iyzicoInitializationError) ?></small><?php endif; ?></div></div>
<?php endif; ?>
<?php if(!empty($paytrIframeUrl)): ?>
<section class="checkout-payment">
<div class="checkout-section-title"><span class="checkout-step">2</span><div><h2><?= e(villa_ui('PayTR güvenli ödeme')) ?></h2></div></div>
<p class="checkout-payment-copy"><?= e(villa_ui('Kart bilgilerinizi PayTR\'nin güvenli ödeme alanına girin. Kart bilgileriniz VillaCMS sunucusuna kaydedilmez.')) ?></p>
<div style="width:100%;min-height:650px;overflow:hidden;border:1px solid var(--villa-border);border-radius:14px;background:#fff">
<iframe src="<?= e($paytrIframeUrl) ?>" id="paytr-iframe" frameborder="0" scrolling="no" style="width:100%;min-height:650px;border:0" title="PayTR güvenli ödeme formu" allow="payment *"></iframe>
</div>
<script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
<script>if(window.iFrameResize){iFrameResize({},'#paytr-iframe');}</script>
</section>
<?php elseif(!empty($paytrPaymentAvailable)&&$booking['status']==='pending'&&($paymentStatus??'')!=='review'): ?>
<section class="checkout-payment">
<div class="checkout-section-title"><span class="checkout-step"><?= !empty($iyzicoPaymentAvailable)?3:2 ?></span><div><h2><?= e(villa_ui('PayTR ile güvenli ödeme')) ?></h2></div></div>
<p class="checkout-payment-copy"><?= e(villa_ui('PayTR\'nin barındırılan güvenli ödeme formu açılır. Ödeme sonucu doğrulanmadan rezervasyon onaylanmaz.')) ?></p>
<form method="post" action="<?= e(app_url('/checkout/'.rawurlencode((string)$booking['booking_code']).'/paytr/start')) ?>">
<?= csrf_field() ?>
<button class="checkout-pay-button" type="submit"><?= e(villa_ui('PayTR ile ödeme yap')) ?></button>
</form>
</section>
<?php endif; ?>
<?php if(!empty($iyzicoPaymentAvailable)&&$booking['status']==='pending'&&($paymentStatus??'')!=='review'): ?>
<section class="checkout-payment">
<div class="checkout-section-title"><span class="checkout-step">2</span><div><h2><?= e(villa_ui('iyzico ile güvenli ödeme')) ?></h2></div></div>
<p class="checkout-payment-copy"><?= e(villa_ui('Devam ettiğinizde iyzico\'nun güvenli ödeme sayfasına yönlendirileceksiniz. Kart bilgileriniz Villa CMS sunucusuna aktarılmaz.')) ?></p>
<form method="post" action="<?= e(app_url('/checkout/'.rawurlencode((string)$booking['booking_code']).'/iyzico/start')) ?>">
<?= csrf_field() ?>
<div class="checkout-fields">
<label class="checkout-field"><?= e(villa_ui('T.C. kimlik numarası')) ?><input name="identity_number" inputmode="numeric" autocomplete="off" pattern="[0-9]{11}" minlength="11" maxlength="11" required placeholder="11 haneli numara" value="<?= e((string)($iyzicoAutoStartData['identity_number']??'')) ?>"></label>
<label class="checkout-field"><?= e(villa_ui('Şehir')) ?><input name="billing_city" autocomplete="address-level2" maxlength="100" required placeholder="<?= e(villa_ui('Örn. Antalya')) ?>" value="<?= e((string)($iyzicoAutoStartData['billing_city']??'')) ?>"></label>
<label class="checkout-field full"><?= e(villa_ui('Fatura adresi')) ?><input name="billing_address" autocomplete="street-address" maxlength="500" required placeholder="<?= e(villa_ui('Mahalle, cadde, sokak ve kapı numarası')) ?>" value="<?= e((string)($iyzicoAutoStartData['billing_address']??'')) ?>"></label>
</div>
<p class="checkout-summary-location"><?= e(villa_ui('Fatura bilgileriniz iyzico\'ya aktarılır. Kart numarası ve güvenlik kodu uygulamamızın sunucusundan geçmez.')) ?></p>
<button class="checkout-pay-button" type="submit"><?= e(villa_ui('iyzico güvenli ödeme sayfasına git')) ?></button>
</form>
</section>
<?php else: ?>
<div class="checkout-notice"><span class="checkout-notice-icon">i</span><div><strong><?= e(villa_ui('Ödeme bilgisi')) ?></strong><?php if(!empty($manualPaymentAvailable)): ?><?= e(villa_ui('Kartla ödeme şu anda kullanılamıyor. Banka havalesi / EFT seçeneğini aşağıda kullanabilirsiniz.')) ?><?php else: ?><?= e(villa_ui('Ödeme yöntemi şu anda etkin değil veya rezervasyonunuzun ödeme durumu güncelleniyor.')) ?><?php endif; ?></div></div>
<?php endif; ?>
<?php if(!empty($manualPaymentAvailable)&&$booking['status']==='pending'&&($manualReportStatus??'')!=='approved'): ?>
<section class="checkout-payment">
<div class="checkout-section-title"><span class="checkout-step"><?= !empty($iyzicoPaymentAvailable)?3:2 ?></span><div><h2><?= e(villa_ui('Banka havalesi / EFT')) ?></h2></div></div>
<p class="checkout-payment-copy"><?= e(villa_ui('Aşağıdaki hesaba rezervasyon tutarını gönderin. Açıklama alanında rezervasyon kodunuzu kullanın. Ödeme, banka hareketi yönetici tarafından doğrulandıktan sonra onaylanır.')) ?></p>
<div class="checkout-booking-grid">
<div class="checkout-data"><span><?= e(villa_ui('Banka')) ?></span><strong><?= e($manualBankDetails['bank_name']??'') ?></strong></div>
<div class="checkout-data"><span><?= e(villa_ui('Hesap sahibi')) ?></span><strong><?= e($manualBankDetails['account_holder']??'') ?></strong></div>
<div class="checkout-data" style="grid-column:1/-1"><span>IBAN</span><strong><?= e($manualBankDetails['iban']??'') ?></strong></div>
<div class="checkout-data"><span><?= e(villa_ui('Transfer açıklaması')) ?></span><strong><?= e(($manualBankDetails['reference_prefix']??'VILLA').'-'.$booking['booking_code']) ?></strong></div>
<div class="checkout-data"><span><?= e(villa_ui('Gönderilecek tutar')) ?></span><strong><?= e(number_format((float)$booking['total'],2,',','.')) ?> <?= e($booking['currency']) ?></strong></div>
<?php if(!empty($manualBankDetails['instructions'])): ?><div class="checkout-data" style="grid-column:1/-1"><span><?= e(villa_ui('Ödeme talimatı')) ?></span><strong><?= nl2br(e($manualBankDetails['instructions'])) ?></strong></div><?php endif; ?>
</div>
<?php if(($manualReportStatus??'')==='pending'): ?>
<div class="checkout-notice"><span class="checkout-notice-icon">◷</span><div><strong><?= e(villa_ui('Havale bildiriminiz incelemede')) ?></strong><?= e(villa_ui('Transfer bildiriminiz yöneticiye ulaştı. Banka hareketi doğrulanana kadar rezervasyonunuz beklemede kalır.')) ?></div></div>
<?php else: ?>
<?php if(($manualReportStatus??'')==='rejected'): ?><div class="checkout-notice error"><span class="checkout-notice-icon">!</span><div><strong><?= e(villa_ui('Önceki bildiriminiz onaylanmadı')) ?></strong><?= e($latestManualReportRow['review_note']??'Transfer bilgilerinizi kontrol edip yeniden bildirim gönderebilirsiniz.') ?></div></div><?php endif; ?>
<form method="post" action="<?= e(app_url('/checkout/'.rawurlencode((string)$booking['booking_code']).'/manual/notify')) ?>">
<?= csrf_field() ?>
<div class="checkout-fields">
<label class="checkout-field"><?= e(villa_ui('Gönderen adı soyadı')) ?><input name="sender_name" maxlength="190" autocomplete="name" required placeholder="<?= e(villa_ui('Havale yapan kişi / şirket')) ?>"></label>
<label class="checkout-field"><?= e(villa_ui('Gönderen banka')) ?><input name="sender_bank" maxlength="120" placeholder="<?= e(villa_ui('Banka adı')) ?>"></label>
<label class="checkout-field full"><?= e(villa_ui('Banka işlem / dekont referansı')) ?><input name="transfer_reference" maxlength="120" required placeholder="<?= e(villa_ui('Bankadaki işlem referansı')) ?>"></label>
<label class="checkout-field full"><?= e(villa_ui('Ek açıklama (isteğe bağlı)')) ?><input name="note" maxlength="1000" placeholder="<?= e(villa_ui('Ödeme tarihi veya ek bilgi')) ?>"></label>
</div>
<button class="checkout-pay-button" type="submit"><?= e(villa_ui('Havale yaptım, bildirimi gönder')) ?></button>
</form>
<?php endif; ?>
</section>
<?php endif; ?>
<div class="checkout-actions"><a class="checkout-action" href="<?= e(app_url('/account/bookings')) ?>"><?= e(villa_ui('Rezervasyonlarım')) ?></a><a class="checkout-action" href="<?= e(app_url('/villa/'.rawurlencode((string)$booking['property_slug']))) ?>"><?= e(villa_ui('Villaya dön')) ?></a></div>
</section>
<aside class="checkout-summary">
<p class="checkout-summary-label"><?= e(villa_ui('Rezervasyon özeti')) ?></p>
<h2><?= e($booking['property_title']) ?></h2>
<p class="checkout-summary-location"><?= e(villa_ui('Konaklama detaylarınız')) ?></p>
<div class="checkout-summary-code"><span><?= e(villa_ui('Rezervasyon kodu')) ?></span><strong><?= e($booking['booking_code']) ?></strong></div>
<div class="checkout-summary-details">
<div class="checkout-summary-detail"><span><?= e(villa_ui('Giriş')) ?></span><strong><?= e(date('d.m.Y',strtotime($booking['check_in']))) ?></strong></div>
<div class="checkout-summary-detail"><span><?= e(villa_ui('Çıkış')) ?></span><strong><?= e(date('d.m.Y',strtotime($booking['check_out']))) ?></strong></div>
<?php if(!empty($booking['early_checkin_requested'])): ?><div class="checkout-summary-detail"><span><?= e(villa_ui('Ek hizmet')) ?></span><strong><?= e(villa_ui('Erken giriş ·')) ?> <?= e(number_format((float)$booking['early_checkin_fee'],2,',','.')) ?> <?= e($booking['currency']) ?></strong></div><?php endif; ?>
<?php if(!empty($booking['late_checkout_requested'])): ?><div class="checkout-summary-detail"><span><?= e(villa_ui('Ek hizmet')) ?></span><strong><?= e(villa_ui('Geç çıkış ·')) ?> <?= e(number_format((float)$booking['late_checkout_fee'],2,',','.')) ?> <?= e($booking['currency']) ?></strong></div><?php endif; ?>
<div class="checkout-summary-detail"><span><?= e(villa_ui('Misafir')) ?></span><strong><?= (int)$booking['guests'] ?> <?= e(villa_ui('kişi')) ?></strong></div>
<div class="checkout-summary-detail"><span><?= e(villa_ui('Durum')) ?></span><strong><?= e($booking['status']==='pending'?villa_ui('Ödeme bekleniyor'):$booking['status']) ?></strong></div>
</div>
<div class="checkout-summary-amount"><span><?= e(villa_ui('Toplam tutar')) ?></span><strong><?= e(number_format((float)$booking['total'],2,',','.')) ?> <?= e($booking['currency']) ?></strong></div>
<div class="checkout-secure"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 10V7a6 6 0 0 1 12 0v3M5 10h14v11H5z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 14v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg><span><?= e(villa_ui('Ödeme işlemi iyzico\'nun güvenli altyapısı üzerinden gerçekleştirilir.')) ?></span></div>
</aside>
</div>
</div>
</main>
<?php \App\Core\ThemeManager::renderPartial('partials/footer.php', get_defined_vars()); ?>
<?php if(!empty($autoStartIyzicoPayment)&&!empty($iyzicoPaymentAvailable)): ?>
<script>
document.addEventListener('DOMContentLoaded',function(){
 const paymentForm=document.querySelector('.checkout-payment form');
 if(paymentForm){const button=paymentForm.querySelector('button[type="submit"]');if(button){button.disabled=true;button.textContent=<?= json_encode(villa_ui('iyzico güvenli ödeme sayfasına yönlendiriliyorsunuz…'),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;}paymentForm.submit();}
});
</script>
<?php endif; ?>
</body>
</html>
