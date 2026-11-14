<?php
declare(strict_types=1);

$footerSettings = \App\Core\ThemeManager::footerSettings();
$mobileBarSettings = [];
try {
    $mobileBarQuery = \App\Core\Database::connection()->prepare("SELECT setting_key,setting_value FROM settings WHERE setting_key IN ('mobile_bar.enabled','mobile_bar.whatsapp','mobile_bar.phone')");
    $mobileBarQuery->execute();
    $mobileBarSettings = $mobileBarQuery->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Throwable $mobileBarError) {
    $mobileBarSettings = [];
}
$mobileBarEnabled = ($mobileBarSettings['mobile_bar.enabled'] ?? '1') === '1';
$mobileWhatsapp = preg_replace('/\\D+/', '', (string)($mobileBarSettings['mobile_bar.whatsapp'] ?? '')) ?: '';
$mobilePhone = preg_replace('/\\D+/', '', (string)($mobileBarSettings['mobile_bar.phone'] ?? '')) ?: '';
?>
<footer class="site-footer" aria-label="Alt bilgi"
    style="--footer-bg:<?= e($footerSettings['background']) ?>;--footer-text:<?= e($footerSettings['text']) ?>;--footer-padding-top:<?= (int)$footerSettings['padding_top'] ?>px;--footer-padding-bottom:<?= (int)$footerSettings['padding_bottom'] ?>px;--footer-margin-top:<?= (int)$footerSettings['margin_top'] ?>px;--footer-content-width:<?= (int)$footerSettings['content_width'] ?>px">
    <div class="footer-inner">
        <div class="footer-row footer-row-primary" aria-label="Üst widget satırı">
            <div class="footer-widget footer-widget-1" data-footer-widget="1">
                <?php if (trim((string)($footerSettings['about_title'] ?? '')) !== '' || trim((string)($footerSettings['about_description'] ?? '')) !== ''): ?>
                    <section class="footer-about" aria-label="Hakkında">
                        <?php if (trim((string)($footerSettings['about_title'] ?? '')) !== ''): ?><h2><?= e($footerSettings['about_title']) ?></h2><?php endif; ?>
                        <?php if (trim((string)($footerSettings['about_description'] ?? '')) !== ''): ?><p><?= nl2br(e($footerSettings['about_description'])) ?></p><?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
            <div class="footer-widget footer-widget-2" data-footer-widget="2"></div>
            <div class="footer-widget footer-widget-3" data-footer-widget="3"></div>
            <div class="footer-widget footer-widget-4" data-footer-widget="4"></div>
        </div>
        <div class="footer-row footer-row-secondary" aria-label="Alt widget satırı">
            <div class="footer-widget footer-widget-5" data-footer-widget="5"></div>
            <div class="footer-widget footer-widget-6" data-footer-widget="6">
                <?php
                $paymentNames = ['mastercard'=>'Mastercard','visa'=>'Visa','troy'=>'Troy','diners_club'=>'Diners Club','american_express'=>'American Express'];
                $paymentOrder = is_array($footerSettings['payment_order'] ?? null) ? $footerSettings['payment_order'] : array_keys($paymentNames);
                ?>
                <div class="footer-payment-logos" aria-label="Kabul edilen ödeme kartları">
                    <?php foreach (array_slice($paymentOrder, 0, 5) as $payment): if (!isset($paymentNames[$payment])) continue; ?>
                        <span class="footer-payment-logo" title="<?= e($paymentNames[$payment]) ?>">
                            <img src="<?= e(\App\Core\ThemeManager::assetUrl('payment-logos/' . $payment . '.svg')) ?>" alt="<?= e($paymentNames[$payment]) ?>" loading="lazy" decoding="async">
                        </span>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </div>
</footer>

