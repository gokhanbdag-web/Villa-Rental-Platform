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
        $themes = [];
        foreach (glob(self::root() . '/*/theme.json') ?: [] as $manifestPath) {
            $dir = dirname($manifestPath);
            $slug = basename($dir);
            if (!preg_match('/^[a-z0-9][a-z0-9_-]{1,49}$/', $slug)) continue;
            $manifest = json_decode((string)file_get_contents($manifestPath), true);
            if (!is_array($manifest) || ($manifest['slug'] ?? '') !== $slug || empty($manifest['name'])) continue;
            $themes[$slug] = [
                'slug' => $slug,
                'name' => (string)$manifest['name'],
                'version' => (string)($manifest['version'] ?? '1.0.0'),
                'description' => (string)($manifest['description'] ?? ''),
                'author' => (string)($manifest['author'] ?? ''),
                'builtin' => !empty($manifest['builtin']),
                'manifest' => $manifest,
            ];
        }
        uasort($themes, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));
        return $themes;
    }

    public static function activeSlug(?PDO $pdo = null): string
    {
        $preview = $_SESSION['villa_theme_preview'] ?? '';
        if (is_string($preview) && isset(self::installed()[$preview]) && Auth::check() && in_array(Auth::user()['role'] ?? '', ['admin','super_admin'], true)) return $preview;
        $demo = $_SESSION['villa_public_demo_theme'] ?? '';
        if (is_string($demo) && isset(self::installed()[$demo])) return $demo;
        $pdo ??= Database::connection();
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
        $stmt->execute(['theme.active']);
        $slug = (string)($stmt->fetchColumn() ?: 'mediterranean');
        return isset(self::installed()[$slug]) ? $slug : (array_key_first(self::installed()) ?: 'mediterranean');
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

    public static function render(string $view, array $variables): void
    {
        $slug = self::activeSlug();
        // Until a theme demo is imported, every frontend template uses the
        // preserved Mediterranean design as the common default. Demo previews
        // are exempt so administrators can inspect a package before importing it.
        $hasImportedDemo = false;
        try {
            $hasImportedDemo = ThemeDemoImporter::status($slug) !== [];
        } catch (\\Throwable $e) {
            error_log('Theme demo status unavailable: ' . $e->getMessage());
        }
        $layout = $variables['pageBuilderLayout'] ?? null;
        $isThemeDemoPreview = basename($view) === 'landing.php'
            && (string)($_SESSION['villa_builder_preview_theme'] ?? '') === $slug
            && is_array($layout)
            && (string)($layout['status'] ?? '') === 'published';
        $isSharedDefaultTheme = !$hasImportedDemo && !$isThemeDemoPreview;
        $isSharedDefaultLanding = basename($view) === 'landing.php' && $isSharedDefaultTheme;
        $renderSlug = $isSharedDefaultTheme ? 'mediterranean' : $slug;
        $themeDir = self::root() . '/' . $renderSlug;
        $manifest = self::installed()[$renderSlug]['manifest'] ?? [];
        $map = $manifest['templates'] ?? [];
        $relative = isset($map[$view]) && is_string($map[$view]) ? $map[$view] : '';
        $template = '';
        if ($isSharedDefaultLanding) {
            $candidate = $themeDir . '/templates/landing.php';
            $real = realpath($candidate);
            $base = realpath($themeDir);
            if ($real && $base && str_starts_with($real, $base . DIRECTORY_SEPARATOR) && is_file($real)) $template = $real;
        } elseif ($relative !== '' && !str_contains($relative, '..') && str_starts_with($relative, 'templates/')) {
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
        }</style>';
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
