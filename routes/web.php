<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Database;
use App\Core\Router;
use App\Core\Translator;
use App\Core\ThemeManager;

function villa_navigation_tree(PDO $pdo, string $location, string $locale): array {
    $stmt=$pdo->prepare('SELECT id,parent_id,label,url,target FROM navigation_items WHERE location=? AND language_code=? AND is_active=1 ORDER BY sort_order,id');
    $stmt->execute([$location,$locale]);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);$byId=[];$children=[];$roots=[];
    foreach($rows as $row)$byId[(int)$row['id']]=$row;
    foreach($rows as $row){$id=(int)$row['id'];$parent=(int)($row['parent_id']??0);
        if($parent>0){if(isset($byId[$parent])&&$parent!==$id)$children[$parent][]=$row;elseif($parent===$id)$roots[]=$row;}
        else $roots[]=$row;
    }
    foreach($roots as &$root)$root['children']=$children[(int)$root['id']]??[];
    unset($root);return $roots;
}

function villa_render_account_view(string $view, array $variables): void {
    $pdo = Database::connection();
    $locale = 'tr';
    $navigation = villa_navigation_tree($pdo, 'header', $locale);
    $footerNavigation = villa_navigation_tree($pdo, 'footer', $locale);
    $contentStmt = $pdo->prepare('SELECT content_key,content_value FROM site_content WHERE language_code=?');
    $contentStmt->execute([$locale]);
    $content = $contentStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $siteName = (string)($content['site_name'] ?? 'VillaStay');
    $accountView = $view;
    ThemeManager::render('account.php', array_merge($variables, compact('navigation','footerNavigation','content','siteName','accountView')));
}

function villa_iyzico_checkout_form_html(string $content): ?string {
    if($content===''||strlen($content)>2000000)return null;
    if(stripos($content,'<script')!==false)return $content;
    $decoded=base64_decode($content,true);
    return is_string($decoded)&&$decoded!==''&&stripos($decoded,'<script')!==false?$decoded:null;
}
function villa_iyzico_checkout_redirect_url(string $url): ?string {
    if($url===''||strlen($url)>2048)return null;
    $parts=parse_url($url);
    if(!is_array($parts))return null;
    $host=strtolower((string)($parts['host']??''));
    $path=(string)($parts['path']??'');
    if(strtolower((string)($parts['scheme']??''))!=='https'
        ||!in_array($host,['sandbox-api.iyzipay.com','sandbox-cpp.iyzipay.com'],true)
        ||isset($parts['user'])||isset($parts['pass'])||isset($parts['fragment']))return null;
    parse_str((string)($parts['query']??''),$query);
    $isSandboxCpp=$host==='sandbox-cpp.iyzipay.com'
        &&in_array($path,['','/'],true)
        &&preg_match('/^[A-Za-z0-9-]{16,128}$/',(string)($query['token']??''))===1;
    $isSandboxApi=$host==='sandbox-api.iyzipay.com'
        &&str_starts_with($path,'/checkoutform/');
    return ($isSandboxCpp||$isSandboxApi)?$url:null;
}
function villa_safe_return_url(string $url): string { $path=parse_url($url,PHP_URL_PATH)?:'/'; if(!str_starts_with($path,'/')||str_starts_with($path,'//'))return '/'; $query=parse_url($url,PHP_URL_QUERY); return $path.($query!==null?'?'.$query:''); }
function villa_return_with_status(string $url,string $key,string $value): string { $back=villa_safe_return_url($url); return $back.(str_contains($back,'?')?'&':'?').rawurlencode($key).'='.rawurlencode($value); }

function villa_resolve_location(PDO $pdo): array {
    $geoCountry=trim((string)($_POST['geo_country']??''));
    $geoCity=trim((string)($_POST['geo_city']??''));
    if($geoCountry!==''&&$geoCity!==''){
        $resolve=static function(string $type,string $name,?int $parent) use($pdo): ?int {
            $name=trim($name);if(preg_match('/^.{0,190}/us',$name,$nameMatch))$name=$nameMatch[0];
            if($name==='')return null;
            $find=$pdo->prepare('SELECT id FROM locations WHERE parent_id <=> ? AND location_type=? AND name=? LIMIT 1');
            $find->execute([$parent,$type,$name]);$id=$find->fetchColumn();
            if($id!==false)return (int)$id;
            $insert=$pdo->prepare('INSERT INTO locations(parent_id,location_type,name,sort_order,is_active) VALUES(?,?,?,10,1)');
            $insert->execute([$parent,$type,$name]);return (int)$pdo->lastInsertId();
        };
        $countryId=$resolve('country',$geoCountry,null);
        $cityId=$resolve('city',$geoCity,$countryId);
        $districtName=trim((string)($_POST['geo_district']??''));$districtId=$districtName!==''?$resolve('district',$districtName,$cityId):null;
        $neighborhoodName=trim((string)($_POST['geo_neighborhood']??''));$neighborhoodId=($neighborhoodName!==''&&$districtId)?$resolve('neighborhood',$neighborhoodName,$districtId):null;
        return ['location_country_id'=>$countryId,'location_city_id'=>$cityId,'location_district_id'=>$districtId,'location_neighborhood_id'=>$neighborhoodId,'country'=>$geoCountry,'city'=>$geoCity,'district'=>$districtName,'neighborhood'=>$neighborhoodName];
    }
    $ids=['country'=>(int)($_POST['location_country_id']??0),'city'=>(int)($_POST['location_city_id']??0),'district'=>(int)($_POST['location_district_id']??0),'neighborhood'=>(int)($_POST['location_neighborhood_id']??0)];
    $get=static function(int $id,string $type,?int $parent) use($pdo): ?array {if($id<1)return null;$s=$pdo->prepare('SELECT id,parent_id,name FROM locations WHERE id=? AND location_type=? AND is_active=1 LIMIT 1');$s->execute([$id,$type]);$row=$s->fetch();if(!$row||($parent!==null&&(int)$row['parent_id']!==$parent))return null;return $row;};
    $country=$get($ids['country'],'country',null);$city=$get($ids['city'],'city',$ids['country']);$district=$ids['district']?$get($ids['district'],'district',$ids['city']):null;$neighborhood=$ids['neighborhood']?$get($ids['neighborhood'],'neighborhood',$ids['district']):null;
    if(!$country||!$city||($ids['district']&&!$district)||($ids['neighborhood']&&!$neighborhood)||($neighborhood&&!$district))throw new InvalidArgumentException('Lütfen adres önerilerinden geçerli bir konum seçin.');
    return ['location_country_id'=>$ids['country'],'location_city_id'=>$ids['city'],'location_district_id'=>$district?$ids['district']:null,'location_neighborhood_id'=>$neighborhood?$ids['neighborhood']:null,'country'=>$country['name'],'city'=>$city['name'],'district'=>$district['name']??'','neighborhood'=>$neighborhood['name']??''];
}
function villa_update_http_get(string $url, bool $json = true): ?array {
    $headers = "User-Agent: VillaRentalPlatform-Updater/1.0\r\nAccept: " . ($json ? "application/vnd.github+json" : "text/plain") . "\r\n";
    $token = trim((string)(getenv('VILLA_GITHUB_TOKEN') ?: ''));
    if ($token !== '') $headers .= "Authorization: Bearer " . $token . "\r\n";
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>4,CURLOPT_HTTPHEADER=>explode("\r\n",trim($headers))]);
        $body = curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE); curl_close($ch);
    } else {
        $context = stream_context_create(['http'=>['method'=>'GET','header'=>$headers,'timeout'=>4,'ignore_errors'=>true]]);
        $body = @file_get_contents($url, false, $context); $code = 0;
        foreach (($http_response_header ?? []) as $header) if (preg_match('/^HTTP\\/\\S+\\s+(\\d+)/',$header,$m)) $code=(int)$m[1];
    }
    if (!is_string($body) || $code < 200 || $code >= 300) return null;
    if (!$json) return ['text'=>trim($body)];
    $decoded=json_decode($body,true);
    return is_array($decoded)?$decoded:null;
}

