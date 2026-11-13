<?php
declare(strict_types=1);

// Theme-specific presentation shell. The shared page supplies all business data and actions.
ob_start();
$pageTemplate = \App\Core\ThemeManager::resolveFile('templates/pages/property.php');
if ($pageTemplate === null) throw new RuntimeException('Tema sayfa bileşeni bulunamadı: property');
require $pageTemplate;
$html = (string) ob_get_clean();

$navHtml = '';
if (preg_match('~<nav\\b[^>]*>.*?</nav>~s', $html, $navMatch)) {
    $navHtml = preg_replace('~<nav\\b[^>]*>~', '<nav class="nav" id="siteNavigation">', $navMatch[0], 1) ?? $navMatch[0];
}
if ($navHtml === '') {
    ob_start();
    $navItems = $navigation ?? [];
    $navAllowAuth = true;
    \App\Core\ThemeManager::renderPartial('partials/navigation-items.php', get_defined_vars());
    $navHtml = '<nav class="nav" id="siteNavigation">' . (string) ob_get_clean() . '</nav>';
}

$themeSiteName = (string) ($content['site_name'] ?? $siteName ?? 'VillaStay');
$brand = '<a class="theme-brand theme-brand--coastal" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--coastal"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
if (stripos($html, '</body>') !== false) {
    $menuScript = '<script>(function(){var b=document.getElementById("menuToggle"),n=document.getElementById("siteNavigation"),o=document.getElementById("menuOverlay");if(!b||!n||!o)return;b.addEventListener("click",function(){requestAnimationFrame(function(){o.classList.toggle("is-open",b.getAttribute("aria-expanded")==="true")})});o.addEventListener("click",function(){if(b.getAttribute("aria-expanded")==="true")b.click()})})();</script>';
    $html = preg_replace('~</body>~i', $menuScript . '</body>', $html, 1) ?? $html;
}
$html = preg_replace('~<body\\b~i', '<body class="villa-property-mediterranean"', $html, 1) ?? $html;
$themePropertyCss = <<<'CSS'
body.villa-property-mediterranean{background:#f7f7f1;color:var(--villa-text);font-family:var(--villa-font-body);}body.villa-property-mediterranean .wrap{width:min(1240px,calc(100% - 56px))}body.villa-property-mediterranean .crumb{color:var(--villa-text);padding:28px 0 18px;font:inherit;}body.villa-property-mediterranean .title{align-items:center;padding:10px 0 5px}body.villa-property-mediterranean .title h1{font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);letter-spacing:-1.8px;color:var(--villa-text);}body.villa-property-mediterranean .title .muted{font:inherit;}body.villa-property-mediterranean .price{color:var(--villa-text);font:inherit;}body.villa-property-mediterranean .gallery{grid-template-columns:1.55fr 1fr 1fr;grid-template-rows:270px 220px;gap:12px;border-radius:28px;margin:25px 0 48px;background:#e7ece4}body.villa-property-mediterranean .gallery-tile:first-child{grid-row:span 2}body.villa-property-mediterranean .gallery-tile:nth-child(2){border-radius:0 24px 0 0}body.villa-property-mediterranean .gallery-tile:nth-child(5){border-radius:0 0 24px 0}body.villa-property-mediterranean .gallery-count{border:0;border-radius:30px;background:#fff;color:var(--villa-text);}body.villa-property-mediterranean .body{grid-template-columns:minmax(0,1fr) 350px;gap:76px;align-items:start}body.villa-property-mediterranean .body h2{font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);color:var(--villa-text);}body.villa-property-mediterranean .description{font:inherit;color:var(--villa-text);}body.villa-property-mediterranean .booking{position:sticky;top:24px;border:1px solid #dfe8dc;border-radius:26px;background:#fff;padding:30px;box-shadow:0 18px 55px #243c2812}body.villa-property-mediterranean .booking strong{font-size:var(--villa-body-size);color:var(--villa-text);}body.villa-property-mediterranean .booking a{background:#477c59;border-radius:30px;padding:16px}body.villa-property-mediterranean .photo-lightbox{background:rgba(19,35,26,.97)}@media(max-width:760px){body.villa-property-mediterranean .wrap{width:calc(100% - 28px)}body.villa-property-mediterranean .gallery{grid-template-rows:minmax(230px,58vw) minmax(130px,34vw);gap:7px;border-radius:17px}body.villa-property-mediterranean .body{grid-template-columns:1fr;gap:24px}body.villa-property-mediterranean .booking{position:static}}
body.villa-property-mediterranean .property-layout{align-items:start}body.villa-property-mediterranean .property-main{min-width:0}body.villa-property-mediterranean .property-amenities-title{margin:34px 0 16px;font:inherit;color:var(--villa-text);}body.villa-property-mediterranean .property-amenities{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:11px}body.villa-property-mediterranean .property-amenities li{padding:14px 16px;border:1px solid #e0e8dd;border-radius:13px;background:#fff;font:inherit;color:var(--villa-text);}body.villa-property-mediterranean .property-reviews{margin-top:48px!important;padding-top:28px;border-top:1px solid #dfe7dc}body.villa-property-mediterranean .property-review{padding:18px 0;border-top:1px solid #e5ebe2}body.villa-property-mediterranean .property-booking p{font-size:var(--villa-body-size);} @media(max-width:760px){body.villa-property-mediterranean .property-amenities{grid-template-columns:1fr 1fr}}
CSS;
$html = str_replace('</head>', '<style>' . $themePropertyCss . '</style></head>', $html);
echo $html;
