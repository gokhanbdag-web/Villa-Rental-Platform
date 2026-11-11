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
$brand = '<a class="theme-brand theme-brand--luxury" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--luxury"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
if (stripos($html, '</body>') !== false) {
    $menuScript = '<script>(function(){var b=document.getElementById("menuToggle"),n=document.getElementById("siteNavigation"),o=document.getElementById("menuOverlay");if(!b||!n||!o)return;b.addEventListener("click",function(){requestAnimationFrame(function(){o.classList.toggle("is-open",b.getAttribute("aria-expanded")==="true")})});o.addEventListener("click",function(){if(b.getAttribute("aria-expanded")==="true")b.click()})})();</script>';
    $html = preg_replace('~</body>~i', $menuScript . '</body>', $html, 1) ?? $html;
}
$html = preg_replace('~<body\\b~i', '<body class="villa-property-prestige"', $html, 1) ?? $html;
$themePropertyCss = <<<'CSS'
body.villa-property-prestige{background:#f5f1e8;color:#27251f;font-family:Georgia,serif}body.villa-property-prestige .wrap{width:min(1380px,calc(100% - 70px))}body.villa-property-prestige .crumb{padding:28px 0 16px;color:#897b62;font:11px Inter,system-ui,sans-serif;letter-spacing:.14em;text-transform:uppercase}body.villa-property-prestige .title{display:block;text-align:center;padding:10px 0 22px}body.villa-property-prestige .title h1{font-size:clamp(38px,5vw,66px);font-weight:400;letter-spacing:-1.5px;color:#202b3a}body.villa-property-prestige .title .muted{font:12px/1.9 Inter,system-ui,sans-serif;color:#77766e}body.villa-property-prestige .price{margin-top:16px;color:#a5874c;font:700 23px Inter,system-ui,sans-serif}body.villa-property-prestige .gallery{grid-template-columns:repeat(4,minmax(0,1fr));grid-template-rows:430px 142px;gap:9px;border-radius:2px;margin:18px 0 52px;background:#e6dfd1;overflow:visible}body.villa-property-prestige .gallery-tile{border-radius:1px}body.villa-property-prestige .gallery-tile:first-child{grid-column:1/-1;grid-row:1}body.villa-property-prestige .gallery-tile:nth-child(n+2){grid-row:2}body.villa-property-prestige .gallery-tile:nth-child(5){display:flex}body.villa-property-prestige .gallery-count{right:14px;bottom:158px;border:1px solid #c8b78f;border-radius:0;background:#f5f1e8;color:#806a42;letter-spacing:.08em;text-transform:uppercase;font-size:10px}body.villa-property-prestige .body{grid-template-columns:minmax(0,1fr) 370px;gap:85px}body.villa-property-prestige .body h2{font-size:32px;font-weight:400;color:#263247}body.villa-property-prestige .description{font-size:16px;line-height:2;color:#5f5b52}body.villa-property-prestige .booking{position:sticky;top:24px;border:1px solid #cdbd9d;border-radius:2px;background:#fffdf8;padding:34px;box-shadow:0 12px 35px #473a2410}body.villa-property-prestige .booking strong{font:500 31px Georgia,serif;color:#9c7a3e}body.villa-property-prestige .booking a{background:#1e2c40;border-radius:0;padding:17px;font:700 11px Inter,system-ui,sans-serif;letter-spacing:.13em;text-transform:uppercase}body.villa-property-prestige .photo-lightbox{background:rgba(13,19,28,.98)}body.villa-property-prestige .site-footer{background:#1e2c40}@media(max-width:760px){body.villa-property-prestige .wrap{width:calc(100% - 28px)}body.villa-property-prestige .gallery{grid-template-columns:repeat(2,minmax(0,1fr));grid-template-rows:minmax(245px,62vw) 110px;gap:6px}body.villa-property-prestige .gallery-tile:first-child{grid-column:1/-1;grid-row:1}body.villa-property-prestige .gallery-tile:nth-child(n+2){grid-row:2}body.villa-property-prestige .gallery-tile:nth-child(n+4){display:none}body.villa-property-prestige .gallery-count{bottom:120px}body.villa-property-prestige .body{grid-template-columns:1fr;gap:25px}body.villa-property-prestige .booking{position:static}}
CSS;
$html = str_replace('</head>', '<style>' . $themePropertyCss . '</style></head>', $html);
echo $html;
