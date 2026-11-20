<?php
declare(strict_types=1);
$r=(string)file_get_contents(dirname(__DIR__).'/routes/web.php');
$start=strpos($r,"\$router->post('/vendor/properties/availability/save'");
$end=strpos($r,"\n});",$start);$block=substr($r,$start,$end-$start);
foreach(['$pdo->beginTransaction()','FOR UPDATE','$pdo->commit()','$pdo->rollBack()'] as $needle){if(!str_contains($block,$needle))throw new RuntimeException('Missing availability lock: '.$needle);}
if(strpos($block,'FOR UPDATE')>strpos($block,"status='confirmed'"))throw new RuntimeException('Property lock must precede booking conflict check');
echo "Vendor calendar transaction checks passed.\n";
