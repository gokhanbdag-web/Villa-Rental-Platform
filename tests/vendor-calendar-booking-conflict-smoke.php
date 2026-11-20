<?php
declare(strict_types=1);
$r=(string)file_get_contents(dirname(__DIR__).'/routes/web.php');
$start=strpos($r,"\$router->post('/vendor/properties/availability/save'");
$end=strpos($r,"\n});",$start);$block=substr($r,$start,$end-$start);
foreach(["status='confirmed'","check_in < ? AND check_out > ?","http_response_code(409)","is_numeric(\$price)","is_finite((float)\$price)"] as $needle){
 if(!str_contains($block,$needle))throw new RuntimeException('Missing calendar conflict guard: '.$needle);
}
echo "Vendor calendar booking conflict checks passed.\n";
