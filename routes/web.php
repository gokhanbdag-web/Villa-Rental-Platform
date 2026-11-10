<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Database;
use App\Core\Router;
use App\Core\Translator;

$router->get('/', static function (): void {
    $pdo = Database::connection();
    $locale = 'tr';
    $mode = (string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode' LIMIT 1")->fetchColumn() ?: 'single_vendor');
    try {
        $locale = (string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.locale' LIMIT 1")->fetchColumn() ?: 'tr');
    } catch (Throwable $e) {}
    $contentStmt = $pdo->prepare('SELECT content_key,content_value FROM site_content WHERE language_code=?');
    $contentStmt->execute([$locale]);
    $content = $contentStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $navStmt = $pdo->prepare('SELECT label,url,target FROM navigation_items WHERE location=? AND language_code=? AND is_active=1 ORDER BY sort_order,id');
    $navStmt->execute(['header',$locale]);
    $navigation = $navStmt->fetchAll();
    $footerStmt = $pdo->prepare('SELECT label,url,target FROM navigation_items WHERE location=? AND language_code=? AND is_active=1 ORDER BY sort_order,id');
    $footerStmt->execute(['footer',$locale]); $footerNavigation = $footerStmt->fetchAll();
    $categories = $pdo->query('SELECT id,name,slug FROM property_categories WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();
    $city = trim((string)($_GET['city'] ?? ''));
    $guests = max(0,(int)($_GET['guests'] ?? 0));
    $categoryId = max(0,(int)($_GET['category'] ?? 0));
    $filters = ['city' => $city, 'guests' => $guests, 'category' => $categoryId];
    $sql = "SELECT p.*, (SELECT pm.file_path FROM property_media pm WHERE pm.property_id=p.id ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image FROM properties p WHERE p.status='published'";
    $params = [];
    if ($city !== '') { $sql .= ' AND (p.city LIKE ? OR p.district LIKE ? OR p.country LIKE ?)'; $like='%'.$city.'%'; array_push($params,$like,$like,$like); }
    if ($guests > 0) { $sql .= ' AND p.max_guests >= ?'; $params[]=$guests; }
    if ($categoryId > 0) { $sql .= ' AND p.category_id = ?'; $params[]=$categoryId; }
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


$router->get('/register', static function (): void {
    if (Auth::check()) redirect('/account/bookings');
    $error = null;
    require APP_ROOT . '/views/auth/register.php';
});

$router->post('/register', static function (): void {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');
    if (strlen($username) < 3 || !$email || strlen($password) < 10 || !hash_equals($password,(string)($_POST['password_confirm'] ?? ''))) {
        $error = 'Bilgileri kontrol edin. Şifre en az 10 karakter olmalı ve tekrar alanıyla eşleşmelidir.';
        require APP_ROOT . '/views/auth/register.php';
        return;
    }
    try {
        $stmt = Database::connection()->prepare("INSERT INTO users (username,email,password_hash,role,status,created_at) VALUES (?,?,?,'customer','active',NOW())");
        $stmt->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT)]);
        Auth::attempt($email,$password);
        redirect('/account/bookings');
    } catch (PDOException $e) {
        $error = $e->getCode() === '23000' ? 'Bu kullanıcı adı veya e-posta zaten kayıtlı.' : 'Hesap oluşturulurken bir hata oluştu.';
        require APP_ROOT . '/views/auth/register.php';
    }
});

$router->get('/villa/{slug}/reserve', static function (array $params): void {
    $user = Auth::user();
    if (!$user) redirect('/register');
    if (($user['role'] ?? '') !== 'customer') redirect('/login');
    $stmt = Database::connection()->prepare("SELECT * FROM properties WHERE slug=? AND status='published' LIMIT 1");
    $stmt->execute([$params['slug'] ?? '']);
    $property = $stmt->fetch();
    if (!$property) { http_response_code(404); echo 'Konaklama bulunamadı.'; return; }
    require APP_ROOT . '/views/site/booking.php';
});

$router->post('/villa/{slug}/reserve', static function (array $params): void {
    $user = Auth::user();
    if (!$user) redirect('/register');
    if (($user['role'] ?? '') !== 'customer') redirect('/login');
    verify_csrf();
    $pdo = Database::connection();
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE slug=? AND status='published' LIMIT 1");
    $stmt->execute([$params['slug'] ?? '']);
    $property = $stmt->fetch();
    if (!$property) { http_response_code(404); exit('Konaklama bulunamadı.'); }
    $inRaw = (string)($_POST['check_in'] ?? '');
    $outRaw = (string)($_POST['check_out'] ?? '');
    $checkIn = \DateTimeImmutable::createFromFormat('!Y-m-d',$inRaw);
    $checkOut = \DateTimeImmutable::createFromFormat('!Y-m-d',$outRaw);
    $today = new \DateTimeImmutable('today');
    $guests = (int)($_POST['guests'] ?? 0);
    if (!$checkIn || !$checkOut || $checkIn->format('Y-m-d') !== $inRaw || $checkOut->format('Y-m-d') !== $outRaw || $checkIn < $today || $checkOut <= $checkIn || $guests < 1 || $guests > (int)$property['max_guests']) {
        http_response_code(422); exit('Tarihleri ve misafir sayısını kontrol edin.');
    }
    $nights = (int)$checkIn->diff($checkOut)->days;
    try {
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT id,base_nightly_price,currency FROM properties WHERE id=? FOR UPDATE');
        $lock->execute([(int)$property['id']]);
        $current = $lock->fetch();
        $conflict = $pdo->prepare("SELECT id FROM bookings WHERE property_id=? AND status IN ('pending','confirmed') AND check_in < ? AND check_out > ? LIMIT 1");
        $conflict->execute([(int)$property['id'],$outRaw,$inRaw]);
        $blocked = $pdo->prepare("SELECT id FROM availability WHERE property_id=? AND status <> 'available' AND date_from < ? AND date_to > ? LIMIT 1");
        $blocked->execute([(int)$property['id'],$outRaw,$inRaw]);
        if (!$current || $conflict->fetchColumn() || $blocked->fetchColumn()) {
            $pdo->rollBack(); http_response_code(409); exit('Seçtiğiniz tarihler şu anda müsait değil. Lütfen başka tarih seçin.');
        }
        $subtotal = (float)$current['base_nightly_price'] * $nights;
        $code = 'VR' . date('Ymd') . strtoupper(bin2hex(random_bytes(3)));
        $save = $pdo->prepare("INSERT INTO bookings (booking_code,property_id,guest_user_id,check_in,check_out,guests,subtotal,fees,total,currency,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,?, 'pending',NOW())");
        $save->execute([$code,(int)$property['id'],(int)$user['id'],$inRaw,$outRaw,$guests,$subtotal,0,$subtotal,$current['currency']]);
        $pdo->commit();
        redirect('/account/bookings');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Booking request failed: '.$e->getMessage());
        http_response_code(500); exit('Rezervasyon talebi kaydedilemedi. Lütfen tekrar deneyin.');
    }
});

$router->get('/account/bookings', static function (): void {
    $user = Auth::user();
    if (!$user) redirect('/login');
    if (($user['role'] ?? '') !== 'customer') redirect('/admin');
    $stmt = Database::connection()->prepare('SELECT b.*,p.title,p.city FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.guest_user_id=? ORDER BY b.created_at DESC');
    $stmt->execute([(int)$user['id']]);
    $bookings = $stmt->fetchAll();
    require APP_ROOT . '/views/account/bookings.php';
});

$router->get('/login', static function (): void {
    if (Auth::check()) { $user = Auth::user(); redirect(($user['role'] ?? '') === 'customer' ? '/account/bookings' : (($user['role'] ?? '') === 'vendor' ? '/vendor' : '/admin')); }
    require APP_ROOT . '/views/auth/login.php';
});

$router->post('/login', static function (): void {
    verify_csrf();
    $identity = trim((string)($_POST['identity'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (Auth::attempt($identity, $password)) { $user = Auth::user(); redirect(($user['role'] ?? '') === 'customer' ? '/account/bookings' : (($user['role'] ?? '') === 'vendor' ? '/vendor' : '/admin')); }
    $_SESSION['login_error'] = 'auth.invalid_credentials';
    redirect('/login');
});

$router->post('/logout', static function (): void {
    verify_csrf();
    Auth::logout();
    redirect('/login');
});



$router->get('/vendor/register', static function (): void {
    $pdo=Database::connection();
    if ((string)$pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn() !== 'multi_vendor') { http_response_code(404); return; }
    $error=null; require APP_ROOT . '/views/vendor/register.php';
});
$router->post('/vendor/register', static function (): void {
    verify_csrf(); $pdo=Database::connection();
    if ((string)$pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn() !== 'multi_vendor') { http_response_code(404); return; }
    $business=trim((string)($_POST['business_name']??'')); $username=trim((string)($_POST['username']??''));
    $email=filter_var(trim((string)($_POST['email']??'')),FILTER_VALIDATE_EMAIL); $phone=trim((string)($_POST['phone']??''));
    $password=(string)($_POST['password']??'');
    if($business===''||strlen($username)<3||!$email||strlen($password)<10){$error='Bilgileri kontrol edin. Şifre en az 10 karakter olmalıdır.';require APP_ROOT.'/views/vendor/register.php';return;}
    try{$pdo->beginTransaction();$u=$pdo->prepare("INSERT INTO users(username,email,password_hash,role,status,created_at) VALUES(?,?,?,'vendor','active',NOW())");$u->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT)]);$v=$pdo->prepare("INSERT INTO vendors(user_id,business_name,phone,status,created_at) VALUES(?,?,?,'pending',NOW())");$v->execute([(int)$pdo->lastInsertId(),$business,$phone]);$pdo->commit();Auth::attempt($email,$password);redirect('/vendor');}
    catch(PDOException $e){if($pdo->inTransaction())$pdo->rollBack();$error='Bu kullanıcı adı veya e-posta kullanılıyor ya da başvuru kaydedilemedi.';require APP_ROOT.'/views/vendor/register.php';}
});
$router->get('/vendor', static function (): void {
    Auth::requireRole(['vendor']);$pdo=Database::connection();
    if((string)$pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn()!=='multi_vendor'){http_response_code(404);return;}
    $s=$pdo->prepare('SELECT * FROM vendors WHERE user_id=?');$s->execute([(int)Auth::user()['id']]);$vendor=$s->fetch();
    if(!$vendor){http_response_code(403);return;}
    $s=$pdo->prepare('SELECT COUNT(*) FROM properties WHERE vendor_id=?');$s->execute([(int)$vendor['id']]);$propertyCount=(int)$s->fetchColumn();
    require APP_ROOT.'/views/vendor/dashboard.php';
});
$router->get('/vendor/properties', static function (): void {
    Auth::requireRole(['vendor']);$pdo=Database::connection();$s=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$s->execute([(int)Auth::user()['id']]);$vid=(int)$s->fetchColumn();
    $s=$pdo->prepare('SELECT id,title,slug,city,district,max_guests,base_nightly_price,currency,status FROM properties WHERE vendor_id=? ORDER BY id DESC');$s->execute([$vid]);$properties=$s->fetchAll();
    $newUrl='/vendor/properties/new';$editBase='/vendor/properties';$deleteAction='/vendor/properties/delete';$backUrl='/vendor';require APP_ROOT.'/views/admin/properties.php';
});
$router->get('/vendor/properties/new', static function (): void {
    Auth::requireRole(['vendor']);$property=[];$categories=Database::connection()->query('SELECT id,name FROM property_categories WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();$formAction='/vendor/properties/save';$backUrl='/vendor/properties';require APP_ROOT.'/views/admin/property-form.php';
});
$router->get('/vendor/properties/{id}', static function (array $params): void {
    Auth::requireRole(['vendor']);$pdo=Database::connection();$s=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$s->execute([(int)Auth::user()['id']]);$vid=(int)$s->fetchColumn();
    $s=$pdo->prepare('SELECT * FROM properties WHERE id=? AND vendor_id=?');$s->execute([(int)$params['id'],$vid]);$property=$s->fetch();
    if(!$property){http_response_code(404);return;}$categories=$pdo->query('SELECT id,name FROM property_categories WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();$formAction='/vendor/properties/save';$backUrl='/vendor/properties';require APP_ROOT.'/views/admin/property-form.php';
});
$router->post('/vendor/properties/save', static function (): void {
    Auth::requireRole(['vendor']);verify_csrf();$pdo=Database::connection();
    $s=$pdo->prepare('SELECT id,status FROM vendors WHERE user_id=?');$s->execute([(int)Auth::user()['id']]);$vendor=$s->fetch();if(!$vendor){http_response_code(403);return;}
    $title=trim((string)($_POST['title']??''));if($title===''){http_response_code(422);exit('İlan başlığı zorunludur.');}
    $slug=trim((string)($_POST['slug']??''));if($slug===''){$a=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$title);$slug=strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/','-',$a!==false?$a:$title),'-'));}
    $slug=trim(strtolower(preg_replace('/[^a-z0-9-]+/','-',$slug)),'-');if($slug==='')$slug='villa-'.bin2hex(random_bytes(4));
    $id=(int)($_POST['id']??0);$status=$vendor['status']==='approved'&&($_POST['status']??'')==='published'?'published':'draft';
    $data=[$title,$slug,trim((string)($_POST['summary']??'')),trim((string)($_POST['description']??'')),trim((string)($_POST['country']??'Türkiye')),trim((string)($_POST['city']??'')),trim((string)($_POST['district']??'')),trim((string)($_POST['address']??'')),max(1,(int)($_POST['max_guests']??1)),max(0,(int)($_POST['bedrooms']??0)),max(0,(int)($_POST['beds']??0)),max(0,(float)($_POST['bathrooms']??1)),max(0,(float)($_POST['base_nightly_price']??0)),strtoupper(substr(preg_replace('/[^A-Za-z]/','',(string)($_POST['currency']??'TRY')),0,3))?:'TRY',$status,($_POST['booking_type']??'')==='instant'?'instant':'request',((int)($_POST['category_id']??0)>0?(int)$_POST['category_id']:null)];
    if($id){$owned=$pdo->prepare('SELECT id FROM properties WHERE id=? AND vendor_id=?');$owned->execute([$id,(int)$vendor['id']]);if(!$owned->fetchColumn()){http_response_code(404);exit('İlan bulunamadı.');}$s=$pdo->prepare('UPDATE properties SET title=?,slug=?,summary=?,description=?,country=?,city=?,district=?,address=?,max_guests=?,bedrooms=?,beds=?,bathrooms=?,base_nightly_price=?,currency=?,status=?,booking_type=?,category_id=?,updated_at=NOW() WHERE id=? AND vendor_id=?');$s->execute([...$data,$id,(int)$vendor['id']]);}
    else{$s=$pdo->prepare('INSERT INTO properties(vendor_id,title,slug,summary,description,country,city,district,address,max_guests,bedrooms,beds,bathrooms,base_nightly_price,currency,status,booking_type,category_id,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');$s->execute([(int)$vendor['id'],...$data]);$id=(int)$pdo->lastInsertId();}
    store_property_uploads($pdo,$id);
    redirect('/vendor/properties');
});
$router->get('/admin/vendors', static function (): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();
    if((string)$pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn()!=='multi_vendor')redirect('/admin');
    $vendors=$pdo->query('SELECT v.*,u.username,u.email FROM vendors v JOIN users u ON u.id=v.user_id ORDER BY v.created_at DESC')->fetchAll();require APP_ROOT.'/views/admin/vendors.php';
});
$router->post('/admin/vendors/approve', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$s=Database::connection()->prepare("UPDATE vendors SET status='approved' WHERE id=?");$s->execute([(int)($_POST['id']??0)]);redirect('/admin/vendors');
});


$router->get('/admin/properties/{id}/availability', static function (array $params): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();$s=$pdo->prepare('SELECT * FROM properties WHERE id=?');$s->execute([(int)$params['id']]);$property=$s->fetch();
    if(!$property){http_response_code(404);return;}$s=$pdo->prepare('SELECT * FROM availability WHERE property_id=? ORDER BY date_from');$s->execute([(int)$property['id']]);$ranges=$s->fetchAll();
    $saveUrl='/admin/properties/availability/save';$deleteUrl='/admin/properties/availability/delete';$backUrl='/admin/properties';require APP_ROOT.'/views/admin/availability.php';
});
$router->post('/admin/properties/availability/save', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$pdo=Database::connection();$pid=(int)($_POST['property_id']??0);$from=(string)($_POST['date_from']??'');$to=(string)($_POST['date_to']??'');$status=($_POST['status']??'')==='available'?'available':'blocked';$price=trim((string)($_POST['nightly_price']??''));$a=DateTimeImmutable::createFromFormat('!Y-m-d',$from);$b=DateTimeImmutable::createFromFormat('!Y-m-d',$to);
    if(!$a||!$b||$a->format('Y-m-d')!==$from||$b->format('Y-m-d')!==$to||$a<new DateTimeImmutable('today')||$b<=$a){http_response_code(422);exit('Tarih aralığı geçersiz.');}
    $over=$pdo->prepare('SELECT id FROM availability WHERE property_id=? AND date_from < ? AND date_to > ? LIMIT 1');$over->execute([$pid,$to,$from]);if($over->fetchColumn()){http_response_code(409);exit('Bu tarih aralığı mevcut bir takvim kuralıyla çakışıyor.');}
    $s=$pdo->prepare('INSERT INTO availability(property_id,date_from,date_to,status,nightly_price) VALUES(?,?,?,?,?)');$s->execute([$pid,$from,$to,$status,$price===''?null:max(0,(float)$price)]);redirect('/admin/properties/'.$pid.'/availability');
});
$router->post('/admin/properties/availability/delete', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$pid=(int)($_POST['property_id']??0);$s=Database::connection()->prepare('DELETE FROM availability WHERE id=? AND property_id=?');$s->execute([(int)($_POST['id']??0),$pid]);redirect('/admin/properties/'.$pid.'/availability');
});
$router->get('/vendor/properties/{id}/availability', static function (array $params): void {
    Auth::requireRole(['vendor']);$pdo=Database::connection();$v=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$v->execute([(int)Auth::user()['id']]);$vid=(int)$v->fetchColumn();$s=$pdo->prepare('SELECT * FROM properties WHERE id=? AND vendor_id=?');$s->execute([(int)$params['id'],$vid]);$property=$s->fetch();
    if(!$property){http_response_code(404);return;}$s=$pdo->prepare('SELECT * FROM availability WHERE property_id=? ORDER BY date_from');$s->execute([(int)$property['id']]);$ranges=$s->fetchAll();
    $saveUrl='/vendor/properties/availability/save';$deleteUrl='/vendor/properties/availability/delete';$backUrl='/vendor/properties';require APP_ROOT.'/views/admin/availability.php';
});
$router->post('/vendor/properties/availability/save', static function (): void {
    Auth::requireRole(['vendor']);verify_csrf();$pdo=Database::connection();$v=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$v->execute([(int)Auth::user()['id']]);$vid=(int)$v->fetchColumn();$pid=(int)($_POST['property_id']??0);$own=$pdo->prepare('SELECT id FROM properties WHERE id=? AND vendor_id=?');$own->execute([$pid,$vid]);if(!$own->fetchColumn()){http_response_code(404);return;}
    $from=(string)($_POST['date_from']??'');$to=(string)($_POST['date_to']??'');$status=($_POST['status']??'')==='available'?'available':'blocked';$price=trim((string)($_POST['nightly_price']??''));$a=DateTimeImmutable::createFromFormat('!Y-m-d',$from);$b=DateTimeImmutable::createFromFormat('!Y-m-d',$to);
    if(!$a||!$b||$a->format('Y-m-d')!==$from||$b->format('Y-m-d')!==$to||$a<new DateTimeImmutable('today')||$b<=$a){http_response_code(422);exit('Tarih aralığı geçersiz.');}
    $over=$pdo->prepare('SELECT id FROM availability WHERE property_id=? AND date_from < ? AND date_to > ? LIMIT 1');$over->execute([$pid,$to,$from]);if($over->fetchColumn()){http_response_code(409);exit('Bu tarih aralığı mevcut bir takvim kuralıyla çakışıyor.');}
    $s=$pdo->prepare('INSERT INTO availability(property_id,date_from,date_to,status,nightly_price) VALUES(?,?,?,?,?)');$s->execute([$pid,$from,$to,$status,$price===''?null:max(0,(float)$price)]);redirect('/vendor/properties/'.$pid.'/availability');
});
$router->post('/vendor/properties/availability/delete', static function (): void {
    Auth::requireRole(['vendor']);verify_csrf();$pdo=Database::connection();$v=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$v->execute([(int)Auth::user()['id']]);$vid=(int)$v->fetchColumn();$pid=(int)($_POST['property_id']??0);$own=$pdo->prepare('SELECT id FROM properties WHERE id=? AND vendor_id=?');$own->execute([$pid,$vid]);if(!$own->fetchColumn()){http_response_code(404);return;}$s=$pdo->prepare('DELETE FROM availability WHERE id=? AND property_id=?');$s->execute([(int)($_POST['id']??0),$pid]);redirect('/vendor/properties/'.$pid.'/availability');
});

