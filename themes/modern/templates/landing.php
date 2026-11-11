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
$brand = '<a class="theme-brand theme-brand--urban" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--urban"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-home-modern"', $html, 1) ?? $html;
$homeCss = <<<'CSS'
body.villa-home-modern{background:#101613;color:#eef2ed}body.villa-home-modern .site-header{background:#101613;border-color:#354139}body.villa-home-modern .hero{padding:0 0 54px;background:#101613}body.villa-home-modern .hero-card{min-height:620px;border-radius:0;background-position:center;box-shadow:none}body.villa-home-modern .hero-copy{max-width:920px;padding-left:6%;}body.villa-home-modern .eyebrow{color:#d9ef7b}body.villa-home-modern .hero h1{font-size:clamp(48px,7vw,88px);line-height:.94;letter-spacing:-4px;text-transform:uppercase;max-width:850px}body.villa-home-modern .hero p{color:#d1d9d2}body.villa-home-modern .section{padding:58px 0;border-top:1px solid #28332c}body.villa-home-modern .section-head h2{font-size:clamp(30px,4vw,46px);letter-spacing:-2px;text-transform:uppercase}body.villa-home-modern .section-head p,body.villa-home-modern .muted{color:#aab5ad}body.villa-home-modern .pill{background:#28332c;color:#d9ef7b;border-radius:2px}body.villa-home-modern .destination-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}body.villa-home-modern .destination{height:260px;border-radius:2px}body.villa-home-modern .cards{grid-template-columns:repeat(4,minmax(0,1fr));gap:13px}body.villa-home-modern .home-accommodations{background:#101613!important}body.villa-home-modern .stay-photo{height:220px;border-radius:2px}body.villa-home-modern .stay h3{text-transform:uppercase;font-size:14px;letter-spacing:.03em}body.villa-home-modern .stay-price strong,body.villa-home-modern .rating-score{color:#d9ef7b!important}body.villa-home-modern .gallery-strip{grid-template-columns:repeat(4,minmax(0,1fr));grid-template-rows:280px;gap:8px}body.villa-home-modern .gallery-strip img,body.villa-home-modern .host-banner{border-radius:2px}body.villa-home-modern .host-banner{filter:saturate(.75)}body.villa-home-modern footer{background:#080d0b}@media(max-width:850px){body.villa-home-modern .cards{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){body.villa-home-modern .hero-card{min-height:570px}body.villa-home-modern .hero h1{font-size:46px;letter-spacing:-2px}body.villa-home-modern .cards{gap:12px}body.villa-home-modern .stay-photo{height:150px}body.villa-home-modern .destination{height:165px}body.villa-home-modern .gallery-strip{grid-template-columns:1fr 1fr;grid-template-rows:130px 130px}}
CSS;
$menuIconCss = '.theme-menu-toggle .menu-open-mark{display:inline!important}.theme-menu-toggle .menu-close-mark{display:none!important}.theme-menu-toggle[aria-expanded="true"] .menu-open-mark{display:none!important}.theme-menu-toggle[aria-expanded="true"] .menu-close-mark{display:inline!important}';
$html = str_replace('</head>', '<style id="villa-menu-icon-state">' . $menuIconCss . '</style><style>' . $homeCss . '</style></head>', $html);
echo $html;
