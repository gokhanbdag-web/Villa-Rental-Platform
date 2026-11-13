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
<?php if ($topPhoneDigits !== ''): ?>
<div class="med-topbar"><div class="wrap"><a href="tel:<?= e($topPhoneDigits) ?>">☎ <?= e($topPhone) ?></a><?php if (($user['role'] ?? '') === 'customer'): ?><a href="<?= e(app_url('/favorites')) ?>">♡ Favorilerim</a><?php endif; ?></div></div>
<?php endif; ?>
<header class="site-header">
  <div class="wrap head">
    <a class="brand" href="<?= e(app_url('/')) ?>" aria-label="<?= e($themeSiteName) ?>"><?php if ($themeLogoUrl !== ''): ?><img src="<?= e($themeLogoUrl) ?>" alt="<?= e($themeSiteName) ?>"><?php else: ?><?= e($themeSiteName) ?><?php endif; ?></a>
    <button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark" hidden>×</span></button>
    <nav class="nav" id="siteNavigation"><?php \App\Core\ThemeManager::renderPartial('partials/navigation-items.php', get_defined_vars()); ?></nav>
  </div>
</header>
<div class="drawer-overlay" id="menuOverlay"></div>
<script>
(function(){
  var button=document.getElementById('menuToggle');
  var menu=document.getElementById('siteNavigation');
  var overlay=document.getElementById('menuOverlay');
  if(!button||!menu||!overlay||button.dataset.menuReady==='1')return;
  button.dataset.menuReady='1';
  function setOpen(open){
    menu.classList.toggle('is-open',open);
    overlay.classList.toggle('is-open',open);
    document.body.classList.toggle('menu-open',open);
    button.setAttribute('aria-expanded',open?'true':'false');
    button.setAttribute('aria-label',open?'Menüyü kapat':'Menüyü aç');
    var openMark=button.querySelector('.menu-open-mark');
    var closeMark=button.querySelector('.menu-close-mark');
    if(openMark)openMark.hidden=open;
    if(closeMark)closeMark.hidden=!open;
  }
  button.addEventListener('click',function(event){
    event.preventDefault();
    event.stopImmediatePropagation();
    setOpen(button.getAttribute('aria-expanded')!=='true');
  },true);
  overlay.addEventListener('click',function(event){event.stopImmediatePropagation();setOpen(false);},true);
  menu.addEventListener('click',function(event){if(event.target.closest('a'))setOpen(false);},true);
  document.addEventListener('keydown',function(event){if(event.key==='Escape')setOpen(false);});
  window.addEventListener('resize',function(){if(window.innerWidth>760)setOpen(false);});
})();
</script>
