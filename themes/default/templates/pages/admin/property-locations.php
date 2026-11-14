<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Toplu konum güncelleme</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f7f4;color:#26332b;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.wrap{max-width:900px;margin:32px auto;padding:0 18px}.panel{background:#fff;border:1px solid #e3eae4;border-radius:16px;padding:24px;margin:18px 0}.back{color:#176b50;text-decoration:none;font-weight:700}.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:20px 0}.stat{background:#f7faf7;border:1px solid #e4ebe5;border-radius:12px;padding:16px}.stat strong{display:block;font-size:28px;color:#176b50}.stat span{font-size:13px;color:#66756b}.btn{border:0;border-radius:9px;background:#176b50;color:white;padding:12px 18px;font-weight:750;cursor:pointer}.btn:disabled{opacity:.55;cursor:wait}.muted{color:#68776d;line-height:1.6}.progress{height:10px;background:#e9eee9;border-radius:20px;overflow:hidden;margin:16px 0}.progress>span{display:block;height:100%;width:0;background:#23845f;transition:width .2s}.log{max-height:260px;overflow:auto;background:#f8faf8;border:1px solid #e5ebe5;border-radius:10px;padding:12px;font-size:13px}.log p{margin:5px 0}.success{color:#176b50}.error{color:#a33b2c}@media(max-width:620px){.wrap{margin:18px auto;padding:0 12px}.panel{padding:18px}.stats{grid-template-columns:1fr}.stat{padding:12px}.stat strong{font-size:24px}}
</style>
</head>
<body>
<main class="wrap" data-process-url="<?= e(app_url('/admin/properties/locations/process')) ?>" data-list-url="<?= e(app_url('/admin/properties')) ?>">
<a class="back" href="<?= e(app_url('/admin/properties')) ?>">← Konaklamalara dön</a>
<section class="panel">
<h1 style="margin:0 0 8px">Toplu konum güncelleme</h1>
<p class="muted">Adres bilgisi bulunan eski villaların koordinatlarını Photon / OpenStreetMap üzerinden bulur ve yeni harita görünümüne hazırlar. İşlem küçük partiler halinde yürür; sayfayı açık tutmanız yeterlidir.</p>
<div class="stats">
<div class="stat"><strong><?= (int)$missingCount ?></strong><span>Koordinatı eksik villa</span></div>
<div class="stat"><strong><?= (int)$partialCount ?></strong><span>Tek koordinatı eksik kayıt (korunur)</span></div>
<div class="stat"><strong><?= (int)$noAddressCount ?></strong><span>Yeterli adres ayrıntısı olmayan kayıt</span></div>
</div>
<p class="muted"><strong>Güvenlik:</strong> Yalnızca hem enlem hem boylamı boş olan kayıtlar işlenir. Önceden konum girilmiş villaların koordinatları değiştirilmez. Adres bulunamayan kayıtlar hata listesinde gösterilir.</p>
<button class="btn" id="start" type="button" <?= $missingCount < 1 ? 'disabled' : '' ?>>Eksik konumları tamamla</button>
<div class="progress" aria-label="İşlem ilerlemesi"><span id="bar"></span></div>
<p id="status" class="muted" aria-live="polite">İşlem başlatılmaya hazır.</p>
<div id="log" class="log" hidden></div>
<input type="hidden" id="csrf" value="<?= e(csrf_token()) ?>">
</section>
</main>
<script>
(function(){
const root=document.querySelector('main[data-process-url]'),button=document.getElementById('start'),bar=document.getElementById('bar'),status=document.getElementById('status'),log=document.getElementById('log');
if(!root||!button)return;
const initialMissing=<?= (int)$missingCount ?>;
let afterId=0,processed=0,updated=0,failures=[];
function addLog(message,kind){log.hidden=false;const p=document.createElement('p');p.className=kind||'';p.textContent=message;log.appendChild(p);log.scrollTop=log.scrollHeight;}
button.addEventListener('click',async function(){
if(!confirm('Koordinatı eksik villaların adresleri harita servisinde aranacak. Mevcut koordinatlar korunacak. Devam edilsin mi?'))return;
button.disabled=true;button.textContent='Konumlar işleniyor…';log.innerHTML='';log.hidden=true;afterId=0;processed=0;updated=0;failures=[];
try{
while(true){
const form=new FormData();form.append('_csrf',document.getElementById('csrf').value);form.append('after_id',String(afterId));
const response=await fetch(root.dataset.processUrl,{method:'POST',body:form,credentials:'same-origin',headers:{'Accept':'application/json'}});
if(!response.ok)throw new Error('Sunucu işlemi tamamlayamadı (HTTP '+response.status+').');
const data=await response.json();if(!data.ok)throw new Error(data.message||'İşlem başarısız.');
processed+=Number(data.processed||0);updated+=Number(data.updated||0);failures=failures.concat(data.failed||[]);afterId=Number(data.last_id||afterId);
const percent=initialMissing?Math.min(100,Math.round(processed/initialMissing*100)):100;bar.style.width=percent+'%';
status.textContent='Taranan: '+processed+' / '+initialMissing+' · Güncellenen: '+updated+' · Kalan: '+Number(data.remaining||0);
(data.failed||[]).forEach(item=>addLog('#'+item.id+' '+item.title+': '+item.reason,'error'));
if(data.done||Number(data.processed)===0)break;
}
status.textContent='İşlem tamamlandı. Güncellenen: '+updated+' · Koordinat bulunamayan: '+failures.length;
addLog('Toplu işlem tamamlandı.','success');
if(failures.length)addLog('Koordinatı bulunamayan kayıtları villa düzenleme ekranından adres seçerek tamamlayabilirsiniz.','error');
button.textContent='İşlem tamamlandı';button.disabled=true;
}catch(error){status.textContent=error.message;addLog(error.message,'error');button.disabled=false;button.textContent='Tekrar dene';}
});
})();
</script>
</body>
</html>
