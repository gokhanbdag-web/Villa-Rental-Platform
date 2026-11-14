<?php
declare(strict_types=1);

/**
 * Ana sayfanın hero başlangıcından önceki ortak üst alanı.
 * Logo, navigasyon ve mobil menü tüm sayfalarda aynı HTML yapısını kullanır.
 */
$themeSiteName = (string) ($content['site_name'] ?? $siteName ?? 'VillaStay');
$themeBrandSettings = is_array($brandSettings ?? null) ? $brandSettings : [];
$themeLogoUrl = (string) ($themeBrandSettings['logo_url'] ?? '');
$navItems = $navigation ?? [];
$navAllowAuth = true;
$topPhone = trim((string) ($content['home_support_phone'] ?? ''));
$topPhoneDigits = preg_replace('/\\D+/', '', $topPhone);
?>
<style>
/* Eski iletişim/sosyal bilgi şeridi bu tasarımda gösterilmez. */
.villa-social-bar{display:none!important}
/* VillaCMS centered brand strip: isolated from the existing navigation/header */
.villacms-brand-strip{position:relative;z-index:2;display:flex;align-items:center;justify-content:center;box-sizing:border-box;width:100%;min-height:108px;padding:13px 20px;background:linear-gradient(112deg,#fbf8f1 0%,#f4eee2 48%,#fbf8f1 100%);border-bottom:1px solid rgba(176,132,57,.24);overflow:hidden}
.villacms-brand-strip:before,.villacms-brand-strip:after{content:"";position:absolute;top:50%;width:min(25vw,300px);height:1px;background:linear-gradient(90deg,transparent,rgba(174,130,57,.55))}
.villacms-brand-strip:before{right:calc(50% + 210px)}
.villacms-brand-strip:after{left:calc(50% + 210px);transform:rotate(180deg)}
.villacms-brand-link{position:relative;z-index:1;display:flex;align-items:center;justify-content:center;gap:13px;color:#17283e;text-decoration:none}
.villacms-brand-mark{display:block;flex:0 0 76px;width:76px;height:66px}
.villacms-brand-wording{display:flex;flex-direction:column;align-items:flex-start;gap:3px}
.villacms-brand-name{font-family:Georgia,"Times New Roman",serif;font-size:clamp(35px,4vw,48px);font-weight:500;letter-spacing:-2.2px;line-height:.98;color:#17283e}
.villacms-brand-name span{color:#b8893d}
.villacms-brand-tagline{padding-left:3px;color:#354256;font-family:Arial,sans-serif;font-size:9px;font-weight:700;letter-spacing:.29em;line-height:1.4;white-space:nowrap}
@media(max-width:760px){
 .villacms-brand-strip{min-height:78px;padding:9px 12px}
 .villacms-brand-strip:before,.villacms-brand-strip:after{width:8vw}
 .villacms-brand-strip:before{right:calc(50% + 142px)}
 .villacms-brand-strip:after{left:calc(50% + 142px)}
 .villacms-brand-link{gap:8px}
 .villacms-brand-mark{flex-basis:52px;width:52px;height:48px}
 .villacms-brand-name{font-size:32px;letter-spacing:-1.7px}
 .villacms-brand-tagline{font-size:7px;letter-spacing:.19em}
}
@media(max-width:360px){.villacms-brand-strip:before,.villacms-brand-strip:after{display:none}.villacms-brand-mark{flex-basis:46px;width:46px}.villacms-brand-name{font-size:29px}}
</style>
<div class="villacms-brand-strip">
  <a class="villacms-brand-link" href="<?= e(app_url('/')) ?>" aria-label="VillaCMS ana sayfa">
    <svg class="villacms-brand-mark" viewBox="0 0 100 82" role="img" aria-label="VillaCMS villa amblemi" xmlns="http://www.w3.org/2000/svg">
      <defs><linearGradient id="villacmsGold" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#e2bd70"/><stop offset=".52" stop-color="#ad7930"/><stop offset="1" stop-color="#e8c982"/></linearGradient></defs>
      <path d="M8 48 48 10l39 37" fill="none" stroke="url(#villacmsGold)" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M25 38v-5l23-22 23 22v17" fill="none" stroke="#17283e" stroke-width="4" stroke-linejoin="round"/>
      <path d="M42 37h13v16H42z" fill="#17283e"/><path d="M48.5 37v16M42 45h13" stroke="#f7f1e6" stroke-width="1.5"/>
      <path d="M5 60c17-9 29-9 45-2 15 7 28 7 45-2" fill="none" stroke="url(#villacmsGold)" stroke-width="5" stroke-linecap="round"/>
      <path d="M11 70c14-6 26-6 39 0 13 6 25 6 39 0" fill="none" stroke="#17283e" stroke-width="3" stroke-linecap="round"/>
    </svg>
    <span class="villacms-brand-wording">
      <span class="villacms-brand-name">Villa<span>CMS</span></span>
      <span class="villacms-brand-tagline">PREMIUM VILLA RENTAL</span>
    </span>
  </a>
</div>
<header class="site-header">
  <div class="wrap head">
    <a class="brand" href="<?= e(app_url('/')) ?>" aria-label="<?= e($themeSiteName) ?>"><?php if ($themeLogoUrl !== ''): ?><img src="<?= e($themeLogoUrl) ?>" alt="<?= e($themeSiteName) ?>"><?php else: ?><?= e($themeSiteName) ?><?php endif; ?></a>
    <button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark" hidden>×</span></button>
    <nav class="nav" id="siteNavigation"><?php \App\Core\ThemeManager::renderPartial('partials/navigation-items.php', get_defined_vars()); ?></nav>
  </div>
</header>
<script>
(function(){
  var button=document.getElementById('menuToggle');
  var menu=document.getElementById('siteNavigation');
  if(!button||!menu||button.dataset.menuReady==='1')return;
  button.dataset.menuReady='1';
  function setOpen(open){
    menu.classList.toggle('is-open',open);
    button.setAttribute('aria-expanded',open?'true':'false');
    button.setAttribute('aria-label',open?'Menüyü kapat':'Menüyü aç');
  }
  button.addEventListener('click',function(event){
    event.preventDefault();
    event.stopImmediatePropagation();
    setOpen(button.getAttribute('aria-expanded')!=='true');
  },true);
  menu.addEventListener('click',function(event){if(event.target.closest('a'))setOpen(false);},true);
  document.addEventListener('keydown',function(event){if(event.key==='Escape')setOpen(false);});
  window.addEventListener('resize',function(){if(window.innerWidth>760)setOpen(false);});
})();
</script>
