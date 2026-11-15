<?php
declare(strict_types=1);

// Session-bound login token and account details must never be cached across auth changes.
if (!headers_sent()) {
    header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}
$drawerUser = is_array($user ?? null) ? $user : null;
if ($drawerUser === null) {
    try {
        $resolvedDrawerUser = \App\Core\Auth::user();
        $drawerUser = is_array($resolvedDrawerUser) ? $resolvedDrawerUser : null;
    } catch (Throwable $drawerAuthError) {
        $drawerUser = null;
    }
}
$drawerRole = (string)($drawerUser['role'] ?? '');
$drawerName = trim((string)($drawerUser['username'] ?? $drawerUser['name'] ?? $drawerUser['email'] ?? ''));
$drawerMenus = [];
if ($drawerUser !== null) {
    if (in_array($drawerRole, ['super_admin', 'admin'], true)) {
        $drawerMenus = [
            ['label'=>'Genel Bakış','url'=>'/admin','icon'=>'⌂'],
            ['label'=>'Rezervasyonlar','url'=>'/admin/bookings','icon'=>'▣'],
            ['label'=>'Villalar','url'=>'/admin/properties','icon'=>'⌂'],
            ['label'=>'Ev Sahipleri','url'=>'/admin/vendors','icon'=>'♧'],
            ['label'=>'Kullanıcılar','url'=>'/admin/users','icon'=>'♙'],
            ['label'=>'Mesajlar','url'=>'/admin/messages','icon'=>'✉'],
            ['label'=>'İçerik Yönetimi','url'=>'/admin/content','icon'=>'▤'],
            ['label'=>'Ayarlar','url'=>'/admin/settings','icon'=>'⚙'],
        ];
        if ($drawerRole === 'super_admin') $drawerMenus[] = ['label'=>'Sistem Tanılama','url'=>'/admin/performance/diagnostics','icon'=>'⌁'];
    } elseif ($drawerRole === 'vendor') {
        $drawerMenus = [
            ['label'=>'Ev Sahibi Paneli','url'=>'/vendor','icon'=>'⌂'],
            ['label'=>'Rezervasyonlarım','url'=>'/vendor/bookings','icon'=>'▣'],
            ['label'=>'Villalarım','url'=>'/vendor/properties','icon'=>'⌂'],
            ['label'=>'Yeni Villa Ekle','url'=>'/vendor/properties/new','icon'=>'+'],
        ];
    } else {
        $drawerMenus = [
            ['label'=>'Hesabım','url'=>'/account/bookings','icon'=>'♙'],
            ['label'=>'Rezervasyonlarım','url'=>'/account/bookings','icon'=>'▣'],
            ['label'=>'Favorilerim','url'=>'/favorites','icon'=>'♡'],
            ['label'=>'Bildirimler','url'=>'/account/notifications','icon'=>'♧'],
            ['label'=>'Yorumlarım','url'=>'/account/reviews','icon'=>'☆'],
        ];
    }
}
?>
<div class="villa-account-drawer-backdrop" id="villaAccountDrawerBackdrop" hidden></div>
<aside class="villa-account-drawer" id="villaAccountDrawer" role="dialog" aria-modal="true" aria-labelledby="villaAccountDrawerTitle" aria-hidden="true" hidden>
  <div class="villa-account-drawer-head">
    <div class="villa-account-drawer-heading">
      <span class="villa-account-drawer-kicker"><?= $drawerUser !== null ? 'HESAP MENÜSÜ' : 'VILLACMS HESAP' ?></span>
      <h2 id="villaAccountDrawerTitle"><?= $drawerUser !== null ? 'Hoş geldiniz' : 'Tekrar hoş geldiniz' ?></h2>
      <p><?= $drawerUser !== null ? e($drawerName !== '' ? $drawerName : 'Hesabınız') : 'Hesabınıza giriş yapın.' ?></p>
    </div>
    <button type="button" class="villa-account-drawer-close" id="villaAccountDrawerClose" aria-label="Kapat">×</button>
  </div>

  <?php if ($drawerUser !== null): ?>
    <div class="villa-account-drawer-role"><?= e(match($drawerRole) {'super_admin'=>'Süper Yönetici','admin'=>'Yönetici','vendor'=>'Ev Sahibi',default=>'Misafir'}) ?></div>
    <nav class="villa-account-drawer-menu" aria-label="Hesap menüsü">
      <?php foreach ($drawerMenus as $item): ?>
        <a href="<?= e(app_url($item['url'])) ?>"><span class="villa-account-drawer-icon" aria-hidden="true"><?= e($item['icon']) ?></span><span><?= e($item['label']) ?></span><span class="villa-account-drawer-arrow" aria-hidden="true">›</span></a>
      <?php endforeach; ?>
    </nav>
    <div class="villa-account-drawer-footer">
      <a class="villa-account-drawer-home" href="<?= e(app_url('/')) ?>">Siteyi görüntüle <span aria-hidden="true">↗</span></a>
      <form method="post" action="<?= e(app_url('/logout')) ?>" class="villa-account-drawer-logout">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <button type="submit"><span aria-hidden="true">↪</span> Güvenli Çıkış Yap</button>
      </form>
    </div>
  <?php else: ?>
    <form id="villaLoginForm" class="villa-account-drawer-login" method="post" action="<?= e(app_url('/login')) ?>" data-endpoint="<?= e(app_url('/login/ajax')) ?>">
      <input type="hidden" name="_csrf" value="<?= e((string)($loginCsrfToken ?? csrf_token())) ?>">
      <input type="hidden" name="return_to" value="">
      <input type="hidden" name="favorite_property_id" value="">
      <div class="villa-login-ajax-error" id="villaLoginAjaxError" role="alert" hidden></div>
      <label for="villaLoginIdentity">E-posta veya kullanıcı adı</label>
      <input id="villaLoginIdentity" name="identity" autocomplete="username" required>
      <label for="villaLoginPassword">Şifre</label>
      <input id="villaLoginPassword" name="password" type="password" autocomplete="current-password" required>
      <button type="submit" class="villa-login-submit">Giriş Yap</button>
    </form>
    <div class="villa-login-foot">Hesabınız yok mu? <a href="<?= e(app_url('/register')) ?>">Misafir hesabı oluşturun</a></div>
  <?php endif; ?>
