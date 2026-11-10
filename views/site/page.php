<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="<?= e($locale ?? 'tr') ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page['meta_title'] ?: $page['title']) ?></title>
<meta name="description" content="<?= e($page['meta_description']??'') ?>">
<style>
:root{--green:#176b50;--ink:#26332b;--line:#e8ece9}
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
</style>
</head>
<body>
<header class="site-header"><div class="wrap site-head">
<a class="site-brand" href="/"><?= e($siteName ?? 'VillaStay') ?></a>
<button class="mobile-menu-toggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false">☰</button>
<nav class="site-nav" id="siteNavigation">
<?php foreach (($navigation ?? []) as $item): ?><a href="<?= e($item['url']) ?>" target="<?= e($item['target']) ?>" <?= $item['target']==='_blank'?'rel="noopener noreferrer"':'' ?>><?= e($item['label']) ?></a><?php endforeach; ?>
<?php if (($mode ?? 'single_vendor') === 'multi_vendor'): ?><a href="/vendor/register">Ev sahibi olun</a><?php endif; ?>
<?php if (($user['role'] ?? '') === 'customer'): ?><a href="/favorites">Favorilerim</a><a href="/account/bookings">Hesabım</a><?php endif; ?>
</nav></div></header>
<div class="drawer-overlay" id="siteMenuOverlay" aria-hidden="true"></div>
<main class="wrap"><h1><?= e($page['title']) ?></h1><article><?= nl2br(e($page['content'])) ?></article></main>
<script>(function(){var button=document.querySelector(".mobile-menu-toggle"),menu=document.getElementById("siteNavigation"),overlay=document.getElementById("siteMenuOverlay");if(!button||!menu||!overlay)return;function close(){menu.classList.remove("is-open");overlay.classList.remove("is-open");document.body.classList.remove("menu-open");button.setAttribute("aria-expanded","false");button.setAttribute("aria-label","Menüyü aç");button.textContent="☰"}function open(){menu.classList.add("is-open");overlay.classList.add("is-open");document.body.classList.add("menu-open");button.setAttribute("aria-expanded","true");button.setAttribute("aria-label","Menüyü kapat");button.textContent="×"}button.addEventListener("click",function(){menu.classList.contains("is-open")?close():open()});overlay.addEventListener("click",close);menu.addEventListener("click",function(e){if(e.target.closest("a"))close()});document.addEventListener("keydown",function(e){if(e.key==="Escape")close()});window.addEventListener("resize",function(){if(window.innerWidth>700)close()})})();</script>
</body></html>