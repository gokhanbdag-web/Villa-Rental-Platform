<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Database;
use App\Core\Router;

$router->get('/', static function (): void {
    redirect('/login');
});

$router->get('/login', static function (): void {
    if (Auth::check()) redirect('/admin');
    require APP_ROOT . '/views/auth/login.php';
});

$router->post('/login', static function (): void {
    verify_csrf();
    $identity = trim((string)($_POST['identity'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (Auth::attempt($identity, $password)) redirect('/admin');
    $_SESSION['login_error'] = 'auth.invalid_credentials';
    redirect('/login');
});

$router->post('/logout', static function (): void {
    verify_csrf();
    Auth::logout();
    redirect('/login');
});

$router->get('/admin', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    $pdo = Database::connection();
    $metrics = [
        'properties' => (int)$pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn(),
        'bookings' => (int)$pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),
        'users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'pending' => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    ];
    require APP_ROOT . '/views/admin/dashboard.php';
});
