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
$html = str_replace('</head>', '<style>' . <<<'CSS'

/* Listing search layout: spacious three-column desktop grid with balanced controls. */
body .filter-panel{padding:clamp(18px,2.2vw,26px);border-radius:18px}
body .filter-panel .filters{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px 16px;align-items:end}
body .filter-panel .field{min-width:0;width:100%}
body .filter-panel .field label{display:block;min-height:17px;margin:0 0 8px;line-height:1.25;font-size:11px;letter-spacing:.055em}
body .filter-panel .field input:not([type=hidden]),body .filter-panel .field select{display:block;width:100%;min-width:0;max-width:100%;height:50px;min-height:50px;padding:0 14px;margin:0;box-sizing:border-box;border:1px solid var(--line,#dfe6e0);border-radius:10px;font:500 14px/1.2 system-ui,sans-serif;vertical-align:middle}
body .filter-panel .field input[type=date]{padding:0 10px}
body .filter-panel .price-filter{grid-column:span 2;min-width:0;min-height:75px;padding:12px 16px;box-sizing:border-box;border-radius:10px}
body .filter-panel .submit{grid-column:span 1;width:100%;min-width:0;min-height:50px;height:50px;padding:0 16px;box-sizing:border-box;white-space:normal;line-height:1.25}
@media(max-width:900px){body .filter-panel .filters{grid-template-columns:repeat(2,minmax(0,1fr));gap:15px 13px}body .filter-panel .price-filter,body .filter-panel .submit{grid-column:1/-1}}
@media(max-width:600px){body .filter-panel{padding:14px}body .filter-panel .filters{grid-template-columns:minmax(0,1fr);gap:13px}body .filter-panel .field input:not([type=hidden]),body .filter-panel .field select{height:48px;min-height:48px}body .filter-panel .price-filter,body .filter-panel .submit{grid-column:1/-1}}
CSS
 . '</style></head>', $html);
echo $html;
