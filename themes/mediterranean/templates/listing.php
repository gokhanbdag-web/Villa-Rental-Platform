<?php
declare(strict_types=1);

// Theme-specific presentation shell. The shared page supplies all business data and actions.
ob_start();
require APP_ROOT . '/views/site/listing.php';
$html = (string) ob_get_clean();

$navHtml = '';
if (preg_match('~<nav\\b[^>]*>.*?</nav>~s', $html, $navMatch)) {
    $navHtml = preg_replace('~<nav\\b[^>]*>~', '<nav class="nav" id="siteNavigation">', $navMatch[0], 1) ?? $navMatch[0];
}
if ($navHtml === '') {
    ob_start();
    $navItems = $navigation ?? [];
    $navAllowAuth = true;
    require APP_ROOT . '/views/site/partials/navigation-items.php';
    $navHtml = '<nav class="nav" id="siteNavigation">' . (string) ob_get_clean() . '</nav>';
}

$themeSiteName = (string) ($content['site_name'] ?? $siteName ?? 'VillaStay');
$brand = '<a class="theme-brand theme-brand--coastal" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--coastal"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-listing-mediterranean"', $html, 1) ?? $html;
$listingCss = <<<'CSS'
body{background:#f7f7f1;color:#304235}.intro{background:linear-gradient(120deg,#e9efe3,#f7f7f1);padding:55px 0 35px}.intro h1{font:500 clamp(36px,4.5vw,58px) Georgia,serif;color:#294936;max-width:720px}.filter-panel{border-radius:24px;box-shadow:0 18px 45px #29493612}.filters{grid-template-columns:repeat(4,minmax(0,1fr))}.grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:28px}.photo{height:265px;border-radius:22px}.stay h3{font:500 20px Georgia,serif;color:#304b38}.submit{background:#456d50}.rating-line .rating-score{color:#456d50}@media(max-width:650px){.intro{padding:30px 0 24px}.grid{grid-template-columns:repeat(2,minmax(0,1fr))}.photo{height:155px}}
CSS;
$html = str_replace('</head>', '<style>' . $listingCss . '</style></head>', $html);
echo $html;
