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
    $filters = ['city' => $city, 'guests' => $guests];
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


$router->get('/admin/properties', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    $properties = Database::connection()->query('SELECT id,title,slug,city,district,max_guests,base_nightly_price,currency,status FROM properties ORDER BY id DESC')->fetchAll();
    require APP_ROOT . '/views/admin/properties.php';
});

$router->get('/admin/properties/new', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    $property = [];
    require APP_ROOT . '/views/admin/property-form.php';
});

$router->get('/admin/properties/{id}', static function (array $params): void {
    Auth::requireRole(['super_admin', 'admin']);
    $stmt = Database::connection()->prepare('SELECT * FROM properties WHERE id=? LIMIT 1');
    $stmt->execute([(int)($params['id'] ?? 0)]);
    $property = $stmt->fetch();
    if (!$property) { http_response_code(404); echo 'Konaklama bulunamadı.'; return; }
    require APP_ROOT . '/views/admin/property-form.php';
});

$router->post('/admin/properties/save', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    verify_csrf();
    $pdo = Database::connection();
    $title = trim((string)($_POST['title'] ?? ''));
    if ($title === '') { http_response_code(422); exit('İlan başlığı zorunludur.'); }
    $slug = trim((string)($_POST['slug'] ?? ''));
    if ($slug === '') {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii !== false ? $ascii : $title), '-'));
    }
    $slug = trim(strtolower(preg_replace('/[^a-z0-9-]+/', '-', $slug)), '-');
    if ($slug === '') $slug = 'villa-' . bin2hex(random_bytes(4));
    $id = (int)($_POST['id'] ?? 0);
    $status = in_array($_POST['status'] ?? '', ['draft','published','archived'], true) ? $_POST['status'] : 'draft';
    $bookingType = ($_POST['booking_type'] ?? '') === 'instant' ? 'instant' : 'request';
    $data = [
        $title, $slug, trim((string)($_POST['summary'] ?? '')), trim((string)($_POST['description'] ?? '')),
        trim((string)($_POST['country'] ?? '')), trim((string)($_POST['city'] ?? '')), trim((string)($_POST['district'] ?? '')),
        trim((string)($_POST['address'] ?? '')), max(1,min(100,(int)($_POST['max_guests'] ?? 1))),
        max(0,min(100,(int)($_POST['bedrooms'] ?? 0))), max(0,min(100,(int)($_POST['beds'] ?? 0))),
        max(0,(float)($_POST['bathrooms'] ?? 1)), max(0,(float)($_POST['base_nightly_price'] ?? 0)),
        strtoupper(substr(preg_replace('/[^A-Za-z]/','',(string)($_POST['currency'] ?? 'TRY')),0,3)),
        $status, $bookingType
    ];
    if ($data[13] === '') $data[13] = 'TRY';
    $check = $pdo->prepare('SELECT id FROM properties WHERE slug=? AND id<>? LIMIT 1');
    $check->execute([$slug,$id]);
    if ($check->fetchColumn()) $slug .= '-' . bin2hex(random_bytes(3));
    $data[1] = $slug;
    if ($id > 0) {
        $data[] = $id;
        $stmt = $pdo->prepare('UPDATE properties SET title=?,slug=?,summary=?,description=?,country=?,city=?,district=?,address=?,max_guests=?,bedrooms=?,beds=?,bathrooms=?,base_nightly_price=?,currency=?,status=?,booking_type=?,updated_at=NOW() WHERE id=?');
        $stmt->execute($data);
    } else {
        $stmt = $pdo->prepare('INSERT INTO properties (title,slug,summary,description,country,city,district,address,max_guests,bedrooms,beds,bathrooms,base_nightly_price,currency,status,booking_type,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
        $stmt->execute($data);
    }
    redirect('/admin/properties');
});

$router->get('/admin/content', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    $pdo = Database::connection();
    $content = $pdo->query("SELECT content_key,content_value FROM site_content WHERE language_code='tr'")->fetchAll(PDO::FETCH_KEY_PAIR);
    $contentFields = [
        'site_name'=>'Site adı',
        'home_eyebrow'=>'Ana sayfa kısa üst başlığı',
        'home_title'=>'Ana sayfa ana başlığı',
        'home_subtitle'=>'Ana sayfa açıklaması',
        'home_search_placeholder'=>'Arama alanı ipucu',
        'featured_title'=>'Öne çıkanlar başlığı',
        'featured_subtitle'=>'Öne çıkanlar açıklaması',
        'empty_properties'=>'Sonuç bulunamadı metni',
        'footer_text'=>'Alt bilgi metni'
    ];
    require APP_ROOT . '/views/admin/content.php';
});

$router->post('/admin/content', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    verify_csrf();
    $allowed = ['site_name','home_eyebrow','home_title','home_subtitle','home_search_placeholder','featured_title','featured_subtitle','empty_properties','footer_text'];
    $stmt = Database::connection()->prepare('INSERT INTO site_content (content_key,language_code,content_value,updated_at) VALUES (?,\'tr\',?,NOW()) ON DUPLICATE KEY UPDATE content_value=VALUES(content_value),updated_at=NOW()');
    foreach ((array)($_POST['content'] ?? []) as $key=>$value) {
        if (in_array($key,$allowed,true)) $stmt->execute([$key,trim((string)$value)]);
    }
    redirect('/admin/content');
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
