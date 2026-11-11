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
$brand = '<a class="theme-brand theme-brand--luxury" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--luxury"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

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
$html = preg_replace('~<body\\b~i', '<body class="villa-destinations-prestige"', $html, 1) ?? $html;
$themeDestinationsCss = <<<'CSS'
body.villa-destinations-prestige{background:#f5f1e8;color:#272b32}body.villa-destinations-prestige .wrap{max-width:1400px;margin:0 auto;padding:0 35px}body.villa-destinations-prestige .wrap>h1{text-align:center;font:400 clamp(40px,5vw,68px) Georgia,serif;color:#202b3a;margin:68px 0 12px}body.villa-destinations-prestige .wrap>p{text-align:center;color:#837c6e;font:13px/1.8 Inter,system-ui,sans-serif;letter-spacing:.04em;margin:0 auto 42px}body.villa-destinations-prestige .grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:22px}body.villa-destinations-prestige .card{height:360px;border-radius:0}body.villa-destinations-prestige .card:nth-child(3n+2){margin-top:42px}body.villa-destinations-prestige .card strong{left:22px;bottom:24px;font:400 27px Georgia,serif;letter-spacing:.02em}body.villa-destinations-prestige .card:after{background:linear-gradient(transparent 30%,#101c2ccc)}@media(max-width:760px){body.villa-destinations-prestige .wrap{padding:0 15px}body.villa-destinations-prestige .grid{grid-template-columns:1fr 1fr;gap:9px}body.villa-destinations-prestige .card{height:210px;margin:0!important}body.villa-destinations-prestige .card strong{font-size:17px;left:12px;bottom:12px}}
CSS;
$html = str_replace('</head>', '<style>' . $themeDestinationsCss . '</style></head>', $html);
echo $html;
