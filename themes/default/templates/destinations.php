<?php
declare(strict_types=1);

// Theme-specific site chrome; the shared view retains its business flow and form handling.
ob_start();
$pageTemplate = \App\Core\ThemeManager::resolveFile('templates/pages/destinations.php');
if ($pageTemplate === null) throw new RuntimeException('Tema sayfa bileşeni bulunamadı: destinations');
require $pageTemplate;
$html = (string) ob_get_clean();

ob_start();
require __DIR__ . '/partials/header.php';
$header = (string) ob_get_clean();
$html = preg_replace('~<div class="nav">.*?</nav></div>~s', '', $html, 1) ?? $html;
$html = preg_replace('~<body\\b[^>]*>~i', '$0' . $header, $html, 1) ?? $html;
$html = preg_replace('~<body\\b~i', '<body class="villa-destinations-mediterranean"', $html, 1) ?? $html;
$themeDestinationsCss = <<<'CSS'
body.villa-destinations-mediterranean{background:#f7f7f1;color:#2d4032}body.villa-destinations-mediterranean .wrap{max-width:1280px;margin:34px auto}body.villa-destinations-mediterranean .wrap>h1{font:500 clamp(38px,5vw,62px) Georgia,serif;max-width:720px;color:#2c4b36;margin:52px 0 10px}body.villa-destinations-mediterranean .wrap>p{max-width:560px;color:#718071;line-height:1.8;margin-bottom:32px}body.villa-destinations-mediterranean .grid{grid-template-columns:1.35fr 1fr 1fr;grid-auto-rows:245px;gap:16px}body.villa-destinations-mediterranean .card{height:100%;border-radius:24px;box-shadow:0 12px 35px #28452d12}body.villa-destinations-mediterranean .card:first-child{grid-row:span 2;min-height:506px}body.villa-destinations-mediterranean .card strong{font:500 25px Georgia,serif;left:22px;bottom:22px}body.villa-destinations-mediterranean .card:after{background:linear-gradient(transparent 35%,#132a1dcc)}@media(max-width:760px){body.villa-destinations-mediterranean .grid{grid-template-columns:1fr 1fr;grid-auto-rows:180px;gap:9px}body.villa-destinations-mediterranean .card:first-child{grid-row:auto;min-height:180px}body.villa-destinations-mediterranean .wrap>h1{margin-top:32px}}
CSS;
$html = str_replace('</head>', '<style>' . $themeDestinationsCss . '</style></head>', $html);
echo $html;
