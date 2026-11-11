<div class="villa-login-overlay" id="villaLoginOverlay" hidden></div>
<section class="villa-login-modal" id="villaLoginModal" role="dialog" aria-modal="true" aria-labelledby="villaLoginTitle" hidden>
<div class="villa-login-card"><button type="button" class="villa-login-close" id="villaLoginClose" aria-label="Kapat">×</button>
<h2 id="villaLoginTitle">Tekrar hoş geldiniz</h2><p>Hesabınıza giriş yapın.</p>
<form id="villaAjaxLogin" method="post" action="<?= e(app_url('/login')) ?>" data-endpoint="<?= e(app_url('/login/ajax')) ?>">
<input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
<label for="villaLoginIdentity">E-posta veya kullanıcı adı</label><input id="villaLoginIdentity" name="identity" autocomplete="username" required>
<label for="villaLoginPassword">Şifre</label><input id="villaLoginPassword" name="password" type="password" autocomplete="current-password" required>
<div class="villa-login-error" id="villaLoginError" role="alert" hidden></div>
<button type="submit" class="villa-login-submit" id="villaLoginSubmit">Giriş Yap</button>
</form><div class="villa-login-foot">Hesabınız yok mu? <a href="<?= e(app_url('/register')) ?>">Misafir hesabı oluşturun</a></div>
</div></section>
<script>
(function(){
var modal=document.getElementById('villaLoginModal'),overlay=document.getElementById('villaLoginOverlay'),form=document.getElementById('villaAjaxLogin'),closeBtn=document.getElementById('villaLoginClose'),error=document.getElementById('villaLoginError'),submit=document.getElementById('villaLoginSubmit');
document.querySelectorAll('.nav-submenu-toggle').forEach(function(button){button.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();var entry=button.closest('.nav-entry'),open=!entry.classList.contains('is-open');var siblings=entry.parentElement?entry.parentElement.querySelectorAll(':scope > .nav-entry.is-open'):[];siblings.forEach(function(item){if(item!==entry){item.classList.remove('is-open');var b=item.querySelector(':scope > .nav-entry-top .nav-submenu-toggle');if(b)b.setAttribute('aria-expanded','false')}});entry.classList.toggle('is-open',open);button.setAttribute('aria-expanded',open?'true':'false');});});
document.addEventListener('click',function(e){if(!e.target.closest('.nav-entry'))document.querySelectorAll('.nav-entry.is-open').forEach(function(item){item.classList.remove('is-open');var b=item.querySelector(':scope > .nav-entry-top .nav-submenu-toggle');if(b)b.setAttribute('aria-expanded','false')});});\nif(!modal||!overlay||!form)return;
function open(){modal.hidden=false;overlay.hidden=false;document.body.classList.add('villa-login-open');error.hidden=true;window.setTimeout(function(){var el=document.getElementById('villaLoginIdentity');if(el)el.focus()},30)}
function close(){modal.hidden=true;overlay.hidden=true;document.body.classList.remove('villa-login-open')}
document.addEventListener('click',function(e){var trigger=e.target.closest('[data-login-trigger]');if(trigger){e.preventDefault();open();}});
overlay.addEventListener('click',close);closeBtn.addEventListener('click',close);
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!modal.hidden)close()});
form.addEventListener('submit',async function(e){
 e.preventDefault();error.hidden=true;submit.disabled=true;submit.textContent='Kontrol ediliyor…';
 try{
  var response=await fetch(form.dataset.endpoint,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
  var data=await response.json();
  if(!response.ok||!data.ok)throw new Error(data.message||'Giriş yapılamadı.');
  document.querySelectorAll('[data-auth-link]').forEach(function(link){link.textContent=data.user.label;link.href=data.user.url;link.removeAttribute('data-login-trigger');});
  close();form.reset();
  document.dispatchEvent(new CustomEvent('villa:login',{detail:data.user}));
 }catch(err){error.textContent=err.message||'Bir hata oluştu. Tekrar deneyin.';error.hidden=false;}
 finally{submit.disabled=false;submit.textContent='Giriş Yap';}
});
})();
</script>