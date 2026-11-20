<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$routes=(string)file_get_contents($root.'/routes/web.php');
$page=(string)file_get_contents($root.'/themes/default/templates/pages/admin/updates.php');
$checker=(string)file_get_contents($root.'/app/Core/VendorUpdateManifest.php');
$checks=[
    'GitHub update polling removed'=>!str_contains($routes,'villa_github_update_info'),
    'Vendor update service wired'=>str_contains($routes,'VendorUpdateManifest::check'),
    'GitHub UI removed'=>!str_contains(strtolower($page),'github'),
    'HTTPS vendor manifest'=>str_contains($checker,'https://kodvadisi.com.tr/updates/villa-rental/manifest.json'),
    'No unsafe installation action'=>!str_contains($routes,"->post('/admin/updates/install'"),
];
foreach($checks as $label=>$ok){echo ($ok?'[PASS] ':'[FAIL] ').$label.PHP_EOL;if(!$ok)exit(1);}
