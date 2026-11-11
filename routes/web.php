<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Database;
use App\Core\Router;
use App\Core\Translator;

function villa_resolve_location(PDO $pdo): array {
    $ids=['country'=>(int)($_POST['location_country_id']??0),'city'=>(int)($_POST['location_city_id']??0),'district'=>(int)($_POST['location_district_id']??0),'neighborhood'=>(int)($_POST['location_neighborhood_id']??0)];
    $get=static function(int $id,string $type,?int $parent) use($pdo): ?array {
        if($id<1)return null;$s=$pdo->prepare('SELECT id,parent_id,name FROM locations WHERE id=? AND location_type=? AND is_active=1 LIMIT 1');$s->execute([$id,$type]);$row=$s->fetch();
        if(!$row||($parent!==null&&(int)$row['parent_id']!==$parent))return null;return $row;
    };
    $country=$get($ids['country'],'country',null);$city=$get($ids['city'],'city',$ids['country']);
    $district=$ids['district']?$get($ids['district'],'district',$ids['city']):null;
    $neighborhood=$ids['neighborhood']?$get($ids['neighborhood'],'neighborhood',$ids['district']):null;
    if(!$country||!$city||($ids['district']&&!$district)||($ids['neighborhood']&&!$neighborhood)||($neighborhood&&!$district))throw new InvalidArgumentException('Lütfen ülke ve şehir seçin; alt konum seçimlerini kontrol edin.');
    return ['location_country_id'=>$ids['country'],'location_city_id'=>$ids['city'],'location_district_id'=>$district?$ids['district']:null,'location_neighborhood_id'=>$neighborhood?$ids['neighborhood']:null,'country'=>$country['name'],'city'=>$city['name'],'district'=>$district['name']??'','neighborhood'=>$neighborhood['name']??''];
}
$router->get('/admin/homepage', static function (): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();
    $sections=$pdo->query('SELECT * FROM homepage_sections ORDER BY sort_order,id')->fetchAll();
    $mode=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn() ?: 'single_vendor');
    if($mode!=='multi_vendor')$sections=array_values(array_filter($sections,static fn(array $section):bool=>$section['section_key']!=='hosts'));
    require APP_ROOT.'/views/admin/homepage.php';
});
$router->post('/admin/homepage', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$pdo=Database::connection();
    $mode=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn() ?: 'single_vendor');
    $allowed=['hero','destinations','featured','styles','gallery'];
    if($mode==='multi_vendor')$allowed[]='hosts';
    $stmt=$pdo->prepare('UPDATE homepage_sections SET title=?,subtitle=?,image_url=?,is_active=?,sort_order=? WHERE section_key=?');
    foreach($allowed as $key){$row=$_POST['sections'][$key]??null;if(!is_array($row))continue;
        $title=trim((string)($row['title']??''));$subtitle=trim((string)($row['subtitle']??''));$image=trim((string)($row['image_url']??''));
        if($image!==''&&!filter_var($image,FILTER_VALIDATE_URL))$image='';
        $stmt->execute([mb_substr($title,0,190),mb_substr($subtitle,0,500),$image,isset($row['is_active'])?1:0,max(0,min(99,(int)($row['sort_order']??10))),$key]);
    }
    redirect('/admin/homepage');
});

$router->get('/locations/children', static function (): void {
    header('Content-Type: application/json; charset=utf-8');$parentId=max(0,(int)($_GET['parent_id']??0));
    $s=Database::connection()->prepare('SELECT id,name FROM locations WHERE parent_id=? AND is_active=1 ORDER BY sort_order,name');$s->execute([$parentId]);echo json_encode($s->fetchAll(),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
});
$router->get('/locations/search', static function (): void {
    header('Content-Type: application/json; charset=utf-8');
    $q=trim((string)($_GET['q']??''));
    if(mb_strlen($q)<2){echo '[]';return;}
    $like='%'.$q.'%';
    $sql="SELECT l.id,l.location_type,l.name,
        TRIM(BOTH ' / ' FROM CONCAT_WS(' / ',g.name,p.name,l.name)) AS label
        FROM locations l
        LEFT JOIN locations p ON p.id=l.parent_id
        LEFT JOIN locations g ON g.id=p.parent_id
        WHERE l.is_active=1 AND (l.name LIKE ? OR p.name LIKE ? OR g.name LIKE ?)
        ORDER BY FIELD(l.location_type,'city','district','neighborhood','country'),l.sort_order,l.name
        LIMIT 15";
    $s=Database::connection()->prepare($sql);$s->execute([$like,$like,$like]);
    echo json_encode($s->fetchAll(),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
});
$router->get('/admin/locations', static function (): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();
    $locations=$pdo->query("SELECT l.*,p.name AS parent_name FROM locations l LEFT JOIN locations p ON p.id=l.parent_id ORDER BY FIELD(l.location_type,'country','city','district','neighborhood'),p.name,l.sort_order,l.name")->fetchAll();
    $parents=$pdo->query("SELECT c.id,c.location_type,CONCAT(COALESCE(p.name,''),CASE WHEN p.name IS NULL THEN '' ELSE ' / ' END,c.name) AS full_name FROM locations c LEFT JOIN locations p ON p.id=c.parent_id WHERE c.is_active=1 ORDER BY FIELD(c.location_type,'country','city','district'),p.name,c.name")->fetchAll();
    require APP_ROOT.'/views/admin/locations.php';
});

$router->get('/admin/locations/template', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="lokasyon-sablonu.csv"');
    echo "\xEF\xBB\xBF";
    $out=fopen('php://output','wb');
    fputcsv($out,['country','city','district','neighborhood'],';');
    fputcsv($out,['Türkiye','Antalya','Kaş','Kalkan'],';');
    fputcsv($out,['Türkiye','Muğla','Fethiye','Ölüdeniz'],';');
    fclose($out);
    exit;
});
$router->post('/admin/locations/import', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();
    $file=$_FILES['locations_csv']??null;
    if(!is_array($file)||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK){http_response_code(422);exit('CSV dosyası yüklenemedi. Dosyayı seçip tekrar deneyin.');}
    if(($file['size']??0)<1||$file['size']>5*1024*1024){http_response_code(422);exit('CSV dosyası boş veya 5 MB sınırından büyük.');}
    $handle=fopen((string)$file['tmp_name'],'rb');
    if(!$handle){http_response_code(422);exit('CSV dosyası okunamadı.');}
    $first=(string)fgets($handle);rewind($handle);
    $counts=['inserted'=>0,'existing'=>0,'errors'=>0,'rows'=>0];$errorRows=[];
    $delimiter=';';
    $candidates=[';',',',"\t"];
    $best=-1;
    foreach($candidates as $candidate){$n=count(str_getcsv($first,$candidate,'"','\\'));if($n>$best){$best=$n;$delimiter=$candidate;}}
    $header=fgetcsv($handle,0,$delimiter,'"','\\');
    if(!is_array($header)){fclose($handle);http_response_code(422);exit('CSV başlık satırı okunamadı.');}
    $header[0]=preg_replace('/^\xEF\xBB\xBF/','',(string)$header[0]);
    $aliases=['country'=>['country','ulke','ülke'],'city'=>['city','sehir','şehir','il'],'district'=>['district','ilce','ilçe'],'neighborhood'=>['neighborhood','semt','mahalle','semt_mahalle']];
    $columns=[];
    foreach($header as $index=>$name){$key=mb_strtolower(trim((string)$name));foreach($aliases as $field=>$names){if(in_array($key,$names,true))$columns[$field]=$index;}}
    if(!isset($columns['country'])){fclose($handle);http_response_code(422);exit('CSV başlığında country (ülke) sütunu bulunamadı. Şablonu indirip kullanabilirsiniz.');}
    $pdo=Database::connection();
    $find=$pdo->prepare('SELECT id FROM locations WHERE parent_id <=> ? AND location_type=? AND name=? LIMIT 1');
    $insert=$pdo->prepare('INSERT INTO locations(parent_id,location_type,name,sort_order,is_active) VALUES(?,?,?,10,1)');
    $rowInserted=0;$rowExisting=0;
    $resolve=static function(string $name,string $type,?int $parent) use($find,$insert,&$rowInserted,&$rowExisting): int {
        $name=trim($name);if($name==='')return 0;
        $find->execute([$parent,$type,$name]);$id=(int)($find->fetchColumn()?:0);
        if($id){$rowExisting++;return $id;}
        $insert->execute([$parent,$type,$name]);$rowInserted++;return (int)Database::connection()->lastInsertId();
    };
    $line=1;
    while(($row=fgetcsv($handle,0,$delimiter,'"','\\'))!==false){
        $line++;if($row===[null]||$row===[])continue;
        $values=[];foreach(['country','city','district','neighborhood'] as $field){$idx=$columns[$field]??null;$values[$field]=$idx!==null?trim((string)($row[$idx]??'')):'';}
        if($values['country']===''&&$values['city']===''&&$values['district']===''&&$values['neighborhood']==='')continue;
        if($counts['rows']>=20000){$counts['errors']++;$errorRows[]=['line'=>$line,'message'=>'20.000 satır sınırına ulaşıldı.'];break;}
        $counts['rows']++;
        $rowInserted=0;$rowExisting=0;
        try{
            if($values['country']==='')throw new InvalidArgumentException('Ülke alanı boş.');
            if($values['district']!==''&&$values['city']==='')throw new InvalidArgumentException('İlçe için şehir bilgisi zorunlu.');
            if($values['neighborhood']!==''&&$values['district']==='')throw new InvalidArgumentException('Semt/mahalle için ilçe bilgisi zorunlu.');
            if(mb_strlen($values['country'])>190||mb_strlen($values['city'])>190||mb_strlen($values['district'])>190||mb_strlen($values['neighborhood'])>190)throw new InvalidArgumentException('Konum adı 190 karakteri aşamaz.');
            $pdo->beginTransaction();
            $countryId=$resolve($values['country'],'country',null);
            if($values['city']!==''){$cityId=$resolve($values['city'],'city',$countryId);
                if($values['district']!==''){$districtId=$resolve($values['district'],'district',$cityId);
                    if($values['neighborhood']!=='')$resolve($values['neighborhood'],'neighborhood',$districtId);
                }
            }
            $pdo->commit();
            $counts['inserted']+=$rowInserted;$counts['existing']+=$rowExisting;
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$counts['errors']++;if(count($errorRows)<100)$errorRows[]=['line'=>$line,'message'=>$e->getMessage()];}
    }
    fclose($handle);
    $locations=$pdo->query("SELECT l.*,p.name AS parent_name FROM locations l LEFT JOIN locations p ON p.id=l.parent_id ORDER BY FIELD(l.location_type,'country','city','district','neighborhood'),p.name,l.sort_order,l.name")->fetchAll();
    $parents=$pdo->query("SELECT c.id,c.location_type,CONCAT(COALESCE(p.name,''),CASE WHEN p.name IS NULL THEN '' ELSE ' / ' END,c.name) AS full_name FROM locations c LEFT JOIN locations p ON p.id=c.parent_id WHERE c.is_active=1 ORDER BY FIELD(c.location_type,'country','city','district'),p.name,c.name")->fetchAll();
    $importReport=['counts'=>$counts,'errors'=>$errorRows];
    require APP_ROOT.'/views/admin/locations.php';
});

