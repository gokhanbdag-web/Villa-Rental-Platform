<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/Router.php';

use App\Core\Router;

$router = new Router();
$seen = [];

$router->get('/admin', static function (array $params) use (&$seen): void {
    $seen = ['route' => 'admin', 'params' => $params];
});
$router->get('/villa/{slug}', static function (array $params) use (&$seen): void {
    $seen = ['route' => 'villa', 'params' => $params];
});
$router->get('/theme-assets/{slug}/{file*}', static function (array $params) use (&$seen): void {
    $seen = ['route' => 'asset', 'params' => $params];
});

if (!$router->hasExactRoute('GET', '/admin')) {
    throw new RuntimeException('Literal route detection failed.');
}
if ($router->hasExactRoute('GET', '/villa/{slug}')) {
    throw new RuntimeException('Parameterized route was incorrectly marked literal.');
}
if (!$router->dispatch('GET', '/admin') || ($seen['route'] ?? '') !== 'admin') {
    throw new RuntimeException('Literal route dispatch failed.');
}
if (!$router->dispatch('GET', '/villa/my-villa') || ($seen['params']['slug'] ?? '') !== 'my-villa') {
    throw new RuntimeException('Parameterized route dispatch failed.');
}
if (!$router->dispatch('GET', '/theme-assets/default/css/site/main.css')
    || ($seen['route'] ?? '') !== 'asset'
    || ($seen['params']['slug'] ?? '') !== 'default'
    || ($seen['params']['file'] ?? '') !== 'css/site/main.css') {
    throw new RuntimeException('Wildcard asset route dispatch failed.');
}
if ($router->dispatch('GET', '/route-does-not-exist')) {
    throw new RuntimeException('Unknown route unexpectedly matched.');
}

fwrite(STDOUT, "Router smoke test passed.\n");
