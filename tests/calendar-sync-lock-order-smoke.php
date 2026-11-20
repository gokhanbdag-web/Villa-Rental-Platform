<?php
declare(strict_types=1);
$source=(string)file_get_contents(dirname(__DIR__).'/app/Core/CalendarSyncService.php');
$start=strpos($source,'public static function applyEvents(');
$end=strpos($source,'public static function cronSync(',$start);
if($start===false||$end===false)throw new RuntimeException('Missing sync implementation');
$block=substr($source,$start,$end-$start);
$begin=strpos($block,'$pdo->beginTransaction()');
$property=strpos($block,"$"."propertyLock->execute");
$feed=strpos($block,"$"."feedStmt->execute");
if($begin===false||$property===false||$feed===false||!($begin<$property&&$property<$feed))throw new RuntimeException('Expected property-first transaction lock order');
if(!str_contains($block,'AND property_id=? AND direction=')||!str_contains($block,"if(!$"."feed) throw new RuntimeException"))throw new RuntimeException('Feed association must be revalidated after property lock');
echo "Calendar feed lock order checks passed.\n";
