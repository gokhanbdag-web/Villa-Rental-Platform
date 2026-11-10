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

// Rewrite root-relative HTML links so the same build works at / or /any/subfolder/.
if (app_base_path() !== '') {
    ob_start(static function (string $html): string {
        $base = app_base_path();
        return preg_replace_callback(
            '/(\\b(?:href|src|action|formaction)\\s*=\\s*)(["\\'])(\\/(?!\\/)[^"\\']*)/i',
            static function (array $m) use ($base): string {
                $path = $m[3];
                if ($path === $base || str_starts_with($path, $base . '/')) return $m[0];
                return $m[1] . $m[2] . $base . $path;
            },
            $html
        ) ?? $html;
    });
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_name('villa_platform_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => app_base_path() . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
