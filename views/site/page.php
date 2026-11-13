<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="<?= e($locale ?? 'tr') ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page['meta_title'] ?: $page['title']) ?></title>
<meta name="description" content="<?= e($page['meta_description']??'') ?>">
<style>
:root{--green:#167c80;--ink:#20332c;--line:#e8eeea}
*{box-sizing:border-box}
body{font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;color:var(--ink);margin:0;background:#fff}
.wrap{max-width:850px;margin:50px auto;padding:0 20px}
.site-header{border-bottom:1px solid var(--line);padding:16px 0}
.site-head{display:flex;align-items:center;justify-content:space-between;gap:18px}
.site-brand{color:var(--green);font-weight:850;text-decoration:none;font-size:22px}
.site-nav{display:flex;align-items:center;gap:18px;font-size:14px;font-weight:650}
.site-nav a{color:var(--ink);text-decoration:none;white-space:nowrap}
.mobile-menu-toggle{display:none;width:42px;height:42px;border:1px solid #e5ebe6;border-radius:11px;background:#fff;color:var(--green);font-size:23px;cursor:pointer;align-items:center;justify-content:center}
article{line-height:1.85;white-space:pre-line;padding:30px 0;color:#4c5a50}
.drawer-overlay{display:none}
@media(max-width:700px){
.site-header{position:relative;z-index:1001}
.site-head{position:static}
.mobile-menu-toggle{display:inline-flex;flex:0 0 auto;position:relative;z-index:1003}
.site-nav{display:flex;position:fixed;top:0;right:0;bottom:0;left:auto;width:min(86vw,360px);height:100dvh;z-index:1002;padding:88px 24px 28px;background:#fff;box-shadow:-18px 0 45px #172a2024;align-items:stretch;justify-content:flex-start;gap:0;flex-direction:column;overflow-y:auto;transform:translateX(105%);visibility:hidden;transition:transform .25s ease,visibility .25s ease}
.site-nav.is-open{transform:translateX(0);visibility:visible}
.site-nav a{padding:16px 5px;border-bottom:1px solid #eef1ee;white-space:normal}
.site-nav a:last-child{border-bottom:0}
.drawer-overlay{display:block;position:fixed;inset:0;z-index:1000;background:rgba(18,29,23,.48);opacity:0;visibility:hidden;transition:opacity .25s ease,visibility .25s ease}
.drawer-overlay.is-open{opacity:1;visibility:visible}
body.menu-open{overflow:hidden}
.wrap{margin:30px auto;padding:0 16px}
}
.site-footer{background:#20352e;color:#fff;padding:36px 0;margin-top:55px}.site-footer p{color:#c3d2ca;line-height:1.65}.site-footer .footer-inner{width:min(1180px,calc(100% - 36px));margin:auto;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,2fr);gap:26px;align-items:start}.site-footer .footer-copy{min-width:0}.site-footer .footer-links{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:11px 16px;font-size:13px}.site-footer .footer-links a{color:inherit;overflow-wrap:anywhere}@media(max-width:600px){.site-footer .footer-inner{grid-template-columns:1fr;gap:16px}.site-footer .footer-links{grid-template-columns:repeat(2,minmax(0,1fr))}}
/* Hierarchical navigation dropdowns */
.nav-entry{position:relative;display:inline-flex;align-items:center;min-width:0}.nav-entry-top{display:flex;align-items:center;gap:4px}.nav-entry-top>a{display:inline-flex;align-items:center}.nav-submenu-toggle{border:0;background:transparent;color:currentColor;font:inherit;padding:5px 7px;cursor:pointer;transition:transform .22s ease}.nav-entry.is-open>.nav-entry-top .nav-submenu-toggle{transform:rotate(180deg)}.nav-dropdown{position:absolute;z-index:1200;top:calc(100% + 9px);left:0;min-width:220px;padding:8px;background:#fff;border:1px solid #e8eeea;border-radius:14px;box-shadow:0 18px 42px #102d2520;opacity:0;visibility:hidden;transform:translateY(8px);transition:opacity .2s ease,transform .2s ease,visibility .2s ease;display:flex;flex-direction:column;gap:2px}.nav-entry:hover>.nav-dropdown,.nav-entry:focus-within>.nav-dropdown,.nav-entry.is-open>.nav-dropdown{opacity:1;visibility:visible;transform:translateY(0)}.nav-dropdown .nav-entry{display:block;width:100%}.nav-dropdown .nav-entry-top>a{display:block;width:100%;padding:10px 12px;border-radius:8px;white-space:normal}.nav-dropdown .nav-entry-top>a:hover{background:#eff7f3}.nav-dropdown .nav-submenu-toggle{display:none}.footer-links .nav-entry{display:block;min-width:0}.footer-links .nav-dropdown{position:static;min-width:0;padding:4px 0 4px 12px;border:0;border-radius:0;box-shadow:none;background:transparent;opacity:1;visibility:visible;transform:none;display:flex}.footer-links .nav-dropdown .nav-entry-top>a{padding:4px 0;color:#c3d2ca}.footer-links .nav-submenu-toggle{display:none}
.villa-login-overlay{position:fixed;inset:0;z-index:5000;background:rgba(13,28,23,.58);backdrop-filter:blur(4px);opacity:0;transition:opacity .22s ease}.villa-login-overlay:not([hidden]){opacity:1}.villa-login-modal{position:fixed;inset:0;z-index:5001;display:grid;place-items:center;padding:18px;pointer-events:none}.villa-login-modal[hidden],.villa-login-overlay[hidden]{display:none}.villa-login-card{pointer-events:auto;position:relative;width:min(440px,100%);padding:30px;background:#fff;border-radius:22px;box-shadow:0 25px 80px #071b1438;transform:translateY(12px) scale(.98);opacity:0;transition:transform .24s ease,opacity .24s ease}.villa-login-open .villa-login-card{transform:translateY(0) scale(1);opacity:1}.villa-login-card h2{margin:0 0 7px;font-size:25px;color:#20332c}.villa-login-card>p{margin:0 0 20px;color:#718078}.villa-login-card label{display:block;margin:14px 0 6px;font-size:13px;font-weight:750}.villa-login-card input{width:100%;padding:13px;border:1px solid #dfe8e2;border-radius:10px;font:inherit;outline:none}.villa-login-card input:focus{border-color:#167c80;box-shadow:0 0 0 3px #167c8018}.villa-login-submit{width:100%;margin-top:20px;padding:14px;border:0;border-radius:11px;background:#167c80;color:#fff;font-weight:850;font-size:15px;cursor:pointer}.villa-login-submit:disabled{opacity:.65;cursor:wait}.villa-login-close{position:absolute;right:14px;top:12px;width:36px;height:36px;border:0;border-radius:50%;background:#f1f5f2;color:#52635a;font-size:24px;cursor:pointer}.villa-login-error{margin-top:12px;padding:10px 12px;border-radius:9px;background:#fff0ef;color:#a43;font-size:13px}.villa-login-foot{margin-top:17px;text-align:center;font-size:13px;color:#718078}.villa-login-foot a{color:#167c80;font-weight:800}
@media(max-width:700px){.footer-links .nav-dropdown{max-height:none;padding:4px 0 4px 12px}.nav-entry{display:block;width:100%}.nav-entry-top{width:100%;justify-content:space-between}.nav-entry-top>a{flex:1;padding:14px 4px;border-bottom:1px solid #eef1ee}.nav-submenu-toggle{width:42px;height:42px;border-radius:9px}.nav-entry.is-open>.nav-entry-top .nav-submenu-toggle{background:#edf6f2}.nav-dropdown{position:static;min-width:0;padding:0 0 0 14px;border:0;border-radius:0;box-shadow:none;background:#f8fbf9;max-height:0;overflow:hidden;opacity:1;visibility:visible;transform:none;display:block;transition:max-height .28s ease,padding .28s ease}.nav-entry.is-open>.nav-dropdown{max-height:520px;padding:5px 0 8px 14px}.nav-dropdown .nav-entry-top>a{padding:12px 9px;font-size:13px}.villa-login-card{padding:26px 21px;border-radius:18px}}
@media(prefers-reduced-motion:reduce){.nav-dropdown,.villa-login-card,.villa-login-overlay{transition:none}}
</style>
</head>
<body>
<header class="site-header"><div class="wrap site-head">
<a class="site-brand" href="/"><?= e($siteName ?? 'VillaStay') ?></a>
<button class="mobile-menu-toggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false">☰</button>
<nav class="site-nav" id="siteNavigation"><?php $navItems=$navigation??[];$navAllowAuth=true;require APP_ROOT.'/views/site/partials/navigation-items.php'; ?></nav></div></header>
<div class="drawer-overlay" id="siteMenuOverlay" aria-hidden="true"></div>
<main class="wrap"><h1><?= e($page['title']) ?></h1><article><?= nl2br(e($page['content'])) ?></article></main>
<script>(function(){var button=document.querySelector(".mobile-menu-toggle"),menu=document.getElementById("siteNavigation"),overlay=document.getElementById("siteMenuOverlay");if(!button||!menu||!overlay)return;function close(){menu.classList.remove("is-open");overlay.classList.remove("is-open");document.body.classList.remove("menu-open");button.setAttribute("aria-expanded","false");button.setAttribute("aria-label","Menüyü aç");button.textContent="☰"}function open(){menu.classList.add("is-open");overlay.classList.add("is-open");document.body.classList.add("menu-open");button.setAttribute("aria-expanded","true");button.setAttribute("aria-label","Menüyü kapat");button.textContent="×"}button.addEventListener("click",function(){menu.classList.contains("is-open")?close():open()});overlay.addEventListener("click",close);menu.addEventListener("click",function(e){if(e.target.closest("a"))close()});document.addEventListener("keydown",function(e){if(e.key==="Escape")close()});window.addEventListener("resize",function(){if(window.innerWidth>700)close()})})();</script>
<?php require APP_ROOT.'/views/site/partials/login-modal.php'; ?></body></html>