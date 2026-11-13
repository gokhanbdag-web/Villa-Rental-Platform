<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

final class ThemeManager
{
    private static function root(): string { return APP_ROOT . '/themes'; }

    /** Discover valid theme folders; default is the mandatory base layer. */
    public static function installed(): array
    {
        $themes = [];
        foreach (glob(self::root() . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $slug = basename($directory);
            if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug)) continue;
            $manifestPath = $directory . '/theme.json';
            if (!is_file($manifestPath)) continue;
            $manifest = json_decode((string)file_get_contents($manifestPath), true);
            if (!is_array($manifest) || ($manifest['slug'] ?? '') !== $slug || trim((string)($manifest['name'] ?? '')) === '') continue;
            $themes[$slug] = [
                'slug' => $slug,
                'name' => (string)$manifest['name'],
                'version' => (string)($manifest['version'] ?? '1.0.0'),
                'description' => (string)($manifest['description'] ?? ''),
                'author' => (string)($manifest['author'] ?? ''),
                'builtin' => $slug === 'default',
                'manifest' => $manifest,
            ];
        }
        if (!isset($themes['default'])) return [];
        return $themes;
    }

    public static function activeSlug(?PDO $pdo = null): string
    {
        try {
            $pdo = $pdo ?? Database::connection();
            $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
            $stmt->execute(['theme.active']);
            $slug = trim((string)($stmt->fetchColumn() ?: 'default'));
            return isset(self::installed()[$slug]) ? $slug : 'default';
        } catch (\Throwable $e) {
            return 'default';
        }
    }

    /** Resolve a theme file active-first, then fall back to the default theme. */
    public static function resolveFile(string $relative, ?string $slug = null): ?string
    {
        $relative = str_replace('\\', '/', trim($relative));
        if ($relative === '' || str_starts_with($relative, '/') || preg_match('/^[a-zA-Z]:/', $relative)) return null;
        foreach (explode('/', $relative) as $part) {
            if ($part === '' || $part === '.' || $part === '..') return null;
        }
        $slug = $slug ?: self::activeSlug();
        if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug) || !isset(self::installed()[$slug])) $slug = 'default';
        foreach (array_unique([$slug, 'default']) as $candidateSlug) {
            $base = realpath(self::root() . '/' . $candidateSlug);
            if ($base === false) continue;
            $candidate = realpath($base . '/' . $relative);
            if ($candidate === false || !is_file($candidate)) continue;
            if (!str_starts_with($candidate, $base . DIRECTORY_SEPARATOR)) continue;
            return $candidate;
        }
        return null;
    }

    /** Build a URL that works both at the domain root and in a subdirectory. */
    public static function assetUrl(string $relative, ?string $slug = null): string
    {
        $slug = $slug ?: self::activeSlug();
        if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug) || !isset(self::installed()[$slug])) $slug = 'default';
        $relative = str_replace('\\', '/', trim($relative));
        $parts = explode('/', $relative);
        if ($relative === '' || str_starts_with($relative, '/') || in_array('..', $parts, true) || in_array('.', $parts, true)) return app_url('/404');
        return app_url('/theme-assets/' . rawurlencode($slug) . '/' . implode('/', array_map('rawurlencode', explode('/', $relative))));
    }

    /** Theme-scoped visual assets; legacy branding is read once as a compatibility fallback for Default. */
    public static function brandSettings(?string $slug = null): array
    {
        $slug = $slug ?: self::activeSlug();
        if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug) || !isset(self::installed()[$slug])) $slug = 'default';
        $defaults = ['logo_path' => '', 'hero_video_path' => ''];
        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
            $stmt->execute(['theme.' . $slug . '.branding']);
            $raw = $stmt->fetchColumn();
            if (($raw === false || $raw === null || $raw === '') && $slug === 'default') {
                $stmt->execute(['theme.branding']);
                $raw = $stmt->fetchColumn();
            }
            $saved = json_decode((string)($raw ?: '{}'), true);
            if (is_array($saved)) foreach ($defaults as $key => $value) {
                if (isset($saved[$key]) && is_string($saved[$key])) $defaults[$key] = $saved[$key];
            }
        } catch (\Throwable $e) {
            error_log('Theme branding settings read failed: ' . $e->getMessage());
        }
        return [
            'logo_path' => $defaults['logo_path'],
            'hero_video_path' => $defaults['hero_video_path'],
            'logo_url' => $defaults['logo_path'] !== '' ? app_url('/' . ltrim($defaults['logo_path'], '/')) : '',
            'hero_video_url' => $defaults['hero_video_path'] !== '' ? app_url('/' . ltrim($defaults['hero_video_path'], '/')) : '',
        ];
    }

    public static function saveBrandAsset(string $type, array $upload, ?string $slug = null): void
    {
        $slug = $slug ?: self::activeSlug();
        if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug) || !isset(self::installed()[$slug])) throw new RuntimeException('Tema bulunamadı.');
        $rules = [
            'logo' => ['max'=>4194304,'mimes'=>['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'],'key'=>'logo_path'],
            'hero_video' => ['max'=>47185920,'mimes'=>['video/mp4'=>'mp4','video/webm'=>'webm'],'key'=>'hero_video_path'],
        ];
        if (!isset($rules[$type])) throw new RuntimeException('Tema görsel dosyası türü geçersiz.');
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($upload['tmp_name']) || !is_uploaded_file((string)$upload['tmp_name'])) throw new RuntimeException('Dosya yüklenemedi. Lütfen yeniden deneyin.');
        $rule = $rules[$type];
        $size = (int)($upload['size'] ?? 0);
        if ($size < 1 || $size > $rule['max']) throw new RuntimeException($type === 'logo' ? 'Logo en fazla 4 MB olabilir.' : 'Video en fazla 45 MB olabilir.');
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file((string)$upload['tmp_name']);
        if (!is_string($mime) || !isset($rule['mimes'][$mime])) throw new RuntimeException($type === 'logo' ? 'Logo PNG, JPG veya WebP olmalıdır.' : 'Video MP4 veya WebM olmalıdır.');
        if ($type === 'logo' && @getimagesize((string)$upload['tmp_name']) === false) throw new RuntimeException('Geçerli bir görsel dosyası yükleyin.');

        $relativeDir = 'uploads/themes/' . $slug . '/branding';
        $dir = APP_ROOT . '/public/' . $relativeDir;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new RuntimeException('Tema dosya klasörü oluşturulamadı.');
        $name = $type . '-' . bin2hex(random_bytes(12)) . '.' . $rule['mimes'][$mime];
        $target = $dir . '/' . $name;
        if (!move_uploaded_file((string)$upload['tmp_name'], $target)) throw new RuntimeException('Dosya kaydedilemedi.');

        $settings = self::brandSettings($slug);
        $old = (string)($settings[$rule['key']] ?? '');
        $settings[$rule['key']] = $relativeDir . '/' . $name;
        $json = json_encode(['logo_path'=>$settings['logo_path'],'hero_video_path'=>$settings['hero_video_path']], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        try {
            $pdo = Database::connection();
            $save = $pdo->prepare('INSERT INTO settings(setting_key,setting_value,autoload) VALUES(?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
            $save->execute(['theme.' . $slug . '.branding', $json]);
        } catch (\Throwable $e) {
            @unlink($target);
            throw $e;
        }
        if ($old !== '' && $old !== $settings[$rule['key']] && str_starts_with($old, 'uploads/themes/' . $slug . '/branding/')) {
            $oldFile = APP_ROOT . '/public/' . $old;
            if (is_file($oldFile)) @unlink($oldFile);
        }
    }

    public static function removeBrandAsset(string $type, ?string $slug = null): void
    {
        $slug = $slug ?: self::activeSlug();
        if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug) || !isset(self::installed()[$slug])) throw new RuntimeException('Tema bulunamadı.');
        $keys = ['logo'=>'logo_path','hero_video'=>'hero_video_path'];
        if (!isset($keys[$type])) throw new RuntimeException('Tema görsel dosyası türü geçersiz.');
        $settings = self::brandSettings($slug);
        $old = (string)($settings[$keys[$type]] ?? '');
        $settings[$keys[$type]] = '';
        $json = json_encode(['logo_path'=>$settings['logo_path'],'hero_video_path'=>$settings['hero_video_path']], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $pdo = Database::connection();
        $save = $pdo->prepare('INSERT INTO settings(setting_key,setting_value,autoload) VALUES(?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
        $save->execute(['theme.' . $slug . '.branding', $json]);
        if ($old !== '' && str_starts_with($old, 'uploads/themes/' . $slug . '/branding/')) {
            $oldFile = APP_ROOT . '/public/' . $old;
            if (is_file($oldFile)) @unlink($oldFile);
        }
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
        $pdo = Database::connection();
        $json = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $stmt = $pdo->prepare('INSERT INTO settings(setting_key,setting_value,autoload) VALUES(?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
        $stmt->execute(['theme.social_icons', $json]);

        // Verify the write before reporting success to the administrator.
        $verify = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
        $verify->execute(['theme.social_icons']);
        $stored = (string)($verify->fetchColumn() ?: '');
        if ($stored !== $json) {
            throw new RuntimeException('Üst bilgi ayarları veritabanına yazılamadı. Lütfen tekrar deneyin.');
        }
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

    private static function typographyFonts(): array
    {
        return [
            'Inter'=>'Inter','Roboto'=>'Roboto','Open Sans'=>'Open Sans','Lato'=>'Lato',
            'Montserrat'=>'Montserrat','Poppins'=>'Poppins','Nunito Sans'=>'Nunito Sans',
            'Playfair Display'=>'Playfair Display','Merriweather'=>'Merriweather',
            'DM Sans'=>'DM Sans','Manrope'=>'Manrope','Source Sans 3'=>'Source Sans 3',
            'Raleway'=>'Raleway','PT Sans'=>'PT Sans','Libre Baskerville'=>'Libre Baskerville',
        ];
    }

    private static function typographyDefaults(): array
    {
        return [
            'topbar_height'=>64,
            'headings'=>['family'=>'Playfair Display','size'=>36,'color'=>'#263b31','weight'=>700,'style'=>'normal'],
            'span'=>['family'=>'Inter','size'=>14,'color'=>'#344039','weight'=>400,'style'=>'normal'],
            'paragraph'=>['family'=>'Inter','size'=>16,'color'=>'#4b574f','weight'=>400,'style'=>'normal'],
            'paragraph_links'=>['family'=>'Inter','size'=>16,'color'=>'#176b50','weight'=>600,'style'=>'normal'],
        ];
    }

    private static function normalizeTypography(array $input): array
    {
        $clean = self::typographyDefaults();
        $height = filter_var($input['topbar_height'] ?? $clean['topbar_height'], FILTER_VALIDATE_INT);
        if ($height !== false) $clean['topbar_height'] = max(52, min(100, $height));
        $weights = [300,400,500,600,700,800,900];
        foreach (['headings','span','paragraph','paragraph_links'] as $group) {
            $row = is_array($input[$group] ?? null) ? $input[$group] : [];
            $family = (string)($row['family'] ?? $clean[$group]['family']);
            if (isset(self::typographyFonts()[$family])) $clean[$group]['family'] = $family;
            $size = filter_var($row['size'] ?? $clean[$group]['size'], FILTER_VALIDATE_INT);
            if ($size !== false) $clean[$group]['size'] = max(10, min(64, $size));
            $color = strtolower(trim((string)($row['color'] ?? $clean[$group]['color'])));
            if (preg_match('/^#[0-9a-f]{6}$/', $color)) $clean[$group]['color'] = $color;
            $weight = filter_var($row['weight'] ?? $clean[$group]['weight'], FILTER_VALIDATE_INT);
            if ($weight !== false && in_array($weight, $weights, true)) $clean[$group]['weight'] = $weight;
            $style = (string)($row['style'] ?? $clean[$group]['style']);
            if (in_array($style, ['normal','italic'], true)) $clean[$group]['style'] = $style;
        }
        return $clean;
    }

    public static function typographySettings(?string $slug = null): array
    {
        $slug = $slug ?: self::activeSlug();
        if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug)) $slug = 'default';
        try {
            $stmt = Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
            $stmt->execute(['theme.' . $slug . '.typography']);
            $saved = json_decode((string)($stmt->fetchColumn() ?: '{}'), true);
            return self::normalizeTypography(is_array($saved) ? $saved : []);
        } catch (\Throwable $e) {
            error_log('Theme typography settings read failed: ' . $e->getMessage());
            return self::typographyDefaults();
        }
    }


    /** Theme-scoped design palette stored in the database, never in editable CMS content. */
    private static function designDefaults(): array
    {
        return ['primary'=>'#176b50','accent'=>'#ef8069','surface'=>'#ffffff','background'=>'#f5f8f6','text'=>'#20352f','muted'=>'#71817a','border'=>'#e2eae5'];
    }

    private static function normalizeDesign(array $input): array
    {
        $clean = self::designDefaults();
        foreach ($clean as $key => $default) {
            $value = strtolower(trim((string)($input[$key] ?? $default)));
            if (!preg_match('/^#[0-9a-f]{6}$/', $value)) {
                throw new RuntimeException('Renk değerleri 6 haneli HEX formatında olmalıdır.');
            }
            $clean[$key] = $value;
        }
        return $clean;
    }

    public static function designSettings(?string $slug = null): array
    {
        $slug = $slug ?: self::activeSlug();
        if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug)) $slug = 'default';
        try {
            $stmt = Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
            $stmt->execute(['theme.' . $slug . '.design']);
            $saved = json_decode((string)($stmt->fetchColumn() ?: '{}'), true);
            return self::normalizeDesign(is_array($saved) ? $saved : []);
        } catch (\Throwable $e) {
            error_log('Theme design settings read failed: ' . $e->getMessage());
            return self::designDefaults();
        }
    }

    public static function saveDesignSettings(array $input, ?string $slug = null): void
    {
        $slug = $slug ?: self::activeSlug();
        if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug) || !isset(self::installed()[$slug])) {
            throw new RuntimeException('Tema kodu geçersiz veya kurulu değil.');
        }
        $clean = self::normalizeDesign($input);
        $key = 'theme.' . $slug . '.design';
        $json = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO settings(setting_key,setting_value,autoload) VALUES(?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
        $stmt->execute([$key, $json]);
        $verify = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
        $verify->execute([$key]);
        if ((string)$verify->fetchColumn() !== $json) throw new RuntimeException('Tema tasarım ayarları veritabanına kaydedilemedi.');
    }

    public static function saveActiveTheme(string $slug): void
    {
        if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug)) {
            throw new RuntimeException('Tema seçimi geçersiz.');
        }
        $installed = self::installed();
        if (!isset($installed[$slug])) {
            throw new RuntimeException('Seçilen tema kurulu değil veya tema bildirimi geçersiz.');
        }
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO settings(setting_key,setting_value,autoload) VALUES(?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
        $stmt->execute(['theme.active', $slug]);
        $verify = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
        $verify->execute(['theme.active']);
        if ((string)$verify->fetchColumn() !== $slug) {
            throw new RuntimeException('Aktif tema tercihi veritabanına kaydedilemedi.');
        }
    }

    public static function saveTypographySettings(array $input, ?string $slug = null): void
    {
        $slug = $slug ?: self::activeSlug();
        if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug)) throw new RuntimeException('Tema kodu geçersiz.');
        $clean = self::normalizeTypography($input);
        $key = 'theme.' . $slug . '.typography';
        $json = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO settings(setting_key,setting_value,autoload) VALUES(?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
        $stmt->execute([$key, $json]);
        $verify = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
        $verify->execute([$key]);
        if ((string)$verify->fetchColumn() !== $json) throw new RuntimeException('Tema tipografi ayarları veritabanına kaydedilemedi.');
    }

    /**
     * Render one overridable PHP component. Themes may replace a single partial
     * or layout without replacing the page template that composes it.
     */
    /** Render a page-level presentation file from the active theme or default layer. */
    public static function renderTemplate(string $relative, array $variables = []): void
    {
        if (!str_starts_with($relative, 'templates/pages/') || strtolower(pathinfo($relative, PATHINFO_EXTENSION)) !== 'php') {
            throw new RuntimeException('Tema şablon yolu geçersiz.');
        }
        $file = self::resolveFile($relative);
        if ($file === null) throw new RuntimeException('Tema şablonu bulunamadı: ' . $relative);
        (static function (string $__file, array $__variables): void {
            extract($__variables, EXTR_SKIP);
            require $__file;
        })($file, $variables);
    }

    /** Apply the active theme's admin layout to rendered admin pages. */
    public static function wrapAdminHtml(string $html, string $requestPath, string $platformMode): string
    {
        $file = self::resolveFile('layouts/admin-shell.php');
        if ($file === null) return $html;
        $load = static function (string $__file): mixed { return require $__file; };
        $wrapper = $load($file);
        if (!is_callable($wrapper)) throw new RuntimeException('Tema yönetim yerleşimi geçersiz.');
        return (string)$wrapper($html, $requestPath, $platformMode);
    }

    public static function renderPartial(string $relative, array $variables = []): void
    {
        $allowed = str_starts_with($relative, 'partials/') || str_starts_with($relative, 'layouts/') || str_starts_with($relative, 'components/');
        if (!$allowed || strtolower(pathinfo($relative, PATHINFO_EXTENSION)) !== 'php') {
            throw new RuntimeException('Tema bileşen yolu geçersiz.');
        }
        $file = self::resolveFile($relative);
        if ($file === null) throw new RuntimeException('Tema bileşeni bulunamadı: ' . $relative);
        (static function (string $__file, array $__variables): void {
            extract($__variables, EXTR_SKIP);
            require $__file;
        })($file, $variables);
    }

    public static function render(string $view, array $variables): void
    {
        $renderSlug = self::activeSlug();
        $installed = self::installed();
        $defaultMap = $installed['default']['manifest']['templates'] ?? [];
        $activeMap = $installed[$renderSlug]['manifest']['templates'] ?? [];
        $activeRelative = isset($activeMap[$view]) && is_string($activeMap[$view]) ? $activeMap[$view] : '';
        $defaultRelative = isset($defaultMap[$view]) && is_string($defaultMap[$view]) ? $defaultMap[$view] : '';
        $template = '';
        if ($activeRelative !== '' && str_starts_with($activeRelative, 'templates/')) {
            $template = self::resolveFile($activeRelative, $renderSlug) ?? '';
        }
        if ($template === '' && $defaultRelative !== '' && str_starts_with($defaultRelative, 'templates/')) {
            $template = self::resolveFile($defaultRelative, 'default') ?? '';
        }
        if ($template === '') $template = APP_ROOT . '/views/site/' . basename($view);
        if (!is_file($template)) throw new RuntimeException('Tema şablonu bulunamadı: ' . $view);

        $variables['brandSettings'] = self::brandSettings($renderSlug);
        extract($variables, EXTR_SKIP);
        ob_start();
        require $template;
        $html = (string)ob_get_clean();

        $brandSettings = self::brandSettings($renderSlug);
        $logoUrl = (string)($brandSettings['logo_url'] ?? '');
        if ($logoUrl !== '') {
            $siteName = (string)($variables['content']['site_name'] ?? $variables['siteName'] ?? 'VillaStay');
            $logoMarkup = '<img class="theme-brand-logo" src="' . e($logoUrl) . '" alt="' . e($siteName) . '">';
            $html = preg_replace('~<span class="theme-brand-name">.*?</span>~s', $logoMarkup, $html) ?? $html;
        }

        $socialSettings = self::socialSettings();
        $renderSocialIcon = static fn(string $platform, string $family): string => self::renderSocialIcon($platform, $family);
        ob_start();
        self::renderPartial('partials/shared-topbar.php', [
            'socialSettings' => $socialSettings,
            'renderSocialIcon' => $renderSocialIcon,
        ]);
        $socialBar = (string)ob_get_clean();
        if (trim($socialBar) !== '' && stripos($html, 'villa-social-bar') === false) {
            if (preg_match('~<div class="med-topbar"~i', $html)) {
                $html = preg_replace('~<div class="med-topbar"~i', $socialBar . '<div class="med-topbar"', $html, 1) ?? $html;
            } else {
                $html = preg_replace('~<header\\b~i', $socialBar . '<header', $html, 1) ?? $html;
            }
        }

        // Base first, active second: browser cascade merges individual declarations.
        $styleTags = '';
        $defaultCss = self::resolveFile('style.css', 'default');
        if ($defaultCss !== null) {
            $styleTags .= '<link rel="stylesheet" href="' . e(self::assetUrl('style.css', 'default')) . '?v=' . rawurlencode((string)filemtime($defaultCss)) . '">';
        }
        if ($view === 'landing.php') {
            $styleTags .= '<style id="villa-default-hero-refresh">
                body.villa-home-mediterranean .theme-header-inner{width:min(1200px,calc(100% - 48px));max-width:1200px;margin:0 auto}
                .theme-brand-logo{display:block;width:auto;height:auto;max-width:220px;max-height:54px;object-fit:contain}
                body.villa-home-mediterranean .hero{padding:0;background:#102a25}
                body.villa-home-mediterranean .hero-card{position:relative;isolation:isolate;width:100%;max-width:none;min-height:680px;padding:68px 24px;box-shadow:none;background-image:linear-gradient(90deg,rgba(14,40,37,.7),rgba(14,40,37,.05)),url(https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=2000&q=88);background-position:center;background-size:cover}
                body.villa-home-mediterranean .hero-video,body.villa-home-mediterranean .hero-shade{position:absolute;inset:0;width:100%;height:100%}
                body.villa-home-mediterranean .hero-video{z-index:-2;object-fit:cover}
                body.villa-home-mediterranean .hero-shade{z-index:-1;background:linear-gradient(90deg,rgba(8,29,25,.78),rgba(8,29,25,.58) 58%,rgba(8,29,25,.44))}
                body.villa-home-mediterranean .hero-inner{position:relative;z-index:1;width:min(1200px,100%);margin:0 auto;display:grid;grid-template-columns:minmax(0,2fr) minmax(310px,1fr);gap:5%;align-items:center}
                body.villa-home-mediterranean .hero-copy{max-width:760px;padding:25px 0;color:#fff}
                body.villa-home-mediterranean .hero-copy h1{max-width:780px;font:500 clamp(42px,5.4vw,76px)/1.04 Georgia,serif;letter-spacing:-2.5px}
                body.villa-home-mediterranean .hero-copy p{max-width:650px;font-size:clamp(16px,1.5vw,20px);line-height:1.7}
                body.villa-home-mediterranean .hero-filter-panel{width:100%;max-width:420px;justify-self:end;padding:22px;background:rgba(255,255,255,.97);border:1px solid rgba(255,255,255,.7);border-radius:22px;box-shadow:0 24px 70px rgba(0,0,0,.24);backdrop-filter:blur(12px)}
                body.villa-home-mediterranean .hero-filter-panel .search-enhanced{display:grid;grid-template-columns:minmax(0,1fr);gap:0;width:100%;max-width:none;padding:0;border:0;border-radius:0;box-shadow:none;background:transparent}
                body.villa-home-mediterranean .hero-filter-panel .search-field{min-height:67px;padding:10px 3px;border-right:0;border-bottom:1px solid #e8eeea}
                body.villa-home-mediterranean .hero-filter-panel .search-submit{width:100%;min-height:54px;margin-top:15px;border-radius:12px}
                @media(max-width:900px){body.villa-home-mediterranean .hero-card{min-height:0;padding:54px 28px}body.villa-home-mediterranean .hero-inner{grid-template-columns:minmax(0,1.3fr) minmax(270px,.9fr);gap:24px}}
                @media(max-width:700px){body.villa-home-mediterranean .theme-header-inner{width:calc(100% - 28px)}.theme-brand-logo{max-width:160px;max-height:43px}body.villa-home-mediterranean .hero-card{padding:45px 18px 30px;min-height:0;gap:18px}body.villa-home-mediterranean .hero-filter-panel{max-width:none;justify-self:stretch;padding:16px;border-radius:17px}body.villa-home-mediterranean .hero-copy h1{font-size:clamp(38px,11vw,48px)}}
            </style>';
        }
        if ($renderSlug !== 'default') {
            $activeCss = self::resolveFile('style.css', $renderSlug);
            if ($activeCss !== null && $activeCss !== $defaultCss) {
                $styleTags .= '<link rel="stylesheet" href="' . e(self::assetUrl('style.css', $renderSlug)) . '?v=' . rawurlencode((string)filemtime($activeCss)) . '">';
            }
        }
        if ($styleTags !== '' && stripos($html, '</head>') !== false) {
            $html = preg_replace('/<\/head>/i', $styleTags . '</head>', $html, 1) ?? $html;
        }
        $html = preg_replace('/<body\b/i', '<body data-villa-theme="' . e($renderSlug) . '"', $html, 1) ?? $html;
        $design = self::designSettings($renderSlug);
        $designCss = '<style id="villa-theme-design-vars">body[data-villa-theme="' . e($renderSlug) . '"]{'
            . '--villa-primary:' . $design['primary'] . ';--villa-accent:' . $design['accent']
            . ';--villa-surface:' . $design['surface'] . ';--villa-background:' . $design['background']
            . ';--villa-text:' . $design['text'] . ';--villa-muted:' . $design['muted'] . ';--villa-border:' . $design['border'] . ';'
            . '--coastal-primary:' . $design['primary'] . ';--coastal-primary-dark:' . $design['primary']
            . ';--coastal-accent:' . $design['accent'] . ';--coastal-paper:' . $design['background']
            . ';--coastal-ink:' . $design['text'] . ';--coastal-muted:' . $design['muted'] . ';--coastal-line:' . $design['border'] . ';'
            . '}body[data-villa-theme="' . e($renderSlug) . '"] .submit,body[data-villa-theme="' . e($renderSlug) . '"] .search-submit{background:var(--villa-primary)}'
            . '</style>';
        if (stripos($html, '</head>') !== false) $html = preg_replace('/<\\/head>/i', $designCss . '</head>', $html, 1) ?? $html;
        $typography = self::typographySettings($renderSlug);
        $fontFamilies = [];
        foreach (['headings','span','paragraph','paragraph_links'] as $group) {
            $fontFamilies[(string)$typography[$group]['family']] = true;
        }
        $fontQuery = [];
        foreach (array_keys($fontFamilies) as $family) {
            $encodedFamily = str_replace('%20', '+', rawurlencode($family));
            $fontQuery[] = 'family=' . $encodedFamily . ':ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
        }
        $fontAssets = '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        $fontAssets .= '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?' . e(implode('&', $fontQuery)) . '&amp;display=swap">';
        $themeScope = 'body[data-villa-theme="' . e($renderSlug) . '"]';
        $heading = $typography['headings'];
        $span = $typography['span'];
        $paragraph = $typography['paragraph'];
        $paragraphLinks = $typography['paragraph_links'];
        $headingBaseSize = (int)$heading['size'];
        $headingBaseRule = '{font-family:' . json_encode($heading['family']) . '!important;color:' . $heading['color'] . '!important;font-weight:' . (int)$heading['weight'] . '!important;font-style:' . $heading['style'] . '!important}';
        $headingRules = $themeScope . ' h1,' . $themeScope . ' h2,' . $themeScope . ' h3,' . $themeScope . ' h4,' . $themeScope . ' h5,' . $themeScope . ' h6' . $headingBaseRule
            . $themeScope . ' h1{font-size:' . $headingBaseSize . 'px!important}'
            . $themeScope . ' h2{font-size:' . max(18, (int)round($headingBaseSize * .78)) . 'px!important}'
            . $themeScope . ' h3{font-size:' . max(16, (int)round($headingBaseSize * .64)) . 'px!important}'
            . $themeScope . ' h4{font-size:' . max(15, (int)round($headingBaseSize * .55)) . 'px!important}'
            . $themeScope . ' h5{font-size:' . max(14, (int)round($headingBaseSize * .48)) . 'px!important}'
            . $themeScope . ' h6{font-size:' . max(13, (int)round($headingBaseSize * .44)) . 'px!important}';
        $typographyCss = $themeScope . ' .villa-social-inner{min-height:' . (int)$typography['topbar_height'] . 'px}'
            . $headingRules
            . $themeScope . ' span{font-family:' . json_encode($span['family']) . '!important;font-size:' . (int)$span['size'] . 'px!important;color:' . $span['color'] . '!important;font-weight:' . (int)$span['weight'] . '!important;font-style:' . $span['style'] . '!important}'
            . $themeScope . ' p{font-family:' . json_encode($paragraph['family']) . '!important;font-size:' . (int)$paragraph['size'] . 'px!important;color:' . $paragraph['color'] . '!important;font-weight:' . (int)$paragraph['weight'] . '!important;font-style:' . $paragraph['style'] . '!important}'
            . $themeScope . ' p a:not(.button):not(.btn):not([class*="button"]):not([class*="btn"]):not([class*="cta"]):not(.search-submit):not([role="button"]){font-family:' . json_encode($paragraphLinks['family']) . '!important;font-size:' . (int)$paragraphLinks['size'] . 'px!important;color:' . $paragraphLinks['color'] . '!important;font-weight:' . (int)$paragraphLinks['weight'] . '!important;font-style:' . $paragraphLinks['style'] . '!important}';
        $typographyAssets = $fontAssets . '<style id="villa-theme-typography">' . $typographyCss . '</style>';
        if (stripos($html, '</head>') !== false) {
            $html = preg_replace('/<\/head>/i', $typographyAssets . '</head>', $html, 1) ?? $html;
        }

        // Apply the saved palette after theme and typography CSS so fixed theme colors cannot override it.
        $paletteCss = $themeScope . '{color-scheme:light}'
            . $themeScope . '{background:var(--villa-background)!important;color:var(--villa-text)!important}'
            . $themeScope . ' .theme-header,' . $themeScope . ' .site-header,' . $themeScope . ' .villa-social-bar,' . $themeScope . ' .theme-header .nav{background:var(--villa-surface)!important;border-color:var(--villa-border)!important}'
            . $themeScope . ' .hero,' . $themeScope . .intro,' . $themeScope . ' .section,' . $themeScope . ' .home-accommodations{background-color:var(--villa-background)!important}'
            . $themeScope . ' .stay,' . $themeScope . ' .premium-stay-card,' . $themeScope . ' .filter-panel,' . $themeScope . ' .booking,' . $themeScope . ' .panel,' . $themeScope . ' .card,' . $themeScope . ' .med-review-card,' . $themeScope . ' .med-support-panel{background:var(--villa-surface)!important;border-color:var(--villa-border)!important;color:var(--villa-text)!important}'
            . $themeScope . ' .theme-brand,' . $themeScope . ' .theme-brand-name,' . $themeScope . ' .brand,' . $themeScope . ' .section-head h2,' . $themeScope . ' .section h2,' . $themeScope . ' .stay h3,' . $themeScope . ' .med-collection-heading h3,' . $themeScope . ' .med-faq-item summary,' . $themeScope . ' .med-support h2{color:var(--villa-text)!important}'
            . $themeScope . ' .theme-header .nav a,' . $themeScope . ' .theme-header .nav-submenu-toggle,' . $themeScope . ' .villa-social-bar,' . $themeScope . ' .med-property-body,' . $themeScope . ' .med-review-card>p{color:var(--villa-text)!important}'
            . $themeScope . ' .muted,' . $themeScope . ' .meta,' . $themeScope . .crumb,' . $themeScope . ' .section-head p,' . $themeScope . ' .med-property-place,' . $themeScope . ' .med-property-meta,' . $themeScope . ' .med-property-price,' . $themeScope . ' .med-collection-heading p,' . $themeScope . ' .med-faq-intro p,' . $themeScope . ' .med-support p{color:var(--villa-muted)!important}'
            . $themeScope . ' .search-submit,' . $themeScope . ' .submit,' . $themeScope . ' .button-primary,' . $themeScope . ' .med-support-whatsapp,' . $themeScope . ' .pagination .current{background:var(--villa-primary)!important;border-color:var(--villa-primary)!important;color:#fff!important}'
            . $themeScope . ' a:not(.search-submit):not(.button-primary):not(.med-support-whatsapp),' . $themeScope . ' .brand{--link-color:var(--villa-primary)}'
            . $themeScope . ' .theme-header .nav a:hover,' . $themeScope . ' .theme-header .nav a[aria-current="page"],' . $themeScope . ' .pill,' . $themeScope . ' .med-trust-icon,' . $themeScope . ' .rating-score{background:var(--villa-accent)!important;color:var(--villa-text)!important}'
            . $themeScope . ' input,' . $themeScope . ' select,' . $themeScope . ' textarea,' . $themeScope . ' .search-enhanced,' . $themeScope . ' .search-field,' . $themeScope . ' .med-faq-item{border-color:var(--villa-border)!important}'
            . $themeScope . ' footer,' . $themeScope . ' .site-footer{background:var(--villa-primary)!important;border-color:var(--villa-primary)!important;color:#fff!important}'
            . $themeScope . ' footer a,' . $themeScope . ' .site-footer a{color:#fff!important}';
        if (stripos($html, '</head>') !== false) {
            $html = preg_replace('/<\\/head>/i', '<style id="villa-theme-palette-application">' . $paletteCss . '</style></head>', $html, 1) ?? $html;
        }

        echo $html;
    }

}
