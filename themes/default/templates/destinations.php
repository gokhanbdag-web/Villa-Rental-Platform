<?php
declare(strict_types=1);

// Theme-specific site chrome; the shared view retains its business flow and form handling.
ob_start();
$pageTemplate = \App\\Core\\ThemeManager::resolveFile('templates/pages/destinations.php');
if ($pageTemplate === null) throw new RuntimeException('Tema sayfa bileşeni bulunamadı: destinations');
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
    \App\\Core\\ThemeManager::renderPartial('partials/navigation-items.php', get_defined_vars());
    $navHtml = '<nav class="nav" id="siteNavigation">' . (string) ob_get_clean() . '</nav>';
}

$themeSiteName = (string) ($content['site_name'] ?? $siteName ?? 'VillaStay');
$brand = '<a class="theme-brand theme-brand--coastal" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--coastal"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

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
$html = preg_replace('~<body\\b~i', '<body class="villa-destinations-mediterranean"', $html, 1) ?? $html;
$themeDestinationsCss = <<<'CSS'
body.villa-destinations-mediterranean{background:#f7f7f1;color:#2d4032}body.villa-destinations-mediterranean .wrap{max-width:1280px;margin:34px auto}body.villa-destinations-mediterranean .wrap>h1{font:500 clamp(38px,5vw,62px) Georgia,serif;max-width:720px;color:#2c4b36;margin:52px 0 10px}body.villa-destinations-mediterranean .wrap>p{max-width:560px;color:#718071;line-height:1.8;margin-bottom:32px}body.villa-destinations-mediterranean .grid{grid-template-columns:1.35fr 1fr 1fr;grid-auto-rows:245px;gap:16px}body.villa-destinations-mediterranean .card{height:100%;border-radius:24px;box-shadow:0 12px 35px #28452d12}body.villa-destinations-mediterranean .card:first-child{grid-row:span 2;min-height:506px}body.villa-destinations-mediterranean .card strong{font:500 25px Georgia,serif;left:22px;bottom:22px}body.villa-destinations-mediterranean .card:after{background:linear-gradient(transparent 35%,#132a1dcc)}@media(max-width:760px){body.villa-destinations-mediterranean .grid{grid-template-columns:1fr 1fr;grid-auto-rows:180px;gap:9px}body.villa-destinations-mediterranean .card:first-child{grid-row:auto;min-height:180px}body.villa-destinations-mediterranean .wrap>h1{margin-top:32px}}
CSS;
$html = str_replace('</head>', '<style>' . $themeDestinationsCss . '</style></head>', $html);
echo $html;
