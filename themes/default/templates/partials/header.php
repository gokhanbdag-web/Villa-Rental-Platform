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
<?php if ($themeLogoUrl !== ''): ?>
<div class="site-logo-topbar">
  <a class="site-logo-topbar__brand" href="<?= e(app_url('/')) ?>" aria-label="<?= e($themeSiteName) ?>">
    <img src="<?= e($themeLogoUrl) ?>" alt="<?= e($themeSiteName) ?>">
  </a>
</div>
<?php endif; ?>
<header class="site-header">
  <div class="wrap head">
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