$router->get('/admin/properties', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    $properties = Database::connection()->query('SELECT id,title,slug,city,district,max_guests,base_nightly_price,currency,status FROM properties ORDER BY id DESC')->fetchAll();
    require APP_ROOT . '/views/admin/properties.php';
});

$router->get('/admin/properties/new', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    $property = []; $categories = Database::connection()->query('SELECT id,name FROM property_categories WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();
    require APP_ROOT . '/views/admin/property-form.php';
});

$router->get('/admin/properties/{id}', static function (array $params): void {
    Auth::requireRole(['super_admin', 'admin']);
    $stmt = Database::connection()->prepare('SELECT * FROM properties WHERE id=? LIMIT 1');
    $stmt->execute([(int)($params['id'] ?? 0)]);
    $property = $stmt->fetch();
    if (!$property) { http_response_code(404); echo 'Konaklama bulunamadı.'; return; }
    $categories = Database::connection()->query('SELECT id,name FROM property_categories WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();
    require APP_ROOT . '/views/admin/property-form.php';
});


$router->post('/vendor/properties/delete', static function (): void {
    Auth::requireRole(['vendor']);verify_csrf();$pdo=Database::connection();
    $v=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$v->execute([(int)Auth::user()['id']]);$vendorId=(int)$v->fetchColumn();$id=(int)($_POST['id']??0);
    $check=$pdo->prepare('SELECT id FROM properties WHERE id=? AND vendor_id=?');$check->execute([$id,$vendorId]);
    if(!$check->fetchColumn()){http_response_code(404);exit('İlan bulunamadı.');}
    $b=$pdo->prepare('SELECT COUNT(*) FROM bookings WHERE property_id=?');$b->execute([$id]);if((int)$b->fetchColumn()>0){$archive=$pdo->prepare(\"UPDATE properties SET status='archived',updated_at=NOW() WHERE id=? AND vendor_id=?\");$archive->execute([$id,$vendorId]);}else{delete_property_uploads($pdo,$id);$del=$pdo->prepare('DELETE FROM properties WHERE id=? AND vendor_id=?');$del->execute([$id,$vendorId]);}redirect('/vendor/properties');
});
$router->post('/admin/properties/delete', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$pdo=Database::connection();$id=(int)($_POST['id']??0);
    $b=$pdo->prepare('SELECT COUNT(*) FROM bookings WHERE property_id=?');$b->execute([$id]);if((int)$b->fetchColumn()>0){$archive=$pdo->prepare(\"UPDATE properties SET status='archived',updated_at=NOW() WHERE id=?\");$archive->execute([$id]);}else{delete_property_uploads($pdo,$id);$del=$pdo->prepare('DELETE FROM properties WHERE id=?');$del->execute([$id]);}redirect('/admin/properties');
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
        $status, $bookingType, ((int)($_POST['category_id'] ?? 0) > 0 ? (int)$_POST['category_id'] : null)
    ];
    if ($data[13] === '') $data[13] = 'TRY';
    $check = $pdo->prepare('SELECT id FROM properties WHERE slug=? AND id<>? LIMIT 1');
    $check->execute([$slug,$id]);
    if ($check->fetchColumn()) $slug .= '-' . bin2hex(random_bytes(3));
    $data[1] = $slug;
    if ($id > 0) {
        $data[] = $id;
        $stmt = $pdo->prepare('UPDATE properties SET title=?,slug=?,summary=?,description=?,country=?,city=?,district=?,address=?,max_guests=?,bedrooms=?,beds=?,bathrooms=?,base_nightly_price=?,currency=?,status=?,booking_type=?,category_id=?,updated_at=NOW() WHERE id=?');
        $stmt->execute($data);
    } else {
        $stmt = $pdo->prepare('INSERT INTO properties (title,slug,summary,description,country,city,district,address,max_guests,bedrooms,beds,bathrooms,base_nightly_price,currency,status,booking_type,category_id,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
        $stmt->execute($data);
        $id = (int)$pdo->lastInsertId();
    }
    if ($id > 0) store_property_uploads($pdo, $id);
    redirect('/admin/properties');
});



$router->get('/admin/categories', static function (): void {
    Auth::requireRole(['super_admin','admin']);$categories=Database::connection()->query('SELECT * FROM property_categories ORDER BY sort_order,name')->fetchAll();require APP_ROOT.'/views/admin/categories.php';
});
$router->post('/admin/categories/save', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$name=trim((string)($_POST['name']??''));$slug=strtolower(trim((string)($_POST['slug']??'')));$slug=trim(preg_replace('/[^a-z0-9-]+/','-',$slug),'-');
    if($name===''||$slug===''){http_response_code(422);exit('Kategori adı ve URL kısa adı zorunludur.');}
    $s=Database::connection()->prepare('INSERT INTO property_categories(name,slug,description,is_active,sort_order) VALUES(?,?,?,1,?)');$s->execute([$name,$slug,trim((string)($_POST['description']??'')),(int)($_POST['sort_order']??10)]);redirect('/admin/categories');
});
$router->post('/admin/categories/delete', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$s=Database::connection()->prepare('DELETE FROM property_categories WHERE id=?');$s->execute([(int)($_POST['id']??0)]);redirect('/admin/categories');
});

$router->get('/admin/navigation', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    $items=Database::connection()->query('SELECT * FROM navigation_items ORDER BY location,sort_order,id')->fetchAll();
    require APP_ROOT.'/views/admin/navigation.php';
});
$router->post('/admin/navigation/save', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();
    $label=trim((string)($_POST['label']??''));$url=trim((string)($_POST['url']??''));
    $location=in_array($_POST['location']??'', ['header','footer'],true)?$_POST['location']:'header';
    $target=($_POST['target']??'')==='_blank'?'_blank':'_self';
    if($label===''||strlen($label)>120||!preg_match('#^(https?://|/)#i',$url)){http_response_code(422);exit('Menü adı veya bağlantı adresi geçersiz.');}
    $s=Database::connection()->prepare('INSERT INTO navigation_items(location,language_code,label,url,target,sort_order,is_active) VALUES(?,?,?,?,?,?,1)');
    $s->execute([$location,'tr',$label,$url,$target,(int)($_POST['sort_order']??10)]);
    redirect('/admin/navigation');
});
$router->post('/admin/navigation/delete', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();
    $s=Database::connection()->prepare('DELETE FROM navigation_items WHERE id=?');$s->execute([(int)($_POST['id']??0)]);redirect('/admin/navigation');
});