$router->post('/admin/locations/save', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$pdo=Database::connection();$type=(string)($_POST['location_type']??'');$name=trim((string)($_POST['name']??''));$parent=(int)($_POST['parent_id']??0);
    $allowed=['country'=>null,'city'=>'country','district'=>'city','neighborhood'=>'district'];
    if(!array_key_exists($type,$allowed)||$name===''||mb_strlen($name)>190){http_response_code(422);exit('Konum türünü ve adını kontrol edin.');}
    if($type==='country')$parent=null;else{$s=$pdo->prepare('SELECT id FROM locations WHERE id=? AND location_type=? AND is_active=1');$s->execute([$parent,$allowed[$type]]);if(!$s->fetchColumn()){http_response_code(422);exit('Seçilen üst konum geçersiz.');}}
    try{$s=$pdo->prepare('INSERT INTO locations(parent_id,location_type,name,sort_order,is_active) VALUES(?,?,?,?,1)');$s->execute([$parent,$type,$name,(int)($_POST['sort_order']??10)]);}catch(PDOException $e){http_response_code(409);exit('Bu üst konum altında aynı isimde kayıt zaten var.');}
    redirect('/admin/locations');
});
$router->post('/admin/locations/toggle', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$s=Database::connection()->prepare('UPDATE locations SET is_active=1-is_active WHERE id=?');$s->execute([(int)($_POST['id']??0)]);redirect('/admin/locations');
});

