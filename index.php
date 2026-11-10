<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Installer;
use App\Core\Router;
use App\Core\Translator;

if (!Installer::isInstalled()) {
    header('Location: /install/');
    exit;
}

$locale = 'tr';
try {
    $stmt = Database::connection()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $stmt->execute(['app.locale']);
    $locale = (string)($stmt->fetchColumn() ?: 'tr');
    Translator::setLocale($locale);
} catch (Throwable $e) {
    error_log('Locale initialization failed: ' . $e->getMessage());
}

$router = new Router();
require APP_ROOT . '/routes/web.php';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
