<?php
declare(strict_types=1);

/**
 * Tek ortak site header'ı.
 * Logo, üst çağrı merkezi şeridi, navigasyon ve mobil çekmece tüm site sayfalarında buradan üretilir.
 */
$themeSiteName = (string) ($content['site_name'] ?? $siteName ?? 'VillaStay');
$navHtml = '';
ob_start();
$navItems = $navigation ?? [];
$navAllowAuth = true;
\App\Core\ThemeManager::renderPartial('partials/navigation-items.php', get_defined_vars());
$navHtml = '<nav class="nav" id="siteNavigation">' . (string) ob_get_clean() . '</nav>';

$topPhone = trim((string) ($content['home_support_phone'] ?? ''));
$topPhoneDigits = preg_replace('/\\D+/', '', $topPhone);
if ($topPhoneDigits !== ''): ?>
<div class="med-topbar"><div class="wrap"><a href="tel:<?= e($topPhoneDigits) ?>">☎ <?= e($topPhone) ?></a><?php if (($user['role'] ?? '') === 'customer'): ?><a href="<?= e(app_url('/favorites')) ?>">♡ Favorilerim</a><?php endif; ?></div></div>
<?php endif; ?>
<header class="theme-header theme-header--coastal">
  <div class="theme-header-inner">
    <a class="theme-brand theme-brand--coastal" href="<?= e(app_url('/')) ?>"><span class="theme-brand-name"><?= e($themeSiteName) ?></span></a>
    <button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark" hidden>×</span></button>
    <?= $navHtml ?>
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
  }
  button.addEventListener('click',function(){setOpen(button.getAttribute('aria-expanded')!=='true');});
  overlay.addEventListener('click',function(){setOpen(false);});
  menu.addEventListener('click',function(event){if(event.target.closest('a'))setOpen(false);});
  document.addEventListener('keydown',function(event){if(event.key==='Escape')setOpen(false);});
  window.addEventListener('resize',function(){if(window.innerWidth>760)setOpen(false);});
})();
</script>
