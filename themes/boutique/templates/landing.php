<?php
declare(strict_types=1);

// Theme-specific presentation shell. The shared page supplies all business data and actions.
ob_start();
require APP_ROOT . '/views/site/landing.php';
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
$html = preg_replace('~<body\\b~i', '<body class="villa-home-boutique"', $html, 1) ?? $html;
$homeCss = <<<'CSS'
body.villa-home-boutique{background:#f1f2eb;color:#354238}body.villa-home-boutique .site-header{background:#f7f8f2;border-color:#dbe0d4}body.villa-home-boutique .hero{padding:30px 0 55px;background:#e7ebdf}body.villa-home-boutique .hero-card{min-height:510px;border-radius:8px;background-position:58% center;box-shadow:10px 12px 0 #d7ddcf}body.villa-home-boutique .hero-copy{max-width:760px;padding:48px 6%}body.villa-home-boutique .hero h1{font:500 clamp(42px,5.5vw,68px)/1.04 Georgia,serif;letter-spacing:-1.8px}body.villa-home-boutique .section{padding:60px 0}body.villa-home-boutique .section-head h2{font:500 clamp(28px,3.6vw,42px) Georgia,serif;color:#405344}body.villa-home-boutique .pill{background:#dfe6d9;color:#526b56;border-radius:4px}body.villa-home-boutique .destination-grid{grid-template-columns:1.2fr 1fr 1fr;gap:14px;align-items:start}body.villa-home-boutique .destination{height:240px;border-radius:5px}body.villa-home-boutique .destination:nth-child(3n+2){margin-top:36px}body.villa-home-boutique .cards{grid-template-columns:repeat(3,minmax(0,1fr));gap:20px 14px;align-items:start}body.villa-home-boutique .home-accommodations{background:#f1f2eb!important}body.villa-home-boutique .stay:nth-child(3n+2){margin-top:32px}body.villa-home-boutique .stay-photo{height:245px;border-radius:5px}body.villa-home-boutique .stay h3{font:500 19px Georgia,serif}body.villa-home-boutique .gallery-strip{grid-template-columns:1fr 1.2fr 1fr;grid-template-rows:150px 220px;gap:10px}body.villa-home-boutique .gallery-strip img{border-radius:5px}body.villa-home-boutique .gallery-strip img:first-child{grid-row:auto}body.villa-home-boutique .host-banner{border-radius:6px;box-shadow:8px 9px 0 #dce1d5}@media(max-width:700px){body.villa-home-boutique .hero-card{min-height:550px}body.villa-home-boutique .destination-grid{grid-template-columns:1fr 1fr}body.villa-home-boutique .destination{height:165px}body.villa-home-boutique .destination:nth-child(3n+2){margin-top:0}body.villa-home-boutique .cards{grid-template-columns:1fr 1fr;gap:14px 10px}body.villa-home-boutique .stay:nth-child(3n+2){margin-top:0}body.villa-home-boutique .stay-photo{height:155px}body.villa-home-boutique .section{padding:38px 0}}
@media(max-width:600px){body.villa-home-boutique .destination-grid,body.villa-home-boutique .cards{grid-template-columns:minmax(0,1fr)!important;gap:18px!important}body.villa-home-boutique .destination-grid>.destination,body.villa-home-boutique .cards>.stay{grid-column:1/-1!important;width:100%;margin:0!important}body.villa-home-boutique .destination{height:220px!important}body.villa-home-boutique .stay-photo{height:230px!important}}
CSS;
$menuIconCss = '.theme-menu-toggle .menu-open-mark{display:inline!important}.theme-menu-toggle .menu-close-mark{display:none!important}.theme-menu-toggle[aria-expanded="true"] .menu-open-mark{display:none!important}.theme-menu-toggle[aria-expanded="true"] .menu-close-mark{display:inline!important}';
$html = str_replace('</head>', '<style id="villa-menu-icon-state">' . $menuIconCss . '</style><style>' . $homeCss . '</style></head>', $html);
$html = str_replace('</head>', '<style>' . <<<'CSS'

/* Symmetric card system: remove intentional stagger and keep consistent columns. */
body.villa-home-boutique .destination-grid,
body.villa-home-boutique .cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));align-items:stretch;gap:24px}
body.villa-home-boutique .destination-grid>.destination,
body.villa-home-boutique .cards>.stay{margin:0!important;min-width:0;width:auto;align-self:stretch}
body.villa-home-boutique .destination{height:240px}
body.villa-home-boutique .stay-photo{height:245px}
@media(max-width:900px){
 body.villa-home-boutique .destination-grid,
 body.villa-home-boutique .cards{grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
 body.villa-home-boutique .destination-grid>.destination,
 body.villa-home-boutique .cards>.stay{margin:0!important}
}
@media(max-width:600px){
 body.villa-home-boutique .destination-grid,
 body.villa-home-boutique .cards{grid-template-columns:minmax(0,1fr)!important;gap:18px!important}
 body.villa-home-boutique .destination-grid>.destination,
 body.villa-home-boutique .cards>.stay{grid-column:1/-1!important;width:100%;margin:0!important}
 body.villa-home-boutique .destination{height:220px!important}
 body.villa-home-boutique .stay-photo{height:230px!important}
}
CSS
 . '</style></head>', $html);
echo $html;