$router->get('/', static function (): void {
    $pdo=Database::connection();$user=Auth::user();$locale='tr';
    try{$locale=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.locale' LIMIT 1")->fetchColumn() ?: 'tr');}catch(Throwable $e){}
    $mode=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode' LIMIT 1")->fetchColumn() ?: 'single_vendor');
    $contentStmt=$pdo->prepare('SELECT content_key,content_value FROM site_content WHERE language_code=?');$contentStmt->execute([$locale]);$content=$contentStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $nav=$pdo->prepare('SELECT label,url,target FROM navigation_items WHERE location=? AND language_code=? AND is_active=1 ORDER BY sort_order,id');$nav->execute(['header',$locale]);$navigation=$nav->fetchAll();$nav->execute(['footer',$locale]);$footerNavigation=$nav->fetchAll();
    $destinations=$pdo->query("SELECT l.id AS location_id,l.name AS city,COUNT(DISTINCT p.id) AS property_count,(SELECT pm.file_path FROM property_media pm JOIN properties p2 ON p2.id=pm.property_id WHERE p2.status='published' AND p2.location_city_id=l.id ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image FROM locations l JOIN locations c ON c.id=l.parent_id AND c.location_type='country' AND c.is_active=1 JOIN properties p ON p.status='published' AND p.location_city_id=l.id WHERE l.location_type='city' AND l.is_active=1 GROUP BY l.id,l.name,l.sort_order ORDER BY property_count DESC,l.sort_order,l.name LIMIT 8")->fetchAll();
    $featuredProperties=$pdo->query("SELECT p.*,(SELECT pm.file_path FROM property_media pm WHERE pm.property_id=p.id ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image,(SELECT COUNT(*) FROM bookings b WHERE b.property_id=p.id AND b.status NOT IN ('cancelled','rejected')) AS booking_count FROM properties p WHERE p.status='published' ORDER BY booking_count DESC,p.created_at DESC,p.id DESC LIMIT 8")->fetchAll();
    $latestProperties=$pdo->query("SELECT p.*,(SELECT pm.file_path FROM property_media pm WHERE pm.property_id=p.id ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image FROM properties p WHERE p.status='published' ORDER BY p.id DESC LIMIT 4")->fetchAll();
    $categories=$pdo->query('SELECT id,name,slug FROM property_categories WHERE is_active=1 ORDER BY sort_order,name LIMIT 8')->fetchAll();
    $homeSections=[];foreach($pdo->query('SELECT * FROM homepage_sections ORDER BY sort_order,id')->fetchAll() as $section){$homeSections[$section['section_key']]=$section;}
    $locationCountries=$pdo->query("SELECT id,name FROM locations WHERE location_type='country' AND is_active=1 ORDER BY sort_order,name")->fetchAll();
    require APP_ROOT.'/views/site/landing.php';
});

$router->get('/konaklamalar', static function (): void {
    $pdo = Database::connection();
    $user = Auth::user();
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
    $locationCountries=$pdo->query("SELECT id,name FROM locations WHERE location_type='country' AND is_active=1 ORDER BY sort_order,name")->fetchAll();
    $locationChildren=static function(int $parent) use($pdo): array {if(!$parent)return [];$s=$pdo->prepare('SELECT id,name FROM locations WHERE parent_id=? AND is_active=1 ORDER BY sort_order,name');$s->execute([$parent]);return $s->fetchAll();};
    $locationCountryId=max(0,(int)($_GET['country_id']??0));$locationCityId=max(0,(int)($_GET['city_id']??0));$locationDistrictId=max(0,(int)($_GET['district_id']??0));$locationNeighborhoodId=max(0,(int)($_GET['neighborhood_id']??0));
    $selectedLocationId=max(0,(int)($_GET['location_id']??0));
    if($selectedLocationId>0){
        $selectedStmt=$pdo->prepare('SELECT location_type FROM locations WHERE id=? AND is_active=1 LIMIT 1');
        $selectedStmt->execute([$selectedLocationId]);$selectedType=(string)($selectedStmt->fetchColumn()?:'');
        if($selectedType==='country')$locationCountryId=$selectedLocationId;
        elseif($selectedType==='city')$locationCityId=$selectedLocationId;
        elseif($selectedType==='district')$locationDistrictId=$selectedLocationId;
        elseif($selectedType==='neighborhood')$locationNeighborhoodId=$selectedLocationId;
    }
    $locationCities=$locationChildren($locationCountryId);$locationDistricts=$locationChildren($locationCityId);$locationNeighborhoods=$locationChildren($locationDistrictId);
    $city = $selectedLocationId>0 ? '' : trim((string)($_GET['city'] ?? ''));
    $guests = max(0,(int)($_GET['guests'] ?? 0));
    $categoryId = max(0,(int)($_GET['category'] ?? 0));
    $minPrice = max(0,(float)($_GET['min_price'] ?? 0));
    $maxPrice = max(0,(float)($_GET['max_price'] ?? 0));
    $priceCeiling = (int)$pdo->query("SELECT COALESCE(MAX(base_nightly_price),100000) FROM properties WHERE status='published'")->fetchColumn();
    $priceCeiling = max(1000,(int)(ceil($priceCeiling / 1000) * 1000));
    $minPrice = min($minPrice,$priceCeiling);
    $maxPrice = $maxPrice > 0 ? min($maxPrice,$priceCeiling) : $priceCeiling;
    if ($minPrice >= $maxPrice) { $minPrice = 0; $maxPrice = $priceCeiling; }
    $checkInDate = trim((string)($_GET['check_in'] ?? ''));
    $checkOutDate = trim((string)($_GET['check_out'] ?? ''));
    $dateRangeValid = false;
    if ($checkInDate !== '' && $checkOutDate !== '') {
        $checkInValue = \DateTimeImmutable::createFromFormat('!Y-m-d', $checkInDate);
        $checkOutValue = \DateTimeImmutable::createFromFormat('!Y-m-d', $checkOutDate);
        $todayValue = new \DateTimeImmutable('today');
        $dateRangeValid = $checkInValue && $checkOutValue
            && $checkInValue->format('Y-m-d') === $checkInDate
            && $checkOutValue->format('Y-m-d') === $checkOutDate
            && $checkInValue >= $todayValue && $checkOutValue > $checkInValue;
    }
    if (!$dateRangeValid) { $checkInDate = ''; $checkOutDate = ''; }
    $sort = in_array($_GET['sort'] ?? '', ['price_asc','price_desc'], true) ? $_GET['sort'] : 'newest';
    $filters = ['city' => $city, 'guests' => $guests, 'category' => $categoryId, 'min_price'=>$minPrice, 'max_price'=>$maxPrice, 'sort'=>$sort,'country_id'=>$locationCountryId,'city_id'=>$locationCityId,'district_id'=>$locationDistrictId,'neighborhood_id'=>$locationNeighborhoodId,'check_in'=>$checkInDate,'check_out'=>$checkOutDate];
    $sql = "SELECT p.*, (SELECT pm.file_path FROM property_media pm WHERE pm.property_id=p.id ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image FROM properties p WHERE p.status='published'";
    $params = [];
    if ($city !== '') { $sql .= ' AND (p.city LIKE ? OR p.district LIKE ? OR p.country LIKE ?)'; $like='%'.$city.'%'; array_push($params,$like,$like,$like); }
    if($locationCountryId>0){$sql.=' AND p.location_country_id=?';$params[]=$locationCountryId;}
    if($locationCityId>0){$sql.=' AND p.location_city_id=?';$params[]=$locationCityId;}
    if($locationDistrictId>0){$sql.=' AND p.location_district_id=?';$params[]=$locationDistrictId;}
    if($locationNeighborhoodId>0){$sql.=' AND p.location_neighborhood_id=?';$params[]=$locationNeighborhoodId;}
    if ($guests > 0) { $sql .= ' AND p.max_guests >= ?'; $params[]=$guests; }
    if ($categoryId > 0) { $sql .= ' AND p.category_id = ?'; $params[]=$categoryId; }
    if ($minPrice > 0) { $sql .= ' AND p.base_nightly_price >= ?'; $params[]=$minPrice; }
    if ($maxPrice > 0) { $sql .= ' AND p.base_nightly_price <= ?'; $params[]=$maxPrice; }
    if ($dateRangeValid) {
        // A property is returned only when no booking or blocked calendar rule overlaps any night of the stay.
        // Checkout is exclusive: a booking ending on check-in day does not conflict.
        $sql .= " AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.property_id=p.id AND b.status IN ('pending','confirmed') AND b.check_in < ? AND b.check_out > ?)";
        $params[] = $checkOutDate; $params[] = $checkInDate;
        $sql .= " AND NOT EXISTS (SELECT 1 FROM availability a WHERE a.property_id=p.id AND a.status<>'available' AND a.date_from < ? AND a.date_to > ?)";
        $params[] = $checkOutDate; $params[] = $checkInDate;
    }
    $orderBy = $sort === 'price_asc' ? 'p.base_nightly_price ASC' : ($sort === 'price_desc' ? 'p.base_nightly_price DESC' : 'p.created_at DESC,p.id DESC');
    $countFrom = strpos($sql, ' FROM properties p WHERE ');
    $countSql = 'SELECT COUNT(*)' . substr($sql, $countFrom);
    $totalStmt=$pdo->prepare($countSql);$totalStmt->execute($params);$totalProperties=(int)$totalStmt->fetchColumn();
    $perPage=12;$totalPages=max(1,(int)ceil($totalProperties/$perPage));$currentPage=min($totalPages,max(1,(int)($_GET['page']??1)));
    $sql .= ' ORDER BY ' . $orderBy . ' LIMIT ' . $perPage . ' OFFSET ' . (($currentPage-1)*$perPage);
    $stmt=$pdo->prepare($sql); $stmt->execute($params); $properties=$stmt->fetchAll();
    require APP_ROOT . '/views/site/listing.php';
});

$router->get('/destinasyonlar', static function (): void {
    $pdo=Database::connection();$user=Auth::user();$locale='tr';
    try{$locale=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.locale' LIMIT 1")->fetchColumn() ?: 'tr');}catch(Throwable $e){}
    $contentStmt=$pdo->prepare('SELECT content_key,content_value FROM site_content WHERE language_code=?');$contentStmt->execute([$locale]);$content=$contentStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $nav=$pdo->prepare('SELECT label,url,target FROM navigation_items WHERE location=? AND language_code=? AND is_active=1 ORDER BY sort_order,id');$nav->execute(['header',$locale]);$navigation=$nav->fetchAll();$nav->execute(['footer',$locale]);$footerNavigation=$nav->fetchAll();
    $destinationsSql = "SELECT l.id AS location_id,l.name AS city,COUNT(DISTINCT p.id) AS property_count,(SELECT pm.file_path FROM property_media pm JOIN properties p2 ON p2.id=pm.property_id WHERE p2.status='published' AND p2.location_city_id=l.id ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image FROM locations l JOIN properties p ON p.location_city_id=l.id AND p.status='published' WHERE l.location_type='city' AND l.is_active=1 GROUP BY l.id,l.name,l.sort_order ORDER BY property_count DESC,l.sort_order,l.name";
    $destinations = $pdo->query($destinationsSql)->fetchAll();
    $footerStmt=$pdo->prepare('SELECT label,url,target FROM navigation_items WHERE location=? AND language_code=? AND is_active=1 ORDER BY sort_order,id');$footerStmt->execute(['footer',$locale]);$footerNavigation=$footerStmt->fetchAll();
    $mode=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode' LIMIT 1")->fetchColumn() ?: 'single_vendor');
    $user=Auth::user();
    require APP_ROOT.'/views/site/destinations.php';
});

$router->get('/villa/{slug}', static function (array $params): void {
    $pdo=Database::connection();
    $stmt=$pdo->prepare("SELECT * FROM properties WHERE slug=? AND status='published' LIMIT 1");
    $stmt->execute([$params['slug'] ?? '']);
    $property=$stmt->fetch();
    if (!$property) { http_response_code(404); echo 'Konaklama bulunamadı.'; return; }
    $media=$pdo->prepare('SELECT file_path,alt_text,is_cover FROM property_media WHERE property_id=? ORDER BY is_cover DESC,sort_order,id');
    $media->execute([(int)$property['id']]); $property['images']=$media->fetchAll();
    $amenityStmt=$pdo->prepare('SELECT a.name,a.icon FROM amenities a JOIN property_amenities pa ON pa.amenity_id=a.id WHERE pa.property_id=? AND a.is_active=1 ORDER BY a.sort_order,a.name');$amenityStmt->execute([(int)$property['id']]);$propertyAmenities=$amenityStmt->fetchAll();
    $contentStmt=$pdo->prepare('SELECT content_key,content_value FROM site_content WHERE language_code=?');
    $contentStmt->execute(['tr']); $content=$contentStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $locale='tr';
    $navStmt=$pdo->prepare('SELECT label,url,target FROM navigation_items WHERE location=? AND language_code=? AND is_active=1 ORDER BY sort_order,id');
    $navStmt->execute(['header',$locale]); $navigation=$navStmt->fetchAll();
    $navStmt->execute(['footer',$locale]); $footerNavigation=$navStmt->fetchAll();
    $mode=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode' LIMIT 1")->fetchColumn() ?: 'single_vendor');
    $user=Auth::user();$isFavorite=false;
    if($user && ($user['role']??'')==='customer'){$fav=$pdo->prepare('SELECT 1 FROM favorites WHERE user_id=? AND property_id=?');$fav->execute([(int)$user['id'],(int)$property['id']]);$isFavorite=(bool)$fav->fetchColumn();}
    $reviewsStmt=$pdo->prepare("SELECT r.rating,r.body,r.created_at,u.username FROM reviews r JOIN users u ON u.id=r.guest_user_id WHERE r.property_id=? AND r.status='approved' ORDER BY r.created_at DESC LIMIT 20");
    $reviewsStmt->execute([(int)$property['id']]);$reviews=$reviewsStmt->fetchAll();
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
    $checkInDate = trim((string)($_GET['check_in'] ?? ''));
    $checkOutDate = trim((string)($_GET['check_out'] ?? ''));
    $checkInValue = \DateTimeImmutable::createFromFormat('!Y-m-d', $checkInDate);
    $checkOutValue = \DateTimeImmutable::createFromFormat('!Y-m-d', $checkOutDate);
    if (!$checkInValue || !$checkOutValue || $checkInValue->format('Y-m-d') !== $checkInDate || $checkOutValue->format('Y-m-d') !== $checkOutDate || $checkInValue < new \DateTimeImmutable('today') || $checkOutValue <= $checkInValue) {
        $checkInDate = ''; $checkOutDate = '';
    }
    $pdo=Database::connection();
    $contentStmt=$pdo->prepare('SELECT content_key,content_value FROM site_content WHERE language_code=?');$contentStmt->execute(['tr']);$content=$contentStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $siteName=$content['site_name']??'VillaStay';
    $footerStmt=$pdo->prepare('SELECT label,url,target FROM navigation_items WHERE location=? AND language_code=? AND is_active=1 ORDER BY sort_order,id');$footerStmt->execute(['footer','tr']);$footerNavigation=$footerStmt->fetchAll();
    $pricePreview=null;
    if($checkInDate!==''&&$checkOutDate!==''){
        $nightRules=$pdo->prepare('SELECT date_from,date_to,status,nightly_price FROM availability WHERE property_id=? AND date_from < ? AND date_to > ?');$nightRules->execute([(int)$property['id'],$checkOutDate,$checkInDate]);$previewRules=$nightRules->fetchAll();
        $previewSubtotal=0.0;$previewNights=0;$previewUnavailable=false;
        for($night=$checkInValue;$night<$checkOutValue;$night=$night->modify('+1 day')){$day=$night->format('Y-m-d');$rate=(float)$property['base_nightly_price'];if((int)$night->format('N')>=6&&(float)($property['weekend_nightly_price']??0)>0)$rate=(float)$property['weekend_nightly_price'];foreach($previewRules as $rule){if($day>=$rule['date_from']&&$day<$rule['date_to']){if($rule['status']!=='available'){$previewUnavailable=true;break;}if($rule['nightly_price']!==null)$rate=(float)$rule['nightly_price'];}}if($previewUnavailable)break;$previewSubtotal+=$rate;$previewNights++;}
        if(!$previewUnavailable){$pricePreview=['nights'=>$previewNights,'subtotal'=>$previewSubtotal,'fees'=>(float)($property['cleaning_fee']??0),'total'=>$previewSubtotal+(float)($property['cleaning_fee']??0)];}
    }
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
        $lock = $pdo->prepare('SELECT id,base_nightly_price,weekend_nightly_price,cleaning_fee,currency FROM properties WHERE id=? FOR UPDATE');
        $lock->execute([(int)$property['id']]);
        $current = $lock->fetch();
        if (!$current) { $pdo->rollBack(); http_response_code(404); exit('Konaklama artık mevcut değil.'); }
        $conflict = $pdo->prepare('SELECT id FROM bookings WHERE property_id=? AND status IN (?,?) AND check_in < ? AND check_out > ? LIMIT 1');
        $conflict->execute([(int)$property['id'],'pending','confirmed',$outRaw,$inRaw]);
        $calendar = $pdo->prepare('SELECT date_from,date_to,status,nightly_price FROM availability WHERE property_id=? AND date_from < ? AND date_to > ?');
        $calendar->execute([(int)$property['id'],$outRaw,$inRaw]);
        $rules = $calendar->fetchAll();
        $unavailable = false;
        $subtotal = 0.0;
        for ($night = $checkIn; $night < $checkOut; $night = $night->modify('+1 day')) {
            $day = $night->format('Y-m-d');
            $nightPrice = (float)$current['base_nightly_price'];
            if ((int)$night->format('N') >= 6 && (float)($current['weekend_nightly_price'] ?? 0) > 0) $nightPrice = (float)$current['weekend_nightly_price'];
            foreach ($rules as $rule) {
                if ($day >= $rule['date_from'] && $day < $rule['date_to']) {
                    if ($rule['status'] !== 'available') { $unavailable = true; break; }
                    if ($rule['nightly_price'] !== null) $nightPrice = (float)$rule['nightly_price'];
                }
            }
            if ($unavailable) break;
            $subtotal += $nightPrice;
        }
        if (!$current || $conflict->fetchColumn() || $unavailable) {
            $pdo->rollBack(); http_response_code(409); exit('Seçtiğiniz tarihler şu anda müsait değil. Lütfen başka tarih seçin.');
        }
        $code = 'VR' . date('Ymd') . strtoupper(bin2hex(random_bytes(3)));
        $fees = (float)($current['cleaning_fee'] ?? 0);
        $total = $subtotal + $fees;
        $save = $pdo->prepare("INSERT INTO bookings (booking_code,property_id,guest_user_id,check_in,check_out,guests,subtotal,fees,total,currency,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,?, 'pending',NOW())");
        $save->execute([$code,(int)$property['id'],(int)$user['id'],$inRaw,$outRaw,$guests,$subtotal,$fees,$total,$current['currency']]);
        $bookingId = (int)$pdo->lastInsertId();
        $vendorNet = $total;
        $platformFee = 0.0;
        if (!empty($property['vendor_id'])) {
            $vendorStmt = $pdo->prepare('SELECT user_id,commission_rate FROM vendors WHERE id=?');
            $vendorStmt->execute([(int)$property['vendor_id']]);
            $vendorInfo = $vendorStmt->fetch();
            if ($vendorInfo) {
                $defaultRaw = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='platform.commission_percent'")->fetchColumn();
                $defaultRate = $defaultRaw === false ? 12.0 : (float)$defaultRaw;
                $rate = $vendorInfo['commission_rate'] !== null ? (float)$vendorInfo['commission_rate'] : $defaultRate;
                $platformFee = round($total * max(0,min(50,$rate)) / 100, 2);
                $vendorNet = $total - $platformFee;
                $notifyVendor = $pdo->prepare("INSERT INTO notifications(user_id,notification_type,title,body,created_at) VALUES(?,'booking.requested','Yeni rezervasyon talebi',?,NOW())");
                $notifyVendor->execute([(int)$vendorInfo['user_id'],'İlanınız için yeni bir rezervasyon talebi alındı: '.$code]);
            }
        }
        $financial = $pdo->prepare("INSERT INTO booking_financials(booking_id,gross_amount,platform_fee,vendor_net_amount,currency,payout_status,created_at) VALUES(?,?,?,?,?,'unpaid',NOW())");
        $financial->execute([$bookingId,$total,$platformFee,$vendorNet,$current['currency']]);
        $notifyGuest = $pdo->prepare("INSERT INTO notifications(user_id,notification_type,title,body,created_at) VALUES(?,'booking.created','Rezervasyon talebiniz alındı',?,NOW())");
        $notifyGuest->execute([(int)$user['id'],'Rezervasyon kodunuz: '.$code]);
        $pdo->commit();
        redirect('/account/bookings');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Booking request failed: '.$e->getMessage());
        http_response_code(500); exit('Rezervasyon talebi kaydedilemedi. Lütfen tekrar deneyin.');
    }
});




$router->get('/account/messages/{bookingId}', static function (array $params): void {
    $user=Auth::user();if(!$user)redirect('/login');$pdo=Database::connection();$bookingId=(int)$params['bookingId'];
    $s=$pdo->prepare('SELECT b.*,p.title,p.vendor_id,g.username AS guest_name,v.user_id AS vendor_user_id FROM bookings b JOIN properties p ON p.id=b.property_id JOIN users g ON g.id=b.guest_user_id LEFT JOIN vendors v ON v.id=p.vendor_id WHERE b.id=?');
    $s->execute([$bookingId]);$booking=$s->fetch();if(!$booking){http_response_code(404);return;}
    $vendorUserId=(int)($booking['vendor_user_id']??0);if(!$vendorUserId)$vendorUserId=(int)$pdo->query("SELECT id FROM users WHERE role='super_admin' ORDER BY id LIMIT 1")->fetchColumn();
    $guestId=(int)$booking['guest_user_id'];$currentId=(int)$user['id'];if($currentId!==$guestId&&$currentId!==$vendorUserId){http_response_code(403);return;}
    $otherId=$currentId===$guestId?$vendorUserId:$guestId;$conversationKey=hash('sha256',$bookingId.':'.min($currentId,$otherId).':'.max($currentId,$otherId));
    $read=$pdo->prepare('UPDATE messages SET read_at=NOW() WHERE conversation_key=? AND recipient_user_id=? AND read_at IS NULL');$read->execute([$conversationKey,$currentId]);
    $messagesStmt=$pdo->prepare('SELECT m.*,u.username AS sender_name FROM messages m JOIN users u ON u.id=m.sender_user_id WHERE m.conversation_key=? ORDER BY m.created_at,id');$messagesStmt->execute([$conversationKey]);$messages=$messagesStmt->fetchAll();
    require APP_ROOT.'/views/account/messages.php';
});
$router->post('/account/messages/send', static function (): void {
    $user=Auth::user();if(!$user)redirect('/login');verify_csrf();$pdo=Database::connection();$bookingId=(int)($_POST['booking_id']??0);$body=trim((string)($_POST['body']??''));
    if($body===''||strlen($body)>5000){http_response_code(422);exit('Mesaj 1 ile 5000 karakter arasında olmalıdır.');}
    $s=$pdo->prepare('SELECT b.*,p.vendor_id,v.user_id AS vendor_user_id FROM bookings b JOIN properties p ON p.id=b.property_id LEFT JOIN vendors v ON v.id=p.vendor_id WHERE b.id=?');$s->execute([$bookingId]);$booking=$s->fetch();if(!$booking){http_response_code(404);return;}
    $vendorUserId=(int)($booking['vendor_user_id']??0);if(!$vendorUserId)$vendorUserId=(int)$pdo->query("SELECT id FROM users WHERE role='super_admin' ORDER BY id LIMIT 1")->fetchColumn();
    $guestId=(int)$booking['guest_user_id'];$currentId=(int)$user['id'];if($currentId!==$guestId&&$currentId!==$vendorUserId){http_response_code(403);return;}
    $recipientId=$currentId===$guestId?$vendorUserId:$guestId;$key=hash('sha256',$bookingId.':'.min($currentId,$recipientId).':'.max($currentId,$recipientId));
    $save=$pdo->prepare('INSERT INTO messages(conversation_key,sender_user_id,recipient_user_id,booking_id,body,created_at) VALUES(?,?,?,?,?,NOW())');$save->execute([$key,$currentId,$recipientId,$bookingId,$body]);
    $notice=$pdo->prepare("INSERT INTO notifications(user_id,notification_type,title,body,created_at) VALUES(?,'message.received','Yeni mesaj',?,NOW())");$notice->execute([$recipientId,'Rezervasyon görüşmesinde yeni bir mesajınız var.']);
    redirect('/account/messages/'.$bookingId);
});

$router->get('/favorites', static function (): void {
    $user=Auth::user();if(!$user)redirect('/login');if(($user['role']??'')!=='customer')redirect('/admin');
    $s=Database::connection()->prepare("SELECT p.*, (SELECT file_path FROM property_media WHERE property_id=p.id ORDER BY is_cover DESC,sort_order,id LIMIT 1) AS cover_image FROM favorites f JOIN properties p ON p.id=f.property_id WHERE f.user_id=? AND p.status='published' ORDER BY f.created_at DESC");
    $s->execute([(int)$user['id']]);$properties=$s->fetchAll();require APP_ROOT.'/views/account/favorites.php';
});
$router->post('/favorites/toggle', static function (): void {
    $user=Auth::user();if(!$user)redirect('/login');if(($user['role']??'')!=='customer')redirect('/admin');verify_csrf();
    $pdo=Database::connection();$pid=(int)($_POST['property_id']??0);$check=$pdo->prepare("SELECT id FROM properties WHERE id=? AND status='published'");$check->execute([$pid]);if(!$check->fetchColumn()){http_response_code(404);return;}
    $exists=$pdo->prepare('SELECT 1 FROM favorites WHERE user_id=? AND property_id=?');$exists->execute([(int)$user['id'],$pid]);
    if($exists->fetchColumn()){$del=$pdo->prepare('DELETE FROM favorites WHERE user_id=? AND property_id=?');$del->execute([(int)$user['id'],$pid]);}
    else{$add=$pdo->prepare('INSERT INTO favorites(user_id,property_id,created_at) VALUES(?,?,NOW())');$add->execute([(int)$user['id'],$pid]);}
    $return=trim((string)($_POST['return_to']??'/favorites'));$parsed=parse_url($return);if(!str_starts_with($return,'/')||str_starts_with($return,'//')||isset($parsed['host'])||preg_match('/[\\r\\n]/',$return))$return='/favorites';redirect($return);
});


$router->get('/account/reviews/{bookingId}', static function (array $params): void {
    $user=Auth::user();if(!$user||($user['role']??'')!=='customer')redirect('/login');
    $s=Database::connection()->prepare("SELECT b.id,p.title FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.id=? AND b.guest_user_id=? AND b.status='completed'");
    $s->execute([(int)$params['bookingId'],(int)$user['id']]);$booking=$s->fetch();if(!$booking){http_response_code(404);return;}
    $exists=Database::connection()->prepare('SELECT id FROM reviews WHERE booking_id=?');$exists->execute([(int)$booking['id']]);if($exists->fetchColumn()){http_response_code(409);exit('Bu rezervasyon için zaten değerlendirme gönderdiniz.');}
    require APP_ROOT.'/views/account/review-form.php';
});
$router->post('/account/reviews', static function (): void {
    $user=Auth::user();if(!$user||($user['role']??'')!=='customer')redirect('/login');verify_csrf();
    $bookingId=(int)($_POST['booking_id']??0);$rating=max(1,min(5,(int)($_POST['rating']??5)));$body=trim((string)($_POST['body']??''));
    $pdo=Database::connection();$s=$pdo->prepare("SELECT property_id FROM bookings WHERE id=? AND guest_user_id=? AND status='completed'");$s->execute([$bookingId,(int)$user['id']]);$propertyId=(int)$s->fetchColumn();if(!$propertyId){http_response_code(403);exit('Yalnızca tamamlanan konaklamalar değerlendirilebilir.');}
    try{$save=$pdo->prepare("INSERT INTO reviews(booking_id,property_id,guest_user_id,rating,body,status,created_at) VALUES(?,?,?,?,?,'pending',NOW())");$save->execute([$bookingId,$propertyId,(int)$user['id'],$rating,$body]);redirect('/account/bookings');}
    catch(PDOException $e){http_response_code(409);exit('Bu rezervasyon için değerlendirme zaten mevcut.');}
});
$router->get('/admin/reviews', static function (): void {
    Auth::requireRole(['super_admin','admin']);$reviews=Database::connection()->query('SELECT r.*,p.title,u.username FROM reviews r JOIN properties p ON p.id=r.property_id JOIN users u ON u.id=r.guest_user_id ORDER BY r.created_at DESC LIMIT 250')->fetchAll();require APP_ROOT.'/views/admin/reviews.php';
});
$router->post('/admin/reviews/status', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$status=($_POST['status']??'')==='approved'?'approved':'rejected';$s=Database::connection()->prepare('UPDATE reviews SET status=? WHERE id=?');$s->execute([$status,(int)($_POST['id']??0)]);redirect('/admin/reviews');
});

