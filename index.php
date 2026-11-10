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
$isAdminPage = str_starts_with($requestPath, '/admin/') && $requestPath !== '/admin/';
if ($isAdminPage) {
    ob_start(static function (string $html) use ($requestPath): string {
        $user = Auth::user();
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
            $active = $requestPath === $url || str_starts_with($requestPath, $url . '/');
            $nav .= '<a class="vr-admin-link' . ($active ? ' is-active' : '') . '" href="' . $url . '">' .
                '<span>' . e($label) . '</span></a>';
        }

        $shell = '<style>
            body{margin:0!important;background:#f7f7f7!important;color:#222;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif!important}
            .vr-admin-top{height:70px;background:#fff;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between;padding:0 28px;gap:18px;position:sticky;top:0;z-index:1000}
            .vr-admin-brand{font-size:17px;font-weight:800;color:#20352b;text-decoration:none}
            .vr-admin-user{font-size:13px;color:#65736a}
            .vr-admin-layout{display:grid;grid-template-columns:240px minmax(0,1fr);min-height:calc(100vh - 70px)}
            .vr-admin-sidebar{background:#fff;border-right:1px solid #eee;padding:24px 16px;display:flex;flex-direction:column;gap:4px}
            .vr-admin-link{display:block;padding:11px 12px;color:#444;text-decoration:none;border-radius:8px;font-size:14px;font-weight:550;transition:background .15s,color .15s}
            .vr-admin-link:hover{background:#f5f6f5;color:#176b50}
            .vr-admin-link.is-active{background:#fff0f1;color:#b73545;font-weight:700}
            .vr-admin-content{min-width:0;padding:30px}
            .vr-admin-content .wrap{max-width:none!important;margin:0!important;padding:0!important}
            .vr-admin-content h1{margin-top:0}
            .vr-admin-content table{max-width:100%}
            @media(max-width:800px){.vr-admin-layout{grid-template-columns:1fr}.vr-admin-sidebar{flex-direction:row;overflow-x:auto;white-space:nowrap;border-right:0;border-bottom:1px solid #eee;padding:10px;position:sticky;top:70px;z-index:900}.vr-admin-link{flex:0 0 auto}.vr-admin-content{padding:18px}}
            @media(max-width:480px){.vr-admin-top{height:60px;padding:0 15px}.vr-admin-sidebar{top:60px}.vr-admin-content{padding:14px}}
        </style><header class="vr-admin-top"><a class="vr-admin-brand" href="/admin">VillaStay · Yönetim</a><span class="vr-admin-user">' . $username . '</span></header><div class="vr-admin-layout"><aside class="vr-admin-sidebar" aria-label="Yönetim menüsü">' . $nav . '</aside><div class="vr-admin-content">';
        $closing = '</div></div>';
        return preg_replace_callback('/(<body\b[^>]*>)(.*?)(<\/body>)/is', static function (array $matches) use ($shell, $closing): string {
            return $matches[1] . $shell . $matches[2] . $closing . $matches[3];
        }, $html, 1) ?? $html;
    });
}
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $requestPath);
