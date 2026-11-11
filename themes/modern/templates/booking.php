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
$brand = '<a class="theme-brand theme-brand--urban" href="' . e(app_url('/')) . '"><span class="theme-brand-name">' . e($themeSiteName) . '</span></a>';
$toggle = '<button class="mobile-menu-toggle theme-menu-toggle" id="menuToggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false"><span class="menu-open-mark">☰</span><span class="menu-close-mark">×</span></button>';
$header = '<header class="theme-header theme-header--urban"><div class="theme-header-inner">' . $brand . $toggle . $navHtml . '</div></header><div class="drawer-overlay" id="menuOverlay"></div>';

$replaced = false;
if (preg_match('~<header\\b[^>]*>.*?</header>~s', $html)) {
    $html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
    $replaced = true;
}
if (!$replaced) $html = preg_replace('~<body\\b[^>]*>~i', '$0' . $header, $html, 1) ?? $html;
$menuScript = '<script>(function(){var b=document.getElementById("menuToggle"),n=document.getElementById("siteNavigation"),o=document.getElementById("menuOverlay");if(!b||!n||!o)return;function close(){n.classList.remove("is-open");o.classList.remove("is-open");b.setAttribute("aria-expanded","false");b.textContent="☰"}b.addEventListener("click",function(){var open=!n.classList.contains("is-open");n.classList.toggle("is-open",open);o.classList.toggle("is-open",open);b.setAttribute("aria-expanded",open?"true":"false");b.textContent=open?"×":"☰"});o.addEventListener("click",close);n.addEventListener("click",function(e){if(e.target.closest("a"))close()});document.addEventListener("keydown",function(e){if(e.key==="Escape")close()})})();</script>';
$html = preg_replace('~</body>~i', $menuScript . '</body>', $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-booking-modern"', $html, 1) ?? $html;
$themeBookingCss = <<<'CSS'
body.villa-booking-modern{background:#111715;color:#edf1ed;font-family:Inter,system-ui,sans-serif}body.villa-booking-modern .wrap{max-width:1120px;margin:42px auto}body.villa-booking-modern h1{font-size:clamp(34px,4vw,52px);letter-spacing:-2px}body.villa-booking-modern .summary{background:#202a25;border:1px solid #3b4941;border-radius:5px;color:#eef3eb;padding:28px}body.villa-booking-modern .summary .muted{color:#aeb9b1}body.villa-booking-modern .panel{background:#1a231e;border:1px solid #354239;border-radius:5px;padding:28px}body.villa-booking-modern input{background:#111915;border-color:#455349;border-radius:3px;color:#fff}body.villa-booking-modern label{color:#d9e2da}body.villa-booking-modern .btn{background:#d9f07c;color:#172019;border-radius:3px;text-transform:uppercase;letter-spacing:.06em}body.villa-booking-modern footer{background:#090e0c}
CSS;
$html = str_replace('</head>', '<style>' . $themeBookingCss . '</style></head>', $html);
echo $html;