$router->get('/account/notifications', static function (): void {
    $user=Auth::user();if(!$user)redirect('/login');$pdo=Database::connection();
    $mark=$pdo->prepare('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL');$mark->execute([(int)$user['id']]);
    $s=$pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 100');$s->execute([(int)$user['id']]);$notifications=$s->fetchAll();
    require APP_ROOT.'/views/account/notifications.php';
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

$router->get('/vendor/bookings', static function (): void {
    Auth::requireRole(['vendor']);$pdo=Database::connection();$v=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$v->execute([(int)Auth::user()['id']]);$vendorId=(int)$v->fetchColumn();
    $s=$pdo->prepare('SELECT b.*,p.title,u.username AS guest_name,u.email AS guest_email FROM bookings b JOIN properties p ON p.id=b.property_id JOIN users u ON u.id=b.guest_user_id WHERE p.vendor_id=? ORDER BY b.created_at DESC');$s->execute([$vendorId]);$bookings=$s->fetchAll();require APP_ROOT.'/views/vendor/bookings.php';
});

$router->get('/vendor/properties', static function (): void {
    Auth::requireRole(['vendor']);$pdo=Database::connection();$s=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$s->execute([(int)Auth::user()['id']]);$vid=(int)$s->fetchColumn();
    $s=$pdo->prepare('SELECT id,title,slug,city,district,max_guests,base_nightly_price,currency,status FROM properties WHERE vendor_id=? ORDER BY id DESC');$s->execute([$vid]);$properties=$s->fetchAll();
    $newUrl='/vendor/properties/new';$editBase='/vendor/properties';$deleteAction='/vendor/properties/delete';$backUrl='/vendor';require APP_ROOT.'/views/admin/properties.php';
});
$router->get('/vendor/properties/new', static function (): void {
    Auth::requireRole(['vendor']);$property=[];$existingImages=[];$deleteMediaAction='/vendor/properties/media/delete';$categories=Database::connection()->query('SELECT id,name FROM property_categories WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();$amenities=Database::connection()->query('SELECT id,name FROM amenities WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();$selectedAmenities=[];$defaultCurrency=(string)(Database::connection()->query("SELECT setting_value FROM settings WHERE setting_key='app.currency'")->fetchColumn()?:'TRY');$formAction='/vendor/properties/save';$backUrl='/vendor/properties';require APP_ROOT.'/views/admin/property-form.php';
});
$router->get('/vendor/properties/{id}', static function (array $params): void {
    Auth::requireRole(['vendor']);$pdo=Database::connection();$s=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$s->execute([(int)Auth::user()['id']]);$vid=(int)$s->fetchColumn();
    $s=$pdo->prepare('SELECT * FROM properties WHERE id=? AND vendor_id=?');$s->execute([(int)$params['id'],$vid]);$property=$s->fetch();
    if(!$property){http_response_code(404);return;}$mediaStmt=$pdo->prepare('SELECT * FROM property_media WHERE property_id=? ORDER BY is_cover DESC,sort_order,id');$mediaStmt->execute([(int)$property['id']]);$existingImages=$mediaStmt->fetchAll();$deleteMediaAction='/vendor/properties/media/delete';$categories=$pdo->query('SELECT id,name FROM property_categories WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();$amenities=$pdo->query('SELECT id,name FROM amenities WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();$a=$pdo->prepare('SELECT amenity_id FROM property_amenities WHERE property_id=?');$a->execute([(int)$property['id']]);$selectedAmenities=array_map('intval',$a->fetchAll(PDO::FETCH_COLUMN));$defaultCurrency=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.currency'")->fetchColumn()?:'TRY');$formAction='/vendor/properties/save';$backUrl='/vendor/properties';require APP_ROOT.'/views/admin/property-form.php';
});
$router->post('/vendor/properties/save', static function (): void {
    Auth::requireRole(['vendor']);verify_csrf();$pdo=Database::connection();
    $s=$pdo->prepare('SELECT id,status FROM vendors WHERE user_id=?');$s->execute([(int)Auth::user()['id']]);$vendor=$s->fetch();if(!$vendor){http_response_code(403);return;}
    $title=trim((string)($_POST['title']??''));if($title===''){http_response_code(422);exit('İlan başlığı zorunludur.');}
    $slug=trim((string)($_POST['slug']??''));if($slug===''){$a=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$title);$slug=strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/','-',$a!==false?$a:$title),'-'));}
    $slug=trim(strtolower(preg_replace('/[^a-z0-9-]+/','-',$slug)),'-');if($slug==='')$slug='villa-'.bin2hex(random_bytes(4));
    $id=(int)($_POST['id']??0);$status=$vendor['status']==='approved'&&($_POST['status']??'')==='published'?'published':'draft';
    $data=[$title,$slug,trim((string)($_POST['summary']??'')),trim((string)($_POST['description']??'')),trim((string)($_POST['country']??'Türkiye')),trim((string)($_POST['city']??'')),trim((string)($_POST['district']??'')),trim((string)($_POST['address']??'')),max(1,(int)($_POST['max_guests']??1)),max(0,(int)($_POST['bedrooms']??0)),max(0,(int)($_POST['beds']??0)),max(0,(float)($_POST['bathrooms']??1)),max(0,(float)($_POST['base_nightly_price']??0)),max(0,(float)($_POST['weekend_nightly_price']??0)),max(0,(float)($_POST['cleaning_fee']??0)),strtoupper(substr(preg_replace('/[^A-Za-z]/','',(string)($_POST['currency']??'TRY')),0,3))?:'TRY',$status,($_POST['booking_type']??'')==='instant'?'instant':'request',((int)($_POST['category_id']??0)>0?(int)$_POST['category_id']:null)];
    if($id){$owned=$pdo->prepare('SELECT id FROM properties WHERE id=? AND vendor_id=?');$owned->execute([$id,(int)$vendor['id']]);if(!$owned->fetchColumn()){http_response_code(404);exit('İlan bulunamadı.');}$s=$pdo->prepare('UPDATE properties SET title=?,slug=?,summary=?,description=?,country=?,city=?,district=?,address=?,max_guests=?,bedrooms=?,beds=?,bathrooms=?,base_nightly_price=?,weekend_nightly_price=?,cleaning_fee=?,currency=?,status=?,booking_type=?,category_id=?,updated_at=NOW() WHERE id=? AND vendor_id=?');$s->execute([...$data,$id,(int)$vendor['id']]);}
    else{$s=$pdo->prepare('INSERT INTO properties(vendor_id,title,slug,summary,description,country,city,district,address,max_guests,bedrooms,beds,bathrooms,base_nightly_price,weekend_nightly_price,cleaning_fee,currency,status,booking_type,category_id,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');$s->execute([(int)$vendor['id'],...$data]);$id=(int)$pdo->lastInsertId();}
    $location=villa_resolve_location($pdo);
    $locSave=$pdo->prepare('UPDATE properties SET location_country_id=?,location_city_id=?,location_district_id=?,location_neighborhood_id=?,country=?,city=?,district=?,neighborhood=? WHERE id=? AND vendor_id=?');
    $locSave->execute([$location['location_country_id'],$location['location_city_id'],$location['location_district_id'],$location['location_neighborhood_id'],$location['country'],$location['city'],$location['district'],$location['neighborhood'],$id,(int)$vendor['id']]);
    store_property_uploads($pdo,$id);sync_property_amenities($pdo,$id);
    redirect('/vendor/properties');
});
$router->get('/admin/vendors', static function (): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();
    if((string)$pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn()!=='multi_vendor')redirect('/admin');
    $vendors=$pdo->query('SELECT v.*,u.username,u.email FROM vendors v JOIN users u ON u.id=v.user_id ORDER BY v.created_at DESC')->fetchAll();require APP_ROOT.'/views/admin/vendors.php';
});

$router->post('/admin/vendors/commission', static function (): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();
    if((string)$pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn()!=='multi_vendor')redirect('/admin');
    verify_csrf();
    $raw=trim((string)($_POST['commission_rate']??''));$rate=$raw===''?null:(float)$raw;
    if($rate!==null&&($rate<0||$rate>50)){http_response_code(422);exit('Komisyon oranı 0 ile 50 arasında olmalıdır.');}
    $s=Database::connection()->prepare('UPDATE vendors SET commission_rate=? WHERE id=?');$s->execute([$rate,(int)($_POST['id']??0)]);redirect('/admin/vendors');
});

$router->post('/admin/vendors/approve', static function (): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();
    if((string)$pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn()!=='multi_vendor')redirect('/admin');
    verify_csrf();$s=$pdo->prepare("UPDATE vendors SET status='approved' WHERE id=?");$s->execute([(int)($_POST['id']??0)]);redirect('/admin/vendors');
});


$router->get('/admin/properties/{id}/availability', static function (array $params): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();$s=$pdo->prepare('SELECT * FROM properties WHERE id=?');$s->execute([(int)$params['id']]);$property=$s->fetch();
    if(!$property){http_response_code(404);return;}
    $monthInput=(string)($_GET['month']??date('Y-m'));$monthDate=DateTimeImmutable::createFromFormat('!Y-m-d',$monthInput.'-01');
    if(!$monthDate||$monthDate->format('Y-m')!==$monthInput)$monthDate=new DateTimeImmutable('first day of this month');
    $calendarMonth=$monthDate->format('Y-m');$monthStart=$monthDate->format('Y-m-01');$monthEnd=$monthDate->modify('first day of next month')->format('Y-m-d');
    $s=$pdo->prepare('SELECT * FROM availability WHERE property_id=? AND date_from < ? AND date_to > ? ORDER BY date_from');$s->execute([(int)$property['id'],$monthEnd,$monthStart]);$ranges=$s->fetchAll();
    $s=$pdo->prepare("SELECT id,check_in,check_out,status,booking_code,guest_user_id FROM bookings WHERE property_id=? AND status IN ('pending','confirmed') AND check_in < ? AND check_out > ? ORDER BY check_in");$s->execute([(int)$property['id'],$monthEnd,$monthStart]);$calendarBookings=$s->fetchAll();
    $calendarPrev=$monthDate->modify('-1 month')->format('Y-m');$calendarNext=$monthDate->modify('+1 month')->format('Y-m');
    $saveUrl='/admin/properties/availability/save';$deleteUrl='/admin/properties/availability/delete';$backUrl='/admin/properties';require APP_ROOT.'/views/admin/availability.php';
});
$router->post('/admin/properties/availability/save', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$pdo=Database::connection();$pid=(int)($_POST['property_id']??0);$ruleId=(int)($_POST['id']??0);$from=(string)($_POST['date_from']??'');$to=(string)($_POST['date_to']??'');$status=($_POST['status']??'')==='available'?'available':'blocked';$price=trim((string)($_POST['nightly_price']??''));$a=DateTimeImmutable::createFromFormat('!Y-m-d',$from);$b=DateTimeImmutable::createFromFormat('!Y-m-d',$to);
    if(!$a||!$b||$a->format('Y-m-d')!==$from||$b->format('Y-m-d')!==$to||$a<new DateTimeImmutable('today')||$b<=$a){http_response_code(422);exit('Tarih aralığı geçersiz.');}
    $over=$pdo->prepare('SELECT id FROM availability WHERE property_id=? AND id<>? AND date_from < ? AND date_to > ? LIMIT 1');$over->execute([$pid,$ruleId,$to,$from]);if($over->fetchColumn()){http_response_code(409);exit('Bu tarih aralığı mevcut bir takvim kuralıyla çakışıyor.');}
    $amount=$price===''?null:max(0,(float)$price);
    if($ruleId>0){$s=$pdo->prepare('UPDATE availability SET date_from=?,date_to=?,status=?,nightly_price=? WHERE id=? AND property_id=?');$s->execute([$from,$to,$status,$amount,$ruleId,$pid]);}
    else{$s=$pdo->prepare('INSERT INTO availability(property_id,date_from,date_to,status,nightly_price) VALUES(?,?,?,?,?)');$s->execute([$pid,$from,$to,$status,$amount]);}
    redirect('/admin/properties/'.$pid.'/availability');
});
$router->post('/admin/properties/availability/delete', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$pid=(int)($_POST['property_id']??0);$s=Database::connection()->prepare('DELETE FROM availability WHERE id=? AND property_id=?');$s->execute([(int)($_POST['id']??0),$pid]);redirect('/admin/properties/'.$pid.'/availability');
});
$router->get('/vendor/properties/{id}/availability', static function (array $params): void {
    Auth::requireRole(['vendor']);$pdo=Database::connection();$v=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$v->execute([(int)Auth::user()['id']]);$vid=(int)$v->fetchColumn();$s=$pdo->prepare('SELECT * FROM properties WHERE id=? AND vendor_id=?');$s->execute([(int)$params['id'],$vid]);$property=$s->fetch();
    if(!$property){http_response_code(404);return;}
    $monthInput=(string)($_GET['month']??date('Y-m'));$monthDate=DateTimeImmutable::createFromFormat('!Y-m-d',$monthInput.'-01');
    if(!$monthDate||$monthDate->format('Y-m')!==$monthInput)$monthDate=new DateTimeImmutable('first day of this month');
    $calendarMonth=$monthDate->format('Y-m');$monthStart=$monthDate->format('Y-m-01');$monthEnd=$monthDate->modify('first day of next month')->format('Y-m-d');
    $s=$pdo->prepare('SELECT * FROM availability WHERE property_id=? AND date_from < ? AND date_to > ? ORDER BY date_from');$s->execute([(int)$property['id'],$monthEnd,$monthStart]);$ranges=$s->fetchAll();
    $s=$pdo->prepare("SELECT id,check_in,check_out,status,booking_code,guest_user_id FROM bookings WHERE property_id=? AND status IN ('pending','confirmed') AND check_in < ? AND check_out > ? ORDER BY check_in");$s->execute([(int)$property['id'],$monthEnd,$monthStart]);$calendarBookings=$s->fetchAll();
    $calendarPrev=$monthDate->modify('-1 month')->format('Y-m');$calendarNext=$monthDate->modify('+1 month')->format('Y-m');
    $saveUrl='/vendor/properties/availability/save';$deleteUrl='/vendor/properties/availability/delete';$backUrl='/vendor/properties';require APP_ROOT.'/views/admin/availability.php';
});
$router->post('/vendor/properties/availability/save', static function (): void {
    Auth::requireRole(['vendor']);verify_csrf();$pdo=Database::connection();$v=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$v->execute([(int)Auth::user()['id']]);$vid=(int)$v->fetchColumn();$pid=(int)($_POST['property_id']??0);$own=$pdo->prepare('SELECT id FROM properties WHERE id=? AND vendor_id=?');$own->execute([$pid,$vid]);if(!$own->fetchColumn()){http_response_code(404);return;}
    $ruleId=(int)($_POST['id']??0);$from=(string)($_POST['date_from']??'');$to=(string)($_POST['date_to']??'');$status=($_POST['status']??'')==='available'?'available':'blocked';$price=trim((string)($_POST['nightly_price']??''));$a=DateTimeImmutable::createFromFormat('!Y-m-d',$from);$b=DateTimeImmutable::createFromFormat('!Y-m-d',$to);
    if(!$a||!$b||$a->format('Y-m-d')!==$from||$b->format('Y-m-d')!==$to||$a<new DateTimeImmutable('today')||$b<=$a){http_response_code(422);exit('Tarih aralığı geçersiz.');}
    $over=$pdo->prepare('SELECT id FROM availability WHERE property_id=? AND id<>? AND date_from < ? AND date_to > ? LIMIT 1');$over->execute([$pid,$ruleId,$to,$from]);if($over->fetchColumn()){http_response_code(409);exit('Bu tarih aralığı mevcut bir takvim kuralıyla çakışıyor.');}
    $amount=$price===''?null:max(0,(float)$price);
    if($ruleId>0){$s=$pdo->prepare('UPDATE availability SET date_from=?,date_to=?,status=?,nightly_price=? WHERE id=? AND property_id=?');$s->execute([$from,$to,$status,$amount,$ruleId,$pid]);}
    else{$s=$pdo->prepare('INSERT INTO availability(property_id,date_from,date_to,status,nightly_price) VALUES(?,?,?,?,?)');$s->execute([$pid,$from,$to,$status,$amount]);}
    redirect('/vendor/properties/'.$pid.'/availability');
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
    $property = []; $existingImages=[]; $deleteMediaAction='/admin/properties/media/delete'; $categories = Database::connection()->query('SELECT id,name FROM property_categories WHERE is_active=1 ORDER BY sort_order,name')->fetchAll(); $amenities=Database::connection()->query('SELECT id,name FROM amenities WHERE is_active=1 ORDER BY sort_order,name')->fetchAll(); $selectedAmenities=[]; $defaultCurrency=(string)(Database::connection()->query("SELECT setting_value FROM settings WHERE setting_key='app.currency'")->fetchColumn() ?: 'TRY');
    require APP_ROOT . '/views/admin/property-form.php';
});

