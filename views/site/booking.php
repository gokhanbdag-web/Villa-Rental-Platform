<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="<?= e(\\App\\Core\\Translator::locale()) ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(\\App\\Core\\Translator::get('booking.request_title')) ?> · <?= e($property['title']) ?></title>
<style>
:root{color-scheme:light;--ink:#26352c;--muted:#718078;--line:#e4ebe5;--brand:#176b50;--soft:#f0f6f1}
*{box-sizing:border-box}body{margin:0;background:#f5f7f4;color:var(--ink);font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.wrap{width:min(980px,calc(100% - 32px));margin:34px auto}.back{color:var(--brand);text-decoration:none;font-weight:650}.heading{margin:22px 0 8px;font-size:clamp(27px,4vw,38px);letter-spacing:-.04em}.lead{color:var(--muted);margin:0 0 22px}.booking-grid{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(260px,.75fr);gap:20px;align-items:start}.panel,.summary{background:#fff;border:1px solid var(--line);border-radius:18px;padding:24px;box-shadow:0 12px 36px #17291d08}.panel{display:grid;grid-template-columns:1fr 1fr;gap:18px}.field{min-width:0}.field.wide{grid-column:1/-1}label{display:block;font-size:13px;font-weight:750;margin-bottom:7px}input{display:block;width:100%;min-height:48px;padding:11px 13px;border:1px solid #d5dfd7;border-radius:10px;background:#fff;color:var(--ink);font:inherit}input:focus{outline:3px solid #176b5020;border-color:var(--brand)}.summary{position:sticky;top:18px}.summary h2{font-size:18px;margin:0 0 8px}.muted{color:var(--muted);line-height:1.6;font-size:13px}.base-price{font-size:18px;font-weight:800;margin:15px 0}.price-lines{border-top:1px solid var(--line);margin-top:16px;padding-top:13px}.price-line{display:flex;justify-content:space-between;gap:12px;margin:9px 0;font-size:14px}.price-total{border-top:1px solid var(--line);padding-top:13px;margin-top:13px;font-size:17px;font-weight:800}.availability-message{display:none;margin-top:14px;padding:12px 13px;border-radius:10px;font-size:13px;line-height:1.5}.availability-message.show{display:block}.availability-message.ok{background:#edf7ef;color:#22613c}.availability-message.bad{background:#fff0ed;color:#a13d2d}.availability-message.wait{background:#f2f4f2;color:#536057}.btn{width:100%;min-height:50px;background:var(--brand);color:#fff;border:0;border-radius:11px;padding:13px 18px;font:inherit;font-weight:800;cursor:pointer}.btn:hover{filter:brightness(.94)}.btn:disabled{opacity:.5;cursor:not-allowed}.notice{margin:18px 0 0;color:var(--muted);font-size:13px;line-height:1.6}.summary a{color:var(--brand)}footer{background:#20352e;color:#fff;padding:32px 0;margin-top:48px}footer p{color:#c3d2ca;line-height:1.6}.footer-inner{width:min(980px,calc(100% - 32px));margin:auto;display:grid;grid-template-columns:1fr 2fr;gap:24px}footer nav{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 15px;font-size:13px}footer nav a{color:#fff;overflow-wrap:anywhere}@media(max-width:720px){.wrap{margin:22px auto}.booking-grid{grid-template-columns:1fr}.summary{position:static;order:-1}.panel{padding:18px;gap:14px}}@media(max-width:480px){.panel{grid-template-columns:1fr}.field.wide{grid-column:auto}.footer-inner{grid-template-columns:1fr}.summary,.panel{border-radius:14px}}
</style>
</head>
<body>
<main class="wrap">
<p><a class="back" href="<?= e(app_url('/villa/' . rawurlencode((string)$property['slug']))) ?>">← <?= e(\\App\\Core\\Translator::get('booking.back')) ?></a></p>
<h1 class="heading"><?= e(\\App\\Core\\Translator::get('booking.request_title')) ?></h1>
<p class="lead"><?= e(\\App\\Core\\Translator::get('booking.intro')) ?></p>
<div class="booking-grid">
<form id="bookingForm" method="post" action="<?= e(app_url('/villa/' . rawurlencode((string)$property['slug']) . '/reserve')) ?>" data-availability-url="<?= e(app_url('/villa/' . rawurlencode((string)$property['slug']) . '/availability')) ?>">
<div class="panel">
<?= csrf_field() ?>
<div class="field"><label for="bookingCheckIn"><?= e(\\App\\Core\\Translator::get('booking.check_in')) ?></label><input type="date" id="bookingCheckIn" name="check_in" min="<?= e(date('Y-m-d')) ?>" value="<?= e($checkInDate??'') ?>" required></div>
<div class="field"><label for="bookingCheckOut"><?= e(\\App\\Core\\Translator::get('booking.check_out')) ?></label><input type="date" id="bookingCheckOut" name="check_out" min="<?= e(date('Y-m-d',strtotime('+1 day'))) ?>" value="<?= e($checkOutDate??'') ?>" required></div>
<div class="field wide"><label for="bookingGuests"><?= e(\\App\\Core\\Translator::get('booking.guests')) ?></label><input type="number" id="bookingGuests" name="guests" min="1" max="<?= (int)$property['max_guests'] ?>" value="<?= e((string)min(2,max(1,(int)$property['max_guests']))) ?>" required></div>
<div class="field wide"><button class="btn" id="bookingSubmit" type="submit"><?= e(\\App\\Core\\Translator::get('booking.request_title')) ?>ni gönder</button></div>
</div>
<p class="notice"><?= e($content['booking_notice'] ?? 'Bu aşamada ödeme alınmaz. <?= e(\\App\\Core\\Translator::get('booking.request_title')) ?>niz ev sahibi veya yönetim tarafından incelenir.') ?></p>
</form>
<aside class="summary">
<h2><?= e($property['title']) ?></h2>
<p class="muted"><?= e($property['city']) ?> · <?= e(\\App\\Core\\Translator::get('booking.max_guests')) ?> <?= (int)$property['max_guests'] ?> misafir</p>
<div class="base-price"><?= e(number_format((float)$property['base_nightly_price'],0,',','.')) ?> <?= e($property['currency']) ?> <span class="muted"><?= e(\\App\\Core\\Translator::get('booking.per_night')) ?></span></div>
<?php if(!empty($property['weekend_nightly_price'])): ?><p class="muted"><?= e(\\App\\Core\\Translator::get('booking.weekend_night')) ?> <?= e(number_format((float)$property['weekend_nightly_price'],2,',','.')) ?> <?= e($property['currency']) ?></p><?php endif; ?>
<div id="availabilityMessage" class="availability-message wait" role="status" aria-live="polite"></div>
<div id="priceLines" class="price-lines" <?= empty($pricePreview)?'hidden':'' ?>>
<div class="price-line"><span id="nightCount"><?= (int)($pricePreview['nights']??0) ?> <?= e(\\App\\Core\\Translator::get('booking.nights')) ?></span><strong id="nightSubtotal"><?= e(number_format((float)($pricePreview['subtotal']??0),2,',','.')) ?> <?= e($property['currency']) ?></strong></div>
<div class="price-line"><span><?= e(\\App\\Core\\Translator::get('booking.cleaning_fee')) ?></span><strong id="cleaningFee"><?= e(number_format((float)($pricePreview['fees']??0),2,',','.')) ?> <?= e($property['currency']) ?></strong></div>
<div class="price-line price-total"><span><?= e(\\App\\Core\\Translator::get('booking.total')) ?></span><strong id="grandTotal"><?= e(number_format((float)($pricePreview['total']??0),2,',','.')) ?> <?= e($property['currency']) ?></strong></div>
</div>
</aside>
</div>
<?php require APP_ROOT . '/views/site/partials/logout-button.php'; ?>
</main>

<script>
(function(){
var form=document.getElementById('bookingForm'),checkIn=document.getElementById('bookingCheckIn'),checkOut=document.getElementById('bookingCheckOut'),guests=document.getElementById('bookingGuests'),message=document.getElementById('availabilityMessage'),lines=document.getElementById('priceLines'),submit=document.getElementById('bookingSubmit'),timer=null,requestId=0,unavailable=false;
if(!form||!checkIn||!checkOut)return;
var endpoint=form.getAttribute('data-availability-url');
function money(value,currency){return new Intl.NumberFormat(<?= json_encode(\\App\\Core\\Translator::locale()==='en'?'en-US':'tr-TR') ?>,{minimumFractionDigits:2,maximumFractionDigits:2}).format(value)+' '+currency}
function setMessage(text,type){message.textContent=text;message.className='availability-message show '+type}
function syncCheckout(){var minDate;if(checkIn.value){var p=checkIn.value.split('-');var d=new Date(Number(p[0]),Number(p[1])-1,Number(p[2]),12);d.setDate(d.getDate()+1);minDate=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')}else{var d=new Date();d.setHours(12,0,0,0);d.setDate(d.getDate()+1);minDate=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')}checkOut.min=minDate;if(checkOut.value&&checkOut.value<minDate)checkOut.value=''}
async function checkAvailability(){var id=++requestId;unavailable=false;submit.disabled=false;lines.hidden=true;if(!checkIn.value||!checkOut.value){message.className='availability-message';return}if(checkOut.value<=checkIn.value){setMessage('<?= e(\\App\\Core\\Translator::get('booking.check_out')) ?> giriş tarihinden sonra olmalıdır.','bad');unavailable=true;submit.disabled=true;return}setMessage(<?= json_encode(\\App\\Core\\Translator::get('booking.checking'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,'wait');var params=new URLSearchParams({check_in:checkIn.value,check_out:checkOut.value,guests:guests.value||'1'});try{var response=await fetch(endpoint+'?'+params.toString(),{headers:{'Accept':'application/json'},credentials:'same-origin'});var data=await response.json();if(id!==requestId)return;if(!response.ok||!data.ok){unavailable=true;submit.disabled=true;setMessage(data.message||<?= json_encode(\\App\\Core\\Translator::get('booking.unavailable'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,'bad');return}document.getElementById('nightCount').textContent=data.nights+' <?= e(\\App\\Core\\Translator::get('booking.nights')) ?>';document.getElementById('nightSubtotal').textContent=money(data.subtotal,data.currency);document.getElementById('cleaningFee').textContent=money(data.fees,data.currency);document.getElementById('grandTotal').textContent=money(data.total,data.currency);lines.hidden=false;setMessage(data.message,'ok')}catch(error){if(id!==requestId)return;setMessage(<?= json_encode(\\App\\Core\\Translator::get('booking.network_error'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,'wait')}}
function scheduleCheck(){clearTimeout(timer);timer=setTimeout(checkAvailability,250)}
checkIn.addEventListener('change',function(){syncCheckout();scheduleCheck()});checkOut.addEventListener('change',scheduleCheck);guests.addEventListener('change',scheduleCheck);syncCheckout();if(checkIn.value&&checkOut.value)checkAvailability();
form.addEventListener('submit',function(event){if(unavailable){event.preventDefault();setMessage(<?= json_encode(\\App\\Core\\Translator::get('booking.select_dates'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,'bad')}});
})();
</script>
</body></html>