function villa_github_update_info(string $root): array {
    $cacheDir=$root.'/storage/cache';
    if(!is_dir($cacheDir)) @mkdir($cacheDir,0750,true);
    $cacheFile=$cacheDir.'/github-update-check.json';
    if(is_file($cacheFile) && (time()-(int)filemtime($cacheFile))<300){
        $cached=json_decode((string)@file_get_contents($cacheFile),true);
        if(is_array($cached)) return $cached;
    }
    $base='https://api.github.com/repos/gokhanbdag-web/Villa-Rental-Platform';
    $commit=villa_update_http_get($base.'/commits/main');
    $version=villa_update_http_get('https://raw.githubusercontent.com/gokhanbdag-web/Villa-Rental-Platform/main/VERSION',false);
    $release=villa_update_http_get($base.'/releases/latest');
    $info=[
        'checked_at'=>date(DATE_ATOM),
        'available'=>false,
        'connected'=>is_array($commit),
        'auth_configured'=>trim((string)(getenv('VILLA_GITHUB_TOKEN') ?: '')) !== '',
        'version'=>is_array($version)?(string)($version['text']??''):'',
        'commit'=>is_array($commit)?(string)($commit['sha']??''):'',
        'commit_url'=>is_array($commit)?(string)($commit['html_url']??''):'',
        'commit_message'=>is_array($commit)?(string)($commit['commit']['message']??''):'',
        'commit_date'=>is_array($commit)?(string)($commit['commit']['committer']['date']??''):'',
        'release_name'=>is_array($release)?(string)($release['name']??$release['tag_name']??''):'',
        'release_notes'=>is_array($release)?(string)($release['body']??''):'',
        'release_url'=>is_array($release)?(string)($release['html_url']??''):'',
    ];
    if($info['commit']!==''){
        $statusFile=$root.'/storage/deploy-status.json';
        $deployed=[];
        if(is_file($statusFile)){$raw=@file_get_contents($statusFile);$parsed=is_string($raw)?json_decode($raw,true):null;if(is_array($parsed))$deployed=$parsed;}
        $deployedCommit=(string)($deployed['commit'] ?? (getenv('VILLA_DEPLOY_COMMIT') ?: ''));
        $info['deployed_commit']=$deployedCommit;
        $info['deployment_status']=(string)($deployed['status']??'unknown');
        $info['deployment_updated_at']=(string)($deployed['updated_at']??'');
        $info['deployment_backup']=(string)($deployed['backup']??'');
        $info['deployment_known']=$deployedCommit!=='';
        $info['available']=$deployedCommit!=='' ? !hash_equals($deployedCommit,$info['commit']) : false;
    }
    if(is_dir($cacheDir)) @file_put_contents($cacheFile,json_encode($info,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX);
    return $info;
}

$router->get('/admin/updates', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    $root=dirname(__DIR__);
    $versionFile=$root.'/VERSION';
    $installedVersion=is_file($versionFile)?trim((string)file_get_contents($versionFile)):'Sürüm bilgisi yok';
    $migrationTotal=count(glob($root.'/database/migrations/*.sql')?:[]);
    $appliedMigrations=0;$migrationError='';
    try{$appliedMigrations=(int)Database::connection()->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();}
    catch(Throwable $e){$migrationError='Migration kayıt tablosu henüz okunamadı.';}
    $githubUpdate=villa_github_update_info($root);
    $logFile=trim((string)(getenv('VILLA_DEPLOY_LOG')?:''));
    $logReadable=$logFile!==''&&is_file($logFile)&&is_readable($logFile);
    $logLines=[];$lastSuccess=null;
    if($logReadable&&($handle=@fopen($logFile,'rb'))){
        $size=filesize($logFile);
        if($size!==false){fseek($handle,max(0,$size-16384));$chunk=stream_get_contents($handle);
            if(is_string($chunk))foreach(preg_split('/\\r?\\n/',$chunk)?:[] as $line){$line=trim($line);if($line!==''&&preg_match('/^(=== DEPLOY |BAŞARILI:|SUCCESS:|PHP dosyaları kontrol ediliyor|Canlı kod yedekleniyor|Yeni sürüm aktarılıyor|Canlı PHP kontrolü|ERROR:|HATA:)/iu',$line))$logLines[]=mb_substr($line,0,240);}
            $logLines=array_slice($logLines,-25);
            foreach(array_reverse($logLines) as $line)if(str_contains($line,'BAŞARILI:')||str_contains($line,'SUCCESS:')){$lastSuccess=$line;break;}
        }fclose($handle);
    }
    $deploymentHistory=[];
    $historyFile=$root.'/storage/deploy-history.jsonl';
    if(is_file($historyFile)&&is_readable($historyFile)){
        $historyLines=@file($historyFile,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[];
        foreach(array_slice($historyLines,-20) as $historyLine){
            $historyItem=json_decode($historyLine,true);
            if(is_array($historyItem))$deploymentHistory[]=$historyItem;
        }
        $deploymentHistory=array_reverse($deploymentHistory);
    }
    ThemeManager::renderTemplate('templates/pages/admin/updates.php',get_defined_vars());
});

$router->get('/admin/updates/status', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    $root = dirname(__DIR__);
    $status = [];
    $statusFile = $root . '/storage/deploy-status.json';
    if (is_file($statusFile) && is_readable($statusFile)) {
        $decoded = json_decode((string)@file_get_contents($statusFile), true);
        if (is_array($decoded)) $status = $decoded;
    }
    $history = [];
    $historyFile = $root . '/storage/deploy-history.jsonl';
    if (is_file($historyFile) && is_readable($historyFile)) {
        $lines = @file($historyFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach (array_slice($lines, -20) as $line) {
            $event = json_decode($line, true);
            if (is_array($event)) $history[] = $event;
        }
        $history = array_reverse($history);
    }
    echo json_encode(['status' => $status, 'history' => $history], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
});

$router->get('/admin/homepage', static function (): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();
    $sections=$pdo->query('SELECT * FROM homepage_sections ORDER BY sort_order,id')->fetchAll();
    $mode=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn() ?: 'single_vendor');
    if($mode!=='multi_vendor')$sections=array_values(array_filter($sections,static fn(array $section):bool=>$section['section_key']!=='hosts'));
    ThemeManager::renderTemplate('templates/pages/admin/homepage.php', get_defined_vars());
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
    header('Cache-Control: no-store');
    $q=trim((string)($_GET['q']??''));
    if($q===''||mb_strlen($q)<1){echo '[]';return;}
    $like=$q.'%';
    $sql="SELECT l.id,l.location_type,l.name,
        CASE
            WHEN l.location_type='city' THEN l.name
            WHEN l.location_type='district' THEN TRIM(BOTH ' / ' FROM CONCAT_WS(' / ',p.name,l.name))
            WHEN l.location_type='neighborhood' THEN TRIM(BOTH ' / ' FROM CONCAT_WS(' / ',g.name,p.name,l.name))
            ELSE l.name
        END AS label
        FROM locations l
        LEFT JOIN locations p ON p.id=l.parent_id
        LEFT JOIN locations g ON g.id=p.parent_id
        WHERE l.is_active=1
          AND (p.id IS NULL OR p.is_active=1)
          AND (g.id IS NULL OR g.is_active=1)
          AND l.name LIKE ?
        ORDER BY FIELD(l.location_type,'city','district','neighborhood','country'),
                 l.name ASC,p.name ASC,g.name ASC,l.id ASC
        LIMIT 30";
    $s=Database::connection()->prepare($sql);$s->execute([$like]);
    echo json_encode($s->fetchAll(),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
});
$router->get('/admin/locations', static function (): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();
    $locations=$pdo->query("SELECT l.*,p.name AS parent_name FROM locations l LEFT JOIN locations p ON p.id=l.parent_id ORDER BY FIELD(l.location_type,'country','city','district','neighborhood'),p.name,l.sort_order,l.name")->fetchAll();
    $parents=$pdo->query("SELECT c.id,c.location_type,CONCAT(COALESCE(p.name,''),CASE WHEN p.name IS NULL THEN '' ELSE ' / ' END,c.name) AS full_name FROM locations c LEFT JOIN locations p ON p.id=c.parent_id WHERE c.is_active=1 ORDER BY FIELD(c.location_type,'country','city','district'),p.name,c.name")->fetchAll();
    ThemeManager::renderTemplate('templates/pages/admin/locations.php', get_defined_vars());
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
    ThemeManager::renderTemplate('templates/pages/admin/locations.php', get_defined_vars());
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
    $navigation=villa_navigation_tree($pdo,'header',$locale);$footerNavigation=villa_navigation_tree($pdo,'footer',$locale);
    $destinations=$pdo->query("SELECT l.id AS location_id,l.name AS city,COUNT(DISTINCT p.id) AS property_count,(SELECT pm.file_path FROM property_media pm JOIN properties p2 ON p2.id=pm.property_id WHERE p2.status='published' AND p2.location_city_id=l.id ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image FROM locations l JOIN locations c ON c.id=l.parent_id AND c.location_type='country' AND c.is_active=1 JOIN properties p ON p.status='published' AND p.location_city_id=l.id WHERE l.location_type='city' AND l.is_active=1 GROUP BY l.id,l.name,l.sort_order ORDER BY property_count DESC,l.sort_order,l.name LIMIT 9")->fetchAll();
    $featuredProperties=$pdo->query("SELECT p.*,(SELECT pm.file_path FROM property_media pm WHERE pm.property_id=p.id ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image,(SELECT COUNT(*) FROM bookings b WHERE b.property_id=p.id AND b.status NOT IN ('cancelled','rejected')) AS booking_count,(SELECT AVG(r.rating) FROM reviews r WHERE r.property_id=p.id AND r.status='approved') AS rating_average,(SELECT COUNT(*) FROM reviews r WHERE r.property_id=p.id AND r.status='approved') AS rating_count FROM properties p WHERE p.status='published' ORDER BY booking_count DESC,p.created_at DESC,p.id DESC LIMIT 9")->fetchAll();
    $featuredCardImages=[];$featuredIds=array_map(static fn(array $p):int=>(int)$p['id'],$featuredProperties);
    if($featuredIds){$marks=implode(',',array_fill(0,count($featuredIds),'?'));$media=$pdo->prepare("SELECT property_id,file_path FROM property_media WHERE property_id IN ($marks) ORDER BY property_id,is_cover DESC,sort_order,id");$media->execute($featuredIds);foreach($media->fetchAll() as $m){$pid=(int)$m['property_id'];if(count($featuredCardImages[$pid]??[])<4)$featuredCardImages[$pid][]=$m['file_path'];}}
    $favoritePropertyIds=[];
    if($user && ($user['role']??'')==='customer' && $featuredIds){$marks=implode(',',array_fill(0,count($featuredIds),'?'));$fav=$pdo->prepare("SELECT property_id FROM favorites WHERE user_id=? AND property_id IN ($marks)");$fav->execute(array_merge([(int)$user['id']],$featuredIds));$favoritePropertyIds=array_map('intval',$fav->fetchAll(PDO::FETCH_COLUMN));}

    $latestProperties=$pdo->query("SELECT p.*,(SELECT pm.file_path FROM property_media pm WHERE pm.property_id=p.id ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image,(SELECT AVG(r.rating) FROM reviews r WHERE r.property_id=p.id AND r.status='approved') AS rating_average,(SELECT COUNT(*) FROM reviews r WHERE r.property_id=p.id AND r.status='approved') AS rating_count,(SELECT COUNT(*) FROM bookings b WHERE b.property_id=p.id AND b.status NOT IN ('cancelled','rejected')) AS booking_count FROM properties p WHERE p.status='published' ORDER BY p.id DESC LIMIT 6")->fetchAll();
    $activeHomeTheme='default';
      if($activeHomeTheme==='default'){
         $categories=$pdo->query("SELECT c.id,c.name,c.slug,COUNT(DISTINCT p.id) AS property_count,(SELECT pm.file_path FROM property_media pm JOIN properties p2 ON p2.id=pm.property_id WHERE p2.category_id=c.id AND p2.status='published' ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image FROM property_categories c LEFT JOIN properties p ON p.category_id=c.id AND p.status='published' WHERE c.is_active=1 GROUP BY c.id,c.name,c.slug,c.sort_order ORDER BY c.sort_order,c.name LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
         $homeCategoryCollections=[];
         foreach(array_slice($categories,0,4) as $homeCategory){
             $categoryProperties=$pdo->prepare("SELECT p.*,(SELECT pm.file_path FROM property_media pm WHERE pm.property_id=p.id ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image,(SELECT AVG(r.rating) FROM reviews r WHERE r.property_id=p.id AND r.status='approved') AS rating_average,(SELECT COUNT(*) FROM reviews r WHERE r.property_id=p.id AND r.status='approved') AS rating_count,(SELECT COUNT(*) FROM bookings b WHERE b.property_id=p.id AND b.status NOT IN ('cancelled','rejected')) AS booking_count FROM properties p WHERE p.status='published' AND p.category_id=? ORDER BY p.created_at DESC,p.id DESC LIMIT 3");
             $categoryProperties->execute([(int)$homeCategory['id']]);
             $categoryRows=$categoryProperties->fetchAll(PDO::FETCH_ASSOC);
             if($categoryRows)$homeCategoryCollections[]=['category'=>$homeCategory,'properties'=>$categoryRows];
         }
         $homeReviews=$pdo->query("SELECT r.rating,r.body,r.created_at,u.username,p.title AS property_title FROM reviews r JOIN users u ON u.id=r.guest_user_id JOIN properties p ON p.id=r.property_id WHERE r.status='approved' AND r.body IS NOT NULL AND TRIM(r.body)<>'' AND p.status='published' ORDER BY r.created_at DESC,r.id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
     }else{
         $categories=$pdo->query('SELECT id,name,slug FROM property_categories WHERE is_active=1 ORDER BY sort_order,name LIMIT 8')->fetchAll();
         $homeCategoryCollections=[];$homeReviews=[];
     }
     $homeCardPropertyIds=array_values(array_unique(array_merge(
        array_map(static fn(array $p):int=>(int)$p['id'],$latestProperties),
        ...array_map(static fn(array $group):array=>array_map(static fn(array $p):int=>(int)$p['id'],$group['properties']),$homeCategoryCollections)
    )));
    $homeCardImages=[];
    if($homeCardPropertyIds){
        $marks=implode(',',array_fill(0,count($homeCardPropertyIds),'?'));
        $mediaStmt=$pdo->prepare("SELECT property_id,file_path FROM property_media WHERE property_id IN ($marks) ORDER BY property_id,is_cover DESC,sort_order,id");
        $mediaStmt->execute($homeCardPropertyIds);
        foreach($mediaStmt->fetchAll(PDO::FETCH_ASSOC) as $mediaRow){
            $propertyId=(int)$mediaRow['property_id'];
            if(count($homeCardImages[$propertyId]??[])<5){$filePath=(string)$mediaRow['file_path'];$homeCardImages[$propertyId][]=preg_match('~^https?://~i',$filePath)?$filePath:app_url('/'.ltrim($filePath,'/'));}
        }
        if($user && ($user['role']??'')==='customer'){
            $favStmt=$pdo->prepare("SELECT property_id FROM favorites WHERE user_id=? AND property_id IN ($marks)");
            $favStmt->execute(array_merge([(int)$user['id']],$homeCardPropertyIds));
            $favoritePropertyIds=array_values(array_unique(array_merge($favoritePropertyIds,array_map('intval',$favStmt->fetchAll(PDO::FETCH_COLUMN)))));
        }
    }
    $blogPosts=$pdo->query("SELECT id,title,slug,excerpt,featured_image,published_at FROM blog_posts WHERE status='published' AND (published_at IS NULL OR published_at<=NOW()) ORDER BY COALESCE(published_at,created_at) DESC,id DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
    $homeSections=[];foreach($pdo->query('SELECT * FROM homepage_sections ORDER BY sort_order,id')->fetchAll() as $section){$homeSections[$section['section_key']]=$section;}
    $locationCountries=$pdo->query("SELECT id,name FROM locations WHERE location_type='country' AND is_active=1 ORDER BY sort_order,name")->fetchAll();
     ThemeManager::render('landing.php', get_defined_vars());
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
    $navigation = villa_navigation_tree($pdo,'header',$locale);
    $footerNavigation = villa_navigation_tree($pdo,'footer',$locale);
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
    $villaNameQuery=mb_substr(trim((string)($_GET['q']??'')),0,120);
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
    $filters = ['q'=>$villaNameQuery,'city' => $city, 'guests' => $guests, 'category' => $categoryId, 'min_price'=>$minPrice, 'max_price'=>$maxPrice, 'sort'=>$sort,'country_id'=>$locationCountryId,'city_id'=>$locationCityId,'district_id'=>$locationDistrictId,'neighborhood_id'=>$locationNeighborhoodId,'check_in'=>$checkInDate,'check_out'=>$checkOutDate];
    $sql = "SELECT p.*, (SELECT pm.file_path FROM property_media pm WHERE pm.property_id=p.id ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image,(SELECT COUNT(*) FROM bookings b WHERE b.property_id=p.id AND b.status NOT IN ('cancelled','rejected')) AS booking_count,(SELECT AVG(r.rating) FROM reviews r WHERE r.property_id=p.id AND r.status='approved') AS rating_average,(SELECT COUNT(*) FROM reviews r WHERE r.property_id=p.id AND r.status='approved') AS rating_count FROM properties p WHERE p.status='published'";
    $params = [];
    if ($city !== '') { $sql .= ' AND (p.city LIKE ? OR p.district LIKE ? OR p.country LIKE ?)'; $like='%'.$city.'%'; array_push($params,$like,$like,$like); }
    if ($villaNameQuery !== '') { $sql .= ' AND p.title LIKE ?'; $params[]='%'.$villaNameQuery.'%'; }
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
        $sql .= " AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.property_id=p.id AND b.status='confirmed' AND b.check_in < ? AND b.check_out > ?)";
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
    $listingCardImages=[];$listingIds=array_map(static fn(array $p):int=>(int)$p['id'],$properties);
    if($listingIds){$marks=implode(',',array_fill(0,count($listingIds),'?'));$media=$pdo->prepare("SELECT property_id,file_path FROM property_media WHERE property_id IN ($marks) ORDER BY property_id,is_cover DESC,sort_order,id");$media->execute($listingIds);foreach($media->fetchAll() as $m){$pid=(int)$m['property_id'];if(count($listingCardImages[$pid]??[])<4)$listingCardImages[$pid][]=$m['file_path'];}}
    $favoritePropertyIds=[];
    if($user && ($user['role']??'')==='customer' && $listingIds){$marks=implode(',',array_fill(0,count($listingIds),'?'));$fav=$pdo->prepare("SELECT property_id FROM favorites WHERE user_id=? AND property_id IN ($marks)");$fav->execute(array_merge([(int)$user['id']],$listingIds));$favoritePropertyIds=array_map('intval',$fav->fetchAll(PDO::FETCH_COLUMN));}

    ThemeManager::render('listing.php', get_defined_vars());
});

$router->get('/destinasyonlar', static function (): void {
    $pdo=Database::connection();$user=Auth::user();$locale='tr';
    try{$locale=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.locale' LIMIT 1")->fetchColumn() ?: 'tr');}catch(Throwable $e){}
    $contentStmt=$pdo->prepare('SELECT content_key,content_value FROM site_content WHERE language_code=?');$contentStmt->execute([$locale]);$content=$contentStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $navigation=villa_navigation_tree($pdo,'header',$locale);$footerNavigation=villa_navigation_tree($pdo,'footer',$locale);
    $destinationsSql = "SELECT l.id AS location_id,l.name AS city,COUNT(DISTINCT p.id) AS property_count,(SELECT pm.file_path FROM property_media pm JOIN properties p2 ON p2.id=pm.property_id WHERE p2.status='published' AND p2.location_city_id=l.id ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image FROM locations l JOIN properties p ON p.location_city_id=l.id AND p.status='published' WHERE l.location_type='city' AND l.is_active=1 GROUP BY l.id,l.name,l.sort_order ORDER BY property_count DESC,l.sort_order,l.name";
    $destinations = $pdo->query($destinationsSql)->fetchAll();
    
    $mode=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode' LIMIT 1")->fetchColumn() ?: 'single_vendor');
    $user=Auth::user();
    ThemeManager::render('destinations.php', get_defined_vars());
});

$router->get('/api/villas/suggest', static function (): void {
    $term = trim((string)($_GET['q'] ?? ''));
    $term = mb_substr($term, 0, 80);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    if ($term === '') {
        echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return;
    }
    $pdo = Database::connection();
    $stmt = $pdo->prepare("SELECT p.title,p.slug,p.city,p.district,p.base_nightly_price,p.max_guests,
        (SELECT pm.file_path FROM property_media pm WHERE pm.property_id=p.id ORDER BY pm.is_cover DESC,pm.sort_order,pm.id LIMIT 1) AS cover_image
        FROM properties p
        WHERE p.status='published' AND p.title LIKE ?
        ORDER BY p.title ASC,p.id ASC
        LIMIT 15");
    $stmt->execute(['%' . $term . '%']);
    $items = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $property) {
        $image = trim((string)($property['cover_image'] ?? ''));
        if ($image !== '' && !preg_match('~^https?://~i', $image)) {
            $image = '/' . ltrim($image, '/');
        }
        $items[] = [
            'title' => (string)$property['title'],
            'url' => app_url('/villa/' . rawurlencode((string)$property['slug'])),
            'image' => $image,
            'city' => (string)($property['city'] ?? ''),
            'district' => (string)($property['district'] ?? ''),
            'price' => (int)($property['base_nightly_price'] ?? 0),
            'guests' => (int)($property['max_guests'] ?? 0),
        ];
    }
    echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
    $navigation=villa_navigation_tree($pdo,'header',$locale);
    $footerNavigation=villa_navigation_tree($pdo,'footer',$locale);
    $mode=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode' LIMIT 1")->fetchColumn() ?: 'single_vendor');
    $user=Auth::user();$isFavorite=false;
    if($user && ($user['role']??'')==='customer'){$fav=$pdo->prepare('SELECT 1 FROM favorites WHERE user_id=? AND property_id=?');$fav->execute([(int)$user['id'],(int)$property['id']]);$isFavorite=(bool)$fav->fetchColumn();}
    $ratingStmt=$pdo->prepare("SELECT AVG(rating) AS rating_average,COUNT(*) AS rating_count FROM reviews WHERE property_id=? AND status='approved'");
    $ratingStmt->execute([(int)$property['id']]);$ratingSummary=$ratingStmt->fetch() ?: ['rating_average'=>null,'rating_count'=>0];
    $reviewsStmt=$pdo->prepare("SELECT r.rating,r.body,r.created_at,u.username FROM reviews r JOIN users u ON u.id=r.guest_user_id WHERE r.property_id=? AND r.status='approved' ORDER BY r.created_at DESC LIMIT 20");
    $reviewsStmt->execute([(int)$property['id']]);$reviews=$reviewsStmt->fetchAll();
    ThemeManager::render('property.php', get_defined_vars());
});


$router->get('/register', static function (): void {
    if (Auth::check()) redirect('/account/bookings');
    $error = null;
    ThemeManager::renderTemplate('templates/pages/auth/register.php', get_defined_vars());
});

$router->post('/register', static function (): void {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $password = (string)($_POST['password'] ?? '');
    if (strlen($username) < 3 || !$email || strlen($password) < 10 || !hash_equals($password,(string)($_POST['password_confirm'] ?? ''))) {
        $error = 'Bilgileri kontrol edin. Şifre en az 10 karakter olmalı ve tekrar alanıyla eşleşmelidir.';
        ThemeManager::renderTemplate('templates/pages/auth/register.php', get_defined_vars());
        return;
    }
    try {
        $stmt = Database::connection()->prepare("INSERT INTO users (username,email,password_hash,role,status,created_at) VALUES (?,?,?,'customer','active',NOW())");
        $stmt->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT)]);
        Auth::attempt($email,$password);
        redirect('/account/bookings');
    } catch (PDOException $e) {
        $error = $e->getCode() === '23000' ? 'Bu kullanıcı adı veya e-posta zaten kayıtlı.' : 'Hesap oluşturulurken bir hata oluştu.';
        ThemeManager::renderTemplate('templates/pages/auth/register.php', get_defined_vars());
    }
});

$router->get('/villa/{slug}/calendar', static function (array $params): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $pdo=Database::connection();
    $s=$pdo->prepare("SELECT id,base_nightly_price,weekend_nightly_price,currency FROM properties WHERE slug=? AND status='published' LIMIT 1");
    $s->execute([$params['slug']??'']);$property=$s->fetch(PDO::FETCH_ASSOC);
    $propertyId=(int)($property['id']??0);
    if(!$propertyId){http_response_code(404);echo json_encode(['ok'=>false,'message'=>'Konaklama bulunamadı.']);return;}
    $month=(string)($_GET['month']??date('Y-m'));
    if(!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month)){http_response_code(422);echo json_encode(['ok'=>false,'message'=>'Ay bilgisi geçersiz.']);return;}
    $start=$month.'-01';$from=(new DateTimeImmutable($start))->modify('first day of this month')->format('Y-m-d');$to=(new DateTimeImmutable($from))->modify('first day of next month')->format('Y-m-d');
    $b=$pdo->prepare("SELECT check_in,check_out,status FROM bookings WHERE property_id=? AND status='confirmed' AND check_in < ? AND check_out > ? ORDER BY check_in");
    $b->execute([$propertyId,$to,$from]);
    $a=$pdo->prepare("SELECT date_from,date_to,status FROM availability WHERE property_id=? AND date_from < ? AND date_to > ? ORDER BY date_from");
    $a->execute([$propertyId,$to,$from]);
    echo json_encode(['ok'=>true,'month'=>$month,'today'=>date('Y-m-d'),'bookings'=>$b->fetchAll(PDO::FETCH_ASSOC),'rules'=>$a->fetchAll(PDO::FETCH_ASSOC),'base_nightly_price'=>(float)$property['base_nightly_price'],'weekend_nightly_price'=>(float)($property['weekend_nightly_price']??0),'currency'=>(string)$property['currency']],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
});