</aside>
<script id="villa-ajax-login-handler">
(function(){
 var form=document.getElementById('villaLoginForm');
 var drawer=document.getElementById('villaAccountDrawer');
 if(!form||!drawer||form.dataset.ajaxBound==='1')return;
 form.dataset.ajaxBound='1';
 var submit=form.querySelector('button[type="submit"]');
 var errorBox=document.getElementById('villaLoginAjaxError');
 function appPath(path){
  var base=form.action.replace(/\/login(?:\?.*)?$/,'');
  return base+path;
 }
 function element(tag,className,text){
  var node=document.createElement(tag);
  if(className)node.className=className;
  if(text!==undefined)node.textContent=text;
  return node;
 }
 function renderAccount(data){
  var head=drawer.querySelector('.villa-account-drawer-head');
  var kicker=head.querySelector('.villa-account-drawer-kicker');
  var title=head.querySelector('#villaAccountDrawerTitle');
  var name=head.querySelector('.villa-account-drawer-heading p');
  kicker.textContent='HESAP MENÜSÜ';
  title.textContent='Hoş geldiniz';
  name.textContent=data.user.label||'Hesabınız';

  Array.from(drawer.children).forEach(function(child){if(child!==head)child.remove();});
  var role=element('div','villa-account-drawer-role',data.user.role_label||'Misafir');
  drawer.appendChild(role);
  var nav=element('nav','villa-account-drawer-menu');
  nav.setAttribute('aria-label','Hesap menüsü');
  (data.menus||[]).forEach(function(item){
   var link=element('a','');
   link.href=item.url;
   var icon=element('span','villa-account-drawer-icon',item.icon||'');
   icon.setAttribute('aria-hidden','true');
   var label=element('span','',item.label||'');
   var arrow=element('span','villa-account-drawer-arrow','›');
   arrow.setAttribute('aria-hidden','true');
   link.append(icon,label,arrow);nav.appendChild(link);
  });
  drawer.appendChild(nav);
  var footer=element('div','villa-account-drawer-footer');
  var home=element('a','villa-account-drawer-home');
  home.href=data.home_url;
  home.append(document.createTextNode('Siteyi görüntüle '),element('span','', '↗'));
  footer.appendChild(home);
  var logout=element('form','villa-account-drawer-logout');
  logout.method='post';logout.action=data.logout_url;
  var token=element('input');token.type='hidden';token.name='_csrf';token.value=data.csrf||'';
  var button=element('button','');
  button.type='submit';button.append(element('span','', '↪'),document.createTextNode(' Güvenli Çıkış Yap'));
  logout.append(token,button);footer.appendChild(logout);drawer.appendChild(footer);

  document.querySelectorAll('[data-auth-link]').forEach(function(link){
   var label=data.user.label||'Hesabım';
   var textNodes=Array.from(link.childNodes).filter(function(node){return node.nodeType===Node.TEXT_NODE&&node.textContent.trim()!=='';});
   if(textNodes.length){textNodes[0].textContent=' '+label+' ';textNodes.slice(1).forEach(function(node){node.remove();});}
   else if(!link.querySelector('[data-auth-label]'))link.appendChild(element('span','',label));
   link.setAttribute('aria-label',label);
   link.href=appPath(data.user.role==='customer'?'/account/bookings':(data.user.role==='vendor'?'/vendor':'/admin'));
   link.removeAttribute('data-login-trigger');
   link.setAttribute('data-account-drawer-trigger','');
  });
  form.reset();
  document.dispatchEvent(new CustomEvent('villa:login',{detail:data.user}));
 }
 form.addEventListener('submit',async function(event){
  event.preventDefault();
  if(errorBox){errorBox.hidden=true;errorBox.textContent='';}
  if(submit){submit.disabled=true;submit.dataset.originalText=submit.textContent;submit.textContent='Kontrol ediliyor…';}
  try{
   var response=await fetch(form.dataset.endpoint,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
   var data=await response.json();
   if(!response.ok||!data.ok)throw new Error(data.message||'Giriş yapılamadı. Bilgilerinizi kontrol edin.');
   renderAccount(data);
   var close=drawer.querySelector('#villaAccountDrawerClose');
   if(close)close.click();
  }catch(error){
   if(errorBox){errorBox.textContent=error.message||'Bağlantı hatası. Lütfen tekrar deneyin.';errorBox.hidden=false;}
  }finally{
   if(submit){submit.disabled=false;submit.textContent=submit.dataset.originalText||'Giriş Yap';}
  }
 });
})();
</script>
<style id="villa-ajax-login-error-style">
.villa-login-ajax-error{margin:0 0 12px;padding:11px 13px;border:1px solid #f1d2cf;border-radius:9px;background:#fff5f4;color:#a43e35;font-size:13px;line-height:1.45}
.villa-login-ajax-error[hidden]{display:none!important}
.villa-account-drawer-login button[disabled]{opacity:.65;cursor:wait}
</style>


<style>
.villa-account-drawer-backdrop{position:fixed;inset:0;z-index:2147482000;background:rgba(10,24,18,.48);backdrop-filter:blur(3px);opacity:0;visibility:hidden;transition:opacity .28s ease,visibility .28s ease}
.villa-account-drawer-backdrop[hidden],.villa-account-drawer[hidden]{display:none!important}
.villa-account-drawer-backdrop.is-open{opacity:1;visibility:visible}
.villa-account-drawer{position:fixed;z-index:2147482001;top:0;right:0;bottom:0;box-sizing:border-box;display:flex;flex-direction:column;width:min(440px,100vw);height:100dvh;max-height:100dvh;overflow-y:auto;padding:34px 30px calc(24px + env(safe-area-inset-bottom,0px));background:#fff;border-left:1px solid #e9eee9;box-shadow:-24px 0 70px rgba(10,24,18,.18);transform:translateX(105%);visibility:hidden;transition:transform .38s cubic-bezier(.22,.75,.25,1),visibility .38s ease}
.villa-account-drawer.is-open{transform:translateX(0);visibility:visible}
.villa-account-drawer-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:8px 0 25px;border-bottom:1px solid #edf1ed}
.villa-account-drawer-kicker{display:block;margin-bottom:9px;color:#7b8b80;font-size:10px;font-weight:800;letter-spacing:.16em}
.villa-account-drawer-heading h2{margin:0;color:#1d3328;font-size:27px;line-height:1.2;letter-spacing:-.04em}
.villa-account-drawer-heading p{margin:7px 0 0;color:#78847d;font-size:13px;overflow-wrap:anywhere}
.villa-account-drawer-close{flex:0 0 40px;width:40px;height:40px;border:1px solid #e7eee8;border-radius:50%;background:#f7faf7;color:#263b30;font-size:25px;line-height:1;cursor:pointer;transition:background .2s,transform .2s}.villa-account-drawer-close:hover{background:#edf2e9;transform:rotate(90deg)}.villa-account-drawer-close:focus-visible{outline:2px solid #66836b;outline-offset:3px}
.villa-account-drawer-role{align-self:flex-start;margin:22px 0 12px;padding:6px 11px;border-radius:999px;background:#edf5ef;color:#286344;font-size:11px;font-weight:750}
.villa-account-drawer-menu{display:flex;flex-direction:column;gap:6px;padding:2px 0 18px}
.villa-account-drawer-menu a{display:flex;align-items:center;gap:13px;min-height:50px;padding:0 12px;border:1px solid transparent;border-radius:12px;color:#304238;text-decoration:none;font-size:14px;font-weight:650;transition:background .18s ease,border-color .18s ease,transform .18s ease}
.villa-account-drawer-menu a:hover{border-color:#e5eee6;background:#f4f8f4;transform:translateX(2px)}
.villa-account-drawer-icon{display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;border-radius:10px;background:#f0f5f0;color:#286344;font-size:18px}
.villa-account-drawer-arrow{margin-left:auto;color:#9aa69d;font-size:22px}
.villa-account-drawer-footer{margin-top:auto;padding-top:18px;border-top:1px solid #edf1ed}
.villa-account-drawer-home{display:flex;align-items:center;justify-content:space-between;padding:12px 2px;color:#617267;text-decoration:none;font-size:13px;font-weight:650}
.villa-account-drawer-logout{margin-top:10px}
.villa-account-drawer-logout button{width:100%;min-height:48px;padding:12px 15px;border:1px solid #f0dfdc;border-radius:11px;background:#fff8f7;color:#a43e35;font:inherit;font-size:13px;font-weight:750;text-align:left;cursor:pointer}
.villa-account-drawer-login{display:flex;flex-direction:column;gap:0;padding-top:28px}
.villa-account-drawer-login label{display:block;margin:14px 0 7px;color:#304238;font-size:13px;font-weight:700}
.villa-account-drawer-login input{box-sizing:border-box;width:100%;min-height:48px;padding:12px 14px;border:1px solid #dfe8e2;border-radius:10px;background:#fff;color:#20332c;font:inherit;font-size:14px;outline:none}
.villa-account-drawer-login input:focus{border-color:#6d987b;box-shadow:0 0 0 3px rgba(72,133,91,.12)}
.villa-login-submit{width:100%;min-height:50px;margin-top:22px;padding:13px 16px;border:0;border-radius:11px;background:#176b50;color:#fff;font:inherit;font-size:14px;font-weight:750;cursor:pointer}
.villa-login-foot{margin-top:20px;text-align:center;color:#78847d;font-size:13px}
.villa-login-foot a{color:#176b50;font-weight:750;text-decoration:none}
body.villa-account-drawer-open{overflow:hidden!important}
@media(max-width:520px){.villa-account-drawer{padding:26px 22px calc(20px + env(safe-area-inset-bottom,0px));width:min(440px,100vw)}.villa-account-drawer-heading h2{font-size:24px}}
@media(prefers-reduced-motion:reduce){.villa-account-drawer,.villa-account-drawer-backdrop,.villa-account-drawer-menu a{transition:none}}
</style>
<script>
(function(){
 var drawer=document.getElementById('villaAccountDrawer'),backdrop=document.getElementById('villaAccountDrawerBackdrop'),closeButton=document.getElementById('villaAccountDrawerClose');
 if(!drawer||!backdrop||!closeButton||drawer.dataset.bound==='1')return;
 drawer.dataset.bound='1';
 var previousFocus=null,closeTimer=null;
 function openDrawer(){
  if(!drawer.hidden)return;
  previousFocus=document.activeElement;
  drawer.hidden=false;backdrop.hidden=false;drawer.setAttribute('aria-hidden','false');
  document.body.classList.add('villa-account-drawer-open');
  window.requestAnimationFrame(function(){backdrop.classList.add('is-open');drawer.classList.add('is-open');});
  window.setTimeout(function(){var target=drawer.querySelector('input, .villa-account-drawer-menu a, .villa-account-drawer-close');if(target)target.focus();},90);
 }
 function closeDrawer(){
  if(drawer.hidden)return;
  drawer.classList.remove('is-open');backdrop.classList.remove('is-open');drawer.setAttribute('aria-hidden','true');
  document.body.classList.remove('villa-account-drawer-open');
  window.clearTimeout(closeTimer);
  closeTimer=window.setTimeout(function(){drawer.hidden=true;backdrop.hidden=true;if(previousFocus&&typeof previousFocus.focus==='function')previousFocus.focus();},390);
 }
 document.addEventListener('click',function(event){
  var trigger=event.target.closest('[data-login-trigger], [data-account-drawer-trigger]');
  if(trigger){
   event.preventDefault();
   if(trigger.hasAttribute('data-favorite-login-trigger')){
    var loginForm=drawer.querySelector('#villaLoginForm');
    if(loginForm){
     var returnField=loginForm.querySelector('input[name="return_to"]');
     var favoriteField=loginForm.querySelector('input[name="favorite_property_id"]');
     if(returnField)returnField.value=window.location.pathname+window.location.search;
     if(favoriteField)favoriteField.value=trigger.getAttribute('data-favorite-property-id')||'';
    }
   }
   openDrawer();return;
  }
  if(event.target===backdrop||event.target.closest('#villaAccountDrawerClose'))closeDrawer();
 });
 document.addEventListener('keydown',function(event){
  if(drawer.hidden)return;
  if(event.key==='Escape'){closeDrawer();return;}
  if(event.key==='Tab'){
   var focusable=Array.prototype.slice.call(drawer.querySelectorAll('a[href],button:not([disabled]),input:not([disabled])')).filter(function(el){return el.offsetParent!==null;});
   if(!focusable.length)return;
   var first=focusable[0],last=focusable[focusable.length-1];
   if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
   else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
  }
 });
})();
</script>
