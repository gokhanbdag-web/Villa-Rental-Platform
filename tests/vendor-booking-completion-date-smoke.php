<?php
declare(strict_types=1);
$r=(string)file_get_contents(dirname(__DIR__).'/routes/web.php');
$start=strpos($r,"\$router->post('/vendor/bookings/status'");
$end=strpos($r,"\n});",$start);$block=substr($r,$start,$end-$start);
foreach(["\$status==='completed'","\$booking['check_out']","new DateTimeImmutable('today')","http_response_code(409)"] as $needle){
 if(!str_contains($block,$needle))throw new RuntimeException('Missing completion date guard: '.$needle);
}
echo "Vendor booking completion date checks passed.\n";