$router->get('/villa/{slug}/availability', static function (array $params): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $fail = static function (int $status, string $message): void {
        http_response_code($status);
        echo json_encode(['ok'=>false,'message'=>$message], JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    };
    try {
    $pdo = Database::connection();
    $propertyStmt = $pdo->prepare("SELECT id,base_nightly_price,weekend_nightly_price,cleaning_fee,currency,max_guests FROM properties WHERE slug=? AND status='published' LIMIT 1");
    $propertyStmt->execute([$params['slug'] ?? '']);
    $property = $propertyStmt->fetch();
    if (!$property) { $fail(404, 'Konaklama bulunamadı.'); return; }
    $inRaw = trim((string)($_GET['check_in'] ?? ''));
    $outRaw = trim((string)($_GET['check_out'] ?? ''));
    $guests = (int)($_GET['guests'] ?? 1);
    $checkIn = \DateTimeImmutable::createFromFormat('!Y-m-d', $inRaw);
    $checkOut = \DateTimeImmutable::createFromFormat('!Y-m-d', $outRaw);
    if (!$checkIn || !$checkOut || $checkIn->format('Y-m-d') !== $inRaw || $checkOut->format('Y-m-d') !== $outRaw || $checkIn < new \DateTimeImmutable('today') || $checkOut <= $checkIn) {
        $fail(422, 'Lütfen geçerli giriş ve çıkış tarihleri seçin.'); return;
    }
    $nights = (int)$checkIn->diff($checkOut)->days;
    if ($nights > 365) { $fail(422, 'Tek rezervasyonda en fazla 365 gece seçilebilir.'); return; }
    if ($guests < 1 || $guests > (int)$property['max_guests']) { $fail(422, 'Misafir sayısı konaklama kapasitesini aşamaz.'); return; }
    $conflict = $pdo->prepare("SELECT id FROM bookings WHERE property_id=? AND status='confirmed' AND check_in < ? AND check_out > ? LIMIT 1");
    $conflict->execute([(int)$property['id'], $outRaw, $inRaw]);
    if ($conflict->fetchColumn()) { $fail(409, 'Bu tarihlerde başka bir rezervasyon talebi veya onaylı rezervasyon bulunuyor.'); return; }
    $rulesStmt = $pdo->prepare('SELECT date_from,date_to,status,nightly_price FROM availability WHERE property_id=? AND date_from < ? AND date_to > ?');
    $rulesStmt->execute([(int)$property['id'], $outRaw, $inRaw]);
    $rules = $rulesStmt->fetchAll();
    $subtotal = 0.0;
    for ($night = $checkIn; $night < $checkOut; $night = $night->modify('+1 day')) {
        $day = $night->format('Y-m-d');
        $rate = (float)$property['base_nightly_price'];
        if ((int)$night->format('N') >= 6 && (float)($property['weekend_nightly_price'] ?? 0) > 0) $rate = (float)$property['weekend_nightly_price'];
        foreach ($rules as $rule) {
            if ($day >= $rule['date_from'] && $day < $rule['date_to']) {
                if ($rule['status'] !== 'available') { $fail(409, 'Seçtiğiniz tarih aralığında kapalı veya müsait olmayan günler var.'); return; }
                if ($rule['nightly_price'] !== null) $rate = (float)$rule['nightly_price'];
            }
        }
        $subtotal += $rate;
    }
    $fees = (float)($property['cleaning_fee'] ?? 0);
    echo json_encode([
        'ok'=>true,'nights'=>$nights,'subtotal'=>round($subtotal,2),'fees'=>round($fees,2),
        'total'=>round($subtotal+$fees,2),'currency'=>(string)$property['currency'],
        'message'=>'Seçtiğiniz tarihler müsait görünüyor. Son kontrol rezervasyon gönderilirken tekrar yapılır.'
    ], JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        error_log('Villa availability check failed: '.$e->getMessage());
        $fail(500, 'Müsaitlik kontrolü sırasında sunucu hatası oluştu. Lütfen daha sonra tekrar deneyin.');
    }
});

$router->get('/villa/{slug}/reserve', static function (array $params): void {
    $query=http_build_query(array_intersect_key($_GET,array_flip(['check_in','check_out','guests'])));
    redirect('/villa/'.rawurlencode((string)($params['slug']??'')).($query!==''?'?'.$query:''));
});

$router->post('/villa/{slug}/reserve', static function (array $params): void {
    verify_csrf();
    $pdo=Database::connection();
    $propertyStmt=$pdo->prepare("SELECT * FROM properties WHERE slug=? AND status='published' LIMIT 1");
    $propertyStmt->execute([$params['slug']??'']);$property=$propertyStmt->fetch();
    if(!$property){http_response_code(404);exit('Konaklama bulunamadı.');}
    $first=trim((string)($_POST['first_name']??''));$last=trim((string)($_POST['last_name']??''));
    $email=strtolower(trim((string)($_POST['email']??'')));$phone=trim((string)($_POST['phone']??''));$password=(string)($_POST['password']??'');
    $inRaw=trim((string)($_POST['check_in']??''));$outRaw=trim((string)($_POST['check_out']??''));
    $checkIn=DateTimeImmutable::createFromFormat('!Y-m-d',$inRaw);$checkOut=DateTimeImmutable::createFromFormat('!Y-m-d',$outRaw);
    $guests=(int)($_POST['guests']??0);$fullName=trim($first.' '.$last);
    if($first===''||$last===''||mb_strlen($first)>90||mb_strlen($last)>90||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||$phone===''||strlen($phone)>40||strlen($password)<10||strlen($password)>128||!$checkIn||!$checkOut||$checkIn->format('Y-m-d')!==$inRaw||$checkOut->format('Y-m-d')!==$outRaw||$checkIn<new DateTimeImmutable('today')||$checkOut<=$checkIn||(int)$checkIn->diff($checkOut)->days>365||$guests<1||$guests>(int)$property['max_guests']){
        http_response_code(422);exit('Lütfen tüm bilgileri ve rezervasyon tarihlerini kontrol edin. Şifre en az 10 karakter olmalıdır.');
    }
    $currentUser=Auth::user();$userId=0;$createNewAccount=false;$newUsername='';
    $existing=$pdo->prepare('SELECT id,username,email,password_hash,role,status FROM users WHERE email=? LIMIT 1');$existing->execute([$email]);$account=$existing->fetch();
    if($currentUser){
        if((int)$currentUser['id']!==(int)($account['id']??0)||strtolower((string)$currentUser['email'])!==$email){http_response_code(409);exit('Oturumunuzdaki hesap ile rezervasyon e-posta adresi aynı olmalıdır.');}
        if(!password_verify($password,(string)$account['password_hash'])){http_response_code(422);exit('Mevcut hesabınızın şifresini doğru girin.');}
        $userId=(int)$currentUser['id'];
    }elseif($account){
        if($account['role']!=='customer'||$account['status']!=='active'||!password_verify($password,(string)$account['password_hash'])){http_response_code(422);exit('Bu e-posta adresiyle kayıtlı hesabın şifresini doğrulayamadık. Giriş bilgilerinizi kontrol edin.');}
        $userId=(int)$account['id'];
        if(!Auth::attempt($email,$password)){http_response_code(429);exit('Giriş doğrulaması şu anda yapılamıyor. Lütfen biraz sonra tekrar deneyin.');}
    }else{
        $base=mb_strtolower($first.' '.$last,'UTF-8');
        $base=strtr($base,['ı'=>'i','ğ'=>'g','ü'=>'u','ş'=>'s','ö'=>'o','ç'=>'c']);
        $base=preg_replace('/[^a-z0-9]+/','.',iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$base)?:$base)?:'misafir';
        $base=trim(substr($base,0,65),'.');$newUsername=$base;$suffix=1;
        $checkUsername=$pdo->prepare('SELECT 1 FROM users WHERE username=? LIMIT 1');
        while(true){$checkUsername->execute([$newUsername]);if(!$checkUsername->fetchColumn())break;$newUsername=substr($base,0,65).'.'.$suffix++;}
        $createNewAccount=true;
    }
    try{
        $pdo->beginTransaction();
        $lock=$pdo->prepare('SELECT id,base_nightly_price,weekend_nightly_price,cleaning_fee,currency,max_guests FROM properties WHERE id=? AND status=\'published\' FOR UPDATE');
        $lock->execute([(int)$property['id']]);$current=$lock->fetch();
        if(!$current){$pdo->rollBack();http_response_code(404);exit('Konaklama artık yayında değil.');}
        if($guests<1||$guests>(int)$current['max_guests']){$pdo->rollBack();http_response_code(422);exit('Misafir sayısı konaklamanın güncel kapasitesini aşıyor.');}
        $conflict=$pdo->prepare("SELECT id FROM bookings WHERE property_id=? AND status='confirmed' AND check_in < ? AND check_out > ? LIMIT 1");
        $conflict->execute([(int)$property['id'],$outRaw,$inRaw]);
        $calendar=$pdo->prepare('SELECT date_from,date_to,status,nightly_price FROM availability WHERE property_id=? AND date_from < ? AND date_to > ?');
        $calendar->execute([(int)$property['id'],$outRaw,$inRaw]);$rules=$calendar->fetchAll();$subtotal=0.0;$unavailable=false;
        for($night=$checkIn;$night<$checkOut;$night=$night->modify('+1 day')){
            $day=$night->format('Y-m-d');$rate=(float)$current['base_nightly_price'];
            if((int)$night->format('N')>=6&&(float)($current['weekend_nightly_price']??0)>0)$rate=(float)$current['weekend_nightly_price'];
            foreach($rules as $rule){if($day>=$rule['date_from']&&$day<$rule['date_to']){if($rule['status']!=='available'){$unavailable=true;break;}if($rule['nightly_price']!==null)$rate=(float)$rule['nightly_price'];}}
            if($unavailable)break;$subtotal+=$rate;
        }
        if($conflict->fetchColumn()||$unavailable){$pdo->rollBack();http_response_code(409);exit('Seçtiğiniz tarihler artık müsait değil. Lütfen yeni tarih seçin.');}
        if($createNewAccount){
            $create=$pdo->prepare("INSERT INTO users(username,email,password_hash,role,status,created_at) VALUES(?,?,?,'customer','active',NOW())");
            $create->execute([$newUsername,$email,password_hash($password,PASSWORD_DEFAULT)]);$userId=(int)$pdo->lastInsertId();
        }
        $fees=(float)($current['cleaning_fee']??0);$total=round($subtotal+$fees,2);$code='VR'.date('Ymd').strtoupper(bin2hex(random_bytes(3)));
        $save=$pdo->prepare("INSERT INTO bookings(booking_code,property_id,guest_user_id,guest_name,guest_email,guest_phone,booking_source,check_in,check_out,guests,subtotal,fees,total,currency,status,created_at) VALUES(?,?,?,?,?,?,'website',?,?,?,?,?,?,?,'pending',NOW())");
        $save->execute([$code,(int)$property['id'],$userId,$fullName,$email,$phone,$inRaw,$outRaw,$guests,$subtotal,$fees,$total,$current['currency']]);
        $bookingId=(int)$pdo->lastInsertId();
        $vendorNet=$total;$platformFee=0.0;
        if(!empty($property['vendor_id'])){
            $vendorStmt=$pdo->prepare('SELECT user_id,commission_rate FROM vendors WHERE id=?');$vendorStmt->execute([(int)$property['vendor_id']]);$vendorInfo=$vendorStmt->fetch();
            if($vendorInfo){$defaultRaw=$pdo->query("SELECT setting_value FROM settings WHERE setting_key='platform.commission_percent'")->fetchColumn();$rate=$vendorInfo['commission_rate']!==null?(float)$vendorInfo['commission_rate']:($defaultRaw===false?12.0:(float)$defaultRaw);$platformFee=round($total*max(0,min(50,$rate))/100,2);$vendorNet=$total-$platformFee;$notify=$pdo->prepare("INSERT INTO notifications(user_id,notification_type,title,body,created_at) VALUES(?,'booking.requested','Yeni rezervasyon talebi',?,NOW())");$notify->execute([(int)$vendorInfo['user_id'],'İlanınız için yeni rezervasyon: '.$code]);}
        }
        $financial=$pdo->prepare("INSERT INTO booking_financials(booking_id,gross_amount,platform_fee,vendor_net_amount,currency,payout_status,created_at) VALUES(?,?,?,?,?,'unpaid',NOW())");$financial->execute([$bookingId,$total,$platformFee,$vendorNet,$current['currency']]);
        $notice=$pdo->prepare("INSERT INTO notifications(user_id,notification_type,title,body,created_at) VALUES(?,'booking.created','Rezervasyonunuz oluşturuldu',?,NOW())");$notice->execute([$userId,'Rezervasyon kodunuz: '.$code]);
        $pdo->commit();
        if($createNewAccount)Auth::attempt($email,$password);
        redirect('/checkout/'.rawurlencode($code));
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Booking creation failed: '.$e->getMessage());http_response_code(500);exit('Rezervasyon kaydedilemedi. Lütfen tekrar deneyin.');}
});

$router->get('/checkout/{bookingCode}', static function(array $params):void {
    $user=Auth::user();if(!$user)redirect('/login');
    $pdo=Database::connection();$stmt=$pdo->prepare("SELECT b.*,p.title AS property_title,p.slug AS property_slug FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.booking_code=? AND b.guest_user_id=? LIMIT 1");
    $stmt->execute([$params['bookingCode']??'',(int)$user['id']]);$booking=$stmt->fetch();
    if(!$booking){http_response_code(404);exit('Rezervasyon bulunamadı.');}
    $iyzicoConfig=\App\Core\PaymentGatewayConfig::forProvider('iyzico');
    $iyzicoPaymentAvailable=!empty($iyzicoConfig['enabled'])&&!empty($iyzicoConfig['configured'])&&($iyzicoConfig['environment']??'')==='sandbox';
    $requestedPaymentStatus=(string)($_GET['payment']??'');
    $paymentStatus=in_array($requestedPaymentStatus,['failed','verify_error','init_error'],true)?$requestedPaymentStatus:'';
    $latestPayment=$pdo->prepare("SELECT status,response_payload,expires_at FROM payment_transactions WHERE booking_id=? AND provider='iyzico' ORDER BY id DESC LIMIT 1");
    $latestPayment->execute([(int)$booking['id']]);$latestPaymentRow=$latestPayment->fetch(PDO::FETCH_ASSOC)?:[];
    $latestPaymentStatus=(string)($latestPaymentRow['status']??'');
    $iyzicoCheckoutFormHtml='';
    $iyzicoCheckoutFormUrl='';
    $iyzicoInitializationError='';
    if($paymentStatus==='init_error'&&$latestPaymentStatus==='failed'){
        $failureData=json_decode((string)($latestPaymentRow['response_payload']??''),true);
        if(is_array($failureData))$iyzicoInitializationError=trim((string)($failureData['initialization_error']??''));
    }
    if($latestPaymentStatus==='processing')$paymentStatus='review';
    elseif($latestPaymentStatus==='paid')$paymentStatus='paid';
    elseif($latestPaymentStatus==='failed'&&$paymentStatus!=='init_error')$paymentStatus='failed';

    $locale='tr';
    $navigation=villa_navigation_tree($pdo,'header',$locale);
    $footerNavigation=villa_navigation_tree($pdo,'footer',$locale);
    $contentStmt=$pdo->prepare('SELECT content_key,content_value FROM site_content WHERE language_code=?');
    $contentStmt->execute([$locale]);
    $content=$contentStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $siteName=(string)($content['site_name']??'VillaStay');
    ThemeManager::render('checkout.php',get_defined_vars());
});

$router->post('/checkout/{bookingCode}/iyzico/start', static function (array $params): void {
    verify_csrf();
    $user=Auth::user();if(!$user)redirect('/login');
    $config=\App\Core\PaymentGatewayConfig::forProvider('iyzico');
    if(!$config['enabled']||!$config['configured']){http_response_code(409);exit('iyzico ödeme altyapısı etkin değil veya API bilgileri eksik.');}
    if($config['environment']!=='sandbox'){http_response_code(409);exit('Bu aşamada yalnızca iyzico Sandbox ödemeleri açıktır. Canlı ödeme akışı ayrıca etkinleştirilecektir.');}
    $pdo=Database::connection();
    $stmt=$pdo->prepare("SELECT b.*,p.title AS property_title,p.slug AS property_slug,u.username,u.email AS account_email,u.created_at AS account_created FROM bookings b JOIN properties p ON p.id=b.property_id JOIN users u ON u.id=b.guest_user_id WHERE b.booking_code=? AND b.guest_user_id=? LIMIT 1");
    $stmt->execute([$params['bookingCode']??'',(int)$user['id']]);$booking=$stmt->fetch();
    if(!$booking){http_response_code(404);exit('Rezervasyon bulunamadı.');}
    if(!in_array((string)$booking['status'],['pending'],true)){http_response_code(409);exit('Yalnızca ödeme bekleyen rezervasyonlar için ödeme başlatılabilir.');}
    $existingPayment=$pdo->prepare("SELECT provider_reference,checkout_url,status,expires_at FROM payment_transactions WHERE booking_id=? AND provider='iyzico' AND status IN ('pending','processing') ORDER BY id DESC LIMIT 1");
    $existingPayment->execute([(int)$booking['id']]);$activePayment=$existingPayment->fetch();
    if($activePayment&&$activePayment['status']==='processing')redirect('/checkout/'.rawurlencode((string)$booking['booking_code']).'?payment=review');
    if($activePayment&&$activePayment['status']==='pending'){
        if(!empty($activePayment['expires_at'])&&strtotime((string)$activePayment['expires_at'])>time()){
            $stored=$pdo->prepare("SELECT response_payload FROM payment_transactions WHERE provider='iyzico' AND provider_reference=? AND status='pending' LIMIT 1");
            $stored->execute([(string)$activePayment['provider_reference']]);
            $storedResponse=json_decode((string)($stored->fetchColumn()?:''),true);
            $existingRedirectUrl=is_array($storedResponse)?villa_iyzico_checkout_redirect_url((string)($storedResponse['paymentPageUrl']??'')):null;
            if($existingRedirectUrl!==null)redirect($existingRedirectUrl);
        }
        if(str_starts_with((string)$activePayment['provider_reference'],'init:')){http_response_code(409);exit('Ödeme oturumu hazırlanıyor. Lütfen birkaç saniye sonra tekrar deneyin.');}
        $expire=$pdo->prepare("UPDATE payment_transactions SET status='expired',updated_at=NOW() WHERE provider='iyzico' AND provider_reference=? AND status='pending'");
        $expire->execute([(string)$activePayment['provider_reference']]);
    }

    $identityNumber=preg_replace('/\D+/','',(string)($_POST['identity_number']??''))??'';
    $address=trim((string)($_POST['billing_address']??''));
    $city=trim((string)($_POST['billing_city']??''));
    if(!preg_match('/^[0-9]{11}$/',$identityNumber)||$address===''||mb_strlen($address)>500||$city===''||mb_strlen($city)>100){
        http_response_code(422);exit('Fatura adresi, şehir ve 11 haneli T.C. kimlik numarası alanlarını kontrol edin.');
    }
    $currency=strtoupper((string)$booking['currency']);
    if($currency==='TL')$currency='TRY';
    if(!in_array($currency,['TRY','USD','EUR','GBP','NOK','CHF'],true)){http_response_code(422);exit('iyzico bu rezervasyonun para birimini desteklemiyor.');}
    $amount=number_format((float)$booking['total'],2,'.','');
    if((float)$amount<=0){http_response_code(422);exit('Ödeme tutarı geçersiz.');}

    $nameParts=preg_split('/\s+/u',trim((string)$booking['guest_name']),2)?:[];
    $firstName=trim((string)($nameParts[0]??'Misafir'));
    $lastName=trim((string)($nameParts[1]??'Misafir'));
    $phoneDigits=preg_replace('/\D+/','',(string)$booking['guest_phone'])??'';
    if(strlen($phoneDigits)===11&&str_starts_with($phoneDigits,'0'))$phone='+90'.substr($phoneDigits,1);
    elseif(strlen($phoneDigits)===12&&str_starts_with($phoneDigits,'90'))$phone='+'.$phoneDigits;
    elseif(strlen($phoneDigits)===10)$phone='+90'.$phoneDigits;
    else{http_response_code(422);exit('Telefon numarası iyzico formatına uygun değil. Lütfen hesap telefon bilginizi güncelleyin.');}
    $now=date('Y-m-d H:i:s');
    $ip=(string)($_SERVER['REMOTE_ADDR']??'127.0.0.1');
    $conversationId='VR'.$booking['id'].'-'.bin2hex(random_bytes(8));
    try{$callbackUrl=app_absolute_url('/payments/iyzico/callback');}
    catch(Throwable $e){http_response_code(503);exit('iyzico ödeme bildirimi için geçerli HTTPS site adresi gereklidir.');}
    $payload=[
        'locale'=>'tr','conversationId'=>$conversationId,'price'=>$amount,'paidPrice'=>$amount,
        'currency'=>$currency,'basketId'=>(string)$booking['booking_code'],'paymentGroup'=>'PRODUCT',
        'callbackUrl'=>$callbackUrl,'enabledInstallments'=>[1],
        'buyer'=>[
            'id'=>(string)$booking['guest_user_id'],'name'=>$firstName,'surname'=>$lastName,
            'identityNumber'=>$identityNumber,'email'=>(string)$booking['guest_email'],'gsmNumber'=>$phone,
            'registrationDate'=>!empty($booking['account_created'])?date('Y-m-d H:i:s',strtotime((string)$booking['account_created'])):$now,
            'lastLoginDate'=>$now,'registrationAddress'=>$address,'city'=>$city,'country'=>'Turkey','ip'=>$ip,
        ],
        'shippingAddress'=>['address'=>$address,'contactName'=>(string)$booking['guest_name'],'city'=>$city,'country'=>'Turkey'],
        'billingAddress'=>['address'=>$address,'contactName'=>(string)$booking['guest_name'],'city'=>$city,'country'=>'Turkey'],
        'basketItems'=>[[
            'id'=>(string)$booking['id'],'price'=>$amount,'name'=>mb_substr((string)$booking['property_title'],0,100),
            'category1'=>'Konaklama','category2'=>'Villa rezervasyonu','itemType'=>'VIRTUAL',
        ]],
    ];

    $idempotency=hash('sha256','iyzico:'.$conversationId);
    $placeholder='init:'.$conversationId;
    try{
        $insert=$pdo->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,idempotency_key,amount,currency,status,request_payload,created_at,updated_at) VALUES(?,'iyzico',?,?,?,?, 'pending',?,NOW(),NOW())");
        $insert->execute([(int)$booking['id'],$placeholder,$idempotency,$amount,$currency,json_encode(['conversationId'=>$conversationId,'basketId'=>$booking['booking_code'],'environment'=>'sandbox'],JSON_UNESCAPED_UNICODE)]);
        $transactionId=(int)$pdo->lastInsertId();
        $response=\App\Core\IyzicoApiClient::initializeCheckoutForm($config,$payload);
        $token=trim((string)($response['token']??''));
        $checkoutFormContent=trim((string)($response['checkoutFormContent']??''));
        $secret=(string)($config['credentials']['secret_key']??'');
        if(!\App\Core\PaymentSignatureVerifier::verifyIyzicoCheckoutFormInitialize($response,$secret)
            ||!hash_equals($conversationId,(string)($response['conversationId']??''))) {
            throw new RuntimeException('iyzico ödeme oturumu yanıtı imza veya işlem referansı doğrulamasından geçmedi.');
        }
        $redirectUrl=villa_iyzico_checkout_redirect_url(trim((string)($response['paymentPageUrl']??'')));
        if($token===''||$redirectUrl===null){
            $responseFields=array_keys($response);
            sort($responseFields);
            throw new RuntimeException('iyzico yanıtında güvenli Sandbox ödeme sayfası adresi bulunamadı (alanlar: '.implode(',',$responseFields).').');
        }
        $storedResponse=json_encode([
            'token'=>$token,'conversationId'=>$conversationId,'checkoutFormContent'=>$checkoutFormContent,
            'paymentPageUrl'=>$redirectUrl,'render_mode'=>'redirect','environment'=>'sandbox',
        ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $update=$pdo->prepare("UPDATE payment_transactions SET provider_reference=?,checkout_url=?,response_payload=?,expires_at=DATE_ADD(NOW(),INTERVAL 30 MINUTE),updated_at=NOW() WHERE id=? AND status='pending'");
        $update->execute([$token,$redirectUrl,$storedResponse,$transactionId]);
        if($update->rowCount()!==1)throw new RuntimeException('Ödeme oturumu yerel kayda bağlanamadı.');
        redirect($redirectUrl);
    }catch(Throwable $e){
        $errorReference='VILLA-IYZ-'.(string)($transactionId??'UNKNOWN');
        $diagnostic=trim((string)$e->getMessage());
        $diagnostic=preg_replace('/(api[_-]?key|secret[_-]?key)\\s*[:=]\\s*\\S+/i','$1=[gizlendi]',$diagnostic)??'Ödeme sağlayıcısı hatası.';
        $diagnostic=mb_substr($diagnostic,0,240);
        if(isset($transactionId)){
            $failurePayload=json_encode([
                'initialization_error'=>$diagnostic,
                'error_reference'=>$errorReference,
                'environment'=>'sandbox',
            ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $fail=$pdo->prepare("UPDATE payment_transactions SET status='failed',response_payload=?,updated_at=NOW() WHERE id=? AND status='pending'");
            $fail->execute([$failurePayload,$transactionId]);
        }
        error_log('iyzico Checkout Form initialize failed ['.$errorReference.']: '.$diagnostic);
        redirect('/checkout/'.rawurlencode((string)$booking['booking_code']).'?payment=init_error');
    }
});

$router->post('/payments/iyzico/callback', static function (): void {
    $token=trim((string)($_POST['token']??''));
    if($token===''||strlen($token)>128){http_response_code(400);exit('Ödeme bildirimi geçersiz.');}
    $pdo=Database::connection();
    $find=$pdo->prepare("SELECT pt.id,pt.booking_id,pt.amount,pt.currency,pt.status,pt.request_payload,b.booking_code FROM payment_transactions pt JOIN bookings b ON b.id=pt.booking_id WHERE pt.provider='iyzico' AND pt.provider_reference=? LIMIT 1");
    $find->execute([$token]);$transaction=$find->fetch();
    if(!$transaction){http_response_code(404);exit('Ödeme işlemi bulunamadı.');}
    try{
        $config=\App\Core\PaymentGatewayConfig::forProvider('iyzico');
        $config['environment']='sandbox';
        $requestData=json_decode((string)$transaction['request_payload'],true);
        $expectedConversationId=is_array($requestData)?trim((string)($requestData['conversationId']??'')):'';
        $expectedBasketId=is_array($requestData)?trim((string)($requestData['basketId']??'')):'';
        if($expectedConversationId===''||$expectedBasketId==='')throw new RuntimeException('Yerel ödeme referansları eksik veya okunamadı.');
        $result=\App\Core\IyzicoApiClient::retrieveCheckoutForm($config,$token,$expectedConversationId);
        if(strtolower((string)($result['status']??''))!=='success')throw new RuntimeException('iyzico ödeme sonucu doğrulanamadı.');
        $secret=(string)($config['credentials']['secret_key']??'');
        if(!\App\Core\PaymentSignatureVerifier::verifyIyzicoCheckoutFormRetrieve($result,$secret))throw new RuntimeException('iyzico ödeme yanıtı imza doğrulamasından geçmedi.');
        if(!hash_equals($expectedConversationId,(string)($result['conversationId']??''))
            ||!hash_equals($expectedBasketId,(string)($result['basketId']??''))) {
            throw new RuntimeException('iyzico ödeme yanıtı rezervasyon referanslarıyla eşleşmedi.');
        }
        $paymentStatus=strtoupper((string)($result['paymentStatus']??''));
        $fraudStatus=(int)($result['fraudStatus']??-1);
        $normalizedStatus=($paymentStatus==='SUCCESS'&&$fraudStatus===1)?'paid':(($paymentStatus==='SUCCESS'&&$fraudStatus===0)?'processing':'failed');
        $paymentId=trim((string)($result['paymentId']??''));
        $eventId=$paymentId!==''?$paymentId:'token-'.$token;
        $paidPriceValue=$result['paidPrice']??null;
        $verifiedPaidAmount=is_numeric($paidPriceValue)&&is_finite((float)$paidPriceValue)?number_format((float)$paidPriceValue,2,'.',''):'';
        $callback=[
            'provider'=>'iyzico','provider_reference'=>$token,'booking_id'=>(int)$transaction['booking_id'],
            'amount'=>$normalizedStatus==='failed'?(string)$transaction['amount']:$verifiedPaidAmount,
            'currency'=>$normalizedStatus==='failed'?strtoupper((string)$transaction['currency']):strtoupper((string)($result['currency']??'')),
            'status'=>$normalizedStatus,
        ];
        $eventPayload=['token'=>$token,'paymentId'=>$paymentId,'paymentStatus'=>$paymentStatus,'fraudStatus'=>$fraudStatus,'paidPrice'=>$callback['amount'],'currency'=>$callback['currency']];
        $processed=(new \App\Core\PaymentCallbackProcessor($pdo))->processVerified(
            'iyzico',$eventId,'CHECKOUT_FORM_AUTH',$token,$eventPayload,$callback
        );
        $currentPayment=$pdo->prepare('SELECT status FROM payment_transactions WHERE id=? LIMIT 1');
        $currentPayment->execute([(int)$transaction['id']]);
        $storedStatus=(string)($currentPayment->fetchColumn()?:'');
        $suffix=match($storedStatus){'paid'=>'success','processing'=>'review','failed'=>'failed',default=>'review'};
        redirect('/payment/'.$suffix.'/'.rawurlencode($token));
    }catch(Throwable $e){
        error_log('iyzico callback verification failed: '.$e->getMessage());
        redirect('/payment/review/'.rawurlencode($token));
    }
});

foreach(['success','failed','review'] as $paymentResultType){
    $router->get('/payment/'.$paymentResultType.'/{paymentToken}', static function(array $params) use($paymentResultType):void {
        $token=trim((string)($params['paymentToken']??''));
        if($token===''||strlen($token)>128){http_response_code(404);exit('Ödeme sonucu bulunamadı.');}
        $pdo=Database::connection();
        $stmt=$pdo->prepare("SELECT b.*,p.title AS property_title,p.slug AS property_slug,pt.status AS payment_status,pt.provider_reference,pt.updated_at AS payment_updated_at FROM payment_transactions pt JOIN bookings b ON b.id=pt.booking_id JOIN properties p ON p.id=b.property_id WHERE pt.provider='iyzico' AND pt.provider_reference=? LIMIT 1");
        $stmt->execute([$token]);$row=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$row){http_response_code(404);exit('Ödeme sonucu bulunamadı.');}
        $payment=['status'=>(string)$row['payment_status'],'provider_reference'=>(string)$row['provider_reference'],'updated_at'=>$row['payment_updated_at']];
        $booking=$row;$actualStatus=(string)$payment['status'];
        if($paymentResultType==='success'&&$actualStatus!=='paid')redirect('/payment/review/'.rawurlencode($token));
        if($paymentResultType==='failed'&&$actualStatus!=='failed')redirect('/payment/review/'.rawurlencode($token));
        if($paymentResultType==='review'&&in_array($actualStatus,['paid','failed'],true))redirect('/payment/'.($actualStatus==='paid'?'success':'failed').'/'.rawurlencode($token));
        $locale='tr';
        $navigation=villa_navigation_tree($pdo,'header',$locale);
        $footerNavigation=villa_navigation_tree($pdo,'footer',$locale);
        $contentStmt=$pdo->prepare('SELECT content_key,content_value FROM site_content WHERE language_code=?');
        $contentStmt->execute([$locale]);$content=$contentStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $siteName=(string)($content['site_name']??'VillaStay');
        $brandSettings=ThemeManager::brandSettings(ThemeManager::activeSlug());
        ThemeManager::renderTemplate('templates/pages/payment-result.php',get_defined_vars());
    });
}

$router->post('/payments/iyzico/webhook', static function (): void {
    $config=\App\Core\PaymentGatewayConfig::forProvider('iyzico');
    if(!$config['configured']){http_response_code(403);exit('Not configured');}
    $config['environment']='sandbox';
    $payload=$_POST;
    if(!$payload){
        $raw=(string)file_get_contents('php://input');
        $decoded=json_decode($raw,true);
        if(is_array($decoded))$payload=$decoded;
    }
    $signature=trim((string)($_SERVER['HTTP_X_IYZ_SIGNATURE_V3']??''));
    $secret=(string)($config['credentials']['secret_key']??'');
    if(!\App\Core\PaymentSignatureVerifier::verifyIyzicoV3($payload,$signature,$secret)){http_response_code(401);exit('Invalid signature');}
    $eventType=(string)($payload['iyziEventType']??'');
    if($eventType!=='CHECKOUT_FORM_AUTH'){http_response_code(200);exit('Ignored');}
    $token=trim((string)($payload['token']??''));
    $iyziPaymentId=trim((string)($payload['iyziPaymentId']??''));
    $conversationId=trim((string)($payload['paymentConversationId']??''));
    $eventStatus=strtoupper(trim((string)($payload['status']??'')));
    if(!in_array($eventStatus,['SUCCESS','FAILURE'],true)){http_response_code(200);exit('Intermediate event ignored');}
    $eventId=trim((string)($payload['iyziReferenceCode']??''));
    if($token===''||$iyziPaymentId===''||$conversationId===''||$eventId===''){http_response_code(422);exit('Invalid event');}
    $pdo=Database::connection();
    $find=$pdo->prepare("SELECT pt.id,pt.booking_id,pt.amount,pt.currency,pt.request_payload,b.booking_code FROM payment_transactions pt JOIN bookings b ON b.id=pt.booking_id WHERE pt.provider='iyzico' AND pt.provider_reference=? LIMIT 1");
    $find->execute([$token]);$transaction=$find->fetch();
    if(!$transaction){http_response_code(404);exit('Unknown payment');}
    $requestData=json_decode((string)$transaction['request_payload'],true);
    if(!is_array($requestData)||!hash_equals((string)($requestData['conversationId']??''),$conversationId)){http_response_code(409);exit('Conversation mismatch');}
    try{
        $result=\App\Core\IyzicoApiClient::retrieveCheckoutForm($config,$token,$conversationId);
        if(strtolower((string)($result['status']??''))!=='success'){http_response_code(503);exit('Retrieve failed');}
        $secret=(string)($config['credentials']['secret_key']??'');
        if(!\App\Core\PaymentSignatureVerifier::verifyIyzicoCheckoutFormRetrieve($result,$secret)){http_response_code(401);exit('Invalid retrieve signature');}
        if((string)($result['paymentId']??'')!==$iyziPaymentId){http_response_code(409);exit('Payment reference mismatch');}
        if(!hash_equals($conversationId,(string)($result['conversationId']??''))||!hash_equals((string)$transaction['booking_code'],(string)($result['basketId']??''))){http_response_code(409);exit('Retrieve reference mismatch');}
        $fraudStatus=(int)($result['fraudStatus']??-1);
        $paymentStatus=strtoupper((string)($result['paymentStatus']??$eventStatus));
        $normalizedStatus=($paymentStatus==='SUCCESS'&&$eventStatus==='SUCCESS'&&$fraudStatus===1)?'paid':(($paymentStatus==='SUCCESS'&&$eventStatus==='SUCCESS'&&$fraudStatus===0)?'processing':'failed');
        $paidPriceValue=$result['paidPrice']??null;
        $verifiedPaidAmount=is_numeric($paidPriceValue)&&is_finite((float)$paidPriceValue)?number_format((float)$paidPriceValue,2,'.',''):'';
        $callback=[
            'provider'=>'iyzico','provider_reference'=>$token,'booking_id'=>(int)$transaction['booking_id'],
            'amount'=>$normalizedStatus==='failed'?(string)$transaction['amount']:$verifiedPaidAmount,
            'currency'=>$normalizedStatus==='failed'?strtoupper((string)$transaction['currency']):strtoupper((string)($result['currency']??'')),
            'status'=>$normalizedStatus,
        ];
        $eventPayload=['token'=>$token,'paymentId'=>$iyziPaymentId,'paymentStatus'=>$paymentStatus,'eventStatus'=>$eventStatus,'fraudStatus'=>$fraudStatus,'paidPrice'=>$callback['amount'],'currency'=>$callback['currency']];
        $processed=(new \App\Core\PaymentCallbackProcessor($pdo))->processVerified(
            'iyzico',$eventId,$eventType,$token,$eventPayload,$callback
        );
        if(in_array($processed['result']??'', ['processed','duplicate','already_applied','in_progress'],true)){http_response_code(200);exit('OK');}
        error_log('iyzico webhook was signature-valid but rejected by payment guards: '.(string)($processed['result']??'unknown'));
        http_response_code(200);exit('Rejected');
    }catch(Throwable $e){
        error_log('iyzico webhook processing failed: '.$e->getMessage());
        http_response_code(503);exit('Retry');
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
    villa_render_account_view('messages.php', get_defined_vars());
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
    $s->execute([(int)$user['id']]);$properties=$s->fetchAll();villa_render_account_view('favorites.php', get_defined_vars());
});
$router->post('/favorites/toggle', static function (): void {
    $isAjax=str_contains((string)($_SERVER['HTTP_ACCEPT']??''),'application/json') || (string)($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='XMLHttpRequest';
    $user=Auth::user();
    if(!$user || ($user['role']??'')!=='customer'){
        if($isAjax){http_response_code(401);header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>false,'message'=>'Favorilerinizi yönetmek için müşteri hesabınızla giriş yapın.'],JSON_UNESCAPED_UNICODE);return;}
        if(!$user)redirect('/login');redirect('/admin');
    }
    verify_csrf();
    $pdo=Database::connection();$pid=(int)($_POST['property_id']??0);
    $check=$pdo->prepare("SELECT id FROM properties WHERE id=? AND status='published'");$check->execute([$pid]);
    if(!$check->fetchColumn()){
        if($isAjax){http_response_code(404);header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>false,'message'=>'Konaklama artık yayında değil.'],JSON_UNESCAPED_UNICODE);return;}
        http_response_code(404);return;
    }
    $exists=$pdo->prepare('SELECT 1 FROM favorites WHERE user_id=? AND property_id=?');$exists->execute([(int)$user['id'],$pid]);
    if($exists->fetchColumn()){$del=$pdo->prepare('DELETE FROM favorites WHERE user_id=? AND property_id=?');$del->execute([(int)$user['id'],$pid]);$active=false;}
    else{$add=$pdo->prepare('INSERT INTO favorites(user_id,property_id,created_at) VALUES(?,?,NOW())');$add->execute([(int)$user['id'],$pid]);$active=true;}
    if($isAjax){header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode(['ok'=>true,'active'=>$active,'message'=>$active?'Favorilerinize eklendi.':'Favorilerinizden çıkarıldı.'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);return;}
    $return=trim((string)($_POST['return_to']??'/favorites'));$parsed=parse_url($return);
    if(!str_starts_with($return,'/')||str_starts_with($return,'//')||isset($parsed['host'])||preg_match('/[\r\n]/',$return))$return='/favorites';
    redirect($return);
});


$router->get('/account/reviews/{bookingId}', static function (array $params): void {
    $user=Auth::user();if(!$user||($user['role']??'')!=='customer')redirect('/login');
    $s=Database::connection()->prepare("SELECT b.id,p.title FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.id=? AND b.guest_user_id=? AND b.status='completed'");
    $s->execute([(int)$params['bookingId'],(int)$user['id']]);$booking=$s->fetch();if(!$booking){http_response_code(404);return;}
    $exists=Database::connection()->prepare('SELECT id FROM reviews WHERE booking_id=?');$exists->execute([(int)$booking['id']]);if($exists->fetchColumn()){http_response_code(409);exit('Bu rezervasyon için zaten değerlendirme gönderdiniz.');}
    villa_render_account_view('review-form.php', get_defined_vars());
});
$router->post('/account/reviews', static function (): void {
    $user=Auth::user();if(!$user||($user['role']??'')!=='customer')redirect('/login');verify_csrf();
    $bookingId=(int)($_POST['booking_id']??0);$rating=filter_var($_POST['rating']??null,FILTER_VALIDATE_INT);$body=trim((string)($_POST['body']??''));
    if($rating===false||$rating===null||$rating<1||$rating>10){http_response_code(422);exit('Puan 1 ile 10 arasında olmalıdır.');}
    if(mb_strlen($body)>3000){http_response_code(422);exit('Değerlendirme metni en fazla 3000 karakter olabilir.');}
    $pdo=Database::connection();$s=$pdo->prepare("SELECT property_id FROM bookings WHERE id=? AND guest_user_id=? AND status='completed'");$s->execute([$bookingId,(int)$user['id']]);$propertyId=(int)$s->fetchColumn();if(!$propertyId){http_response_code(403);exit('Yalnızca tamamlanan konaklamalar değerlendirilebilir.');}
    try{$save=$pdo->prepare("INSERT INTO reviews(booking_id,property_id,guest_user_id,rating,body,status,created_at) VALUES(?,?,?,?,?,'pending',NOW())");$save->execute([$bookingId,$propertyId,(int)$user['id'],$rating,$body]);redirect('/account/bookings');}
    catch(PDOException $e){http_response_code(409);exit('Bu rezervasyon için değerlendirme zaten mevcut.');}
});
$router->get('/admin/reviews', static function (): void {
    Auth::requireRole(['super_admin','admin']);$reviews=Database::connection()->query('SELECT r.*,p.title,u.username FROM reviews r JOIN properties p ON p.id=r.property_id JOIN users u ON u.id=r.guest_user_id ORDER BY r.created_at DESC LIMIT 250')->fetchAll();ThemeManager::renderTemplate('templates/pages/admin/reviews.php', get_defined_vars());
});
$router->post('/admin/reviews/status', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$status=($_POST['status']??'')==='approved'?'approved':'rejected';$s=Database::connection()->prepare('UPDATE reviews SET status=? WHERE id=?');$s->execute([$status,(int)($_POST['id']??0)]);redirect('/admin/reviews');
});

$router->get('/account/notifications', static function (): void {
    $user=Auth::user();if(!$user)redirect('/login');$pdo=Database::connection();
    $mark=$pdo->prepare('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL');$mark->execute([(int)$user['id']]);
    $s=$pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 100');$s->execute([(int)$user['id']]);$notifications=$s->fetchAll();
    villa_render_account_view('notifications.php', get_defined_vars());
});

$router->get('/account/bookings', static function (): void {
    $user = Auth::user();
    if (!$user) redirect('/login');
    if (($user['role'] ?? '') !== 'customer') redirect('/admin');
    $pdo = Database::connection();
    $stmt = $pdo->prepare('SELECT b.*,p.title,p.city,EXISTS(SELECT 1 FROM reviews r WHERE r.booking_id=b.id) AS has_review FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.guest_user_id=? ORDER BY b.created_at DESC');
    $stmt->execute([(int)$user['id']]);
    $bookings = $stmt->fetchAll();

    villa_render_account_view('bookings.php', get_defined_vars());
});

$router->get('/login', static function (): void {
    if (Auth::check()) { $user = Auth::user(); redirect(($user['role'] ?? '') === 'customer' ? '/account/bookings' : (($user['role'] ?? '') === 'vendor' ? '/vendor' : '/admin')); }
    ThemeManager::renderTemplate('templates/pages/auth/login.php', get_defined_vars());
});

$router->post('/login/ajax', static function (): void {
    header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
    if(!hash_equals(csrf_token(),(string)($_POST['_csrf']??''))){http_response_code(419);echo json_encode(['ok'=>false,'message'=>'Oturum doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.']);return;}
    $identity=trim((string)($_POST['identity']??''));$password=(string)($_POST['password']??'');
    if($identity===''||$password===''||!Auth::attempt($identity,$password)){http_response_code(401);echo json_encode(['ok'=>false,'message'=>'E-posta/kullanıcı adı veya şifre hatalı.']);return;}
    $user=Auth::user();$role=(string)($user['role']??'customer');
    $destination=$role==='customer'?'/account/bookings':($role==='vendor'?'/vendor':'/admin');
    echo json_encode(['ok'=>true,'user'=>['username'=>(string)($user['username']??''),'role'=>$role,'label'=>in_array($role,['admin','super_admin'],true)?'Yönetim Paneli':($role==='vendor'?'Ev Sahibi Paneli':'Hesabım'),'url'=>app_url($destination)]],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
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
    redirect('/');
});



$router->get('/vendor/register', static function (): void {
    $pdo=Database::connection();
    if ((string)$pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn() !== 'multi_vendor') { http_response_code(404); return; }
    $error=null; ThemeManager::renderTemplate('templates/pages/vendor/register.php', get_defined_vars());
});
$router->post('/vendor/register', static function (): void {
    verify_csrf(); $pdo=Database::connection();
    if ((string)$pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn() !== 'multi_vendor') { http_response_code(404); return; }
    $business=trim((string)($_POST['business_name']??'')); $username=trim((string)($_POST['username']??''));
    $email=filter_var(trim((string)($_POST['email']??'')),FILTER_VALIDATE_EMAIL); $phone=trim((string)($_POST['phone']??''));
    $password=(string)($_POST['password']??'');
    if($business===''||strlen($username)<3||!$email||strlen($password)<10){$error='Bilgileri kontrol edin. Şifre en az 10 karakter olmalıdır.';ThemeManager::renderTemplate('templates/pages/vendor/register.php', get_defined_vars());return;}
    try{$pdo->beginTransaction();$u=$pdo->prepare("INSERT INTO users(username,email,password_hash,role,status,created_at) VALUES(?,?,?,'vendor','active',NOW())");$u->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT)]);$v=$pdo->prepare("INSERT INTO vendors(user_id,business_name,phone,status,created_at) VALUES(?,?,?,'pending',NOW())");$v->execute([(int)$pdo->lastInsertId(),$business,$phone]);$pdo->commit();Auth::attempt($email,$password);redirect('/vendor');}
    catch(PDOException $e){if($pdo->inTransaction())$pdo->rollBack();$error='Bu kullanıcı adı veya e-posta kullanılıyor ya da başvuru kaydedilemedi.';ThemeManager::renderTemplate('templates/pages/vendor/register.php', get_defined_vars());}
});
$router->get('/vendor', static function (): void {
    Auth::requireRole(['vendor']);$pdo=Database::connection();
    if((string)$pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn()!=='multi_vendor'){http_response_code(404);return;}
    $s=$pdo->prepare('SELECT * FROM vendors WHERE user_id=?');$s->execute([(int)Auth::user()['id']]);$vendor=$s->fetch();
    if(!$vendor){http_response_code(403);return;}
    $s=$pdo->prepare('SELECT COUNT(*) FROM properties WHERE vendor_id=?');$s->execute([(int)$vendor['id']]);$propertyCount=(int)$s->fetchColumn();
    ThemeManager::renderTemplate('templates/pages/vendor/dashboard.php', get_defined_vars());
});

