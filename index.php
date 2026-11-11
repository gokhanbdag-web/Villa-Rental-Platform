<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Installer;
use App\Core\Migrator;
use App\Core\Router;
use App\Core\Translator;

if (!Installer::isInstalled()) {
    header('Location: ' . app_url('/install/'));
    exit;
}

try {
    Migrator::run(Database::connection());
} catch (Throwable $e) {
    error_log('Database migration failed: ' . $e->getMessage());
    http_response_code(503);
    exit('Veritabanı güncellemesi tamamlanamadı. Lütfen sistem yöneticisine başvurun.');
}

$locale = 'tr';
try {
    $tzStmt = Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $tzStmt->execute(['app.timezone']);
    $timezone = (string)($tzStmt->fetchColumn() ?: 'Europe/Istanbul');
    if (in_array($timezone, DateTimeZone::listIdentifiers(), true)) date_default_timezone_set($timezone);
    $stmt = Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute(['app.locale']);
    $locale = (string)($stmt->fetchColumn() ?: 'tr');
    Translator::setLocale($locale);
} catch (Throwable $e) {
    error_log('Locale initialization failed: ' . $e->getMessage());
}

$router = new Router();
require APP_ROOT . '/routes/web.php';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = app_base_path();
if ($basePath !== '' && ($requestPath === $basePath || str_starts_with($requestPath, $basePath . '/'))) {
    $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
}
$platformMode = 'single_vendor';
try {
    $modeStmt = Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $modeStmt->execute(['app.mode']);
    $platformMode = (string)($modeStmt->fetchColumn() ?: 'single_vendor');
} catch (Throwable $e) {
    error_log('Platform mode lookup failed: ' . $e->getMessage());
}

// Vendor-only endpoints must not be reachable in single-vendor installations,
// even if an old vendor account or a bookmarked URL exists.
if ($platformMode !== 'multi_vendor' &&
    ($requestPath === '/vendor' || str_starts_with($requestPath, '/vendor/') ||
     $requestPath === '/admin/vendors' || str_starts_with($requestPath, '/admin/vendors/'))) {
    http_response_code(404);
    exit('Bu özellik mevcut platform modelinde etkin değil.');
}

