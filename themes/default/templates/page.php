<?php
declare(strict_types=1);

// Theme-specific site chrome; the shared view retains its business flow and form handling.
ob_start();
$pageTemplate = \App\Core\ThemeManager::resolveFile('templates/pages/page.php');
if ($pageTemplate === null) throw new RuntimeException('Tema sayfa bileşeni bulunamadı: page');
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

$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="(?:menuOverlay|siteMenuOverlay)"[^>]*></div>)?~s', $header, $html, 1) ?? $html;
$menuScript = '<script>(function(){var b=document.getElementById("menuToggle"),n=document.getElementById("siteNavigation"),o=document.getElementById("menuOverlay");if(!b||!n||!o||b.dataset.menuReady==="1")return;b.dataset.menuReady="1";function set(open){n.classList.toggle("is-open",open);o.classList.toggle("is-open",open);b.setAttribute("aria-expanded",open?"true":"false");b.setAttribute("aria-label",open?"Menüyü kapat":"Menüyü aç");document.body.classList.toggle("menu-open",open)}b.addEventListener("click",function(){set(b.getAttribute("aria-expanded")!=="true")});o.addEventListener("click",function(){set(false)});n.addEventListener("click",function(e){if(e.target.closest("a"))set(false)});document.addEventListener("keydown",function(e){if(e.key==="Escape")set(false)})})();</script>';
$html = preg_replace('~</body>~i', $menuScript . '</body>', $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-page-mediterranean"', $html, 1) ?? $html;
$themePageCss = <<<'CSS'
body.villa-page-mediterranean{background:#f7f7f1;color:var(--villa-text);}body.villa-page-mediterranean .wrap{max-width:1080px;margin:55px auto}body.villa-page-mediterranean main.wrap{padding:0 28px}body.villa-page-mediterranean main h1{font:inherit;color:var(--villa-text);max-width:820px;margin:55px 0 25px}body.villa-page-mediterranean main article{font:inherit;color:var(--villa-text);max-width:820px;border-left:3px solid #b7c8ae;padding:12px 0 12px 28px}body.villa-page-mediterranean .site-footer{background:#294536}
CSS;
$html = str_replace('</head>', '<style>' . $themePageCss . '</style></head>', $html);
echo $html;
