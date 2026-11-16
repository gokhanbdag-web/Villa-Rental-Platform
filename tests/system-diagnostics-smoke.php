<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$routes=file_get_contents($root.'/routes/web.php');
$bootstrap=file_get_contents($root.'/app/bootstrap.php');
$page=file_get_contents($root.'/themes/default/templates/pages/admin/performance.php');
$version=trim((string)file_get_contents($root.'/VERSION'));
$checks=[
 'super-admin diagnostics menu opens the HTML dashboard'=>str_contains($routes,"['label'=>'Sistem Tanılama','url'=>'/admin/performance','icon'=>'⌁']"),
 'dashboard route is separate from the JSON data endpoint'=>str_contains($routes,"$"."router->get('/admin/performance',")&&str_contains($routes,"$"."router->get('/admin/performance/diagnostics',"),
 'dashboard is protected for super admins'=>preg_match("~\$router->get\('/admin/performance',\s*static function \(\): void \{\s*Auth::requireRole\(\['super_admin'\]\);~",$routes)===1,
 'dashboard uses the theme admin template renderer'=>str_contains($routes,"templates/pages/admin/performance.php"),
 'dashboard parses JSON log lines into readable request and SQL tables'=>str_contains($page,'JSON.parse(row)')&&str_contains($page,'Yavaş HTTP istekleri')&&str_contains($page,'Yavaş SQL sorguları'),
 'dashboard provides summary metrics, filters and expandable raw details'=>str_contains($page,'requestCount')&&str_contains($page,'requestFilter')&&str_contains($page,'Ham kaydı göster'),
 'dashboard fetch URL respects subdirectory installation'=>str_contains($page,"app_url('/admin/performance/diagnostics')"),
 'successful video byte-range transfers are excluded from slow app request logs'=>str_contains($bootstrap,'successful MP4/WebM byte-range response')&&str_contains($bootstrap,'in_array($status, [200, 206, 304], true)'),
 'application version is bumped to 1.5.2'=>$version==='1.5.2',
 'changelog documents diagnostics dashboard and media timing changes'=>str_contains(file_get_contents($root.'/CHANGELOG.md'),'1.5.2')&&str_contains(file_get_contents($root.'/CHANGELOG.md'),'byte-range delivery time'),
];
$failed=[];
foreach($checks as $label=>$passed){echo ($passed?'[PASS] ':'[FAIL] ').$label.PHP_EOL;if(!$passed)$failed[]=$label;}
if($failed){fwrite(STDERR,count($failed).' diagnostics smoke check(s) failed.'.PHP_EOL);exit(1);}
echo 'All system diagnostics smoke checks passed.'.PHP_EOL;
