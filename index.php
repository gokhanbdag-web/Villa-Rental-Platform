<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

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
$isAdminPage = str_starts_with($requestPath, '/admin/') && $requestPath !== '/admin/';
if ($isAdminPage) {
    ob_start(static function (string $html) use ($requestPath): string {
        $user = \\App\\Core\\Auth::user();
        $username = e((string)($user['username'] ?? 'Yönetici'));
        $links = [
            '/admin' => 'Genel Bakış',
            '/admin/properties' => 'Konaklamalar',
            '/admin/categories' => 'Kategoriler',
            '/admin/amenities' => 'Villa Özellikleri',
            '/admin/bookings' => 'Rezervasyonlar',
            '/admin/users' => 'Kullanıcılar',
            '/admin/vendors' => 'Ev Sahipleri',
            '/admin/reviews' => 'Yorumlar',
            '/admin/pages' => 'İçerik Sayfaları',
            '/admin/navigation' => 'Menü Yönetimi',
            '/admin/content' => 'Site İçerikleri',
            '/admin/settings' => 'Ayarlar',
            '/admin/translations' => 'Çeviriler',
        ];
        $nav = '';
        foreach ($links as $url => $label) {
            if ($url === '/admin') continue;
            $active = $requestPath === $url || str_starts_with($requestPath, $url . '/') || str_starts_with($requestPath, $url . '?');
            $nav .= '<a class="vr-admin-link' . ($active ? ' is-active' : '') . '" href="' . $url . '">' . e($label) . '</a>';
        }
        $shell = '<style>
            .vr-admin-header{font-family:Inter,system-ui,sans-serif;background:#fff;border-bottom:1px solid #e8ece9;padding:15px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;position:sticky;top:0;z-index:1000}
            .vr-admin-brand{font-size:17px;font-weight:800;color:#20352b;text-decoration:none}
            .vr-admin-user{font-size:13px;color:#65736a}
            .vr-admin-nav{font-family:Inter,system-ui,sans-serif;background:#fff;border-bottom:1px solid #e8ece9;padding:8px 18px;display:flex;gap:6px;overflow-x:auto;white-space:nowrap;position:sticky;top:54px;z-index:999}
            .vr-admin-link{display:inline-block;padding:9px 11px;border-radius:8px;text-decoration:none;color:#46544a;font-size:13px;font-weight:650}
            .vr-admin-link:hover,.vr-admin-link.is-active{background:#eaf3ed;color:#176b50}
            @media(max-width:600px){.vr-admin-header{padding:12px 15px}.vr-admin-nav{top:48px;padding:7px 9px}}
        </style><header class="vr-admin-header"><a class="vr-admin-brand" href="/admin">VillaStay · Yönetim</a><span class="vr-admin-user">' . $username . '</span></header><nav class="vr-admin-nav" aria-label="Yönetim menüsü">' . $nav . '</nav>';
        return preg_replace('/(<body\\b[^>]*>)/i', '$1' . $shell, $html, 1) ?? $html;
    });
}
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $requestPath);
