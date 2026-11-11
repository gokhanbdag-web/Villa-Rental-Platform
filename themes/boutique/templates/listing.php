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
$brand = '<a class="theme-brand theme-brand--boutique" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--boutique"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-listing-boutique"', $html, 1) ?? $html;
$listingCss = <<<'CSS'
body{background:#f1f2eb;color:#354238}.intro{background:#e7ebdf;padding:42px 0 26px}.intro h1{font:500 clamp(36px,4.5vw,58px)/1.06 Georgia,serif;color:#405344;max-width:650px}.intro p{max-width:520px;color:#788276}.filter-panel{border-radius:8px;background:#fafaf5;border-color:#dbe0d4;box-shadow:7px 8px 0 #e1e5da}.filters{grid-template-columns:1.3fr 1fr 1fr 1fr}.field input,.field select,.price-filter{border-radius:4px;border-color:#d5ddd2}.submit{background:#526b56;border-radius:4px}.results-head{align-items:center}.results-head h2{font:500 28px Georgia,serif}.grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:24px 16px;align-items:start}.stay:nth-child(3n+2){margin-top:35px}.photo{height:245px;border-radius:4px}.stay h3{font:500 19px Georgia,serif}.rating-line .rating-score{color:#526b56}footer{background:#35483b}@media(max-width:650px){.filters{grid-template-columns:1fr 1fr}.grid{grid-template-columns:1fr 1fr;gap:18px 10px}.stay:nth-child(3n+2){margin-top:0}.photo{height:160px}}
CSS;
$html = str_replace('</head>', '<style>' . $listingCss . '</style></head>', $html);
$html = str_replace('</head>', '<style>' . <<<'CSS'

/* Symmetric listing cards: equal tracks, no staggered second item. */
body.villa-listing-boutique .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));align-items:stretch;gap:24px}
body.villa-listing-boutique .grid>.stay{min-width:0;width:auto;margin:0!important;align-self:stretch}
body.villa-listing-boutique .grid .photo{height:245px}
@media(max-width:900px){body.villa-listing-boutique .grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}body.villa-listing-boutique .grid>.stay{margin:0!important}}
@media(max-width:600px){body.villa-listing-boutique .grid{grid-template-columns:minmax(0,1fr)!important;gap:18px!important}body.villa-listing-boutique .grid>.stay{grid-column:1/-1!important;width:100%;margin:0!important}body.villa-listing-boutique .grid .photo{height:230px}}
CSS
 . '</style></head>', $html);
echo $html;
