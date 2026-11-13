<?php
declare(strict_types=1);

// Theme-specific presentation shell. The shared page supplies all business data and actions.
ob_start();
$pageTemplate = \App\Core\ThemeManager::resolveFile('templates/pages/listing.php');
if ($pageTemplate === null) throw new RuntimeException('Tema sayfa bileşeni bulunamadı: listing');
require $pageTemplate;
$html = (string) ob_get_clean();

ob_start();
require __DIR__ . '/partials/header.php';
$header = (string) ob_get_clean();
$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="(?:menuOverlay|siteMenuOverlay)"[^>]*></div>)?~s', $header, $html, 1) ?? $html;
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
ob_start();
require __DIR__ . '/partials/footer.php';
$footerMarkup = (string) ob_get_clean();
$html = preg_replace('~</body>~i', $footerMarkup . '</body>', $html, 1) ?? $html;

echo $html;
