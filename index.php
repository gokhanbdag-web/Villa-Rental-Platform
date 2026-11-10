<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Installer;
use App\Core\Router;

if (!Installer::isInstalled()) {
    header('Location: install/');
    exit;
}

$router = new Router();
require APP_ROOT . '/routes/web.php';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
