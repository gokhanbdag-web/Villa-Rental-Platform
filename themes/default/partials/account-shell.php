<?php
declare(strict_types=1);

// Shared account-page chrome. The account content remains in its focused view file.
$accountView = (string)($accountView ?? 'bookings.php');
if (!preg_match('/^[a-z0-9-]+\.php$/', $accountView)) {
    throw new RuntimeException('Hesap sayfası şablonu geçersiz.');
}
ob_start();
\App\Core\ThemeManager::renderTemplate('templates/pages/account/' . $accountView, get_defined_vars());
$html = (string)ob_get_clean();

ob_start();
$navItems = $navigation ?? [];
$navAllowAuth = true;
\App\Core\ThemeManager::renderPartial('partials/navigation-items.php', get_defined_vars());
$navHtml = '<nav class="nav" id="siteNavigation">' . (string)ob_get_clean() . '</nav>';

$themeSiteName = (string)($content['site_name'] ?? $siteName ?? 'VillaStay');
$brand = '<a class="theme-brand ' . e($accountBrandClass) . '" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header ' . e($accountHeaderClass) . '"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';
$html = preg_replace_callback('~<body\\b[^>]*>~i', static fn(array $match): string => $match[0] . $header, $html, 1) ?? $html;
$accountPath = (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
$accountLinks = [
    ['/account/bookings','Rezervasyonlarım'],
    ['/favorites','Favorilerim'],
    ['/account/notifications','Bildirimler'],
];
$accountNav = '<nav class="villa-account-nav" aria-label="Hesap menüsü">';
foreach ($accountLinks as [$path,$label]) {
    $linkPath = (string)(parse_url(app_url($path), PHP_URL_PATH) ?: $path);
    $active = $accountPath === $linkPath || str_starts_with($accountPath, rtrim($linkPath, '/') . '/');
    $accountNav .= '<a href="' . e(app_url($path)) . '"' . ($active ? ' aria-current="page" class="is-active"' : '') . '>' . e($label) . '</a>';
}
$accountNav .= '</nav>';
$html = preg_replace_callback('~(<main\\b[^>]*>)~i', static fn(array $match): string => $match[1] . $accountNav, $html, 1) ?? $html;

ob_start();
$footerTemplate = \App\Core\ThemeManager::resolveFile('templates/partials/footer.php');
if ($footerTemplate !== null) {
    require $footerTemplate;
}
$footer = (string) ob_get_clean();
$html = preg_replace_callback('~</body>~i', static fn(array $match): string => $footer . $match[0], $html, 1) ?? $html;

$accountStyles = '<style id="villa-account-shell-styles">.villa-account-nav{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 26px;padding:12px 0;border-bottom:1px solid color-mix(in srgb,currentColor 13%,transparent)}.villa-account-nav a{display:inline-flex;align-items:center;padding:9px 14px;border:1px solid color-mix(in srgb,currentColor 16%,transparent);border-radius:999px;color:inherit;text-decoration:none;font-size:13px;font-weight:700;opacity:.82}.villa-account-nav a:hover,.villa-account-nav a.is-active{opacity:1;background:color-mix(in srgb,currentColor 8%,transparent)}/* Burger appearance, shape, colors and drawer geometry intentionally come from the active theme stylesheet. */@media(max-width:650px){.villa-account-nav{gap:6px;margin-bottom:20px}.villa-account-nav a{padding:8px 11px;font-size:12px}}</style>';
$html = preg_replace_callback('~</body>~i', static fn(array $match): string => $menuScript . $accountStyles . $match[0], $html, 1) ?? $html;
echo $html;