$router->get('/admin/pages', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    $pages=Database::connection()->query("SELECT id,title,slug,status FROM cms_pages WHERE language_code='tr' ORDER BY id DESC")->fetchAll();
    require APP_ROOT.'/views/admin/pages.php';
});
$router->get('/admin/pages/new', static function (): void {
    Auth::requireRole(['super_admin','admin']);$page=[];require APP_ROOT.'/views/admin/page-form.php';
});
$router->get('/admin/pages/{id}', static function (array $params): void {
    Auth::requireRole(['super_admin','admin']);$s=Database::connection()->prepare("SELECT * FROM cms_pages WHERE id=? AND language_code='tr'");$s->execute([(int)$params['id']]);$page=$s->fetch();
    if(!$page){http_response_code(404);return;}require APP_ROOT.'/views/admin/page-form.php';
});
$router->post('/admin/pages/save', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$pdo=Database::connection();
    $id=(int)($_POST['id']??0);$title=trim((string)($_POST['title']??''));$slug=strtolower(trim((string)($_POST['slug']??'')));
    $slug=trim(preg_replace('/[^a-z0-9-]+/','-',$slug),'-');$content=trim((string)($_POST['content']??''));$status=($_POST['status']??'')==='published'?'published':'draft';
    if($title===''||$slug===''||$content===''){http_response_code(422);exit('Başlık, URL ve içerik zorunludur.');}
    if($id){$s=$pdo->prepare("UPDATE cms_pages SET title=?,slug=?,content=?,status=? WHERE id=? AND language_code='tr'");$s->execute([$title,$slug,$content,$status,$id]);}
    else{$s=$pdo->prepare("INSERT INTO cms_pages(language_code,title,slug,content,status) VALUES('tr',?,?,?,?)");$s->execute([$title,$slug,$content,$status]);}
    redirect('/admin/pages');
});
$router->get('/page/{slug}', static function (array $params): void {
    $s=Database::connection()->prepare("SELECT * FROM cms_pages WHERE slug=? AND language_code='tr' AND status='published' LIMIT 1");$s->execute([$params['slug']]);$page=$s->fetch();
    if(!$page){http_response_code(404);echo 'Sayfa bulunamadı.';return;}
    $content=Database::connection()->query("SELECT content_value FROM site_content WHERE content_key='site_name' AND language_code='tr'")->fetchColumn();$siteName=$content?:'VillaStay';
    require APP_ROOT.'/views/site/page.php';
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


$router->get('/admin/bookings', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    $sql="SELECT b.*,p.title,u.username AS guest_name,u.email AS guest_email FROM bookings b JOIN properties p ON p.id=b.property_id JOIN users u ON u.id=b.guest_user_id ORDER BY b.created_at DESC LIMIT 250";
    $bookings=Database::connection()->query($sql)->fetchAll();
    require APP_ROOT.'/views/admin/bookings.php';
});
$router->post('/admin/bookings/status', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();
    $pdo=Database::connection();$id=(int)($_POST['id']??0);$status=(string)($_POST['status']??'');
    if(!in_array($status,['pending','confirmed','cancelled','completed'],true)){http_response_code(422);exit('Rezervasyon durumu geçersiz.');}
    try{
        $pdo->beginTransaction();
        $s=$pdo->prepare('SELECT property_id,check_in,check_out,status FROM bookings WHERE id=? FOR UPDATE');$s->execute([$id]);$booking=$s->fetch();
        if(!$booking){$pdo->rollBack();http_response_code(404);exit('Rezervasyon bulunamadı.');}
        if($status==='confirmed'){
            $conflict=$pdo->prepare("SELECT id FROM bookings WHERE property_id=? AND id<>? AND status='confirmed' AND check_in < ? AND check_out > ? LIMIT 1");
            $conflict->execute([(int)$booking['property_id'],$id,$booking['check_out'],$booking['check_in']]);
            if($conflict->fetchColumn()){$pdo->rollBack();http_response_code(409);exit('Bu tarihlerde onaylanmış başka bir rezervasyon var.');}
        }
        $update=$pdo->prepare('UPDATE bookings SET status=? WHERE id=?');$update->execute([$status,$id]);
        $log=$pdo->prepare('INSERT INTO audit_logs(user_id,action,entity_type,entity_id,ip_address,details,created_at) VALUES(?,?,?,?,?,?,NOW())');
        $log->execute([(int)Auth::user()['id'],'booking.status_changed','booking',$id,$_SERVER['REMOTE_ADDR']??null,json_encode(['from'=>$booking['status'],'to'=>$status],JSON_UNESCAPED_UNICODE)]);
        $pdo->commit();redirect('/admin/bookings');
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Booking status update failed: '.$e->getMessage());http_response_code(500);exit('Rezervasyon güncellenemedi.');}
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