$router->get('/admin/properties/{id}', static function (array $params): void {
    Auth::requireRole(['super_admin', 'admin']);
    $pdo = Database::connection();
    $stmt = $pdo->prepare('SELECT * FROM properties WHERE id=? LIMIT 1');
    $stmt->execute([(int)($params['id'] ?? 0)]);
    $property = $stmt->fetch();
    if (!$property) { http_response_code(404); echo 'Konaklama bulunamadı.'; return; }
    $mediaStmt=$pdo->prepare('SELECT * FROM property_media WHERE property_id=? ORDER BY is_cover DESC,sort_order,id');$mediaStmt->execute([(int)$property['id']]);$existingImages=$mediaStmt->fetchAll();$deleteMediaAction='/admin/properties/media/delete';
    $categories = Database::connection()->query('SELECT id,name FROM property_categories WHERE is_active=1 ORDER BY sort_order,name')->fetchAll(); $amenities=Database::connection()->query('SELECT id,name FROM amenities WHERE is_active=1 ORDER BY sort_order,name')->fetchAll(); $a=$pdo->prepare('SELECT amenity_id FROM property_amenities WHERE property_id=?');$a->execute([(int)$property['id']]);$selectedAmenities=array_map('intval',$a->fetchAll(PDO::FETCH_COLUMN)); $defaultCurrency=(string)(Database::connection()->query("SELECT setting_value FROM settings WHERE setting_key='app.currency'")->fetchColumn() ?: 'TRY');
    require APP_ROOT . '/views/admin/property-form.php';
});


