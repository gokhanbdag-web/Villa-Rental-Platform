<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$routes=file_get_contents($root.'/routes/web.php');
$bootstrap=file_get_contents($root.'/app/bootstrap.php');
$page=file_get_contents($root.'/themes/default/templates/pages/admin/performance.php');
$version=trim((string)file_get_contents($root.'/VERSION'));
$dashboardRoutePosition=strpos($routes,"$"."router->get('/admin/performance',");
$dashboardRoute=$dashboardRoutePosition===false?'':substr($routes,$dashboardRoutePosition,300);
$checks=[
 'super-admin diagnostics menu opens the HTML dashboard'=>str_contains($routes,"['label'=>'Sistem Tanılama','url'=>'/admin/performance','icon'=>'⌁']"),
 'dashboard route is separate from the JSON data endpoint'=>str_contains($routes,"$"."router->get('/admin/performance',")&&str_contains($routes,"$"."router->get('/admin/performance/data',")&&str_contains($routes,"$"."router->get('/admin/performance/diagnostics',"),
 'dashboard is protected for super admins'=>str_contains($dashboardRoute,"Auth::requireRole(['super_admin']);"),
 'dashboard uses the theme admin template renderer'=>str_contains($routes,"templates/pages/admin/performance.php"),
 'legacy diagnostics URL redirects to the styled dashboard'=>str_contains($routes,"redirect('/admin/performance');"),
 'admin shell includes the diagnostics navigation item'=>str_contains(file_get_contents($root.'/themes/default/layouts/admin-shell.php'),"'/admin/performance' => 'Sistem Tanılama'"),
 'page title header survives the admin shell content extraction'=>str_contains($page,'<div class="head">'),
 'diagnostics API suppresses historical successful video transfer rows'=>str_contains($routes,"preg_match('~/(?:brand-assets)/[^/]+\\\\.(?:mp4|webm)$~i")&&str_contains($routes,'in_array($status, [200, 206, 304], true)')&&str_contains($routes,'$performance'),
 'dashboard parses JSON log lines into readable request and SQL tables'=>str_contains($page,'JSON.parse(row)')&&str_contains($page,'Yavaş HTTP istekleri')&&str_contains($page,'Yavaş SQL sorguları'),
 'dashboard provides summary metrics, filters and expandable raw details'=>str_contains($page,'requestCount')&&str_contains($page,'requestFilter')&&str_contains($page,'Ham kaydı göster'),
 'dashboard fetch URL respects subdirectory installation'=>str_contains($page,"app_url('/admin/performance/data')"),
 'successful video byte-range transfers are excluded from slow app request logs'=>str_contains($bootstrap,'successful MP4/WebM byte-range response')&&str_contains($bootstrap,'in_array($status, [200, 206, 304], true)'),
 'cleanup endpoint is restricted to super admins and verifies CSRF'=>str_contains($routes,"$"."router->post('/admin/performance/cleanup'")&&str_contains($routes,"Auth::requireRole(['super_admin']);")&&str_contains($routes,'verify_csrf();'),
 'cleanup supports HTTP, SQL and all diagnostic logs'=>str_contains($routes,"['performance', 'slow_sql', 'all']")&&str_contains("['performance', 'slow_sql', 'all']","all"),
 'cleanup keeps the requested newest lines and truncates logs in place'=>str_contains($routes,'$keepLast')&&str_contains($routes,'flock($handle, LOCK_EX)')&&str_contains($routes,'ftruncate($handle, 0)'),
 'dashboard exposes cleanup controls with confirmation and CSRF token'=>str_contains($page,'cleanupScope')&&str_contains($page,'cleanupKeep')&&str_contains($page,'window.confirm')&&str_contains($page,'csrfToken'),
 'dashboard displays log counts and cleanup results'=>str_contains($page,'log_counts')&&str_contains($page,'cleanupResult'),
 'application version is bumped to 1.5.4'=>$version==='1.5.4',
 'changelog documents diagnostics dashboard and media timing changes'=>str_contains(file_get_contents($root.'/CHANGELOG.md'),'1.5.2')&&str_contains(file_get_contents($root.'/CHANGELOG.md'),'byte-range delivery time'),
 'changelog documents safe diagnostics log cleanup'=>str_contains(file_get_contents($root.'/CHANGELOG.md'),'1.5.4')&&str_contains(file_get_contents($root.'/CHANGELOG.md'),'cleanup controls'),
];
$failed=[];
foreach($checks as $label=>$passed){echo ($passed?'[PASS] ':'[FAIL] ').$label.PHP_EOL;if(!$passed)$failed[]=$label;}
if($failed){fwrite(STDERR,count($failed).' diagnostics smoke check(s) failed.'.PHP_EOL);exit(1);}
echo 'All system diagnostics smoke checks passed.'.PHP_EOL;
