<?php
declare(strict_types=1);

// Theme-specific site chrome; the shared view retains its business flow and form handling.
ob_start();
require APP_ROOT . '/views/site/booking.php';
$html = (string) ob_get_clean();

// Booking view has its own footer navigation but intentionally no header navigation.
// Always build the header from the same configured navigation source as other site pages.
ob_start();
$navItems = $navigation ?? [];
$navAllowAuth = true;
require APP_ROOT . '/views/site/partials/navigation-items.php';
$navHtml = '<nav class="nav" id="siteNavigation">' . (string) ob_get_clean() . '</nav>';

$themeSiteName = (string) ($content['site_name'] ?? $siteName ?? 'VillaStay');
$brand = '<a class="theme-brand theme-brand--luxury" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--luxury"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

$replaced = false;
if (preg_match('~<header\\b[^>]*>.*?</header>~s', $html)) {
    $html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
    $replaced = true;
}
if (!$replaced) $html = preg_replace('~<body\\b[^>]*>~i', '$0' . $header, $html, 1) ?? $html;
$menuScript = '<script>(function(){var b=document.getElementById("menuToggle"),n=document.getElementById("siteNavigation"),o=document.getElementById("menuOverlay");if(!b||!n||!o)return;function close(){n.classList.remove("is-open");o.classList.remove("is-open");b.setAttribute("aria-expanded","false");b.textContent="☰"}b.addEventListener("click",function(){var open=!n.classList.contains("is-open");n.classList.toggle("is-open",open);o.classList.toggle("is-open",open);b.setAttribute("aria-expanded",open?"true":"false");b.textContent=open?"×":"☰"});o.addEventListener("click",close);n.addEventListener("click",function(e){if(e.target.closest("a"))close()});document.addEventListener("keydown",function(e){if(e.key==="Escape")close()})})();</script>';
$html = preg_replace('~</body>~i', $menuScript . '</body>', $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-booking-prestige"', $html, 1) ?? $html;
$themeBookingCss = <<<'CSS'
body.villa-booking-prestige{background:#f5f1e8;color:#282a2c;font-family:Georgia,serif}body.villa-booking-prestige .wrap{max-width:1160px;margin:48px auto}body.villa-booking-prestige h1{text-align:center;font:400 clamp(38px,5vw,60px) Georgia,serif;color:#202b3a}body.villa-booking-prestige .summary{background:#fffdf8;border:1px solid #cdbd9d;border-radius:0;padding:30px;color:#34332e}body.villa-booking-prestige .panel{background:#fffdf8;border:1px solid #d8c9ad;border-radius:0;padding:34px}body.villa-booking-prestige input{border-radius:0;border-color:#cfc5b2;padding:15px;background:#fffefa}body.villa-booking-prestige .btn{background:#1e2c40;border-radius:0;text-transform:uppercase;letter-spacing:.12em;font-size:11px;padding:17px 28px}body.villa-booking-prestige footer{background:#1e2c40}
CSS;
$html = str_replace('</head>', '<style>' . $themeBookingCss . '</style></head>', $html);
echo $html;