$router->post('/vendor/properties/delete', static function (): void {
    Auth::requireRole(['vendor']);verify_csrf();$pdo=Database::connection();
    $v=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$v->execute([(int)Auth::user()['id']]);$vendorId=(int)$v->fetchColumn();$id=(int)($_POST['id']??0);
    $check=$pdo->prepare('SELECT id FROM properties WHERE id=? AND vendor_id=?');$check->execute([$id,$vendorId]);
    if(!$check->fetchColumn()){http_response_code(404);exit('İlan bulunamadı.');}
    $b=$pdo->prepare('SELECT COUNT(*) FROM bookings WHERE property_id=?');$b->execute([$id]);if((int)$b->fetchColumn()>0){$archive=$pdo->prepare("UPDATE properties SET status='archived',updated_at=NOW() WHERE id=? AND vendor_id=?");$archive->execute([$id,$vendorId]);}else{delete_property_uploads($pdo,$id);$del=$pdo->prepare('DELETE FROM properties WHERE id=? AND vendor_id=?');$del->execute([$id,$vendorId]);}redirect('/vendor/properties');
});

$router->post('/admin/properties/media/manage', static function (): void {
    Auth::requireRole(['super_admin','admin']); verify_csrf();
    $pdo=Database::connection(); $pid=(int)($_POST['property_id']??0); $action=(string)($_POST['action']??'');
    $check=$pdo->prepare('SELECT id FROM properties WHERE id=?'); $check->execute([$pid]); if(!$check->fetchColumn()){http_response_code(404);exit('Konaklama bulunamadı.');}
    if($action==='cover'){
        $mid=(int)($_POST['media_id']??0);$valid=$pdo->prepare('SELECT id FROM property_media WHERE id=? AND property_id=?');$valid->execute([$mid,$pid]);if(!$valid->fetchColumn()){http_response_code(404);exit('Fotoğraf bulunamadı.');}
        $pdo->beginTransaction();try{$pdo->prepare('UPDATE property_media SET is_cover=0 WHERE property_id=?')->execute([$pid]);$pdo->prepare('UPDATE property_media SET is_cover=1 WHERE id=? AND property_id=?')->execute([$mid,$pid]);$pdo->commit();}catch(Throwable $e){$pdo->rollBack();throw $e;}
    }elseif($action==='sort'){
        $ids=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['media_ids']??[])),static fn($v)=>$v>0)));
        $all=$pdo->prepare('SELECT id FROM property_media WHERE property_id=?');$all->execute([$pid]);$known=array_map('intval',$all->fetchAll(PDO::FETCH_COLUMN));
        if(count($ids)!==count($known)||array_diff($ids,$known)){http_response_code(422);exit('Fotoğraf sıralaması doğrulanamadı.');}
        $pdo->beginTransaction();try{$u=$pdo->prepare('UPDATE property_media SET sort_order=? WHERE id=? AND property_id=?');foreach($ids as $i=>$mid)$u->execute([$i,$mid,$pid]);$pdo->commit();}catch(Throwable $e){$pdo->rollBack();throw $e;}
    }else{http_response_code(422);exit('Geçersiz işlem.');}
    header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>true],JSON_THROW_ON_ERROR);
});
$router->post('/vendor/properties/media/manage', static function (): void {
    Auth::requireRole(['vendor']); verify_csrf();$pdo=Database::connection();
    $v=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$v->execute([(int)Auth::user()['id']]);$vid=(int)$v->fetchColumn();$pid=(int)($_POST['property_id']??0);
    $own=$pdo->prepare('SELECT id FROM properties WHERE id=? AND vendor_id=?');$own->execute([$pid,$vid]);if(!$own->fetchColumn()){http_response_code(404);exit('Konaklama bulunamadı.');}
    $action=(string)($_POST['action']??'');
    if($action==='cover'){$mid=(int)($_POST['media_id']??0);$valid=$pdo->prepare('SELECT id FROM property_media WHERE id=? AND property_id=?');$valid->execute([$mid,$pid]);if(!$valid->fetchColumn()){http_response_code(404);exit('Fotoğraf bulunamadı.');}$pdo->beginTransaction();try{$pdo->prepare('UPDATE property_media SET is_cover=0 WHERE property_id=?')->execute([$pid]);$pdo->prepare('UPDATE property_media SET is_cover=1 WHERE id=? AND property_id=?')->execute([$mid,$pid]);$pdo->commit();}catch(Throwable $e){$pdo->rollBack();throw $e;}}
    elseif($action==='sort'){$ids=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['media_ids']??[])),static fn($v)=>$v>0)));$all=$pdo->prepare('SELECT id FROM property_media WHERE property_id=?');$all->execute([$pid]);$known=array_map('intval',$all->fetchAll(PDO::FETCH_COLUMN));if(count($ids)!==count($known)||array_diff($ids,$known)){http_response_code(422);exit('Fotoğraf sıralaması doğrulanamadı.');}$pdo->beginTransaction();try{$u=$pdo->prepare('UPDATE property_media SET sort_order=? WHERE id=? AND property_id=?');foreach($ids as $i=>$mid)$u->execute([$i,$mid,$pid]);$pdo->commit();}catch(Throwable $e){$pdo->rollBack();throw $e;}}
    else{http_response_code(422);exit('Geçersiz işlem.');}
    header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>true],JSON_THROW_ON_ERROR);
});
$router->post('/admin/properties/media/delete', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$pid=(int)($_POST['property_id']??0);$mid=(int)($_POST['media_id']??0);delete_property_media(Database::connection(),$pid,$mid);redirect('/admin/properties/'.$pid);
});
$router->post('/vendor/properties/media/delete', static function (): void {
    Auth::requireRole(['vendor']);verify_csrf();$pdo=Database::connection();$v=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$v->execute([(int)Auth::user()['id']]);$vid=(int)$v->fetchColumn();$pid=(int)($_POST['property_id']??0);$own=$pdo->prepare('SELECT id FROM properties WHERE id=? AND vendor_id=?');$own->execute([$pid,$vid]);if(!$own->fetchColumn()){http_response_code(404);return;}delete_property_media($pdo,$pid,(int)($_POST['media_id']??0));redirect('/vendor/properties/'.$pid);
});

