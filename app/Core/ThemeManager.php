<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;
use ZipArchive;

final class ThemeManager
{
    private static function root(): string { return APP_ROOT . '/themes'; }

    public static function installed(): array
    {
        $manifestPath = self::root() . '/default/theme.json';
        if (!is_file($manifestPath)) return [];
        $manifest = json_decode((string)file_get_contents($manifestPath), true);
        if (!is_array($manifest) || ($manifest['slug'] ?? '') !== 'default' || empty($manifest['name'])) return [];
        return ['default' => [
            'slug' => 'default',
            'name' => (string)$manifest['name'],
            'version' => (string)($manifest['version'] ?? '1.0.0'),
            'description' => (string)($manifest['description'] ?? ''),
            'author' => (string)($manifest['author'] ?? ''),
            'builtin' => true,
            'manifest' => $manifest,
        ]];
    }

    public static function activeSlug(?PDO $pdo = null): string
    {
        return 'default';
    }

    public static function activate(string $slug): void
    {
        if (!isset(self::installed()[$slug])) throw new RuntimeException('Tema paketi bulunamadı veya geçersiz.');
        $stmt = Database::connection()->prepare('INSERT INTO settings(setting_key,setting_value,autoload) VALUES(?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
        $stmt->execute(['theme.active', $slug]);
        unset($_SESSION['villa_theme_preview']);
    }

    public static function preview(string $slug): void
    {
        if (!isset(self::installed()[$slug])) throw new RuntimeException('Tema paketi bulunamadı.');
        $_SESSION['villa_theme_preview'] = $slug;
    }

    public static function clearPreview(): void { unset($_SESSION['villa_theme_preview']); }

    public static function customSettings(string $slug): array
    {
        if (!isset(self::installed()[$slug])) return [];
        try {
            $stmt = Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
            $stmt->execute(['theme.custom.' . $slug]);
            $value = json_decode((string)($stmt->fetchColumn() ?: '{}'), true);
            return is_array($value) ? $value : [];
        } catch (\Throwable $e) {
            error_log('Theme settings read failed: ' . $e->getMessage());
            return [];
        }
    }

    public static function saveCustomSettings(string $slug, array $settings): void
    {
        if (!isset(self::installed()[$slug])) throw new RuntimeException('Tema paketi bulunamadı.');
        $clean = [];
        foreach (['primary', 'background', 'surface', 'text', 'accent', 'button_color'] as $key) {
            $value = (string)($settings[$key] ?? '');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) throw new RuntimeException('Renk değerlerinden biri geçersiz.');
            $clean[$key] = strtolower($value);
        }
        $radius = filter_var($settings['radius'] ?? 18, FILTER_VALIDATE_INT);
        if ($radius === false || $radius < 0 || $radius > 32) throw new RuntimeException('Kart köşe yuvarlaklığı 0 ile 32 arasında olmalıdır.');
        $clean['radius'] = $radius;
        $stmt = Database::connection()->prepare('INSERT INTO settings(setting_key,setting_value,autoload) VALUES(?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
        $stmt->execute(['theme.custom.' . $slug, json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
    }

    public static function socialSettings(): array
    {
        $defaults = [];
        foreach (['instagram','facebook','youtube','tiktok','x','whatsapp','pinterest','telegram','linkedin'] as $platform) {
            $defaults[$platform] = ['url'=>'','family'=>'brand','size'=>20,'active'=>false];
        }
        $defaults['_address'] = ['text'=>'','active'=>false];
        $defaults['_call_center'] = ['title'=>'Çağrı Merkezi','phone'=>''];
        $defaults['_license'] = ['title'=>'','number'=>''];
        try {
            $stmt = Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
            $stmt->execute(['theme.social_icons']);
            $saved = json_decode((string)($stmt->fetchColumn() ?: '{}'), true);
            if (is_array($saved)) {
                foreach ($defaults as $key=>$item) {
                    if (isset($saved[$key]) && is_array($saved[$key])) {
                        $defaults[$key] = array_merge($item, $saved[$key]);
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('Shared header settings read failed: ' . $e->getMessage());
        }
        return $defaults;
    }

    public static function saveSocialSettings(array $input): void
    {
        $clean = [];
        foreach (['instagram','facebook','youtube','tiktok','x','whatsapp','pinterest','telegram','linkedin'] as $platform) {
            $row = is_array($input[$platform] ?? null) ? $input[$platform] : [];
            $url = trim((string)($row['url'] ?? ''));
            if ($url !== '' && (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http','https'], true))) {
                throw new RuntimeException(strtoupper($platform) . ' bağlantısı geçerli bir http/https adresi olmalıdır.');
            }
            $family = (string)($row['family'] ?? 'brand');
            if (!in_array($family, ['brand','circle'], true)) $family = 'brand';
            $size = filter_var($row['size'] ?? 20, FILTER_VALIDATE_INT);
            if ($size === false || $size < 14 || $size > 36) throw new RuntimeException('Sosyal medya ikon boyutu 14 ile 36 px arasında olmalıdır.');
            $clean[$platform] = ['url'=>$url,'family'=>$family,'size'=>$size];
        }
        $address = is_array($input['_address'] ?? null) ? $input['_address'] : [];
        $addressText = trim((string)($address['text'] ?? ''));
        $addressLength = preg_match_all('/./us', $addressText, $addressCharacters);
        if ($addressLength === false || $addressLength > 180) throw new RuntimeException('Header adres bilgisi en fazla 180 karakter olabilir.');
        $clean['_address'] = ['text'=>$addressText];
        $callCenter = is_array($input['_call_center'] ?? null) ? $input['_call_center'] : [];
        $callCenterTitle = trim((string)($callCenter['title'] ?? ''));
        $titleLength = preg_match_all('/./us', $callCenterTitle, $titleCharacters);
        if ($titleLength === false || $titleLength > 60) throw new RuntimeException('Üst şerit başlığı en fazla 60 karakter olabilir.');
        $phone = trim((string)($callCenter['phone'] ?? ''));
        $phoneLength = preg_match_all('/./us', $phone, $phoneCharacters);
        if ($phoneLength === false || $phoneLength > 40) throw new RuntimeException('Çağrı merkezi telefon numarası en fazla 40 karakter olabilir.');
        if ($phone !== '' && !preg_match('/^[0-9+()\\s.\\-]+$/u', $phone)) throw new RuntimeException('Çağrı merkezi telefon numarası geçersiz karakter içeriyor.');
        $clean['_call_center'] = ['title'=>$callCenterTitle,'phone'=>$phone];
        $license = is_array($input['_license'] ?? null) ? $input['_license'] : [];
        $licenseTitle = trim((string)($license['title'] ?? ''));
        $licenseNumber = trim((string)($license['number'] ?? ''));
        $licenseTitleLength = preg_match_all('/./us', $licenseTitle, $licenseTitleCharacters);
        $licenseNumberLength = preg_match_all('/./us', $licenseNumber, $licenseNumberCharacters);
        if ($licenseTitleLength === false || $licenseTitleLength > 60) throw new RuntimeException('Lisans başlığı en fazla 60 karakter olabilir.');
        if ($licenseNumberLength === false || $licenseNumberLength > 40) throw new RuntimeException('Lisans numarası en fazla 40 karakter olabilir.');
        $clean['_license'] = ['title'=>$licenseTitle,'number'=>$licenseNumber];
        $stmt = Database::connection()->prepare('INSERT INTO settings(setting_key,setting_value,autoload) VALUES(?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
        $stmt->execute(['theme.social_icons', json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
    }

    private static function renderSocialIcon(string $platform, string $family): string
    {
        $marks = [
            'instagram'=>'<rect x="3" y="3" width="18" height="18" rx="6"/><circle cx="12" cy="12" r="4"/><circle cx="18" cy="6" r="1" fill="currentColor" stroke="none"/>',
            'facebook'=>'<path d="M14 8h3V4h-3c-3 0-5 2-5 5v2H6v4h3v5h4v-5h3l1-4h-4V9c0-.7.3-1 1-1z" fill="currentColor" stroke="none"/>',
            'youtube'=>'<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3z" fill="currentColor" stroke="none"/>',
            'tiktok'=>'<path d="M14 3v11a4 4 0 1 1-4-4" /><path d="M14 3c1 3 3 4 6 4"/>',
            'x'=>'<path d="M4 4l16 16M20 4 4 20"/>',
            'whatsapp'=>'<path d="M5 19l1-4a8 8 0 1 1 3 3z"/><path d="M9 9c1 3 3 5 6 6l1-2-2-1-1 1c-1-1-2-2-2-3l1-1-1-2z" fill="currentColor" stroke="none"/>',
            'pinterest'=>'<circle cx="12" cy="12" r="9"/><path d="m10 18 3-11m-2 6c2 2 5 0 5-3 0-3-5-4-7-1-2 3 0 5 2 5" />',
            'telegram'=>'<path d="m3 11 18-7-5 17-4-6-4-3z"/><path d="m8 12 8-5-4 8"/>',
            'linkedin'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M8 10v7m0-10v.1M12 17v-7m0 3c0-3 5-3 5 0v4"/>'
        ];
        $shape = $marks[$platform] ?? '';
        $stroke = $family === 'circle' ? 'stroke="currentColor" stroke-width="1.8" fill="none" stroke-linecap="round" stroke-linejoin="round"' : 'stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"';
        return '<svg viewBox="0 0 24 24" aria-hidden="true" '.$stroke.'>'.$shape.'</svg>';
    }

    public static function render(string $view, array $variables): void
    {
        $slug = 'default';
        $renderSlug = 'default';
        $themeDir = self::root() . '/default';
        $manifest = self::installed()['default']['manifest'] ?? [];
        $map = $manifest['templates'] ?? [];
        $relative = isset($map[$view]) && is_string($map[$view]) ? $map[$view] : '';
        $template = '';
        if ($relative !== '' && !str_contains($relative, '..') && str_starts_with($relative, 'templates/')) {
            $candidate = $themeDir . '/' . $relative;
            $real = realpath($candidate);
            $base = realpath($themeDir);
            if ($real && $base && str_starts_with($real, $base . DIRECTORY_SEPARATOR) && is_file($real)) $template = $real;
        }
        if ($template === '') $template = APP_ROOT . '/views/site/' . basename($view);
        if (!is_file($template)) throw new RuntimeException('Tema şablonu bulunamadı: ' . $view);

        extract($variables, EXTR_SKIP);
        ob_start();
        require $template;
        $html = (string)ob_get_clean();

        $socialSettings = self::socialSettings();
        $socialNames = ['instagram'=>'Instagram','facebook'=>'Facebook','youtube'=>'YouTube','tiktok'=>'TikTok','x'=>'X','whatsapp'=>'WhatsApp','pinterest'=>'Pinterest','telegram'=>'Telegram','linkedin'=>'LinkedIn'];
        $socialItems = '';
        foreach ($socialNames as $platform=>$label) {
            $item = $socialSettings[$platform] ?? [];
            $url = trim((string)($item['url'] ?? ''));
            if ($url === '') continue;
            $size = max(14, min(36, (int)($item['size'] ?? 20)));
            $family = (string)($item['family'] ?? 'brand');
            $socialItems .= '<a class="villa-social-link villa-social-link--'.e($family).'" href="'.e($url).'" target="_blank" rel="noopener noreferrer" aria-label="'.e($label).'" title="'.e($label).'" style="--social-icon-size:'.$size.'px">'.self::renderSocialIcon($platform,$family).'</a>';
        }
        $addressSettings = $socialSettings['_address'] ?? ['text'=>'','active'=>false];
        $addressText = trim((string)($addressSettings['text'] ?? ''));
        $addressMarkup = ($addressText !== '')
            ? '<div class="villa-social-address">'.e($addressText).'</div>'
            : '';
        $callCenterSettings = $socialSettings['_call_center'] ?? ['title'=>'','phone'=>''];
        $callCenterTitle = trim((string)($callCenterSettings['title'] ?? ''));
        $callCenterPhone = trim((string)($callCenterSettings['phone'] ?? ''));
        $callCenterDigits = preg_replace('/[^0-9+]/', '', $callCenterPhone) ?? '';
        $callCenterTitleMarkup = $callCenterTitle !== '' ? '<span class="villa-social-call-label">'.e($callCenterTitle).'</span>' : '';
        $callCenterPhoneMarkup = $callCenterPhone !== '' && $callCenterDigits !== '' ? '<a href="tel:'.e($callCenterDigits).'">'.e($callCenterPhone).'</a>' : '';
        $callCenterMarkup = ($callCenterTitleMarkup !== '' || $callCenterPhoneMarkup !== '')
            ? '<div class="villa-social-call-center">'.$callCenterTitleMarkup.$callCenterPhoneMarkup.'</div>'
            : '';
        $licenseSettings = $socialSettings['_license'] ?? ['title'=>'','number'=>''];
        $licenseTitle = trim((string)($licenseSettings['title'] ?? ''));
        $licenseNumber = trim((string)($licenseSettings['number'] ?? ''));
        $licenseTitleMarkup = $licenseTitle !== '' ? '<span class="villa-social-license-title">'.e($licenseTitle).'</span>' : '';
        $licenseNumberMarkup = $licenseNumber !== '' ? '<span class="villa-social-license-number">'.e($licenseNumber).'</span>' : '';
        $licenseSeparator = ($licenseTitleMarkup !== '' && $licenseNumberMarkup !== '') ? '<span class="villa-social-license-separator">:</span>' : '';
        $licenseMarkup = ($licenseTitleMarkup !== '' || $licenseNumberMarkup !== '')
            ? '<div class="villa-social-license">'.$licenseTitleMarkup.$licenseSeparator.$licenseNumberMarkup.'</div>'
            : '';
        if (($socialItems !== '' || $addressMarkup !== '' || $callCenterMarkup !== '' || $licenseMarkup !== '') && stripos($html, 'villa-social-bar') === false) {
            $socialBar = '<div class="villa-social-bar"><div class="villa-social-inner"><div class="villa-social-left">'.$socialItems.'</div>'.$addressMarkup.$callCenterMarkup.($licenseMarkup !== '' ? $licenseMarkup : '<div class="villa-social-right"></div>').'</div></div>';
            if (preg_match('~<div class="med-topbar"~i', $html)) $html = preg_replace('~<div class="med-topbar"~i', $socialBar.'<div class="med-topbar"', $html, 1) ?? $html;
            else $html = preg_replace('~<header\\b~i', $socialBar.'<header', $html, 1) ?? $html;
        }

        $css = $themeDir . '/style.css';
        if (is_file($css)) {
            $tag = '<link rel="stylesheet" href="' . e(app_url('/theme-assets/' . rawurlencode($renderSlug) . '/style.css')) . '?v=' . rawurlencode((string)filemtime($css)) . '">';
            $custom = $isSharedDefaultTheme ? [] : self::customSettings($slug);
            if ($custom !== []) {
                $primary = e((string)($custom['primary'] ?? '#176b50'));
                $background = e((string)($custom['background'] ?? '#f7f8f5'));
                $surface = e((string)($custom['surface'] ?? '#ffffff'));
                $text = e((string)($custom['text'] ?? '#26352e'));
                $accent = e((string)($custom['accent'] ?? '#c5a56b'));
                $buttonColor = e((string)($custom['button_color'] ?? $custom['primary'] ?? '#176b50'));
                $radius = max(0, min(32, (int)($custom['radius'] ?? 18)));
                $scope = 'body[data-villa-theme="' . e($renderSlug) . '"]';
                $tag .= '<style id="villa-theme-custom">' . $scope . '{--villa-theme-primary:' . $primary . ';--villa-theme-background:' . $background . ';--villa-theme-surface:' . $surface . ';--villa-theme-text:' . $text . ';--villa-theme-accent:' . $accent . ';--villa-theme-button:' . $buttonColor . ';--villa-theme-radius:' . $radius . 'px;background:var(--villa-theme-background);color:var(--villa-theme-text)}' . $scope . ' .theme-header{' . 'background:var(--villa-theme-surface)}' . $scope . ' .theme-header .nav a:hover,' . $scope . ' .search-submit{background:var(--villa-theme-primary);color:#fff}' . $scope . ' .theme-brand,' . $scope . ' .section h2{color:var(--villa-theme-primary)}' . $scope . ' .stay,' . $scope . ' .hero-card,' . $scope . ' .search{border-radius:var(--villa-theme-radius)}' . $scope . ' .site-footer{background:var(--villa-theme-primary)}' .
                $scope . ' button,' . $scope . ' input[type=submit],' . $scope . ' input[type=button],' . $scope . ' .btn,' . $scope . ' a.btn,' . $scope . ' .button,' . $scope . ' a.more,' . $scope . ' a.reserve,' . $scope . ' .gallery-count,' . $scope . ' .lightbox-arrow,' . $scope . ' .lightbox-close,' . $scope . ' .pagination a,' . $scope . ' .calendar-reserve,' . $scope . ' .search-submit,' . $scope . ' .submit,' . $scope . ' .guest-done button,' . $scope . ' .booking button,' . $scope . ' .booking a,' . $scope . ' .theme-menu-toggle,' . $scope . ' .nav-submenu-toggle,' . $scope . ' .calendar-nav button,' . $scope . ' .card-gallery-arrow,' . $scope . ' .card-favorite,' . $scope . ' .property-favorite-button,' . $scope . ' .pagination a.active{background:var(--villa-theme-button)!important;border-color:var(--villa-theme-button)!important;color:#fff!important}' .
                $scope . ' .nav a:hover,' . $scope . ' .nav a[aria-current=page]{background:var(--villa-theme-button)!important;color:#fff!important}' .
                $scope . ' .price-range-track span,' . $scope . ' .price-range-fill{background:var(--villa-theme-button)!important}' .
                $scope . ' input[type=range]{accent-color:var(--villa-theme-button)}' .
                $scope . ' input[type=range]::-webkit-slider-thumb,' . $scope . ' input[type=range]::-moz-range-thumb{background:var(--villa-theme-button)!important;border-color:#fff!important}' .
                $scope . ' button:hover,' . $scope . ' input[type=submit]:hover,' . $scope . ' .btn:hover{filter:brightness(.92)}' .
                $scope . ' .card-favorite.is-active{color:#fff!important}' .
                '</style>';
            }
            if (stripos($html, '</head>') !== false) $html = preg_replace('/<\/head>/i', $tag . '</head>', $html, 1) ?? $html;
            $html = preg_replace('/<body\b/i', '<body data-villa-theme="' . e($renderSlug) . '"', $html, 1) ?? $html;
        }
        // Inject after the theme stylesheet so every theme obeys the phone layout rule.
        $mobileLayout = '<style id="villa-mobile-single-column-global">
        /* Every installed theme shares a clean white page canvas. */
        body[data-villa-theme]{background-color:#fff!important;}
        /* Keep the account logout action on the same desktop navigation row. */
        @media(min-width:761px){
          body[data-villa-theme] .theme-header-inner{display:flex!important;flex-wrap:nowrap!important;align-items:center!important;gap:16px!important;min-width:0}
          body[data-villa-theme] .theme-header .theme-brand{flex:0 0 auto!important;min-width:0!important}
          body[data-villa-theme] .theme-header .nav{display:flex!important;flex:1 1 auto!important;flex-wrap:nowrap!important;align-items:center!important;justify-content:flex-end!important;gap:4px!important;min-width:0!important}
          body[data-villa-theme] .theme-header .nav-entry{flex:0 0 auto!important;white-space:nowrap!important}
          body[data-villa-theme] .theme-header .nav-entry-top{flex-wrap:nowrap!important;white-space:nowrap!important}
          body[data-villa-theme] .theme-header .nav-entry-top>a,
          body[data-villa-theme] .theme-header .nav .villa-logout-button{white-space:nowrap!important}
          body[data-villa-theme] .theme-header .nav .nav-logout-entry{flex:0 0 auto!important;margin-left:4px!important}
          body[data-villa-theme] .theme-header .nav .villa-logout-form{display:flex!important;align-items:center!important;margin:0!important;padding:0!important}
          body[data-villa-theme] .theme-header .nav .villa-logout-button{display:inline-flex!important;align-items:center!important;justify-content:center!important;margin:0!important;padding:10px 12px!important;line-height:1.2!important}
        }
        @media(max-width:760px){
          body[data-villa-theme] .cards,body[data-villa-theme] .grid,
          body[data-villa-theme] .destination-grid,body[data-villa-theme] .pb-grid,
          body[data-villa-theme] .pb-widget-items,body[data-villa-theme] .pb-property-grid,
          body[data-villa-theme] .pb-destination-grid,body[data-villa-theme] .pb-category-grid,
          body[data-villa-theme] .pb-host-grid,body[data-villa-theme] .pb-blog-grid,
          body[data-villa-theme] .pb-pricing-grid,body[data-villa-theme] .pb-seasonal-grid,
          body[data-villa-theme] .pb-gallery,body[data-villa-theme] .pb-gallery-grid,
          body[data-villa-theme] .gallery-grid{
            display:grid!important;grid-template-columns:minmax(0,1fr)!important;
            grid-auto-flow:row!important;grid-auto-rows:auto!important;
            width:100%!important;min-width:0!important;
          }
          body[data-villa-theme] .cards>*,body[data-villa-theme] .grid>*,
          body[data-villa-theme] .destination-grid>*,body[data-villa-theme] .pb-grid>*,
          body[data-villa-theme] .pb-widget-items>*,body[data-villa-theme] .pb-property-grid>*,
          body[data-villa-theme] .pb-destination-grid>*,body[data-villa-theme] .pb-category-grid>*,
          body[data-villa-theme] .pb-host-grid>*,body[data-villa-theme] .pb-blog-grid>*,
          body[data-villa-theme] .pb-pricing-grid>*,body[data-villa-theme] .pb-seasonal-grid>*,
          body[data-villa-theme] .pb-gallery>*,body[data-villa-theme] .pb-gallery-grid>*,
          body[data-villa-theme] .gallery-grid>*{
            grid-column:1/-1!important;grid-row:auto!important;width:100%!important;
            max-width:100%!important;min-width:0!important;
          }
          body[data-villa-theme] .pb-search-form,body[data-villa-theme] .search-enhanced{
            grid-template-columns:minmax(0,1fr)!important;
          }
          body[data-villa-theme] .pb-widget-properties .pb-card,
          body[data-villa-theme] .pb-widget-destinations .pb-card{
            grid-template-columns:minmax(0,1fr)!important;
          }
        }
        .villa-social-bar{position:relative;z-index:40;width:100%;background:#f5f5f0;border-bottom:1px solid #e8e8df;box-sizing:border-box}
        .villa-social-inner{width:100%;max-width:none;margin:0;padding:7px 22px;box-sizing:border-box;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));align-items:center;gap:10px}
        .villa-social-left{grid-column:1;display:flex;align-items:center;justify-content:flex-start;gap:10px;min-width:0}
        .villa-social-address{grid-column:2;text-align:center;color:#4b574f;font-size:12px;line-height:1.5;overflow-wrap:anywhere}
        .villa-social-call-center{grid-column:3;display:flex;align-items:center;justify-content:center;gap:7px;min-width:0;color:#4b574f;font-size:12px;line-height:1.4}
        .villa-social-call-label{font-weight:700;white-space:nowrap}
        .villa-social-call-center a{color:inherit;text-decoration:none;font-weight:700;white-space:nowrap}
        .villa-social-call-center a:hover{text-decoration:underline}
        .villa-social-license{grid-column:4;display:flex;align-items:center;justify-content:center;gap:5px;min-width:0;color:#4b574f;font-size:12px;line-height:1.4;overflow-wrap:anywhere}
        .villa-social-license-title{font-weight:700}
        .villa-social-license-number{font-weight:700}
        .villa-social-right{grid-column:4;min-height:1px}
        .villa-social-link{width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;color:inherit;text-decoration:none;border-radius:50%;transition:opacity .2s,background .2s}
        .villa-social-link svg{width:var(--social-icon-size,20px);height:var(--social-icon-size,20px);display:block}
        .villa-social-link--circle{background:#ffffff;border:1px solid currentColor}
        .villa-social-link:hover{opacity:.72}
        @media(max-width:760px){.villa-social-inner{padding:6px 12px;grid-template-columns:1fr 1fr;gap:8px}.villa-social-left{grid-column:1;grid-row:1}.villa-social-address{grid-column:2;grid-row:1;text-align:right;font-size:11px}.villa-social-call-center{grid-column:1;grid-row:2;justify-content:flex-start;font-size:11px}.villa-social-license{grid-column:2;grid-row:2;justify-content:flex-end;font-size:11px}.villa-social-right{display:none}.villa-social-link{width:28px;height:28px}}
        </style>';
        $html = preg_replace('/<\\/body>/i', $mobileLayout . '</body>', $html, 1) ?? $html;

        $preview = (string)($_SESSION['villa_theme_preview'] ?? '');
        if ($preview !== '' && Auth::check() && in_array(Auth::user()['role'] ?? '', ['admin','super_admin'], true)) {
            $banner = '<div style="position:fixed;z-index:99999;left:12px;right:12px;bottom:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;border-radius:12px;background:#182b24;color:#fff;font:13px/1.4 system-ui;box-shadow:0 8px 30px #0003"><span>Şu anda <strong>' . e(self::installed()[$preview]['name'] ?? $preview) . '</strong> temasını önizliyorsunuz. Bu görünüm ziyaretçilere açık değildir.</span><a href="' . e(app_url('/admin/themes/preview/clear')) . '" style="color:#fff;font-weight:800;text-decoration:underline;white-space:nowrap">Önizlemeyi kapat</a></div>';
            $html = preg_replace('/<\\/body>/i', $banner . '</body>', $html, 1) ?? $html;
        }
        echo $html;
    }

    public static function installUploaded(array $upload): string
    {
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('Sunucuda PHP ZipArchive etkin değil. Hosting panelinden etkinleştirin veya ZIP desteği olan bir sunucu kullanın.');
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($upload['tmp_name']) || !is_uploaded_file($upload['tmp_name'])) throw new RuntimeException('Geçerli bir tema ZIP dosyası seçin.');
        if ((int)($upload['size'] ?? 0) > 12 * 1024 * 1024) throw new RuntimeException('Tema paketi en fazla 12 MB olabilir.');

        $zip = new ZipArchive();
        if ($zip->open($upload['tmp_name']) !== true) throw new RuntimeException('ZIP paketi açılamadı.');
        $manifestData = null;
        $files = [];
        try {
            if ($zip->numFiles > 150) throw new RuntimeException('Tema paketi çok fazla dosya içeriyor.');
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = str_replace('\\', '/', (string)$zip->getNameIndex($i));
                if ($name === '' || str_starts_with($name, '/') || preg_match('#(^|/)\.\.?(/|$)#', $name) || str_contains($name, ':')) throw new RuntimeException('ZIP içinde güvenli olmayan dosya yolu bulundu.');
                if (str_ends_with($name, '/')) continue;
                if ($name !== 'theme.json' && $name !== 'style.css' && $name !== 'demo.json' && !preg_match('#^(templates/[a-zA-Z0-9_-]+\.php|assets/[a-zA-Z0-9_.-]+\.(css|js|png|jpg|jpeg|webp|svg|woff2?))$#i', $name)) throw new RuntimeException('Desteklenmeyen tema dosyası: ' . $name);
                $stat = $zip->statIndex($i);
                if (!$stat || (int)$stat['size'] > 4 * 1024 * 1024) throw new RuntimeException('Tema içindeki bir dosya çok büyük.');
                $data = $zip->getFromIndex($i);
                if ($data === false) throw new RuntimeException('Tema dosyası okunamadı.');
                if ($name === 'theme.json') $manifestData = $data;
                $files[$name] = $data;
            }
        } finally { $zip->close(); }

        $manifest = json_decode((string)$manifestData, true);
        $slug = is_array($manifest) ? (string)($manifest['slug'] ?? '') : '';
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{1,49}$/', $slug) || empty($manifest['name'])) throw new RuntimeException('theme.json dosyası veya tema kimliği geçersiz.');
        if (isset(self::installed()[$slug])) throw new RuntimeException('Bu tema zaten yüklü. Güncelleme özelliği sonraki aşamada eklenecek.');
        if (!isset($files['style.css'])) throw new RuntimeException('Tema paketinde style.css bulunmalıdır.');

        $target = self::root() . '/' . $slug;
        if (!mkdir($target, 0755, true) && !is_dir($target)) throw new RuntimeException('Tema klasörü oluşturulamadı.');
        try {
            foreach ($files as $name => $data) {
                $path = $target . '/' . $name;
                $parent = dirname($path);
                if (!is_dir($parent) && !mkdir($parent, 0755, true) && !is_dir($parent)) throw new RuntimeException('Tema alt klasörü oluşturulamadı.');
                if (file_put_contents($path, $data, LOCK_EX) === false) throw new RuntimeException('Tema dosyası yazılamadı: ' . $name);
            }
        } catch (\Throwable $e) {
            self::removeDirectory($target);
            throw $e;
        }
        return $slug;
    }

    public static function remove(string $slug): void
    {
        $themes = self::installed();
        if (!isset($themes[$slug])) throw new RuntimeException('Tema bulunamadı.');
        if (!empty($themes[$slug]['builtin'])) throw new RuntimeException('Yerleşik temalar silinemez.');
        if (self::activeSlug() === $slug) throw new RuntimeException('Etkin tema silinemez. Önce başka bir tema etkinleştirin.');
        self::removeDirectory(self::root() . '/' . $slug);
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) return;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($directory);
    }
}
