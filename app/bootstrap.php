<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_STORAGE', APP_ROOT . '/storage');

if (is_file(APP_ROOT . '/config/config.php')) {
    $config = require APP_ROOT . '/config/config.php';
    date_default_timezone_set($config['app']['timezone'] ?? 'Europe/Istanbul');
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) require $file;
});

require APP_ROOT . '/app/Support/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('villa_platform_session');
    session_start();
}
