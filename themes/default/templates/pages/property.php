<?php
declare(strict_types=1);
/** @var array $property */
/** @var array $content */
$siteName = $content['site_name'] ?? 'VillaStay';
$images = $property['images'] ?? [];
$featureIconSvg = static function (string $key): string {
    $paths = [
        'users'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M20 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'bed'=>'<path d="M2 4v16M2 8h20v12M2 17h20M6 8v4h6a4 4 0 0 0-4-4zM14 8h4a4 4 0 0 1 4 4h-8z"/>',
        'bath'=>'<path d="M3 12h18v3a5 5 0 0 1-5 5H8a5 5 0 0 1-5-5zM5 12V6a3 3 0 0 1 6 0v1M7 20v2M17 20v2"/>',
        'pool'=>'<path d="M2 16c1.5 0 1.5 1 3 1s1.5-1 3-1 1.5 1 3 1 1.5-1 3-1 1.5 1 3 1 1.5-1 3-1 1.5 1 3 1M2 20c1.5 0 1.5 1 3 1s1.5-1 3-1 1.5 1 3 1 1.5-1 3-1 1.5 1 3 1 1.5-1 3-1 1.5 1 3 1M7 13V5a2 2 0 0 1 4 0v2h4"/>',
        'wifi'=>'<path d="M5 12.55a11 11 0 0 1 14.08 0M1.42 9a16 16 0 0 1 21.16 0M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01"/>',
        'snow'=>'<path d="M12 2v20M4.93 4.93l14.14 14.14M2 12h20M4.93 19.07 19.07 4.93M8 4l4 2 4-2M8 20l4-2 4 2"/>',
        'car'=>'<path d="M5 17h14l1-5-2-6H6l-2 6zM3 12h18M7 17v2M17 17v2"/><circle cx="7.5" cy="14.5" r="1"/><circle cx="16.5" cy="14.5" r="1"/>',
        'utensils'=>'<path d="M3 2v7a3 3 0 0 0 6 0V2M6 2v20M18 2v20M18 2c-3 3-3 8 0 10"/>',
        'tv'=>'<rect x="3" y="4" width="18" height="14" rx="2"/><path d="m8 22 4-4 4 4"/>',
        'waves'=>'<path d="M2 8c2.5 0 2.5-2 5-2s2.5 2 5 2 2.5-2 5-2 2.5 2 5 2M2 14c2.5 0 2.5-2 5-2s2.5 2 5 2 2.5-2 5-2 2.5 2 5 2M2 20c2.5 0 2.5-2 5-2s2.5 2 5 2 2.5-2 5-2 2.5 2 5 2"/>',
        'flame'=>'<path d="M12 22a7 7 0 0 0 7-7c0-4-3-6-4-10-2 2-3 4-3 6-2-1-3-3-3-5-3 3-4 6-4 9a7 7 0 0 0 7 7z"/>',
        'leaf'=>'<path d="M20 4c-8 0-14 3-14 10a6 6 0 0 0 6 6c7 0 10-6 8-16zM4 21c3-5 7-8 12-11"/>',
        'shield'=>'<path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z"/><path d="m9 12 2 2 4-4"/>',
        'sparkles'=>'<path d="m12 3 1.9 5.8L20 11l-6.1 2.2L12 19l-1.9-5.8L4 11l6.1-2.2L12 3zM19 14l1.2 2.8L23 18l-2.8 1.2L19 22l-1.2-2.8L15 18l2.8-1.2L19 14z"/>',
    ];
    return '<svg viewBox="0 0 24 24" width="21" height="21" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$key] ?? $paths['sparkles']).'</svg>';
};
$featureItems = [
    ['icon'=>'users','label'=>(int)$property['max_guests'].' misafir'],
    ['icon'=>'bed','label'=>(int)$property['bedrooms'].' yatak odası'],
    ['icon'=>'bed','label'=>(int)$property['beds'].' yatak'],
    ['icon'=>'bath','label'=>(string)$property['bathrooms'].' banyo'],
];
foreach (($propertyAmenities ?? []) as $amenity) {
    $rawIcon = strtolower(trim((string)($amenity['icon'] ?? '')));
    $name = mb_strtolower((string)($amenity['name'] ?? ''), 'UTF-8');
    $icon = match (true) {
        str_contains($rawIcon,'pool'), str_contains($name,'havuz') => 'pool',
        str_contains($rawIcon,'wifi'), str_contains($name,'wifi'), str_contains($name,'internet') => 'wifi',
        str_contains($rawIcon,'snow'), str_contains($name,'klima'), str_contains($name,'ısıtma') => 'snow',
        str_contains($rawIcon,'car'), str_contains($name,'otopark'), str_contains($name,'park yeri') => 'car',
        str_contains($rawIcon,'utensils'), str_contains($name,'mutfak'), str_contains($name,'yemek') => 'utensils',
        str_contains($rawIcon,'tv'), str_contains($name,'televizyon'), str_contains($name,'tv') => 'tv',
        str_contains($rawIcon,'waves'), str_contains($name,'deniz'), str_contains($name,'sahil') => 'waves',
        str_contains($rawIcon,'flame'), str_contains($name,'şömine'), str_contains($name,'barbekü') => 'flame',
        str_contains($rawIcon,'leaf'), str_contains($name,'bahçe') => 'leaf',
        str_contains($rawIcon,'shield'), str_contains($name,'güvenlik') => 'shield',
        str_contains($rawIcon,'bath'), str_contains($name,'jakuzi'), str_contains($name,'spa') => 'bath',
        default => 'sparkles',
    };
    $featureItems[]=['icon'=>$icon,'label'=>(string)$amenity['name']];
}
?>
<!doctype html><html lang="<?= e($locale ?? 'tr') ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e(trim((string)($property['meta_title']??''))?:$property['title']) ?> · <?= e($siteName) ?></title><meta name="description" content="<?= e(trim((string)($property['meta_description']??''))?:($property['summary']??'')) ?>">
<style>
*{box-sizing:border-box}body{margin:0;color:var(--villa-text);font-family:var(--villa-font-body);}.wrap{width:min(1080px,calc(100% - 36px));margin:auto}.site-header{height:74px;border-bottom:1px solid #e8ece9;display:flex;align-items:center}.site-head{display:flex;align-items:center;justify-content:space-between;gap:20px}.site-brand{font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);color:var(--villa-text);text-decoration:none}.site-nav{display:flex;align-items:center;gap:22px;font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);}.site-nav a{color:var(--villa-text);text-decoration:none;white-space:nowrap}.mobile-menu-toggle{display:none;width:42px;height:42px;border:1px solid #e5ebe6;border-radius:11px;background:#fff;color:var(--villa-text);font-size:var(--villa-body-size);cursor:pointer;align-items:center;justify-content:center}.crumb{padding:22px 0;color:var(--villa-text);font-size:var(--villa-body-size);}.crumb a{color:var(--villa-text);}.title{display:flex;justify-content:space-between;gap:20px;align-items:end}.title h1{font-size:var(--villa-body-size);letter-spacing:-1.5px;margin:0 0 8px}.muted{color:var(--villa-text);}.price{font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);white-space:nowrap}.gallery{display:grid;grid-template-columns:minmax(0,1.7fr) repeat(2,minmax(0,1fr));grid-template-rows:300px 300px;gap:10px;margin:28px 0 38px;border-radius:20px;overflow:hidden;position:relative;background:#f3f5f2}.gallery-count-1{grid-template-rows: minmax(300px,auto)}.gallery-tile{position:relative;min-width:0;min-height:0;overflow:hidden;background:#f3f5f2;cursor:pointer;padding:0;border:0;display:flex;align-items:center;justify-content:center}.gallery-tile img{position:absolute;inset:0;display:block;width:100%;height:100%;max-width:none;max-height:none;margin:0;object-fit:cover;object-position:center center;transition:transform .35s ease}.gallery-tile:first-child{background:#f3f5f2;display:flex;align-items:center;justify-content:center}.gallery-tile:first-child img{object-fit:cover;object-position:center center;width:100%;height:100%;min-width:0;min-height:0;max-width:100%;max-height:100%;padding:0;margin:auto}.gallery-tile:hover img{transform:scale(1.015)}.gallery-tile:first-child{grid-row:span 2}.gallery-tile:nth-child(n+6){display:none}.gallery-count{display:none;position:absolute;right:14px;bottom:14px;border:1px solid #fff;background:#fff;color:var(--villa-text);border-radius:9px;padding:10px 13px;font-weight:var(--villa-body-weight);font-size:var(--villa-body-size);box-shadow:0 4px 16px #0002;cursor:pointer}.gallery-has-overflow .gallery-count{display:block}.gallery-mobile-counter{display:none}.gallery-empty{background:linear-gradient(135deg,#dce8df,#e8e4d8);display:grid;place-items:center;color:var(--villa-text);min-height:190px}.photo-lightbox{position:fixed!important;inset:0!important;z-index:2147483647!important;isolation:isolate;background:rgba(15,20,17,.97);display:none;color:#fff!important}.photo-lightbox.is-open{display:flex;flex-direction:column}.lightbox-head{position:relative;z-index:2;height:70px;display:flex;align-items:center;justify-content:space-between;padding:0 22px;flex:0 0 auto}.lightbox-close,.lightbox-arrow{border:1px solid #fff;background:#fff;color:#17231d;border-radius:50%;width:44px;height:44px;font-size:var(--villa-body-size);cursor:pointer}.lightbox-close{display:inline-flex;align-items:center;justify-content:center;align-self:flex-start;width:48px;height:48px;flex:0 0 48px;margin:20px 10px 0 0;background:#fff!important;color:#17231d!important;border:2px solid #fff!important;box-shadow:0 3px 18px rgba(0,0,0,.55);font-size:32px!important;font-weight:700;line-height:1;padding:0;text-shadow:none;opacity:1!important;position:relative;z-index:3}.lightbox-close:focus-visible{outline:3px solid #eb3443;outline-offset:3px}.lightbox-stage{flex:1;min-height:0;display:flex;align-items:center;justify-content:center;gap:22px;padding:8px 22px 24px}.lightbox-stage img{max-width:min(100%,1200px);max-height:100%;width:auto;height:auto;object-fit:contain}.lightbox-counter{font-size:var(--villa-body-size);color:var(--villa-text);font-weight:var(--villa-body-weight);}.lightbox-arrow{flex:0 0 auto}@media(max-width:700px){.gallery{grid-template-columns:repeat(2,minmax(0,1fr));grid-template-rows: minmax(250px,62vw) minmax(150px,42vw);gap:7px;margin:20px 0 28px;border-radius:14px}.gallery.gallery-count-1{grid-template-rows:minmax(250px,62vw)}.gallery.gallery-count-2 .gallery-tile:nth-child(2){grid-column:1/-1}.gallery-tile:first-child{grid-column:1/-1;grid-row:auto}.gallery-tile:nth-child(n+4){display:none}.gallery-count{right:9px;bottom:9px;padding:8px 10px;font-size:var(--villa-body-size);}.lightbox-head{height:58px;padding:0 14px}.lightbox-stage{padding:5px 10px 18px;gap:8px}.lightbox-arrow{width:36px;height:36px;font-size:var(--villa-body-size);}.lightbox-stage img{max-width:calc(100% - 88px)}}.no-image{background:linear-gradient(135deg,#dce8df,#e8e4d8);display:grid;place-items:center;color:var(--villa-text);min-height:190px}.body{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:55px}.description{line-height:var(--villa-body-line-height);white-space:pre-line}.booking{border:1px solid #e5eae6;border-radius:18px;padding:24px;height:max-content;box-shadow:0 8px 30px #14291c0c}.booking strong{font-size:var(--villa-body-size);}.booking p{color:var(--villa-text);line-height:var(--villa-body-line-height);font-size:var(--villa-body-size);}.booking a{display:block;background:#ff806b;color:var(--villa-text);text-align:center;padding:14px;border-radius:12px;text-decoration:none;font-weight:var(--villa-body-weight);}@media(max-width:700px){.site-head{position:relative}.mobile-menu-toggle{display:inline-flex;flex:0 0 auto}.site-nav{display:none;position:absolute;top:74px;left:0;right:0;z-index:50;padding:10px 18px 16px;background:#fff;border-bottom:1px solid #e8ece9;box-shadow:0 14px 24px #172a2018;align-items:stretch;gap:0;flex-direction:column}.site-nav.is-open{display:flex}.site-nav a{padding:13px 6px;border-bottom:1px solid #f0f2ef}.site-nav a:last-child{border-bottom:0}.title{display:block}.price{margin-top:12px}.gallery{margin:20px 0 28px}.body{grid-template-columns:1fr;gap:25px}}
.site-footer{background:#20352e;color:var(--villa-text);padding:36px 0;margin-top:55px}.site-footer p{color:var(--villa-text);line-height:var(--villa-body-line-height);}.site-footer .footer-inner{width:min(1180px,calc(100% - 36px));margin:auto;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,2fr);gap:26px;align-items:start}.site-footer .footer-copy{min-width:0}.site-footer .footer-links{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:11px 16px;font-size:var(--villa-body-size);}.site-footer .footer-links a{color:var(--villa-text);overflow-wrap:anywhere}@media(max-width:600px){.site-footer .footer-inner{grid-template-columns:1fr;gap:16px}.site-footer .footer-links{grid-template-columns:repeat(2,minmax(0,1fr))}}
/* Hierarchical navigation dropdowns */
.nav-entry{position:relative;display:inline-flex;align-items:center;min-width:0}.nav-entry-top{display:flex;align-items:center;gap:4px}.nav-entry-top>a{display:inline-flex;align-items:center}.nav-submenu-toggle{border:0;background:transparent;color:var(--villa-text);font:inherit;padding:5px 7px;cursor:pointer;transition:transform .22s ease}.nav-entry.is-open>.nav-entry-top .nav-submenu-toggle{transform:rotate(180deg)}.nav-dropdown{position:absolute;z-index:1200;top:calc(100% + 9px);left:0;min-width:220px;padding:8px;background:#fff;border:1px solid #e8eeea;border-radius:14px;box-shadow:0 18px 42px #102d2520;opacity:0;visibility:hidden;transform:translateY(8px);transition:opacity .2s ease,transform .2s ease,visibility .2s ease;display:flex;flex-direction:column;gap:2px}.nav-entry:hover>.nav-dropdown,.nav-entry:focus-within>.nav-dropdown,.nav-entry.is-open>.nav-dropdown{opacity:1;visibility:visible;transform:translateY(0)}.nav-dropdown .nav-entry{display:block;width:100%}.nav-dropdown .nav-entry-top>a{display:block;width:100%;padding:10px 12px;border-radius:8px;white-space:normal}.nav-dropdown .nav-entry-top>a:hover{background:#eff7f3}.nav-dropdown .nav-submenu-toggle{display:none}.footer-links .nav-entry{display:block;min-width:0}.footer-links .nav-dropdown{position:static;min-width:0;padding:4px 0 4px 12px;border:0;border-radius:0;box-shadow:none;background:transparent;opacity:1;visibility:visible;transform:none;display:flex}.footer-links .nav-dropdown .nav-entry-top>a{padding:4px 0;color:var(--villa-text);}.footer-links .nav-submenu-toggle{display:none}
.villa-login-overlay{position:fixed;inset:0;z-index:5000;background:rgba(13,28,23,.58);backdrop-filter:blur(4px);opacity:0;transition:opacity .22s ease}.villa-login-overlay:not([hidden]){opacity:1}.villa-login-modal{position:fixed;inset:0;z-index:5001;display:grid;place-items:center;padding:18px;pointer-events:none}.villa-login-modal[hidden],.villa-login-overlay[hidden]{display:none}.villa-login-card{pointer-events:auto;position:relative;width:min(440px,100%);padding:30px;background:#fff;border-radius:22px;box-shadow:0 25px 80px #071b1438;transform:translateY(12px) scale(.98);opacity:0;transition:transform .24s ease,opacity .24s ease}.villa-login-open .villa-login-card{transform:translateY(0) scale(1);opacity:1}.villa-login-card h2{margin:0 0 7px;font-size:var(--villa-body-size);color:var(--villa-text);}.villa-login-card>p{margin:0 0 20px;color:var(--villa-text);}.villa-login-card label{display:block;margin:14px 0 6px;font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);}.villa-login-card input{width:100%;padding:13px;border:1px solid #dfe8e2;border-radius:10px;font:inherit;outline:none}.villa-login-card input:focus{border-color:#167c80;box-shadow:0 0 0 3px #167c8018}.villa-login-submit{width:100%;margin-top:20px;padding:14px;border:0;border-radius:11px;background:#167c80;color:var(--villa-text);font-weight:var(--villa-body-weight);font-size:var(--villa-body-size);cursor:pointer}.villa-login-submit:disabled{opacity:.65;cursor:wait}.villa-login-close{position:absolute;right:14px;top:12px;width:36px;height:36px;border:0;border-radius:50%;background:#f1f5f2;color:var(--villa-text);font-size:var(--villa-body-size);cursor:pointer}.villa-login-error{margin-top:12px;padding:10px 12px;border-radius:9px;background:#fff0ef;color:var(--villa-text);font-size:var(--villa-body-size);}.villa-login-foot{margin-top:17px;text-align:center;font-size:var(--villa-body-size);color:var(--villa-text);}.villa-login-foot a{color:var(--villa-text);font-weight:var(--villa-body-weight);}
@media(max-width:700px){.footer-links .nav-dropdown{max-height:none;padding:4px 0 4px 12px}.nav-entry{display:block;width:100%}.nav-entry-top{width:100%;justify-content:space-between}.nav-entry-top>a{flex:1;padding:14px 4px;border-bottom:1px solid #eef1ee}.nav-submenu-toggle{width:42px;height:42px;border-radius:9px}.nav-entry.is-open>.nav-entry-top .nav-submenu-toggle{background:#edf6f2}.nav-dropdown{position:static;min-width:0;padding:0 0 0 14px;border:0;border-radius:0;box-shadow:none;background:#f8fbf9;max-height:0;overflow:hidden;opacity:1;visibility:visible;transform:none;display:block;transition:max-height .28s ease,padding .28s ease}.nav-entry.is-open>.nav-dropdown{max-height:520px;padding:5px 0 8px 14px}.nav-dropdown .nav-entry-top>a{padding:12px 9px;font-size:var(--villa-body-size);}.villa-login-card{padding:26px 21px;border-radius:18px}}
@media(prefers-reduced-motion:reduce){.nav-dropdown,.villa-login-card,.villa-login-overlay{transition:none}}

.availability-panel{margin:0 0 34px;padding:22px;border:1px solid #e3ebe5;border-radius:18px;background:#fff;box-shadow:0 10px 30px #14291c09}.availability-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}.availability-heading h2{margin:0 0 5px;font-size:var(--villa-body-size);}.availability-heading p{margin:0}.calendar-nav{display:flex;align-items:center;gap:12px}.calendar-nav button{width:38px;height:38px;border:1px solid #dce7df;background:#f6faf7;border-radius:10px;font-size:var(--villa-body-size);cursor:pointer;color:var(--villa-text);}.calendar-legend{display:flex;gap:15px;flex-wrap:wrap;margin:18px 0 12px;font-size:var(--villa-body-size);color:var(--villa-text);}.calendar-legend span{display:flex;align-items:center;gap:6px}.calendar-legend i{width:10px;height:10px;border-radius:50%;background:#fff;border:1px solid var(--villa-primary);box-sizing:border-box}.calendar-legend i.booked{background:#d7dbe0;border-color:var(--villa-primary)}.calendar-legend i.blocked{background:#eb3443;border-color:transparent}.calendar-legend i.selected{background:var(--villa-primary);border-color:var(--villa-primary)}.calendar-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:6px;background:#fff}.calendar-weekday{text-align:center;padding:7px 2px;color:var(--villa-primary);font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);}.calendar-day{min-height:62px;border:1px solid #e3e8eb;border-radius:9px;background:#fff!important;background-color:#fff!important;color:var(--villa-primary)!important;-webkit-appearance:none!important;appearance:none!important;font:inherit;cursor:pointer;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px}.calendar-day .day-number,.calendar-day .day-state{font-weight:var(--villa-body-weight);color:var(--villa-primary)!important}.calendar-day .day-state{font-size:var(--villa-body-size);font-weight:700;white-space:nowrap}.calendar-day.selected,.calendar-day.in-range,.calendar-day.is-selected,.calendar-day:focus,.calendar-day:focus-visible{color:#fff!important}.calendar-day.selected .day-number,.calendar-day.selected .day-state,.calendar-day.in-range .day-number,.calendar-day.in-range .day-state,.calendar-day.is-selected .day-number,.calendar-day.is-selected .day-state,.calendar-day:focus .day-number,.calendar-day:focus .day-state,.calendar-day:focus-visible .day-number,.calendar-day:focus-visible .day-state{color:#fff!important}body[data-villa-theme] #villaCalendarGrid button.calendar-day:not(.selected):not(.in-range):not(.is-selected){background:#fff!important;background-color:#fff!important;color:var(--villa-primary)!important;border-color:#e3e8eb!important}body[data-villa-theme] #villaCalendarGridNext button.calendar-day.available:not(.selected):not(.in-range):not(.is-selected){background:#fff!important;background-color:#fff!important;color:var(--villa-primary)!important;border-color:#e3e8eb!important;box-shadow:none!important}body[data-villa-theme] #villaCalendarGridNext button.calendar-day.available:not(.selected):not(.in-range):not(.is-selected) .day-number,body[data-villa-theme] #villaCalendarGridNext button.calendar-day.available:not(.selected):not(.in-range):not(.is-selected) .day-state{color:var(--villa-primary)!important}body[data-villa-theme] #villaCalendarGrid button.calendar-day:not(.selected):not(.in-range):not(.is-selected) .day-number,body[data-villa-theme] #villaCalendarGrid button.calendar-day:not(.selected):not(.in-range):not(.is-selected) .day-state{color:var(--villa-primary)!important}body[data-villa-theme] #villaCalendarGrid button.calendar-day.booked{background:#d7dbe0!important;background-color:#d7dbe0!important;color:var(--villa-primary)!important}body[data-villa-theme] #villaCalendarGrid button.calendar-day.booked .day-number,body[data-villa-theme] #villaCalendarGrid button.calendar-day.booked .day-state{color:var(--villa-primary)!important}body[data-villa-theme] #villaCalendarGrid button.calendar-day.blocked{background:#eb3443!important;background-color:#eb3443!important;color:#fff!important}body[data-villa-theme] #villaCalendarGrid button.calendar-day.blocked .day-number,body[data-villa-theme] #villaCalendarGrid button.calendar-day.blocked .day-state{color:#fff!important}.calendar-day.past{opacity:.55;cursor:not-allowed}.calendar-day:disabled{cursor:not-allowed}.calendar-day.empty{visibility:hidden}.calendar-status{font-size:var(--villa-body-size);color:var(--villa-text);min-height:20px}.calendar-selection{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;border-top:1px solid #edf1ed;padding-top:14px;font-size:var(--villa-body-size);}.calendar-selection a{background:#176b50;color:var(--villa-text);padding:11px 15px;border-radius:10px;text-decoration:none;font-weight:var(--villa-body-weight);}.calendar-selection a[aria-disabled=true]{opacity:.45;pointer-events:none}@media(max-width:560px){.availability-panel{padding:14px;border-radius:14px}.calendar-grid{gap:3px}.calendar-day{min-height:49px;border-radius:7px}.calendar-day .day-state{font-size:var(--villa-body-size);}.calendar-weekday{font-size:var(--villa-body-size);}.availability-heading h2{font-size:var(--villa-body-size);}}

/* Mobile gallery: compact desktop-style collage with three visible photos. */
@media(max-width:760px){
  .gallery.gallery-has-photos{display:grid!important;grid-template-columns:minmax(0,1.55fr) minmax(0,1fr)!important;grid-template-rows:repeat(2,minmax(105px,29vw))!important;gap:7px!important;overflow:hidden!important;margin:20px 0 28px!important;padding:0!important;border-radius:15px!important}
  .gallery.gallery-has-photos .gallery-tile,.gallery.gallery-has-photos .gallery-tile:first-child{display:flex!important;position:relative!important;grid-column:auto!important;grid-row:auto!important;width:auto!important;height:auto!important;min-height:0!important;max-height:none!important;flex:initial!important;border-radius:9px!important;overflow:hidden!important}
  .gallery.gallery-has-photos .gallery-tile:first-child{grid-row:1 / span 2!important}
  .gallery.gallery-has-photos .gallery-tile:nth-child(2),.gallery.gallery-has-photos .gallery-tile:nth-child(3){display:flex!important}
  .gallery.gallery-has-photos .gallery-tile:nth-child(n+4){display:none!important}
  .gallery.gallery-has-photos .gallery-tile img{position:absolute!important;inset:0!important;width:100%!important;height:100%!important;max-width:none!important;max-height:none!important;object-fit:cover!important;padding:0!important;margin:0!important}
  .gallery.gallery-has-photos .gallery-mobile-counter{display:none!important}
  .gallery.gallery-has-photos .gallery-count{display:block!important;right:8px!important;bottom:8px!important;z-index:5!important;padding:7px 9px!important;border:1px solid rgba(255,255,255,.9)!important;border-radius:8px!important;background:rgba(255,255,255,.96)!important;color:var(--villa-text);font-size:var(--villa-body-size);white-space:nowrap!important}
  .gallery.gallery-count-1{grid-template-columns:minmax(0,1fr)!important;grid-template-rows:minmax(220px,62vw)!important}
  .gallery.gallery-count-1 .gallery-tile:first-child{grid-row:auto!important}
  .gallery.gallery-count-2{grid-template-rows:minmax(220px,62vw)!important}
  .gallery.gallery-count-2 .gallery-tile:nth-child(2){grid-row:auto!important}
}
</style><style>.property-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(300px,360px);gap:clamp(28px,5vw,72px);align-items:start}.property-main{min-width:0}.property-section-title{font-size:clamp(22px,2vw,30px)!important;line-height:1.2;margin:0 0 20px!important;letter-spacing:-.5px}.property-feature-section,.property-description-section,.property-location-section{margin:0 0 42px;padding:0 0 34px;border-bottom:1px solid var(--villa-border)}.property-amenities{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.property-amenities li{display:flex;align-items:center;gap:12px;padding:14px 16px;border:1px solid var(--villa-border);border-radius:14px;background:var(--villa-surface);color:var(--villa-text)}.feature-icon{display:grid;place-items:center;width:30px;height:30px;flex:0 0 30px;border-radius:50%;background:var(--villa-primary);color:white;font-weight:700}.property-description-section .description{font-size:var(--villa-body-size);line-height:1.85;color:var(--villa-text)}.property-location-map{height:360px;width:100%;border-radius:18px;overflow:hidden;background:var(--villa-surface);border:1px solid var(--villa-border)}.property-location-map iframe{display:block;width:100%;height:100%;border:0}.property-map-empty{padding:24px;border:1px dashed var(--villa-border);border-radius:14px;color:var(--villa-muted)}.map-attribution{display:block;margin-top:8px;color:var(--villa-muted)}.property-booking{position:sticky;top:24px;max-height:calc(100vh - 48px);overflow:auto;align-self:start;background:var(--villa-surface);border:1px solid var(--villa-border);border-radius:22px;padding:26px;box-shadow:0 16px 45px rgba(20,40,25,.09)}.property-booking strong{display:block;font-size:26px!important;color:var(--villa-text)}.property-booking strong span{font-size:14px;color:var(--villa-muted)}.property-booking a,.property-booking-form button{background:var(--villa-primary)!important;color:#fff!important;border-radius:12px!important}.booking-price-row{display:flex;align-items:baseline;gap:7px;margin-bottom:18px}.booking-price-row strong{font-size:26px!important}.booking-price-row span{color:var(--villa-muted)}.property-booking-form{display:grid;gap:13px;margin-bottom:20px}.booking-calendar-wrap{border-top:1px solid var(--villa-border);padding-top:18px;margin-top:18px}.booking-calendar-title{font-size:18px;margin:0 0 12px;color:var(--villa-text)}.property-booking .availability-panel{margin:0;padding:0;border:0;background:transparent;box-shadow:none}.property-booking .availability-heading{display:block}.property-booking .availability-heading h2{display:none}.property-booking .availability-heading p{font-size:13px}.property-booking .calendar-nav{justify-content:space-between;margin-top:12px}.property-booking .calendar-grid{gap:3px}.property-booking .calendar-day{min-height:46px;font-size:12px}.property-booking .calendar-day .day-state{font-size:9px}.property-booking .calendar-legend{gap:8px;font-size:11px}.property-booking .calendar-selection{display:grid;gap:10px}.property-booking .calendar-selection a{display:block;text-align:center}.property-booking-form label{display:grid;gap:6px;font-size:14px;font-weight:600;color:var(--villa-text)}.property-booking-form input,.property-booking-form select{width:100%;min-width:0;padding:12px;border:1px solid var(--villa-border);border-radius:10px;background:var(--villa-background);color:var(--villa-text);font:inherit}.property-booking-form button{width:100%;border:0;padding:14px;font:inherit;font-weight:700;cursor:pointer}.property-booking-form button:hover{filter:brightness(.96)}.property-reviews{margin-top:0!important;padding-top:0!important;border:0!important}.property-review{border-top:1px solid var(--villa-border);padding:18px 0}.property-review strong span{color:var(--villa-primary)}@media(max-width:760px){.property-layout{grid-template-columns:1fr;gap:28px}.property-booking{position:static;order:2;max-height:none;overflow:visible}.property-amenities{grid-template-columns:1fr 1fr}.property-location-map{height:280px}}@media(max-width:460px){.property-amenities{grid-template-columns:1fr}}</style><style>
.property-layout{grid-template-columns:minmax(0,1fr) minmax(310px,370px);gap:clamp(24px,4vw,58px);align-items:start}
.property-main{min-width:0}
.property-main>.gallery{width:100%;max-width:100%;margin:0 0 34px;grid-template-rows:clamp(190px,24vw,270px) clamp(150px,17vw,190px)}
.property-main>.photo-lightbox{position:fixed}
.property-booking{position:sticky;top:18px;max-height:calc(100vh - 36px);overflow:auto}
.booking-steps{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:0 0 18px;padding:5px;background:var(--villa-background);border-radius:12px}
.booking-step{padding:9px 7px;border-radius:9px;text-align:center;font-size:13px;color:var(--villa-muted);font-weight:700}
.booking-step.is-active{background:var(--villa-primary);color:#fff}
.booking-step-title{font-size:19px;margin:0 0 14px;color:var(--villa-text)}
.booking-calendar-wrap{border-top:1px solid var(--villa-border);padding-top:15px;margin-top:4px}
.booking-price-preview{display:grid;gap:9px;margin:15px 0;padding:13px;border-radius:12px;background:var(--villa-background)}
.booking-price-preview>div{display:flex;justify-content:space-between;gap:10px;font-size:13px}
.booking-price-preview strong{font-size:13px!important}
.booking-price-preview .booking-total{border-top:1px solid var(--villa-border);padding-top:10px;font-size:15px;font-weight:800}
.booking-price-preview .booking-total strong{font-size:18px!important}
.booking-inline-status{font-size:13px;line-height:1.5;color:var(--villa-muted);margin:10px 0}
.booking-primary,.booking-secondary{display:block;width:100%;border:0;border-radius:12px;padding:14px 16px;font:inherit;font-weight:800;cursor:pointer}
.booking-primary{background:var(--villa-primary);color:#fff}
.booking-primary:disabled{opacity:.45;cursor:not-allowed}
.booking-secondary{background:var(--villa-background);color:var(--villa-text);border:1px solid var(--villa-border);margin-bottom:10px}
.booking-selected-summary{padding:11px 12px;border-radius:10px;background:var(--villa-background);font-size:13px;line-height:1.6}
.property-booking-form small{font-size:12px;color:var(--villa-muted);font-weight:400}
.booking-price-preview-compact{margin:12px 0}
.booking-terms-note{font-size:12px;line-height:1.55;color:var(--villa-muted);margin:12px 0 0}
.booking-step-panel[hidden],.booking-price-preview[hidden]{display:none!important}@media(max-width:760px){
 .property-layout{grid-template-columns:minmax(0,1fr);gap:28px}
 .property-main{display:contents}
 .property-main>.gallery{grid-column:1;grid-row:1;width:100%;margin:0 0 8px;grid-template-rows:clamp(190px,56vw,270px) clamp(110px,32vw,160px)}
 .property-main>.photo-lightbox{position:fixed}
 .property-booking{position:static;order:99;grid-row:auto;max-height:none;overflow:visible;margin-top:8px}
 .property-feature-section{order:1}.property-description-section{order:2}.property-location-section{order:3}.property-reviews{order:4}
}
</style><style>
.property-hero-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,2fr);gap:clamp(22px,3vw,42px);align-items:stretch;margin:24px 0 42px}
.property-hero-copy{min-width:0;display:flex;flex-direction:column;align-items:flex-start;justify-content:center;gap:12px}
.property-hero-copy .title{display:block;width:100%}.property-hero-copy .title h1{font-size:clamp(28px,3vw,42px);line-height:1.12;letter-spacing:-.04em}
.property-hero-copy .price{margin-top:22px;font-size:clamp(23px,2.3vw,32px);color:var(--villa-primary);white-space:normal}
.property-hero-copy .property-rating{flex-wrap:wrap}.property-hero-copy .property-favorite-form{margin:8px 0!important}
.property-rating .rating-score{display:inline-flex;align-items:center;gap:7px;padding:8px 13px;border:1px solid #edca70;border-radius:999px;background:#fff4d5;color:#805300;font-size:clamp(20px,2vw,25px)!important;font-weight:900;line-height:1.1;letter-spacing:-.035em;box-shadow:0 3px 10px rgba(128,83,0,.10)}
.property-rating .rating-count{font-size:14px;font-weight:700;color:var(--villa-text)}
.property-rating-summary{font-size:16px;font-weight:750;color:var(--villa-text);margin:10px 0 18px}
.property-review .review-rating-score{display:inline-flex;align-items:center;gap:4px;margin-left:7px;padding:5px 10px;border:1px solid #edca70;border-radius:999px;background:#fff4d5;color:#805300;font-size:17px;font-weight:900;line-height:1.15;white-space:nowrap}
@media(max-width:700px){.property-rating .rating-score{font-size:22px!important;padding:8px 12px}.property-rating-summary{font-size:15px}.property-review .review-rating-score{font-size:16px}}
.property-hero-gallery{min-width:0}.property-hero-gallery .gallery{margin:0;grid-template-rows:clamp(230px,25vw,310px) clamp(150px,15vw,190px);border-radius:20px}
.property-hero-gallery .gallery.gallery-count-1{grid-template-rows:clamp(390px,40vw,510px)}
.property-info-layout{display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:clamp(24px,4vw,56px);align-items:start;margin:0 0 40px}
.property-feature-section,.property-location-section{min-width:0;margin:0;padding:0;border:0}
.property-feature-groups{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px 18px;list-style:none;margin:0;padding:0}
.property-feature-item{display:flex;align-items:center;gap:11px;min-width:0;padding:11px 0;border-bottom:1px solid var(--villa-border)}
.property-feature-label{min-width:0;overflow-wrap:anywhere;line-height:1.4}
.feature-icon{display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;border-radius:10px;background:var(--villa-surface);border:1px solid var(--villa-border);color:var(--villa-primary)}
.property-feature-group li>span:last-child{min-width:0;overflow-wrap:anywhere;font-size:14px;line-height:1.45}
.property-location-section .property-section-title{margin-bottom:12px!important}.property-location-map{position:relative;isolation:isolate;height:300px;width:100%;border-radius:16px;border:1px solid var(--villa-border);background:var(--villa-background,#f4f5f3);overflow:hidden}.property-location-map.is-loading::before{content:'';position:absolute;inset:0;background:linear-gradient(110deg,transparent 25%,rgba(120,130,120,.09) 45%,transparent 65%);background-size:220% 100%;animation:propertyMapShimmer 1.6s ease-in-out infinite;pointer-events:none}.property-map-loading{position:absolute;inset:0;display:grid;place-items:center;color:var(--villa-muted);font-size:13px;z-index:1;pointer-events:none}.property-location-map:not(.is-loading) .property-map-loading{display:none}.property-location-map iframe{position:relative;z-index:2;background:var(--villa-background,#f4f5f3)}.property-map-empty{padding:22px;border:1px solid var(--villa-border);border-radius:14px;background:var(--villa-background,#f4f5f3);color:var(--villa-muted);line-height:1.6}@keyframes propertyMapShimmer{to{background-position-x:-220%}}
.property-description-section,.property-reviews{margin:0 0 38px;padding:0 0 30px;border-bottom:1px solid var(--villa-border)}
.property-booking{width:100%;margin:10px 0 42px;padding:clamp(18px,3vw,30px);border:1px solid var(--villa-border);border-radius:20px;background:var(--villa-surface);box-shadow:0 12px 36px rgba(20,40,28,.06)}
.property-booking .booking-price-row{display:flex;align-items:baseline;gap:9px;margin-bottom:18px}.property-booking .booking-price-row strong{font-size:24px;color:var(--villa-primary)}
.property-booking .booking-steps{display:flex;gap:10px;margin:0 0 22px;padding-bottom:16px;border-bottom:1px solid var(--villa-border)}
.property-booking .booking-step{padding:9px 14px;border-radius:999px;background:var(--villa-background);color:var(--villa-muted);font-size:13px;font-weight:700}
.property-booking .booking-step.is-active{background:color-mix(in srgb,var(--villa-primary) 12%,white);color:var(--villa-primary)}
.property-booking-form{display:block}.property-booking .booking-step-title{margin:0 0 16px;font-size:clamp(20px,2vw,26px)}
.property-booking .availability-panel{margin:0;padding:clamp(16px,2.5vw,26px);border:1px solid var(--villa-border);border-radius:16px;box-shadow:none}
.property-booking .availability-heading h2{margin:0 0 5px;font-size:17px}.property-booking .availability-heading p{margin:0;color:var(--villa-muted);font-size:13px}
.property-booking .calendar-months{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px}.property-booking .calendar-month{min-width:0}
.property-booking .calendar-month h3{margin:0 0 12px;text-align:center;font-size:16px}.property-booking .calendar-grid{gap:5px}
.property-booking .calendar-weekday{padding:8px 2px;font-size:12px}.property-booking .calendar-day{min-width:0;min-height:70px;padding:5px 2px;border-radius:9px}
.property-booking .calendar-day .day-number{font-size:15px}.property-booking .calendar-day .day-state{font-size:10px;line-height:1.2;white-space:normal;text-align:center;overflow-wrap:anywhere}
.property-booking .calendar-selection{align-items:center;margin-top:18px;padding-top:18px}
.calendar-selection-actions{display:flex;align-items:center;justify-content:flex-end;gap:18px;margin-left:auto}
.calendar-total{display:grid;gap:4px;text-align:right}.calendar-total small{font-size:11px;color:var(--villa-muted)}.calendar-total strong{font-size:19px;color:var(--villa-primary);white-space:nowrap}
.property-booking .calendar-selection a{display:inline-flex;align-items:center;justify-content:center;min-height:48px;padding:12px 20px;background:var(--villa-primary);color:#fff;border-radius:11px;font-weight:800}
.property-booking .calendar-selection a[aria-disabled=true]{opacity:.45;pointer-events:none}.property-booking .booking-inline-status{margin:12px 0 0;color:var(--villa-muted);font-size:13px}
.property-booking .booking-price-preview{display:none!important}.property-booking .booking-step-panel[hidden]{display:none!important}
.property-booking #bookingStepDetails{max-width:920px}.property-booking #bookingStepDetails>label{display:block;margin:14px 0 6px;font-size:13px;font-weight:700}
.property-booking #bookingStepDetails input,.property-booking #bookingStepDetails select{display:block;width:100%;height:48px;margin-top:7px;padding:0 13px;border:1px solid var(--villa-border);border-radius:10px;background:var(--villa-surface);color:var(--villa-text);font:inherit}
.property-booking #bookingStepDetails input:focus,.property-booking #bookingStepDetails select:focus{outline:2px solid color-mix(in srgb,var(--villa-primary) 28%,transparent);border-color:var(--villa-primary)}
.property-booking .booking-primary,.property-booking .booking-secondary{min-height:48px;padding:12px 20px;border:1px solid var(--villa-primary);border-radius:11px;font:inherit;font-weight:800;cursor:pointer}
.property-booking .booking-primary{background:var(--villa-primary);color:#fff}.property-booking .booking-secondary{margin-right:8px;background:var(--villa-surface);color:var(--villa-primary)}
.property-booking .booking-selected-summary{padding:12px 14px;border-radius:10px;background:var(--villa-background);color:var(--villa-muted)}.property-booking .booking-terms-note{font-size:12px;color:var(--villa-muted);line-height:1.6}
@media(max-width:900px){.property-hero-layout{grid-template-columns:minmax(0,1fr) minmax(0,1.5fr);gap:18px}.property-info-layout{grid-template-columns:minmax(0,1.5fr) minmax(230px,1fr);gap:22px}.property-feature-groups{gap:10px}.property-booking .calendar-day{min-height:62px}}
@media(max-width:700px){
.property-hero-layout{grid-template-columns:minmax(0,1fr);gap:18px;margin:16px 0 30px}.property-hero-copy{order:1}.property-hero-gallery{order:2}
.property-hero-gallery .gallery{margin:0;grid-template-rows:clamp(220px,62vw,330px) clamp(125px,34vw,175px)}.property-hero-gallery .gallery.gallery-count-1{grid-template-rows:clamp(250px,70vw,390px)}
.property-info-layout{grid-template-columns:minmax(0,1fr);gap:28px;margin-bottom:30px}.property-feature-groups{grid-template-columns:repeat(2,minmax(0,1fr));gap:6px 14px}
.property-feature-item{align-items:center;gap:8px;padding:9px 0}.property-feature-item .feature-icon{flex:0 0 26px;width:26px;height:26px;border-radius:8px}.property-feature-item .property-feature-label{font-size:12px;line-height:1.35}.property-location-map{height:250px}.property-booking{padding:15px;margin-top:0;border-radius:16px}
.property-booking .calendar-months{grid-template-columns:minmax(0,1fr);gap:22px}.property-booking .calendar-month h3{text-align:left}.property-booking .calendar-day{min-height:58px}.property-booking .calendar-day .day-state{font-size:9px}
.property-booking .calendar-selection{align-items:stretch;flex-direction:column}.calendar-selection-actions{width:100%;margin:0;justify-content:space-between;gap:10px}
.calendar-total{text-align:left}.property-booking .calendar-selection a{flex:1}
}
@media(max-width:420px){.property-feature-groups{grid-template-columns:repeat(2,minmax(0,1fr));gap:5px 10px}.property-feature-item{padding:8px 0;gap:6px}.property-feature-item .feature-icon{flex-basis:23px;width:23px;height:23px}.property-feature-item .property-feature-label{font-size:11px;line-height:1.3}.calendar-selection-actions{align-items:flex-end}.calendar-total strong{font-size:16px}.property-booking .calendar-selection a{padding:10px 12px;font-size:13px}}
</style></head><body><header class="site-header"><div class="wrap site-head"><a class="site-brand" href="/"><?= e($siteName) ?></a><button class="mobile-menu-toggle" type="button" aria-label="Menüyü aç" aria-controls="siteNavigation" aria-expanded="false">☰</button><nav class="site-nav" id="siteNavigation"><?php $navItems=$navigation??[];$navAllowAuth=true;\App\Core\ThemeManager::renderPartial('partials/navigation-items.php', get_defined_vars()); ?></nav></div></header><main class="wrap"><div class="crumb"><a href="/">Ana sayfa</a> / <?= e($property['city'] ?? '') ?> / <?= e($property['title']) ?></div>
<section class="property-hero-layout"><div class="property-hero-copy"><div class="title"><div><h1><?= e($property['title']) ?></h1><div class="property-rating" style="display:flex;align-items:center;gap:7px;margin:8px 0 10px;font-size:var(--villa-body-size);color:var(--villa-text);"><?php if((int)($ratingSummary['rating_count']??0)>0): ?><strong class="rating-score">★ <?= number_format((float)$ratingSummary['rating_average'],1,',','.') ?>/10</strong><span class="rating-count"><?= (int)$ratingSummary['rating_count'] ?> değerlendirme</span><?php else: ?><span>★ Henüz değerlendirme yok</span><?php endif; ?></div><div class="muted"><?= e(trim(($property['neighborhood'] ? $property['neighborhood'].', ' : '').($property['district'] ? $property['district'].', ' : '').($property['city'] ?? '').($property['country'] ? ', '.$property['country'] : ''))) ?> · <?= (int)$property['max_guests'] ?> <?= e($content['property_guests'] ?? 'misafir') ?> · <?= (int)$property['bedrooms'] ?> <?= e($content['property_bedrooms'] ?? 'yatak odası') ?> · <?= (int)$property['beds'] ?> <?= e($content['property_beds'] ?? 'yatak') ?> · <?= e($property['bathrooms']) ?> <?= e($content['property_bathrooms'] ?? 'banyo') ?></div></div><div class="price"><?= e(number_format((float)$property['base_nightly_price'],0,',','.')) ?> <?= e($property['currency']) ?> <span class="muted" style="font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);">/ gece</span></div></div>
<?php if(($user['role']??'')==='customer'): ?><form class="js-favorite-form property-favorite-form" method="post" action="<?= e(app_url('/favorites/toggle')) ?>" style="margin:18px 0"><?= csrf_field() ?><input type="hidden" name="property_id" value="<?= (int)$property['id'] ?>"><input type="hidden" name="return_to" value="<?= e(app_url('/villa/'.rawurlencode((string)$property['slug']))) ?>"><button class="property-favorite-button <?= $isFavorite?'is-active':'' ?>" type="submit" aria-pressed="<?= $isFavorite?'true':'false' ?>" style="border:1px solid #dfe6e0;background:#fff;padding:10px 15px;border-radius:9px;cursor:pointer"><?= $isFavorite?'♥ Favorilerden çıkar':'♡ Favorilere ekle' ?></button></form><?php endif; ?>
<button class="property-compare-button" type="button" data-compare-property
 data-property-id="<?= (int)$property['id'] ?>"
 data-property-title="<?= e((string)$property['title']) ?>"
 data-property-url="<?= e(app_url('/villa/'.rawurlencode((string)$property['slug']))) ?>"
 data-property-image="<?= e((string)($property['images'][0]['file_path'] ?? '')) ?>"
 data-property-price="<?= e((string)$property['base_nightly_price']) ?>"
 data-property-currency="<?= e((string)$property['currency']) ?>"
 data-property-location="<?= e(trim((string)($property['district']??'').', '.(string)($property['city']??''),', ')) ?>"
 data-property-guests="<?= (int)$property['max_guests'] ?>"
 aria-pressed="false">⇄ Karşılaştırmaya ekle</button>
</div><div class="property-hero-gallery">
<div class="gallery gallery-count-<?= min(5,count($images)) ?><?= count($images)>1?' gallery-has-photos':'' ?><?= count($images)>5?' gallery-has-overflow':'' ?>" id="propertyGallery"><?php if($images): foreach($images as $index=>$image): ?><button class="gallery-tile" type="button" data-gallery-index="<?= (int)$index ?>" aria-label="Fotoğrafı büyüt"><img src="<?= e(preg_match('~^https?://~i',(string)$image['file_path']) ? $image['file_path'] : uploaded_media_url((string)$image['file_path'])) ?>" alt="<?= e($image['alt_text'] ?: $property['title']) ?>"></button><?php endforeach; if(count($images)>1): ?><span class="gallery-mobile-counter" id="galleryMobileCounter" aria-live="polite">1 / <?= count($images) ?></span><button class="gallery-count" type="button" id="showAllPhotos">Tüm <?= count($images) ?> fotoğrafı gör</button><?php endif; else: ?><div class="gallery-empty" style="grid-column:1/-1;grid-row:1/-1"><?= e($content['property_no_images'] ?? 'Bu konaklama için henüz fotoğraf eklenmemiş.') ?></div><?php endif; ?></div>
<?php if($images): ?><div class="photo-lightbox" id="photoLightbox" role="dialog" aria-modal="true" aria-label="Konaklama fotoğraf galerisi"><div class="lightbox-head"><span class="lightbox-counter" id="lightboxCounter">1 / <?= count($images) ?></span><button class="lightbox-close" type="button" id="lightboxClose" aria-label="Galeriyi kapat">×</button></div><div class="lightbox-stage"><button class="lightbox-arrow" type="button" id="lightboxPrev" aria-label="Önceki fotoğraf">‹</button><img id="lightboxImage" src="<?= e(preg_match('~^https?://~i',(string)$images[0]['file_path'])?$images[0]['file_path']:uploaded_media_url((string)$images[0]['file_path'])) ?>" alt="<?= e($images[0]['alt_text'] ?: $property['title']) ?>"><button class="lightbox-arrow" type="button" id="lightboxNext" aria-label="Sonraki fotoğraf">›</button></div></div><?php endif; ?>
</div></section>
<section class="property-info-layout"><section class="property-feature-section"><h2 class="property-section-title">Villa özellikleri</h2><ul class="property-feature-groups"><?php foreach($featureItems as $feature): ?><li class="property-feature-item"><span class="feature-icon"><?= $featureIconSvg($feature['icon']) ?></span><span class="property-feature-label"><?= e($feature['label']) ?></span></li><?php endforeach; ?></ul></section><section class="property-location-section"><h2 class="property-section-title">Villa konumu</h2><p class="muted"><?= e(trim(($property['address']??'').' '.($property['district']??'').' '.($property['city']??'').' '.($property['country']??''))) ?></p><?php if(is_numeric($property['latitude']??null)&&is_numeric($property['longitude']??null)): ?><div id="propertyLocationMap" class="property-location-map is-loading" data-lat="<?= e((string)$property['latitude']) ?>" data-lng="<?= e((string)$property['longitude']) ?>" role="application" aria-label="Villa konumu haritası"><span class="property-map-loading" role="status">Harita yükleniyor…</span></div><small class="map-attribution">© OpenStreetMap katkıda bulunanlar</small><?php else: ?><p class="property-map-empty">Bu villa için harita konumu henüz seçilmemiş.</p><?php endif; ?></section></section>
<section class="property-description-section"><h2 class="property-section-title"><?= e($content['property_about'] ?? 'Konaklama hakkında') ?></h2><p class="description"><?= e($property['description'] ?: $property['summary'] ?: '') ?></p></section><section class="guest-reviews property-reviews"><h2 class="property-section-title">Misafir değerlendirmeleri</h2><p class="muted property-rating-summary"><?php if((int)($ratingSummary['rating_count']??0)>0): ?>Ortalama <?= number_format((float)$ratingSummary['rating_average'],1,',','.') ?>/10 · <?= (int)$ratingSummary['rating_count'] ?> değerlendirme<?php else: ?>Bu villa için henüz yayınlanmış değerlendirme bulunmuyor.<?php endif; ?></p><?php foreach($reviews as $review): ?><article class="property-review"><strong><?= e($review['username']) ?> · <span class="review-rating-score">★ <?= (int)$review['rating'] ?>/10</span></strong><?php if(!empty($review['is_demo'])): ?> <small style="display:inline-block;margin:6px 0;padding:4px 8px;border-radius:999px;background:var(--villa-surface);color:var(--villa-muted);font-size:11px;">Demo yorum · Gerçek müşteri deneyimi değildir</small><?php endif; ?><?php if(trim((string)$review['body'])!==''): ?><p><?= nl2br(e($review['body'])) ?></p><?php endif; ?><small class="muted"><?= e(date('d.m.Y',strtotime($review['created_at']))) ?></small></article><?php endforeach; ?></section>
<section class="booking property-booking" id="propertyBooking">
<div class="booking-price-row"><strong><?= e(number_format((float)$property['base_nightly_price'],0,',','.')) ?> <?= e($property['currency']) ?></strong><span>/ gece başlangıç</span></div>
<div class="booking-steps" aria-label="Rezervasyon adımları"><span class="booking-step is-active" data-step-indicator="1">1. Müsaitlik</span><span class="booking-step" data-step-indicator="2">2. Bilgiler</span></div>
<form class="property-booking-form" id="propertyBookingForm" method="post" action="<?= e(app_url('/villa/' . rawurlencode((string)$property['slug']) . '/reserve')) ?>">
<?= csrf_field() ?>
<section class="booking-step-panel" id="bookingStepAvailability" data-booking-step="1">
<h2 class="booking-step-title">Tarihlerinizi seçin</h2>
<input id="bookingCheckIn" type="hidden" name="check_in" value="<?= e((string)($_GET['check_in']??'')) ?>" required>
<input id="bookingCheckOut" type="hidden" name="check_out" value="<?= e((string)($_GET['check_out']??'')) ?>" required>
<div class="booking-calendar-wrap"><section class="availability-panel" id="villaAvailability" data-calendar-url="<?= e(app_url('/villa/' . rawurlencode((string)$property['slug']) . '/calendar')) ?>" data-price-url="<?= e(app_url('/villa/' . rawurlencode((string)$property['slug']) . '/availability')) ?>" data-reserve-url="<?= e(app_url('/villa/' . rawurlencode((string)$property['slug']) . '/reserve')) ?>">
<div class="availability-heading"><div><h2>Giriş ve çıkış tarihlerinizi seçin</h2><p>İki aylık müsaitlik takviminden konaklama aralığınızı belirleyin.</p></div><div class="calendar-nav"><button type="button" id="calendarPrev" aria-label="Önceki ay">‹</button><button type="button" id="calendarNext" aria-label="Sonraki ay">›</button></div></div>
<div class="calendar-legend"><span><i class="available"></i>Müsait</span><span><i class="booked"></i>Rezerve</span><span><i class="blocked"></i>Kapalı</span><span><i class="selected"></i>Seçiminiz</span></div>
<div class="calendar-months"><section class="calendar-month"><h3 id="calendarMonth">Takvim</h3><div class="calendar-grid" id="villaCalendarGrid" aria-live="polite"></div></section><section class="calendar-month"><h3 id="calendarNextMonth">Sonraki ay</h3><div class="calendar-grid" id="villaCalendarGridNext" aria-live="polite"></div></section></div>
<p class="calendar-status" id="villaCalendarStatus" role="status">Müsaitlik bilgisi yükleniyor…</p>
<div class="calendar-selection"><span id="calendarSelectionText">Giriş ve çıkış tarihlerinizi takvimden seçin.</span><div class="calendar-selection-actions"><div class="calendar-total"><small>Toplam konaklama</small><strong id="calendarTotalAmount">Tarih seçin</strong></div><a id="calendarReserve" href="#" aria-disabled="true">Rezervasyona devam et</a></div></div>
</section></div>
<div class="booking-price-preview" id="bookingPricePreview" hidden><div><span id="bookingNightCount">0 gece</span><strong id="bookingNightSubtotal">—</strong></div><div><span>Temizlik ücreti</span><strong id="bookingCleaningFee">—</strong></div><div class="booking-total"><span>Toplam tutar</span><strong id="bookingGrandTotal">—</strong></div></div>
<p class="booking-inline-status" id="bookingAvailabilityStatus" role="status" aria-live="polite">Giriş ve çıkış tarihlerinizi seçin.</p>

</section><section class="booking-step-panel" id="bookingStepDetails" data-booking-step="2" hidden>
<h2 class="booking-step-title">Rezervasyon bilgileri</h2>
<p class="booking-selected-summary" id="bookingSelectedSummary"></p>
<?php if(!empty($property['early_checkin_enabled'])): ?><label class="booking-field-full"><span style="display:flex;align-items:center;gap:10px"><input type="checkbox" id="bookingEarlyCheckin" name="early_checkin" value="1" style="width:18px;height:18px"> Erken giriş talep ediyorum<?php if((float)($property['early_checkin_fee']??0)>0): ?> · <?= e(number_format((float)$property['early_checkin_fee'],2,',','.')) ?> <?= e($property['currency']) ?><?php else: ?> · Ücretsiz<?php endif; ?></span></label><?php endif; ?>
<?php if(!empty($property['late_checkout_enabled'])): ?><label class="booking-field-full"><span style="display:flex;align-items:center;gap:10px"><input type="checkbox" id="bookingLateCheckout" name="late_checkout" value="1" style="width:18px;height:18px"> Geç çıkış talep ediyorum<?php if((float)($property['late_checkout_fee']??0)>0): ?> · <?= e(number_format((float)$property['late_checkout_fee'],2,',','.')) ?> <?= e($property['currency']) ?><?php else: ?> · Ücretsiz<?php endif; ?></span></label><?php endif; ?>
<label>Misafir sayısı<select name="guests" id="bookingGuests"><?php for($guestCount=1;$guestCount<=(int)$property['max_guests'];$guestCount++): ?><option value="<?= $guestCount ?>" <?= (int)($_GET['guests']??1)===$guestCount?'selected':'' ?>><?= $guestCount ?> misafir</option><?php endfor; ?></select></label>
<label>Adınız<input type="text" name="first_name" autocomplete="given-name" maxlength="90" required></label>
<label>Soyadınız<input type="text" name="last_name" autocomplete="family-name" maxlength="90" required></label>
<label>E-posta adresiniz<input type="email" name="email" autocomplete="email" maxlength="190" required></label>
<label>Telefon numaranız<input type="tel" name="phone" autocomplete="tel" maxlength="40" required></label>
<label>T.C. kimlik numaranız<input type="text" name="identity_number" inputmode="numeric" autocomplete="off" pattern="[0-9]{11}" minlength="11" maxlength="11" required></label>
<label>Fatura şehri<input type="text" name="billing_city" autocomplete="address-level2" maxlength="100" required></label>
<label class="booking-field-full">Fatura adresi<input type="text" name="billing_address" autocomplete="street-address" maxlength="500" required></label>
<label>Şifreniz<input type="password" name="password" autocomplete="new-password" minlength="10" maxlength="128" required><small>En az 10 karakter. Mevcut hesabınız varsa hesabınızın şifresini girin.</small></label>
<div class="booking-price-preview booking-price-preview-compact" id="bookingPricePreviewDetails" aria-live="polite"></div>
<p class="booking-inline-status" id="bookingDetailsStatus" role="status" aria-live="polite">Misafir sayısını seçin; fiyat ve kapasite yeniden doğrulanır.</p>
<button type="button" class="booking-secondary" id="bookingBack">Tarihleri değiştir</button>
<button type="submit" class="booking-primary" id="bookingComplete">Rezervasyonumu Tamamla</button>
<p class="booking-terms-note">Devam ettiğinizde rezervasyonunuz kaydedilir ve ödeme adımına yönlendirilirsiniz. Rezervasyon, ödeme tamamlanana kadar kesinleşmez.</p>
</section>
</form>
</section></main><script>(function(){var button=document.querySelector(".mobile-menu-toggle"),menu=document.getElementById("siteNavigation");if(!button||!menu)return;function close(){menu.classList.remove("is-open");button.setAttribute("aria-expanded","false");button.setAttribute("aria-label","Menüyü aç");button.textContent="☰"}button.addEventListener("click",function(){var open=menu.classList.toggle("is-open");button.setAttribute("aria-expanded",open?"true":"false");button.setAttribute("aria-label",open?"Menüyü kapat":"Menüyü aç");button.textContent=open?"×":"☰"});menu.addEventListener("click",function(e){if(e.target.closest("a"))close()});document.addEventListener("keydown",function(e){if(e.key==="Escape")close()});window.addEventListener("resize",function(){if(window.innerWidth>700)close()})})();var galleryImages=<?= json_encode(array_map(static function(array $image):array{return ['src'=>preg_match('~^https?://~i',(string)$image['file_path'])?$image['file_path']:'/'.ltrim($image['file_path'],'/'),'alt'=>$image['alt_text']?:$property['title']];},$images),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>;
(function(){var modal=document.getElementById('photoLightbox');if(!modal||!galleryImages.length)return;var image=document.getElementById('lightboxImage'),counter=document.getElementById('lightboxCounter'),index=0,touchX=0,galleryRoot=document.getElementById('propertyGallery'),mobileCounter=document.getElementById('galleryMobileCounter');
function show(n){index=(n+galleryImages.length)%galleryImages.length;image.src=galleryImages[index].src;image.alt=galleryImages[index].alt;counter.textContent=(index+1)+' / '+galleryImages.length}
function open(n){show(n);modal.classList.add('is-open');document.body.style.overflow='hidden';document.getElementById('lightboxClose').focus()}
function close(){modal.classList.remove('is-open');document.body.style.overflow=''}
document.querySelectorAll('[data-gallery-index]').forEach(function(button){button.addEventListener('click',function(){open(Number(button.dataset.galleryIndex))})});
var all=document.getElementById('showAllPhotos');if(all)all.addEventListener('click',function(){open(0)});if(galleryRoot&&mobileCounter){function updateMobileCounter(){var tiles=galleryRoot.querySelectorAll('.gallery-tile');if(!tiles.length)return;var nearest=0,distance=Infinity;tiles.forEach(function(tile,i){var d=Math.abs(tile.offsetLeft-galleryRoot.scrollLeft);if(d<distance){distance=d;nearest=i}});mobileCounter.textContent=(nearest+1)+' / '+galleryImages.length}galleryRoot.addEventListener('scroll',function(){window.requestAnimationFrame(updateMobileCounter)},{passive:true});window.addEventListener('resize',updateMobileCounter);updateMobileCounter()}
document.getElementById('lightboxClose').addEventListener('click',close);document.getElementById('lightboxPrev').addEventListener('click',function(){show(index-1)});document.getElementById('lightboxNext').addEventListener('click',function(){show(index+1)});
modal.addEventListener('click',function(e){if(e.target===modal)close()});document.addEventListener('keydown',function(e){if(!modal.classList.contains('is-open'))return;if(e.key==='Escape')close();if(e.key==='ArrowLeft')show(index-1);if(e.key==='ArrowRight')show(index+1)});
modal.addEventListener('touchstart',function(e){touchX=e.changedTouches[0].screenX},{passive:true});modal.addEventListener('touchend',function(e){var delta=e.changedTouches[0].screenX-touchX;if(Math.abs(delta)>45)show(index+(delta<0?1:-1))},{passive:true});
})();

(function(){
var root=document.getElementById('villaAvailability');if(!root)return;
var gridA=document.getElementById('villaCalendarGrid'),gridB=document.getElementById('villaCalendarGridNext'),labelA=document.getElementById('calendarMonth'),labelB=document.getElementById('calendarNextMonth');
var status=document.getElementById('villaCalendarStatus'),selection=document.getElementById('calendarSelectionText'),totalLabel=document.getElementById('calendarTotalAmount'),reserve=document.getElementById('calendarReserve');
var checkIn=document.getElementById('bookingCheckIn'),checkOut=document.getElementById('bookingCheckOut'),guestSelect=document.getElementById('bookingGuests');
var now=new Date(),view=new Date(now.getFullYear(),now.getMonth(),1),today=new Date(now.getFullYear(),now.getMonth(),now.getDate());
var first=checkIn&&checkIn.value?checkIn.value:null,last=checkOut&&checkOut.value?checkOut.value:null,bookings=[],rules=[],baseRate=0,weekendRate=0,currency='TRY';
var names=['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
if(first){var initialDate=new Date(first+'T00:00:00');if(!isNaN(initialDate.getTime()))view=new Date(initialDate.getFullYear(),initialDate.getMonth(),1)}
function iso(d){return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')}
function parse(s){var p=s.split('-').map(Number);return new Date(p[0],p[1]-1,p[2])}
function dayState(d){var key=iso(d);for(var b of bookings)if(key>=b.check_in&&key<b.check_out)return 'booked';for(var r of rules)if(key>=r.date_from&&key<r.date_to&&r.status!=='available')return 'blocked';return 'available'}
function blocked(d){return d<today||['booked','pending','blocked'].includes(dayState(d))}
function money(n,c){return new Intl.NumberFormat('tr-TR',{style:'currency',currency:c||'TRY',maximumFractionDigits:2}).format(Number(n||0))}
function renderMonth(grid,label,month){
grid.innerHTML='';['Pzt','Sal','Çar','Per','Cum','Cmt','Paz'].forEach(function(n){var el=document.createElement('div');el.className='calendar-weekday';el.textContent=n;grid.appendChild(el)});
var offset=(month.getDay()+6)%7;for(var i=0;i<offset;i++){var empty=document.createElement('div');empty.className='calendar-day empty';grid.appendChild(empty)}
var count=new Date(month.getFullYear(),month.getMonth()+1,0).getDate();
for(var n=1;n<=count;n++){var d=new Date(month.getFullYear(),month.getMonth(),n),state=dayState(d),key=iso(d),el=document.createElement('button');el.type='button';el.className='calendar-day '+state+(d<today?' past':'');
var inRange=!!(first&&last&&key>=first&&key<=last),isSelected=!!(first&&key===first||last&&key===last);if(isSelected||inRange)el.classList.add(isSelected?'selected':'in-range','is-selected');
el.setAttribute('aria-pressed',(isSelected||inRange)?'true':'false');el.disabled=blocked(d);
var rate=baseRate;if((d.getDay()===0||d.getDay()===6)&&weekendRate>0)rate=weekendRate;for(var rule of rules)if(key>=rule.date_from&&key<rule.date_to&&rule.nightly_price!==null&&rule.nightly_price!=='')rate=Number(rule.nightly_price);
var priceText=new Intl.NumberFormat('tr-TR',{maximumFractionDigits:0}).format(rate)+' '+currency;el.innerHTML='<span class="day-number">'+n+'</span><span class="day-state">'+priceText+'</span>';
el.setAttribute('aria-label',n+' '+names[month.getMonth()]+' '+month.getFullYear()+' · '+priceText+' · '+(state==='available'?'Müsait':state==='booked'?'Rezerve':'Kapalı'));
el.addEventListener('click',(function(date){return function(){choose(iso(date))}})(d));grid.appendChild(el)}
label.textContent=names[month.getMonth()]+' '+month.getFullYear();
}
function render(){renderMonth(gridA,labelA,view);renderMonth(gridB,labelB,new Date(view.getFullYear(),view.getMonth()+1,1))}
function renderSelection(){selection.textContent=first?(last?'Giriş: '+first+' · Çıkış: '+last:'Giriş: '+first+' · Çıkış tarihini seçin.'):'Giriş ve çıkış tarihlerinizi takvimden seçin.'}
function resetPrice(){totalLabel.textContent='Tarih seçin';reserve.href='#';reserve.setAttribute('aria-disabled','true')}
async function verifySelection(){
if(!first||!last||last<=first){resetPrice();return false}status.textContent='Müsaitlik ve toplam fiyat doğrulanıyor…';
var u=new URL(root.dataset.priceUrl,location.origin);u.search=new URLSearchParams({check_in:first,check_out:last,guests:guestSelect?guestSelect.value:'1'});
try{var response=await fetch(u,{headers:{Accept:'application/json'},credentials:'same-origin'}),data=await response.json();if(!response.ok||!data.ok)throw new Error(data.message||'Bu tarih aralığı müsait değil.');
totalLabel.textContent=money(data.total,data.currency);status.textContent=data.nights+' gece · Seçtiğiniz tarihler müsait.';reserve.href=root.dataset.reserveUrl+'?'+new URLSearchParams({check_in:first,check_out:last,guests:guestSelect?guestSelect.value:'1'}).toString();reserve.setAttribute('aria-disabled','false');return true;
}catch(error){last=null;if(checkOut)checkOut.value='';resetPrice();status.textContent=error.message||'Müsaitlik doğrulanamadı.';renderSelection();render();return false}
}
async function choose(key){
if(!first||last||key<=first){first=key;last=null;resetPrice();status.textContent='Şimdi çıkış tarihinizi seçin.'}else{last=key}
if(checkIn)checkIn.value=first||'';if(checkOut)checkOut.value=last||'';
if(first&&last){var start=parse(first),end=parse(last),bad=false;for(var d=new Date(start);d<end;d.setDate(d.getDate()+1)){if(blocked(d)){bad=true;break}}
if(bad){last=null;if(checkOut)checkOut.value='';resetPrice();status.textContent='Seçtiğiniz aralıkta dolu veya kapalı gün bulunuyor. Başka bir çıkış tarihi seçin.'}else await verifySelection()}
renderSelection();render();
}
async function load(){
status.textContent='İki aylık müsaitlik bilgisi yükleniyor…';var months=[new Date(view.getFullYear(),view.getMonth(),1),new Date(view.getFullYear(),view.getMonth()+1,1)];
try{var results=await Promise.all(months.map(function(month){var key=month.getFullYear()+'-'+String(month.getMonth()+1).padStart(2,'0');return fetch(root.dataset.calendarUrl+'?month='+key,{headers:{Accept:'application/json'},credentials:'same-origin'}).then(function(r){return r.json()})}));
if(results.some(function(data){return !data.ok}))throw new Error('Takvim verisi alınamadı.');
var bookingMap=new Map();results.forEach(function(data){(data.bookings||[]).forEach(function(item){bookingMap.set(String(item.id||item.check_in+'|'+item.check_out),item)})});bookings=Array.from(bookingMap.values());
var ruleMap=new Map();results.forEach(function(data){(data.rules||[]).forEach(function(item){ruleMap.set(String(item.id||item.date_from+'|'+item.date_to),item)})});rules=Array.from(ruleMap.values());
baseRate=Number(results[0].base_nightly_price||0);weekendRate=Number(results[0].weekend_nightly_price||0);currency=results[0].currency||'TRY';status.textContent='Takvim güncel rezervasyon ve kapalı gün kayıtlarına göre gösteriliyor.';render();if(first&&last)await verifySelection();
}catch(error){status.textContent='Takvim bilgisi alınamadı. Lütfen sayfayı yenileyin.';gridA.innerHTML='';gridB.innerHTML=''}
}
document.getElementById('calendarPrev').addEventListener('click',function(){view.setMonth(view.getMonth()-1);resetPrice();load()});
document.getElementById('calendarNext').addEventListener('click',function(){view.setMonth(view.getMonth()+1);resetPrice();load()});
if(checkIn)checkIn.addEventListener('change',function(){first=checkIn.value||null;last=null;if(checkOut)checkOut.value='';resetPrice();renderSelection();render()});
if(checkOut)checkOut.addEventListener('change',function(){if(checkIn&&checkIn.value&&checkOut.value&&checkOut.value>checkIn.value){first=checkIn.value;last=checkOut.value;verifySelection().then(function(){renderSelection();render()})}else{last=null;resetPrice()}renderSelection();render()});
if(guestSelect)guestSelect.addEventListener('change',function(){if(first&&last)verifySelection()});load();
})();</script><script>(function(){document.querySelectorAll('.js-favorite-form').forEach(function(form){form.addEventListener('submit',async function(event){event.preventDefault();var button=form.querySelector('button[type="submit"]');if(!button||button.disabled)return;var oldText=button.textContent;button.disabled=true;try{var response=await fetch(form.action,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});var data=await response.json();if(!response.ok||!data.ok)throw new Error(data.message||'Favori işlemi tamamlanamadı.');button.classList.toggle('is-active',!!data.active);button.setAttribute('aria-pressed',data.active?'true':'false');button.textContent=data.active?'♥ Favorilerden çıkar':'♡ Favorilere ekle';var status=document.getElementById('favoriteActionStatus');if(!status){status=document.createElement('div');status.id='favoriteActionStatus';status.setAttribute('role','status');status.setAttribute('aria-live','polite');status.style.cssText='position:fixed;left:50%;bottom:22px;transform:translateX(-50%);z-index:6000;padding:11px 16px;border-radius:999px;background:#20352e;color:var(--villa-text);font:inherit;box-shadow:0 8px 24px #0002';document.body.appendChild(status)}status.textContent=data.message;status.hidden=false;clearTimeout(status._hideTimer);status._hideTimer=setTimeout(function(){status.hidden=true},2400)}catch(error){button.textContent=oldText;window.alert(error.message||'Favori işlemi sırasında hata oluştu.')}finally{button.disabled=false}})})})();</script><?php \App\Core\ThemeManager::renderPartial('partials/login-modal.php', get_defined_vars()); ?><script>(function(){var el=document.getElementById("propertyLocationMap");if(!el)return;var lat=Number(el.dataset.lat),lng=Number(el.dataset.lng);if(!Number.isFinite(lat)||!Number.isFinite(lng))return;var bbox=[lng-0.018,lat-0.012,lng+0.018,lat+0.012].join("%2C");var src="https://www.openstreetmap.org/export/embed.html?bbox="+bbox+"&layer=mapnik&marker="+encodeURIComponent(lat+","+lng);var frame=document.createElement("iframe");frame.title="Villa konumu · OpenStreetMap";frame.loading="lazy";frame.referrerPolicy="no-referrer-when-downgrade";frame.src=src;frame.style.cssText="position:relative;z-index:2;display:block;width:100%;height:100%;border:0;background:var(--villa-background,#f4f5f3)";frame.addEventListener("load",function(){el.classList.remove("is-loading")},{once:true});frame.addEventListener("error",function(){el.classList.remove("is-loading");var message=el.querySelector(".property-map-loading");if(message)message.textContent="Harita şu anda yüklenemedi."},{once:true});el.replaceChildren(el.querySelector(".property-map-loading"),frame)})();</script><script>
(function(){
 const form=document.getElementById('propertyBookingForm');if(!form)return;
 const checkIn=document.getElementById('bookingCheckIn'),checkOut=document.getElementById('bookingCheckOut'),guests=document.getElementById('bookingGuests'),early=document.getElementById('bookingEarlyCheckin'),late=document.getElementById('bookingLateCheckout');
 const backBtn=document.getElementById('bookingBack');
 const step1=document.getElementById('bookingStepAvailability'),step2=document.getElementById('bookingStepDetails');
 const status=document.getElementById('bookingAvailabilityStatus'),detailsStatus=document.getElementById('bookingDetailsStatus'),preview=document.getElementById('bookingPricePreview');
 const detailsPreview=document.getElementById('bookingPricePreviewDetails'),summary=document.getElementById('bookingSelectedSummary');
 const complete=document.getElementById('bookingComplete');
 const indicators=document.querySelectorAll('[data-step-indicator]');let verified=false,verifiedKey='',priceData=null,checking=false,checkSequence=0;
 function key(){return [checkIn.value,checkOut.value,guests.value,early&&early.checked?'1':'0',late&&late.checked?'1':'0'].join('|')}
 function setStatus(message){status.textContent=message;if(detailsStatus)detailsStatus.textContent=message;}
 function money(n,c){return new Intl.NumberFormat('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2}).format(Number(n||0))+' '+c}
 function updateSummary(){if(!priceData)return;let html='<div><span>'+priceData.nights+' gece konaklama</span><strong>'+money(priceData.subtotal,priceData.currency)+'</strong></div><div><span>Temizlik ücreti</span><strong>'+money(priceData.cleaning_fee===undefined?priceData.fees:priceData.cleaning_fee,priceData.currency)+'</strong></div>';if(Number(priceData.early_checkin_fee||0)>0||priceData.early_checkin_requested){html+='<div><span>Erken giriş</span><strong>'+money(priceData.early_checkin_fee,priceData.currency)+'</strong></div>'}if(Number(priceData.late_checkout_fee||0)>0||priceData.late_checkout_requested){html+='<div><span>Geç çıkış</span><strong>'+money(priceData.late_checkout_fee,priceData.currency)+'</strong></div>'}html+='<div class="booking-total"><span>Toplam</span><strong>'+money(priceData.total,priceData.currency)+'</strong></div>';preview.innerHTML=html;detailsPreview.innerHTML=html;preview.hidden=false}
 function updateSelectedSummary(){summary.textContent=checkIn.value+' giriş · '+checkOut.value+' çıkış · '+guests.value+' misafir';}
 function setStep(n){step1.hidden=n!==1;step2.hidden=n!==2;indicators.forEach(el=>el.classList.toggle('is-active',el.dataset.stepIndicator===String(n)));if(n===2){updateSelectedSummary();updateSummary();}}
 async function check(){
  const requestId=++checkSequence;verified=false;priceData=null;preview.hidden=true;detailsPreview.innerHTML='';
  if(!checkIn.value||!checkOut.value||checkOut.value<=checkIn.value){setStatus('Geçerli giriş ve çıkış tarihlerini seçin.');return false;}
  setStatus('Müsaitlik, kapasite ve fiyat kontrol ediliyor…');checking=true;
  try{
   const url=new URL(document.getElementById('villaAvailability').dataset.priceUrl,location.origin);
   url.search=new URLSearchParams({check_in:checkIn.value,check_out:checkOut.value,guests:guests.value,early_checkin:early&&early.checked?'1':'0',late_checkout:late&&late.checked?'1':'0'});
   const res=await fetch(url,{headers:{Accept:'application/json'},credentials:'same-origin'}),data=await res.json();
   if(requestId!==checkSequence)return false;
   if(!res.ok||!data.ok)throw new Error(data.message||'Seçilen tarihler müsait değil.');   priceData=data;verified=true;verifiedKey=key();updateSummary();setStatus(data.message||'Seçtiğiniz tarihler müsait.');return true;
  }catch(err){if(requestId!==checkSequence)return false;setStatus(err.message||'Müsaitlik kontrol edilemedi.');return false;}
  finally{if(requestId===checkSequence)checking=false;}
 }
 [checkIn,checkOut].forEach(el=>el.addEventListener('change',()=>{verified=false;setStatus('Tarih değişti. Rezervasyona devam etmeden önce yeniden kontrol edin.');}));
 [early,late].filter(Boolean).forEach(el=>el.addEventListener('change',async()=>{if(step2.hidden)return;complete.disabled=true;const ok=await check();complete.disabled=false;if(ok)setStatus('Ek hizmetler ve toplam tutar güncellendi.');else detailsPreview.innerHTML='';}));
 guests.addEventListener('change',async()=>{
  if(step2.hidden){verified=false;setStatus('Misafir sayısı değişti. Yeniden kontrol edin.');return;}
  updateSelectedSummary();guests.disabled=true;complete.disabled=true;
  const ok=await check();
  guests.disabled=false;complete.disabled=false;
  if(ok){updateSelectedSummary();setStatus('Misafir sayısı, kapasite ve toplam fiyat güncellendi.');}
  else{verified=false;detailsPreview.innerHTML='';}
 });
 backBtn.addEventListener('click',()=>setStep(1));
 form.addEventListener('submit',async e=>{
  if(checking){e.preventDefault();return;}
  if(!verified||verifiedKey!==key()){
   e.preventDefault();
   if(step2.hidden){setStep(1);if(await check())setStep(2);}
   else if(await check()){updateSelectedSummary();setStep(2);}
   return;
  }
  const password=form.querySelector('[name="password"]');if(password.value.length<10){e.preventDefault();password.focus();return;}
  complete.disabled=true;complete.textContent='Rezervasyon kaydediliyor…';
 });
 const calendarContinue=document.getElementById('calendarReserve');
 if(calendarContinue){calendarContinue.addEventListener('click',async e=>{e.preventDefault();if(checking)return;if(await check())setStep(2);});}
 if(checkIn.value&&checkOut.value)check();
})();
</script><?php \App\Core\ThemeManager::renderPartial('partials/compare-bar.php', get_defined_vars()); ?><style>
/* Mobile villa availability calendar: give each day room and keep prices readable. */
@media (max-width: 560px) {
  .property-booking .availability-panel,
  .property-booking .calendar-grid {
    width: 100%;
    min-width: 0;
  }
  .property-booking .calendar-grid {
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 5px;
  }
  .property-booking .calendar-weekday {
    min-width: 0;
    padding: 8px 0;
    font-size: 10px;
    line-height: 1.2;
  }
  .property-booking .calendar-day {
    min-width: 0;
    min-height: 54px;
    padding: 5px 1px;
    gap: 5px;
    border-radius: 9px;
    font-size: 14px;
    line-height: 1;
  }
  .property-booking .calendar-day .day-number {
    font-size: 14px;
    line-height: 1;
    font-variant-numeric: tabular-nums;
  }
  .property-booking .calendar-day .day-state {
    max-width: 100%;
    font-size: 8px;
    line-height: 1.05;    letter-spacing: -.25px;
    white-space: normal;
    overflow-wrap: normal;
    text-align: center;
    font-variant-numeric: tabular-nums;
  }
}

/* Second-month calendar contrast: keep every unselected, non-blocked day readable even when focused. */
body[data-villa-theme] .property-booking #villaCalendarGridNext button.calendar-day:not(.selected):not(.in-range):not(.is-selected):not(.booked):not(.blocked),
body[data-villa-theme] .property-booking #villaCalendarGridNext button.calendar-day.available:not(.selected):not(.in-range):not(.is-selected):focus,
body[data-villa-theme] .property-booking #villaCalendarGridNext button.calendar-day.available:not(.selected):not(.in-range):not(.is-selected):focus-visible{background:#fff!important;background-color:#fff!important;background-image:none!important;color:var(--villa-primary)!important;border:1px solid #e3e8eb!important;box-shadow:none!important;}
body[data-villa-theme] .property-booking #villaCalendarGridNext button.calendar-day:not(.selected):not(.in-range):not(.is-selected):not(.booked):not(.blocked) .day-number,
body[data-villa-theme] .property-booking #villaCalendarGridNext button.calendar-day:not(.selected):not(.in-range):not(.is-selected):not(.booked):not(.blocked) .day-state{color:var(--villa-primary)!important;}
</style></body></html>