<?php
declare(strict_types=1);

// Theme-specific presentation shell. The shared page supplies all business data and actions.
ob_start();
require APP_ROOT . '/views/site/property.php';
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
if (stripos($html, '</body>') !== false) {
    $menuScript = '<script>(function(){var b=document.getElementById("menuToggle"),n=document.getElementById("siteNavigation"),o=document.getElementById("menuOverlay");if(!b||!n||!o)return;b.addEventListener("click",function(){requestAnimationFrame(function(){o.classList.toggle("is-open",b.getAttribute("aria-expanded")==="true")})});o.addEventListener("click",function(){if(b.getAttribute("aria-expanded")==="true")b.click()})})();</script>';
    $html = preg_replace('~</body>~i', $menuScript . '</body>', $html, 1) ?? $html;
}
$html = preg_replace('~<body\\b~i', '<body class="villa-property-modern"', $html, 1) ?? $html;
$themePropertyCss = <<<'CSS'
body.villa-property-modern{background:#111715;color:#edf1ed;font-family:Inter,system-ui,sans-serif}body.villa-property-modern .wrap{width:min(1320px,calc(100% - 64px))}body.villa-property-modern .site-header{background:#111715;border-color:#2b3530}body.villa-property-modern .crumb{color:#87938c;padding:26px 0 18px;font-size:12px;letter-spacing:.08em;text-transform:uppercase}body.villa-property-modern .crumb a,body.villa-property-modern .site-nav a{color:#cbd4cd}body.villa-property-modern .title{align-items:center;border-bottom:1px solid #2c3831;padding:8px 0 22px}body.villa-property-modern .title h1{font-size:clamp(32px,4.2vw,52px);letter-spacing:-2px;font-weight:780;color:#f3f5f1}body.villa-property-modern .title .muted,body.villa-property-modern .muted{color:#a2ada5}body.villa-property-modern .price{color:#d9f07c;font-size:24px}body.villa-property-modern .gallery{grid-template-columns:1.4fr 1fr 1fr;grid-template-rows:230px 230px;gap:5px;border-radius:4px;margin:22px 0 42px;background:#27312c}body.villa-property-modern .gallery-tile:first-child{grid-row:span 2}body.villa-property-modern .gallery-tile{border-radius:0}body.villa-property-modern .gallery-tile img{filter:saturate(.82)}body.villa-property-modern .gallery-count{border:0;border-radius:3px;background:#d9f07c;color:#172019;text-transform:uppercase;font-size:11px}body.villa-property-modern .body{grid-template-columns:minmax(0,1fr) 390px;gap:48px}body.villa-property-modern .body h2{font-size:27px;letter-spacing:-.8px;color:#f0f4ee}body.villa-property-modern .description{font-size:15px;line-height:1.9;color:#bac5bd}body.villa-property-modern .booking{position:sticky;top:20px;background:#202a25;border:1px solid #3b4941;border-radius:5px;padding:28px;box-shadow:none}body.villa-property-modern .booking strong{font-size:29px;color:#e5f69b}body.villa-property-modern .booking p{color:#b5c0b8}body.villa-property-modern .booking a{background:#d9f07c;color:#172019;border-radius:3px;text-transform:uppercase;letter-spacing:.06em;font-size:12px}body.villa-property-modern .photo-lightbox{background:rgba(5,9,7,.98)}body.villa-property-modern .site-footer{background:#090e0c}@media(max-width:760px){body.villa-property-modern .wrap{width:calc(100% - 28px)}body.villa-property-modern .gallery{grid-template-rows:minmax(220px,58vw) minmax(130px,34vw);gap:5px}body.villa-property-modern .body{grid-template-columns:1fr;gap:25px}body.villa-property-modern .booking{position:static}}
body.villa-property-modern .property-layout{align-items:start}body.villa-property-modern .property-main{min-width:0}body.villa-property-modern .property-amenities-title{margin:34px 0 15px;color:#e9f0e9;font-size:22px;text-transform:uppercase;letter-spacing:.04em}body.villa-property-modern .property-amenities{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}body.villa-property-modern .property-amenities li{padding:14px 15px;background:#202a25;border:1px solid #3b4941;color:#cbd6cd;font-size:13px}body.villa-property-modern .property-reviews{margin-top:45px!important;padding-top:26px;border-top:1px solid #344139}body.villa-property-modern .property-review{padding:18px 0;border-top:1px solid #344139;color:#d5ded7}body.villa-property-modern .property-review p{color:#b8c4bb}body.villa-property-modern .property-booking{border-top:3px solid #d9f07c}@media(max-width:760px){body.villa-property-modern .property-amenities{grid-template-columns:1fr 1fr}}
CSS;
$html = str_replace('</head>', '<style>' . $themePropertyCss . '</style></head>', $html);
echo $html;
