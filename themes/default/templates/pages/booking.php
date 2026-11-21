<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="<?= e(\App\Core\Translator::locale()) ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(villa_ui('Rezervasyon talebi ·')) ?> <?= e($property['title']) ?></title>
<style>
:root{color-scheme:light;--ink:#26352c;--muted:#718078;--line:#e4ebe5;--brand:#176b50;--soft:#f0f6f1}
*{box-sizing:border-box}body{margin:0;background:#f5f7f4;color:var(--villa-text);font-family:var(--villa-font-body);"Segoe UI",sans-serif}.wrap{width:min(980px,calc(100% - 32px));margin:34px auto}.back{color:var(--villa-text);text-decoration:none;font-weight:var(--villa-body-weight);}.heading{margin:22px 0 8px;font-size:var(--villa-body-size);letter-spacing:-.04em}.lead{color:var(--villa-text);margin:0 0 22px}.booking-grid{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(260px,.75fr);gap:20px;align-items:start}.panel,.summary{background:#fff;border:1px solid var(--line);border-radius:18px;padding:24px;box-shadow:0 12px 36px #17291d08}.panel{display:grid;grid-template-columns:1fr 1fr;gap:18px}.field{min-width:0}.field.wide{grid-column:1/-1}label{display:block;font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);margin-bottom:7px}input{display:block;width:100%;min-height:48px;padding:11px 13px;border:1px solid #d5dfd7;border-radius:10px;background:#fff;color:var(--villa-text);font:inherit;}input:focus{outline:3px solid #176b5020;border-color:var(--brand)}.summary{position:sticky;top:18px}.summary h2{font-size:var(--villa-body-size);margin:0 0 8px}.muted{color:var(--villa-text);line-height:var(--villa-body-line-height);font-size:var(--villa-body-size);}.base-price{font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);margin:15px 0}.price-lines{border-top:1px solid var(--line);margin-top:16px;padding-top:13px}.price-line{display:flex;justify-content:space-between;gap:12px;margin:9px 0;font-size:var(--villa-body-size);}.price-total{border-top:1px solid var(--line);padding-top:13px;margin-top:13px;font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);}.availability-message{display:none;margin-top:14px;padding:12px 13px;border-radius:10px;font-size:var(--villa-body-size);line-height:var(--villa-body-line-height);}.availability-message.show{display:block}.availability-message.ok{background:#edf7ef;color:var(--villa-text);}.availability-message.bad{background:#fff0ed;color:var(--villa-text);}.availability-message.wait{background:#f2f4f2;color:var(--villa-text);}.btn{width:100%;min-height:50px;background:var(--brand);color:var(--villa-text);border:0;border-radius:11px;padding:13px 18px;font:inherit;font-weight:var(--villa-body-weight);cursor:pointer}.btn:hover{filter:brightness(.94)}.btn:disabled{opacity:.5;cursor:not-allowed}.notice{margin:18px 0 0;color:var(--villa-text);font-size:var(--villa-body-size);line-height:var(--villa-body-line-height);}.summary a{color:var(--villa-text);}footer{background:#20352e;color:var(--villa-text);padding:32px 0;margin-top:48px}footer p{color:var(--villa-text);line-height:var(--villa-body-line-height);}.footer-inner{width:min(980px,calc(100% - 32px));margin:auto;display:grid;grid-template-columns:1fr 2fr;gap:24px}footer nav{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 15px;font-size:var(--villa-body-size);}footer nav a{color:var(--villa-text);overflow-wrap:anywhere}@media(max-width:720px){.wrap{margin:22px auto}.booking-grid{grid-template-columns:1fr}.summary{position:static;order:-1}.panel{padding:18px;gap:14px}}@media(max-width:480px){.panel{grid-template-columns:1fr}.field.wide{grid-column:auto}.footer-inner{grid-template-columns:1fr}.summary,.panel{border-radius:14px}}
</style>
</head>
<body>
<main class="wrap">
<p><a class="back" href="<?= e(app_url('/villa/' . rawurlencode((string)$property['slug']))) ?>"><?= e(villa_ui('← İlana dön')) ?></a></p>
<h1 class="heading"><?= e(villa_ui('Rezervasyon talebi')) ?></h1>
<p class="lead"><?= e(villa_ui('Konaklama tarihlerinizi seçin; müsaitlik ve toplam tutar anında kontrol edilsin.')) ?></p>
<div class="booking-grid">
<form id="bookingForm" method="post" action="<?= e(app_url('/villa/' . rawurlencode((string)$property['slug']) . '/reserve')) ?>" data-availability-url="<?= e(app_url('/villa/' . rawurlencode((string)$property['slug']) . '/availability')) ?>">
<div class="panel">
<?= csrf_field() ?>
<div class="field"><label for="bookingCheckIn"><?= e(villa_ui('Giriş tarihi')) ?></label><input type="date" id="bookingCheckIn" name="check_in" min="<?= e(date('Y-m-d')) ?>" value="<?= e($checkInDate??'') ?>" required></div>
<div class="field"><label for="bookingCheckOut"><?= e(villa_ui('Çıkış tarihi')) ?></label><input type="date" id="bookingCheckOut" name="check_out" min="<?= e(date('Y-m-d',strtotime('+1 day'))) ?>" value="<?= e($checkOutDate??'') ?>" required></div>
<div class="field wide"><label for="bookingGuests"><?= e(villa_ui('Misafir sayısı')) ?></label><input type="number" id="bookingGuests" name="guests" min="1" max="<?= (int)$property['max_guests'] ?>" value="<?= e((string)min(2,max(1,(int)$property['max_guests']))) ?>" required></div>
<div class="field"><label for="guestFirstName"><?= e(villa_ui('Adınız')) ?></label><input id="guestFirstName" name="first_name" type="text" maxlength="90" autocomplete="given-name" required></div>
<div class="field"><label for="guestLastName"><?= e(villa_ui('Soyadınız')) ?></label><input id="guestLastName" name="last_name" type="text" maxlength="90" autocomplete="family-name" required></div>
<div class="field"><label for="guestEmail"><?= e(villa_ui('E-posta adresi')) ?></label><input id="guestEmail" name="email" type="email" maxlength="190" autocomplete="email" required></div>
<div class="field"><label for="guestPhone"><?= e(villa_ui('Telefon')) ?></label><input id="guestPhone" name="phone" type="tel" maxlength="40" autocomplete="tel" required></div>
<div class="field wide"><label for="guestPassword"><?= e(villa_ui('Hesap şifresi')) ?></label><input id="guestPassword" name="password" type="password" minlength="10" maxlength="128" autocomplete="new-password" required><small class="muted"><?= e(villa_ui('Rezervasyonunuzu hesabınızdan takip edebilmeniz için en az 10 karakterli bir şifre belirleyin. Bu e-posta ile hesabınız varsa mevcut şifrenizi girin.')) ?></small></div>
<div class="field wide"><button class="btn" id="bookingSubmit" type="submit"><?= e(villa_ui('Rezervasyon talebini gönder')) ?></button></div>
</div>
<p class="notice"><?= e($content['booking_notice'] ?? villa_ui('Bu aşamada ödeme alınmaz. Rezervasyon talebiniz ev sahibi veya yönetim tarafından incelenir.')) ?></p>
</form>
<aside class="summary">
<h2><?= e($property['title']) ?></h2>
<p class="muted"><?= e($property['city']) ?> <?= e(villa_ui('· En fazla')) ?> <?= (int)$property['max_guests'] ?> <?= e(villa_ui('misafir')) ?></p>
<div class="base-price"><?= e(number_format((float)$property['base_nightly_price'],0,',','.')) ?> <?= e($property['currency']) ?> <span class="muted"><?= e(villa_ui('/ gece başlangıç')) ?></span></div>
<?php if(!empty($property['weekend_nightly_price'])): ?><p class="muted"><?= e(villa_ui('Hafta sonu gecesi:')) ?> <?= e(number_format((float)$property['weekend_nightly_price'],2,',','.')) ?> <?= e($property['currency']) ?></p><?php endif; ?>
<div id="availabilityMessage" class="availability-message wait" role="status" aria-live="polite"></div>
<div id="priceLines" class="price-lines" <?= empty($pricePreview)?'hidden':'' ?>>
<div class="price-line"><span id="nightCount"><?= (int)($pricePreview['nights']??0) ?> <?= e(villa_ui('gece konaklama')) ?></span><strong id="nightSubtotal"><?= e(number_format((float)($pricePreview['subtotal']??0),2,',','.')) ?> <?= e($property['currency']) ?></strong></div>
<div class="price-line"><span><?= e(villa_ui('Temizlik ücreti')) ?></span><strong id="cleaningFee"><?= e(number_format((float)($pricePreview['fees']??0),2,',','.')) ?> <?= e($property['currency']) ?></strong></div>
<div class="price-line price-total"><span><?= e(villa_ui('Toplam')) ?></span><strong id="grandTotal"><?= e(number_format((float)($pricePreview['total']??0),2,',','.')) ?> <?= e($property['currency']) ?></strong></div>
</div>
</aside>
</div>
<?php \App\Core\ThemeManager::renderPartial('partials/logout-button.php', get_defined_vars()); ?>
</main>

<script>
(function(){
var form=document.getElementById('bookingForm'),checkIn=document.getElementById('bookingCheckIn'),checkOut=document.getElementById('bookingCheckOut'),guests=document.getElementById('bookingGuests'),message=document.getElementById('availabilityMessage'),lines=document.getElementById('priceLines'),submit=document.getElementById('bookingSubmit'),timer=null,requestId=0,unavailable=false;
if(!form||!checkIn||!checkOut)return;
var endpoint=form.getAttribute('data-availability-url');
function money(value,currency){return new Intl.NumberFormat('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2}).format(value)+' '+currency}
function setMessage(text,type){message.textContent=text;message.className='availability-message show '+type}
function syncCheckout(){var minDate;if(checkIn.value){var p=checkIn.value.split('-');var d=new Date(Number(p[0]),Number(p[1])-1,Number(p[2]),12);d.setDate(d.getDate()+1);minDate=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')}else{var d=new Date();d.setHours(12,0,0,0);d.setDate(d.getDate()+1);minDate=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')}checkOut.min=minDate;if(checkOut.value&&checkOut.value<minDate)checkOut.value=''}
async function checkAvailability(){var id=++requestId;unavailable=false;submit.disabled=false;lines.hidden=true;if(!checkIn.value||!checkOut.value){message.className='availability-message';return}if(checkOut.value<=checkIn.value){setMessage(<?= json_encode(villa_ui('Çıkış tarihi giriş tarihinden sonra olmalıdır.'),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'bad');unavailable=true;submit.disabled=true;return}setMessage(<?= json_encode(villa_ui('Müsaitlik ve fiyat kontrol ediliyor…'),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'wait');var params=new URLSearchParams({check_in:checkIn.value,check_out:checkOut.value,guests:guests.value||'1'});try{var response=await fetch(endpoint+'?'+params.toString(),{headers:{'Accept':'application/json'},credentials:'same-origin'});var data=await response.json();if(id!==requestId)return;if(!response.ok||!data.ok){unavailable=true;submit.disabled=true;setMessage(data.message||<?= json_encode(villa_ui('Bu tarih aralığı kontrol edilemedi.'),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'bad');return}document.getElementById('nightCount').textContent=data.nights+' gece konaklama';document.getElementById('nightSubtotal').textContent=money(data.subtotal,data.currency);document.getElementById('cleaningFee').textContent=money(data.fees,data.currency);document.getElementById('grandTotal').textContent=money(data.total,data.currency);lines.hidden=false;setMessage(data.message,'ok')}catch(error){if(id!==requestId)return;setMessage(<?= json_encode(villa_ui('Anlık kontrol yapılamadı. Talep gönderildiğinde sunucu tarihleri yeniden doğrulayacaktır.'),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'wait')}}
function scheduleCheck(){clearTimeout(timer);timer=setTimeout(checkAvailability,250)}
checkIn.addEventListener('change',function(){syncCheckout();scheduleCheck()});checkOut.addEventListener('change',scheduleCheck);guests.addEventListener('change',scheduleCheck);syncCheckout();if(checkIn.value&&checkOut.value)checkAvailability();
form.addEventListener('submit',function(event){if(unavailable){event.preventDefault();setMessage(<?= json_encode(villa_ui('Lütfen müsait tarihleri seçin.'),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>,'bad')}});
})();
</script>
</body></html>
