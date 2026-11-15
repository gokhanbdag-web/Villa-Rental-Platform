<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$routes=(string)file_get_contents($root.'/routes/web.php');
$menu=(string)file_get_contents($root.'/themes/default/templates/pages/admin/navigation.php');

$checks=[
    'blog archive is registered as a system menu page'=>str_contains($routes,"['label'=>'Blog Arşivi','url'=>'/blog']"),
    'system page options are rendered in menu selector'=>str_contains($menu,'$systemPages'),
    'selected system page fills the URL field'=>str_contains($menu,"url.value=picker.value"),
    'blog archive points to canonical blog route'=>str_contains($routes,"'url'=>'/blog'"),
];
$failed=[];
foreach($checks as $name=>$ok){echo ($ok?'PASS':'FAIL').' '.$name.PHP_EOL;if(!$ok)$failed[]=$name;}
if($failed){fwrite(STDERR,'Failed: '.implode(', ',$failed).PHP_EOL);exit(1);}
echo 'All blog archive menu checks passed.'.PHP_EOL;