$router->get('/vendor/bookings', static function (): void {
    Auth::requireRole(['vendor']);$pdo=Database::connection();
    if((string)$pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn()!=='multi_vendor'){http_response_code(404);return;}
    $v=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$v->execute([(int)Auth::user()['id']]);$vendorId=(int)$v->fetchColumn();
    if(!$vendorId){http_response_code(403);return;}
    $status=(string)($_GET['status']??'');$allowed=['pending','confirmed','cancelled','completed'];
    $sql="SELECT b.*,p.title,COALESCE(NULLIF(b.guest_name,''),u.username,'Misafir') AS guest_name,COALESCE(NULLIF(b.guest_email,''),u.email,'') AS guest_email,COALESCE(b.guest_phone,'') AS guest_phone FROM bookings b JOIN properties p ON p.id=b.property_id LEFT JOIN users u ON u.id=b.guest_user_id WHERE p.vendor_id=?";
    if(in_array($status,$allowed,true)){$sql.=' AND b.status=?';$s=$pdo->prepare($sql.' ORDER BY b.created_at DESC LIMIT 250');$s->execute([$vendorId,$status]);$bookings=$s->fetchAll();}
    else{$s=$pdo->prepare($sql.' ORDER BY b.created_at DESC LIMIT 250');$s->execute([$vendorId]);$bookings=$s->fetchAll();}
    $bookingFilter=in_array($status,$allowed,true)?$status:'';
    ThemeManager::renderTemplate('templates/pages/vendor/bookings.php', get_defined_vars());
});

