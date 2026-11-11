<?php
declare(strict_types=1);

// Theme-specific site chrome; the shared view retains its business flow and form handling.
ob_start();
require APP_ROOT . '/views/site/destinations.php';
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

$replaced = false;
if (preg_match('~<header\\b[^>]*>.*?</header>~s', $html)) {
    $html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
    $replaced = true;
} elseif (preg_match('~<div class="nav">.*?</nav></div>~s', $html)) {
    $html = preg_replace('~<div class="nav">.*?</nav></div>~s', $header, $html, 1) ?? $html;
    $replaced = true;
}
if (!$replaced) $html = preg_replace('~<body\\b[^>]*>~i', '$0' . $header, $html, 1) ?? $html;
$menuScript = '<script>(function(){var b=document.getElementById("menuToggle"),n=document.getElementById("siteNavigation"),o=document.getElementById("menuOverlay");if(!b||!n||!o)return;function close(){n.classList.remove("is-open");o.classList.remove("is-open");b.setAttribute("aria-expanded","false");b.textContent="☰"}b.addEventListener("click",function(){var open=!n.classList.contains("is-open");n.classList.toggle("is-open",open);o.classList.toggle("is-open",open);b.setAttribute("aria-expanded",open?"true":"false");b.textContent=open?"×":"☰"});o.addEventListener("click",close);n.addEventListener("click",function(e){if(e.target.closest("a"))close()});document.addEventListener("keydown",function(e){if(e.key==="Escape")close()})})();</script>';
$html = preg_replace('~</body>~i', $menuScript . '</body>', $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-destinations-modern"', $html, 1) ?? $html;
$themeDestinationsCss = <<<'CSS'
body.villa-destinations-modern{background:#111715;color:#eef2ed}body.villa-destinations-modern .wrap{max-width:1360px;margin:25px auto}body.villa-destinations-modern .wrap>h1{font-size:clamp(42px,6vw,76px);letter-spacing:-3px;line-height:.98;max-width:800px;margin:65px 0 15px;text-transform:uppercase}body.villa-destinations-modern .wrap>p{color:#aab5ad;border-left:3px solid #d9f07c;padding:8px 18px;margin-bottom:35px;max-width:560px}body.villa-destinations-modern .grid{grid-template-columns:repeat(12,minmax(0,1fr));grid-auto-rows:190px;gap:7px}body.villa-destinations-modern .card{height:100%;border-radius:2px;grid-column:span 3}body.villa-destinations-modern .card:nth-child(5n+1){grid-column:span 6;grid-row:span 2}body.villa-destinations-modern .card:nth-child(5n+2){grid-column:span 3}body.villa-destinations-modern .card strong{font-size:18px;text-transform:uppercase;letter-spacing:.03em}body.villa-destinations-modern .card:after{background:linear-gradient(transparent 30%,#080e0bcc)}body.villa-destinations-modern .card img{filter:saturate(.72)}@media(max-width:760px){body.villa-destinations-modern .grid{grid-template-columns:1fr 1fr;grid-auto-rows:165px}body.villa-destinations-modern .card,body.villa-destinations-modern .card:nth-child(5n+1),body.villa-destinations-modern .card:nth-child(5n+2){grid-column:span 1;grid-row:auto}body.villa-destinations-modern .wrap>h1{margin-top:38px}}

@media(min-width:761px){body.villa-destinations-modern .theme-header .nav .nav-dropdown .nav-entry-top>a{color:#263241!important;font-weight:700;text-transform:none;letter-spacing:0}body.villa-destinations-modern .theme-header .nav .nav-dropdown .nav-entry-top>a:hover{color:#111923!important;background:#edf1f5}}
CSS;
$html = str_replace('</head>', '<style>' . $themeDestinationsCss . '</style></head>', $html);
echo $html;
