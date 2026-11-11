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
$brand = '<a class="theme-brand theme-brand--luxury" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--luxury"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-listing-prestige"', $html, 1) ?? $html;
$listingCss = <<<'CSS'
body{background:#f5f1e8;color:#252d39}.intro{background:#1e2c40;color:#fff;padding:58px 0 38px}.intro h1{font:400 clamp(38px,5vw,62px) Georgia,serif;max-width:800px}.intro p{color:#d9d0c1}.filter-panel{border-radius:2px;border-color:#d8c9a9;box-shadow:0 15px 40px #111c2b18}.filters{grid-template-columns:repeat(4,minmax(0,1fr))}.field label{letter-spacing:.08em}.field input,.field select,.price-filter{border-radius:2px;border-color:#d8c9a9}.submit{background:#1e2c40;border-radius:1px}.results-head{border-bottom:1px solid #d8c9a9;padding-bottom:17px}.results-head h2{font:400 30px Georgia,serif}.grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:30px}.photo{height:310px;border-radius:0}.stay:nth-child(4n+2){margin-top:45px}.stay h3{font:400 22px Georgia,serif}.price strong{color:#1e2c40}.rating-line .rating-score{color:#8a6d38}footer{background:#1e2c40}@media(max-width:650px){.grid{grid-template-columns:1fr 1fr;gap:14px}.photo{height:170px}.stay:nth-child(4n+2){margin-top:0}.intro{padding:32px 0 24px}}
CSS;
$html = str_replace('</head>', '<style>' . $listingCss . '</style></head>', $html);
echo $html;