$router->post('/vendor/bookings/status', static function (): void {
    Auth::requireRole(['vendor']);verify_csrf();$pdo=Database::connection();
    $vendor=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$vendor->execute([(int)Auth::user()['id']]);$vendorId=(int)$vendor->fetchColumn();
    if(!$vendorId){http_response_code(403);return;}
    $id=(int)($_POST['id']??0);$status=(string)($_POST['status']??'');
    if(!in_array($status,['confirmed','cancelled','completed'],true)){http_response_code(422);exit('Rezervasyon durumu geçersiz.');}
    try{
        $pdo->beginTransaction();
        $s=$pdo->prepare('SELECT b.property_id,b.guest_user_id,b.check_in,b.check_out,b.status FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.id=? AND p.vendor_id=? FOR UPDATE');
        $s->execute([$id,$vendorId]);$booking=$s->fetch();
        if(!$booking){$pdo->rollBack();http_response_code(404);exit('Rezervasyon bulunamadı.');}
        if($status==='confirmed'){
            $conflict=$pdo->prepare("SELECT id FROM bookings WHERE property_id=? AND id<>? AND status='confirmed' AND check_in < ? AND check_out > ? LIMIT 1");
            $conflict->execute([(int)$booking['property_id'],$id,$booking['check_out'],$booking['check_in']]);
            if($conflict->fetchColumn()){$pdo->rollBack();http_response_code(409);exit('Bu tarihlerde başka bir rezervasyon talebi veya onaylı rezervasyon var.');}
            $blocked=$pdo->prepare("SELECT id FROM availability WHERE property_id=? AND status<>'available' AND date_from < ? AND date_to > ? LIMIT 1");
            $blocked->execute([(int)$booking['property_id'],$booking['check_out'],$booking['check_in']]);
            if($blocked->fetchColumn()){$pdo->rollBack();http_response_code(409);exit('Bu rezervasyon tarihleri takvimde kapatılmış.');}
        }
        $u=$pdo->prepare('UPDATE bookings SET status=? WHERE id=?');$u->execute([$status,$id]);
        $titles=['confirmed'=>'Rezervasyonunuz onaylandı','cancelled'=>'Rezervasyonunuz iptal edildi','completed'=>'Konaklamanız tamamlandı'];
        if(!empty($booking['guest_user_id'])){
            $n=$pdo->prepare('INSERT INTO notifications(user_id,notification_type,title,body,created_at) VALUES(?,?,?,?,NOW())');
            $n->execute([(int)$booking['guest_user_id'],'booking.status_changed',$titles[$status],'Rezervasyon durumunuz güncellendi.']);
        }
        $log=$pdo->prepare('INSERT INTO audit_logs(user_id,action,entity_type,entity_id,ip_address,details,created_at) VALUES(?,?,?,?,?,?,NOW())');
        $log->execute([(int)Auth::user()['id'],'booking.status_changed','booking',$id,$_SERVER['REMOTE_ADDR']??null,json_encode(['from'=>$booking['status'],'to'=>$status],JSON_UNESCAPED_UNICODE)]);
        $pdo->commit();redirect('/vendor/bookings');
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Vendor booking status update failed: '.$e->getMessage());http_response_code(500);exit('Rezervasyon güncellenemedi.');}
});

$router->get('/vendor/properties', static function (): void {
    Auth::requireRole(['vendor']);$pdo=Database::connection();$s=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$s->execute([(int)Auth::user()['id']]);$vid=(int)$s->fetchColumn();
    $s=$pdo->prepare('SELECT id,title,slug,city,district,max_guests,base_nightly_price,currency,status FROM properties WHERE vendor_id=? ORDER BY id DESC');$s->execute([$vid]);$properties=$s->fetchAll();
    $newUrl='/vendor/properties/new';$editBase='/vendor/properties';$deleteAction='/vendor/properties/delete';$backUrl='/vendor';ThemeManager::renderTemplate('templates/pages/admin/properties.php', get_defined_vars());
});
$router->get('/vendor/properties/new', static function (): void {
    Auth::requireRole(['vendor']);$property=[];$existingImages=[];$deleteMediaAction='/vendor/properties/media/delete';$categories=Database::connection()->query('SELECT id,name FROM property_categories WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();$amenities=Database::connection()->query('SELECT id,name FROM amenities WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();$selectedAmenities=[];$defaultCurrency=(string)(Database::connection()->query("SELECT setting_value FROM settings WHERE setting_key='app.currency'")->fetchColumn()?:'TRY');$formAction='/vendor/properties/save';$backUrl='/vendor/properties';ThemeManager::renderTemplate('templates/pages/admin/property-form.php', get_defined_vars());
});
$router->get('/vendor/properties/{id}', static function (array $params): void {
    Auth::requireRole(['vendor']);$pdo=Database::connection();$s=$pdo->prepare('SELECT id FROM vendors WHERE user_id=?');$s->execute([(int)Auth::user()['id']]);$vid=(int)$s->fetchColumn();
    $s=$pdo->prepare('SELECT * FROM properties WHERE id=? AND vendor_id=?');$s->execute([(int)$params['id'],$vid]);$property=$s->fetch();
    if(!$property){http_response_code(404);return;}$mediaStmt=$pdo->prepare('SELECT * FROM property_media WHERE property_id=? ORDER BY is_cover DESC,sort_order,id');$mediaStmt->execute([(int)$property['id']]);$existingImages=$mediaStmt->fetchAll();$deleteMediaAction='/vendor/properties/media/delete';$categories=$pdo->query('SELECT id,name FROM property_categories WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();$amenities=$pdo->query('SELECT id,name FROM amenities WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();$a=$pdo->prepare('SELECT amenity_id FROM property_amenities WHERE property_id=?');$a->execute([(int)$property['id']]);$selectedAmenities=array_map('intval',$a->fetchAll(PDO::FETCH_COLUMN));$defaultCurrency=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.currency'")->fetchColumn()?:'TRY');$formAction='/vendor/properties/save';$backUrl='/vendor/properties';$availabilityUrl='/vendor/properties/'.(int)$property['id'].'/availability';ThemeManager::renderTemplate('templates/pages/admin/property-form.php', get_defined_vars());
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
    $latitudeRaw=trim((string)($_POST['latitude']??''));$longitudeRaw=trim((string)($_POST['longitude']??''));
    if(($latitudeRaw==='')!==($longitudeRaw==='')){http_response_code(422);exit('Harita için enlem ve boylam birlikte girilmelidir.');}
    $latitude=$latitudeRaw===''?null:(is_numeric($latitudeRaw)?(float)$latitudeRaw:null);$longitude=$longitudeRaw===''?null:(is_numeric($longitudeRaw)?(float)$longitudeRaw:null);
    if(($latitudeRaw!==''&&($latitude===null||$latitude < -90||$latitude > 90))||($longitudeRaw!==''&&($longitude===null||$longitude < -180||$longitude > 180))){http_response_code(422);exit('Enlem veya boylam değeri geçersiz.');}
    $coordSave=$pdo->prepare('UPDATE properties SET latitude=?,longitude=? WHERE id=? AND vendor_id=?');$coordSave->execute([$latitude,$longitude,$id,(int)$vendor['id']]);
    $location=villa_resolve_location($pdo);
    $locSave=$pdo->prepare('UPDATE properties SET location_country_id=?,location_city_id=?,location_district_id=?,location_neighborhood_id=?,country=?,city=?,district=?,neighborhood=? WHERE id=? AND vendor_id=?');
    $locSave->execute([$location['location_country_id'],$location['location_city_id'],$location['location_district_id'],$location['location_neighborhood_id'],$location['country'],$location['city'],$location['district'],$location['neighborhood'],$id,(int)$vendor['id']]);
    store_property_uploads($pdo,$id);sync_property_amenities($pdo,$id);
    redirect('/vendor/properties');
});
$router->get('/admin/vendors', static function (): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();
    if((string)$pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn()!=='multi_vendor')redirect('/admin');
    $vendors=$pdo->query('SELECT v.*,u.username,u.email FROM vendors v JOIN users u ON u.id=v.user_id ORDER BY v.created_at DESC')->fetchAll();ThemeManager::renderTemplate('templates/pages/admin/vendors.php', get_defined_vars());
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
    $s=$pdo->prepare("SELECT id,check_in,check_out,status,booking_code,guest_user_id FROM bookings WHERE property_id=? AND status='confirmed' AND check_in < ? AND check_out > ? ORDER BY check_in");$s->execute([(int)$property['id'],$monthEnd,$monthStart]);$calendarBookings=$s->fetchAll();
    $calendarPrev=$monthDate->modify('-1 month')->format('Y-m');$calendarNext=$monthDate->modify('+1 month')->format('Y-m');
    $saveUrl='/admin/properties/availability/save';$deleteUrl='/admin/properties/availability/delete';$backUrl='/admin/properties';ThemeManager::renderTemplate('templates/pages/admin/availability.php', get_defined_vars());
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
    $s=$pdo->prepare("SELECT id,check_in,check_out,status,booking_code,guest_user_id FROM bookings WHERE property_id=? AND status='confirmed' AND check_in < ? AND check_out > ? ORDER BY check_in");$s->execute([(int)$property['id'],$monthEnd,$monthStart]);$calendarBookings=$s->fetchAll();
    $calendarPrev=$monthDate->modify('-1 month')->format('Y-m');$calendarNext=$monthDate->modify('+1 month')->format('Y-m');
    $saveUrl='/vendor/properties/availability/save';$deleteUrl='/vendor/properties/availability/delete';$backUrl='/vendor/properties';ThemeManager::renderTemplate('templates/pages/admin/availability.php', get_defined_vars());
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
    ThemeManager::renderTemplate('templates/pages/admin/properties.php', get_defined_vars());
});

$router->get('/admin/properties/new', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    $property = []; $existingImages=[]; $deleteMediaAction='/admin/properties/media/delete'; $categories = Database::connection()->query('SELECT id,name FROM property_categories WHERE is_active=1 ORDER BY sort_order,name')->fetchAll(); $amenities=Database::connection()->query('SELECT id,name FROM amenities WHERE is_active=1 ORDER BY sort_order,name')->fetchAll(); $selectedAmenities=[]; $defaultCurrency=(string)(Database::connection()->query("SELECT setting_value FROM settings WHERE setting_key='app.currency'")->fetchColumn() ?: 'TRY');
    ThemeManager::renderTemplate('templates/pages/admin/property-form.php', get_defined_vars());
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
    $availabilityUrl='/admin/properties/'.(int)$property['id'].'/availability';
    ThemeManager::renderTemplate('templates/pages/admin/property-form.php', get_defined_vars());
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
    $latitudeRaw=trim((string)($_POST['latitude']??''));$longitudeRaw=trim((string)($_POST['longitude']??''));
    if(($latitudeRaw==='')!==($longitudeRaw==='')){http_response_code(422);exit('Harita için enlem ve boylam birlikte girilmelidir.');}
    $latitude=$latitudeRaw===''?null:(is_numeric($latitudeRaw)?(float)$latitudeRaw:null);$longitude=$longitudeRaw===''?null:(is_numeric($longitudeRaw)?(float)$longitudeRaw:null);
    if(($latitudeRaw!==''&&($latitude===null||$latitude < -90||$latitude > 90))||($longitudeRaw!==''&&($longitude===null||$longitude < -180||$longitude > 180))){http_response_code(422);exit('Enlem veya boylam değeri geçersiz.');}
    $coordSave=$pdo->prepare('UPDATE properties SET latitude=?,longitude=? WHERE id=?');$coordSave->execute([$latitude,$longitude,$id]);
    $location=villa_resolve_location($pdo);
    $locSave=$pdo->prepare('UPDATE properties SET location_country_id=?,location_city_id=?,location_district_id=?,location_neighborhood_id=?,country=?,city=?,district=?,neighborhood=? WHERE id=?');
    $locSave->execute([$location['location_country_id'],$location['location_city_id'],$location['location_district_id'],$location['location_neighborhood_id'],$location['country'],$location['city'],$location['district'],$location['neighborhood'],$id]);
    if ($id > 0) { store_property_uploads($pdo, $id); sync_property_amenities($pdo, $id); }
    redirect('/admin/properties');
});




$router->get('/admin/amenities', static function (): void {
    Auth::requireRole(['super_admin','admin']);$amenities=Database::connection()->query('SELECT * FROM amenities ORDER BY sort_order,name')->fetchAll();ThemeManager::renderTemplate('templates/pages/admin/amenities.php', get_defined_vars());
});
$router->post('/admin/amenities/save', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$name=trim((string)($_POST['name']??''));$icon=trim((string)($_POST['icon']??''));if($name===''){http_response_code(422);exit('Özellik adı zorunludur.');}
    $s=Database::connection()->prepare('INSERT INTO amenities(name,icon,is_active,sort_order) VALUES(?,?,1,?)');$s->execute([$name,$icon,(int)($_POST['sort_order']??10)]);redirect('/admin/amenities');
});
$router->post('/admin/amenities/delete', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$s=Database::connection()->prepare('DELETE FROM amenities WHERE id=?');$s->execute([(int)($_POST['id']??0)]);redirect('/admin/amenities');
});

