<?php
declare(strict_types=1);

use App\Core\Router;

$router->get('/', static function (): void {
    http_response_code(200);
    echo '<!doctype html><html lang="tr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Villa Rental Platform</title><body><main><h1>Villa Rental Platform</h1><p>Uygulama çekirdeği kuruluyor.</p></main></body></html>';
});
