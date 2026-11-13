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
            'headings'=>[
                'family'=>'Manrope','size'=>46,'sizes'=>['h1'=>46,'h2'=>34,'h3'=>26,'h4'=>22,'h5'=>19,'h6'=>17],
                'line_heights'=>['h1'=>1.12,'h2'=>1.16,'h3'=>1.22,'h4'=>1.25,'h5'=>1.3,'h6'=>1.35],
                'color'=>'#263b31','weight'=>700,'style'=>'normal'
            ],
            'span'=>['family'=>'Inter','size'=>11,'line_height'=>1.2,'color'=>'#344039','weight'=>700,'style'=>'normal'],
            'paragraph'=>['family'=>'Inter','size'=>16,'line_height'=>1.7,'color'=>'#56635b','weight'=>400,'style'=>'normal'],
            'paragraph_links'=>['family'=>'Inter','size'=>16,'line_height'=>1.7,'color'=>'#176b50','weight'=>600,'style'=>'normal'],
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
            if ($size !== false) $clean[$group]['size'] = max(10, min(96, $size));
            // H1-H6 line heights are normalized independently below.
            if (array_key_exists('line_height', $clean[$group])) {
                $lineHeight = filter_var($row['line_height'] ?? $clean[$group]['line_height'], FILTER_VALIDATE_FLOAT);
                if ($lineHeight !== false) $clean[$group]['line_height'] = max(0.9, min(2.5, (float)$lineHeight));
            }
            $color = strtolower(trim((string)($row['color'] ?? $clean[$group]['color'])));
            if (preg_match('/^#[0-9a-f]{6}$/', $color)) $clean[$group]['color'] = $color;
            $weight = filter_var($row['weight'] ?? $clean[$group]['weight'], FILTER_VALIDATE_INT);
            if ($weight !== false && in_array($weight, $weights, true)) $clean[$group]['weight'] = $weight;
            $style = (string)($row['style'] ?? $clean[$group]['style']);
            if (in_array($style, ['normal','italic'], true)) $clean[$group]['style'] = $style;
        }
        // Keep existing theme settings compatible while allowing independent H1-H6 scales.
        $headingRow = is_array($input['headings'] ?? null) ? $input['headings'] : [];
        foreach (['h1','h2','h3','h4','h5','h6'] as $level) {
            $size = filter_var($headingRow['sizes'][$level] ?? $clean['headings']['sizes'][$level], FILTER_VALIDATE_INT);
            if ($size !== false) $clean['headings']['sizes'][$level] = max(12, min(120, $size));
            $lineHeight = filter_var($headingRow['line_heights'][$level] ?? $clean['headings']['line_heights'][$level], FILTER_VALIDATE_FLOAT);
            if ($lineHeight !== false) $clean['headings']['line_heights'][$level] = max(0.9, min(2.5, (float)$lineHeight));
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


    /** Theme-scoped villa card layout and typography settings. */
    private static function cardSettingsDefaults(): array
    {
        return ['columns_desktop'=>4,'columns_tablet'=>2,'columns_mobile'=>1,'grid_gap'=>22,'radius'=>22,'border_width'=>1,'border_color'=>'#e3e9e4','padding_x'=>20,'padding_y'=>19,'image_height'=>250,'image_height_mobile'=>205,'title_size'=>17,'title_weight'=>700,'title_line_height'=>1.32,'meta_size'=>12.5,'meta_line_height'=>1.5,'price_size'=>19,'price_weight'=>800,'rating_size'=>10,'rating_weight'=>400,'rating_note_size'=>9];
    }

    private static function normalizeCardSettings(array $input): array
    {
        $clean = self::cardSettingsDefaults();
        $integerRanges = ['columns_desktop'=>[2,5],'columns_tablet'=>[1,4],'columns_mobile'=>[1,2],'grid_gap'=>[8,48],'radius'=>[0,40],'border_width'=>[0,4],'padding_x'=>[8,32],'padding_y'=>[8,32],'image_height'=>[160,360],'image_height_mobile'=>[120,280],'title_size'=>[12,28],'title_weight'=>[300,900],'price_size'=>[12,32],'price_weight'=>[300,900],'rating_weight'=>[300,900]];
        foreach ($integerRanges as $key=>$range) {
            $value = filter_var($input[$key] ?? $clean[$key], FILTER_VALIDATE_INT);
            if ($value !== false) $clean[$key] = max($range[0], min($range[1], $value));
        }
        foreach (['title_line_height'=>[1.0,2.0],'meta_size'=>[9,18],'meta_line_height'=>[1.0,2.0],'rating_size'=>[8,14],'rating_note_size'=>[7,13]] as $key=>$range) {
            $value = filter_var($input[$key] ?? $clean[$key], FILTER_VALIDATE_FLOAT);
            if ($value !== false) $clean[$key] = max($range[0], min($range[1], (float)$value));
        }
        $color = strtolower(trim((string)($input['border_color'] ?? $clean['border_color'])));
        if (preg_match('/^#[0-9a-f]{6}$/', $color)) $clean['border_color'] = $color;
        return $clean;
    }

    public static function cardSettings(?string $slug = null): array
    {
        $slug = $slug ?: self::activeSlug();
        if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug)) $slug = 'default';
        try {
            $stmt = Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
            $stmt->execute(['theme.' . $slug . '.cards']);
            $saved = json_decode((string)($stmt->fetchColumn() ?: '{}'), true);
            return self::normalizeCardSettings(is_array($saved) ? $saved : []);
        } catch (\Throwable $e) {
            error_log('Theme card settings read failed: ' . $e->getMessage());
            return self::cardSettingsDefaults();
        }
    }

    /** Theme-scoped homepage trust-strip icon library and icon choices. */
    public static function iconSettings(?string $slug = null): array
    {
        $slug = $slug ?: self::activeSlug();
        if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug)) $slug = 'default';
        $clean = ['library'=>'fontawesome','icons'=>['fa-star','fa-house','fa-heart','fa-shield-halved']];
        try {
            $stmt = Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
            $stmt->execute(['theme.' . $slug . '.icons']);
            $saved = json_decode((string)($stmt->fetchColumn() ?: '{}'), true);
            if (is_array($saved)) {
                $library = (string)($saved['library'] ?? $clean['library']);
                if (in_array($library, ['fontawesome','bootstrap','material','lucide','unicode'], true)) $clean['library'] = $library;
                $icons = is_array($saved['icons'] ?? null) ? $saved['icons'] : [];
                foreach (range(0, 3) as $i) {
                    $value = trim((string)($icons[$i] ?? $clean['icons'][$i]));
                    $pattern = $clean['library'] === 'material' ? '/^[a-z0-9_]{1,50}$/' : ($clean['library'] === 'unicode' ? '/^.{1,8}$/u' : '/^[a-z0-9-]{1,60}$/');
                    if (preg_match($pattern, $value)) $clean['icons'][$i] = $value;
                }
            }
        } catch (\Throwable $e) {
            error_log('Theme icon settings read failed: ' . $e->getMessage());
        }
        return $clean;
    }

    public static function saveIconSettings(array $input, ?string $slug = null): void
    {
        $slug = $slug ?: self::activeSlug();
        if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug) || !isset(self::installed()[$slug])) throw new RuntimeException('Tema kodu geçersiz veya kurulu değil.');
        $library = (string)($input['library'] ?? 'fontawesome');
        if (!in_array($library, ['fontawesome','bootstrap','material','lucide','unicode'], true)) throw new RuntimeException('İkon kütüphanesi seçimi geçersiz.');
        $defaults = ['fontawesome'=>['fa-star','fa-house','fa-heart','fa-shield-halved'],'bootstrap'=>['bi-star-fill','bi-house-door-fill','bi-heart-fill','bi-shield-check'],'material'=>['star','home','favorite','verified_user'],'lucide'=>['star','house','heart','shield-check'],'unicode'=>['✦','⌂','♡','✓']];
        $submitted = is_array($input['icons'] ?? null) ? $input['icons'] : [];
        $icons = [];
        foreach (range(0, 3) as $i) {
            $value = trim((string)($submitted[$i] ?? $defaults[$library][$i]));
            $pattern = $library === 'material' ? '/^[a-z0-9_]{1,50}$/' : ($library === 'unicode' ? '/^.{1,8}$/u' : '/^[a-z0-9-]{1,60}$/');
            if (!preg_match($pattern, $value)) throw new RuntimeException('İkon adı geçersiz. Seçilen ikon kütüphanesinin adlandırmasını kullanın.');
            $icons[] = $value;
        }
        $json = json_encode(['library'=>$library,'icons'=>$icons], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $key = 'theme.' . $slug . '.icons';
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO settings(setting_key,setting_value,autoload) VALUES(?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
        $stmt->execute([$key, $json]);
        $verify = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
        $verify->execute([$key]);
        if ((string)$verify->fetchColumn() !== $json) throw new RuntimeException('Tema ikon ayarları veritabanına kaydedilemedi.');
    }

    public static function renderTrustIcon(int $index, array $settings): string
    {
        $library = (string)($settings['library'] ?? 'unicode');
        $icon = (string)($settings['icons'][$index] ?? '');
        if ($library === 'material') return '<span class="material-symbols-rounded" aria-hidden="true">' . e($icon) . '</span>';
        if ($library === 'unicode') return e($icon);
        if ($library === 'lucide') return '<i data-lucide="' . e($icon) . '" aria-hidden="true"></i>';
        $prefix = $library === 'bootstrap' ? 'bi ' : 'fa-solid ';
        return '<i class="' . $prefix . e($icon) . '" aria-hidden="true"></i>';
    }

    /** Independent typography and color controls for the homepage hero. */
    private static function heroTypographyDefaults(): array
    {
        return [
            'eyebrow'=>['family'=>'Inter','size'=>12,'line_height'=>1.2,'color'=>'#ffffff','weight'=>800,'style'=>'normal'],
            'title'=>['family'=>'Playfair Display','size'=>68,'line_height'=>1.08,'color'=>'#ffffff','weight'=>600,'style'=>'normal'],
            'description'=>['family'=>'Inter','size'=>18,'line_height'=>1.6,'color'=>'#f4f7f4','weight'=>400,'style'=>'normal'],
            'button'=>['family'=>'Inter','size'=>15,'line_height'=>1.2,'color'=>'#ffffff','weight'=>700,'style'=>'normal'],
            'eyebrow_background'=>'#176b50','button_background'=>'#176b50',
        ];
    }
    private static function normalizeHeroTypography(array $input): array
    {
        $clean=self::heroTypographyDefaults(); $weights=[300,400,500,600,700,800,900];
        foreach(['eyebrow','title','description','button'] as $group){
            $row=is_array($input[$group]??null)?$input[$group]:[];
            $family=(string)($row['family']??$clean[$group]['family']);
            if(isset(self::typographyFonts()[$family]))$clean[$group]['family']=$family;
            $size=filter_var($row['size']??$clean[$group]['size'],FILTER_VALIDATE_INT);
            if($size!==false)$clean[$group]['size']=max(10,min(96,$size));
            $lineHeight=filter_var($row['line_height']??$clean[$group]['line_height'],FILTER_VALIDATE_FLOAT);
            if($lineHeight!==false)$clean[$group]['line_height']=max(0.9,min(2.5,(float)$lineHeight));
            $color=strtolower(trim((string)($row['color']??$clean[$group]['color'])));
            if(preg_match('/^#[0-9a-f]{6}$/',$color))$clean[$group]['color']=$color;
            $weight=filter_var($row['weight']??$clean[$group]['weight'],FILTER_VALIDATE_INT);
            if($weight!==false&&in_array($weight,$weights,true))$clean[$group]['weight']=$weight;
            $style=(string)($row['style']??$clean[$group]['style']);
            if(in_array($style,['normal','italic'],true))$clean[$group]['style']=$style;
        }
        foreach(['eyebrow_background','button_background'] as $key){
            $color=strtolower(trim((string)($input[$key]??$clean[$key])));
            if(preg_match('/^#[0-9a-f]{6}$/',$color))$clean[$key]=$color;
        }
        return $clean;
    }
    public static function heroTypographySettings(?string $slug=null): array
    {
        $slug=$slug?:self::activeSlug();
        if(!preg_match('/^[a-z0-9_-]{1,60}$/',$slug))$slug='default';
        try{$stmt=Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');$stmt->execute(['theme.'.$slug.'.hero_typography']);$saved=json_decode((string)($stmt->fetchColumn()?:'{}'),true);return self::normalizeHeroTypography(is_array($saved)?$saved:[]);}
        catch (\Throwable $e){error_log('Hero typography settings read failed: '.$e->getMessage());return self::heroTypographyDefaults();}
    }
    public static function saveHeroTypographySettings(array $input,?string $slug=null): void
    {
        $slug=$slug?:self::activeSlug();
        if(!preg_match('/^[a-z0-9_-]{1,60}$/',$slug)||!isset(self::installed()[$slug]))throw new RuntimeException('Tema kodu geçersiz veya kurulu değil.');
        $clean=self::normalizeHeroTypography($input);$key='theme.'.$slug.'.hero_typography';$json=json_encode($clean,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$pdo=Database::connection();
        $stmt=$pdo->prepare('INSERT INTO settings(setting_key,setting_value,autoload) VALUES(?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');$stmt->execute([$key,$json]);
        $verify=$pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');$verify->execute([$key]);
        if((string)$verify->fetchColumn()!==$json)throw new RuntimeException('Hero yazı ayarları kaydedilemedi.');
    }

    /** Theme-scoped design palette stored in the database, never in editable CMS content. */
    private static function designDefaults(): array
    {
        return ['primary'=>'#176b50','accent'=>'#ef8069','surface'=>'#ffffff','background'=>'#f5f8f6','text'=>'#20352f','muted'=>'#71817a','border'=>'#e2eae5','eyebrow_background'=>'#ffffff','eyebrow_text'=>'#26352e','image_overlay_text'=>'#ffffff','image_overlay_muted'=>'#e8edf2'];
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

    public static function saveCardSettings(array $input, ?string $slug = null): void
    {
        $slug = $slug ?: self::activeSlug();
        if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug)) throw new RuntimeException('Tema kodu geçersiz.');
        $clean = self::normalizeCardSettings($input);
        $key = 'theme.' . $slug . '.cards';
        $json = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO settings(setting_key,setting_value,autoload) VALUES(?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
        $stmt->execute([$key, $json]);
        $verify = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
        $verify->execute([$key]);
        if ((string)$verify->fetchColumn() !== $json) throw new RuntimeException('Villa kart ayarları veritabanına kaydedilemedi.');
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
        $variables['trustIconSettings'] = self::iconSettings($renderSlug);
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
                body.villa-home-mediterranean .hero-copy h1{max-width:780px}
                body.villa-home-mediterranean .hero-copy p{max-width:650px}
                body.villa-home-mediterranean .hero-filter-panel{width:100%;max-width:420px;justify-self:end;padding:22px;background:rgba(255,255,255,.97);border:1px solid rgba(255,255,255,.7);border-radius:22px;box-shadow:0 24px 70px rgba(0,0,0,.24);backdrop-filter:blur(12px)}
                body.villa-home-mediterranean .hero-filter-panel .search-enhanced{display:grid;grid-template-columns:minmax(0,1fr);gap:0;width:100%;max-width:none;padding:0;border:0;border-radius:0;box-shadow:none;background:transparent}
                body.villa-home-mediterranean .hero-filter-panel .search-field{min-height:67px;padding:10px 3px;border-right:0;border-bottom:1px solid #e8eeea}
                body.villa-home-mediterranean .hero-filter-panel .search-submit{width:100%;min-height:54px;margin-top:15px;border-radius:12px}
                @media(max-width:900px){body.villa-home-mediterranean .hero-card{min-height:0;padding:54px 28px}body.villa-home-mediterranean .hero-inner{grid-template-columns:minmax(0,1.3fr) minmax(270px,.9fr);gap:24px}}
                @media(max-width:700px){body.villa-home-mediterranean .theme-header-inner{width:calc(100% - 28px)}.theme-brand-logo{max-width:160px;max-height:43px}body.villa-home-mediterranean .hero-card{padding:45px 18px 30px;min-height:0;gap:18px}body.villa-home-mediterranean .hero-filter-panel{max-width:none;justify-self:stretch;padding:16px;border-radius:17px}}
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
            . ';--villa-text:' . $design['text'] . ';--villa-muted:' . $design['muted'] . ';--villa-border:' . $design['border'] . ';--villa-eyebrow_background:' . $design['eyebrow_background'] . ';--villa-eyebrow_text:' . $design['eyebrow_text'] . ';--villa-image_overlay_text:' . $design['image_overlay_text'] . ';--villa-image_overlay_muted:' . $design['image_overlay_muted'] . ';'
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
        // Build each heading rule as one valid CSS rule. Keep hero H1 isolated so its
        // dedicated controls below remain authoritative.
        $headingRules = '';
        foreach (['h1','h2','h3','h4','h5','h6'] as $level) {
            $levelSize = (int)$heading['sizes'][$level];
            $levelLineHeight = rtrim(rtrim(number_format((float)$heading['line_heights'][$level], 2, '.', ''), '0'), '.');
            $headingSelector = $themeScope . ' ' . $level;
            if ($level === 'h1') $headingSelector .= ':not(.hero-copy h1)';
            $headingRules .= $headingSelector . '{'
                . 'font-family:' . json_encode($heading['family']) . ',system-ui,sans-serif!important;'
                . 'font-size:' . $levelSize . 'px!important;'
                . 'line-height:' . $levelLineHeight . '!important;'
                . 'color:' . $heading['color'] . '!important;'
                . 'font-weight:' . (int)$heading['weight'] . '!important;'
                . 'font-style:' . $heading['style'] . '!important;'
                . 'letter-spacing:-.035em!important;text-wrap:balance}';
        }
        $typographyCss = $themeScope . '{'
            . '--villa-font-body:' . json_encode($paragraph['family']) . ';'
            . '--villa-body-size:' . (int)$paragraph['size'] . 'px;'
            . '--villa-body-line-height:' . $paragraph['line_height'] . ';'
            . '--villa-body-weight:' . (int)$paragraph['weight'] . ';'
            . '--villa-body-style:' . $paragraph['style'] . ';'
            . '--villa-font-heading:' . json_encode($heading['family']) . ';'
            . '--villa-text-color:' . $design['text'] . ';'
            . 'font-family:' . json_encode($paragraph['family']) . ',system-ui,sans-serif!important;'
            . 'font-size:' . (int)$paragraph['size'] . 'px!important;'
            . 'line-height:' . $paragraph['line_height'] . '!important;'
            . 'color:var(--villa-text)!important;'
            . 'font-weight:' . (int)$paragraph['weight'] . '!important;'
            . 'font-style:' . $paragraph['style'] . '!important}'
            . $themeScope . ' .villa-social-inner{min-height:' . (int)$typography['topbar_height'] . 'px}'
            . $headingRules
            . $themeScope . ' p:not(.hero-copy p){'
            . 'font-family:' . json_encode($paragraph['family']) . ',system-ui,sans-serif!important;'
            . 'font-size:' . (int)$paragraph['size'] . 'px!important;'
            . 'color:' . $paragraph['color'] . '!important;'
            . 'font-weight:' . (int)$paragraph['weight'] . '!important;'
            . 'font-style:' . $paragraph['style'] . '!important;'
            . 'line-height:' . $paragraph['line_height'] . '!important;letter-spacing:0!important}'
            . $themeScope . ' .villa-eyebrow,'
            . $themeScope . ' .eyebrow:not(.villa-hero-eyebrow),'
            . $themeScope . ' .section-eyebrow,'
            . $themeScope . ' .pill,'
            . $themeScope . ' .section-head .eyebrow,'
            . $themeScope . ' .med-faq-intro .eyebrow{'
            . 'font-family:' . json_encode($span['family']) . ',system-ui,sans-serif!important;'
            . 'font-size:' . (int)$span['size'] . 'px!important;'
            . 'font-weight:' . (int)$span['weight'] . '!important;'
            . 'font-style:' . $span['style'] . '!important;'
            . 'letter-spacing:.075em!important;line-height:' . $span['line_height'] . '!important}'
            . $themeScope . ' p a:not(.button):not(.btn):not([class*="button"]):not([class*="btn"]):not([class*="cta"]):not(.search-submit):not([role="button"]){'
            . 'font-family:' . json_encode($paragraphLinks['family']) . ',system-ui,sans-serif!important;'
            . 'font-size:inherit!important;line-height:' . $paragraphLinks['line_height'] . '!important;'
            . 'color:' . $paragraphLinks['color'] . '!important;'
            . 'font-weight:' . (int)$paragraphLinks['weight'] . '!important;'
            . 'font-style:' . $paragraphLinks['style'] . '!important;'
            . 'text-decoration-thickness:1px;text-underline-offset:3px}';
        $heroTypography = self::heroTypographySettings($renderSlug);
        $heroFontQuery = [];
        foreach (['eyebrow','title','description','button'] as $heroGroup) {
            $family = (string)$heroTypography[$heroGroup]['family'];
            if (!isset($fontFamilies[$family])) {
                $fontFamilies[$family] = true;
                $encodedFamily = str_replace('%20', '+', rawurlencode($family));
                $heroFontQuery[] = 'family=' . $encodedFamily . ':ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
            }
        }
        $heroTypographyCss = $themeScope . ' .hero-copy .villa-hero-eyebrow{font-family:' . json_encode($heroTypography['eyebrow']['family']) . '!important;font-size:' . (int)$heroTypography['eyebrow']['size'] . 'px!important;color:' . $heroTypography['eyebrow']['color'] . '!important;font-weight:' . (int)$heroTypography['eyebrow']['weight'] . '!important;font-style:' . $heroTypography['eyebrow']['style'] . '!important;line-height:' . $heroTypography['eyebrow']['line_height'] . '!important;background:' . $heroTypography['eyebrow_background'] . '!important;border-color:' . $heroTypography['eyebrow_background'] . '!important}'
            . $themeScope . ' .hero-copy h1{font-family:' . json_encode($heroTypography['title']['family']) . '!important;font-size:clamp(' . min(38,(int)$heroTypography['title']['size']) . 'px,5.4vw,' . (int)$heroTypography['title']['size'] . 'px)!important;color:' . $heroTypography['title']['color'] . '!important;font-weight:' . (int)$heroTypography['title']['weight'] . '!important;font-style:' . $heroTypography['title']['style'] . '!important;line-height:' . $heroTypography['title']['line_height'] . '!important}'
            . $themeScope . ' .hero-copy p{font-family:' . json_encode($heroTypography['description']['family']) . '!important;font-size:' . (int)$heroTypography['description']['size'] . 'px!important;color:' . $heroTypography['description']['color'] . '!important;font-weight:' . (int)$heroTypography['description']['weight'] . '!important;font-style:' . $heroTypography['description']['style'] . '!important;line-height:' . $heroTypography['description']['line_height'] . '!important}'
            . $themeScope . ' .hero .search-submit,' . $themeScope . ' .hero button[type="submit"]{font-family:' . json_encode($heroTypography['button']['family']) . '!important;font-size:' . (int)$heroTypography['button']['size'] . 'px!important;color:' . $heroTypography['button']['color'] . '!important;font-weight:' . (int)$heroTypography['button']['weight'] . '!important;font-style:' . $heroTypography['button']['style'] . '!important;line-height:' . $heroTypography['button']['line_height'] . '!important;background:' . $heroTypography['button_background'] . '!important;border-color:' . $heroTypography['button_background'] . '!important}';
        $heroFontAssets = ($heroFontQuery ? '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?' . e(implode('&', $heroFontQuery)) . '&amp;display=swap">' : '') . '<style id="villa-theme-hero-typography">' . $heroTypographyCss . '</style>';
                $typographyAssets = $fontAssets . '<style id="villa-theme-typography">' . $typographyCss . '</style>';
        if (stripos($html, '</head>') !== false) {
            $html = preg_replace('/<\/head>/i', $typographyAssets . $heroFontAssets . '</head>', $html, 1) ?? $html;
        }

        $iconLibrary = (string)($trustIconSettings['library'] ?? 'unicode');
        $iconAssets = '';
        if ($iconLibrary === 'fontawesome') {
            $iconAssets = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">';
        } elseif ($iconLibrary === 'bootstrap') {
            $iconAssets = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">';
        } elseif ($iconLibrary === 'material') {
            $iconAssets = '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0">';
        } elseif ($iconLibrary === 'lucide') {
            $iconAssets = '<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script><script>window.addEventListener("DOMContentLoaded",function(){if(window.lucide){window.lucide.createIcons();}});</script>';
        }
        if ($view === 'landing.php' && $iconAssets !== '' && stripos($html, '</head>') !== false) {
            $html = preg_replace('/<\\/head>/i', $iconAssets . '</head>', $html, 1) ?? $html;
        }

        // Apply the saved palette after theme and typography CSS so fixed theme colors cannot override it.
        $paletteCss = $themeScope . '{color-scheme:light}'
            . $themeScope . '{background:var(--villa-background)!important;color:var(--villa-text)!important}'
            . $themeScope . ' .theme-header,' . $themeScope . ' .site-header,' . $themeScope . ' .villa-social-bar,' . $themeScope . ' .theme-header .nav{background:var(--villa-surface)!important;border-color:var(--villa-border)!important}'
            . $themeScope . ' .hero,' . $themeScope . ' .intro,' . $themeScope . ' .section,' . $themeScope . ' .home-accommodations{background-color:var(--villa-background)!important}'
            . $themeScope . ' .stay,' . $themeScope . ' .premium-stay-card,' . $themeScope . ' .filter-panel,' . $themeScope . ' .booking,' . $themeScope . ' .panel,' . $themeScope . ' .card,' . $themeScope . ' .med-review-card,' . $themeScope . ' .med-support-panel{background:var(--villa-surface)!important;border-color:var(--villa-border)!important;color:var(--villa-text)!important}'
            . $themeScope . ' .theme-brand,' . $themeScope . ' .theme-brand-name,' . $themeScope . ' .brand,' . $themeScope . ' .section-head h2,' . $themeScope . ' .section h2,' . $themeScope . ' .stay h3,' . $themeScope . ' .med-collection-heading h3,' . $themeScope . ' .med-faq-item summary,' . $themeScope . ' .med-support h2{color:var(--villa-text)!important}'
            . $themeScope . ' .theme-header .nav a,' . $themeScope . ' .theme-header .nav-submenu-toggle,' . $themeScope . ' .villa-social-bar,' . $themeScope . ' .med-property-body,' . $themeScope . ' .med-review-card>p{color:var(--villa-text)!important}'
            . $themeScope . ' .muted,' . $themeScope . ' .meta,' . $themeScope . ' .crumb,' . $themeScope . ' .section-head p,' . $themeScope . ' .med-property-place,' . $themeScope . ' .med-property-meta,' . $themeScope . ' .med-property-price,' . $themeScope . ' .med-collection-heading p,' . $themeScope . ' .med-faq-intro p,' . $themeScope . ' .med-support p{color:var(--villa-muted)!important}'
            . $themeScope . ' .search-submit,' . $themeScope . ' .submit,' . $themeScope . ' .button-primary,' . $themeScope . ' .med-support-whatsapp,' . $themeScope . ' .pagination .current{background:var(--villa-primary)!important;border-color:var(--villa-primary)!important;color:#fff!important}'
            . $themeScope . ' a:not(.search-submit):not(.button-primary):not(.med-support-whatsapp),' . $themeScope . ' .brand{--link-color:var(--villa-primary)}'
            . $themeScope . ' .theme-header .nav a:hover,' . $themeScope . ' .theme-header .nav a[aria-current="page"],' . $themeScope . ' .pill,' . $themeScope . ' .med-trust-icon,' . $themeScope . ' .rating-score{background:var(--villa-accent)!important;color:var(--villa-text)!important}'
            . $themeScope . ' input,' . $themeScope . ' select,' . $themeScope . ' textarea,' . $themeScope . ' .search-enhanced,' . $themeScope . ' .search-field,' . $themeScope . ' .med-faq-item{border-color:var(--villa-border)!important}'
            . $themeScope . ' footer,' . $themeScope . ' .site-footer{background:var(--villa-primary)!important;border-color:var(--villa-primary)!important;color:#fff!important}'
            . $themeScope . ' footer a,' . $themeScope . ' .site-footer a{color:#fff!important}'

            . $themeScope . ' .theme-header .nav a,' . $themeScope . ' .theme-header .nav-submenu-toggle{color:var(--villa-primary)!important}'
            . $themeScope . ' .theme-header .nav a:hover,' . $themeScope . ' .theme-header .nav a:focus-visible,' . $themeScope . ' .theme-header .nav a[aria-current="page"],' . $themeScope . ' .theme-header .nav-entry.is-open>.nav-entry-top>a{background:var(--villa-primary)!important;color:#fff!important}'
            . $themeScope . ' .theme-header .nav-dropdown{border-color:var(--villa-primary)!important}'
            . $themeScope . ' .theme-header .nav-dropdown a:hover,' . $themeScope . ' .theme-header .nav-dropdown a:focus-visible{background:var(--villa-primary)!important;color:#fff!important}'
            . $themeScope . ' .search-submit,' . $themeScope . ' .submit,' . $themeScope . ' .button-primary,' . $themeScope . ' .button,' . $themeScope . ' .btn,' . $themeScope . ' .btn-primary,' . $themeScope . ' .cta,' . $themeScope . ' button[type="submit"],' . $themeScope . ' input[type="submit"],' . $themeScope . ' .med-support-whatsapp,' . $themeScope . ' .pagination .current,' . $themeScope . ' .premium-stay-card .card-gallery-arrow{background:var(--villa-primary)!important;border:1px solid var(--villa-primary)!important;color:#fff!important}'
            . $themeScope . ' .med-trust-icon,' . $themeScope . ' .trust-icon,' . $themeScope . ' .feature-icon,' . $themeScope . ' .icon-circle{background:var(--villa-primary)!important;border-color:var(--villa-primary)!important;color:#fff!important}'
            . $themeScope . ' .premium-stay-card .card-gallery-arrow:hover,' . $themeScope . ' .premium-stay-card .card-gallery-arrow:focus-visible{background:var(--villa-primary)!important;color:#fff!important;filter:brightness(.92)}'
            . $themeScope . ' .theme-menu-toggle{background:var(--villa-primary)!important;border-color:var(--villa-primary)!important;color:#fff!important}'
            . $themeScope . ' input:focus,' . $themeScope . ' select:focus,' . $themeScope . ' textarea:focus,' . $themeScope . ' .search-enhanced:focus-within{border-color:var(--villa-primary)!important;outline-color:var(--villa-primary)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--villa-primary) 16%,transparent)!important}'
            . $themeScope . ' .stay:hover,' . $themeScope . ' .premium-stay-card:hover,' . $themeScope . ' .card:hover{border-color:color-mix(in srgb,var(--villa-primary) 48%,var(--villa-border))!important}'
            . $themeScope . ' .pagination a{border-color:var(--villa-primary)!important;color:var(--villa-primary)!important}'
            . $themeScope . ' .villa-eyebrow,' . $themeScope . ' .pill,' . $themeScope . ' .eyebrow,' . $themeScope . ' .section-eyebrow,' . $themeScope . ' .section-head .eyebrow,' . $themeScope . ' .med-faq-intro .eyebrow{display:inline-flex;align-items:center;width:fit-content;padding:7px 13px;border-radius:999px;background:var(--villa-eyebrow_background)!important;border:1px solid var(--villa-eyebrow_background)!important;color:var(--villa-eyebrow_text)!important;}'
            . $themeScope . ' .section-head>div{display:flex;flex-direction:column;align-items:flex-start;text-align:left}'
            . $themeScope . ' .section-head>div>.villa-eyebrow,' . $themeScope . ' .section-head>div>.pill,' . $themeScope . ' .med-faq-intro>.villa-eyebrow{align-self:flex-start;margin-left:0!important;margin-right:0!important}'
            . $themeScope . ' .section-head>div>h2,' . $themeScope . ' .section-head>div>p,' . $themeScope . ' .med-faq-intro>h2,' . $themeScope . ' .med-faq-intro>p{text-align:left;margin-left:0!important;margin-right:0!important}'
            . $themeScope . ' .premium-stay-card .card-gallery-count,' . $themeScope . ' .premium-stay-card .stay-badge{color:var(--villa-image_overlay_text)!important}'
            . $themeScope . ' .premium-stay-card .card-gallery-count,' . $themeScope . ' .premium-stay-card .stay-badge{background:rgba(8,29,25,.72)!important;border-color:rgba(255,255,255,.25)!important}'
            . $themeScope . ' .pagination a:hover{background:var(--villa-primary)!important;color:#fff!important}'
            . $themeScope . ' .destination-label,' . $themeScope . ' .destination-label :is(h1,h2,h3,h4,h5,h6,p,span,strong,small,a),' . $themeScope . ' .style-card,' . $themeScope . ' .style-card :is(h1,h2,h3,h4,h5,h6,p,span,strong,small,a),' . $themeScope . ' .media-overlay,' . $themeScope . ' .media-overlay :is(h1,h2,h3,h4,h5,h6,p,span,strong,small,a),' . $themeScope . ' .image-overlay,' . $themeScope . ' .image-overlay :is(h1,h2,h3,h4,h5,h6,p,span,strong,small,a),' . $themeScope . ' .gallery-overlay,' . $themeScope . ' .gallery-overlay :is(h1,h2,h3,h4,h5,h6,p,span,strong,small,a),' . $themeScope . ' [data-text-on-media],' . $themeScope . ' [data-text-on-media] :is(h1,h2,h3,h4,h5,h6,p,span,strong,small,a){color:var(--villa-image_overlay_text)!important}'
            . $themeScope . ' .med-concept-copy,' . $themeScope . ' .med-concept-copy strong,' . $themeScope . ' .med-concept-copy small{color:var(--villa-image_overlay_text,#fff)!important;text-shadow:0 2px 12px rgba(0,0,0,.68)!important}'
            . $themeScope . ' .med-concept-copy strong{font-family:var(--villa-font-heading,var(--villa-font-body,system-ui,sans-serif))!important;font-weight:750!important}'
            . $themeScope . ' .med-concept-copy small{color:var(--villa-image_overlay_muted,#f1f4ef)!important;font-weight:500!important}'
            . $themeScope . ' .destination-label small,' . $themeScope . ' .media-overlay p,' . $themeScope . ' .image-overlay p,' . $themeScope . ' .gallery-overlay p,' . $themeScope . ' [data-text-on-media] p{color:var(--villa-image_overlay_muted)!important}'
            . $themeScope . ' .premium-stay-card .card-gallery-count,' . $themeScope . ' .premium-stay-card .stay-badge{color:var(--villa-image_overlay_text)!important}'
            . $themeScope . ' .premium-stay-card .card-gallery-count small,' . $themeScope . ' .premium-stay-card .stay-badge small{color:var(--villa-image_overlay_muted)!important}';
        $cardSettings = self::cardSettings($renderSlug);
        $paletteCss .=
            $themeScope . ' .premium-stay-card{border-radius:' . (int)$cardSettings['radius'] . 'px!important;border:' . (int)$cardSettings['border_width'] . 'px solid ' . $cardSettings['border_color'] . '!important}'
            . $themeScope . ' .premium-stay-card .stay-content{padding:' . (int)$cardSettings['padding_y'] . 'px ' . (int)$cardSettings['padding_x'] . 'px!important}'
            . $themeScope . ' .premium-stay-card .card-gallery{height:' . (int)$cardSettings['image_height'] . 'px!important}'
            . $themeScope . ' .premium-stay-card .stay-content h3{font-size:' . (int)$cardSettings['title_size'] . 'px!important;font-weight:' . (int)$cardSettings['title_weight'] . '!important;line-height:' . $cardSettings['title_line_height'] . '!important}'
            . $themeScope . ' .premium-stay-card .stay-content .meta{font-size:' . $cardSettings['meta_size'] . 'px!important;line-height:' . $cardSettings['meta_line_height'] . '!important}'
            . $themeScope . ' .premium-stay-card .card-bottom-row .price strong{font-size:' . (int)$cardSettings['price_size'] . 'px!important;font-weight:' . (int)$cardSettings['price_weight'] . '!important}'
            . $themeScope . ' .premium-stay-card .card-bottom-row .rating-line{font-size:' . $cardSettings['rating_size'] . 'px!important;font-weight:' . (int)$cardSettings['rating_weight'] . '!important}'
            . $themeScope . ' .premium-stay-card .card-bottom-row .rating-line>span{font-size:inherit!important}'
            . $themeScope . ' .premium-stay-card .card-bottom-row .rating-line>span:last-child{font-size:' . $cardSettings['rating_note_size'] . 'px!important}'
            . $themeScope . ' .cards:has(> .premium-stay-card),' . $themeScope . ' .grid:has(> .premium-stay-card),' . $themeScope . ' .home-accommodations .cards,' . $themeScope . ' .med-collection-row .cards,' . $themeScope . ' .med-property-grid,' . $themeScope . ' .med-property-grid--compact{display:grid!important;grid-template-columns:repeat(' . (int)$cardSettings['columns_desktop'] . ',minmax(0,1fr))!important;gap:' . (int)$cardSettings['grid_gap'] . 'px!important}'
            . '@media(max-width:900px){' . $themeScope . ' .cards:has(> .premium-stay-card),' . $themeScope . ' .grid:has(> .premium-stay-card),' . $themeScope . ' .home-accommodations .cards,' . $themeScope . ' .med-collection-row .cards,' . $themeScope . ' .med-property-grid,' . $themeScope . ' .med-property-grid--compact{grid-template-columns:repeat(' . (int)$cardSettings['columns_tablet'] . ',minmax(0,1fr))!important}}'
            . '@media(max-width:600px){' . $themeScope . ' .cards:has(> .premium-stay-card),' . $themeScope . ' .grid:has(> .premium-stay-card),' . $themeScope . ' .home-accommodations .cards,' . $themeScope . ' .med-collection-row .cards,' . $themeScope . ' .med-property-grid,' . $themeScope . ' .med-property-grid--compact{grid-template-columns:repeat(' . (int)$cardSettings['columns_mobile'] . ',minmax(0,1fr))!important;gap:' . max(8,(int)$cardSettings['grid_gap']-8) . 'px!important}' . $themeScope . ' .premium-stay-card .card-gallery{height:' . (int)$cardSettings['image_height_mobile'] . 'px!important}}';
        if (stripos($html, '</head>') !== false) {
            $html = preg_replace('/<\\/head>/i', '<style id="villa-theme-palette-application">' . $paletteCss . '</style></head>', $html, 1) ?? $html;
        }

        echo $html;
    }

}
