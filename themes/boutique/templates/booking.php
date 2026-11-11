<?php
declare(strict_types=1);

// Theme-specific site chrome; the shared view retains its business flow and form handling.
ob_start();
require APP_ROOT . '/views/site/booking.php';
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

$replaced = false;
if (preg_match('~<header\\b[^>]*>.*?</header>~s', $html)) {
    $html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
    $replaced = true;
}
if (!$replaced) $html = preg_replace('~<body\\b[^>]*>~i', '$0' . $header, $html, 1) ?? $html;
$menuScript = '<script>(function(){var b=document.getElementById("menuToggle"),n=document.getElementById("siteNavigation"),o=document.getElementById("menuOverlay");if(!b||!n||!o)return;function close(){n.classList.remove("is-open");o.classList.remove("is-open");b.setAttribute("aria-expanded","false");b.textContent="☰"}b.addEventListener("click",function(){var open=!n.classList.contains("is-open");n.classList.toggle("is-open",open);o.classList.toggle("is-open",open);b.setAttribute("aria-expanded",open?"true":"false");b.textContent=open?"×":"☰"});o.addEventListener("click",close);n.addEventListener("click",function(e){if(e.target.closest("a"))close()});document.addEventListener("keydown",function(e){if(e.key==="Escape")close()})})();</script>';
$html = preg_replace('~</body>~i', $menuScript . '</body>', $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-booking-boutique"', $html, 1) ?? $html;
$themeBookingCss = <<<'CSS'
body.villa-booking-boutique{background:#f3f2eb;color:#354238;font-family:Inter,system-ui,sans-serif}body.villa-booking-boutique .wrap{max-width:980px;margin:52px auto}body.villa-booking-boutique h1{font:500 clamp(36px,4.5vw,54px) Georgia,serif;color:#405344}body.villa-booking-boutique .summary{background:#e8ebe1;border:1px solid #d7dfd2;border-radius:3px;padding:24px;box-shadow:6px 6px 0 #e1e5da}body.villa-booking-boutique .panel{background:#fbfbf6;border:1px solid #d9dfd3;border-radius:3px;padding:28px;box-shadow:6px 6px 0 #e2e6dc}body.villa-booking-boutique input{border-radius:2px;border-color:#cfd8cb;background:#fff;padding:14px}body.villa-booking-boutique .btn{background:#637c63;border-radius:2px;padding:15px 24px}body.villa-booking-boutique footer{background:#35483b}
CSS;
$html = str_replace('</head>', '<style>' . $themeBookingCss . '</style></head>', $html);
echo $html;