$router->post('/admin/properties/delete', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$pdo=Database::connection();$id=(int)($_POST['id']??0);
    $b=$pdo->prepare('SELECT COUNT(*) FROM bookings WHERE property_id=?');$b->execute([$id]);if((int)$b->fetchColumn()>0){$archive=$pdo->prepare("UPDATE properties SET status='archived',updated_at=NOW() WHERE id=?");$archive->execute([$id]);}else{delete_property_uploads($pdo,$id);$del=$pdo->prepare('DELETE FROM properties WHERE id=?');$del->execute([$id]);}redirect('/admin/properties');
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
        max(0,(float)($_POST['bathrooms'] ?? 1)), max(0,(float)($_POST['base_nightly_price'] ?? 0)), max(0,(float)($_POST['weekend_nightly_price'] ?? 0)), max(0,(float)($_POST['cleaning_fee'] ?? 0)),
        strtoupper(substr(preg_replace('/[^A-Za-z]/','',(string)($_POST['currency'] ?? 'TRY')),0,3)),
        $status, $bookingType, ((int)($_POST['category_id'] ?? 0) > 0 ? (int)$_POST['category_id'] : null)
    ];
    if ($data[15] === '') $data[15] = 'TRY';
    $check = $pdo->prepare('SELECT id FROM properties WHERE slug=? AND id<>? LIMIT 1');
    $check->execute([$slug,$id]);
    if ($check->fetchColumn()) $slug .= '-' . bin2hex(random_bytes(3));
    $data[1] = $slug;
    if ($id > 0) {
        $data[] = $id;
        $stmt = $pdo->prepare('UPDATE properties SET title=?,slug=?,summary=?,description=?,country=?,city=?,district=?,address=?,max_guests=?,bedrooms=?,beds=?,bathrooms=?,base_nightly_price=?,weekend_nightly_price=?,cleaning_fee=?,currency=?,status=?,booking_type=?,category_id=?,updated_at=NOW() WHERE id=?');
        $stmt->execute($data);
    } else {
        $stmt = $pdo->prepare('INSERT INTO properties (title,slug,summary,description,country,city,district,address,max_guests,bedrooms,beds,bathrooms,base_nightly_price,weekend_nightly_price,cleaning_fee,currency,status,booking_type,category_id,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())');
        $stmt->execute($data);
        $id = (int)$pdo->lastInsertId();
    }
    $location=villa_resolve_location($pdo);
    $locSave=$pdo->prepare('UPDATE properties SET location_country_id=?,location_city_id=?,location_district_id=?,location_neighborhood_id=?,country=?,city=?,district=?,neighborhood=? WHERE id=?');
    $locSave->execute([$location['location_country_id'],$location['location_city_id'],$location['location_district_id'],$location['location_neighborhood_id'],$location['country'],$location['city'],$location['district'],$location['neighborhood'],$id]);
    if ($id > 0) { store_property_uploads($pdo, $id); sync_property_amenities($pdo, $id); }
    redirect('/admin/properties');
});




