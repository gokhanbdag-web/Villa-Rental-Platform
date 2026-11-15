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

$GLOBALS['villa_perf_stage'] = 'installer_check';
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

$GLOBALS['villa_perf_stage'] = 'locale_and_settings';
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

$requestedPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = app_base_path();

// Prefer the literal URL first. This matters when a valid application route
// (such as /villa/{slug}) has the same prefix as the physical install folder.
// Keep both URL forms; exact routes are prioritized across both forms below.
$pathCandidates = [$requestedPath];
if ($basePath !== '' && ($requestedPath === $basePath || str_starts_with($requestedPath, $basePath . '/'))) {
    $strippedPath = substr($requestedPath, strlen($basePath)) ?: '/';
    if ($strippedPath !== $requestedPath) {
        $pathCandidates[] = $strippedPath;
    }
}

$platformMode = 'single_vendor';
try {
    $modeStmt = Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $modeStmt->execute(['app.mode']);
    $platformMode = (string)($modeStmt->fetchColumn() ?: 'single_vendor');
} catch (Throwable $e) {
    error_log('Platform mode lookup failed: ' . $e->getMessage());
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Across all URL representations, prefer literal routes before parameterized
// routes. For example, /villa/register must resolve to /register on a
// /villa subdirectory install, rather than treating "register" as a villa slug.
$exactCandidates = [];
$dynamicCandidates = [];
foreach ($pathCandidates as $candidate) {
    if ($router->hasExactRoute($method, $candidate)) {
        $exactCandidates[] = $candidate;
    } else {
        $dynamicCandidates[] = $candidate;
    }
}
$pathCandidates = array_merge($exactCandidates, $dynamicCandidates);

foreach ($pathCandidates as $requestPath) {
    // Vendor-only endpoints must not be reachable in single-vendor installs.
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

    $GLOBALS['villa_perf_stage'] = 'dispatch:' . $method . ':' . substr($requestPath, 0, 120);
    if ($router->dispatch($method, $requestPath)) {
        // A matching route owns the response; do not try another path.
        exit;
    }

    // No route matched this candidate. Discard any empty admin output buffer
    // before trying the alternate path representation.
    if ($isAdminPage && ob_get_level() > 0) {
        ob_end_clean();
    }
}

http_response_code(404);
echo 'Sayfa bulunamadı.';
