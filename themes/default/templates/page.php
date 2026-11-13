<?php
declare(strict_types=1);

// Theme-specific site chrome; the shared view retains its business flow and form handling.
ob_start();
$pageTemplate = \App\Core\ThemeManager::resolveFile('templates/pages/page.php');
if ($pageTemplate === null) throw new RuntimeException('Tema sayfa bileşeni bulunamadı: page');
require $pageTemplate;
$html = (string) ob_get_clean();

ob_start();
require __DIR__ . '/partials/header.php';
$header = (string) ob_get_clean();
$html = preg_replace('~<header\\b[^>]*>.*?</header>(?:\\s*<div class="drawer-overlay" id="(?:menuOverlay|siteMenuOverlay)"[^>]*></div>)?~s', $header, $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-page-mediterranean"', $html, 1) ?? $html;
$themePageCss = <<<'CSS'
body.villa-page-mediterranean{background:#f7f7f1;color:var(--villa-text);}body.villa-page-mediterranean .wrap{max-width:1080px;margin:55px auto}body.villa-page-mediterranean main.wrap{padding:0 28px}body.villa-page-mediterranean main h1{font:inherit;color:var(--villa-text);max-width:820px;margin:55px 0 25px}body.villa-page-mediterranean main article{font:inherit;color:var(--villa-text);max-width:820px;border-left:3px solid #b7c8ae;padding:12px 0 12px 28px}body.villa-page-mediterranean .site-footer{background:#294536}
CSS;
$html = str_replace('</head>', '<style>' . $themePageCss . '</style></head>', $html);
ob_start();
require __DIR__ . '/partials/footer.php';
$footerMarkup = (string) ob_get_clean();
$html = preg_replace('~</body>~i', $footerMarkup . '</body>', $html, 1) ?? $html;

echo $html;
