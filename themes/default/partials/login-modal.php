<div class="villa-login-overlay" id="villaLoginOverlay" hidden></div>
<section class="villa-login-modal" id="villaLoginModal" role="dialog" aria-modal="true" aria-labelledby="villaLoginTitle" hidden>
<div class="villa-login-card"><button type="button" class="villa-login-close" id="villaLoginClose" aria-label="Kapat">×</button>
<h2 id="villaLoginTitle">Tekrar hoş geldiniz</h2><p>Hesabınıza giriş yapın.</p>
<form id="villaAjaxLogin" method="post" action="<?= e(app_url('/login')) ?>" data-endpoint="<?= e(app_url('/login/ajax')) ?>">
<input type="hidden" name="_csrf" value="<?= e((string)($loginCsrfToken ?? csrf_token())) ?>">
<label for="villaLoginIdentity">E-posta veya kullanıcı adı</label><input id="villaLoginIdentity" name="identity" autocomplete="username" required>
<label for="villaLoginPassword">Şifre</label><input id="villaLoginPassword" name="password" type="password" autocomplete="current-password" required>
<div class="villa-login-error" id="villaLoginError" role="alert" hidden></div>
<button type="submit" class="villa-login-submit" id="villaLoginSubmit">Giriş Yap</button>
</form><div class="villa-login-foot">Hesabınız yok mu? <a href="<?= e(app_url('/register')) ?>">Misafir hesabı oluşturun</a></div>
</div></section>
<style>
.villa-login-overlay{position:fixed;inset:0;z-index:5000;background:rgba(13,28,23,.58);backdrop-filter:blur(4px);opacity:0;transition:opacity .22s ease}
.villa-login-overlay:not([hidden]){opacity:1}
.villa-login-modal{position:fixed;inset:0;z-index:5001;display:grid;place-items:center;padding:18px;pointer-events:none}
.villa-login-modal[hidden],.villa-login-overlay[hidden]{display:none}
.villa-login-card{pointer-events:auto;position:relative;width:min(440px,100%);padding:30px;background:#fff;border-radius:22px;box-shadow:0 25px 80px #071b1438;transform:translateY(12px) scale(.98);opacity:0;transition:transform .32s cubic-bezier(.22,.75,.25,1),opacity .28s ease}
.villa-login-open .villa-login-card{transform:translateY(0) scale(1);opacity:1}
.villa-login-card h2{margin:0 0 7px;font-size:24px;color:#20332c}
.villa-login-card>p{margin:0 0 20px;color:#718078}
.villa-login-card label{display:block;margin:14px 0 6px;font-size:13px;font-weight:650}
.villa-login-card input{width:100%;box-sizing:border-box;padding:13px;border:1px solid #dfe8e2;border-radius:10px;font:inherit}
.villa-login-submit{width:100%;margin-top:20px;padding:14px;border:0;border-radius:11px;background:#167c80;color:#fff;font-weight:700;cursor:pointer}
.villa-login-close{position:absolute;right:14px;top:12px;width:36px;height:36px;border:0;border-radius:50%;background:#f1f5f2;color:#20332c;font-size:24px;cursor:pointer}
.villa-login-error{margin-top:12px;padding:10px 12px;border-radius:9px;background:#fff0ef;color:#a32b20;font-size:13px}
.villa-login-foot{margin-top:17px;text-align:center;font-size:13px;color:#718078}
.villa-login-foot a{color:#167c80;font-weight:700}
@media(max-width:600px){.villa-login-modal{align-items:end;padding:0 12px calc(76px + env(safe-area-inset-bottom))}.villa-login-card{width:100%;max-width:480px;padding:26px 21px 22px;border-radius:22px 22px 18px 18px;transform:translateY(calc(100% + 24px));max-height:calc(100dvh - 112px);overflow-y:auto}.villa-login-open .villa-login-card{transform:translateY(0)}}
@media(prefers-reduced-motion:reduce){.villa-login-card,.villa-login-overlay{transition:none}}
</style>
<style>
.villa-logout-form{display:inline-flex;align-items:center;margin:0}
.villa-logout-button{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border:1px solid #f0d9d6;border-radius:10px;background:#fff8f7;color:var(--villa-text);font:inherit;font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);cursor:pointer;transition:background .18s ease,border-color .18s ease}
.villa-logout-button:hover{background:#fff0ee;border-color:#e9c2bd}
.nav-logout-entry{display:flex;align-items:center}
@media(max-width:600px){.villa-logout-form{width:100%}.villa-logout-button{width:100%;justify-content:flex-start;padding:14px 4px;border:0;border-bottom:1px solid var(--line,#e8eeea);border-radius:0;background:transparent;color:var(--villa-text);text-align:left}}
</style>
<script>
(function(){
var modal=document.getElementById('villaLoginModal'),overlay=document.getElementById('villaLoginOverlay'),form=document.getElementById('villaAjaxLogin'),closeBtn=document.getElementById('villaLoginClose'),error=document.getElementById('villaLoginError'),submit=document.getElementById('villaLoginSubmit');
document.querySelectorAll('.nav-submenu-toggle').forEach(function(button){button.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();var entry=button.closest('.nav-entry'),open=!entry.classList.contains('is-open');var siblings=entry.parentElement?entry.parentElement.querySelectorAll(':scope > .nav-entry.is-open'):[];siblings.forEach(function(item){if(item!==entry){item.classList.remove('is-open');var b=item.querySelector(':scope > .nav-entry-top .nav-submenu-toggle');if(b)b.setAttribute('aria-expanded','false')}});entry.classList.toggle('is-open',open);button.setAttribute('aria-expanded',open?'true':'false');});});
document.addEventListener('click',function(e){if(!e.target.closest('.nav-entry'))document.querySelectorAll('.nav-entry.is-open').forEach(function(item){item.classList.remove('is-open');var b=item.querySelector(':scope > .nav-entry-top .nav-submenu-toggle');if(b)b.setAttribute('aria-expanded','false')});});
if(!modal||!overlay||!form)return;
function open(){modal.hidden=false;overlay.hidden=false;document.body.classList.add('villa-login-open');error.hidden=true;window.setTimeout(function(){var el=document.getElementById('villaLoginIdentity');if(el)el.focus()},30)}
function close(){modal.hidden=true;overlay.hidden=true;document.body.classList.remove('villa-login-open')}
document.addEventListener('click',function(e){var trigger=e.target.closest('[data-login-trigger]');if(trigger){e.preventDefault();open();}});
overlay.addEventListener('click',close);closeBtn.addEventListener('click',close);
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!modal.hidden)close()});
form.addEventListener('submit',async function(e){
 e.preventDefault();error.hidden=true;submit.disabled=true;submit.textContent='Kontrol ediliyor…';
 try{
  var response=await fetch(form.dataset.endpoint,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
  var contentType=(response.headers.get('content-type')||'').toLowerCase();
  if(!contentType.includes('application/json'))throw new Error('Giriş servisi beklenen yanıtı vermedi. Sayfa yenilenmedi; lütfen tekrar deneyin.');
  var data;
  try{data=await response.json()}catch(parseError){throw new Error('Sunucudan geçerli yanıt alınamadı. Sayfa yenilenmedi; tekrar deneyin.');}
  if(!response.ok||!data.ok)throw new Error(data.message||'Giriş yapılamadı.');
  document.querySelectorAll('[data-auth-link], [data-login-trigger]').forEach(function(link){
   var accountLink=link;
   if(link.tagName==='BUTTON'){
    accountLink=document.createElement('a');
    accountLink.className=link.className;
    accountLink.innerHTML=link.innerHTML;
    link.replaceWith(accountLink);
   }
   accountLink.href=data.user.url;
   accountLink.setAttribute('data-auth-link','');
   accountLink.removeAttribute('data-login-trigger');
   accountLink.setAttribute('aria-label',data.user.label);
   accountLink.setAttribute('title',data.user.label);
   var label=accountLink.querySelector('span');
   if(label)label.textContent=data.user.label;
  });
  close();form.reset();
  document.dispatchEvent(new CustomEvent('villa:login',{detail:data.user}));
 }catch(err){error.textContent=err.message||'Bir hata oluştu. Tekrar deneyin.';error.hidden=false;}
 finally{submit.disabled=false;submit.textContent='Giriş Yap';}
});
})();
</script>