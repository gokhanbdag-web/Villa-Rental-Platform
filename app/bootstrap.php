<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('APP_STORAGE', APP_ROOT . '/storage');

// Lightweight, privacy-safe slow-request diagnostics. Only requests taking
// at least one second are written to the PHP error log; no query strings,
// cookies, session IDs, credentials, or request bodies are recorded.
$GLOBALS['villa_perf_started_at'] = microtime(true);
$GLOBALS['villa_perf_session_wait_ms'] = 0.0;
$GLOBALS['villa_perf_stage'] = 'bootstrap';
$GLOBALS['villa_perf_request_id'] = bin2hex(random_bytes(6));

/**
 * Persist a tiny stage marker immediately. Unlike the shutdown summary, this
 * survives a request that gets stuck before PHP can execute shutdown handlers.
 * No cookies, session IDs, SQL values, or request parameters are recorded.
 */
function villa_perf_mark(string $stage): void
{
    $GLOBALS['villa_perf_stage'] = substr($stage, 0, 120);
    $record = [
        'type' => 'villa_request_stage',
        'request_id' => (string)($GLOBALS['villa_perf_request_id'] ?? ''),
        'stage' => (string)$GLOBALS['villa_perf_stage'],
        'elapsed_ms' => round((microtime(true) - (float)($GLOBALS['villa_perf_started_at'] ?? microtime(true))) * 1000, 1),
        'session_start_ms' => round((float)($GLOBALS['villa_perf_session_wait_ms'] ?? 0), 1),
    ];
    $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (is_string($line) && is_dir(APP_STORAGE)) {
        @file_put_contents(APP_STORAGE . '/performance.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
register_shutdown_function(static function (): void {
    $started = (float)($GLOBALS['villa_perf_started_at'] ?? microtime(true));
    $totalMs = (microtime(true) - $started) * 1000;
    if ($totalMs < 1000) return;
    $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $path = substr($path, 0, 180);
    $method = (string)($_SERVER['REQUEST_METHOD'] ?? 'CLI');
    $stage = (string)($GLOBALS['villa_perf_stage'] ?? 'unknown');
    $sessionWait = (float)($GLOBALS['villa_perf_session_wait_ms'] ?? 0);
    $record = [
        'type' => 'villa_slow_request',
        'method' => $method,
        'path' => $path,
        'total_ms' => round($totalMs, 1),
        'session_start_ms' => round($sessionWait, 1),
        'last_stage' => $stage,
        'peak_memory_mb' => round(memory_get_peak_usage(true) / 1048576, 1),
        'http_status' => http_response_code(),
    ];
    $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (is_string($line) && is_dir(APP_STORAGE)) {
        @file_put_contents(APP_STORAGE . '/performance.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    error_log('[VILLA_PERF] ' . (is_string($line) ? $line : '{}'));
});

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
            '/(\b(?:href|src|action|formaction)\s*=\s*)(["\'])(\/(?!\/)[^"\']*)/i',
            static function (array $m) use ($base): string {
                $path = $m[3];
                // app_url() may already have added the installation prefix.
                // Never prepend it a second time to form actions or asset links.
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
    $sessionStartedAt = microtime(true);
    session_start();
    $GLOBALS['villa_perf_session_wait_ms'] = (microtime(true) - $sessionStartedAt) * 1000;
    $GLOBALS['villa_perf_stage'] = 'session_started';
}
