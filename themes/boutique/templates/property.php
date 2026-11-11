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
$brand = '<a class="theme-brand theme-brand--boutique" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--boutique"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
if (stripos($html, '</body>') !== false) {
    $menuScript = '<script>(function(){var b=document.getElementById("menuToggle"),n=document.getElementById("siteNavigation"),o=document.getElementById("menuOverlay");if(!b||!n||!o)return;b.addEventListener("click",function(){requestAnimationFrame(function(){o.classList.toggle("is-open",b.getAttribute("aria-expanded")==="true")})});o.addEventListener("click",function(){if(b.getAttribute("aria-expanded")==="true")b.click()})})();</script>';
    $html = preg_replace('~</body>~i', $menuScript . '</body>', $html, 1) ?? $html;
}
$html = preg_replace('~<body\\b~i', '<body class="villa-property-boutique"', $html, 1) ?? $html;
$themePropertyCss = <<<'CSS'
body.villa-property-boutique{background:#f3f2eb;color:#303a32;font-family:Inter,system-ui,sans-serif}body.villa-property-boutique .wrap{width:min(1220px,calc(100% - 58px))}body.villa-property-boutique .crumb{padding:25px 0 17px;color:#788276;font-size:12px;letter-spacing:.04em}body.villa-property-boutique .title{align-items:end;padding:8px 0}body.villa-property-boutique .title h1{max-width:760px;font:500 clamp(34px,4.6vw,58px)/1.05 Georgia,serif;letter-spacing:-1.4px;color:#35463a}body.villa-property-boutique .title .muted{font-size:12px;line-height:1.9;color:#748074}body.villa-property-boutique .price{font:700 21px Georgia,serif;color:#71866e}body.villa-property-boutique .gallery{grid-template-columns:1.05fr 1.25fr;grid-template-rows:205px 205px 135px;gap:10px;border-radius:3px;margin:24px 0 44px;background:#e6e8de}body.villa-property-boutique .gallery-tile{border-radius:2px}body.villa-property-boutique .gallery-tile:first-child{grid-column:1;grid-row:1/3}body.villa-property-boutique .gallery-tile:nth-child(2){grid-column:2;grid-row:1}body.villa-property-boutique .gallery-tile:nth-child(3){grid-column:2;grid-row:2}body.villa-property-boutique .gallery-tile:nth-child(4){grid-column:1;grid-row:3}body.villa-property-boutique .gallery-tile:nth-child(5){grid-column:2;grid-row:3}body.villa-property-boutique .gallery-count{border:1px solid #d6ddcf;border-radius:2px;background:#f8f8f2;color:#52634f;font-size:11px}body.villa-property-boutique .body{grid-template-columns:minmax(0,1fr) 330px;gap:60px;align-items:start}body.villa-property-boutique .body h2{font:500 30px Georgia,serif;color:#405344}body.villa-property-boutique .description{font-size:14px;line-height:2.05;color:#606b60}body.villa-property-boutique .booking{position:sticky;top:22px;border:1px solid #d9dfd3;border-radius:3px;background:#fbfbf6;padding:24px;box-shadow:7px 7px 0 #e2e6dc}body.villa-property-boutique .booking strong{font:600 26px Georgia,serif;color:#5e765d}body.villa-property-boutique .booking a{background:#637c63;border-radius:2px;padding:15px;letter-spacing:.03em}body.villa-property-boutique .photo-lightbox{background:rgba(26,34,28,.97)}body.villa-property-boutique .site-footer{background:#35483b}@media(max-width:760px){body.villa-property-boutique .wrap{width:calc(100% - 28px)}body.villa-property-boutique .gallery{grid-template-columns:1fr 1fr;grid-template-rows:minmax(220px,57vw) 125px 110px;gap:6px}body.villa-property-boutique .gallery-tile:first-child{grid-column:1/-1;grid-row:1}body.villa-property-boutique .gallery-tile:nth-child(2){grid-column:1;grid-row:2}body.villa-property-boutique .gallery-tile:nth-child(3){grid-column:2;grid-row:2}body.villa-property-boutique .gallery-tile:nth-child(4){grid-column:1;grid-row:3}body.villa-property-boutique .gallery-tile:nth-child(5){grid-column:2;grid-row:3}body.villa-property-boutique .body{grid-template-columns:1fr;gap:24px}body.villa-property-boutique .booking{position:static}}
CSS;
$html = str_replace('</head>', '<style>' . $themePropertyCss . '</style></head>', $html);
echo $html;
