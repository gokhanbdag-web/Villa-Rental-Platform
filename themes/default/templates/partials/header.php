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
