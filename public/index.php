<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Installer;

if (!Installer::isInstalled()) {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $basePath = app_base_path();
    if ($basePath !== '' && ($requestPath === $basePath || str_starts_with($requestPath, $basePath . '/'))) {
        $requestPath = substr($requestPath, strlen($basePath)) ?: '/';
    }
    if (in_array(rtrim($requestPath, '/') . '/', ['/install/'], true)) {
        require dirname(__DIR__) . '/install/index.php';
        exit;
    }
    header('Location: ' . app_url('/install/'));
    exit;
}

require dirname(__DIR__) . '/index.php';
