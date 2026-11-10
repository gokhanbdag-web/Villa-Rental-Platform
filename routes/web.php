<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Database;
use App\Core\Router;
use App\Core\Translator;

$router->get('/', static function (): void {
    $pdo = Database::connection();
    $locale = 'tr';
    try {
        $locale = (string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.locale' LIMIT 1")->fetchColumn() ?: 'tr');
    } catch (Throwable $e) {}
    $contentStmt = $pdo->prepare('SELECT content_key,content_value FROM site_content WHERE language_code=?');
    $contentStmt->execute([$locale]);
    $content = $contentStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $navStmt = $pdo->prepare('SELECT label,url,target FROM navigation_items WHERE location=? AND language_code=? AND is_active=1 ORDER BY sort_order,id');
    $navStmt->execute(['header',$locale]);
    $navigation = $navStmt->fetchAll();
    $city = trim((string)($_GET['city'] ?? ''));
    $guests = max(0,(int)($_GET['guests'] ?? 0));
    $sql = "SELECT p.*, (SELECT pm.file_path FROM property_media pm WHERE pm.property_id=p.id ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image FROM properties p WHERE p.status='published'";
    $params = [];
    if ($city !== '') { $sql .= ' AND (p.city LIKE ? OR p.district LIKE ? OR p.country LIKE ?)'; $like='%'.$city.'%'; array_push($params,$like,$like,$like); }
    if ($guests > 0) { $sql .= ' AND p.max_guests >= ?'; $params[]=$guests; }
    $sql .= ' ORDER BY p.created_at DESC,p.id DESC LIMIT 48';
    $stmt=$pdo->prepare($sql); $stmt->execute($params); $properties=$stmt->fetchAll();
    require APP_ROOT . '/views/site/home.php';
});

$router->get('/villa/{slug}', static function (array $params): void {
    $pdo=Database::connection();
    $stmt=$pdo->prepare("SELECT * FROM properties WHERE slug=? AND status='published' LIMIT 1");
    $stmt->execute([$params['slug'] ?? '']);
    $property=$stmt->fetch();
    if (!$property) { http_response_code(404); echo 'Konaklama bulunamadı.'; return; }
    $media=$pdo->prepare('SELECT file_path,alt_text,is_cover FROM property_media WHERE property_id=? ORDER BY is_cover DESC,sort_order,id');
    $media->execute([(int)$property['id']]); $property['images']=$media->fetchAll();
    $contentStmt=$pdo->prepare('SELECT content_key,content_value FROM site_content WHERE language_code=?');
    $contentStmt->execute(['tr']); $content=$contentStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $locale='tr';
    require APP_ROOT . '/views/site/property.php';
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