$router->get('/admin/amenities', static function (): void {
    Auth::requireRole(['super_admin','admin']);$amenities=Database::connection()->query('SELECT * FROM amenities ORDER BY sort_order,name')->fetchAll();require APP_ROOT.'/views/admin/amenities.php';
});
$router->post('/admin/amenities/save', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$name=trim((string)($_POST['name']??''));$icon=trim((string)($_POST['icon']??''));if($name===''){http_response_code(422);exit('Özellik adı zorunludur.');}
    $s=Database::connection()->prepare('INSERT INTO amenities(name,icon,is_active,sort_order) VALUES(?,?,1,?)');$s->execute([$name,$icon,(int)($_POST['sort_order']??10)]);redirect('/admin/amenities');
});
$router->post('/admin/amenities/delete', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$s=Database::connection()->prepare('DELETE FROM amenities WHERE id=?');$s->execute([(int)($_POST['id']??0)]);redirect('/admin/amenities');
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
    $slug=trim(preg_replace('/[^a-z0-9-]+/','-',$slug),'-');$content=trim((string)($_POST['content']??''));$metaTitle=trim((string)($_POST['meta_title']??''));$metaDescription=trim((string)($_POST['meta_description']??''));$status=($_POST['status']??'')==='published'?'published':'draft';
    if($title===''||$slug===''||$content===''){http_response_code(422);exit('Başlık, URL ve içerik zorunludur.');}
    if($id){$s=$pdo->prepare("UPDATE cms_pages SET title=?,slug=?,content=?,status=?,meta_title=?,meta_description=? WHERE id=? AND language_code='tr'");$s->execute([$title,$slug,$content,$status,$metaTitle,$metaDescription,$id]);}
    else{$s=$pdo->prepare("INSERT INTO cms_pages(language_code,title,slug,content,status,meta_title,meta_description) VALUES('tr',?,?,?,?,?,?)");$s->execute([$title,$slug,$content,$status,$metaTitle,$metaDescription]);}
    redirect('/admin/pages');
});
$router->get('/page/{slug}', static function (array $params): void {
    $s=Database::connection()->prepare("SELECT * FROM cms_pages WHERE slug=? AND language_code='tr' AND status='published' LIMIT 1");$s->execute([$params['slug']]);$page=$s->fetch();
    if(!$page){http_response_code(404);echo 'Sayfa bulunamadı.';return;}
    $pdo=Database::connection();
    $content=$pdo->query("SELECT content_key,content_value FROM site_content WHERE language_code='tr'")->fetchAll(PDO::FETCH_KEY_PAIR);
    $siteName=$content['site_name']??'VillaStay';
    $navStmt=$pdo->prepare('SELECT label,url,target FROM navigation_items WHERE location=? AND language_code=? AND is_active=1 ORDER BY sort_order,id');
    $navStmt->execute(['header','tr']); $navigation=$navStmt->fetchAll();
    $navStmt->execute(['footer','tr']); $footerNavigation=$navStmt->fetchAll();
    $mode=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode' LIMIT 1")->fetchColumn() ?: 'single_vendor');
    $user=Auth::user(); $locale='tr';
    require APP_ROOT.'/views/site/page.php';
});




$router->get('/admin/translations', static function (): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();$languages=$pdo->query('SELECT code,name FROM languages WHERE is_active=1 ORDER BY is_default DESC,name')->fetchAll();
    $locale=(string)($_GET['lang']??'tr');$codes=array_column($languages,'code');if(!in_array($locale,$codes,true))$locale='tr';
    $s=$pdo->prepare('SELECT translation_key,translation_value FROM translations WHERE language_code=? ORDER BY translation_key');$s->execute([$locale]);$translations=$s->fetchAll(PDO::FETCH_KEY_PAIR);require APP_ROOT.'/views/admin/translations.php';
});
$router->post('/admin/translations', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$pdo=Database::connection();$locale=(string)($_POST['lang']??'tr');$check=$pdo->prepare('SELECT code FROM languages WHERE code=? AND is_active=1');$check->execute([$locale]);if(!$check->fetchColumn()){$locale='tr';}
    $save=$pdo->prepare('INSERT INTO translations(language_code,translation_key,translation_value) VALUES(?,?,?) ON DUPLICATE KEY UPDATE translation_value=VALUES(translation_value)');
    foreach((array)($_POST['translations']??[]) as $key=>$value){$key=(string)$key;if(preg_match('/^[a-zA-Z0-9_.-]{1,190}$/',$key))$save->execute([$locale,$key,(string)$value]);}
    $newKey=trim((string)($_POST['new_key']??''));$newValue=(string)($_POST['new_value']??'');if($newKey!==''&&preg_match('/^[a-zA-Z0-9_.-]{1,190}$/',$newKey))$save->execute([$locale,$newKey,$newValue]);
    redirect('/admin/translations?lang='.rawurlencode($locale));
});

$router->get('/admin/users', static function (): void {
    Auth::requireRole(['super_admin','admin']);$users=Database::connection()->query('SELECT id,username,email,role,status,created_at FROM users ORDER BY created_at DESC LIMIT 500')->fetchAll();$currentUser=Auth::user();require APP_ROOT.'/views/admin/users.php';
});
$router->post('/admin/users/status', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$id=(int)($_POST['id']??0);$status=($_POST['status']??'')==='active'?'active':'disabled';
    if($id===(int)Auth::user()['id']){http_response_code(422);exit('Kendi hesabınızın durumunu bu ekrandan değiştiremezsiniz.');}
    $s=Database::connection()->prepare("UPDATE users SET status=? WHERE id=? AND role<>'super_admin'");$s->execute([$status,$id]);redirect('/admin/users');
});

$router->get('/admin/settings', static function (): void {
    Auth::requireRole(['super_admin','admin']);$settings=Database::connection()->query("SELECT setting_key,setting_value FROM settings WHERE setting_key IN ('platform.commission_percent','app.currency','app.timezone')")->fetchAll(PDO::FETCH_KEY_PAIR);require APP_ROOT.'/views/admin/settings.php';
});
$router->post('/admin/settings', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$commission=(float)($_POST['commission']??12);$currency=strtoupper(trim((string)($_POST['currency']??'TRY')));$timezone=trim((string)($_POST['timezone']??'Europe/Istanbul'));
    if($commission<0||$commission>50||!preg_match('/^[A-Z]{3}$/',$currency)||!in_array($timezone,DateTimeZone::listIdentifiers(),true)){http_response_code(422);exit('Ayar değerlerini kontrol edin.');}
    $s=Database::connection()->prepare('INSERT INTO settings(setting_key,setting_value,autoload) VALUES(?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
    $s->execute(['platform.commission_percent',(string)$commission]);$s->execute(['app.currency',$currency]);$s->execute(['app.timezone',$timezone]);redirect('/admin/settings');
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
        'search_button'=>'Arama butonu',
        'guest_select_label'=>'Misafir seçimi etiketi',
        'guest_unit'=>'Misafir birimi',
        'all_categories'=>'Tüm kategoriler etiketi',
        'property_about'=>'Villa detay başlığı',
        'property_reserve'=>'Rezervasyon butonu',
        'property_no_images'=>'Görsel yok metni',
        'booking_notice'=>'Rezervasyon bilgilendirme metni',
        'footer_text'=>'Alt bilgi metni'
    ];
    require APP_ROOT . '/views/admin/content.php';
});

$router->post('/admin/content', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    verify_csrf();
    $allowed = ['site_name','home_eyebrow','home_title','home_subtitle','home_search_placeholder','featured_title','featured_subtitle','empty_properties','search_button','guest_select_label','guest_unit','all_categories','property_about','property_reserve','property_no_images','booking_notice','footer_text'];
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
        $s=$pdo->prepare('SELECT property_id,guest_user_id,check_in,check_out,status FROM bookings WHERE id=? FOR UPDATE');$s->execute([$id]);$booking=$s->fetch();
        if(!$booking){$pdo->rollBack();http_response_code(404);exit('Rezervasyon bulunamadı.');}
        if($status==='confirmed'){
            $conflict=$pdo->prepare("SELECT id FROM bookings WHERE property_id=? AND id<>? AND status='confirmed' AND check_in < ? AND check_out > ? LIMIT 1");
            $conflict->execute([(int)$booking['property_id'],$id,$booking['check_out'],$booking['check_in']]);
            if($conflict->fetchColumn()){$pdo->rollBack();http_response_code(409);exit('Bu tarihlerde onaylanmış başka bir rezervasyon var.');}
            $blocked=$pdo->prepare("SELECT id FROM availability WHERE property_id=? AND status<>'available' AND date_from < ? AND date_to > ? LIMIT 1");
            $blocked->execute([(int)$booking['property_id'],$booking['check_out'],$booking['check_in']]);
            if($blocked->fetchColumn()){$pdo->rollBack();http_response_code(409);exit('Bu rezervasyon tarihleri takvimde kapatılmış.');}
        }
        $update=$pdo->prepare('UPDATE bookings SET status=? WHERE id=?');$update->execute([$status,$id]);
        $statusTitle = ['pending'=>'Rezervasyon talebiniz beklemede','confirmed'=>'Rezervasyonunuz onaylandı','cancelled'=>'Rezervasyonunuz iptal edildi','completed'=>'Konaklamanız tamamlandı'][$status];
        $notice=$pdo->prepare('INSERT INTO notifications(user_id,notification_type,title,body,created_at) VALUES(?,?,?,?,NOW())');
        $notice->execute([(int)$booking['guest_user_id'],'booking.status_changed',$statusTitle,'Rezervasyon durumunuz güncellendi. Rezervasyon kodunuzu hesabınızdan görüntüleyebilirsiniz.']);
        $log=$pdo->prepare('INSERT INTO audit_logs(user_id,action,entity_type,entity_id,ip_address,details,created_at) VALUES(?,?,?,?,?,?,NOW())');
        $log->execute([(int)Auth::user()['id'],'booking.status_changed','booking',$id,$_SERVER['REMOTE_ADDR']??null,json_encode(['from'=>$booking['status'],'to'=>$status],JSON_UNESCAPED_UNICODE)]);
        $pdo->commit();redirect('/admin/bookings');
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Booking status update failed: '.$e->getMessage());http_response_code(500);exit('Rezervasyon güncellenemedi.');}
});

$router->get('/admin', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    $pdo = Database::connection();
    $mode = (string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn() ?: 'single_vendor');
    $metrics = [
        'properties' => (int)$pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn(),
        'bookings' => (int)$pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),
        'users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'pending' => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
    ];
    require APP_ROOT . '/views/admin/dashboard.php';
});