$router->get('/admin/categories', static function (): void {
    Auth::requireRole(['super_admin','admin']);$categories=Database::connection()->query('SELECT * FROM property_categories ORDER BY sort_order,name')->fetchAll();ThemeManager::renderTemplate('templates/pages/admin/categories.php', get_defined_vars());
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
    $pdo=Database::connection();
    $items=$pdo->query('SELECT * FROM navigation_items ORDER BY location,COALESCE(parent_id,0),sort_order,id')->fetchAll();
    $mode=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode' LIMIT 1")->fetchColumn() ?: 'single_vendor');
    $systemPages=[
        ['label'=>'Ana Sayfa','url'=>'/'],
        ['label'=>'Konaklamalar','url'=>'/konaklamalar'],
        ['label'=>'Destinasyonlar','url'=>'/destinasyonlar'],
        ['label'=>'Giriş Yap','url'=>'/login'],
        ['label'=>'Favorilerim','url'=>'/favorites'],
        ['label'=>'Rezervasyonlarım','url'=>'/account/bookings'],
    ];
    if($mode==='multi_vendor')$systemPages[]=['label'=>'Ev Sahibi Kaydı','url'=>'/vendor/register'];
    $cmsPages=$pdo->query("SELECT title,slug FROM cms_pages WHERE language_code='tr' AND status='published' ORDER BY title")->fetchAll();
    $editItem=null;$editId=(int)($_GET['edit']??0);
    if($editId>0){$find=$pdo->prepare('SELECT * FROM navigation_items WHERE id=? LIMIT 1');$find->execute([$editId]);$editItem=$find->fetch()?:null;}
    ThemeManager::renderTemplate('templates/pages/admin/navigation.php', get_defined_vars());
});
$router->post('/admin/navigation/save', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$pdo=Database::connection();
    $id=(int)($_POST['id']??0);$label=trim((string)($_POST['label']??''));$url=trim((string)($_POST['url']??''));
    $location=in_array($_POST['location']??'', ['header','footer'],true)?$_POST['location']:'header';
    $target=($_POST['target']??'')==='_blank'?'_blank':'_self';
    $sort=max(0,min(9999,(int)($_POST['sort_order']??10)));$active=isset($_POST['is_active'])?1:0;$parentId=max(0,(int)($_POST['parent_id']??0));
    if($id>0){$current=$pdo->prepare('SELECT location,parent_id FROM navigation_items WHERE id=? LIMIT 1');$current->execute([$id]);$currentRow=$current->fetch();if($currentRow&&(int)($currentRow['parent_id']??0)===0&&(string)$currentRow['location']!==$location){$has=$pdo->prepare('SELECT 1 FROM navigation_items WHERE parent_id=? LIMIT 1');$has->execute([$id]);if($has->fetchColumn()){http_response_code(422);exit('Alt menüleri bulunan ana öğenin konumunu değiştirmeden önce alt öğelerini taşıyın.');}}}if($parentId>0){$p=$pdo->prepare('SELECT id,parent_id,location FROM navigation_items WHERE id=? LIMIT 1');$p->execute([$parentId]);$parent=$p->fetch();if(!$parent||(int)$parent['id']===$id||(string)$parent['location']!==$location||(int)($parent['parent_id']??0)>0){http_response_code(422);exit('Alt menü yalnızca aynı konumdaki bir ana menüye bağlanabilir.');}$hasChildren=$pdo->prepare('SELECT 1 FROM navigation_items WHERE parent_id=? LIMIT 1');$hasChildren->execute([$id]);if($id>0&&$hasChildren->fetchColumn()){http_response_code(422);exit('Alt menüsü bulunan bir öğeyi başka bir öğenin altına taşıyamazsınız. Önce alt öğelerini taşıyın.');}}
    $validUrl=$url!==''&&!preg_match('/[\r\n\\x00-\\x1F]/',$url)&&!str_starts_with($url,'//')&&(str_starts_with($url,'/')||preg_match('#^https?://#i',$url));
    if($label===''||mb_strlen($label)>120||!$validUrl){http_response_code(422);exit('Menü adı veya bağlantı adresi geçersiz.');}
    if($id>0){
        $s=$pdo->prepare('UPDATE navigation_items SET location=?,parent_id=?,label=?,url=?,target=?,sort_order=?,is_active=? WHERE id=?');
        $s->execute([$location,$parentId?:null,$label,$url,$target,$sort,$active,$id]);
    }else{
        $s=$pdo->prepare('INSERT INTO navigation_items(location,parent_id,language_code,label,url,target,sort_order,is_active) VALUES(?,?,?,?,?,?,?,?)');
        $s->execute([$location,$parentId?:null,'tr',$label,$url,$target,$sort,$active]);
    }
    redirect('/admin/navigation');
});
$router->post('/admin/navigation/reorder', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();header('Content-Type: application/json; charset=utf-8');
    $payload=json_decode((string)($_POST['tree']??''),true);
    if(!is_array($payload)){http_response_code(422);echo json_encode(['ok'=>false,'message'=>'Sıralama verisi geçersiz.']);return;}
    $pdo=Database::connection();$all=$pdo->query('SELECT id,location,parent_id FROM navigation_items')->fetchAll(PDO::FETCH_ASSOC);$known=[];foreach($all as $row)$known[(int)$row['id']]=$row;
    $seen=[];$updates=[];
    foreach(['header','footer'] as $location){
        $group=$payload[$location]??[];if(!is_array($group))continue;$order=0;
        foreach($group as $node){if(!is_array($node))continue;$id=(int)($node['id']??0);if(!isset($known[$id])||$known[$id]['location']!==$location||isset($seen[$id]))continue;$seen[$id]=true;$updates[]=[$order++,$id,null];
            $childOrder=0;foreach((array)($node['children']??[]) as $childId){$childId=(int)$childId;if(!isset($known[$childId])||$known[$childId]['location']!==$location||isset($seen[$childId])||$childId===$id)continue;$seen[$childId]=true;$updates[]=[$childOrder++,$childId,$id];}
        }
    }
    try{$pdo->beginTransaction();$up=$pdo->prepare('UPDATE navigation_items SET sort_order=?,parent_id=? WHERE id=?');foreach($updates as [$sort,$id,$parent])$up->execute([$sort,$parent,$id]);$pdo->commit();echo json_encode(['ok'=>true]);}
    catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();http_response_code(500);echo json_encode(['ok'=>false,'message'=>'Sıralama kaydedilemedi.']);}
});
$router->post('/admin/navigation/toggle', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();
    $s=Database::connection()->prepare('UPDATE navigation_items SET is_active=1-is_active WHERE id=?');$s->execute([(int)($_POST['id']??0)]);
    redirect('/admin/navigation');
});
$router->post('/admin/navigation/delete', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();
    $pdo=Database::connection();$id=(int)($_POST['id']??0);$pdo->beginTransaction();$promote=$pdo->prepare('UPDATE navigation_items SET parent_id=NULL WHERE parent_id=?');$promote->execute([$id]);$s=$pdo->prepare('DELETE FROM navigation_items WHERE id=?');$s->execute([$id]);$pdo->commit();redirect('/admin/navigation');
});


$router->get('/admin/pages', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    $pages=Database::connection()->query("SELECT id,title,slug,status FROM cms_pages WHERE language_code='tr' ORDER BY id DESC")->fetchAll();
    ThemeManager::renderTemplate('templates/pages/admin/pages.php', get_defined_vars());
});
$router->get('/admin/pages/new', static function (): void {
    Auth::requireRole(['super_admin','admin']);$page=[];ThemeManager::renderTemplate('templates/pages/admin/page-form.php', get_defined_vars());
});
$router->get('/admin/pages/{id}', static function (array $params): void {
    Auth::requireRole(['super_admin','admin']);$s=Database::connection()->prepare("SELECT * FROM cms_pages WHERE id=? AND language_code='tr'");$s->execute([(int)$params['id']]);$page=$s->fetch();
    if(!$page){http_response_code(404);return;}ThemeManager::renderTemplate('templates/pages/admin/page-form.php', get_defined_vars());
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
    $navigation=villa_navigation_tree($pdo,'header','tr');
    $footerNavigation=villa_navigation_tree($pdo,'footer','tr');
    $mode=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode' LIMIT 1")->fetchColumn() ?: 'single_vendor');
    $user=Auth::user(); $locale='tr';
    $destinations=[];$featuredProperties=[];$latestProperties=[];$categories=[];$stylePhotos=[];
    $blogPosts=$pdo->query("SELECT id,title,slug,excerpt,featured_image,published_at FROM blog_posts WHERE status='published' AND (published_at IS NULL OR published_at<=NOW()) ORDER BY COALESCE(published_at,created_at) DESC,id DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
    ThemeManager::render('page.php', get_defined_vars());
});





$router->post('/newsletter/subscribe', static function (): void {
    verify_csrf();$pdo=Database::connection();
    if(trim((string)($_POST['website']??''))!==''){redirect('/?newsletter=success');}
    $email=mb_strtolower(trim((string)($_POST['email']??'')));
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen($email)>190||empty($_POST['consent'])){redirect(villa_return_with_status((string)($_POST['return_to']??'/'),'newsletter','invalid'));}
    $source=mb_substr((string)($_POST['source']??'site'),0,190);
    $ip=mb_substr((string)($_SERVER['REMOTE_ADDR']??''),0,45);
    $s=$pdo->prepare("INSERT INTO newsletter_subscribers(email,status,source,ip_address) VALUES(?,'active',?,?) ON DUPLICATE KEY UPDATE status='active',source=VALUES(source),ip_address=VALUES(ip_address),updated_at=NOW()");
    $s->execute([$email,$source,$ip]);
    $back=villa_safe_return_url((string)($_POST['return_to']??'/'));
    redirect($back.(str_contains($back,'?')?'&':'?').'newsletter=success');
});
$router->post('/contact', static function (): void {
    verify_csrf();$pdo=Database::connection();
    if(trim((string)($_POST['website']??''))!==''){redirect('/?contact=success');}
    $name=trim((string)($_POST['name']??''));$email=mb_strtolower(trim((string)($_POST['email']??'')));
    $phone=trim((string)($_POST['phone']??''));$subject=trim((string)($_POST['subject']??''));$message=trim((string)($_POST['message']??''));
    if($name===''||mb_strlen($name)>150||!filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen($email)>190||$message===''||mb_strlen($message)>5000||mb_strlen($phone)>50||mb_strlen($subject)>220){
        redirect(villa_return_with_status((string)($_POST['return_to']??'/'),'contact','invalid'));
    }
    $s=$pdo->prepare("INSERT INTO contact_messages(name,email,phone,subject,message,ip_address) VALUES(?,?,?,?,?,?)");
    $s->execute([$name,$email,$phone,$subject,$message,mb_substr((string)($_SERVER['REMOTE_ADDR']??''),0,45)]);
    redirect(villa_return_with_status((string)($_POST['return_to']??'/'),'contact','success'));
});
$router->get('/blog/{slug}', static function (array $params): void {
    $pdo=Database::connection();$s=$pdo->prepare("SELECT * FROM blog_posts WHERE slug=? AND status='published' AND (published_at IS NULL OR published_at<=NOW()) LIMIT 1");$s->execute([(string)$params['slug']]);$post=$s->fetch(PDO::FETCH_ASSOC);
    if(!$post){http_response_code(404);echo 'Yazı bulunamadı.';return;}
    $page=['title'=>$post['title'],'content'=>trim((string)$post['excerpt'])."\n\n".(string)$post['content']];
    $content=$pdo->query("SELECT content_key,content_value FROM site_content WHERE language_code='tr'")->fetchAll(PDO::FETCH_KEY_PAIR);$siteName=$content['site_name']??'VillaStay';
    $navigation=villa_navigation_tree($pdo,'header','tr');$footerNavigation=villa_navigation_tree($pdo,'footer','tr');
    $mode=(string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode' LIMIT 1")->fetchColumn() ?: 'single_vendor');$user=Auth::user();$locale='tr';$destinations=[];$featuredProperties=[];$latestProperties=[];$categories=[];$stylePhotos=[];
    ThemeManager::render('page.php',get_defined_vars());
});


$router->get('/admin/blog', static function (): void {
    Auth::requireRole(['super_admin','admin']);$posts=Database::connection()->query("SELECT id,title,slug,status,published_at,updated_at FROM blog_posts ORDER BY updated_at DESC,id DESC")->fetchAll(PDO::FETCH_ASSOC);$post=null;ThemeManager::renderTemplate('templates/pages/admin/blog.php', get_defined_vars());
});
$router->get('/admin/blog/new', static function (): void {
    Auth::requireRole(['super_admin','admin']);$posts=[];$post=[];ThemeManager::renderTemplate('templates/pages/admin/blog.php', get_defined_vars());
});
$router->get('/admin/blog/{id}', static function (array $params): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();$posts=[];$s=$pdo->prepare('SELECT * FROM blog_posts WHERE id=?');$s->execute([(int)$params['id']]);$post=$s->fetch(PDO::FETCH_ASSOC);if(!$post){http_response_code(404);exit('Blog yazısı bulunamadı.');}ThemeManager::renderTemplate('templates/pages/admin/blog.php', get_defined_vars());
});
$router->post('/admin/blog/save', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$pdo=Database::connection();$id=(int)($_POST['id']??0);
    $title=trim((string)($_POST['title']??''));$slug=strtolower(trim((string)($_POST['slug']??'')));$slug=trim(preg_replace('/[^a-z0-9-]+/','-',$slug),'-');
    $excerpt=trim((string)($_POST['excerpt']??''));$content=trim((string)($_POST['content']??''));$image=trim((string)($_POST['featured_image']??''));$status=($_POST['status']??'')==='published'?'published':'draft';
    if($title===''||$slug===''||$content===''||mb_strlen($title)>220||mb_strlen($slug)>190||mb_strlen($excerpt)>1500||mb_strlen($image)>500||($image!==''&&!preg_match('~^(https?://|/)~i',$image))){http_response_code(422);exit('Başlık, URL, içerik ve görsel adresini kontrol edin.');}
    try{if($id){$s=$pdo->prepare("UPDATE blog_posts SET title=?,slug=?,excerpt=?,content=?,featured_image=?,status=?,published_at=IF(?='published',COALESCE(published_at,NOW()),NULL) WHERE id=?");$s->execute([$title,$slug,$excerpt,$content,$image,$status,$status,$id]);}else{$s=$pdo->prepare("INSERT INTO blog_posts(title,slug,excerpt,content,featured_image,status,published_at) VALUES(?,?,?,?,?,?,IF(?='published',NOW(),NULL))");$s->execute([$title,$slug,$excerpt,$content,$image,$status,$status]);}}catch(PDOException $e){http_response_code(409);exit('Bu URL kısa adı başka bir yazıda kullanılıyor.');}
    redirect('/admin/blog');
});
$router->post('/admin/blog/delete', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$s=Database::connection()->prepare('DELETE FROM blog_posts WHERE id=?');$s->execute([(int)($_POST['id']??0)]);redirect('/admin/blog');
});
$router->get('/admin/newsletter', static function (): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();$subscribers=$pdo->query('SELECT id,email,status,source,created_at FROM newsletter_subscribers ORDER BY created_at DESC,id DESC')->fetchAll(PDO::FETCH_ASSOC);$activeCount=(int)$pdo->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status='active'")->fetchColumn();ThemeManager::renderTemplate('templates/pages/admin/newsletter.php', get_defined_vars());
});
$router->post('/admin/newsletter/toggle', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$s=Database::connection()->prepare("UPDATE newsletter_subscribers SET status=IF(status='active','unsubscribed','active') WHERE id=?");$s->execute([(int)($_POST['id']??0)]);redirect('/admin/newsletter');
});
$router->get('/admin/messages', static function (): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();$messages=$pdo->query('SELECT * FROM contact_messages ORDER BY FIELD(status,\'new\',\'read\',\'replied\',\'archived\'),created_at DESC,id DESC')->fetchAll(PDO::FETCH_ASSOC);$newCount=(int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status='new'")->fetchColumn();ThemeManager::renderTemplate('templates/pages/admin/messages.php', get_defined_vars());
});
$router->post('/admin/messages/status', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$status=(string)($_POST['status']??'read');if(!in_array($status,['new','read','replied','archived'],true))$status='read';$s=Database::connection()->prepare('UPDATE contact_messages SET status=? WHERE id=?');$s->execute([$status,(int)($_POST['id']??0)]);redirect('/admin/messages');
});

$router->get('/admin/translations', static function (): void {
    Auth::requireRole(['super_admin','admin']);$pdo=Database::connection();$languages=$pdo->query('SELECT code,name FROM languages WHERE is_active=1 ORDER BY is_default DESC,name')->fetchAll();
    $locale=(string)($_GET['lang']??'tr');$codes=array_column($languages,'code');if(!in_array($locale,$codes,true))$locale='tr';
    $s=$pdo->prepare('SELECT translation_key,translation_value FROM translations WHERE language_code=? ORDER BY translation_key');$s->execute([$locale]);$translations=$s->fetchAll(PDO::FETCH_KEY_PAIR);ThemeManager::renderTemplate('templates/pages/admin/translations.php', get_defined_vars());
});
$router->post('/admin/translations', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$pdo=Database::connection();$locale=(string)($_POST['lang']??'tr');$check=$pdo->prepare('SELECT code FROM languages WHERE code=? AND is_active=1');$check->execute([$locale]);if(!$check->fetchColumn()){$locale='tr';}
    $save=$pdo->prepare('INSERT INTO translations(language_code,translation_key,translation_value) VALUES(?,?,?) ON DUPLICATE KEY UPDATE translation_value=VALUES(translation_value)');
    foreach((array)($_POST['translations']??[]) as $key=>$value){$key=(string)$key;if(preg_match('/^[a-zA-Z0-9_.-]{1,190}$/',$key))$save->execute([$locale,$key,(string)$value]);}
    $newKey=trim((string)($_POST['new_key']??''));$newValue=(string)($_POST['new_value']??'');if($newKey!==''&&preg_match('/^[a-zA-Z0-9_.-]{1,190}$/',$newKey))$save->execute([$locale,$newKey,$newValue]);
    redirect('/admin/translations?lang='.rawurlencode($locale));
});

$router->get('/admin/users', static function (): void {
    Auth::requireRole(['super_admin','admin']);$users=Database::connection()->query('SELECT id,username,email,role,status,created_at FROM users ORDER BY created_at DESC LIMIT 500')->fetchAll();$currentUser=Auth::user();ThemeManager::renderTemplate('templates/pages/admin/users.php', get_defined_vars());
});
$router->post('/admin/users/status', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();$id=(int)($_POST['id']??0);$status=($_POST['status']??'')==='active'?'active':'disabled';
    if($id===(int)Auth::user()['id']){http_response_code(422);exit('Kendi hesabınızın durumunu bu ekrandan değiştiremezsiniz.');}
    $s=Database::connection()->prepare("UPDATE users SET status=? WHERE id=? AND role<>'super_admin'");$s->execute([$status,$id]);redirect('/admin/users');
});

$router->post('/admin/users/delete', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    verify_csrf();
    $id=(int)($_POST['id']??0);
    $currentUser=Auth::user();
    if($id<1||$id===(int)$currentUser['id']){
        http_response_code(422);
        exit('Geçersiz işlem: kendi hesabınızı silemezsiniz.');
    }
    $pdo=Database::connection();
    try{
        $pdo->beginTransaction();
        $lookup=$pdo->prepare('SELECT id,role FROM users WHERE id=? FOR UPDATE');
        $lookup->execute([$id]);
        $target=$lookup->fetch(PDO::FETCH_ASSOC);
        if(!$target||!in_array((string)$target['role'],['customer','vendor'],true)){
            $pdo->rollBack();
            http_response_code(422);
            exit('Yalnızca müşteri veya ev sahibi üyelikleri silinebilir. Yönetici hesapları korunur.');
        }
        // Keep reservation and payment history; detach the account before deleting it.
        $bookings=$pdo->prepare('UPDATE bookings SET guest_user_id=NULL WHERE guest_user_id=?');
        $bookings->execute([$id]);
        $delete=$pdo->prepare("DELETE FROM users WHERE id=? AND role IN ('customer','vendor')");
        $delete->execute([$id]);
        if($delete->rowCount()!==1){
            throw new RuntimeException('Üye kaydı silinemedi.');
        }
        $pdo->commit();
        redirect('/admin/users?deleted=1');
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        error_log('Admin member deletion failed: '.$e->getMessage());
        http_response_code(409);
        exit('Üye silinemedi. İlişkili kayıtlar korunarak işlem güvenli şekilde durduruldu.');
    }
});


$router->get('/brand-assets/{slug}/{filename}', static function (array $params): void {
    $slug = (string)($params['slug'] ?? '');
    $filename = (string)($params['filename'] ?? '');
    if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug) ||
        !preg_match('/^[a-z0-9_-]+\\.(?:png|jpe?g|webp|mp4|webm)$/i', $filename) ||
        !isset(ThemeManager::installed()[$slug])) {
        http_response_code(404); exit;
    }
    $directory = APP_ROOT . '/public/uploads/themes/' . $slug . '/branding';
    $path = $directory . '/' . $filename;
    $realDirectory = realpath($directory);
    $realPath = realpath($path);
    if ($realDirectory === false || $realPath === false ||
        !str_starts_with($realPath, rtrim($realDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR) ||
        !is_file($realPath) || !is_readable($realPath)) {
        http_response_code(404); exit;
    }
    $types = [
        'png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp',
        'mp4'=>'video/mp4','webm'=>'video/webm'
    ];
    $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
    if (!isset($types[$extension])) { http_response_code(404); exit; }
    $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($realPath);
    if (!is_string($mime) || $mime !== $types[$extension]) { http_response_code(404); exit; }
    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=3600');
    header('Content-Length: ' . (string)filesize($realPath));
    readfile($realPath);
    exit;
});

