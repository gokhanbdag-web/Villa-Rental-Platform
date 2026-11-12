<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;

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
        $renderSocialIcon = static fn(string $platform, string $family): string => self::renderSocialIcon($platform, $family);
        ob_start();
        require APP_ROOT . '/themes/default/partials/shared-topbar.php';
        $socialBar = (string)ob_get_clean();
        if (trim($socialBar) !== '' && stripos($html, 'villa-social-bar') === false) {
            if (preg_match('~<div class="med-topbar"~i', $html)) {
                $html = preg_replace('~<div class="med-topbar"~i', $socialBar . '<div class="med-topbar"', $html, 1) ?? $html;
            } else {
                $html = preg_replace('~<header\\b~i', $socialBar . '<header', $html, 1) ?? $html;
            }
        }

        $css = $themeDir . '/style.css';
        if (is_file($css)) {
            $tag = '<link rel="stylesheet" href="' . e(app_url('/theme-assets/' . rawurlencode($renderSlug) . '/style.css')) . '?v=' . rawurlencode((string)filemtime($css)) . '">';
            if (stripos($html, '</head>') !== false) $html = preg_replace('/<\/head>/i', $tag . '</head>', $html, 1) ?? $html;
            $html = preg_replace('/<body\b/i', '<body data-villa-theme="' . e($renderSlug) . '"', $html, 1) ?? $html;
        }
        echo $html;
    }

}