<?php if ($mobileBarEnabled): ?>
<style>
.villa-mobile-bar,.villa-mobile-search-overlay{display:none}
@media(max-width:760px){
 body{padding-bottom:calc(82px + env(safe-area-inset-bottom,0px))!important}
 .villa-mobile-bar{position:fixed;z-index:9998;left:0;right:0;bottom:0;width:100%;box-sizing:border-box;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));align-items:stretch;gap:0;padding:7px 8px calc(7px + env(safe-area-inset-bottom,0px));background:#fff;border:0;border-top:1px solid #e8e8e8;border-radius:0;box-shadow:0 -5px 20px rgba(0,0,0,.055)}
 .villa-mobile-bar>a,.villa-mobile-bar>button,.villa-mobile-bar>span{box-sizing:border-box;position:relative;min-width:0;width:100%;min-height:62px;margin:0;padding:7px 2px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:5px;border:0;border-radius:0;background:transparent;color:#222;text-decoration:none;font:inherit;cursor:pointer;-webkit-tap-highlight-color:transparent}
 .villa-mobile-bar>span{opacity:.35;cursor:default}
 .villa-mobile-bar svg{width:23px;height:23px;display:block;stroke:currentColor;fill:none;stroke-width:1.7;stroke-linecap:round;stroke-linejoin:round;color:#252525}
 .villa-mobile-bar a>span,.villa-mobile-bar button>span,.villa-mobile-bar>span>span{font-size:10px;font-weight:600;line-height:1.1;letter-spacing:0;white-space:nowrap;color:#333}
 .villa-mobile-bar [data-villa-search-open]{color:#111}
 .villa-mobile-bar [data-villa-search-open]:after{content:"";position:absolute;top:0;left:50%;width:27px;height:2px;transform:translateX(-50%);background:#222}
 .villa-mobile-bar>a:active,.villa-mobile-bar>button:active{background:#f5f5f5}
 .villa-mobile-bar a:focus-visible,.villa-mobile-bar button:focus-visible{outline:2px solid #555;outline-offset:-3px}
 .villa-mobile-search-overlay{position:fixed!important;z-index:2147483000!important;isolation:isolate;inset:0;display:flex;flex-direction:column;align-items:stretch;justify-content:flex-start;padding:0;background:#f7f7f5;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .2s ease,visibility .2s ease}
 .villa-mobile-search-overlay.is-open{z-index:2147483000!important;opacity:1;visibility:visible;pointer-events:auto}
 .villa-mobile-search-card{position:relative;z-index:1;box-sizing:border-box;width:100%;height:100%;min-height:100dvh;overflow-y:auto;padding:calc(18px + env(safe-area-inset-top,0px)) 18px calc(24px + env(safe-area-inset-bottom,0px));border:0;border-radius:0;background:#f7f7f5;box-shadow:none;transform:translateY(8px);transition:transform .22s ease}
 .villa-mobile-search-overlay.is-open .villa-mobile-search-card{transform:translateY(0)}
 .villa-mobile-search-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:24px}
 .villa-mobile-search-heading{display:flex;flex-direction:column;gap:5px}
 .villa-mobile-search-kicker{font-size:10px;font-weight:750;letter-spacing:.16em;text-transform:uppercase;color:#888}
 .villa-mobile-search-head strong{font-size:24px;font-weight:750;letter-spacing:-.045em;color:#171717}
 .villa-mobile-search-close{flex:0 0 42px;width:42px;height:42px;border:1px solid #e5e5e2;border-radius:50%;background:#fff;color:#222;font-size:24px;line-height:1;cursor:pointer}
 .villa-mobile-search-form{position:relative;display:flex;align-items:center;gap:10px;margin-bottom:20px;padding:7px 8px 7px 16px;border:1px solid #e4e3df;border-radius:14px;background:#fff;box-shadow:0 5px 20px rgba(0,0,0,.035)}
 .villa-mobile-search-form:before{content:"";flex:0 0 18px;width:18px;height:18px;background:center/contain no-repeat url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23757575' stroke-width='1.8' stroke-linecap='round'%3E%3Ccircle cx='10.8' cy='10.8' r='6.8'/%3E%3Cpath d='m16 16 5 5'/%3E%3C/svg%3E")}
 .villa-mobile-search-form input{box-sizing:border-box;flex:1;min-width:0;height:44px;padding:0;border:0;background:transparent;color:#222;font:inherit;font-size:15px;outline:none}
 .villa-mobile-search-form input::placeholder{color:#999}
 .villa-mobile-search-form button{height:42px;padding:0 17px;border:0;border-radius:10px;background:#202020;color:#fff;font:inherit;font-size:12px;font-weight:700;white-space:nowrap;cursor:pointer}
 .villa-mobile-search-meta{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:0 2px 12px;color:#777;font-size:11px}
 .villa-mobile-search-meta strong{color:#333;font-size:11px;font-weight:700}
 .villa-mobile-search-results{display:grid;grid-template-columns:1fr;gap:10px}
 .villa-mobile-result{display:flex;align-items:center;gap:13px;min-width:0;padding:10px;border:1px solid #eae9e5;border-radius:15px;background:#fff;color:#222;text-decoration:none;box-shadow:0 3px 12px rgba(0,0,0,.025);transition:border-color .15s,transform .15s}
 .villa-mobile-result:active{transform:scale(.99);border-color:#c9c9c4}
 .villa-mobile-result-image{flex:0 0 78px;width:78px;height:72px;overflow:hidden;border-radius:10px;background:#ecebe7}
 .villa-mobile-result-image img{display:block;width:100%;height:100%;object-fit:cover}
 .villa-mobile-result-copy{flex:1;min-width:0;display:flex;flex-direction:column;gap:5px}
 .villa-mobile-result-title{overflow:hidden;color:#202020;font-size:13px;font-weight:750;line-height:1.3;text-overflow:ellipsis;white-space:nowrap}
 .villa-mobile-result-location{overflow:hidden;color:#858585;font-size:11px;text-overflow:ellipsis;white-space:nowrap}
 .villa-mobile-result-bottom{display:flex;align-items:center;gap:8px;color:#555;font-size:10px}
 .villa-mobile-result-price{color:#252525;font-size:11px;font-weight:750}
 .villa-mobile-result-arrow{flex:0 0 28px;width:28px;height:28px;display:grid;place-items:center;border-radius:50%;background:#f4f4f1;color:#444;font-size:17px}
 .villa-mobile-search-state{padding:28px 18px;border:1px dashed #d9d8d2;border-radius:14px;color:#777;text-align:center;font-size:13px;line-height:1.6}
 .villa-mobile-search-state strong{display:block;margin-bottom:5px;color:#333;font-size:14px}
}
@media(max-width:360px){.villa-mobile-bar{padding-left:4px;padding-right:4px}.villa-mobile-bar>a,.villa-mobile-bar>button,.villa-mobile-bar>span{min-height:58px}.villa-mobile-bar svg{width:21px;height:21px}.villa-mobile-bar a>span,.villa-mobile-bar button>span,.villa-mobile-bar>span>span{font-size:9px}}
@media(prefers-reduced-motion:reduce){.villa-mobile-search-overlay,.villa-mobile-search-card,.villa-mobile-bar>a,.villa-mobile-bar>button,.villa-mobile-result{transition:none}}
</style>
<nav class="villa-mobile-bar" aria-label="Mobil hızlı menü">
 <a href="<?= e(app_url('/')) ?>" aria-label="Ana Sayfa"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7"></path><path d="M5 9.5V21h14V9.5M9 21v-7h6v7"></path></svg><span>Ana Sayfa</span></a>
 <button type="button" data-villa-search-open aria-label="Villa adıyla ara"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 5 5"></path></svg><span>Villa Ara</span></button>
 <?php if ($mobileWhatsapp !== ''): ?><a class="villa-mobile-whatsapp" href="https://wa.me/<?= e($mobileWhatsapp) ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp ile iletişim"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.2 11.7a8.2 8.2 0 0 1-12.1 7.2L3 20l1.2-4.8a8.2 8.2 0 1 1 16-3.5Z"></path><path d="M8.4 8.1c.3-.6.6-.6.9-.6h.5c.2 0 .4.1.5.4l.8 1.8c.1.2.1.4-.1.6l-.6.7c-.2.2-.2.4 0 .7.5.9 1.3 1.6 2.2 2.1.2.1.4.1.6-.1l.8-.9c.2-.2.4-.2.6-.1l1.7.8c.3.1.4.3.4.5 0 .3-.2 1.1-.7 1.5-.5.5-1.2.7-1.9.6-1.1-.2-2.4-.8-3.7-2-1.1-1-2.1-2.5-2.3-3.5-.2-.8 0-1.6.3-2.1Z"></path></svg><span>WhatsApp</span></a><?php else: ?><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20.2 11.7a8.2 8.2 0 0 1-12.1 7.2L3 20l1.2-4.8a8.2 8.2 0 1 1 16-3.5Z"></path></svg><span>WhatsApp</span></span><?php endif; ?>
 <?php if ($mobilePhone !== ''): ?><a class="villa-mobile-phone" href="tel:<?= e($mobilePhone) ?>" aria-label="Telefonla ara"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3H4.8A1.8 1.8 0 0 0 3 4.8C3 13.7 10.3 21 19.2 21a1.8 1.8 0 0 0 1.8-1.8V17l-4.7-2-1.4 2a14.7 14.7 0 0 1-6-6l2-1.4L9 3H7Z"></path></svg><span>Ara</span></a><?php else: ?><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 3H4.8A1.8 1.8 0 0 0 3 4.8C3 13.7 10.3 21 19.2 21a1.8 1.8 0 0 0 1.8-1.8V17l-4.7-2-1.4 2a14.7 14.7 0 0 1-6-6l2-1.4L9 3H7Z"></path></svg><span>Ara</span></span><?php endif; ?>
</nav>
<div class="villa-mobile-search-overlay" data-villa-search-overlay aria-hidden="true"><section class="villa-mobile-search-card" role="dialog" aria-modal="true" aria-labelledby="villaMobileSearchTitle"><div class="villa-mobile-search-head"><div class="villa-mobile-search-heading"><span class="villa-mobile-search-kicker">Konaklama keşfi</span><strong id="villaMobileSearchTitle">Size uygun villayı bulun</strong></div><button type="button" class="villa-mobile-search-close" data-villa-search-close aria-label="Kapat">×</button></div><form class="villa-mobile-search-form" method="get" action="<?= e(app_url('/konaklamalar')) ?>"><input type="search" name="q" maxlength="120" placeholder="Villa adını yazmaya başlayın..." aria-label="Villa adıyla ara" autocomplete="off" required><button type="submit">Ara</button></form><div class="villa-mobile-search-meta"><strong data-villa-result-heading>Önerilen villalar</strong><span data-villa-result-count></span></div><div class="villa-mobile-search-results" data-villa-search-results aria-live="polite"><div class="villa-mobile-search-state"><strong>Hayalinizdeki villayı keşfedin</strong>Villa adını yazdığınızda eşleşen seçenekler burada listelenir.</div></div></section></div>
<script>
(function(){
 var overlay=document.querySelector('[data-villa-search-overlay]'),openButton=document.querySelector('[data-villa-search-open]');
 if(!overlay||!openButton||overlay.dataset.ready==='1')return;
 overlay.dataset.ready='1';
 var closeButton=overlay.querySelector('[data-villa-search-close]'),input=overlay.querySelector('input[name="q"]'),form=overlay.querySelector('.villa-mobile-search-form'),results=overlay.querySelector('[data-villa-search-results]'),heading=overlay.querySelector('[data-villa-result-heading]'),count=overlay.querySelector('[data-villa-result-count]');
 var endpoint=<?= json_encode(app_url('/api/villas/suggest'), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>,requestNo=0,debounce=null;
 function state(title,message){results.replaceChildren();var box=document.createElement('div');box.className='villa-mobile-search-state';var strong=document.createElement('strong');strong.textContent=title;box.appendChild(strong);box.appendChild(document.createTextNode(message));results.appendChild(box);count.textContent='';}
 function render(items){results.replaceChildren();if(!items.length){state('Eşleşen villa bulunamadı','Farklı bir villa adı deneyebilirsiniz.');return;}items.forEach(function(item){var a=document.createElement('a');a.className='villa-mobile-result';a.href=item.url;var media=document.createElement('span');media.className='villa-mobile-result-image';if(item.image){var img=document.createElement('img');img.src=item.image;img.alt='';img.loading='lazy';media.appendChild(img);}var copy=document.createElement('span');copy.className='villa-mobile-result-copy';var title=document.createElement('span');title.className='villa-mobile-result-title';title.textContent=item.title;var location=document.createElement('span');location.className='villa-mobile-result-location';location.textContent=[item.district,item.city].filter(Boolean).join(', ')||'Türkiye';var bottom=document.createElement('span');bottom.className='villa-mobile-result-bottom';var price=document.createElement('span');price.className='villa-mobile-result-price';price.textContent=item.price?item.price+' ₺ / gece':'Detayları görüntüle';var guests=document.createElement('span');guests.textContent=item.guests?item.guests+' kişi':'Villa';bottom.append(price,guests);copy.append(title,location,bottom);var arrow=document.createElement('span');arrow.className='villa-mobile-result-arrow';arrow.setAttribute('aria-hidden','true');arrow.textContent='›';a.append(media,copy,arrow);results.appendChild(a);});count.textContent=items.length+' sonuç';}
 async function search(){var term=input.value.trim(),id=++requestNo;if(term.length<1){heading.textContent='Önerilen villalar';state('Villa aramaya başlayın','Villa adını yazdığınızda eşleşen seçenekler alfabetik sırayla gösterilir.');return;}heading.textContent='Arama sonuçları';state('Villalar aranıyor','Eşleşen konaklamalar getiriliyor...');try{var response=await fetch(endpoint+'?q='+encodeURIComponent(term),{headers:{'Accept':'application/json'},credentials:'same-origin'});if(!response.ok)throw new Error('search');var data=await response.json();if(id!==requestNo)return;render(Array.isArray(data.items)?data.items:[]);}catch(error){if(id===requestNo)state('Arama şu anda yapılamıyor','Lütfen tekrar deneyin veya arama butonunu kullanın.');}}
 function close(){overlay.classList.remove('is-open');overlay.setAttribute('aria-hidden','true');document.body.style.overflow='';}
 function open(){overlay.classList.add('is-open');overlay.setAttribute('aria-hidden','false');document.body.style.overflow='hidden';window.setTimeout(function(){input.focus()},80);}
 openButton.addEventListener('click',open);closeButton.addEventListener('click',close);
 overlay.addEventListener('click',function(event){if(event.target===overlay)close();});
 input.addEventListener('input',function(){window.clearTimeout(debounce);debounce=window.setTimeout(search,180);});
 form.addEventListener('submit',function(event){if(!input.value.trim())return;});
 document.addEventListener('keydown',function(event){if(event.key==='Escape'&&overlay.classList.contains('is-open'))close();});
})();
</script>
<?php endif; ?>