$router->get('/theme-assets/{slug}/{file*}', static function (array $params): void {
    $slug = (string)($params['slug'] ?? '');
    $relative = str_replace('\\', '/', (string)($params['file'] ?? ''));
    if (!preg_match('/^[a-z0-9_-]{1,60}$/', $slug) || $relative === '') {
        http_response_code(404); exit;
    }
    $themeRelative = $relative === 'style.css' ? 'style.css' : 'assets/' . $relative;
    $path = ThemeManager::resolveFile($themeRelative, $slug);
    if ($path === null) { http_response_code(404); exit; }
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $types = [
        'css'=>'text/css','js'=>'application/javascript','mjs'=>'application/javascript',
        'png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif',
        'webp'=>'image/webp','avif'=>'image/avif','svg'=>'image/svg+xml','ico'=>'image/x-icon',
        'woff'=>'font/woff','woff2'=>'font/woff2','ttf'=>'font/ttf','otf'=>'font/otf','eot'=>'application/vnd.ms-fontobject',
        'json'=>'application/json','map'=>'application/json'
    ];
    if (!isset($types[$extension])) { http_response_code(404); exit; }
    $etag = '"' . sha1($path . ':' . (string)filemtime($path)) . '"';
    $charset = in_array($extension, ['css','js','mjs','svg','json','map'], true) ? '; charset=UTF-8' : '';
    header('Content-Type: ' . $types[$extension] . $charset);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=3600');
    header('ETag: ' . $etag);
    if (trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) { http_response_code(304); exit; }
    readfile($path); exit;
});

$router->get('/admin/themes', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    $themes = ThemeManager::installed();
    $activeTheme = ThemeManager::activeSlug();
    $themeMessage = (string)($_SESSION['villa_theme_manager_message'] ?? '');
    $themeMessageType = (string)($_SESSION['villa_theme_manager_message_type'] ?? 'success');
    unset($_SESSION['villa_theme_manager_message'], $_SESSION['villa_theme_manager_message_type']);
    ThemeManager::renderTemplate('templates/pages/admin/themes.php', get_defined_vars());
});
$router->post('/admin/themes/activate', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    verify_csrf();
    try {
        ThemeManager::saveActiveTheme((string)($_POST['theme_slug'] ?? ''));
        $_SESSION['villa_theme_manager_message'] = Translator::get('admin.themes.save_success');
        $_SESSION['villa_theme_manager_message_type'] = 'success';
    } catch (Throwable $e) {
        error_log('Theme activation failed: ' . $e->getMessage());
        $_SESSION['villa_theme_manager_message'] = Translator::get('admin.themes.save_error');
        $_SESSION['villa_theme_manager_message_type'] = 'error';
    }
    redirect('/admin/themes');
});


$router->get('/admin/theme-design', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    $themeDesignSlug = ThemeManager::activeSlug();
    $themeDesignSettings = ThemeManager::designSettings($themeDesignSlug);
    $themeCardSettings = ThemeManager::cardSettings($themeDesignSlug);
    $themeHeroTypographySettings = ThemeManager::heroTypographySettings($themeDesignSlug);
    $themeTypographySettings = ThemeManager::typographySettings($themeDesignSlug);
    $themeIconSettings = ThemeManager::iconSettings($themeDesignSlug);
    $themeBrandSettings = ThemeManager::brandSettings($themeDesignSlug);
    $themeFamilyBannerTextSettings = ThemeManager::familyBannerTextSettings($themeDesignSlug);
    $themeFooterSettings = ThemeManager::footerSettings($themeDesignSlug);
    $themeBrandMessage = (string)($_SESSION['villa_theme_brand_message'] ?? '');
    $themeBrandMessageType = (string)($_SESSION['villa_theme_brand_message_type'] ?? 'success');
    unset($_SESSION['villa_theme_brand_message'], $_SESSION['villa_theme_brand_message_type']);
    $themeDesignMessage = (string)($_SESSION['villa_theme_design_message'] ?? '');
    $themeDesignMessageType = (string)($_SESSION['villa_theme_design_message_type'] ?? 'success');
    unset($_SESSION['villa_theme_design_message'], $_SESSION['villa_theme_design_message_type']);
    ThemeManager::renderTemplate('templates/pages/admin/theme-design.php', get_defined_vars());
});
$router->post('/admin/theme-design', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    verify_csrf();
    try {
        $activeThemeSlug = ThemeManager::activeSlug();
        ThemeManager::saveDesignSettings((array)($_POST['design'] ?? []), $activeThemeSlug);
        if (isset($_POST['hero_typography']) && is_array($_POST['hero_typography'])) ThemeManager::saveHeroTypographySettings($_POST['hero_typography'], $activeThemeSlug);
        if (isset($_POST['typography']) && is_array($_POST['typography'])) ThemeManager::saveTypographySettings($_POST['typography'], $activeThemeSlug);
        if (isset($_POST['cards']) && is_array($_POST['cards'])) ThemeManager::saveCardSettings($_POST['cards'], $activeThemeSlug);
        if (isset($_POST['footer']) && is_array($_POST['footer'])) ThemeManager::saveFooterSettings($_POST['footer'], $activeThemeSlug);
        ThemeManager::saveIconSettings((array)($_POST['icons'] ?? []), $activeThemeSlug);
        $_SESSION['villa_theme_design_message'] = 'Tema, tipografi, villa kartı ve footer ayarları başarıyla kaydedildi.';
        $_SESSION['villa_theme_design_message_type'] = 'success';
    } catch (Throwable $e) {
        error_log('Theme design save failed: ' . $e->getMessage());
        $_SESSION['villa_theme_design_message'] = 'Kayıt başarısız: ' . $e->getMessage();
        $_SESSION['villa_theme_design_message_type'] = 'error';
    }
    redirect('/admin/theme-design');
});

$router->post('/admin/theme-design/family-banner-text', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    verify_csrf();
    try {
        ThemeManager::saveFamilyBannerTextSettings((array)($_POST['family_banner_text'] ?? []), ThemeManager::activeSlug());
        $_SESSION['villa_theme_brand_message'] = 'Ana sayfa banner metin ve tipografi ayarları kaydedildi.';
        $_SESSION['villa_theme_brand_message_type'] = 'success';
    } catch (Throwable $e) {
        error_log('Family banner text settings save failed: ' . $e->getMessage());
        $_SESSION['villa_theme_brand_message'] = 'Banner metin ayarları kaydedilemedi: ' . $e->getMessage();
        $_SESSION['villa_theme_brand_message_type'] = 'error';
    }
    redirect('/admin/theme-design');
});

$router->post('/admin/theme-design/brand-asset', static function (): void {
    Auth::requireRole(['super_admin','admin']); verify_csrf();
    try {
        $type = (string)($_POST['asset_type'] ?? '');
        $field = $type === 'logo' ? 'brand_logo' : ($type === 'hero_video' ? 'hero_video' : ($type === 'family_banner' ? 'family_banner' : ''));
        if ($field === '') throw new RuntimeException('Dosya türü geçersiz.');
        ThemeManager::saveBrandAsset($type, (array)($_FILES[$field] ?? []), ThemeManager::activeSlug());
        $_SESSION['villa_theme_brand_message'] = ['logo'=>'Aktif tema logosu başarıyla güncellendi.','hero_video'=>'Aktif tema hero videosu başarıyla güncellendi.','family_banner'=>'Ana sayfa aile banner görseli başarıyla güncellendi.'][$type] ?? 'Tema görseli güncellendi.';
        $_SESSION['villa_theme_brand_message_type'] = 'success';
    } catch (Throwable $e) {
        error_log('Theme visual asset upload failed: ' . $e->getMessage());
        $_SESSION['villa_theme_brand_message'] = 'Yükleme başarısız: ' . $e->getMessage();
        $_SESSION['villa_theme_brand_message_type'] = 'error';
    }
    redirect('/admin/theme-design');
});
$router->post('/admin/theme-design/brand-asset/remove', static function (): void {
    Auth::requireRole(['super_admin','admin']); verify_csrf();
    try {
        ThemeManager::removeBrandAsset((string)($_POST['asset_type'] ?? ''), ThemeManager::activeSlug());
        $_SESSION['villa_theme_brand_message'] = 'Tema dosyası kaldırıldı.';
        $_SESSION['villa_theme_brand_message_type'] = 'success';
    } catch (Throwable $e) {
        error_log('Theme visual asset removal failed: ' . $e->getMessage());
        $_SESSION['villa_theme_brand_message'] = 'Dosya kaldırılamadı.';
        $_SESSION['villa_theme_brand_message_type'] = 'error';
    }
    redirect('/admin/theme-design');
});

$router->get('/admin/header-settings', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    $socialSettings=ThemeManager::socialSettings();
    $themeMessage=(string)($_SESSION['villa_theme_message']??'');
    $themeMessageType=(string)($_SESSION['villa_theme_message_type']??'success');
    unset($_SESSION['villa_theme_message'],$_SESSION['villa_theme_message_type']);
    ThemeManager::renderTemplate('templates/pages/admin/header-settings.php', get_defined_vars());
});
$router->post('/admin/header-settings/social-icons', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();
    try {
        ThemeManager::saveSocialSettings((array)($_POST['social']??[]));
        $_SESSION['villa_theme_message']='Üst bilgi ayarları başarıyla veritabanına kaydedildi.';
        $_SESSION['villa_theme_message_type']='success';
    } catch(Throwable $e) {
        error_log('Shared header settings save failed: '.$e->getMessage());
        $_SESSION['villa_theme_message']='Kayıt başarısız: '.$e->getMessage();
        $_SESSION['villa_theme_message_type']='error';
    }
    redirect('/admin/header-settings');
});

$router->get('/admin/typography', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    $typographySettings = ThemeManager::typographySettings(ThemeManager::activeSlug());
    $typographyMessage = (string)($_SESSION['villa_typography_message'] ?? '');
    $typographyMessageType = (string)($_SESSION['villa_typography_message_type'] ?? 'success');
    unset($_SESSION['villa_typography_message'], $_SESSION['villa_typography_message_type']);
    ThemeManager::renderTemplate('templates/pages/admin/typography.php', get_defined_vars());
});
$router->post('/admin/typography', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    verify_csrf();
    try {
        ThemeManager::saveTypographySettings((array)($_POST['typography'] ?? []), ThemeManager::activeSlug());
        $_SESSION['villa_typography_message'] = 'Tema tipografi ayarları başarıyla kaydedildi.';
        $_SESSION['villa_typography_message_type'] = 'success';
    } catch (Throwable $e) {
        error_log('Theme typography save failed: ' . $e->getMessage());
        $_SESSION['villa_typography_message'] = 'Kayıt başarısız: ' . $e->getMessage();
        $_SESSION['villa_typography_message_type'] = 'error';
    }
    redirect('/admin/typography');
});

$router->get('/admin/settings', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    $settings=Database::connection()->query("SELECT setting_key,setting_value FROM settings WHERE setting_key IN ('platform.commission_percent','app.currency','app.timezone','payments.iyzico.environment','payments.paytr.environment','payments.iyzico.enabled','payments.paytr.enabled','payments.iyzico.api_key','payments.iyzico.secret_key','payments.paytr.merchant_id','payments.paytr.merchant_key','payments.paytr.merchant_salt','mobile_bar.enabled','mobile_bar.whatsapp','mobile_bar.phone')")->fetchAll(PDO::FETCH_KEY_PAIR);
    $iyzicoEnabled=($settings['payments.iyzico.enabled']??'0')==='1';
    $paytrEnabled=($settings['payments.paytr.enabled']??'0')==='1';
    $iyzicoConfigured=!empty($settings['payments.iyzico.api_key'])&&!empty($settings['payments.iyzico.secret_key']);
    $paytrConfigured=!empty($settings['payments.paytr.merchant_id'])&&!empty($settings['payments.paytr.merchant_key'])&&!empty($settings['payments.paytr.merchant_salt']);
    $iyzicoTestMessage=(string)($_SESSION['villa_iyzico_test_message']??'');
    $iyzicoTestMessageType=(string)($_SESSION['villa_iyzico_test_message_type']??'success');
    unset($_SESSION['villa_iyzico_test_message'],$_SESSION['villa_iyzico_test_message_type']);
    unset($settings['payments.iyzico.api_key'],$settings['payments.iyzico.secret_key'],$settings['payments.paytr.merchant_id'],$settings['payments.paytr.merchant_key'],$settings['payments.paytr.merchant_salt']);
    $mobileBarEnabled=($settings['mobile_bar.enabled']??'1')==='1';
    ThemeManager::renderTemplate('templates/pages/admin/settings.php',get_defined_vars());
});
$router->post('/admin/settings', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();
    if (!empty($_POST['test_iyzico_sandbox'])) {
        try {
            $config=\App\Core\PaymentGatewayConfig::forProvider('iyzico');
            if (!$config['configured']) throw new RuntimeException('Önce iyzico API Key ve Secret Key bilgilerini kaydedin.');
            $result=\App\Core\IyzicoApiClient::testSandboxConnection($config);
            $_SESSION['villa_iyzico_test_message']='iyzico Sandbox bağlantısı başarılı. BIN kontrolü yanıtı alındı; ödeme veya tahsilat oluşturulmadı.';
            $_SESSION['villa_iyzico_test_message_type']='success';
        } catch (Throwable $e) {
            error_log('iyzico sandbox connection test failed: '.$e->getMessage());
            $_SESSION['villa_iyzico_test_message']='Sandbox bağlantı testi başarısız: '.$e->getMessage();
            $_SESSION['villa_iyzico_test_message_type']='error';
        }
        redirect('/admin/settings');
    }
    $mobileBarEnabled=!empty($_POST['mobile_bar_enabled'])&&$_POST['mobile_bar_enabled']==='1';
    $mobileWhatsapp=preg_replace('/\\D+/','',trim((string)($_POST['mobile_bar_whatsapp']??'')))??'';
    $mobilePhone=preg_replace('/\\D+/','',trim((string)($_POST['mobile_bar_phone']??'')))??'';
    if(($mobileWhatsapp!==''&&(strlen($mobileWhatsapp)<7||strlen($mobileWhatsapp)>15))||($mobilePhone!==''&&(strlen($mobilePhone)<7||strlen($mobilePhone)>15))){http_response_code(422);exit('WhatsApp ve telefon numaralarını ülke koduyla birlikte kontrol edin.');}
    $commission=(float)($_POST['commission']??12);$currency=strtoupper(trim((string)($_POST['currency']??'TRY')));$timezone=trim((string)($_POST['timezone']??'Europe/Istanbul'));
    $iyzicoEnvironment=(string)($_POST['iyzico_environment']??'sandbox');$paytrEnvironment=(string)($_POST['paytr_environment']??'sandbox');
    if($commission<0||$commission>50||!preg_match('/^[A-Z]{3}$/',$currency)||!in_array($timezone,DateTimeZone::listIdentifiers(),true)||!in_array($iyzicoEnvironment,['sandbox','production'],true)||!in_array($paytrEnvironment,['sandbox','production'],true)){http_response_code(422);exit('Ayar değerlerini kontrol edin.');}
    $pdo=Database::connection();$save=$pdo->prepare('INSERT INTO settings(setting_key,setting_value,autoload) VALUES(?,?,1) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),autoload=1');
    $iyzicoEnabled=!empty($_POST['iyzico_enabled'])&&$_POST['iyzico_enabled']==='1';
    $paytrEnabled=!empty($_POST['paytr_enabled'])&&$_POST['paytr_enabled']==='1';
    $anyPaymentEnabled=$iyzicoEnabled||$paytrEnabled;
    foreach(['platform.commission_percent'=>(string)$commission,'app.currency'=>$currency,'app.timezone'=>$timezone,'payments.iyzico.environment'=>$iyzicoEnvironment,'payments.paytr.environment'=>$paytrEnvironment,'payments.iyzico.enabled'=>$iyzicoEnabled?'1':'0','payments.paytr.enabled'=>$paytrEnabled?'1':'0','payments.enabled'=>$anyPaymentEnabled?'1':'0','payments.provider'=>$anyPaymentEnabled?'multiple':'none','mobile_bar.enabled'=>$mobileBarEnabled?'1':'0','mobile_bar.whatsapp'=>$mobileWhatsapp,'mobile_bar.phone'=>$mobilePhone] as $key=>$value)$save->execute([$key,$value]);
    $secrets=['payments.iyzico.api_key'=>'iyzico_api_key','payments.iyzico.secret_key'=>'iyzico_secret_key','payments.paytr.merchant_id'=>'paytr_merchant_id','payments.paytr.merchant_key'=>'paytr_merchant_key','payments.paytr.merchant_salt'=>'paytr_merchant_salt'];
    foreach($secrets as $key=>$field){$value=trim((string)($_POST[$field]??''));if($value!=='')$save->execute([$key,\App\Core\PaymentSecretVault::encrypt($value)]);}
    $clear=[];if(!empty($_POST['clear_iyzico']))$clear=array_merge($clear,['payments.iyzico.api_key','payments.iyzico.secret_key']);if(!empty($_POST['clear_paytr']))$clear=array_merge($clear,['payments.paytr.merchant_id','payments.paytr.merchant_key','payments.paytr.merchant_salt']);
    if($clear){$delete=$pdo->prepare('DELETE FROM settings WHERE setting_key=?');foreach($clear as $key)$delete->execute([$key]);}
    redirect('/admin/settings');
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
        'footer_text'=>'Alt bilgi metni',
        'home_latest_title'=>'Son eklenen villalar başlığı',
        'home_latest_subtitle'=>'Son eklenen villalar açıklaması',
        'home_trust_1_title'=>'Güven avantajı 1 başlığı',
        'home_trust_1_text'=>'Güven avantajı 1 açıklaması',
        'home_trust_2_title'=>'Güven avantajı 2 başlığı',
        'home_trust_2_text'=>'Güven avantajı 2 açıklaması',
        'home_trust_3_title'=>'Güven avantajı 3 başlığı',
        'home_trust_3_text'=>'Güven avantajı 3 açıklaması',
        'home_trust_4_title'=>'Güven avantajı 4 başlığı',
        'home_trust_4_text'=>'Güven avantajı 4 açıklaması',
        'home_concepts_title'=>'Tatil konseptleri başlığı',
        'home_concepts_subtitle'=>'Tatil konseptleri açıklaması',
        'home_collections_title'=>'Villa koleksiyonları başlığı',
        'home_collections_subtitle'=>'Villa koleksiyonları açıklaması',
        'home_regions_title'=>'Bölgeler başlığı',
        'home_regions_subtitle'=>'Bölgeler açıklaması',
        'home_reviews_title'=>'Misafir yorumları başlığı',
        'home_reviews_subtitle'=>'Misafir yorumları açıklaması',
        'home_faq_title'=>'Sıkça sorulan sorular başlığı',
        'home_faq_subtitle'=>'Sıkça sorulan sorular açıklaması',
        'home_faq_1_question'=>'SSS 1 soru',
        'home_faq_1_answer'=>'SSS 1 yanıt',
        'home_faq_2_question'=>'SSS 2 soru',
        'home_faq_2_answer'=>'SSS 2 yanıt',
        'home_faq_3_question'=>'SSS 3 soru',
        'home_faq_3_answer'=>'SSS 3 yanıt',
        'home_faq_4_question'=>'SSS 4 soru',
        'home_faq_4_answer'=>'SSS 4 yanıt',
        'home_faq_5_question'=>'SSS 5 soru',
        'home_faq_5_answer'=>'SSS 5 yanıt',
        'home_support_title'=>'Canlı destek başlığı',
        'home_support_subtitle'=>'Canlı destek açıklaması',
        'home_support_phone'=>'Canlı destek telefon numarası',
        'home_support_whatsapp'=>'Canlı destek WhatsApp numarası',
        'home_host_title'=>'Villa sahipleri tanıtım başlığı',
        'home_host_subtitle'=>'Villa sahipleri tanıtım açıklaması',
        'home_host_cta'=>'Villa sahipleri buton metni',
        'home_latest_eyebrow'=>'Son eklenenler üst etiketi',
        'home_latest_link'=>'Son eklenenler bağlantı metni',
        'home_concepts_eyebrow'=>'Konseptler üst etiketi',
        'home_concepts_count_label'=>'Konsept kartı villa sayısı etiketi',
        'home_collections_eyebrow'=>'Koleksiyonlar üst etiketi',
        'home_collection_count_suffix'=>'Koleksiyon villa sayısı açıklaması',
        'home_collection_link'=>'Koleksiyon bağlantı metni',
        'home_regions_eyebrow'=>'Bölgeler üst etiketi',
        'home_reviews_eyebrow'=>'Yorumlar üst etiketi',
        'home_review_verified_label'=>'Yorum doğrulama etiketi',
        'home_faq_eyebrow'=>'SSS üst etiketi',
        'home_support_eyebrow'=>'Destek üst etiketi',
        'home_support_whatsapp_label'=>'WhatsApp buton metni',
        'home_footer_regions_title'=>'Footer bölgeler başlığı',
        'home_footer_categories_title'=>'Footer konseptler başlığı',
        'home_footer_contact_title'=>'Footer iletişim başlığı',
        'home_guest_unit'=>'Misafir birimi ana sayfa',
        'home_bedroom_unit'=>'Yatak odası birimi ana sayfa',
        'home_night_unit'=>'Gece fiyat birimi',
        'home_empty_photo'=>'Görsel bulunamadı metni'
    ];
    ThemeManager::renderTemplate('templates/pages/admin/content.php', get_defined_vars());
});

