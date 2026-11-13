<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Rezervasyon talebi · <?= e($property['title']) ?></title>
<?php $pageCssPath = \App\Core\ThemeManager::resolveFile('pages/booking.css', 'default'); if ($pageCssPath !== null): ?><link rel="stylesheet" href="<?= e(\App\Core\ThemeManager::assetUrl('pages/booking.css', 'default')) ?>?v=<?= (int)filemtime($pageCssPath) ?>"><?php endif; ?>
</head>
<body>
<main class="wrap">
<p><a class="back" href="<?= e(app_url('/villa/' . rawurlencode((string)$property['slug']))) ?>">← İlana dön</a></p>
<h1 class="heading">Rezervasyon talebi</h1>
<p class="lead">Konaklama tarihlerinizi seçin; müsaitlik ve toplam tutar anında kontrol edilsin.</p>
<div class="booking-grid">
<form id="bookingForm" method="post" action="<?= e(app_url('/villa/' . rawurlencode((string)$property['slug']) . '/reserve')) ?>" data-availability-url="<?= e(app_url('/villa/' . rawurlencode((string)$property['slug']) . '/availability')) ?>">
<div class="panel">
<?= csrf_field() ?>
<div class="field"><label for="bookingCheckIn">Giriş tarihi</label><input type="date" id="bookingCheckIn" name="check_in" min="<?= e(date('Y-m-d')) ?>" value="<?= e($checkInDate??'') ?>" required></div>
<div class="field"><label for="bookingCheckOut">Çıkış tarihi</label><input type="date" id="bookingCheckOut" name="check_out" min="<?= e(date('Y-m-d',strtotime('+1 day'))) ?>" value="<?= e($checkOutDate??'') ?>" required></div>
<div class="field wide"><label for="bookingGuests">Misafir sayısı</label><input type="number" id="bookingGuests" name="guests" min="1" max="<?= (int)$property['max_guests'] ?>" value="<?= e((string)min(2,max(1,(int)$property['max_guests']))) ?>" required></div>
<div class="field wide"><button class="btn" id="bookingSubmit" type="submit">Rezervasyon talebini gönder</button></div>
</div>
<p class="notice"><?= e($content['booking_notice'] ?? 'Bu aşamada ödeme alınmaz. Rezervasyon talebiniz ev sahibi veya yönetim tarafından incelenir.') ?></p>
</form>
<aside class="summary">
<h2><?= e($property['title']) ?></h2>
<p class="muted"><?= e($property['city']) ?> · En fazla <?= (int)$property['max_guests'] ?> misafir</p>
<div class="base-price"><?= e(number_format((float)$property['base_nightly_price'],0,',','.')) ?> <?= e($property['currency']) ?> <span class="muted">/ gece başlangıç</span></div>
<?php if(!empty($property['weekend_nightly_price'])): ?><p class="muted">Hafta sonu gecesi: <?= e(number_format((float)$property['weekend_nightly_price'],2,',','.')) ?> <?= e($property['currency']) ?></p><?php endif; ?>
<div id="availabilityMessage" class="availability-message wait" role="status" aria-live="polite"></div>
<div id="priceLines" class="price-lines" <?= empty($pricePreview)?'hidden':'' ?>>
<div class="price-line"><span id="nightCount"><?= (int)($pricePreview['nights']??0) ?> gece konaklama</span><strong id="nightSubtotal"><?= e(number_format((float)($pricePreview['subtotal']??0),2,',','.')) ?> <?= e($property['currency']) ?></strong></div>
<div class="price-line"><span>Temizlik ücreti</span><strong id="cleaningFee"><?= e(number_format((float)($pricePreview['fees']??0),2,',','.')) ?> <?= e($property['currency']) ?></strong></div>
<div class="price-line price-total"><span>Toplam</span><strong id="grandTotal"><?= e(number_format((float)($pricePreview['total']??0),2,',','.')) ?> <?= e($property['currency']) ?></strong></div>
</div>
</aside>
</div>
<?php \App\Core\ThemeManager::renderPartial('partials/logout-button.php', get_defined_vars()); ?>
</main>
<footer><div class="footer-inner"><div><strong><?= e($siteName??'VillaStay') ?></strong><p><?= e($content['footer_text']??'') ?></p></div><nav><?php foreach(($footerNavigation??[]) as $item): ?><a href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a><?php endforeach; ?><a href="<?= e(app_url('/konaklamalar')) ?>">Konaklamalar</a><a href="<?= e(app_url('/destinasyonlar')) ?>">Destinasyonlar</a></nav></div></footer>
<script>
(function(){
var form=document.getElementById('bookingForm'),checkIn=document.getElementById('bookingCheckIn'),checkOut=document.getElementById('bookingCheckOut'),guests=document.getElementById('bookingGuests'),message=document.getElementById('availabilityMessage'),lines=document.getElementById('priceLines'),submit=document.getElementById('bookingSubmit'),timer=null,requestId=0,unavailable=false;
if(!form||!checkIn||!checkOut)return;
var endpoint=form.getAttribute('data-availability-url');
function money(value,currency){return new Intl.NumberFormat('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2}).format(value)+' '+currency}
function setMessage(text,type){message.textContent=text;message.className='availability-message show '+type}
function syncCheckout(){var minDate;if(checkIn.value){var p=checkIn.value.split('-');var d=new Date(Number(p[0]),Number(p[1])-1,Number(p[2]),12);d.setDate(d.getDate()+1);minDate=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')}else{var d=new Date();d.setHours(12,0,0,0);d.setDate(d.getDate()+1);minDate=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')}checkOut.min=minDate;if(checkOut.value&&checkOut.value<minDate)checkOut.value=''}
async function checkAvailability(){var id=++requestId;unavailable=false;submit.disabled=false;lines.hidden=true;if(!checkIn.value||!checkOut.value){message.className='availability-message';return}if(checkOut.value<=checkIn.value){setMessage('Çıkış tarihi giriş tarihinden sonra olmalıdır.','bad');unavailable=true;submit.disabled=true;return}setMessage('Müsaitlik ve fiyat kontrol ediliyor…','wait');var params=new URLSearchParams({check_in:checkIn.value,check_out:checkOut.value,guests:guests.value||'1'});try{var response=await fetch(endpoint+'?'+params.toString(),{headers:{'Accept':'application/json'},credentials:'same-origin'});var data=await response.json();if(id!==requestId)return;if(!response.ok||!data.ok){unavailable=true;submit.disabled=true;setMessage(data.message||'Bu tarih aralığı kontrol edilemedi.','bad');return}document.getElementById('nightCount').textContent=data.nights+' gece konaklama';document.getElementById('nightSubtotal').textContent=money(data.subtotal,data.currency);document.getElementById('cleaningFee').textContent=money(data.fees,data.currency);document.getElementById('grandTotal').textContent=money(data.total,data.currency);lines.hidden=false;setMessage(data.message,'ok')}catch(error){if(id!==requestId)return;setMessage('Anlık kontrol yapılamadı. Talep gönderildiğinde sunucu tarihleri yeniden doğrulayacaktır.','wait')}}
function scheduleCheck(){clearTimeout(timer);timer=setTimeout(checkAvailability,250)}
checkIn.addEventListener('change',function(){syncCheckout();scheduleCheck()});checkOut.addEventListener('change',scheduleCheck);guests.addEventListener('change',scheduleCheck);syncCheckout();if(checkIn.value&&checkOut.value)checkAvailability();
form.addEventListener('submit',function(event){if(unavailable){event.preventDefault();setMessage('Lütfen müsait tarihleri seçin.','bad')}});
})();
</script>
</body></html>
