<?php
declare(strict_types=1);
$r=(string)file_get_contents(dirname(__DIR__).'/routes/web.php');
foreach(["\$router->get('/vendor/properties',","\$router->get('/vendor/properties/{id}',","\$router->post('/vendor/properties/delete',"] as $needle){
 $start=strpos($r,$needle);if($start===false)throw new RuntimeException('Missing route '.$needle);
 $end=strpos($r,"\n});",$start);$block=substr($r,$start,$end-$start);
 if(!str_contains($block,'canManageVendorBookings')||!str_contains($block,'vendor_id=?'))throw new RuntimeException('Missing approval or ownership guard '.$needle);
}
echo "Vendor property access checks passed.\n";
