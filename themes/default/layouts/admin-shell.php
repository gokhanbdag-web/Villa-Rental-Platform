<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Translator;
use App\Core\ThemeManager;

return static function (string $html, string $requestPath, string $platformMode): string {

        $user = Auth::user();
        $username = e((string)($user['username'] ?? 'Yönetici'));
        $adminPrimaryColor = e(ThemeManager::designSettings()['primary'] ?? 'var(--villa-admin-primary)');
        // Keep the same three navigation groups on the first load and every AJAX page.
        $groups = [
            'Yönetim' => [
                '/admin' => 'Genel Bakış',
                '/admin/properties' => 'Konaklamalar',
                '/admin/bookings' => 'Rezervasyonlar',
                '/admin/finance' => 'Finans ve Ödemeler',
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
                '/admin/blog' => 'Blog Yönetimi',
                '/admin/newsletter' => 'E-posta Aboneleri',
                '/admin/messages' => 'İletişim Mesajları',
                '/admin/theme-design' => 'Tema Ayarları',
                '/admin/themes' => Translator::get('admin.themes.title'),
            ],
            'Sistem' => [
                '/admin/settings' => 'Ayarlar',
                '/admin/updates' => 'Güncelleme Merkezi',
                '/admin/header-settings' => 'Üst Bilgi Ayarları',
                '/admin/translations' => 'Çeviriler',
            ],
        ];
        if ($platformMode === 'multi_vendor') {
            $groups['Yönetim']['/admin/vendors'] = 'Ev Sahipleri';
        }

        // Dashboard and sub-pages are reduced to content only; the shell below is the single admin layout.
        $bodyContent = '';
        $hasDocumentBody = preg_match('/<body\\b[^>]*>(.*?)<\\/body>/is', $html, $bodyMatch) === 1;
        if ($hasDocumentBody) {
            $bodyContent = $bodyMatch[1];
        } else {
            // Fragment-based admin pages must still render inside the shared shell.
            $bodyContent = $html;
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

        // Bind every green CSS accent in admin pages to the theme's configured primary.
        // Light greens remain tints of the primary; darker greens remain deeper primary shades.
        $mapAdminGreenStyles = static function (string $markup) use ($adminPrimaryColor): string {
            $primary = strtolower(html_entity_decode($adminPrimaryColor, ENT_QUOTES, 'UTF-8'));
            $mapCss = static function (string $css) use ($primary): string {
                return preg_replace_callback('/#(?:[0-9a-f]{3}|[0-9a-f]{6})\\b/i', static function (array $match) use ($primary): string {
                    $hex = strtolower($match[0]);
                    if (strlen($hex) === 4) $hex = '#' . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
                    if ($hex === $primary) return 'var(--villa-admin-primary)';
                    $red = hexdec(substr($hex, 1, 2)) / 255;
                    $green = hexdec(substr($hex, 3, 2)) / 255;
                    $blue = hexdec(substr($hex, 5, 2)) / 255;
                    $max = max($red, $green, $blue);
                    $min = min($red, $green, $blue);
                    $delta = $max - $min;
                    $lightness = ($max + $min) / 2;
                    if ($delta < 0.0001) return $match[0];
                    $saturation = $delta / (1 - abs(2 * $lightness - 1));
                    if ($max === $red) $hue = 60 * fmod((($green - $blue) / $delta), 6);
                    elseif ($max === $green) $hue = 60 * ((($blue - $red) / $delta) + 2);
                    else $hue = 60 * ((($red - $green) / $delta) + 4);
                    if ($hue < 0) $hue += 360;
                    if ($hue < 72 || $hue > 170 || $saturation < 0.10) return $match[0];
                    if ($lightness >= 0.78) {
                        $weight = max(10, min(35, (int)round((1 - $lightness) * 180)));
                        return 'color-mix(in srgb, var(--villa-admin-primary) ' . $weight . '%, white)';
                    }
                    if ($lightness >= 0.55) return 'color-mix(in srgb, var(--villa-admin-primary) 55%, white)';
                    if ($lightness >= 0.38) return 'color-mix(in srgb, var(--villa-admin-primary) 82%, white)';
                    if ($lightness >= 0.25) return 'var(--villa-admin-primary)';
                    return 'color-mix(in srgb, var(--villa-admin-primary) 82%, black)';
                }, $css) ?? $css;
            };
            $markup = preg_replace_callback('/(<style\\b[^>]*>)(.*?)(<\\/style>)/is', static function (array $match) use ($mapCss): string {
                return $match[1] . $mapCss($match[2]) . $match[3];
            }, $markup) ?? $markup;
            $markup = preg_replace_callback('~(\\bstyle\\s*=\\s*")([^"]*)(")~is', static function (array $match) use ($mapCss): string {
                return $match[1] . $mapCss($match[2]) . $match[3];
            }, $markup) ?? $markup;
            $markup = preg_replace_callback("~(\\bstyle\\s*=\\s*')([^']*)(')~is", static function (array $match) use ($mapCss): string {
                return $match[1] . $mapCss($match[2]) . $match[3];
            }, $markup) ?? $markup;
            $markup = preg_replace_callback('~\\b(fill|stroke)=(")(#[0-9a-f]{3}(?:[0-9a-f]{3})?)(")~i', static function (array $match) use ($mapCss): string {
                return $match[1] . $match[2] . $mapCss($match[3]) . $match[4];
            }, $markup) ?? $markup;
            $markup = preg_replace_callback("~\\b(fill|stroke)=(')(#[0-9a-f]{3}(?:[0-9a-f]{3})?)(')~i", static function (array $match) use ($mapCss): string {
                return $match[1] . $match[2] . $mapCss($match[3]) . $match[4];
            }, $markup) ?? $markup;
            return $markup;
        };
        $bodyContent = $mapAdminGreenStyles($bodyContent);

        // A single icon map keeps the navigation identical on every admin screen.
        $icons = [
            '/admin' => '▦',
            '/admin/properties' => '⌂',
            '/admin/locations' => '⌖',
            '/admin/categories' => '▤',
            '/admin/amenities' => '✧',
            '/admin/bookings' => '▣',
            '/admin/finance' => '₺',
            '/admin/users' => '♙',
            '/admin/reviews' => '☆',
            '/admin/pages' => '▧',
            '/admin/navigation' => '☷',
            '/admin/content' => '✎',
            '/admin/blog' => '✎',
            '/admin/newsletter' => '✉',
            '/admin/messages' => '☏',
            '/admin/theme-design' => '◉',
            '/admin/themes' => '◈',
            '/admin/settings' => '⚙',
            '/admin/updates' => '↻',
            '/admin/header-settings' => '☷',
            '/admin/translations' => '文',
            '/admin/vendors' => '♧',
        ];
        $nav = '';
        foreach ($groups as $groupLabel => $groupLinks) {
            $nav .= '<div class="vr-admin-nav-title">' . e($groupLabel) . '</div>';
            foreach ($groupLinks as $url => $label) {
                $active = ($url === '/admin') ? ($requestPath === '/admin' || $requestPath === '/admin/') : ($requestPath === $url || str_starts_with($requestPath, $url . '/'));
                $icon = $icons[$url] ?? '•';
                $nav .= '<a class="vr-admin-link' . ($active ? ' is-active' : '') . '" href="' . e(app_url($url)) . '" data-admin-link>'
                    . '<span class="vr-admin-icon" aria-hidden="true">' . e($icon) . '</span><span class="vr-admin-link-label">' . e($label) . '</span></a>';
            }
        }
        $adminRootUrl = json_encode(app_url('/admin'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        $shell = '<style>:root{--villa-admin-primary:' . $adminPrimaryColor . ';--villa-admin-primary-hover:color-mix(in srgb,var(--villa-admin-primary) 82%,#000)}
            html,body{min-height:100%;margin:0!important;background:#f5f6f8!important;color:#202631;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif!important}
            body{overflow-x:hidden}.vr-admin-top{height:68px;box-sizing:border-box;background:#fff;border-bottom:1px solid #e8ebef;display:flex;align-items:center;gap:16px;padding:0 26px;position:sticky;top:0;z-index:1002}
            .vr-admin-brand{font-size:17px;font-weight:800;letter-spacing:-.3px;color:var(--villa-admin-primary);text-decoration:none;white-space:nowrap}
            .vr-admin-user{font-size:13px;color:#657080}.vr-admin-site-link{margin-left:auto;display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:0 13px;border:1px solid #dfe7e2;border-radius:9px;background:color-mix(in srgb,var(--villa-admin-primary) 4%,white);color:var(--villa-admin-primary);text-decoration:none;font-size:12px;font-weight:700;white-space:nowrap}.vr-admin-site-link:hover{background:color-mix(in srgb,var(--villa-admin-primary) 10%,white)}.vr-admin-logout{display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:0 13px;border:1px solid #f0d9d6;border-radius:9px;background:#fff8f7;color:#a43e35;font-size:12px;font-weight:750;cursor:pointer}.vr-admin-logout:hover{background:#fff0ee}.vr-admin-burger{display:none;width:40px;height:40px;border:1px solid #e5e8ed;border-radius:9px;background:#fff;color:#263238;align-items:center;justify-content:center;font-size:22px;cursor:pointer}
            .vr-admin-layout{display:grid;grid-template-columns:252px minmax(0,1fr);min-height:calc(100vh - 68px)}
            .vr-admin-sidebar{background:#fff;border-right:1px solid #e8ebef;padding:22px 15px;display:flex;flex-direction:column;gap:5px;box-sizing:border-box}
            .vr-admin-nav-title{padding:5px 12px 13px;color:#9aa2ad;font-size:10px;font-weight:800;letter-spacing:1.1px;text-transform:uppercase}
            .vr-admin-link{display:flex;align-items:center;gap:11px;min-height:42px;box-sizing:border-box;padding:10px 13px;color:#566170;text-decoration:none;border-radius:9px;font-size:13px;font-weight:600;transition:background .15s,color .15s}.vr-admin-icon{flex:0 0 20px;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:17px;line-height:1;color:var(--villa-admin-primary)}.vr-admin-link.is-active .vr-admin-icon{color:var(--villa-admin-primary)}.vr-admin-link-label{min-width:0}
            .vr-admin-link:hover{background:#f4f6f8;color:var(--villa-admin-primary)}.vr-admin-link.is-active{background:color-mix(in srgb,var(--villa-admin-primary) 10%,white);color:var(--villa-admin-primary);font-weight:750}
            .vr-admin-content{min-width:0;padding:30px 32px;box-sizing:border-box;overflow-x:hidden}.vr-admin-content *{box-sizing:border-box;max-width:100%}.vr-admin-content .wrap{max-width:none!important;margin:0!important;padding:0!important}
            .vr-admin-content h1{margin-top:0}.vr-admin-content table{width:100%;max-width:100%;border-collapse:collapse}
            .vr-admin-content img,.vr-admin-content input,.vr-admin-content select,.vr-admin-content textarea{max-width:100%}
            /* Shared, visible file-upload treatment for every native admin uploader. */
            .vr-admin-content input[type="file"]:not(.footer-logo-file-input):not(#propertyImages){display:block;width:100%;min-height:58px;padding:9px;border:1px dashed color-mix(in srgb,var(--villa-admin-primary) 35%,#d5dbe2);border-radius:12px;background:color-mix(in srgb,var(--villa-admin-primary) 3%,white);color:var(--villa-admin-primary);font:600 12px/1.45 Inter,system-ui,sans-serif;cursor:pointer}
            .vr-admin-content input[type="file"]:not(.footer-logo-file-input):not(#propertyImages)::file-selector-button{display:inline-flex;align-items:center;justify-content:center;min-height:38px;margin:0 12px 0 0;padding:0 16px;border:0;border-radius:8px;background:var(--villa-admin-primary);color:#fff;font:750 12px Inter,system-ui,sans-serif;cursor:pointer;transition:background .15s ease}
            .vr-admin-content input[type="file"]:not(.footer-logo-file-input):not(#propertyImages)::file-selector-button:hover{background:var(--villa-admin-primary-hover)}
            .vr-admin-upload-preview{display:flex;align-items:center;gap:14px;min-height:76px;margin-top:10px;padding:12px;border:1px solid #e1e9e3;border-radius:11px;background:#fff}
            .vr-admin-upload-preview[hidden]{display:none}
            .vr-admin-upload-preview img,.vr-admin-upload-preview video{display:block;width:auto;max-width:min(100%,220px);max-height:118px;object-fit:contain;border-radius:7px;background:#f1f4f2}
            .vr-admin-upload-preview video{width:min(100%,220px)}
            .vr-admin-upload-preview-copy{display:grid;gap:4px;min-width:0;color:#35483c;font-size:12px;overflow-wrap:anywhere}
            .vr-admin-upload-preview-copy strong{font-size:12px}
            .vr-admin-upload-preview-copy span{color:#78867d;font-size:11px;font-weight:500}
            @media(max-width:560px){.vr-admin-content input[type="file"]:not(.footer-logo-file-input):not(#propertyImages){padding:8px;font-size:11px}.vr-admin-content input[type="file"]:not(.footer-logo-file-input):not(#propertyImages)::file-selector-button{min-height:36px;padding:0 11px;margin-right:8px}.vr-admin-upload-preview{align-items:flex-start;flex-direction:column}}
            .vr-admin-rich-color-toolbar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0 0 7px;padding:8px 10px;border:1px solid #dce5df;border-radius:9px;background:#f8faf8}.vr-admin-rich-color-toolbar label{display:inline-flex;align-items:center;gap:7px;margin:0;color:var(--villa-admin-primary);font-size:12px;font-weight:700}.vr-admin-rich-color-toolbar input[type=color]{width:34px;height:30px;padding:2px;border:1px solid #dce5df;border-radius:6px;background:#fff;cursor:pointer}.vr-admin-rich-color-toolbar button{min-height:32px;padding:0 12px;border:1px solid var(--villa-admin-primary);border-radius:7px;background:var(--villa-admin-primary);color:#fff;font-size:12px;font-weight:750;cursor:pointer}.vr-admin-rich-color-toolbar button:hover{background:var(--villa-admin-primary-hover)}
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
              /* Footer widget editor is an intentional horizontal layout, including on phones. */
              .vr-admin-content .theme-design [data-design-tab-panel="footer"] .footer-layout-preview .footer-widget-row.footer-widget-four{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;grid-auto-flow:row!important}
              .vr-admin-content .theme-design [data-design-tab-panel="footer"] .footer-layout-preview .footer-widget-row.footer-widget-two{display:grid!important;grid-template-columns:minmax(0,7fr) minmax(0,5fr)!important;grid-auto-flow:row!important}
              .vr-admin-content .theme-design [data-design-tab-panel="footer"] .footer-layout-preview .footer-widget-row>.footer-widget-tile{grid-column:auto!important;grid-row:auto!important;width:100%!important;min-width:0!important}
            }
            @media(max-width:900px){.vr-admin-top{height:62px;padding:0 16px}.vr-admin-burger{display:flex}.vr-admin-layout{display:block;min-height:calc(100vh - 62px)}.vr-admin-sidebar{position:fixed;z-index:1004;left:0;top:0;bottom:0;width:278px;max-width:84vw;overflow-y:auto;transform:translateX(-105%);transition:transform .22s ease;box-shadow:12px 0 35px rgba(20,30,40,.12);padding:22px 15px}.vr-admin-layout.menu-open .vr-admin-sidebar{transform:translateX(0)}.vr-admin-layout.menu-open .vr-admin-overlay{display:block;position:fixed;inset:0;background:rgba(15,23,32,.42);z-index:1003}.vr-admin-content{padding:22px 20px}}
            @media(max-width:520px){.vr-admin-brand{font-size:15px}.vr-admin-site-link{min-height:33px;padding:0 9px;font-size:11px}.vr-admin-user{display:none}.vr-admin-content{padding:16px 13px}}
        </style><header class="vr-admin-top"><button class="vr-admin-burger" type="button" aria-label="Menüyü aç" aria-expanded="false" id="vrAdminBurger">☰</button><a class="vr-admin-brand" href="' . e(app_url('/admin')) . '" data-admin-link>VillaStay <span style="font-weight:500;color:#87918c">/ Yönetim</span></a><a class="vr-admin-site-link" href="' . e(app_url('/')) . '">Siteye dön ↗</a><form method="post" action="' . e(app_url('/logout')) . '" style="margin:0"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '"><button class="vr-admin-logout" type="submit">Çıkış Yap</button></form><span class="vr-admin-user">' . $username . '</span></header><div class="vr-admin-layout" id="vrAdminLayout"><aside class="vr-admin-sidebar" aria-label="Yönetim menüsü">' . $nav . '</aside><div class="vr-admin-overlay" id="vrAdminOverlay"></div><main class="vr-admin-content" id="vrAdminContent">' . $bodyContent . '<style id="villa-admin-primary-sync">.vr-admin-content .design-save,.vr-admin-content .theme-tab.is-active,.vr-admin-content .footer-logo-choose-button,.vr-admin-content .theme-file-button{background:var(--villa-admin-primary)!important;border-color:var(--villa-admin-primary)!important;color:#fff!important}.vr-admin-content .footer-widget-tile:hover,.vr-admin-content .theme-file-drop:hover,.vr-admin-content .footer-rich-toolbar button:hover{border-color:var(--villa-admin-primary)!important}</style></main></div>
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
            function enhanceRichTextColors(root){
                (root||content).querySelectorAll("[contenteditable=\"true\"]:not([data-admin-color-ready])").forEach(function(editor){
                    if(editor.hasAttribute("data-footer-rich-editor"))return;
                    editor.setAttribute("data-admin-color-ready","1");
                    const toolbar=document.createElement("div");
                    toolbar.className="vr-admin-rich-color-toolbar";
                    toolbar.setAttribute("role","group");
                    toolbar.setAttribute("aria-label","Metin biçimlendirme");
                    const label=document.createElement("label");
                    label.appendChild(document.createTextNode("Yazı rengi"));
                    const picker=document.createElement("input");
                    picker.type="color";picker.value="#26352e";picker.setAttribute("aria-label","Seçili metnin yazı rengi");
                    label.appendChild(picker);
                    const apply=document.createElement("button");
                    apply.type="button";apply.textContent="Rengi uygula";
                    toolbar.append(label,apply);
                    editor.parentNode.insertBefore(toolbar,editor);
                    let savedRange=null;
                    function remember(){const selection=window.getSelection();if(selection&&selection.rangeCount&&editor.contains(selection.anchorNode)&&editor.contains(selection.focusNode))savedRange=selection.getRangeAt(0).cloneRange();}
                    editor.addEventListener("mouseup",remember);editor.addEventListener("keyup",remember);
                    picker.addEventListener("mousedown",function(event){event.preventDefault();});
                    apply.addEventListener("mousedown",function(event){event.preventDefault();});
                    apply.addEventListener("click",function(){
                        if(!savedRange||!editor.contains(savedRange.commonAncestorContainer)){window.alert("Önce rengini değiştirmek istediğiniz metni seçin.");return;}
                        const selection=window.getSelection();if(!selection)return;
                        selection.removeAllRanges();selection.addRange(savedRange);
                        if(selection.isCollapsed){window.alert("Önce rengini değiştirmek istediğiniz metni seçin.");return;}
                        const range=selection.getRangeAt(0),span=document.createElement("span");
                        span.style.color=/^#[0-9a-f]{6}$/i.test(picker.value)?picker.value:"#26352e";
                        span.appendChild(range.extractContents());range.insertNode(span);
                        selection.removeAllRanges();const next=document.createRange();next.selectNodeContents(span);selection.addRange(next);savedRange=next.cloneRange();
                        editor.dispatchEvent(new Event("input",{bubbles:true}));
                    });
                });
            }
            enhanceRichTextColors(content);
            function enhanceFileUploaders(root){
                (root||content).querySelectorAll("input[type=file]:not([data-vr-upload-enhanced])").forEach(function(input){
                    if(input.classList.contains("footer-logo-file-input")||input.classList.contains("theme-file-input")||input.id==="propertyImages"||input.closest(".drop-zone"))return;
                    input.setAttribute("data-vr-upload-enhanced","1");
                    const preview=document.createElement("div");
                    preview.className="vr-admin-upload-preview";
                    preview.hidden=true;
                    preview.setAttribute("aria-live","polite");
                    input.insertAdjacentElement("afterend",preview);
                    let objectUrl="";
                    input.addEventListener("change",function(){
                        if(objectUrl){URL.revokeObjectURL(objectUrl);objectUrl=""}
                        preview.replaceChildren();
                        const file=input.files&&input.files[0];
                        if(!file){preview.hidden=true;return}
                        preview.hidden=false;
                        const type=(file.type||"").toLowerCase();
                        if(type.indexOf("image/")===0||type.indexOf("video/")===0){
                            objectUrl=URL.createObjectURL(file);
                            const media=document.createElement(type.indexOf("video/")===0?"video":"img");
                            media.src=objectUrl;
                            if(media.tagName==="VIDEO"){media.controls=true;media.muted=true;media.playsInline=true;media.preload="metadata"}
                            else media.alt="Seçilen görselin önizlemesi";
                            preview.appendChild(media);
                        }
                        const copy=document.createElement("div");
                        copy.className="vr-admin-upload-preview-copy";
                        const name=document.createElement("strong");
                        name.textContent=file.name;
                        const details=document.createElement("span");
                        details.textContent=(file.size/1024/1024).toFixed(2)+" MB · "+(file.type||"Dosya türü belirlenemedi");
                        copy.append(name,details);
                        preview.appendChild(copy);
                    });
                });
            }
            enhanceFileUploaders(content);
            burger&&burger.addEventListener("click",function(){const open=layout.classList.toggle("menu-open");burger.setAttribute("aria-expanded",open?"true":"false")});
            overlay&&overlay.addEventListener("click",closeMenu);
            function activate(url){document.querySelectorAll("[data-admin-link]").forEach(a=>{const u=new URL(a.href,location.href);a.classList.toggle("is-active",u.pathname===url|| (url!=="/admin"&&u.pathname!=="/admin"&&url.startsWith(u.pathname+"/")));});}
            // Re-apply the server-side green-to-theme-primary mapping to AJAX fragments.
            // The initial page is transformed in PHP, but subsequent admin navigation replaces
            // the content directly in the browser and must receive the same treatment.
            function mapAdminGreenMarkup(markup){
                function mapCss(css){
                    return css.replace(/#(?:[0-9a-f]{3}|[0-9a-f]{6})\\b/gi,function(match){
                        let hex=match.toLowerCase();
                        if(hex.length===4)hex='#'+hex[1]+hex[1]+hex[2]+hex[2]+hex[3]+hex[3];
                        const r=parseInt(hex.slice(1,3),16)/255,g=parseInt(hex.slice(3,5),16)/255,b=parseInt(hex.slice(5,7),16)/255;
                        const max=Math.max(r,g,b),min=Math.min(r,g,b),delta=max-min,light=(max+min)/2;
                        if(delta<0.0001)return match;
                        let hue;
                        if(max===r)hue=60*(((g-b)/delta)%6);
                        else if(max===g)hue=60*((b-r)/delta+2);
                        else hue=60*((r-g)/delta+4);
                        if(hue<0)hue+=360;
                        const saturation=delta/(1-Math.abs(2*light-1));
                        if(hue<72||hue>170||saturation<0.10)return match;
                        if(light>=0.78){const weight=Math.max(10,Math.min(35,Math.round((1-light)*180)));return 'color-mix(in srgb,var(--villa-admin-primary) '+weight+'%,white)';}
                        if(light>=0.55)return 'color-mix(in srgb,var(--villa-admin-primary) 55%,white)';
                        if(light>=0.38)return 'color-mix(in srgb,var(--villa-admin-primary) 82%,white)';
                        if(light>=0.25)return 'var(--villa-admin-primary)';
                        return 'color-mix(in srgb,var(--villa-admin-primary) 82%,black)';
                    });
                }
                markup=markup.replace(/(<style\\b[^>]*>)([\\s\\S]*?)(<\\/style>)/gi,function(all,open,css,close){return open+mapCss(css)+close;});
                markup=markup.replace(/(\\bstyle\\s*=\\s*")([^"]*)(")/gi,function(all,open,css,close){return open+mapCss(css)+close;});
                markup=markup.replace(/(\\bstyle\\s*=\\s*')([^']*)(')/gi,function(all,open,css,close){return open+mapCss(css)+close;});
                markup=markup.replace(/(\\b(?:fill|stroke)\\s*=\\s*")([^"]*)(")/gi,function(all,open,color,close){return open+mapCss(color)+close;});
                markup=markup.replace(/(\\b(?:fill|stroke)\\s*=\\s*')([^']*)(')/gi,function(all,open,color,close){return open+mapCss(color)+close;});
                return markup;
            }
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
                    content.innerHTML=mapAdminGreenMarkup(next.innerHTML);
                    enhanceResponsiveTables(content);
                    enhanceRichTextColors(content);
                    enhanceFileUploaders(content);
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
        // Apply the same green-to-primary mapping to the shared shell CSS.
        $primaryPlaceholder = '__VILLA_ADMIN_PRIMARY_VALUE__';
        $shell = str_replace($adminPrimaryColor, $primaryPlaceholder, $shell);
        $shell = $mapAdminGreenStyles($shell);
        $shell = str_replace($primaryPlaceholder, $adminPrimaryColor, $shell);
        if ($hasDocumentBody) {
            return preg_replace_callback('/<body\\b[^>]*>.*?<\\/body>/is', static function (array $matches) use ($shell): string {
                return '<body>' . $shell . '</body>';
            }, $html, 1) ?? $html;
        }

        // The original page was a fragment, so create a valid document around it.
        return '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Yönetim Paneli</title></head><body>' . $shell . '</body></html>';
};
