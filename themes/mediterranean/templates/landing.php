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
$brand = '<a class="theme-brand theme-brand--coastal" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--coastal"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-home-mediterranean"', $html, 1) ?? $html;
$homeCss = <<<'CSS'
body.villa-home-mediterranean{background:#f8f8f1;color:#304235}body.villa-home-mediterranean .site-header{background:#fbfcf7;border-color:#e4e9dc}body.villa-home-mediterranean .hero{padding:24px 0 54px;background:#eef2e7}body.villa-home-mediterranean .hero-card{min-height:530px;border-radius:30px;background-position:center 55%;box-shadow:0 22px 55px #344c3518}body.villa-home-mediterranean .hero-copy{max-width:850px}body.villa-home-mediterranean .hero h1{font:500 clamp(42px,6vw,72px)/1.02 Georgia,serif;letter-spacing:-2px}body.villa-home-mediterranean .section{padding:66px 0}body.villa-home-mediterranean .section-head h2{font:500 clamp(29px,3.4vw,40px) Georgia,serif;color:#294936}body.villa-home-mediterranean .destination-grid{grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}body.villa-home-mediterranean .destination{height:250px;border-radius:22px}body.villa-home-mediterranean .cards{grid-template-columns:repeat(3,minmax(0,1fr));gap:25px}body.villa-home-mediterranean .stay-photo{height:250px;border-radius:22px}body.villa-home-mediterranean .stay h3{font:500 20px Georgia,serif}body.villa-home-mediterranean .home-accommodations{background:#f8f8f1!important}body.villa-home-mediterranean .gallery-strip{grid-template-columns:1.5fr 1fr 1fr;grid-template-rows:210px 210px;gap:15px}body.villa-home-mediterranean .gallery-strip img{border-radius:22px}body.villa-home-mediterranean .host-banner{border-radius:30px}@media(max-width:700px){body.villa-home-mediterranean .hero-card{min-height:560px;border-radius:20px}body.villa-home-mediterranean .cards{grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}body.villa-home-mediterranean .stay-photo{height:155px}body.villa-home-mediterranean .destination{height:165px}body.villa-home-mediterranean .section{padding:38px 0}}
CSS;
$menuIconCss = '.theme-menu-toggle .menu-open-mark{display:inline!important}.theme-menu-toggle .menu-close-mark{display:none!important}.theme-menu-toggle[aria-expanded="true"] .menu-open-mark{display:none!important}.theme-menu-toggle[aria-expanded="true"] .menu-close-mark{display:inline!important}';
$html = str_replace('</head>', '<style id="villa-menu-icon-state">' . $menuIconCss . '</style><style>' . $homeCss . '</style></head>', $html);
echo $html;
