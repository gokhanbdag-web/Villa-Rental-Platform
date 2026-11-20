<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$ui=(string)file_get_contents($root.'/install/index.php');
$installer=(string)file_get_contents($root.'/app/Core/Installer.php');
$checks=[
 'English installer document'=>str_contains($ui,'<html lang="en">'),
 'English setup title'=>str_contains($ui,'Initial setup wizard'),
 'English installation action'=>str_contains($ui,'Install now'),
 'English installer default locale'=>str_contains($installer,"'app.locale' => 'en'"),
 'English password validation'=>str_contains($installer,'Passwords do not match.'),
];
foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)exit(1);}
