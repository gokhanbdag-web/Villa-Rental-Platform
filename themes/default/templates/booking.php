<?php
declare(strict_types=1);

// Theme-specific site chrome; the shared view retains its business flow and form handling.
ob_start();
$pageTemplate = \App\Core\ThemeManager::resolveFile('templates/pages/booking.php');
if ($pageTemplate === null) throw new RuntimeException('Tema sayfa bileşeni bulunamadı: booking');
require $pageTemplate;
$html = (string) ob_get_clean();

ob_start();
require __DIR__ . '/partials/header.php';
$header = (string) ob_get_clean();
$replaced = false;
if (preg_match('~<header\\b[^>]*>.*?</header>~s', $html)) {
    $html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="(?:menuOverlay|siteMenuOverlay)"[^>]*></div>)?~s', $header, $html, 1) ?? $html;
    $replaced = true;
}
if (!$replaced) $html = preg_replace('~<body\\b[^>]*>~i', '$0' . $header, $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-booking-mediterranean"', $html, 1) ?? $html;
$themeBookingCss = <<<'CSS'
body.villa-booking-mediterranean{background:#f6f6ef;color:var(--villa-text);font-family:var(--villa-font-body);}body.villa-booking-mediterranean .wrap{max-width:1080px;margin:55px auto}body.villa-booking-mediterranean h1{font:inherit;color:var(--villa-text);}body.villa-booking-mediterranean .summary{background:#e9f0e5;border:1px solid #dbe6d7;border-radius:22px;padding:25px;color:var(--villa-text);}body.villa-booking-mediterranean .panel{border:1px solid #e1e9de;border-radius:22px;padding:30px;box-shadow:0 15px 40px #28452d0b}body.villa-booking-mediterranean input{border-radius:13px;padding:15px;border-color:#d6e1d4}body.villa-booking-mediterranean .btn{background:#477c59;border-radius:30px;padding:16px 26px}body.villa-booking-mediterranean footer{background:#294536}
CSS;
$html = str_replace('</head>', '<style>' . $themeBookingCss . '</style></head>', $html);
ob_start();
require __DIR__ . '/partials/footer.php';
$footerMarkup = (string) ob_get_clean();
$html = preg_replace('~</body>~i', $footerMarkup . '</body>', $html, 1) ?? $html;

echo $html;
