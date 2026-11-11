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
$brand = '<a class="theme-brand theme-brand--urban" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--urban"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-listing-modern"', $html, 1) ?? $html;
$listingCss = <<<'CSS'
body{background:#111715;color:#eef2ed}.intro{background:#111715;border-bottom:1px solid #354139;padding:54px 0 30px}.intro h1{font-size:clamp(40px,6vw,70px);line-height:.98;letter-spacing:-3px;text-transform:uppercase;max-width:800px}.intro p,.results-head p,.meta{color:#aab5ad}.filter-panel{background:#1a211e;border:1px solid #354139;border-radius:3px;box-shadow:none}.field label{color:#aab5ad}.field input,.field select,.price-filter{background:#111715;color:#eef2ed;border-color:#3c4a41;border-radius:2px}.submit{background:#d9ef7b;color:#141b16;border-radius:2px}.results{padding-top:45px}.grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.photo{height:215px;border-radius:2px}.stay h3{text-transform:uppercase;letter-spacing:.02em;font-size:15px}.price strong{color:#d9ef7b}.rating-line .rating-score{color:#d9ef7b}.empty{background:#1a211e;border-radius:2px;color:#c2cbc4}footer{background:#080e0c}@media(max-width:850px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.intro h1{letter-spacing:-1.5px}.photo{height:155px}}
CSS;
$html = str_replace('</head>', '<style>' . $listingCss . '</style></head>', $html);
echo $html;