$isAdminPage = $requestPath === '/admin' || str_starts_with($requestPath, '/admin/');
if ($isAdminPage) {
    ob_start(static function (string $html) use ($requestPath, $platformMode): string {
        $user = Auth::user();
        $username = e((string)($user['username'] ?? 'Yönetici'));
        // Keep the same three navigation groups on the first load and every AJAX page.
        $groups = [
            'Yönetim' => [
                '/admin' => 'Genel Bakış',
                '/admin/properties' => 'Konaklamalar',
                '/admin/bookings' => 'Rezervasyonlar',
                '/admin/users' => 'Kullanıcılar',
                '/admin/reviews' => 'Değerlendirmeler',
                '/admin/locations' => 'Lokasyonlar',
                '/admin/categories' => 'Kategoriler',
                '/admin/amenities' => 'Villa Özellikleri',
            ],
            'Web Sitesi' => [
                '/admin/pages' => 'İçerik Sayfaları',
                '/admin/navigation' => 'Menü Yönetimi',
                '/admin/content' => 'Site İçerikleri',
            ],
            'Sistem' => [
                '/admin/settings' => 'Ayarlar',
                '/admin/translations' => 'Çeviriler',
            ],
        ];
        if ($platformMode === 'multi_vendor') {
            $groups['Yönetim']['/admin/vendors'] = 'Ev Sahipleri';
        }

        // Dashboard and sub-pages are reduced to content only; the shell below is the single admin layout.
        $bodyContent = '';
        if (preg_match('/<body\b[^>]*>(.*?)<\/body>/is', $html, $bodyMatch)) {
            $bodyContent = $bodyMatch[1];
        }
        // Preserve page-specific CSS from the original document head.
        if (preg_match('/<head\b[^>]*>(.*?)<\/head>/is', $html, $headMatch)) {
            preg_match_all('/<style\b[^>]*>.*?<\/style>/is', $headMatch[1], $styleMatches);
            $bodyContent = implode("\n", $styleMatches[0] ?? []) . $bodyContent;
        }
        if ($requestPath === '/admin' || $requestPath === '/admin/') {
            if (preg_match('/<main\b[^>]*>(.*?)<\/main>/is', $bodyContent, $mainMatch)) {
                $bodyContent = $mainMatch[1];
            }
        }
        // Remove page-level headers/navigation left by older standalone admin views.
        $bodyContent = preg_replace('/<header\b[^>]*>.*?<\/header>/is', '', $bodyContent, 1) ?? $bodyContent;

        // A single icon map keeps the navigation identical on every admin screen.
        $icons = [
            '/admin' => '▦',
            '/admin/properties' => '⌂',
            '/admin/locations' => '⌖',
            '/admin/categories' => '▤',
            '/admin/amenities' => '✧',
            '/admin/bookings' => '▣',
            '/admin/users' => '♙',
            '/admin/reviews' => '☆',
            '/admin/pages' => '▧',
            '/admin/navigation' => '☷',
            '/admin/content' => '✎',
            '/admin/settings' => '⚙',
            '/admin/translations' => '文',
            '/admin/vendors' => '♧',
        ];
        $nav = '';
        foreach ($groups as $groupLabel => $groupLinks) {
            $nav .= '<div class="vr-admin-nav-title">' . e($groupLabel) . '</div>';
            foreach ($groupLinks as $url => $label) {
                $active = ($url === '/admin') ? ($requestPath === '/admin' || $requestPath === '/admin/') : ($requestPath === $url || str_starts_with($requestPath, $url . '/'));
                $icon = $icons[$url] ?? '•';
                $nav .= '<a class="vr-admin-link' . ($active ? ' is-active' : '') . '" href="' . e(app_url($url)) . '" data-admin-link>' .
                    '<span class="vr-admin-icon" aria-hidden="true">' . e($icon) . '</span><span class="vr-admin-link-label">' . e($label) . '</span></a>';
            }
        }
        $adminRootUrl = json_encode(app_url('/admin'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        $shell = '<style>
            html,body{min-height:100%;margin:0!important;background:#f5f6f8!important;color:#202631;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif!important}
            body{overflow-x:hidden}.vr-admin-top{height:68px;box-sizing:border-box;background:#fff;border-bottom:1px solid #e8ebef;display:flex;align-items:center;gap:16px;padding:0 26px;position:sticky;top:0;z-index:1002}
            .vr-admin-brand{font-size:17px;font-weight:800;letter-spacing:-.3px;color:#20352b;text-decoration:none;white-space:nowrap}
            .vr-admin-user{font-size:13px;color:#657080}.vr-admin-site-link{margin-left:auto;display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:0 13px;border:1px solid #dfe7e2;border-radius:9px;background:#f7faf8;color:#176b50;text-decoration:none;font-size:12px;font-weight:700;white-space:nowrap}.vr-admin-site-link:hover{background:#edf5f1}.vr-admin-logout{display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:0 13px;border:1px solid #f0d9d6;border-radius:9px;background:#fff8f7;color:#a43e35;font-size:12px;font-weight:750;cursor:pointer}.vr-admin-logout:hover{background:#fff0ee}.vr-admin-burger{display:none;width:40px;height:40px;border:1px solid #e5e8ed;border-radius:9px;background:#fff;color:#263238;align-items:center;justify-content:center;font-size:22px;cursor:pointer}
            .vr-admin-layout{display:grid;grid-template-columns:252px minmax(0,1fr);min-height:calc(100vh - 68px)}
            .vr-admin-sidebar{background:#fff;border-right:1px solid #e8ebef;padding:22px 15px;display:flex;flex-direction:column;gap:5px;box-sizing:border-box}
            .vr-admin-nav-title{padding:5px 12px 13px;color:#9aa2ad;font-size:10px;font-weight:800;letter-spacing:1.1px;text-transform:uppercase}
            .vr-admin-link{display:flex;align-items:center;gap:11px;min-height:42px;box-sizing:border-box;padding:10px 13px;color:#566170;text-decoration:none;border-radius:9px;font-size:13px;font-weight:600;transition:background .15s,color .15s}.vr-admin-icon{flex:0 0 20px;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:17px;line-height:1;color:#829188}.vr-admin-link.is-active .vr-admin-icon{color:#176b50}.vr-admin-link-label{min-width:0}
            .vr-admin-link:hover{background:#f4f6f8;color:#176b50}.vr-admin-link.is-active{background:#edf5f1;color:#176b50;font-weight:750}
            .vr-admin-content{min-width:0;padding:30px 32px;box-sizing:border-box;overflow-x:hidden}.vr-admin-content *{box-sizing:border-box;max-width:100%}.vr-admin-content .wrap{max-width:none!important;margin:0!important;padding:0!important}
            .vr-admin-content h1{margin-top:0}.vr-admin-content table{width:100%;max-width:100%;border-collapse:collapse}
            .vr-admin-content img,.vr-admin-content input,.vr-admin-content select,.vr-admin-content textarea{max-width:100%}
            .vr-admin-content input,.vr-admin-content select,.vr-admin-content textarea{min-width:0}
            .vr-admin-content button,.vr-admin-content a{overflow-wrap:anywhere}
            .vr-admin-content form{min-width:0;max-width:100%}
            .vr-admin-content .panel,.vr-admin-content article,.vr-admin-content section{min-width:0;max-width:100%}
            .vr-admin-overlay{display:none}
            @media(max-width:680px){
              .vr-admin-content{padding:15px 12px}
              .vr-admin-content table,.vr-admin-content table tbody{display:block;width:100%}
              .vr-admin-content table thead{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0}
              .vr-admin-content table tbody tr{display:block;width:100%;margin:0 0 13px;padding:9px 12px;background:#fff;border:1px solid #e7eaf0;border-radius:12px;box-shadow:0 2px 8px rgba(20,30,40,.035)}
              .vr-admin-content table tbody td{display:grid;grid-template-columns:minmax(88px,34%) minmax(0,1fr);align-items:start;gap:10px;width:100%;padding:9px 2px;border:0;border-bottom:1px solid #f0f1f3;text-align:left;overflow-wrap:anywhere;word-break:break-word}
              .vr-admin-content table tbody td:last-child{border-bottom:0}
              .vr-admin-content table tbody td:before{content:attr(data-label);font-size:11px;font-weight:750;color:#89919d;line-height:1.45}
              .vr-admin-content table tbody td[data-label=""]{grid-template-columns:1fr}
              .vr-admin-content table tbody td[data-label=""]:before{display:none}
              .vr-admin-content table tbody td[colspan]{display:block;text-align:center;padding:15px 4px}
              .vr-admin-content table tbody td[colspan]:before{display:none}
              .vr-admin-content table tbody td form{display:flex!important;flex-wrap:wrap;align-items:center;gap:7px}
              .vr-admin-content table tbody td form input,.vr-admin-content table tbody td form select{width:auto;min-width:0;flex:1 1 100px}
              .vr-admin-content table tbody td form button{flex:0 0 auto}
              .vr-admin-content input:not([type="checkbox"]):not([type="radio"]),.vr-admin-content select,.vr-admin-content textarea{width:100%;min-width:0}
              .vr-admin-content button{max-width:100%;white-space:normal}
              .vr-admin-content .grid{grid-template-columns:minmax(0,1fr)!important}
              .vr-admin-content [style*="grid-template-columns"]{grid-template-columns:minmax(0,1fr)!important}
            }
            @media(max-width:900px){.vr-admin-top{height:62px;padding:0 16px}.vr-admin-burger{display:flex}.vr-admin-layout{display:block;min-height:calc(100vh - 62px)}.vr-admin-sidebar{position:fixed;z-index:1004;left:0;top:0;bottom:0;width:278px;max-width:84vw;overflow-y:auto;transform:translateX(-105%);transition:transform .22s ease;box-shadow:12px 0 35px rgba(20,30,40,.12);padding:22px 15px}.vr-admin-layout.menu-open .vr-admin-sidebar{transform:translateX(0)}.vr-admin-layout.menu-open .vr-admin-overlay{display:block;position:fixed;inset:0;background:rgba(15,23,32,.42);z-index:1003}.vr-admin-content{padding:22px 20px}}
            @media(max-width:520px){.vr-admin-brand{font-size:15px}.vr-admin-site-link{min-height:33px;padding:0 9px;font-size:11px}.vr-admin-user{display:none}.vr-admin-content{padding:16px 13px}}
        </style><header class="vr-admin-top"><button class="vr-admin-burger" type="button" aria-label="Menüyü aç" aria-expanded="false" id="vrAdminBurger">☰</button><a class="vr-admin-brand" href="' . e(app_url('/admin')) . '" data-admin-link>VillaStay <span style="font-weight:500;color:#87918c">/ Yönetim</span></a><a class="vr-admin-site-link" href="' . e(app_url('/')) . '">Siteye dön ↗</a><form method="post" action="' . e(app_url('/logout')) . '" style="margin:0"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '"><button class="vr-admin-logout" type="submit">Çıkış Yap</button></form><span class="vr-admin-user">' . $username . '</span></header><div class="vr-admin-layout" id="vrAdminLayout"><aside class="vr-admin-sidebar" aria-label="Yönetim menüsü">' . $nav . '</aside><div class="vr-admin-overlay" id="vrAdminOverlay"></div><main class="vr-admin-content" id="vrAdminContent">' . $bodyContent . '</main></div>
        <script>
        (function(){
            if(window.__villaAdminShellReady)return;window.__villaAdminShellReady=true;
            const layout=document.getElementById("vrAdminLayout"),burger=document.getElementById("vrAdminBurger"),overlay=document.getElementById("vrAdminOverlay"),content=document.getElementById("vrAdminContent"),adminRootPath=new URL(' . $adminRootUrl . ',location.href).pathname.replace(/\\/$/,"");
            function closeMenu(){layout&&layout.classList.remove("menu-open");burger&&burger.setAttribute("aria-expanded","false")}
            function enhanceResponsiveTables(root){
                (root||content).querySelectorAll("table").forEach(function(table){
                    const headers=Array.from(table.querySelectorAll("thead th")).map(th=>th.textContent.trim());
                    if(!headers.length)return;
                    table.classList.add("vr-responsive-table");
                    table.querySelectorAll("tbody tr").forEach(function(row){
                        Array.from(row.cells).forEach(function(cell,index){
                            if(cell.hasAttribute("colspan"))return;
                            if(!cell.hasAttribute("data-label"))cell.setAttribute("data-label",headers[index]||"");
                        });
                    });
                });
            }
            enhanceResponsiveTables(content);
            burger&&burger.addEventListener("click",function(){const open=layout.classList.toggle("menu-open");burger.setAttribute("aria-expanded",open?"true":"false")});
            overlay&&overlay.addEventListener("click",closeMenu);
            function activate(url){document.querySelectorAll("[data-admin-link]").forEach(a=>{const u=new URL(a.href,location.href);a.classList.toggle("is-active",u.pathname===url|| (url!=="/admin"&&u.pathname!=="/admin"&&url.startsWith(u.pathname+"/")));});}
            async function navigate(url,push=true){
                closeMenu();content.style.opacity=".58";
                try{
                    const response=await fetch(url,{method:"GET",credentials:"same-origin",cache:"no-store",headers:{"X-Requested-With":"XMLHttpRequest","Accept":"text/html"}});
                    if(!response.ok)throw new Error("Sunucu yanıtı: HTTP "+response.status);
                    const source=await response.text();
                    const doc=new DOMParser().parseFromString(source,"text/html");
                    let next=doc.querySelector("#vrAdminContent");
                    // Accept a plain admin document too; keep the existing shell/sidebar alive.
                    if(!next){
                        const main=doc.querySelector("main");
                        const body=doc.body;
                        const holder=document.createElement("div");
                        holder.innerHTML=main?main.innerHTML:(body?body.innerHTML:"");
                        holder.querySelectorAll("header,nav,aside").forEach(el=>el.remove());
                        next=holder;
                    }
                    if(!next||!next.innerHTML.trim())throw new Error("Sayfa içeriği alınamadı. Oturum veya sunucu yanıtını kontrol edin.");
                    content.innerHTML=next.innerHTML;
                    enhanceResponsiveTables(content);
                    if(push)history.pushState({admin:true}, "",url);
                    activate(new URL(url,location.href).pathname);
                    if(doc.title)document.title=doc.title;
                    content.querySelectorAll("script").forEach(old=>{const script=document.createElement("script");Array.from(old.attributes).forEach(a=>script.setAttribute(a.name,a.value));script.textContent=old.textContent;old.replaceWith(script)});
                    window.scrollTo(0,0);
                }catch(error){
                    console.error("Admin AJAX navigation failed:",error);
                    let notice=content.querySelector(".vr-admin-navigation-error");
                    if(!notice){notice=document.createElement("div");notice.className="vr-admin-navigation-error";notice.style.cssText="margin:0 0 18px;padding:13px 15px;border:1px solid #f0c8c3;border-radius:10px;background:#fff5f4;color:#963d35;font:13px/1.5 system-ui";content.prepend(notice)}
                    notice.textContent="Sayfa yenilenmeden açılamadı: "+error.message+" — Lütfen tekrar deneyin.";
                }finally{content.style.opacity="1"}
            }
            // Intercept every internal admin link, including dashboard quick actions,
            // rather than only sidebar links. This prevents mixed full reload / AJAX states.
            window.addEventListener("click",function(event){
                const origin=event.target instanceof Element?event.target:event.target&&event.target.parentElement;
                const link=origin&&origin.closest("a[href]");if(!link)return;
                if(event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey)return;
                if(link.target&&link.target!=="_self")return;
                const target=new URL(link.href,location.href);
                if(target.origin!==location.origin)return;
                if(!(target.pathname===adminRootPath||target.pathname.startsWith(adminRootPath+"/")))return;
                event.preventDefault();event.stopPropagation();event.stopImmediatePropagation();
                navigate(target.href,true);
            },true);
            window.addEventListener("popstate",function(){if(location.pathname.startsWith((document.querySelector(".vr-admin-brand")?.pathname||"/admin").replace(/\/$/,"")))navigate(location.href,false);});
            activate(location.pathname);
        })();
        </script>';
        return preg_replace_callback('/<body\b[^>]*>.*?<\/body>/is', static function (array $matches) use ($shell): string {
            return '<body>' . $shell . '</body>';
        }, $html, 1) ?? $html;
    });
}
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $requestPath);
