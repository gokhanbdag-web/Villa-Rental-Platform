<?php
declare(strict_types=1);

// Invoked against the disposable installation created by installer-smoke.php.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/bootstrap.php';
use App\Core\Auth;
use App\Core\Database;
use App\Core\HomepageDesign;
use App\Core\Router;
use App\Core\Translator;

if (in_array($argv[1]??'',['english-admin','turkish-admin','english-listing'],true)) {
    if ($argv[1]!=='english-listing' && !Auth::attempt('smoke_admin','SmokeTestPassword!2026')) throw new RuntimeException('Fixture login failed.');
    if ($argv[1]==='turkish-admin') {
        Database::connection()->exec("INSERT INTO languages(code,name,is_default,is_active) VALUES('tr','Türkçe',0,1) ON DUPLICATE KEY UPDATE is_active=1");
        $_SESSION['villa_locale']='tr';
        $_SESSION['villa_language_preference_version']=1;
    } else {
        $_SESSION['villa_locale']='tr'; // A session from before the default-language upgrade.
    }
    $_SERVER['REQUEST_METHOD']='GET';
    $_SERVER['REQUEST_URI']=$argv[1]==='english-listing'?'/konaklamalar':'/admin/pages';
    require APP_ROOT.'/index.php';
    exit;
}
$router=new Router();
require APP_ROOT.'/routes/web.php';
$locale=(string)Database::connection()->query("SELECT code FROM languages WHERE is_default=1")->fetchColumn();
Translator::setLocale($locale);
$request=$argv[1]??'';
if ($request==='public') {
    $router->dispatch('GET','/');
    exit;
}
if (!Auth::attempt('smoke_admin','SmokeTestPassword!2026')) throw new RuntimeException('Fixture login failed.');
if ($request==='editor') {
    $_GET=['page'=>'home','lang'=>$locale];
    $router->dispatch('GET','/admin/pages/layout');
    exit;
}
$design=['hero'=>['home_title'=>'Villa editor <test>','visible'=>true,'order'=>12],'banner'=>['visible'=>false]];
if (!in_array($request,['save','preview','reset'],true)) throw new RuntimeException('Unknown fixture request.');
$_POST=['_csrf'=>csrf_token(),'language_code'=>$locale,'design_key'=>HomepageDesign::key(villa_selected_homepage(Database::connection())),'design'=>json_encode($request==='reset'?[]:$design)];
$router->dispatch('POST',$request==='preview'?'/homepage-design-preview':'/admin/pages/home-design');
