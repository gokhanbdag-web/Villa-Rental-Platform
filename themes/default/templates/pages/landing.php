<?php
declare(strict_types=1);
$siteName=$content['site_name']??'VillaStay';
$brandSettings=is_array($brandSettings??null)?$brandSettings:[];
$logoUrl=(string)($brandSettings['logo_url']??'');
$heroVideoUrl=(string)($brandSettings['hero_video_url']??'');
$heroBackgroundImageUrl=trim((string)($brandSettings['hero_background_image_url']??''));
if($heroBackgroundImageUrl==='')$heroBackgroundImageUrl=trim((string)($homeSections['hero']['image_url']??''));
if (isset($homeDesign['hero']['hero_image'])) $heroBackgroundImageUrl=$homeDesign['hero']['hero_image'];
$heroYouTubeId=static function(string $url):string{
    $parts=parse_url(trim($url));if(!is_array($parts))return '';
    $host=strtolower((string)($parts['host']??''));$path=(string)($parts['path']??'');$id='';
    if(in_array($host,['youtu.be','www.youtu.be'],true))$id=trim($path,'/');
    elseif(in_array($host,['youtube.com','www.youtube.com','m.youtube.com','youtube-nocookie.com','www.youtube-nocookie.com'],true)){
        parse_str((string)($parts['query']??''),$query);$id=(string)($query['v']??'');
        if($id===''&&preg_match('~^/(?:embed|shorts)/([A-Za-z0-9_-]{11})(?:/|$)~',$path,$match))$id=$match[1];
    }
    return preg_match('/^[A-Za-z0-9_-]{11}$/',$id)?$id:'';
};
$heroYouTubeIdValue=$heroYouTubeId((string)($brandSettings['hero_youtube_url']??$homeSections['hero']['video_url']??''));
$heroOverlayOpacity=max(0,min(100,(int)($brandSettings['hero_overlay_opacity']??$homeSections['hero']['overlay_opacity']??58)));
$heroOverlayAlpha=number_format($heroOverlayOpacity/100,2,'.','');
$heroOverlayColor=(string)($brandSettings['hero_overlay_color']??'#081d19');
if(!preg_match('/^#[0-9a-fA-F]{6}$/',$heroOverlayColor))$heroOverlayColor='#081d19';
$heroOverlayRgb=sprintf('%d,%d,%d',hexdec(substr($heroOverlayColor,1,2)),hexdec(substr($heroOverlayColor,3,2)),hexdec(substr($heroOverlayColor,5,2)));
$heroOverlayTexture=(string)($brandSettings['hero_overlay_texture']??'none');
if(!in_array($heroOverlayTexture,['none','diagonal','grid','dots'],true))$heroOverlayTexture='none';
$heroVideoStartSeconds=max(0,min(86400,(int)($brandSettings['hero_video_start_seconds']??0)));
$heroVideoLoop=!array_key_exists('hero_video_loop',$brandSettings)||!empty($brandSettings['hero_video_loop']);
// Every homepage must surface accommodation inventory, regardless of optional section toggles.
$showFeatured = $homeDesign['featured']['visible'] ?? true;
$photo=static function(array $row):string{$v=(string)($row['cover_image']??'');return $v===''?'':(preg_match('~^https?://~i',$v)?$v:'/'.ltrim($v,'/'));};
$active=static function(string $key)use($homeSections):bool{return !isset($homeSections[$key])||(int)($homeSections[$key]['is_active']??1)===1;};
$order=static function(string $key,int $fallback)use($homeSections):int{return (int)($homeSections[$key]['sort_order']??$fallback);};
$stylePhotos=[(string)($content['home_style_1_image']??'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=900&q=82'),(string)($content['home_style_2_image']??'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=900&q=82'),(string)($content['home_style_3_image']??'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=900&q=82'),(string)($content['home_style_4_image']??'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=900&q=82')];
$galleryPhotos=[(string)($content['home_gallery_1_image']??'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=900&q=82'),(string)($content['home_gallery_2_image']??'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=900&q=82'),(string)($content['home_gallery_3_image']??'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=900&q=82'),(string)($content['home_gallery_4_image']??'https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=900&q=82')];
$hostBannerImage=(string)($content['home_host_banner_image']??'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1800&q=85');
?>
<!doctype html><html lang="<?= e($locale??'en') ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($content['home_title']??'Your dream holiday awaits') ?> · <?= e($siteName) ?></title><meta name="description" content="<?= e($content['home_subtitle']??'Discover beautiful holiday villas.') ?>">
<style>
:root{--ink:#20332c;--muted:#718078;--teal:#167c80;--coral:#ff806b;--line:#e8eeea}*{box-sizing:border-box}body{margin:0;color:var(--villa-text);font-family:var(--villa-font-body);background:#fff}a{color:var(--villa-text);text-decoration:none}.wrap{width:min(1200px,calc(100% - 40px));margin:auto}.site-header{height:78px;display:flex;align-items:center;border-bottom:1px solid var(--line);background:#fff;position:relative;z-index:20}.head{display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);color:var(--villa-text);}.nav{display:flex;align-items:center;gap:24px;font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);}.nav a:hover{color:var(--villa-text);}.nav-cta{padding:12px 18px;border-radius:999px;background:#e7f4f2;color:var(--villa-text);}.mobile-menu-toggle{display:none;border:1px solid var(--line);background:#fff;border-radius:12px;width:44px;height:44px;font-size:var(--villa-body-size);color:var(--villa-text);}
.hero{padding:34px 0 62px;background:#f5faf8}.hero-card{min-height:460px;overflow:hidden;background-color:#183b32;background-position:center;background-size:cover;background-repeat:no-repeat;display:flex;align-items:center}.hero-copy{padding:55px 7%;color:var(--villa-text);max-width:780px}.eyebrow{font-size:var(--villa-body-size);letter-spacing:2px;font-weight:var(--villa-body-weight);color:var(--villa-text);}.hero h1{font-size:var(--villa-body-size);line-height:var(--villa-body-line-height);letter-spacing:-2.8px;margin:17px 0}.hero p{font-size:var(--villa-body-size);line-height:var(--villa-body-line-height);max-width:570px;color:var(--villa-text);}.search{display:flex;gap:8px;max-width:1050px;margin-top:30px;padding:9px;background:#fff;border-radius:17px}.search input,.search select{flex:1;min-width:0;border:0;padding:14px 9px;font:inherit;outline:none}.search input[type=date]{min-width:145px;font-size:var(--villa-body-size);}.search button{border:0;border-radius:12px;background:var(--coral);color:var(--villa-text);font-weight:var(--villa-body-weight);padding:0 25px}
.home-sections{display:flex;flex-direction:column}.home-sections>.section{width:100%}.section{padding:54px 0}.section-head{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:25px}.section-head h2{font-size:var(--villa-body-size);margin:8px 0}.section-head p{color:var(--villa-text);margin:0}.more{font-weight:var(--villa-body-weight);color:var(--villa-text);white-space:nowrap}.pill{display:inline-block;background:#fff0eb;color:var(--villa-text);border-radius:30px;padding:7px 11px;font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);}.destination-grid,.cards,.styles{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}.destination,.style-card{height:225px;border-radius:19px;overflow:hidden;position:relative;background:#e5ece7}.destination img,.style-card img{width:100%;height:100%;object-fit:cover;transition:transform .35s}.destination:hover img,.style-card:hover img{transform:scale(1.05)}.destination:after,.style-card:after{content:"";position:absolute;inset:35% 0 0;background:linear-gradient(transparent,#0009)}.destination-label{position:absolute;z-index:1;left:17px;bottom:15px;color:var(--villa-text);}.destination-label strong{display:block;font-size:var(--villa-body-size);}.destination-label small{font-size:var(--villa-body-size);}.stay-photo,.cards .photo{height:205px;border-radius:17px;overflow:hidden;background:#e9eee9}.stay-photo img,.cards .photo img{width:100%;height:100%;object-fit:cover}.cards .stay-content{display:block}.stay h3{font-size:var(--villa-body-size);margin:12px 0 5px}.muted,.cards .meta{font-size:var(--villa-body-size);color:var(--villa-text);}.rating-line{display:flex;align-items:center;gap:6px;margin-top:7px;color:var(--villa-text);font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);}.rating-line .rating-score{color:var(--villa-text);font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);}.rating-line .rating-star{color:var(--villa-text);}.stay-price,.cards .price{margin-top:8px;font-size:var(--villa-body-size);}.stay-price strong,.cards .price strong{font-size:var(--villa-body-size);}.style-card strong{position:absolute;z-index:1;bottom:18px;left:17px;color:var(--villa-text);font-size:var(--villa-body-size);}.gallery-strip{display:grid;grid-template-columns:1.3fr 1fr 1fr;grid-template-rows:180px 180px;gap:12px}.gallery-strip img{width:100%;height:100%;object-fit:cover;border-radius:17px}.gallery-strip img:first-child{grid-row:span 2}.host-banner{margin:30px auto 65px;min-height:290px;border-radius:25px;overflow:hidden;background:linear-gradient(90deg,#153b35dd,#153b3530),url('https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1800&q=85') center/cover;display:flex;align-items:center;color:var(--villa-text);}.host-copy{padding:42px;max-width:650px}.host-copy h2{font-size:var(--villa-body-size);margin:0 0 12px}.host-copy p{line-height:var(--villa-body-line-height);}.host-copy a{display:inline-block;background:#fff;color:var(--villa-text);padding:13px 19px;border-radius:12px;font-weight:var(--villa-body-weight);margin-top:10px}footer{background:#20352e;color:var(--villa-text);padding:38px 0}footer p{color:var(--villa-text);}.footer-inner{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,2fr);gap:28px;align-items:start}.footer-copy{min-width:0}footer nav{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px 18px;font-size:var(--villa-body-size);}footer nav a{min-width:0;overflow-wrap:anywhere}.drawer-overlay{display:none}
@media(max-width:900px){.destination-grid,.cards,.styles{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){html,body{max-width:100%;overflow-x:clip}.wrap{width:calc(100% - 28px);min-width:0}.site-header{height:68px;position:relative;z-index:1002}.mobile-menu-toggle{display:block;position:relative;z-index:1003}.nav{position:fixed;z-index:1002;top:0;right:0;bottom:0;width:min(86vw,360px);height:100dvh;background:#fff;padding:85px 24px 25px;display:flex;flex-direction:column;align-items:stretch;gap:0;transform:translateX(105%);visibility:hidden;transition:.25s;box-shadow:-15px 0 40px #102d2520}.nav.is-open{transform:translateX(0);visibility:visible}.nav a{padding:16px 4px;border-bottom:1px solid var(--line)}.drawer-overlay{display:block;position:fixed;inset:0;z-index:1001;background:#10251f80;opacity:0;visibility:hidden;transition:.25s}.drawer-overlay.is-open{opacity:1;visibility:visible}body.menu-open{overflow:hidden}.hero{padding:15px 0 28px}.hero-card{min-height:550px;background-position:58% center}.hero-copy{padding:30px 21px}.hero h1{font-size:var(--villa-body-size);letter-spacing:-1.6px}.hero p{font-size:var(--villa-body-size);}.search{display:grid;grid-template-columns:1fr;padding:10px}.search input,.search select{width:100%;padding:12px}.search button{height:48px}.section{padding:36px 0;min-width:0}.section-head{align-items:flex-start;flex-wrap:wrap;min-width:0}.section-head>div{min-width:0;max-width:100%}.section-head .more{display:inline-block;max-width:100%;white-space:normal;overflow-wrap:anywhere;text-align:left;margin-top:2px}.section-head p{font-size:var(--villa-body-size);}.destination-grid,.styles{gap:10px}.destination,.style-card{height:165px;border-radius:14px}.destination-label{left:11px;bottom:11px}.destination-label strong{font-size:var(--villa-body-size);}.cards{gap:12px}.stay-photo{height:145px;border-radius:13px}.stay h3{font-size:var(--villa-body-size);}.style-card strong{font-size:var(--villa-body-size);left:11px;bottom:12px}.gallery-strip{grid-template-rows:120px 120px;gap:7px}.host-banner{margin:20px auto 38px;min-height:290px}.host-copy{padding:23px}.host-copy h2{font-size:var(--villa-body-size);}.footer-inner{grid-template-columns:1fr;gap:18px}footer nav{grid-template-columns:repeat(2,minmax(0,1fr));gap:11px 14px}}

.search-enhanced{display:grid;grid-template-columns:minmax(190px,1.5fr) repeat(2,minmax(135px,1fr)) minmax(150px,1.1fr) auto;align-items:stretch;gap:0;padding:8px;background:#fff;border:1px solid #e8eeea;border-radius:19px;box-shadow:0 15px 40px #102d251c;color:var(--villa-text);max-width:1100px}.search-enhanced .search-field{position:relative;display:flex;flex-direction:column;justify-content:center;gap:5px;min-width:0;padding:9px 14px;border-right:1px solid #e8eeea;text-align:left}.search-enhanced .search-label{display:block;color:var(--villa-text);font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);letter-spacing:.3px}.search-icon{color:var(--villa-text);font-size:var(--villa-body-size);margin-right:4px}.search-enhanced .destination-field input{width:100%;min-width:0;padding:2px 0;border:0;background:transparent;color:var(--villa-text);font:inherit;outline:none}.search-enhanced .destination-field input::placeholder{color:var(--villa-text);font-weight:var(--villa-body-weight);}.search-enhanced .date-display,.search-enhanced .guest-trigger{display:block;width:100%;padding:2px 0;border:0;background:transparent;color:var(--villa-text);text-align:left;font:inherit;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.search-enhanced .date-field:has(input:valid) .date-display{color:var(--villa-text);}.search-enhanced .date-field input[type=date]{position:absolute;inset:0;width:100%;height:100%;opacity:.01;cursor:pointer}.search-enhanced .guest-trigger{display:flex;justify-content:space-between;align-items:center;color:var(--villa-text);cursor:pointer}.guest-chevron{color:var(--villa-text);font-size:var(--villa-body-size);}.search-enhanced .search-submit{align-self:stretch;min-width:125px;padding:0 18px;border:0;border-radius:13px;background:var(--coral);color:var(--villa-text);font-weight:var(--villa-body-weight);font-size:var(--villa-body-size);cursor:pointer}.search-enhanced .search-submit span{font-size:var(--villa-body-size);margin-right:4px}.guest-popover{position:absolute;z-index:30;top:calc(100% + 14px);right:0;width:280px;padding:15px;background:#fff;border:1px solid #e6ece8;border-radius:16px;box-shadow:0 18px 45px #102d2526;color:var(--villa-text);}.guest-popover[hidden]{display:none}.guest-row{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:11px 0;border-bottom:1px solid #edf1ee}.guest-row strong,.guest-row small{display:block}.guest-row strong{font-size:var(--villa-body-size);}.guest-row small{margin-top:3px;color:var(--villa-text);font-size:var(--villa-body-size);}.counter{display:flex;align-items:center;gap:12px}.counter b{min-width:12px;text-align:center;font-size:var(--villa-body-size);}.counter button{width:30px;height:30px;border:1px solid #dce6e0;border-radius:50%;background:#fff;color:var(--villa-text);font-size:var(--villa-body-size);cursor:pointer}.counter button:disabled{opacity:.35;cursor:not-allowed}.guest-done{text-align:right;padding-top:12px}.guest-done button{border:0;border-radius:9px;background:var(--teal);color:var(--villa-text);padding:9px 16px;font-weight:var(--villa-body-weight);cursor:pointer}
@media(max-width:900px){.search-enhanced{grid-template-columns:repeat(2,minmax(0,1fr));gap:0}.search-enhanced .search-field{min-height:72px;border-bottom:1px solid #e8eeea}.search-enhanced .destination-field,.search-enhanced .guest-field{border-right:0}.search-enhanced .date-field:nth-of-type(3){border-right:0}.search-enhanced .search-submit{grid-column:1/-1;min-height:49px;margin-top:8px}}
@media(max-width:600px){.search-enhanced{display:grid;grid-template-columns:1fr 1fr;gap:0;padding:8px;border-radius:16px}.search-enhanced .search-field{padding:10px 9px;min-height:69px}.search-enhanced .destination-field,.search-enhanced .guest-field{grid-column:1/-1;border-right:0}.search-enhanced .date-field{border-right:1px solid #e8eeea}.search-enhanced .date-field:nth-of-type(3){border-right:0}.search-enhanced .date-display,.search-enhanced .guest-trigger{font-size:var(--villa-body-size);}.search-enhanced .search-submit{grid-column:1/-1;min-height:49px;margin-top:8px}.guest-popover{right:0;left:0;width:100%;top:calc(100% + 8px)}}
.location-suggestions{position:absolute;z-index:1000;top:calc(100% + 7px);left:0;right:0;max-height:280px;overflow-y:auto;background:#fff;border:1px solid #e3ebe6;border-radius:12px;box-shadow:0 14px 35px #102d2526;padding:8px 0}.location-suggestions[hidden]{display:none}.location-suggestion{display:block;width:100%;padding:10px 15px;border:0;border-radius:0;background:transparent;color:var(--villa-text);text-align:left;font:inherit;line-height:var(--villa-body-line-height);cursor:pointer;white-space:normal}.location-suggestion:hover,.location-suggestion.is-active{background:#f4f8f5;color:var(--villa-text);}.destination-field{z-index:5}
.mobile-premium-search,.mobile-premium-search__form{overflow:visible}
.mobile-premium-search{position:relative;z-index:12}
.mobile-premium-search__destination{isolation:isolate}
.mobile-premium-search__destination .location-suggestions{z-index:1300;max-height:min(280px,45vh);}

/* Hierarchical navigation dropdowns */
.nav-entry{position:relative;display:inline-flex;align-items:center;min-width:0}.nav-entry-top{display:flex;align-items:center;gap:4px}.nav-entry-top>a{display:inline-flex;align-items:center}.nav-submenu-toggle{border:0;background:transparent;color:var(--villa-text);font:inherit;padding:5px 7px;cursor:pointer;transition:transform .22s ease}.nav-entry.is-open>.nav-entry-top .nav-submenu-toggle{transform:rotate(180deg)}.nav-dropdown{position:absolute;z-index:1200;top:calc(100% + 9px);left:0;min-width:220px;padding:8px;background:#fff;border:1px solid #e8eeea;border-radius:14px;box-shadow:0 18px 42px #102d2520;opacity:0;visibility:hidden;transform:translateY(8px);transition:opacity .2s ease,transform .2s ease,visibility .2s ease;display:flex;flex-direction:column;gap:2px}.nav-entry:hover>.nav-dropdown,.nav-entry:focus-within>.nav-dropdown,.nav-entry.is-open>.nav-dropdown{opacity:1;visibility:visible;transform:translateY(0)}.nav-dropdown .nav-entry{display:block;width:100%}.nav-dropdown .nav-entry-top>a{display:block;width:100%;padding:10px 12px;border-radius:8px;white-space:normal}.nav-dropdown .nav-entry-top>a:hover{background:#eff7f3}.nav-dropdown .nav-submenu-toggle{display:none}.footer-links .nav-entry{display:block;min-width:0}.footer-links .nav-dropdown{position:static;min-width:0;padding:4px 0 4px 12px;border:0;border-radius:0;box-shadow:none;background:transparent;opacity:1;visibility:visible;transform:none;display:flex}.footer-links .nav-dropdown .nav-entry-top>a{padding:4px 0;color:var(--villa-text);}.footer-links .nav-submenu-toggle{display:none}
.villa-login-overlay{position:fixed;inset:0;z-index:5000;background:rgba(13,28,23,.58);backdrop-filter:blur(4px);opacity:0;transition:opacity .22s ease}.villa-login-overlay:not([hidden]){opacity:1}.villa-login-modal{position:fixed;inset:0;z-index:5001;display:grid;place-items:center;padding:18px;pointer-events:none}.villa-login-modal[hidden],.villa-login-overlay[hidden]{display:none}.villa-login-card{pointer-events:auto;position:relative;width:min(440px,100%);padding:30px;background:#fff;border-radius:22px;box-shadow:0 25px 80px #071b1438;transform:translateY(12px) scale(.98);opacity:0;transition:transform .32s cubic-bezier(.22,.75,.25,1),opacity .28s ease}.villa-login-open .villa-login-card{transform:translateY(0) scale(1);opacity:1}@media(max-width:600px){.villa-login-modal{align-items:end;padding:0 12px calc(76px + env(safe-area-inset-bottom))}.villa-login-card{width:100%;max-width:480px;padding:26px 21px 22px;border-radius:22px 22px 18px 18px;transform:translateY(calc(100% + 24px));max-height:calc(100dvh - 112px);overflow-y:auto}.villa-login-open .villa-login-card{transform:translateY(0)}}.villa-login-card h2{margin:0 0 7px;font-size:var(--villa-body-size);color:var(--villa-text);}.villa-login-card>p{margin:0 0 20px;color:var(--villa-text);}.villa-login-card label{display:block;margin:14px 0 6px;font-size:var(--villa-body-size);font-weight:var(--villa-body-weight);}.villa-login-card input{width:100%;padding:13px;border:1px solid #dfe8e2;border-radius:10px;font:inherit;outline:none}.villa-login-card input:focus{border-color:#167c80;box-shadow:0 0 0 3px #167c8018}.villa-login-submit{width:100%;margin-top:20px;padding:14px;border:0;border-radius:11px;background:#167c80;color:var(--villa-text);font-weight:var(--villa-body-weight);font-size:var(--villa-body-size);cursor:pointer}.villa-login-submit:disabled{opacity:.65;cursor:wait}.villa-login-close{position:absolute;right:14px;top:12px;width:36px;height:36px;border:0;border-radius:50%;background:#f1f5f2;color:var(--villa-text);font-size:var(--villa-body-size);cursor:pointer}.villa-login-error{margin-top:12px;padding:10px 12px;border-radius:9px;background:#fff0ef;color:var(--villa-text);font-size:var(--villa-body-size);}.villa-login-foot{margin-top:17px;text-align:center;font-size:var(--villa-body-size);color:var(--villa-text);}.villa-login-foot a{color:var(--villa-text);font-weight:var(--villa-body-weight);}
@media(max-width:700px){.footer-links .nav-dropdown{max-height:none;padding:4px 0 4px 12px}.nav-entry{display:block;width:100%}.nav-entry-top{width:100%;justify-content:space-between}.nav-entry-top>a{flex:1;padding:14px 4px;border-bottom:1px solid #eef1ee}.nav-submenu-toggle{width:42px;height:42px;border-radius:9px}.nav-entry.is-open>.nav-entry-top .nav-submenu-toggle{background:#edf6f2}.nav-dropdown{position:static;min-width:0;padding:0 0 0 14px;border:0;border-radius:0;box-shadow:none;background:#f8fbf9;max-height:0;overflow:hidden;opacity:1;visibility:visible;transform:none;display:block;transition:max-height .28s ease,padding .28s ease}.nav-entry.is-open>.nav-dropdown{max-height:520px;padding:5px 0 8px 14px}.nav-dropdown .nav-entry-top>a{padding:12px 9px;font-size:var(--villa-body-size);}.villa-login-card{padding:26px 21px;border-radius:18px}}
@media(prefers-reduced-motion:reduce){.nav-dropdown,.villa-login-card,.villa-login-overlay{transition:none}}

.site-header .head{width:min(1200px,calc(100% - 48px));max-width:1200px;margin:0 auto;justify-content:space-between}
.brand{display:inline-flex;align-items:center;min-width:0;max-width:260px;line-height:var(--villa-body-line-height);}.brand img{display:block;width:auto;height:auto;max-width:220px;max-height:54px;object-fit:contain}
.hero{padding:0;background:#102a25}.hero>.hero-card{width:100%;max-width:none;margin:0}
.hero-card{position:relative;isolation:isolate;width:100%;min-height:680px;padding:68px max(28px,calc((100vw - 1200px)/2));display:flex;align-items:center;background:#183b32 center/cover no-repeat}
.hero-video{position:absolute;top:50%;left:50%;width:100%;height:100%;z-index:0;object-fit:cover;transform:translate(-50%,-50%);pointer-events:none}.hero-video-youtube{border:0}
/* Uploaded MP4/WebM hero background: force full-bleed sizing independently of iframe rules. */
.hero-card>video.hero-video{position:absolute!important;inset:0!important;display:block!important;width:100%!important;height:100%!important;min-width:100%!important;min-height:100%!important;max-width:none!important;max-height:none!important;margin:0!important;transform:none!important;object-fit:cover!important;object-position:center center!important;}
.hero-shade{position:absolute;inset:0;z-index:1;background:rgba(8,29,25,.58);pointer-events:none;overflow:hidden}.hero-shade::after{content:"";position:absolute;inset:0;pointer-events:none;opacity:.34}.hero-shade[data-texture="diagonal"]::after{background-image:repeating-linear-gradient(135deg,rgba(255,255,255,.22) 0 1px,transparent 1px 9px)}.hero-shade[data-texture="grid"]::after{background-image:linear-gradient(rgba(255,255,255,.16) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.16) 1px,transparent 1px);background-size:24px 24px}.hero-shade[data-texture="dots"]::after{background-image:radial-gradient(rgba(255,255,255,.28) 1px,transparent 1.5px);background-size:12px 12px}
.hero-inner{position:relative;z-index:2;width:100%;max-width:1200px;margin:0 auto;display:grid;grid-template-columns:minmax(0,2fr) minmax(310px,1fr);gap:5%;align-items:center}
.hero-copy{width:100%;max-width:760px;padding:25px 0;color:var(--villa-text);}.hero-copy .eyebrow{display:inline-block;margin-bottom:12px}.hero-copy h1{max-width:780px;font-size:var(--villa-body-size);line-height:var(--villa-body-line-height);letter-spacing:-2.5px;text-wrap:balance}.hero-copy p{max-width:650px;font-size:var(--villa-body-size);line-height:var(--villa-body-line-height);}
.hero-filter-panel{position:relative;z-index:3;width:100%;max-width:420px;justify-self:end;padding:22px;background:rgba(255,255,255,.97);border:1px solid rgba(255,255,255,.7);border-radius:22px;box-shadow:0 24px 70px rgba(0,0,0,.24);backdrop-filter:blur(12px)}
.hero-filter-panel .search-enhanced{display:grid;grid-template-columns:minmax(0,1fr);gap:0;width:100%;max-width:none;padding:0;border:0;border-radius:0;box-shadow:none;background:transparent}
.hero-filter-panel .search-field{min-height:67px;padding:10px 3px;border-right:0;border-bottom:1px solid #e8eeea}.hero-filter-panel .search-submit{width:100%;min-height:54px;margin-top:15px;border-radius:12px}.hero-filter-panel .guest-popover{left:0;right:0;width:100%}
@media(max-width:900px){.hero-card{min-height:0;padding:54px 28px}.hero-inner{grid-template-columns:minmax(0,1.3fr) minmax(270px,.9fr);gap:24px}.hero-copy h1{font-size:var(--villa-body-size);}}
@media(max-width:680px){.site-header .head{width:calc(100% - 28px)}.brand{max-width:190px}.brand img{max-width:160px;max-height:43px}.hero-card{padding:45px 18px 30px;min-height:0}.hero-inner{grid-template-columns:1fr;gap:18px}.hero-copy{padding:5px 2px}.hero-copy h1{font-size:var(--villa-body-size);letter-spacing:-1.6px}.hero-copy p{font-size:var(--villa-body-size);}.hero-filter-panel{max-width:none;justify-self:stretch;padding:16px;border-radius:17px}.hero-shade{background:linear-gradient(180deg,rgba(8,29,25,.72),rgba(8,29,25,.58))}}


/* Airbnb-inspired range calendar for the homepage hero search. */
.date-picker-trigger{width:100%;padding:2px 0;border:0;background:transparent;color:var(--villa-text);font:inherit;text-align:left;cursor:pointer;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.date-picker-trigger:focus-visible{outline:2px solid var(--teal);outline-offset:4px;border-radius:4px}.home-calendar[hidden]{display:none!important}.home-calendar{position:fixed;z-index:11000;width:min(680px,calc(100vw - 24px));max-height:calc(100vh - 24px);overflow-y:auto;padding:18px;background:#fff;border:1px solid #e6ece8;border-radius:20px;box-shadow:0 22px 65px rgba(12,35,27,.24);color:#24352e}.calendar-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px}.calendar-toolbar strong{font-size:15px;font-weight:700;text-align:center}.calendar-toolbar button{width:36px;height:36px;border:1px solid #e4ebe6;border-radius:50%;background:#fff;color:#24352e;font-size:23px;line-height:1;cursor:pointer}.calendar-toolbar button:disabled{opacity:.3;cursor:not-allowed}.calendar-months{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:26px}.calendar-month h3{margin:0 0 13px;text-align:center;font-size:14px;font-weight:700;text-transform:capitalize}.calendar-weekdays,.calendar-days{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:3px}.calendar-weekdays{margin-bottom:6px}.calendar-weekdays span{padding:7px 0;color:#829087;text-align:center;font-size:11px;font-weight:600}.calendar-day,.calendar-empty{min-width:0;aspect-ratio:1;border:0;border-radius:50%;display:grid;place-items:center}.calendar-day{background:transparent;color:#26372f;font-size:13px;cursor:pointer}.calendar-day:hover:not(:disabled){border:1px solid #26372f}.calendar-day.is-disabled{color:#c8d0cb;text-decoration:line-through;cursor:not-allowed}.calendar-day.is-selected{background:#203d32;color:#fff;font-weight:700}.calendar-day.is-in-range{background:#eaf4ef;border-radius:0}.calendar-day.is-start{border-radius:50% 0 0 50%}.calendar-day.is-end{border-radius:0 50% 50% 0}.calendar-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:17px;padding-top:14px;border-top:1px solid #edf1ee}.calendar-footer>span{color:#64736b;font-size:12px}.calendar-footer>div{display:flex;align-items:center;gap:8px}.calendar-footer button{padding:9px 14px;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer}.calendar-clear{border:0;background:transparent;color:#52645a;text-decoration:underline}.calendar-done{border:0;background:#203d32;color:#fff}
@media(max-width:600px){.home-calendar{padding:13px;border-radius:16px;max-height:calc(100dvh - 20px);overflow-y:auto}.calendar-months{grid-template-columns:1fr;gap:8px}.calendar-month:nth-child(2){display:none}.calendar-toolbar{margin-bottom:10px}.calendar-day,.calendar-empty{aspect-ratio:1.12}.calendar-footer{align-items:flex-start;flex-direction:column}.calendar-footer>div{width:100%;justify-content:flex-end}}

/* Airbnb-style full-width hero booking bar. */
.hero-card{display:flex;flex-direction:column;justify-content:center;align-items:stretch;gap:28px}
.hero-inner{display:block;width:100%;max-width:1200px;margin:0 auto}
.hero-copy{max-width:820px;padding:0}
.hero-filter-panel{width:100%;max-width:1200px;margin:0 auto;padding:8px;background:rgba(255,255,255,.98);border:1px solid rgba(255,255,255,.8);border-radius:22px;box-shadow:0 18px 55px rgba(0,0,0,.2)}
.hero-filter-panel .search-enhanced{display:grid;grid-template-columns:minmax(170px,1.35fr) repeat(3,minmax(130px,1fr)) auto;gap:0;width:100%;max-width:none;padding:0;border:0;border-radius:15px;background:transparent;box-shadow:none}
.hero-filter-panel .search-field{min-height:68px;padding:10px 15px;border-right:1px solid #e8eeea;border-bottom:0}
.hero-filter-panel .search-field:last-of-type{border-right:0}
.hero-filter-panel .search-submit{width:auto;min-width:145px;min-height:54px;margin:0 0 0 8px;border-radius:14px}
.hero-filter-panel .guest-popover{left:auto;right:0;width:300px}
@media(max-width:900px){.hero-card{min-height:0;padding:58px 28px 42px;gap:24px}.hero-inner{display:block}.hero-filter-panel{max-width:100%}.hero-filter-panel .search-enhanced{grid-template-columns:repeat(2,minmax(0,1fr))}.hero-filter-panel .search-field{min-height:68px;border-right:1px solid #e8eeea;border-bottom:1px solid #e8eeea}.hero-filter-panel .destination-field,.hero-filter-panel .guest-field{grid-column:1/-1;border-right:0}.hero-filter-panel .date-field:nth-of-type(3){border-right:0}.hero-filter-panel .search-submit{grid-column:1/-1;width:100%;margin:8px 0 0}.hero-filter-panel .guest-popover{left:0;right:0;width:100%}}
@media(max-width:680px){.hero-card{padding:46px 16px 28px;gap:20px}.hero-copy{padding:0 3px}.hero-filter-panel{padding:7px;border-radius:17px}.hero-filter-panel .search-enhanced{grid-template-columns:1fr 1fr}.hero-filter-panel .search-field{padding:10px 9px;min-height:65px}.hero-filter-panel .destination-field,.hero-filter-panel .guest-field{grid-column:1/-1}.hero-filter-panel .date-field{border-right:1px solid #e8eeea}.hero-filter-panel .date-field:nth-of-type(3){border-right:0}.hero-filter-panel .search-submit{min-height:50px}}


/* Mobile conversion focus: make villa search the primary hero action. */
@media(max-width:680px){
 body .hero{padding:12px 0 22px;background:#f4f7f4}
 body .hero-card{
  min-height:0;padding:27px 13px 17px;gap:17px;
  border-radius:0!important;background-position:center 48%;
 }
 body .hero-inner{width:100%;max-width:none}
 body .hero-copy{max-width:100%;padding:0 5px;text-align:center}
 body .hero-copy .eyebrow{
  margin:0 0 8px;padding:6px 10px;border-radius:30px;
  background:rgba(255,255,255,.16);font-size:11px;letter-spacing:1.4px
 }
 body .hero-copy h1{max-width:100%;margin:7px 0 8px;font-size:clamp(25px,7vw,32px);line-height:1.15;letter-spacing:-.8px}
 body .hero-copy p{max-width:340px;margin:0 auto;font-size:13px;line-height:1.45}
 body .hero-filter-panel{
  width:100%;max-width:none;justify-self:stretch;margin:0;padding:16px!important;
  border:2px solid rgba(255,255,255,.98)!important;border-radius:20px!important;
  background:#fff!important;box-shadow:0 19px 48px rgba(0,0,0,.34)!important
 }
 body .hero-filter-panel::before{
  content:"PLAN YOUR HOLIDAY";display:block;margin:0 0 9px;padding:0 10px;
  color:#20332c;font-size:15px;font-weight:850;letter-spacing:.65px;text-align:left
 }
 body .mobile-hero-search{display:none!important}
 body .hero-filter-panel .search-enhanced{
  display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);
  gap:0;padding:0;border:0;background:transparent
 }
 body .hero-filter-panel .search-field{
  min-width:0;min-height:76px;padding:11px 10px;
  border-bottom:1px solid #e8eeea;border-right:1px solid #e8eeea
 }
 body .hero-filter-panel .destination-field,
 body .hero-filter-panel .guest-field{grid-column:1/-1;border-right:0}
 body .hero-filter-panel .date-field:nth-of-type(3){border-right:0}
 body .hero-filter-panel .search-label{font-size:12px;font-weight:700;color:#52645a}
 body .hero-filter-panel .search-icon{font-size:15px;color:var(--teal)}
 body .hero-filter-panel .destination-field input,
 body .hero-filter-panel .date-display,
 body .hero-filter-panel .guest-trigger{min-height:27px;font-size:15px;font-weight:600;color:#20332c}
 body .hero-filter-panel .destination-field input::placeholder{color:#69776f;font-weight:500}
 body .hero-filter-panel .search-submit{
  grid-column:1/-1;width:100%;min-height:58px;margin:13px 0 0;
  border-radius:14px;background:var(--villa-primary,var(--coral))!important;color:#fff!important;
  font-size:16px;font-weight:850;letter-spacing:.25px;
  box-shadow:0 7px 16px rgba(255,128,107,.28)
 }
 body .hero-filter-panel .search-submit span{font-size:20px;margin-right:7px}
 body .hero-filter-panel .guest-popover{left:0;right:0;width:100%}
}


/* Active default-theme hero: give the real search form the full visual priority. */
body.villa-home-mediterranean .hero-card{
  display:flex!important;
  flex-direction:column!important;
  align-items:center!important;
  justify-content:center!important;
  min-height:680px!important;
  padding:clamp(48px,6vw,82px) clamp(22px,5vw,64px)!important;
}
body.villa-home-mediterranean .hero-inner{
  display:block!important;
  width:100%!important;
  max-width:1160px!important;
  margin:0 auto!important;
}
body.villa-home-mediterranean .hero-copy{
  width:100%!important;
  max-width:none!important;
  padding:0!important;
  text-align:center!important;
}
body.villa-home-mediterranean .hero-copy h1{
  max-width:980px!important;
  margin:12px auto 14px!important;
  font-size:clamp(40px,5.4vw,70px)!important;
  line-height:1.06!important;
  letter-spacing:-1.8px!important;
  text-wrap:balance;
}
body.villa-home-mediterranean .hero-copy p{
  max-width:760px!important;
  margin:0 auto!important;
  font-size:clamp(16px,1.5vw,19px)!important;
  line-height:1.55!important;
}
body.villa-home-mediterranean .hero-filter-panel{
  width:100%!important;
  max-width:1160px!important;
  margin:34px auto 0!important;
  padding:14px!important;
  justify-self:auto!important;
  border:1px solid rgba(255,255,255,.9)!important;
  border-radius:24px!important;
  background:#fff!important;
  box-shadow:0 24px 68px rgba(5,25,22,.34),0 0 0 7px rgba(255,255,255,.15)!important;
  backdrop-filter:none!important;
}
body.villa-home-mediterranean .hero-filter-panel .search-enhanced{
  display:grid!important;
  grid-template-columns:minmax(210px,1.45fr) repeat(3,minmax(145px,1fr)) minmax(160px,.9fr)!important;
  align-items:stretch!important;
  gap:0!important;
  width:100%!important;
  max-width:none!important;
  min-height:96px!important;
  padding:0!important;
  border:0!important;
  border-radius:0!important;
  background:transparent!important;
  box-shadow:none!important;
}
body.villa-home-mediterranean .hero-filter-panel .search-field{
  min-width:0!important;
  min-height:88px!important;
  padding:15px 17px!important;
  border:0!important;
  border-right:1px solid #e5ebe6!important;
  border-bottom:0!important;
  background:transparent!important;
}
body.villa-home-mediterranean .hero-filter-panel .search-label{
  margin-bottom:5px!important;
  color:#53645b!important;
  font-size:12px!important;
  font-weight:800!important;
  letter-spacing:.45px!important;
}
body.villa-home-mediterranean .hero-filter-panel .search-icon{
  color:var(--villa-primary)!important;
  font-size:18px!important;
}
body.villa-home-mediterranean .hero-filter-panel .destination-field input,
body.villa-home-mediterranean .hero-filter-panel .date-display,
body.villa-home-mediterranean .hero-filter-panel .guest-trigger{
  min-height:30px!important;
  color:#24362e!important;
  font-size:16px!important;
  font-weight:700!important;
}
body.villa-home-mediterranean .hero-filter-panel .destination-field input::placeholder{
  color:#77857d!important;
  font-weight:500!important;
}
body.villa-home-mediterranean .hero-filter-panel .search-submit{
  align-self:stretch!important;
  min-width:0!important;
  min-height:78px!important;
  margin:0 0 0 12px!important;
  padding:0 20px!important;
  border:0!important;
  border-radius:16px!important;
  background:var(--villa-primary)!important;
  color:#fff!important;
  font-size:17px!important;
  font-weight:850!important;
  box-shadow:0 10px 24px color-mix(in srgb,var(--villa-primary) 28%,transparent)!important;
}
body.villa-home-mediterranean .hero-filter-panel .search-submit:hover{
  filter:brightness(.96);
  transform:translateY(-1px);
}
@media(max-width:1050px){
  body.villa-home-mediterranean .hero-card{min-height:640px!important;padding:48px 24px!important}
  body.villa-home-mediterranean .hero-filter-panel{margin-top:28px!important}
  body.villa-home-mediterranean .hero-filter-panel .search-enhanced{
    grid-template-columns:repeat(2,minmax(0,1fr))!important;
    min-height:0!important;
  }
  body.villa-home-mediterranean .hero-filter-panel .search-field{
    min-height:78px!important;
    border-bottom:1px solid #e5ebe6!important;
  }
  body.villa-home-mediterranean .hero-filter-panel .destination-field,
  body.villa-home-mediterranean .hero-filter-panel .guest-field{
    grid-column:1/-1!important;
    border-right:0!important;
  }
  body.villa-home-mediterranean .hero-filter-panel .date-field:nth-of-type(3){border-right:0!important}
  body.villa-home-mediterranean .hero-filter-panel .search-submit{
    grid-column:1/-1!important;
    min-height:60px!important;
    margin:12px 0 0!important;
  }
}
@media(max-width:600px){
  body.villa-home-mediterranean .hero-card{
    min-height:0!important;
    padding:38px 14px 24px!important;
  }
  body.villa-home-mediterranean .hero-copy h1{
    margin:8px auto 10px!important;
    font-size:clamp(30px,8vw,38px)!important;
    letter-spacing:-.9px!important;
  }
  body.villa-home-mediterranean .hero-copy p{font-size:14px!important}
  body.villa-home-mediterranean .hero-filter-panel{
    margin-top:22px!important;
    padding:13px!important;
    border-radius:20px!important;
    box-shadow:0 18px 46px rgba(0,0,0,.34),0 0 0 5px rgba(255,255,255,.14)!important;
  }
  body.villa-home-mediterranean .hero-filter-panel .search-field{
    min-height:72px!important;
    padding:10px 9px!important;
  }
  body.villa-home-mediterranean .hero-filter-panel .search-label{font-size:11px!important}
  body.villa-home-mediterranean .hero-filter-panel .destination-field input,
  body.villa-home-mediterranean .hero-filter-panel .date-display,
  body.villa-home-mediterranean .hero-filter-panel .guest-trigger{font-size:14px!important}
  body.villa-home-mediterranean .hero-filter-panel .search-submit{
    min-height:56px!important;
    font-size:16px!important;
  }
}
@media(prefers-reduced-motion:reduce){
  body.villa-home-mediterranean .hero-filter-panel .search-submit{transition:none!important}
}


/* Homepage hero search: global rules (the document body intentionally has no theme-specific class). */
body .hero-card{
  min-height:680px!important;
  padding:64px max(24px,calc((100vw - 1200px)/2)) 54px!important;
  display:flex!important;
  flex-direction:column!important;
  justify-content:center!important;
  align-items:stretch!important;
  gap:0!important;
}
body .hero-inner{
  display:block!important;
  width:100%!important;
  max-width:1160px!important;
  margin:0 auto!important;
}
body .hero-copy{
  width:100%!important;
  max-width:900px!important;
  margin:0 auto!important;
  padding:0 12px!important;
  text-align:center!important;
}
body .hero-copy .eyebrow{margin-bottom:12px!important}
body .hero-copy h1{
  max-width:900px!important;
  margin:8px auto 12px!important;
  font-size:clamp(34px,4.5vw,58px)!important;
  line-height:1.08!important;
  letter-spacing:-1.6px!important;
  text-wrap:balance;
}
body .hero-copy p{
  max-width:700px!important;
  margin:0 auto!important;
  font-size:clamp(15px,1.35vw,18px)!important;
  line-height:1.55!important;
}
body .hero-filter-panel{
  position:relative!important;
  z-index:5!important;
  width:100%!important;
  max-width:1160px!important;
  margin:34px auto 0!important;
  padding:13px!important;
  justify-self:auto!important;
  border:1px solid rgba(255,255,255,.92)!important;
  border-radius:22px!important;
  background:#fff!important;
  box-shadow:0 24px 68px rgba(5,25,22,.34),0 0 0 7px rgba(255,255,255,.15)!important;
  backdrop-filter:none!important;
}
body .hero-filter-panel .search-enhanced{
  display:grid!important;
  grid-template-columns:minmax(190px,1.4fr) repeat(3,minmax(135px,1fr)) minmax(150px,.95fr)!important;
  align-items:stretch!important;
  gap:0!important;
  width:100%!important;
  max-width:none!important;
  min-height:92px!important;
  padding:0!important;
  border:0!important;
  border-radius:14px!important;
  background:transparent!important;
  box-shadow:none!important;
}
body .hero-filter-panel .search-field{
  min-width:0!important;
  min-height:82px!important;
  padding:14px 15px!important;
  border:0!important;
  border-right:1px solid #e5ebe6!important;
  border-bottom:0!important;
  background:transparent!important;
}
body .hero-filter-panel .search-label{
  margin-bottom:6px!important;
  color:#53645b!important;
  font-size:12px!important;
  font-weight:800!important;
  letter-spacing:.45px!important;
}
body .hero-filter-panel .search-icon{color:var(--villa-primary)!important;font-size:18px!important}
body .hero-filter-panel .destination-field input,
body .hero-filter-panel .date-display,
body .hero-filter-panel .guest-trigger{
  min-height:30px!important;
  color:#24362e!important;
  font-size:16px!important;
  font-weight:700!important;
}
body .hero-filter-panel .destination-field input::placeholder{color:#77857d!important;font-weight:500!important}
body .hero-filter-panel .search-submit{
  align-self:stretch!important;
  min-width:0!important;
  min-height:72px!important;
  margin:0 0 0 12px!important;
  padding:0 18px!important;
  border:0!important;
  border-radius:15px!important;
  background:var(--villa-primary)!important;
  color:#fff!important;
  font-size:17px!important;
  font-weight:850!important;
  box-shadow:0 10px 24px color-mix(in srgb,var(--villa-primary) 28%,transparent)!important;
  transition:transform .18s ease,box-shadow .18s ease!important;
}
body .hero-filter-panel .search-submit:hover{
  background:var(--villa-primary)!important;
  filter:brightness(.94);
  transform:translateY(-2px);
  box-shadow:0 14px 28px color-mix(in srgb,var(--villa-primary) 35%,transparent)!important;
}
body .hero-filter-panel .home-calendar{grid-column:1/-1}
body .hero-filter-panel .guest-popover{left:auto;right:0;width:300px}
@media(max-width:1050px){
  body .hero-card{min-height:0!important;padding:58px 26px 46px!important}
  body .hero-filter-panel{margin-top:28px!important}
  body .hero-filter-panel .search-enhanced{grid-template-columns:repeat(2,minmax(0,1fr))!important;min-height:0!important}
  body .hero-filter-panel .search-field{min-height:76px!important;border-bottom:1px solid #e5ebe6!important}
  body .hero-filter-panel .destination-field,
  body .hero-filter-panel .guest-field{grid-column:1/-1!important;border-right:0!important}
  body .hero-filter-panel .date-field:nth-of-type(3){border-right:0!important}
  body .hero-filter-panel .search-submit{grid-column:1/-1!important;min-height:60px!important;margin:10px 0 0!important}
  body .hero-filter-panel .home-calendar{grid-column:1/-1!important}
  body .hero-filter-panel .guest-popover{left:0;right:0;width:100%}
}
@media(max-width:600px){
  body .hero-card{min-height:0!important;padding:40px 14px 28px!important}
  body .hero-copy{padding:0 3px!important}
  body .hero-copy h1{font-size:clamp(30px,8vw,38px)!important;letter-spacing:-.9px!important}
  body .hero-copy p{font-size:14px!important}
  body .hero-filter-panel{margin-top:22px!important;padding:11px!important;border-radius:19px!important;box-shadow:0 18px 46px rgba(0,0,0,.32),0 0 0 5px rgba(255,255,255,.14)!important}
  body .hero-filter-panel .search-enhanced{grid-template-columns:1fr 1fr!important}
  body .hero-filter-panel .search-field{min-height:70px!important;padding:10px 9px!important}
  body .hero-filter-panel .destination-field,
  body .hero-filter-panel .guest-field{grid-column:1/-1!important}
  body .hero-filter-panel .date-field{border-right:1px solid #e5ebe6!important}
  body .hero-filter-panel .date-field:nth-of-type(3){border-right:0!important}
  body .hero-filter-panel .search-label{font-size:11px!important}
  body .hero-filter-panel .destination-field input,
  body .hero-filter-panel .date-display,
  body .hero-filter-panel .guest-trigger{font-size:14px!important}
  body .hero-filter-panel .search-submit{min-height:55px!important;font-size:16px!important}
  body .hero-filter-panel .home-calendar{grid-column:1/-1!important}
}
@media(prefers-reduced-motion:reduce){body .hero-filter-panel .search-submit{transition:none!important}}


/* Desktop: keep the hero message clear on the left and the search panel on the right.
   The existing stacked/mobile composition remains unchanged below this breakpoint. */
@media(min-width:901px){
  body .hero-card{
    display:grid!important;
    grid-template-columns:minmax(0,.82fr) minmax(0,1.28fr)!important;
    align-items:center!important;
    gap:clamp(20px,2.4vw,36px)!important;
    min-height:640px!important;
    padding:54px max(28px,calc((100vw - 1200px)/2))!important;
  }
  body .hero-inner,
  body .hero-filter-panel{min-width:0!important;box-sizing:border-box!important}
  body .hero-inner{display:block!important;width:100%!important;max-width:none!important;margin:0!important}
  body .hero-copy{width:100%!important;max-width:520px!important;min-width:0!important;margin:0!important;padding:0!important;text-align:left!important}
  body .hero-copy h1{max-width:520px!important;margin:8px 0 12px!important;text-align:left!important}
  body .hero-copy p{max-width:480px!important;margin:0!important;text-align:left!important}
  body .hero-filter-panel{width:100%!important;max-width:none!important;min-width:0!important;margin:0!important;justify-self:stretch!important;padding:12px!important;overflow:visible!important}
  body .hero-filter-panel .search-enhanced{display:grid!important;width:100%!important;max-width:100%!important;min-width:0!important;box-sizing:border-box!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;min-height:0!important}
  body .hero-filter-panel .search-field{min-width:0!important;max-width:100%!important;box-sizing:border-box!important;min-height:72px!important;border-right:1px solid #e5ebe6!important;border-bottom:1px solid #e5ebe6!important}
  body .hero-filter-panel .destination-field,
  body .hero-filter-panel .guest-field{grid-column:1/-1!important;border-right:0!important}
  body .hero-filter-panel .date-field:nth-of-type(3){border-right:0!important}
  body .hero-filter-panel .search-submit{display:block!important;grid-column:1/-1!important;width:100%!important;max-width:100%!important;min-width:0!important;box-sizing:border-box!important;justify-self:stretch!important;min-height:58px!important;margin:8px 0 0!important;padding-left:12px!important;padding-right:12px!important}
  body .hero-filter-panel .home-calendar{grid-column:1/-1!important}
  body .hero-filter-panel .guest-popover{left:auto;right:0;width:min(300px,100%)}
  /* Keep the desktop submit button inside the white search card at every desktop width. */
  body .hero-card .hero-filter-panel form#homeSearch.search-enhanced{
    grid-template-columns:minmax(0,1fr) minmax(0,1fr)!important;
    width:100%!important;max-width:100%!important;min-width:0!important;
  }
  body .hero-card .hero-filter-panel form#homeSearch.search-enhanced > .search-submit{
    display:block!important;grid-column:1 / -1!important;
    justify-self:stretch!important;align-self:stretch!important;
    width:100%!important;max-width:100%!important;min-width:0!important;
    box-sizing:border-box!important;margin:8px 0 0!important;
    padding-left:12px!important;padding-right:12px!important;
  }
}

/* Mobile-only hero search emphasis. Desktop rules above remain untouched. */
@media(max-width:600px){
  body .hero-card{
    min-height:0!important;
    padding:22px 13px 28px!important;
    gap:15px!important;
  }
  body .hero-filter-panel{
    order:-1!important;
    width:100%!important;
    max-width:none!important;
    margin:0 auto!important;
    padding:15px!important;
    border:2px solid var(--villa-primary)!important;
    border-radius:21px!important;
    background:#fff!important;
    box-shadow:0 18px 46px rgba(0,0,0,.38),0 0 0 4px rgba(255,255,255,.28)!important;
    overflow:visible!important;
  }
  body .hero-filter-panel::before{
    content:"FIND YOUR VILLA";
    display:block!important;
    margin:0 0 3px!important;
    color:#20332c!important;
    font-size:19px!important;
    line-height:1.2!important;
    font-weight:900!important;
    letter-spacing:.2px!important;
    text-align:left!important;
  }
  body .hero-filter-panel::after{
    content:"Choose your destination, dates and guests";
    display:block!important;
    margin:0 0 12px!important;
    color:#65736b!important;
    font-size:12px!important;
    line-height:1.4!important;
    font-weight:500!important;
    text-align:left!important;
  }
  body .hero-filter-panel .search-enhanced{
    display:grid!important;
    grid-template-columns:repeat(2,minmax(0,1fr))!important;
    gap:8px!important;
    width:100%!important;
    min-height:0!important;
    padding:0!important;
    border:0!important;
    background:transparent!important;
    box-shadow:none!important;
  }
  body .hero-filter-panel .search-field{
    box-sizing:border-box!important;
    min-width:0!important;
    min-height:74px!important;
    margin:0!important;
    padding:11px 12px!important;
    border:1px solid #e1e9e3!important;
    border-radius:13px!important;
    background:#f7faf8!important;
  }
  body .hero-filter-panel .destination-field,
  body .hero-filter-panel .guest-field{
    grid-column:1/-1!important;
  }
  body .hero-filter-panel .date-field{
    border-right:1px solid #e1e9e3!important;
  }
  body .hero-filter-panel .date-field:nth-of-type(3){
    border-right:1px solid #e1e9e3!important;
  }
  body .hero-filter-panel .search-label{
    display:block!important;
    margin:0 0 6px!important;
    color:#58685f!important;
    font-size:12px!important;
    line-height:1.2!important;
    font-weight:800!important;
    letter-spacing:.25px!important;
  }
  body .hero-filter-panel .search-icon{
    color:var(--villa-primary)!important;
    font-size:16px!important;
  }
  body .hero-filter-panel .destination-field input,
  body .hero-filter-panel .date-display,
  body .hero-filter-panel .guest-trigger{
    box-sizing:border-box!important;
    width:100%!important;
    min-width:0!important;
    min-height:27px!important;
    margin:0!important;
    color:#20332c!important;
    font-size:15px!important;
    line-height:1.35!important;
    font-weight:700!important;
  }
  body .hero-filter-panel .destination-field input::placeholder{
    color:#69776f!important;
    font-size:14px!important;
    font-weight:500!important;
  }
  body .hero-filter-panel .search-submit{
    grid-column:1/-1!important;
    display:flex!important;
    align-items:center!important;
    justify-content:center!important;
    width:100%!important;
    min-width:0!important;
    min-height:64px!important;
    margin:2px 0 0!important;
    padding:12px 16px!important;
    border:0!important;
    border-radius:14px!important;
    background:var(--villa-primary)!important;
    color:#fff!important;
    font-size:17px!important;
    line-height:1.2!important;
    font-weight:900!important;
    letter-spacing:.2px!important;
    box-shadow:0 9px 20px rgba(0,0,0,.2)!important;
  }
  body .hero-filter-panel .search-submit span{
    margin-right:8px!important;
    font-size:22px!important;
  }
  body .hero-filter-panel .guest-popover{
    left:0!important;
    right:0!important;
    width:100%!important;
  }
  body .hero-copy{
    order:0!important;
    width:100%!important;
    max-width:100%!important;
    margin:0!important;
    padding:0 4px!important;
    text-align:center!important;
  }
  body .hero-filter-panel .home-calendar{grid-column:1/-1!important}
}


/* Compact mobile hero copy and vertical spacing to bring the complete search panel into view. */
@media(max-width:600px){
  body .hero-card{padding:17px 13px 20px!important;gap:12px!important}
  body .hero-copy .eyebrow{font-size:10px!important;letter-spacing:1.3px!important}
  body .hero-copy h1{margin:4px auto 6px!important;font-size:clamp(24px,6.4vw,30px)!important;line-height:1.08!important}
  body .hero-copy p{font-size:12px!important;line-height:1.35!important}
}


/* Premium search strip: mobile only, placed directly after the hero video. */
.mobile-premium-search{display:none}
@media(max-width:600px){
  .mobile-premium-search{display:block;width:100%;padding:15px 14px 22px;background:#f5f7f6}
  .mobile-premium-search__form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:9px;width:100%;padding:13px;background:#fff;border:1px solid #e7ece9;border-radius:18px;box-shadow:0 12px 32px rgba(20,43,34,.10)}
  .mobile-premium-search__form label{display:flex;flex-direction:column;justify-content:center;gap:6px;min-width:0;min-height:63px;padding:9px 11px;border:1px solid #edf0ee;border-radius:11px;background:#fbfcfb}
  .mobile-premium-search__form label span{color:#7c8982;font-size:9px;font-weight:800;letter-spacing:1px}
  .mobile-premium-search__form input,.mobile-premium-search__form select{width:100%;min-width:0;padding:0;border:0;outline:0;background:transparent;color:#263b31;font:600 12px/1.35 var(--villa-font-body);appearance:auto}
  .mobile-premium-search__form input::placeholder{color:#9aa59f;font-weight:500}
  .mobile-premium-search__form .mobile-premium-search__destination{grid-column:1/-1;min-height:68px;position:relative;z-index:8}
  .mobile-premium-search__form button{grid-column:2;min-width:0;display:flex;align-items:center;justify-content:center;gap:9px;min-height:48px;margin-top:1px;padding:0 18px;border:0;border-radius:11px;background:var(--villa-primary);color:#fff;font:800 13px/1 var(--villa-font-body);letter-spacing:.1px;box-shadow:0 7px 16px color-mix(in srgb,var(--villa-primary) 22%,transparent)}
  .mobile-premium-search__form button span{font-size:19px;line-height:1}
}

</style></head><body>
<?php \App\Core\ThemeManager::renderPartial('partials/header.php', get_defined_vars()); ?>
<main class="home-sections">
<?php $hasCustomHomepageLayout=is_array($pageLayout??null)&&!empty($pageLayout['rows']); ?>
<?php if($hasCustomHomepageLayout): ?>
<?php include dirname(__DIR__).'/partials/page-builder-render.php'; ?>
<?php endif; ?>
<?php
$baseVisible=!$hasCustomHomepageLayout && ($demoHomepageEnabled??true);
$showBase=static fn(string $key): bool => $homeDesign[$key]['visible'] ?? ($baseVisible && $active($key));
?>
<?php if($showBase('hero')): ?><section class="hero" style="order:<?= $order('hero',1) ?>"><div class="hero-card" style="<?= $heroBackgroundImageUrl!==''?'background-image:url(&quot;'.e($heroBackgroundImageUrl).'&quot;)':'' ?>">
<?php if($heroYouTubeIdValue!==''): ?><iframe class="hero-video hero-video-youtube" src="https://www.youtube-nocookie.com/embed/<?= e($heroYouTubeIdValue) ?>?autoplay=1&amp;mute=1&amp;start=<?= $heroVideoStartSeconds ?>&amp;loop=<?= $heroVideoLoop?'1':'0' ?><?= $heroVideoLoop?'&amp;playlist='.e($heroYouTubeIdValue):'' ?>&amp;controls=0&amp;rel=0&amp;playsinline=1" title="Welcome background video" frameborder="0" allow="autoplay; encrypted-media; picture-in-picture" tabindex="-1" aria-hidden="true"></iframe><?php elseif($heroVideoUrl!==''): ?><video class="hero-video" <?= $heroVideoStartSeconds===0?'autoplay':'' ?> muted <?= $heroVideoLoop?'loop':'' ?> playsinline preload="metadata"<?= $heroBackgroundImageUrl!==''?' poster="'.e($heroBackgroundImageUrl).'"':'' ?>><source src="<?= e($heroVideoUrl) ?>" type="<?= str_ends_with(strtolower((string)parse_url($heroVideoUrl,PHP_URL_PATH)),'.webm')?'video/webm':'video/mp4' ?>"></video><?php endif; ?><div class="hero-shade" data-texture="<?= e($heroOverlayTexture) ?>" style="position:absolute;inset:0;z-index:1;pointer-events:none;background:rgba(<?= e($heroOverlayRgb) ?>,<?= e($heroOverlayAlpha) ?>)"></div>
<?php if($heroVideoStartSeconds>0 && $heroVideoUrl!==''): ?><script>
(function(){
 'use strict';
 const video=document.querySelector('.hero-card video.hero-video');
 if(!video)return;
 const requestedStart=<?= $heroVideoStartSeconds ?>;
 let started=false;
 const playSafely=function(){
  try{
   const result=video.play();
   if(result&&typeof result.catch==='function')result.catch(function(){});
  }catch(error){}
 };
 const seekAndPlay=function(){
  if(started||video.error||!Number.isFinite(video.duration)||video.duration<=0)return;
  started=true;
  const target=Math.min(requestedStart,Math.max(0,video.duration-0.05));
  if(target<=0||Math.abs(video.currentTime-target)<0.25){playSafely();return;}
  const resume=function(){
   video.removeEventListener('seeked',resume);
   playSafely();
  };
  video.addEventListener('seeked',resume,{once:true});
  try{
   video.currentTime=target;
  }catch(error){
   video.removeEventListener('seeked',resume);
   playSafely();
  }
 };
 const readinessEvents=['loadedmetadata','durationchange','loadeddata','canplay'];
 const onReady=function(){
  if(started)return;
  seekAndPlay();
  if(started)readinessEvents.forEach(function(name){video.removeEventListener(name,onReady)});
 };
 readinessEvents.forEach(function(name){video.addEventListener(name,onReady)});
 if(video.readyState>=1)onReady();
 // preload="metadata" is only a browser hint. Some mobile browsers do not
 // expose a finite duration until data has been requested explicitly.
 try{video.load();}catch(error){}

})();
</script><?php endif; ?>
<?php if($heroYouTubeIdValue!==''): ?><script>
(function(){
 const frame=document.querySelector('.hero-video-youtube');
 if(!frame)return;
 const box=frame.closest('.hero-card');
 if(!box)return;
 const fit=()=>{
  const rect=box.getBoundingClientRect();
  const w=rect.width,h=rect.height;
  if(!w||!h)return;
  const scale=Math.max(w/16,h/9);
  const videoWidth=Math.ceil(scale*16),videoHeight=Math.ceil(scale*9);
  // Override theme-wide iframe sizing rules: the YouTube player must cover
  // the complete hero, not inherit a content-width or intrinsic iframe size.
  frame.style.setProperty('position','absolute','important');
  frame.style.setProperty('display','block','important');
  frame.style.setProperty('width',videoWidth+'px','important');
  frame.style.setProperty('height',videoHeight+'px','important');
  frame.style.setProperty('max-width','none','important');
  frame.style.setProperty('max-height','none','important');
  frame.style.setProperty('min-width','0','important');
  frame.style.setProperty('min-height','0','important');
  frame.style.setProperty('left','50%','important');
  frame.style.setProperty('top','50%','important');
  frame.style.setProperty('right','auto','important');
  frame.style.setProperty('bottom','auto','important');
  frame.style.setProperty('margin','0','important');
  frame.style.setProperty('transform','translate(-50%,-50%)','important');
 };
 fit();
 if('ResizeObserver' in window)new ResizeObserver(fit).observe(box);
 window.addEventListener('resize',fit,{passive:true});
 window.addEventListener('orientationchange',fit,{passive:true});
})();
</script><?php endif; ?>
<div class="hero-inner"><div class="hero-copy" data-villa-overlay-text><span class="eyebrow villa-hero-eyebrow"><?= e($content['home_eyebrow']??'MAKE YOUR HOLIDAY SPECIAL') ?></span><h1><?= e($content['home_title']??'Your dream holiday awaits') ?></h1><p><?= e($content['home_subtitle']??'Sea, nature and relaxation. Discover your ideal holiday home.') ?></p></div></div>
<div class="hero-filter-panel"><form class="search search-enhanced" action="/konaklamalar" method="get" id="homeSearch">
<label class="search-field destination-field" data-location-autocomplete><span class="search-label"><span class="search-icon">⌖</span> <?= e($content['home_search_destination_label']??'Where?') ?></span><input type="text" name="city" id="homeDestination" placeholder="<?= e($content['home_search_destination_placeholder']??'Choose a city or area') ?>" aria-label="<?= e('Destination') ?>" autocomplete="off" aria-autocomplete="list" aria-controls="homeDestinationOptions"><div class="location-suggestions" id="homeDestinationOptions" role="listbox" hidden></div><input type="hidden" name="location_id" id="homeLocationId" value=""></label>
<div class="search-field date-field"><span class="search-label"><span class="search-icon">↘</span> Check-in</span><button type="button" class="date-display date-picker-trigger" id="checkInDisplay" aria-haspopup="dialog" aria-controls="homeCalendar" aria-expanded="false"><?= e($content['home_calendar_add_date']??'Add dates') ?></button><input type="hidden" name="check_in" id="homeCheckIn" min="<?= e(date('Y-m-d')) ?>" aria-label="<?= e($content['home_search_checkin_label']??'Check-in date') ?>"></div>
<div class="search-field date-field"><span class="search-label"><span class="search-icon">↗</span> Check-out</span><button type="button" class="date-display date-picker-trigger" id="checkOutDisplay" aria-haspopup="dialog" aria-controls="homeCalendar" aria-expanded="false"><?= e($content['home_calendar_add_date']??'Add dates') ?></button><input type="hidden" name="check_out" id="homeCheckOut" min="<?= e(date('Y-m-d',strtotime('+1 day'))) ?>" aria-label="<?= e($content['home_search_checkout_label']??'Check-out date') ?>"></div>
<div class="home-calendar" id="homeCalendar" role="dialog" aria-label="<?= e($content['home_search_date_range_label']??'Check-in and check-out dates') ?>" hidden>
  <div class="calendar-toolbar"><button type="button" id="calendarPrev" aria-label="<?= e($content['home_calendar_previous']??'Previous month') ?>">‹</button><strong id="calendarHeading"><?= e($content['home_calendar_choose']??'Choose dates') ?></strong><button type="button" id="calendarNext" aria-label="<?= e($content['home_calendar_next']??'Next month') ?>">›</button></div>
  <div class="calendar-months" id="calendarMonths"></div>
  <div class="calendar-footer"><span id="calendarNights"><?= e($content['home_search_date_range_label']??'Check-in and check-out dates') ?></span><div><button type="button" class="calendar-clear" id="calendarClear"><?= e($content['home_calendar_clear']??'Clear') ?></button><button type="button" class="calendar-done" id="calendarDone"><?= e($content['home_calendar_done']??'Done') ?></button></div></div>
</div>
<div class="search-field guest-field" id="guestField"><span class="search-label"><span class="search-icon">♙</span> Guests</span><button type="button" class="guest-trigger" id="guestTrigger" aria-expanded="false"><span id="guestSummary">2 adults</span><span class="guest-chevron">⌄</span></button><input type="hidden" name="guests" id="guestTotal" value="2"><div class="guest-popover" id="guestPopover" hidden>
<div class="guest-row"><span><strong>Adult</strong><small>Ages 13 and up</small></span><span class="counter"><button type="button" data-count="adults" data-step="-1" aria-label="<?= e($content['home_guest_decrease_adults']??'Decrease adults') ?>">−</button><b id="adultsCount">2</b><button type="button" data-count="adults" data-step="1" aria-label="<?= e($content['home_guest_increase_adults']??'Increase adults') ?>">+</button></span></div>
<div class="guest-row"><span><strong>Child</strong><small>Ages 2–12</small></span><span class="counter"><button type="button" data-count="children" data-step="-1" aria-label="<?= e($content['home_guest_decrease_children']??'Decrease children') ?>">−</button><b id="childrenCount">0</b><button type="button" data-count="children" data-step="1" aria-label="<?= e($content['home_guest_increase_children']??'Increase children') ?>">+</button></span></div>
<div class="guest-row"><span><strong>Infant</strong><small>Ages 0–1</small></span><span class="counter"><button type="button" data-count="infants" data-step="-1" aria-label="<?= e($content['home_guest_decrease_infants']??'Decrease infants') ?>">−</button><b id="infantsCount">0</b><button type="button" data-count="infants" data-step="1" aria-label="<?= e($content['home_guest_increase_infants']??'Increase infants') ?>">+</button></span></div>
<div class="guest-done"><button type="button" id="guestDone"><?= e($content['home_calendar_done']??'Done') ?></button></div></div></div>
<button type="submit" class="search-submit"><span>⌕</span> <?= e($content['search_button']??'Search villas') ?></button></form></div></section><?php endif; ?>

<?php if($showBase('hero')): ?>
<section class="mobile-premium-search" aria-label="Villa search" style="order:<?= $order('hero',1) ?>">
  <form class="mobile-premium-search__form" action="/konaklamalar" method="get">
    <label class="mobile-premium-search__destination" data-location-autocomplete>
      <span>DESTINATION</span>
      <input type="text" name="city" id="mobileDestination" placeholder="<?= e('Where would you like to go?') ?>" aria-label="<?= e(villa_ui('Destinasyon')) ?>" autocomplete="off" aria-autocomplete="list" aria-controls="mobileDestinationOptions">
      <div class="location-suggestions" id="mobileDestinationOptions" role="listbox" hidden></div>
      <input type="hidden" name="location_id" value="">
    </label>
    <label>
      <span>CHECK-IN</span>
      <input type="date" name="check_in" min="<?= e(date('Y-m-d')) ?>" aria-label="<?= e($content['home_search_checkin_label']??'Check-in date') ?>">
    </label>
    <label>
      <span>CHECK-OUT</span>
      <input type="date" name="check_out" min="<?= e(date('Y-m-d',strtotime('+1 day'))) ?>" aria-label="<?= e($content['home_search_checkout_label']??'Check-out date') ?>">
    </label>
    <label>
      <span>GUESTS</span>
      <select name="guests" aria-label="Number of guests">
        <option value="1">1 guest</option><option value="2" selected>2 guests</option><option value="3">3 guests</option><option value="4">4 guests</option><option value="5">5 guests</option><option value="6">6+ guests</option>
      </select>
    </label>
    <button type="submit"><span aria-hidden="true">⌕</span> <?= e($content['home_mobile_search_button']??'Find villas') ?></button>
  </form>
</section>
<?php endif; ?>


<?php if($showBase('destinations')): ?><section class="section" style="order:<?= $order('destinations',2) ?>"><div class="wrap"><div class="section-head"><div><span><?= e(villa_ui((string)($homeDesign['destinations']['home_regions_eyebrow']??'START EXPLORING'))) ?></span><h2><?= e(villa_ui((string)($homeDesign['destinations']['home_regions_title']??$homeSections['destinations']['title']??'Popular destinations'))) ?></h2><p><?= e(villa_ui((string)($homeDesign['destinations']['home_regions_subtitle']??$homeSections['destinations']['subtitle']??'Find your perfect stay in beautiful holiday destinations.'))) ?></p></div><a class="more" href="/destinasyonlar"><?= e($content['home_destinations_link']??'All destinations') ?> →</a></div><div class="destination-editorial-grid"><?php foreach(array_values($destinations) as $destinationIndex=>$d): ?><a class="destination-editorial-item" href="/konaklamalar?<?= e(http_build_query(['location_id'=>$d['location_id'],'city'=>$d['city']])) ?>"><span class="destination-editorial-index"><?= str_pad((string)($destinationIndex+1),2,'0',STR_PAD_LEFT) ?></span><span class="destination-editorial-copy" data-villa-overlay-text><strong><?= e($d['city']) ?></strong><small><?= (int)$d['property_count'] ?> stays available</small></span><span class="destination-editorial-arrow" aria-hidden="true">↗</span></a><?php endforeach; ?></div></div></section><?php endif; ?>
<?php if($homeDesign['featured']['visible'] ?? ($baseVisible && $showFeatured)): ?><section class="section home-accommodations" data-home-section="accommodations" style="order:<?= $order('featured',3) ?>"><div class="wrap"><div class="section-head"><div><span class="pill villa-eyebrow">HANDPICKED FOR YOU</span><h2><?= e($homeSections['featured']['title']??'Featured places to stay') ?></h2><p><?= e($homeSections['featured']['subtitle']??'Unique holiday homes worth discovering.') ?></p></div><a class="more" href="/konaklamalar"><?= e($content['home_properties_link']??'All properties') ?> →</a></div><div class="cards"><?php if(empty($featuredProperties)): ?><div class="empty-accommodations">No published properties yet. New listings will appear here.</div><?php else: ?><?php foreach($featuredProperties as $p):
    $img=$photo($p);
    $cardImages=array_map(static fn($v)=>preg_match('~^https?://~i',(string)$v)?(string)$v:'/'.ltrim((string)$v,'/'),$featuredCardImages[(int)$p['id']]??($img?[$img]:[]));
    if(!$cardImages&&$img)$cardImages=[$img];
    $isCardFavorite=in_array((int)$p['id'],$favoritePropertyIds??[],true);
    $filters=[];
    \App\Core\ThemeManager::renderPartial('components/cards/villa-card.php', get_defined_vars());
endforeach; ?><?php endif; ?></div></div></section><?php endif; ?>
<?php if($baseVisible && $active('styles')): ?><section class="section" style="order:<?= $order('styles',4) ?>"><div class="wrap"><div class="section-head"><div><span class="pill villa-eyebrow">CHOOSE YOUR HOLIDAY STYLE</span><h2><?= e($homeSections['styles']['title']??'What kind of holiday are you dreaming of?') ?></h2><p><?= e($homeSections['styles']['subtitle']??'Find the experience that suits you.') ?></p></div></div><div class="styles"><?php $styleNames=[(string)($content['home_style_1_title']??'Private pool retreats'),(string)($content['home_style_2_title']??'Modern luxury villas'),(string)($content['home_style_3_title']??'Family holidays'),(string)($content['home_style_4_title']??'Nature escapes')]; $styleLinks=[(string)($content['home_style_1_link']??'/konaklamalar'),(string)($content['home_style_2_link']??'/konaklamalar'),(string)($content['home_style_3_link']??'/konaklamalar'),(string)($content['home_style_4_link']??'/konaklamalar')]; foreach($styleNames as $i=>$name): ?><a class="style-card" href="<?= e($styleLinks[$i]) ?>"><img src="<?= e($stylePhotos[$i]) ?>" alt="<?= e($name) ?>" loading="lazy"><strong><?= e($name) ?> ↗</strong></a><?php endforeach; ?></div></div></section><?php endif; ?>
<?php if($baseVisible && $active('gallery')): ?><section class="section" style="order:<?= $order('gallery',5) ?>;background:#f7f5ef"><div class="wrap"><div class="section-head"><div><span class="pill villa-eyebrow">HOLIDAY INSPIRATION</span><h2><?= e($homeSections['gallery']['title']??'Ready to imagine your next escape?') ?></h2><p><?= e($homeSections['gallery']['subtitle']??'Wake up to beautiful views and enjoy sunsets from your terrace.') ?></p></div></div><div class="gallery-strip"><img src="<?= e($galleryPhotos[0]) ?>" alt="Private pool villa" loading="lazy"><img src="<?= e($galleryPhotos[1]) ?>" alt="Modern holiday home" loading="lazy"><img src="<?= e($galleryPhotos[2]) ?>" alt="Villa interior" loading="lazy"><img src="<?= e($galleryPhotos[3]) ?>" alt="Holiday villa" loading="lazy"></div></div></section><?php endif; ?>
<?php if(($mode??'single_vendor')==='multi_vendor' && $showBase('hosts')): ?><section class="wrap" style="order:<?= $order('hosts',6) ?>"><div class="host-banner" style="background-image:linear-gradient(90deg,#153b35dd,#153b3530),url(<?= e(json_encode($hostBannerImage,JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP)) ?>)"><div class="host-copy" data-villa-overlay-text><h2><?= e($homeSections['hosts']['title']??'Share your villa with travelers') ?></h2><p><?= e($homeSections['hosts']['subtitle']??'List your property, manage availability and welcome new guests.') ?></p><a href="/vendor/register"><?= e($content['home_host_cta']??'Become a host') ?> ↗</a></div></div></section><?php endif; ?>
</main>

<script>(function(){document.querySelectorAll('[data-card-gallery]').forEach(function(card){var data=card.querySelector('.card-gallery-data'),image=card.querySelector('.card-gallery-image'),count=card.querySelector('.card-gallery-count');if(!data||!image)return;var images=[];try{images=JSON.parse(data.textContent||'[]')}catch(e){}var index=0;card.querySelectorAll('[data-gallery-step]').forEach(function(button){button.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();if(images.length<2)return;index=(index+Number(button.dataset.galleryStep)+images.length)%images.length;image.src=images[index];if(count)count.textContent=(index+1)+' / '+images.length})})})})();</script><script>(function(){document.querySelectorAll('[data-location-autocomplete]').forEach(function(field){var input=field.querySelector('input[name="city"]'),list=field.querySelector('.location-suggestions'),selected=field.querySelector('input[name="location_id"]'),form=input&&input.closest('form');if(!input||!list||!selected)return;var timer=null,items=[],active=-1,seq=0;function close(){list.hidden=true;list.innerHTML='';active=-1;input.setAttribute('aria-expanded','false')}function choose(item){input.value=item.label;selected.value=String(item.id);close();input.setCustomValidity('')}function render(rows){items=Array.isArray(rows)?rows:[];list.innerHTML='';active=-1;items.forEach(function(item,i){var row=document.createElement('div');row.className='location-suggestion';row.id=list.id+'-'+i;row.setAttribute('role','option');row.setAttribute('aria-selected','false');row.textContent=item.label;row.addEventListener('pointerdown',function(e){e.preventDefault();choose(item)});list.appendChild(row)});list.hidden=items.length===0;input.setAttribute('aria-expanded',items.length?'true':'false')}function search(q,id){fetch('<?= e(app_url('/locations/search')) ?>?q='+encodeURIComponent(q),{credentials:'same-origin',headers:{'Accept':'application/json'}}).then(function(r){if(!r.ok)throw new Error('HTTP '+r.status);return r.json()}).then(function(rows){if(id!==seq)return;render(rows)}).catch(function(){if(id===seq)close()})}input.addEventListener('input',function(){selected.value='';input.setCustomValidity('');var q=input.value.trim(),id=++seq;clearTimeout(timer);if(!q){items=[];close();return}timer=setTimeout(function(){search(q,id)},180)});input.addEventListener('focus',function(){if(items.length&&input.value.trim())render(items)});input.addEventListener('keydown',function(e){if(list.hidden||!items.length)return;if(e.key==='ArrowDown'||e.key==='ArrowUp'){e.preventDefault();active=(active+(e.key==='ArrowDown'?1:-1)+items.length)%items.length;Array.from(list.children).forEach(function(el,i){el.classList.toggle('is-active',i===active);el.setAttribute('aria-selected',i===active?'true':'false')});list.children[active].scrollIntoView({block:'nearest'});input.setAttribute('aria-activedescendant',list.children[active].id)}else if(e.key==='Enter'&&active>=0){e.preventDefault();choose(items[active])}else if(e.key==='Escape')close()});input.addEventListener('change',function(){var item=items.find(function(x){return x.label===input.value});if(item)choose(item)});input.addEventListener('blur',function(){setTimeout(function(){close()},140)});if(form)form.addEventListener('submit',function(e){if(input.value.trim()&&!selected.value){e.preventDefault();input.setCustomValidity('Please select a location from the suggestions.');input.reportValidity();input.focus()}})})})();</script><script>(function(){var ci=document.getElementById('homeCheckIn'),co=document.getElementById('homeCheckOut'),ciText=document.getElementById('checkInDisplay'),coText=document.getElementById('checkOutDisplay'),panel=document.getElementById('homeCalendar'),months=document.getElementById('calendarMonths'),heading=document.getElementById('calendarHeading'),nightsLabel=document.getElementById('calendarNights'),prev=document.getElementById('calendarPrev'),next=document.getElementById('calendarNext'),done=document.getElementById('calendarDone'),clear=document.getElementById('calendarClear'),form=document.getElementById('homeSearch');if(!ci||!co||!panel||!months)return;var today=new Date();today.setHours(0,0,0,0);var view=new Date(today.getFullYear(),today.getMonth(),1),selecting='check_in';var fmt=new Intl.DateTimeFormat('en-US',{day:'numeric',month:'long',year:'numeric'}),monthFmt=new Intl.DateTimeFormat('en-US',{month:'long',year:'numeric'});function parse(v){if(!v)return null;var p=v.split('-');return new Date(Number(p[0]),Number(p[1])-1,Number(p[2]))}function key(d){return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')}function pretty(v){var d=parse(v);return d?fmt.format(d):<?= json_encode((string)($content['home_calendar_add_date']??'Add dates'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>}function addDays(d,n){var x=new Date(d);x.setDate(x.getDate()+n);return x}function sync(){ciText.textContent=ci.value?pretty(ci.value):<?= json_encode((string)($content['home_calendar_add_date']??'Add dates'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;coText.textContent=co.value?pretty(co.value):<?= json_encode((string)($content['home_calendar_add_date']??'Add dates'), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>;var a=parse(ci.value),b=parse(co.value);if(a&&b){var count=Math.round((b-a)/86400000);nightsLabel.textContent=count+' nights';}else if(a){nightsLabel.textContent='Now choose a check-out date';}else{nightsLabel.textContent='<?= e($content['home_search_date_range_label']??'Check-in and check-out dates') ?>';}}function monthMarkup(year,month){var first=new Date(year,month,1),offset=(first.getDay()+6)%7,total=new Date(year,month+1,0).getDate(),html='<section class="calendar-month"><h3>'+monthFmt.format(first)+'</h3><div class="calendar-weekdays"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div><div class="calendar-days">';for(var i=0;i<offset;i++)html+='<span class="calendar-empty"></span>';for(var day=1;day<=total;day++){var d=new Date(year,month,day),v=key(d),past=d<today,selected=v===ci.value||v===co.value,inRange=ci.value&&co.value&&v>ci.value&&v<co.value;var cls='calendar-day'+(past?' is-disabled':'')+(selected?' is-selected':'')+(inRange?' is-in-range':'')+(v===ci.value?' is-start':'')+(v===co.value?' is-end':'');html+='<button type="button" class="'+cls+'" data-date="'+v+'"'+(past?' disabled':'')+' aria-label="'+fmt.format(d)+'">'+day+'</button>';}return html+'</div></section>';}function render(){var second=new Date(view.getFullYear(),view.getMonth()+1,1);heading.textContent=monthFmt.format(view)+' · '+monthFmt.format(second);months.innerHTML=monthMarkup(view.getFullYear(),view.getMonth())+monthMarkup(second.getFullYear(),second.getMonth());months.querySelectorAll('[data-date]').forEach(function(btn){btn.addEventListener('click',function(){choose(btn.dataset.date);});});prev.disabled=view.getFullYear()===today.getFullYear()&&view.getMonth()===today.getMonth();sync();}function choose(v){if(selecting==='check_in'||!ci.value||(co.value&&v<=ci.value)){ci.value=v;co.value='';selecting='check_out';}else{co.value=v;selecting='check_in';}var a=parse(ci.value);co.min=a?key(addDays(a,1)):key(addDays(today,1));sync();render();if(ci.value&&co.value){ciText.setAttribute('aria-expanded','false');coText.setAttribute('aria-expanded','false');}}function position(anchor){var r=anchor.getBoundingClientRect(),width=Math.min(680,window.innerWidth-24),left=Math.max(12,Math.min(r.left+(r.width/2)-(width/2),window.innerWidth-width-12)),height=panel.getBoundingClientRect().height,spaceBelow=window.innerHeight-r.bottom-12,spaceAbove=r.top-12,top;if(spaceBelow>=height||spaceBelow>=spaceAbove){top=r.bottom+10;top=Math.min(top,window.innerHeight-height-12);}else{top=Math.max(12,r.top-height-10);}panel.style.left=left+'px';panel.style.top=top+'px';panel.style.width=width+'px';panel.style.maxHeight='calc(100vh - 24px)';panel.style.overflowY='auto';}function open(which){selecting=which;var chosen=which==='check_out'&&ci.value?parse(ci.value):parse(ci.value)||today;view=new Date(chosen.getFullYear(),chosen.getMonth(),1);if(panel.parentElement!==document.body)document.body.appendChild(panel);panel.hidden=false;ciText.setAttribute('aria-expanded','true');coText.setAttribute('aria-expanded','true');render();position(which==='check_in'?ciText:coText);}function close(){panel.hidden=true;ciText.setAttribute('aria-expanded','false');coText.setAttribute('aria-expanded','false');}ciText.addEventListener('click',function(){if(panel.hidden)open('check_in');else open('check_in');});coText.addEventListener('click',function(){if(panel.hidden)open('check_out');else open('check_out');});prev.addEventListener('click',function(){view=new Date(view.getFullYear(),view.getMonth()-1,1);render();});next.addEventListener('click',function(){view=new Date(view.getFullYear(),view.getMonth()+1,1);render();});done.addEventListener('click',close);clear.addEventListener('click',function(){ci.value='';co.value='';co.min=key(addDays(today,1));selecting='check_in';sync();render();});document.addEventListener('click',function(e){if(!panel.hidden&&!panel.contains(e.target)&&!ciText.contains(e.target)&&!coText.contains(e.target))close();});document.addEventListener('keydown',function(e){if(e.key==='Escape')close();});window.addEventListener('resize',function(){if(!panel.hidden)position(selecting==='check_in'?ciText:coText);});window.addEventListener('scroll',function(){if(!panel.hidden)position(selecting==='check_in'?ciText:coText)},true);if(form)form.addEventListener('submit',function(e){if(ci.value&&co.value&&co.value<=ci.value){e.preventDefault();open('check_out');}});co.min=key(addDays(parse(ci.value)||today,1));sync();render();})();</script><script>(function(){var field=document.getElementById('guestField'),trigger=document.getElementById('guestTrigger'),panel=document.getElementById('guestPopover'),hidden=document.getElementById('guestTotal'),summary=document.getElementById('guestSummary'),done=document.getElementById('guestDone');if(!field||!trigger||!panel)return;var counts={adults:2,children:0,infants:0};function render(){Object.keys(counts).forEach(function(k){document.getElementById(k+'Count').textContent=counts[k];panel.querySelector('[data-count="'+k+'"][data-step="-1"]').disabled=counts[k]<(k==='adults'?1:0)});var total=counts.adults+counts.children+counts.infants;hidden.value=total;var parts=[];if(counts.adults)parts.push(counts.adults+' adults');if(counts.children)parts.push(counts.children+' children');if(counts.infants)parts.push(counts.infants+' infants');summary.textContent=parts.join(', ')}function positionPanel(){var r=trigger.getBoundingClientRect(),w=Math.min(Math.max(r.width,280),window.innerWidth-24),left=Math.max(12,Math.min(r.left,window.innerWidth-w-12));panel.style.position='fixed';panel.style.top=Math.max(12,Math.min(r.bottom+8,window.innerHeight-250))+'px';panel.style.left=left+'px';panel.style.right='auto';panel.style.bottom='auto';panel.style.width=w+'px';panel.style.maxHeight='min(360px, calc(100vh - 24px))';panel.style.overflowY='auto';panel.style.zIndex='99999'}function open(){document.body.appendChild(panel);panel.hidden=false;positionPanel();trigger.setAttribute('aria-expanded','true')}function close(){panel.hidden=true;trigger.setAttribute('aria-expanded','false');field.appendChild(panel);panel.removeAttribute('style')}trigger.addEventListener('click',function(){if(panel.hidden)open();else close()});panel.querySelectorAll('[data-count]').forEach(function(btn){btn.addEventListener('click',function(){var k=btn.dataset.count,total=counts.adults+counts.children+counts.infants;if(btn.dataset.step==='1'&&total>=16)return;counts[k]=Math.max(k==='adults'?1:0,Math.min(16,counts[k]+Number(btn.dataset.step)));render();positionPanel()})});done.addEventListener('click',close);document.addEventListener('click',function(e){if(!field.contains(e.target)&&!panel.contains(e.target))close()});document.addEventListener('keydown',function(e){if(e.key==='Escape')close()});window.addEventListener('resize',function(){if(!panel.hidden)positionPanel()});window.addEventListener('scroll',function(){if(!panel.hidden)positionPanel()},true);render()})();</script><?php \App\Core\ThemeManager::renderPartial('partials/compare-bar.php', get_defined_vars()); ?><?php  ?>
</body></html>