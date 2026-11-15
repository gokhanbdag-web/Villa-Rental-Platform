<?php
declare(strict_types=1);
$comparePageUrl = app_url('/karsilastir');
?>
<style>
.villa-compare-bar{position:fixed;z-index:8500;left:50%;bottom:18px;transform:translateX(-50%);width:min(620px,calc(100% - 28px));display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 14px 12px 18px;border:1px solid #dce5df;border-radius:18px;background:#fff;color:#20332c;box-shadow:0 12px 42px #17251d2b;font:inherit}
.villa-compare-bar[hidden]{display:none}.villa-compare-bar-copy{min-width:0}.villa-compare-bar-copy strong{display:block;font-size:14px}.villa-compare-bar-copy span{display:block;margin-top:3px;color:#68766e;font-size:12px}
.villa-compare-bar-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}.villa-compare-open{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:0 16px;border:0;border-radius:12px;background:#203d32;color:#fff;text-decoration:none;font:inherit;font-size:13px;font-weight:700;white-space:nowrap}.villa-compare-open[aria-disabled=true]{opacity:.45;pointer-events:none}
.villa-compare-clear{width:38px;height:38px;border:1px solid #dce5df;border-radius:11px;background:#fff;color:#526158;font-size:20px;cursor:pointer}
.card-compare-button{position:absolute;right:12px;bottom:12px;z-index:4;width:38px;height:38px;border:1px solid #fff;border-radius:50%;background:#fff;color:#263a30;box-shadow:0 3px 12px #0002;font-size:21px;line-height:1;cursor:pointer}.card-compare-button.is-active,.property-compare-button.is-active{background:#203d32;color:#fff;border-color:#203d32}
.property-compare-button{display:inline-flex;align-items:center;gap:8px;margin:18px 0;padding:10px 15px;border:1px solid #dfe6e0;border-radius:10px;background:#fff;color:#20332c;font:inherit;cursor:pointer}
.villa-compare-toast{position:fixed;z-index:8600;left:50%;bottom:94px;transform:translateX(-50%);max-width:calc(100% - 32px);padding:10px 15px;border-radius:12px;background:#20332c;color:#fff;font-size:13px;box-shadow:0 8px 24px #0002}
@media(max-width:560px){.villa-compare-bar{bottom:calc(82px + env(safe-area-inset-bottom,0px) + 12px);padding:10px 11px 10px 14px;border-radius:15px}.villa-compare-bar-copy strong{font-size:13px}.villa-compare-bar-copy span{font-size:11px}.villa-compare-open{min-height:40px;padding:0 12px;font-size:12px}.villa-compare-clear{width:35px;height:35px}}
</style>
<aside class="villa-compare-bar" data-villa-compare-bar data-compare-url="<?= e($comparePageUrl) ?>" hidden aria-live="polite">
 <div class="villa-compare-bar-copy"><strong><span data-compare-count>0</span> villa seçildi</strong><span data-compare-hint>Karşılaştırmak için en az 2 villa seçin. En fazla 4 villa ekleyebilirsiniz.</span></div>
 <div class="villa-compare-bar-actions"><button class="villa-compare-clear" type="button" data-compare-clear aria-label="Karşılaştırma listesini temizle" title="Temizle">×</button><a class="villa-compare-open" data-compare-open href="<?= e($comparePageUrl) ?>" aria-disabled="true">Karşılaştır</a></div>
</aside>
<script>
(function(){
 if(window.villaCompareReady)return;window.villaCompareReady=true;
 var key='villaCompareItemsV1',bar=document.querySelector('[data-villa-compare-bar]'),count=bar&&bar.querySelector('[data-compare-count]'),hint=bar&&bar.querySelector('[data-compare-hint]'),open=bar&&bar.querySelector('[data-compare-open]');
 function read(){try{var v=JSON.parse(localStorage.getItem(key)||'[]');return Array.isArray(v)?v.filter(function(x){return x&&Number(x.id)>0}).slice(0,4):[]}catch(e){return []}}
 function write(items){try{localStorage.setItem(key,JSON.stringify(items))}catch(e){}}
 function url(items){return (bar?bar.dataset.compareUrl:'/karsilastir')+(items.length?'?ids='+items.map(function(x){return Number(x.id)}).join(','):'')}
 function sync(){var items=read(),ids=items.map(function(x){return String(x.id)});document.querySelectorAll('[data-compare-property]').forEach(function(button){var active=ids.indexOf(String(button.dataset.propertyId))!==-1;button.classList.toggle('is-active',active);button.setAttribute('aria-pressed',active?'true':'false');if(button.classList.contains('card-compare-button'))button.title=active?'Karşılaştırmadan çıkar':'Karşılaştırmaya ekle';else button.textContent=active?'✓ Karşılaştırmada':'⇄ Karşılaştırmaya ekle'});if(bar){bar.hidden=items.length===0;count.textContent=String(items.length);hint.textContent=items.length<2?'Karşılaştırmak için en az 2 villa seçin. En fazla 4 villa ekleyebilirsiniz.':'Villaları yan yana incelemeye hazırsınız.';open.href=url(items);open.setAttribute('aria-disabled',items.length>=2?'false':'true')}}
 function toast(message){var el=document.querySelector('.villa-compare-toast');if(!el){el=document.createElement('div');el.className='villa-compare-toast';el.setAttribute('role','status');document.body.appendChild(el)}el.textContent=message;el.hidden=false;clearTimeout(el._timer);el._timer=setTimeout(function(){el.hidden=true},2200)}
 document.addEventListener('click',function(event){
  var button=event.target.closest('[data-compare-property]');
  if(button){event.preventDefault();var items=read(),id=Number(button.dataset.propertyId),index=items.findIndex(function(x){return Number(x.id)===id});
   if(index>=0){items.splice(index,1);write(items);sync();toast('Villa karşılaştırma listenizden çıkarıldı.');return}
   if(items.length>=4){toast('En fazla 4 villa karşılaştırabilirsiniz.');return}
   items.push({id:id,title:button.dataset.propertyTitle||'',url:button.dataset.propertyUrl||'',image:button.dataset.propertyImage||'',price:button.dataset.propertyPrice||'',currency:button.dataset.propertyCurrency||'TRY',location:button.dataset.propertyLocation||'',guests:button.dataset.propertyGuests||''});
   write(items);sync();toast('Villa karşılaştırma listenize eklendi.');return;
  }
  if(event.target.closest('[data-compare-clear]')){write([]);sync();if(bar&&window.location.pathname===new URL(bar.dataset.compareUrl,window.location.origin).pathname){window.location.href=url([]);return}toast('Karşılaştırma listeniz temizlendi.');}
  var remove=event.target.closest('[data-compare-remove-id]');
  if(remove){var rid=Number(remove.dataset.compareRemoveId),next=read().filter(function(x){return Number(x.id)!==rid});write(next);window.location.href=url(next)}
 });
 if(open)open.addEventListener('click',function(event){if(read().length<2){event.preventDefault();toast('Karşılaştırmak için en az 2 villa seçin.')}});
 window.addEventListener('storage',sync);sync();
})();
</script>
