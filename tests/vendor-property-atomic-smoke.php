<?php
declare(strict_types=1);
$r=(string)file_get_contents(dirname(__DIR__).'/routes/web.php');
$start=strpos($r,"\$router->post('/vendor/properties/save'");
$end=strpos($r,"\n});",$start);
$b=substr($r,$start,$end-$start);
foreach(['$latitudeRaw=','villa_resolve_location($pdo)','$pdo->beginTransaction()','$pdo->commit()','$pdo->rollBack()'] as $n){if(!str_contains($b,$n))throw new RuntimeException('Missing '.$n);}
if(strpos($b,'$latitudeRaw=')>strpos($b,"INSERT INTO properties"))throw new RuntimeException('Coordinate validation must precede inserts');
if(strpos($b,'villa_resolve_location($pdo)')>strpos($b,"INSERT INTO properties"))throw new RuntimeException('Location resolution must precede inserts');
echo "Vendor property atomic validation checks passed.\n";
