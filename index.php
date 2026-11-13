<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\Installer;
use App\Core\Migrator;
use App\Core\Router;
use App\Core\Translator;
use App\Core\ThemeManager;

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
        return ThemeManager::wrapAdminHtml($html, $requestPath, $platformMode);
    });
}
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $requestPath);
