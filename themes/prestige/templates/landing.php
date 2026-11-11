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
$brand = '<a class="theme-brand theme-brand--luxury" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--luxury"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-home-prestige"', $html, 1) ?? $html;
$homeCss = <<<'CSS'
body.villa-home-prestige{background:#f4f0e7;color:#252d39}body.villa-home-prestige .site-header{background:#f4f0e7;border-color:#d8c9a9}body.villa-home-prestige .hero{padding:0 0 50px;background:#1e2c40}body.villa-home-prestige .hero-card{min-height:600px;border-radius:0;background-position:center 48%}body.villa-home-prestige .hero-copy{max-width:900px;margin:auto;text-align:center;padding:55px 7%}body.villa-home-prestige .eyebrow{color:#e7d2a2;letter-spacing:4px}body.villa-home-prestige .hero h1{font:400 clamp(44px,6vw,76px)/1.04 Georgia,serif;letter-spacing:-1.5px}body.villa-home-prestige .hero p{margin-left:auto;margin-right:auto;color:#f0e8d9}body.villa-home-prestige .section{padding:70px 0}body.villa-home-prestige .section-head{justify-content:center;text-align:center}body.villa-home-prestige .section-head h2{font:400 clamp(30px,4vw,46px) Georgia,serif;color:#1e2c40}body.villa-home-prestige .pill{background:#e9dfcb;color:#7b6338;border-radius:1px;letter-spacing:1.5px}body.villa-home-prestige .destination-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}body.villa-home-prestige .destination{height:300px;border-radius:0;margin:0!important;align-self:stretch}body.villa-home-prestige .cards{grid-template-columns:repeat(2,minmax(0,1fr));gap:32px}body.villa-home-prestige .home-accommodations{background:#f4f0e7!important}body.villa-home-prestige .stay-photo{height:330px;border-radius:0}body.villa-home-prestige .stay h3{font:400 23px Georgia,serif}body.villa-home-prestige .stay-price strong{color:#1e2c40}body.villa-home-prestige .gallery-strip{grid-template-columns:1fr 1fr 1fr;grid-template-rows:230px 150px}body.villa-home-prestige .gallery-strip img{border-radius:0}body.villa-home-prestige .gallery-strip img:first-child{grid-row:span 2}body.villa-home-prestige .host-banner{border-radius:0}@media(max-width:650px){body.villa-home-prestige .hero-card{min-height:560px}body.villa-home-prestige .hero h1{font-size:43px}body.villa-home-prestige .destination-grid{grid-template-columns:1fr 1fr}body.villa-home-prestige .destination{height:170px}body.villa-home-prestige .destination:nth-child(3n+2){margin-top:0}body.villa-home-prestige .cards{grid-template-columns:1fr 1fr;gap:14px}body.villa-home-prestige .stay-photo{height:160px}body.villa-home-prestige .section{padding:40px 0}}
@media(max-width:600px){body.villa-home-prestige .destination-grid,body.villa-home-prestige .cards{grid-template-columns:minmax(0,1fr)!important;gap:18px!important}body.villa-home-prestige .destination-grid>.destination,body.villa-home-prestige .cards>.stay{grid-column:1/-1!important;width:100%;margin:0!important}body.villa-home-prestige .destination{height:230px!important}body.villa-home-prestige .stay-photo{height:230px!important}}
CSS;
$menuIconCss = '.theme-menu-toggle .menu-open-mark{display:inline!important}.theme-menu-toggle .menu-close-mark{display:none!important}.theme-menu-toggle[aria-expanded="true"] .menu-open-mark{display:none!important}.theme-menu-toggle[aria-expanded="true"] .menu-close-mark{display:inline!important}';
$html = str_replace('</head>', '<style id="villa-menu-icon-state">' . $menuIconCss . '</style><style>' . $homeCss . '</style></head>', $html);
echo $html;
