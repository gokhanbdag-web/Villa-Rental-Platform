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



