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
$brand = '<a class="theme-brand theme-brand--coastal" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--coastal"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

$replaced = false;
if (preg_match('~<header\\b[^>]*>.*?</header>~s', $html)) {
    $html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
    $replaced = true;
}
if (!$replaced) $html = preg_replace('~<body\\b[^>]*>~i', '$0' . $header, $html, 1) ?? $html;
$menuScript = '<script>(function(){var b=document.getElementById("menuToggle"),n=document.getElementById("siteNavigation"),o=document.getElementById("menuOverlay");if(!b||!n||!o)return;function close(){n.classList.remove("is-open");o.classList.remove("is-open");b.setAttribute("aria-expanded","false");b.textContent="☰"}b.addEventListener("click",function(){var open=!n.classList.contains("is-open");n.classList.toggle("is-open",open);o.classList.toggle("is-open",open);b.setAttribute("aria-expanded",open?"true":"false");b.textContent=open?"×":"☰"});o.addEventListener("click",close);n.addEventListener("click",function(e){if(e.target.closest("a"))close()});document.addEventListener("keydown",function(e){if(e.key==="Escape")close()})})();</script>';
$html = preg_replace('~</body>~i', $menuScript . '</body>', $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-booking-mediterranean"', $html, 1) ?? $html;
$themeBookingCss = <<<'CSS'
body.villa-booking-mediterranean{background:#f6f6ef;color:#304235;font-family:Inter,system-ui,sans-serif}body.villa-booking-mediterranean .wrap{max-width:1080px;margin:55px auto}body.villa-booking-mediterranean h1{font:500 clamp(34px,4vw,48px) Georgia,serif;color:#294b36}body.villa-booking-mediterranean .summary{background:#e9f0e5;border:1px solid #dbe6d7;border-radius:22px;padding:25px;color:#38533e}body.villa-booking-mediterranean .panel{border:1px solid #e1e9de;border-radius:22px;padding:30px;box-shadow:0 15px 40px #28452d0b}body.villa-booking-mediterranean input{border-radius:13px;padding:15px;border-color:#d6e1d4}body.villa-booking-mediterranean .btn{background:#477c59;border-radius:30px;padding:16px 26px}body.villa-booking-mediterranean footer{background:#294536}
CSS;
$html = str_replace('</head>', '<style>' . $themeBookingCss . '</style></head>', $html);
echo $html;
