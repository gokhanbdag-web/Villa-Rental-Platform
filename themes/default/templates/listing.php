<?php
declare(strict_types=1);

// Theme-specific presentation shell. The shared page supplies all business data and actions.
ob_start();
$pageTemplate = \App\Core\ThemeManager::resolveFile('templates/pages/listing.php');
if ($pageTemplate === null) throw new RuntimeException('Tema sayfa bileşeni bulunamadı: listing');
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

$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="menuOverlay"></div>)?~s', $header, $html, 1) ?? $html;
<script>(function(){var b=document.getElementById("menuToggle"),n=document.getElementById("siteNavigation"),o=document.getElementById("menuOverlay");if(!b||!n||!o||b.dataset.menuReady==="1")return;b.dataset.menuReady="1";function set(open){n.classList.toggle("is-open",open);o.classList.toggle("is-open",open);b.setAttribute("aria-expanded",open?"true":"false");b.setAttribute("aria-label",open?"Menüyü kapat":"Menüyü aç");document.body.classList.toggle("menu-open",open)}b.addEventListener("click",function(){set(b.getAttribute("aria-expanded")!=="true")});o.addEventListener("click",function(){set(false)});n.addEventListener("click",function(e){if(e.target.closest("a"))set(false)});document.addEventListener("keydown",function(e){if(e.key==="Escape")set(false)})})();</script>
$html = preg_replace('~<body\\b~i', '<body class="villa-listing-mediterranean"', $html, 1) ?? $html;
$listingCss = <<<'CSS'
body{background:#f7f7f1;color:var(--villa-text);}.intro{background:linear-gradient(120deg,#e9efe3,#f7f7f1);padding:55px 0 35px}.intro h1{font:inherit;color:var(--villa-text);max-width:720px}.filter-panel{border-radius:24px;box-shadow:0 18px 45px #29493612}.filters{grid-template-columns:repeat(4,minmax(0,1fr))}.grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:28px}.photo{height:265px;border-radius:22px}.stay h3{font:inherit;color:var(--villa-text);}.submit{background:#456d50}.rating-line .rating-score{color:var(--villa-text);}@media(max-width:650px){.intro{padding:30px 0 24px}.grid{grid-template-columns:repeat(2,minmax(0,1fr))}.photo{height:155px}}
CSS;
$html = str_replace('</head>', '<style>' . $listingCss . '</style></head>', $html);
$html = str_replace('</head>', '<style>' . <<<'CSS'

/* Listing search layout: spacious three-column desktop grid with balanced controls. */
body .filter-panel{padding:clamp(18px,2.2vw,26px);border-radius:18px}
body .filter-panel .filters{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px 16px;align-items:end}
body .filter-panel .field{min-width:0;width:100%}
body .filter-panel .field label{display:block;min-height:17px;margin:0 0 8px;line-height:var(--villa-body-line-height);font-size:var(--villa-body-size);letter-spacing:.055em}
body .filter-panel .field input:not([type=hidden]),body .filter-panel .field select{display:block;width:100%;min-width:0;max-width:100%;height:50px;min-height:50px;padding:0 14px;margin:0;box-sizing:border-box;border:1px solid var(--line,#dfe6e0);border-radius:10px;font:inherit;vertical-align:middle}
body .filter-panel .field input[type=date]{padding:0 10px}
body .filter-panel .price-filter{grid-column:span 2;min-width:0;min-height:75px;padding:12px 16px;box-sizing:border-box;border-radius:10px}
body .filter-panel .submit{grid-column:span 1;width:100%;min-width:0;min-height:50px;height:50px;padding:0 16px;box-sizing:border-box;white-space:normal;line-height:var(--villa-body-line-height);}
@media(max-width:900px){body .filter-panel .filters{grid-template-columns:repeat(2,minmax(0,1fr));gap:15px 13px}body .filter-panel .price-filter,body .filter-panel .submit{grid-column:1/-1}}
@media(max-width:600px){body .filter-panel{padding:14px}body .filter-panel .filters{grid-template-columns:minmax(0,1fr);gap:13px}body .filter-panel .field input:not([type=hidden]),body .filter-panel .field select{height:48px;min-height:48px}body .filter-panel .price-filter,body .filter-panel .submit{grid-column:1/-1}}
CSS
 . '</style></head>', $html);
echo $html;
