<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="<?= e($locale ?? 'tr') ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page['meta_title'] ?: $page['title']) ?></title>
<meta name="description" content="<?= e($page['meta_description']??'') ?>">
<?php $pageCssPath = \\App\\Core\\ThemeManager::resolveFile('pages/page.css', 'default'); if ($pageCssPath !== null): ?><link rel="stylesheet" href="<?= e(\\App\\Core\\ThemeManager::assetUrl('pages/page.css', 'default')) ?>?v=<?= (int)filemtime($pageCssPath) ?>"><?php endif; ?>
</head>
<body>
<header class="site-header"><div class="wrap site-head">
<a class="site-brand" href="/"><?= e($siteName ?? 'VillaStay') ?></a>
<button class="mobile-menu-toggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false">☰</button>
<nav class="site-nav" id="siteNavigation"><?php $navItems=$navigation??[];$navAllowAuth=true;\App\Core\ThemeManager::renderPartial('partials/navigation-items.php', get_defined_vars()); ?></nav></div></header>
<div class="drawer-overlay" id="siteMenuOverlay" aria-hidden="true"></div>
<main class="wrap"><h1><?= e($page['title']) ?></h1><article><?= nl2br(e($page['content'])) ?></article></main><footer class="site-footer"><div class="footer-inner"><div class="footer-copy"><strong><?= e($siteName) ?></strong><p><?= e($content['footer_text']??'') ?></p></div><nav class="footer-links"><?php $navItems=$footerNavigation??[];$navAllowAuth=false;\App\Core\ThemeManager::renderPartial('partials/navigation-items.php', get_defined_vars()); ?></nav></div></footer>
<script>(function(){var button=document.querySelector(".mobile-menu-toggle"),menu=document.getElementById("siteNavigation"),overlay=document.getElementById("siteMenuOverlay");if(!button||!menu||!overlay)return;function close(){menu.classList.remove("is-open");overlay.classList.remove("is-open");document.body.classList.remove("menu-open");button.setAttribute("aria-expanded","false");button.setAttribute("aria-label","Menüyü aç");button.textContent="☰"}function open(){menu.classList.add("is-open");overlay.classList.add("is-open");document.body.classList.add("menu-open");button.setAttribute("aria-expanded","true");button.setAttribute("aria-label","Menüyü kapat");button.textContent="×"}button.addEventListener("click",function(){menu.classList.contains("is-open")?close():open()});overlay.addEventListener("click",close);menu.addEventListener("click",function(e){if(e.target.closest("a"))close()});document.addEventListener("keydown",function(e){if(e.key==="Escape")close()});window.addEventListener("resize",function(){if(window.innerWidth>700)close()})})();</script>
<?php \App\Core\ThemeManager::renderPartial('partials/login-modal.php', get_defined_vars()); ?></body></html>