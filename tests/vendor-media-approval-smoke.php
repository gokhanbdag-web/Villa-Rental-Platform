<?php
declare(strict_types=1);
$routes=(string)file_get_contents(dirname(__DIR__).'/routes/web.php');
foreach(['/vendor/properties/media/manage','/vendor/properties/media/delete'] as $route){
 $start=strpos($routes,"\$router->post('".$route."'");
 if($start===false)throw new RuntimeException('Missing '.$route);
 $end=strpos($routes,"\n});",$start);
 $block=substr($routes,$start,$end-$start);
 if(!str_contains($block,'canManageVendorBookings')||!str_contains($block,'vendor_id=?'))throw new RuntimeException('Missing approval or ownership guard '.$route);
}
echo "Vendor media approval checks passed.\n";
