<?php declare(strict_types=1); ?>
<style>
.vr-seo-panel{margin:16px 0;padding:18px;border:1px solid #e2e9e4;border-radius:14px;background:#fff;min-width:0}
.vr-seo-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}.vr-seo-head h3{margin:0;font-size:17px}
.vr-seo-score{display:flex;align-items:center;gap:10px}.vr-seo-score strong{font-size:27px;color:var(--villa-admin-primary,#176b50)}
.vr-seo-track{height:8px;overflow:hidden;border-radius:20px;background:#edf1ee;margin:12px 0}.vr-seo-track span{display:block;height:100%;width:0;background:var(--villa-admin-primary,#176b50);transition:width .2s ease}
.vr-seo-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px}.vr-seo-checks{display:grid;gap:7px;margin:10px 0 0;padding:0;list-style:none}
.vr-seo-checks li{display:flex;gap:8px;font-size:12px;line-height:1.5;color:#69776f}.vr-seo-checks li:before{content:"○";font-weight:800;color:#a36b36}.vr-seo-checks li.pass:before{content:"✓";color:var(--villa-admin-primary,#176b50)}
.vr-seo-preview{padding:13px;border:1px solid #e5e9e6;border-radius:10px;background:#fbfcfb;min-width:0}.vr-seo-preview small{display:block;color:#69776f;font-size:10px;margin-bottom:7px}.vr-seo-preview strong{display:block;color:#1a0dab;font-size:16px;font-weight:500;line-height:1.35;overflow-wrap:anywhere}.vr-seo-preview a{display:block;color:#188038;font-size:11px;margin:4px 0;overflow-wrap:anywhere}.vr-seo-preview p{margin:0;color:#4b5156;font-size:12px;line-height:1.5}.vr-seo-note{margin:10px 0 0;color:#78857d;font-size:11px;line-height:1.5}
@media(max-width:700px){.vr-seo-panel{padding:13px}.vr-seo-grid{grid-template-columns:minmax(0,1fr)}}
</style>
<section class="vr-seo-panel" data-vr-seo-analyzer aria-live="polite">
 <div class="vr-seo-head"><h3>SEO analiz ve puanlama</h3><div class="vr-seo-score"><strong data-seo-score>0</strong><span>/ 100 puan</span></div></div>
 <div class="vr-seo-track"><span data-seo-progress></span></div>
 <div class="vr-seo-grid"><div><strong style="font-size:12px">Kontrol listesi</strong><ul class="vr-seo-checks" data-seo-checks><li>SEO analizi hazırlanıyor…</li></ul></div>
 <div><strong style="font-size:12px">Google arama önizlemesi</strong><div class="vr-seo-preview"><small>Arama sonucu önizlemesi</small><strong data-seo-preview-title>Sayfa başlığı</strong><a data-seo-preview-url href="#" tabindex="-1">site.com/sayfa</a><p data-seo-preview-description>Meta açıklaması burada görünecek.</p></div></div></div>
 <p class="vr-seo-note">Bu puan editör için içerik ve teknik kontrol rehberidir; Google sıralama puanı veya sıralama garantisi değildir.</p>
</section>
<script>
(function(){
 function init(panel){
  var form=panel.closest('form');if(!form||panel.dataset.ready)return;panel.dataset.ready='1';
  var field=function(n){return form.querySelector('[name="'+n+'"]')};
  var title=field('title'),seoTitle=field('meta_title'),description=field('meta_description'),keyword=field('focus_keyword'),slug=field('slug'),content=field('content'),imageAlt=field('featured_image_alt'),image=field('featured_image');
  var scoreNode=panel.querySelector('[data-seo-score]'),progress=panel.querySelector('[data-seo-progress]'),list=panel.querySelector('[data-seo-checks]'),previewTitle=panel.querySelector('[data-seo-preview-title]'),previewUrl=panel.querySelector('[data-seo-preview-url]'),previewDescription=panel.querySelector('[data-seo-preview-description]');
  function plain(html){var box=document.createElement('div');box.innerHTML=html||'';return (box.textContent||box.innerText||'').replace(/\s+/g,' ').trim()}
  function run(){
   var t=(title&&title.value||'').trim(),st=(seoTitle&&seoTitle.value||t).trim(),d=(description&&description.value||'').trim(),k=(keyword&&keyword.value||'').trim().toLocaleLowerCase('tr'),s=(slug&&slug.value||'').trim().toLocaleLowerCase('tr'),raw=content&&content.value||'',txt=plain(raw),words=txt?txt.split(/\s+/).length:0,lower=txt.toLocaleLowerCase('tr'),checks=[],score=0;
   function add(label,points,ok,tip){score+=ok?points:0;checks.push({label:label,ok:ok,tip:tip})}
   add('SEO başlığı 30–60 karakter',15,st.length>=30&&st.length<=60,'Başlığı 30–60 karaktere yaklaştırın.');
   add('Meta açıklaması 120–160 karakter',15,d.length>=120&&d.length<=160,'Açıklamayı 120–160 karakter arasında yazın.');
   add('Odak anahtar kelime belirlenmiş',5,k.length>=2,'Bir ana odak kelime belirleyin.');
   add('Anahtar kelime başlıkta ve açıklamada',10,k.length>1&&t.toLocaleLowerCase('tr').includes(k)&&d.toLocaleLowerCase('tr').includes(k),'Odak kelimeyi başlık ve açıklamada doğal kullanın.');
   add('İçerikte anahtar kelime kullanımı',5,k.length>1&&lower.includes(k),'Odak kelimeyi içerikte doğal biçimde kullanın.');
   var h2=(raw.match(/<h2\b/gi)||[]).length,h3=(raw.match(/<h3\b/gi)||[]).length;
   add('İçerik H2/H3 alt başlıklarla bölünmüş',10,h2+h3>=2,'İçeriği en az iki anlamlı alt başlığa ayırın.');
   add('İçerik yeterli uzunlukta (300+ kelime)',10,words>=300,'Özgün ve faydalı en az 300 kelimelik içerik hazırlayın.');
   var box=document.createElement('div');box.innerHTML=raw;var imgs=Array.from(box.querySelectorAll('img')),missing=imgs.filter(function(el){return !el.getAttribute('alt')||!el.getAttribute('alt').trim()}).length;
   add('Görsellerin alternatif metinleri tamam',10,(!image||!image.value.trim()||(imageAlt&&imageAlt.value.trim()))&&(imgs.length?missing===0:true),'Görsellere açıklayıcı alt metin ekleyin.');
   var links=Array.from(box.querySelectorAll('a[href]')),internal=links.some(function(a){return a.getAttribute('href').startsWith('/')||a.hostname===location.hostname}),external=links.some(function(a){return /^https?:/i.test(a.href)&&a.hostname!==location.hostname});
   add('İç ve dış bağlantılar mevcut',5,internal&&external,'İlgili site içi ve güvenilir dış bağlantı ekleyin.');
   add('URL kısa ve anahtar kelimeyle ilişkili',5,s.length>=3&&s.length<=75&&(!k||s.includes(k.replace(/\s+/g,'-'))),'Kısa URL kullanın ve mümkünse odak kelimeyi ekleyin.');
   add('Başlık ve içerik okunabilir',5,t.length>=15&&words>=100&&h2+h3>=1,'Açıklayıcı başlık ve taranabilir paragraflar kullanın.');
   add('Canonical ve indexability etiketleri otomatik hazırlanıyor',5,true,'Yayınlanan sayfalarda canonical ve robots etiketleri sistem tarafından eklenir.');
   score=Math.max(0,Math.min(100,score));scoreNode.textContent=String(score);progress.style.width=score+'%';progress.style.background=score>=80?'var(--villa-admin-primary,#176b50)':(score>=50?'#b88635':'#b45b50');
   list.innerHTML='';checks.forEach(function(c){var li=document.createElement('li');if(c.ok)li.className='pass';li.textContent=(c.ok?'Tamamlandı: ':'İyileştir: ')+c.label+(c.ok?'':' — '+c.tip);list.appendChild(li)});
   previewTitle.textContent=st||t||'Sayfa başlığı';previewUrl.textContent=(location.host||'site.com')+'/'+(s||'sayfa');previewUrl.href='#';previewDescription.textContent=d||txt.slice(0,155)||'Meta açıklaması burada görünecek.';
  }
  [title,seoTitle,description,keyword,slug,content,imageAlt,image].forEach(function(el){if(el)el.addEventListener('input',run)});run();
 }
 function start(){document.querySelectorAll('[data-vr-seo-analyzer]').forEach(init)}
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
})();
</script>