$router->post('/admin/content', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    verify_csrf();
    $allowed = ['site_name','home_eyebrow','home_title','home_subtitle','home_search_placeholder','featured_title','featured_subtitle','empty_properties','search_button','guest_select_label','guest_unit','all_categories','property_about','property_reserve','property_no_images','booking_notice','footer_text','home_latest_title','home_latest_subtitle','home_trust_1_title','home_trust_1_text','home_trust_2_title','home_trust_2_text','home_trust_3_title','home_trust_3_text','home_trust_4_title','home_trust_4_text','home_concepts_title','home_concepts_subtitle','home_collections_title','home_collections_subtitle','home_regions_title','home_regions_subtitle','home_reviews_title','home_reviews_subtitle','home_faq_title','home_faq_subtitle','home_faq_1_question','home_faq_1_answer','home_faq_2_question','home_faq_2_answer','home_faq_3_question','home_faq_3_answer','home_faq_4_question','home_faq_4_answer','home_faq_5_question','home_faq_5_answer','home_support_title','home_support_subtitle','home_support_phone','home_support_whatsapp','home_host_title','home_host_subtitle','home_host_cta','home_latest_eyebrow','home_latest_link','home_concepts_eyebrow','home_concepts_count_label','home_collections_eyebrow','home_collection_count_suffix','home_collection_link','home_regions_eyebrow','home_reviews_eyebrow','home_review_verified_label','home_faq_eyebrow','home_support_eyebrow','home_support_whatsapp_label','home_footer_regions_title','home_footer_categories_title','home_footer_contact_title','home_guest_unit','home_bedroom_unit','home_night_unit','home_empty_photo'];
    $stmt = Database::connection()->prepare('INSERT INTO site_content (content_key,language_code,content_value,updated_at) VALUES (?,\'tr\',?,NOW()) ON DUPLICATE KEY UPDATE content_value=VALUES(content_value),updated_at=NOW()');
    foreach ((array)($_POST['content'] ?? []) as $key=>$value) {
        if (in_array($key,$allowed,true)) $stmt->execute([$key,trim((string)$value)]);
    }
    redirect('/admin/content');
});


$router->get('/admin/bookings', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    $pdo=Database::connection();$status=(string)($_GET['status']??'');
    $allowed=['pending','confirmed','cancelled','completed'];
    $sql="SELECT b.*,p.title,COALESCE(NULLIF(b.guest_name,''),u.username,'Misafir') AS guest_name,COALESCE(NULLIF(b.guest_email,''),u.email,'') AS guest_email,COALESCE(b.guest_phone,'') AS guest_phone FROM bookings b JOIN properties p ON p.id=b.property_id LEFT JOIN users u ON u.id=b.guest_user_id";
    if(in_array($status,$allowed,true)){$sql.=' WHERE b.status=?';$s=$pdo->prepare($sql.' ORDER BY b.created_at DESC LIMIT 250');$s->execute([$status]);$bookings=$s->fetchAll();}
    else{$bookings=$pdo->query($sql.' ORDER BY b.created_at DESC LIMIT 250')->fetchAll();}
    $bookingProperties=$pdo->query("SELECT id,title,max_guests FROM properties WHERE status<>'archived' ORDER BY title")->fetchAll();
    $bookingFilter=in_array($status,$allowed,true)?$status:'';
    ThemeManager::renderTemplate('templates/pages/admin/bookings.php', get_defined_vars());
});
$router->post('/admin/bookings/manual', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();
    $pdo=Database::connection();$propertyId=(int)($_POST['property_id']??0);
    $guestName=trim((string)($_POST['guest_name']??''));$guestEmail=trim((string)($_POST['guest_email']??''));$guestPhone=trim((string)($_POST['guest_phone']??''));$source=(string)($_POST['booking_source']??'other');if(!in_array($source,['phone','whatsapp','agency','walk_in','other'],true))$source='other';
    $inRaw=trim((string)($_POST['check_in']??''));$outRaw=trim((string)($_POST['check_out']??''));$guests=(int)($_POST['guests']??1);
    $checkIn=DateTimeImmutable::createFromFormat('!Y-m-d',$inRaw);$checkOut=DateTimeImmutable::createFromFormat('!Y-m-d',$outRaw);
    $totalInput=trim((string)($_POST['total_amount']??''));
    if($propertyId<1||$guestName===''||strlen($guestName)>760||strlen($guestEmail)>760||strlen($guestPhone)>160||!$checkIn||!$checkOut||$checkIn->format('Y-m-d')!==$inRaw||$checkOut->format('Y-m-d')!==$outRaw||$checkIn<new DateTimeImmutable('today')||$checkOut<=$checkIn||(int)$checkIn->diff($checkOut)->days>365||($guestEmail!==''&&!filter_var($guestEmail,FILTER_VALIDATE_EMAIL))||($totalInput!==''&&(!is_numeric($totalInput)||(float)$totalInput<0||!is_finite((float)$totalInput)))){http_response_code(422);exit('Misafir, tutar ve tarih bilgilerini kontrol edin.');}
    try{
        $pdo->beginTransaction();
        $lock=$pdo->prepare("SELECT id,base_nightly_price,weekend_nightly_price,cleaning_fee,currency,max_guests FROM properties WHERE id=? AND status<>'archived' FOR UPDATE");
        $lock->execute([$propertyId]);$property=$lock->fetch();
        if(!$property||$guests<1||$guests>(int)$property['max_guests']){$pdo->rollBack();http_response_code(422);exit('Konaklama veya misafir kapasitesi geçersiz.');}
        $conflict=$pdo->prepare("SELECT id FROM bookings WHERE property_id=? AND status='confirmed' AND check_in < ? AND check_out > ? LIMIT 1");
        $conflict->execute([$propertyId,$outRaw,$inRaw]);
        if($conflict->fetchColumn()){$pdo->rollBack();http_response_code(409);exit('Bu tarihlerde başka bir rezervasyon bulunuyor.');}
        $calendar=$pdo->prepare("SELECT date_from,date_to,status,nightly_price FROM availability WHERE property_id=? AND date_from < ? AND date_to > ?");
        $calendar->execute([$propertyId,$outRaw,$inRaw]);$rules=$calendar->fetchAll();$subtotal=0.0;
        for($night=$checkIn;$night<$checkOut;$night=$night->modify('+1 day')){
            $day=$night->format('Y-m-d');$rate=(float)$property['base_nightly_price'];
            if((int)$night->format('N')>=6&&(float)$property['weekend_nightly_price']>0)$rate=(float)$property['weekend_nightly_price'];
            foreach($rules as $rule){if($day>=$rule['date_from']&&$day<$rule['date_to']){if($rule['status']!=='available'){$pdo->rollBack();http_response_code(409);exit('Seçilen tarihlerden biri takvimde kapalı.');}if($rule['nightly_price']!==null)$rate=(float)$rule['nightly_price'];}}
            $subtotal+=$rate;
        }
        $fees=(float)$property['cleaning_fee'];$calculated=round($subtotal+$fees,2);
        $entered=$totalInput;$total=$entered===''?$calculated:(float)$entered;
        if($total<0||!is_finite($total)){$pdo->rollBack();http_response_code(422);exit('Toplam tutar geçersiz.');}
        $code='VRM'.date('ymd').strtoupper(bin2hex(random_bytes(3)));
        $save=$pdo->prepare("INSERT INTO bookings (booking_code,property_id,guest_user_id,guest_name,guest_email,guest_phone,booking_source,check_in,check_out,guests,subtotal,fees,total,currency,status,created_at) VALUES(?,?,NULL,?,?,?,?,?,?,?,?,?,?,?,'confirmed',NOW())");
        $save->execute([$code,$propertyId,$guestName,$guestEmail?:null,$guestPhone?:null,$source,$inRaw,$outRaw,$guests,$subtotal,$fees,$total,$property['currency']]);
        $bookingId=(int)$pdo->lastInsertId();
        $financial=$pdo->prepare("INSERT INTO booking_financials(booking_id,gross_amount,platform_fee,vendor_net_amount,currency,payout_status,created_at) VALUES(?,?,0,?,?, 'unpaid',NOW())");
        $financial->execute([$bookingId,$total,$total,$property['currency']]);
        $audit=$pdo->prepare('INSERT INTO audit_logs(user_id,action,entity_type,entity_id,ip_address,details,created_at) VALUES(?,?,?,?,?,?,NOW())');
        $audit->execute([(int)Auth::user()['id'],'booking.created_manual','booking',$bookingId,$_SERVER['REMOTE_ADDR']??null,json_encode(['property_id'=>$propertyId,'guest_name'=>$guestName,'check_in'=>$inRaw,'check_out'=>$outRaw,'source'=>$source],JSON_UNESCAPED_UNICODE)]);
        $pdo->commit();redirect('/admin/bookings');
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Manual booking creation failed: '.$e->getMessage());http_response_code(500);exit('Manuel rezervasyon kaydedilemedi.');}
});
$router->post('/admin/bookings/status', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();
    $pdo=Database::connection();$id=(int)($_POST['id']??0);$status=(string)($_POST['status']??'');
    if($id<1||!in_array($status,['pending','confirmed','cancelled','completed'],true)){http_response_code(422);exit('Rezervasyon durumu geçersiz.');}
    try{
        $pdo->beginTransaction();

        // Determine the property first, then always lock property before booking.
        // This consistent lock order serializes all writes for a property and
        // avoids booking-row/property-row lock inversions between admin actions.
        $lookup=$pdo->prepare('SELECT property_id FROM bookings WHERE id=?');
        $lookup->execute([$id]);$propertyId=(int)($lookup->fetchColumn()?:0);
        if($propertyId<1){$pdo->rollBack();http_response_code(404);exit('Rezervasyon bulunamadı.');}

        $propertyLock=$pdo->prepare('SELECT id FROM properties WHERE id=? FOR UPDATE');
        $propertyLock->execute([$propertyId]);
        if(!$propertyLock->fetchColumn()){$pdo->rollBack();http_response_code(404);exit('Konaklama bulunamadı.');}

        $s=$pdo->prepare('SELECT property_id,guest_user_id,check_in,check_out,status FROM bookings WHERE id=? FOR UPDATE');
        $s->execute([$id]);$booking=$s->fetch();
        if(!$booking){$pdo->rollBack();http_response_code(404);exit('Rezervasyon bulunamadı.');}
        if((int)$booking['property_id']!==$propertyId){$pdo->rollBack();http_response_code(409);exit('Rezervasyon kaydı işlem sırasında değişti. Lütfen tekrar deneyin.');}

        if($status==='confirmed'){
            // Locking reads see the latest committed rows after waiting for the
            // property lock; a snapshot read could otherwise miss a concurrent change.
            $conflict=$pdo->prepare("SELECT id FROM bookings WHERE property_id=? AND id<>? AND status='confirmed' AND check_in < ? AND check_out > ? LIMIT 1 FOR UPDATE");
            $conflict->execute([$propertyId,$id,$booking['check_out'],$booking['check_in']]);
            if($conflict->fetchColumn()){$pdo->rollBack();http_response_code(409);exit('Bu tarihlerde bekleyen veya onaylanmış başka bir rezervasyon var.');}

            $blocked=$pdo->prepare("SELECT id FROM availability WHERE property_id=? AND status<>'available' AND date_from < ? AND date_to > ? LIMIT 1 FOR UPDATE");
            $blocked->execute([$propertyId,$booking['check_out'],$booking['check_in']]);
            if($blocked->fetchColumn()){$pdo->rollBack();http_response_code(409);exit('Bu rezervasyon tarihleri takvimde kapatılmış.');}
        }

        $update=$pdo->prepare('UPDATE bookings SET status=? WHERE id=?');$update->execute([$status,$id]);
        $statusTitle=['pending'=>'Rezervasyon talebiniz beklemede','confirmed'=>'Rezervasyonunuz onaylandı','cancelled'=>'Rezervasyonunuz iptal edildi','completed'=>'Konaklamanız tamamlandı'][$status];
        if(!empty($booking['guest_user_id'])){
            $notice=$pdo->prepare('INSERT INTO notifications(user_id,notification_type,title,body,created_at) VALUES(?,?,?,?,NOW())');
            $notice->execute([(int)$booking['guest_user_id'],'booking.status_changed',$statusTitle,'Rezervasyon durumunuz güncellendi. Rezervasyon kodunuzu hesabınızdan görüntüleyebilirsiniz.']);
        }
        $log=$pdo->prepare('INSERT INTO audit_logs(user_id,action,entity_type,entity_id,ip_address,details,created_at) VALUES(?,?,?,?,?,?,NOW())');
        $log->execute([(int)Auth::user()['id'],'booking.status_changed','booking',$id,$_SERVER['REMOTE_ADDR']??null,json_encode(['from'=>$booking['status'],'to'=>$status],JSON_UNESCAPED_UNICODE)]);
        $pdo->commit();redirect('/admin/bookings');
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('Booking status update failed: '.$e->getMessage());http_response_code(500);exit('Rezervasyon güncellenemedi.');}
});

$router->get('/admin/payment-tests', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    $simulationResult = null;
    ThemeManager::renderTemplate('templates/pages/admin/payment-tests.php', get_defined_vars());
});
$router->post('/admin/payment-tests', static function (): void {
    Auth::requireRole(['super_admin','admin']);verify_csrf();
    $provider=(string)($_POST['provider']??'');
    $scenario=(string)($_POST['scenario']??'success');
    try {
        $simulationResult=\App\Core\PaymentSandboxSimulator::run($provider,$scenario);
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        $simulationResult=['error'=>$e->getMessage(),'real_charge'=>false,'network_requests'=>0];
    }
    ThemeManager::renderTemplate('templates/pages/admin/payment-tests.php', get_defined_vars());
});

$router->get('/admin/finance', static function (): void {
    Auth::requireRole(['super_admin','admin']);
    $pdo = Database::connection();

    // Keep totals separated by currency; never combine unlike currencies.
    $financialSummary = $pdo->query("SELECT currency, COUNT(*) AS booking_count,
        COALESCE(SUM(gross_amount),0) AS gross_amount,
        COALESCE(SUM(platform_fee),0) AS platform_fee,
        COALESCE(SUM(vendor_net_amount),0) AS vendor_net_amount
        FROM booking_financials GROUP BY currency ORDER BY currency")->fetchAll();

    $payoutSummary = $pdo->query("SELECT currency, status, COUNT(*) AS payout_count,
        COALESCE(SUM(amount),0) AS payout_amount
        FROM vendor_payouts GROUP BY currency,status ORDER BY currency,status")->fetchAll();

    $recentFinancials = $pdo->query("SELECT bf.booking_id,bf.gross_amount,bf.platform_fee,
        bf.vendor_net_amount,bf.currency,bf.payout_status,b.created_at,b.booking_code,
        b.status AS booking_status,b.check_in,b.check_out,p.title AS property_title
        FROM booking_financials bf
        JOIN bookings b ON b.id=bf.booking_id
        JOIN properties p ON p.id=b.property_id
        ORDER BY b.created_at DESC,b.id DESC LIMIT 30")->fetchAll();

    $recentTransactions = $pdo->query("SELECT pt.id,pt.provider,pt.provider_reference,pt.amount,
        pt.currency,pt.status,pt.paid_at,pt.created_at,b.booking_code,p.title AS property_title
        FROM payment_transactions pt
        JOIN bookings b ON b.id=pt.booking_id
        JOIN properties p ON p.id=b.property_id
        ORDER BY pt.created_at DESC,pt.id DESC LIMIT 20")->fetchAll();

    $paymentProvider = (string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='payments.provider'")->fetchColumn() ?: 'none');
    $paymentsEnabled = (string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='payments.enabled'")->fetchColumn() ?: '0') === '1';

    ThemeManager::renderTemplate('templates/pages/admin/finance.php', get_defined_vars());
});

$router->get('/admin', static function (): void {
    Auth::requireRole(['super_admin', 'admin']);
    $pdo = Database::connection();
    $mode = (string)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn() ?: 'single_vendor');
    $metrics = [
        'properties' => (int)$pdo->query('SELECT COUNT(*) FROM properties')->fetchColumn(),
        'published_properties' => (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE status='published'")->fetchColumn(),
        'bookings' => (int)$pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),
        'users' => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'pending' => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='pending'")->fetchColumn(),
        'confirmed' => (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn(),
        'categories' => (int)$pdo->query('SELECT COUNT(*) FROM property_categories')->fetchColumn(),
        'locations' => (int)$pdo->query('SELECT COUNT(*) FROM locations')->fetchColumn(),
        'pending_reviews' => (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE status='pending'")->fetchColumn(),
    ];
    $recentBookings = $pdo->query("SELECT b.booking_code,b.check_in,b.check_out,b.total,b.currency,b.status,b.created_at,p.title AS property_title,COALESCE(NULLIF(b.guest_name,''),u.username,'Misafir') AS guest_name
        FROM bookings b JOIN properties p ON p.id=b.property_id LEFT JOIN users u ON u.id=b.guest_user_id
        ORDER BY b.created_at DESC LIMIT 6")->fetchAll();
    ThemeManager::renderTemplate('templates/pages/admin/dashboard.php', get_defined_vars());
});

require_once __DIR__ . '/admin-property-location-routes.php';
