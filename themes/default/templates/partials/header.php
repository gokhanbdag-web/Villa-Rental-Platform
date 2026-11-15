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
$headerUser = is_array($user ?? null) ? $user : \App\Core\Auth::user();
$topPhone = trim((string) ($content['home_support_phone'] ?? ''));
$topPhoneDigits = preg_replace('/\\D+/', '', $topPhone);
?>
<style>
.site-header{position:relative;z-index:1003;height:76px;background:#fbfcf7;border-bottom:1px solid #e4e9dc}
.site-header .head{width:min(1200px,calc(100% - 40px));height:100%;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:18px}
.site-header .nav{display:flex;align-items:center;gap:23px;font-size:var(--villa-body-size,14px);font-weight:var(--villa-body-weight,500)}
.site-header .mobile-menu-toggle{display:none;align-items:center;justify-content:center;width:43px;height:43px;padding:0;border:1px solid var(--line,#dfe6e0);border-radius:12px;background:#fff;color:var(--villa-text,#23352d)!important;font-size:25px;line-height:1;cursor:pointer;appearance:none;-webkit-appearance:none}
.site-header .mobile-menu-toggle .menu-open-mark,.site-header .mobile-menu-toggle .menu-close-mark{color:var(--villa-text,#23352d)!important}
.site-header .mobile-menu-toggle [hidden]{display:none!important}
.site-header-mobile-brand img{display:block;max-width:180px;max-height:54px;object-fit:contain}
.drawer-overlay{display:none}
@media(max-width:760px){
 .site-header{height:68px}
 .site-header .head{width:calc(100% - 28px)}
 .site-header .mobile-menu-toggle{display:inline-flex;position:relative;z-index:1004;flex:0 0 43px}
 .site-header .nav{position:fixed;z-index:1002;top:0;right:0;bottom:0;width:min(86vw,360px);height:100dvh;overflow-y:auto;box-sizing:border-box;padding:85px 24px 30px;display:flex;flex-direction:column;align-items:stretch;justify-content:flex-start;gap:0;background:#fff;color:var(--villa-text,#23352d);transform:translateX(105%);visibility:hidden;transition:transform .25s ease,visibility .25s ease;box-shadow:-15px 0 40px #102d2520}
 .site-header .nav.is-open{transform:translateX(0);visibility:visible}
 .site-header .nav a{color:var(--villa-text,#23352d)}
 .drawer-overlay{display:block;position:fixed;inset:0;z-index:1001;background:#10251f80;opacity:0;visibility:hidden;transition:opacity .25s ease,visibility .25s ease}
 .drawer-overlay.is-open{opacity:1;visibility:visible}
 body.menu-open{overflow:hidden}
}
@media(prefers-reduced-motion:reduce){.site-header .nav,.drawer-overlay{transition:none}}
</style>
<header class="site-header">
  <div class="wrap head">
    <button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark" hidden>×</span></button>
    <?php if ($themeLogoUrl !== ''): ?>
    <a class="site-header-mobile-brand" href="<?= e(app_url('/')) ?>" aria-label="<?= e($themeSiteName) ?>">
      <img src="<?= e($themeLogoUrl) ?>" alt="<?= e($themeSiteName) ?>">
    </a>
    <?php endif; ?>
    <nav class="nav" id="siteNavigation"><?php \App\Core\ThemeManager::renderPartial('partials/navigation-items.php', get_defined_vars()); ?>
      <?php $drawerSocialLinks = \App\Core\ThemeManager::desktopSocialLinks(); ?>
      <?php if ($drawerSocialLinks !== ''): ?>
        <div class="site-drawer-social" aria-label="Sosyal medya">
          <?= $drawerSocialLinks ?>
        </div>
      <?php endif; ?>
    </nav>
    <div class="site-header-auth">
      <button type="button" class="site-header-auth-link villa-explore-trigger" data-explore-drawer-trigger aria-controls="villaExploreDrawer" aria-expanded="false" aria-label="Keşfet" title="Keşfet">
        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="m15.8 8.2-2.5 5.1-5.1 2.5 2.5-5.1 5.1-2.5Z"/></svg>
      </button>
      <?php
        $headerRole = (string) ($headerUser['role'] ?? '');
        $headerAccountPath = $headerRole === 'customer' ? '/account/bookings' : ($headerRole === 'vendor' ? '/vendor' : '/admin');
        $headerAccountLabel = in_array($headerRole, ['admin', 'super_admin'], true) ? 'Yönetim Paneli' : ($headerRole === 'vendor' ? 'Ev Sahibi Paneli' : 'Hesabım');
      ?>
      <?php if ($headerUser !== null): ?>
        <a class="site-header-auth-link" data-auth-link data-account-drawer-trigger href="<?= e(app_url($headerAccountPath)) ?>" aria-label="<?= e($headerAccountLabel) ?>" title="<?= e($headerAccountLabel) ?>">
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
        </a>
      <?php else: ?>
        <a class="site-header-auth-link" data-login-trigger href="<?= e(app_url('/login')) ?>" aria-label="Giriş Yap" title="Giriş Yap">
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5m5 5H3"/></svg>
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>
<div class="drawer-overlay" id="menuOverlay"></div>
<?php \App\Core\ThemeManager::renderPartial('partials/explore-drawer.php', get_defined_vars()); ?>



