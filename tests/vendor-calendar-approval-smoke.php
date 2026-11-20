<?php
declare(strict_types=1);
$routes=(string)file_get_contents(dirname(__DIR__).'/routes/web.php');
if(str_contains($routes,"trim((string)("."\$_POST['nightly_price']??''),(string)"))throw new RuntimeException('Broken calendar nightly price parser');
foreach(['/vendor/properties/availability/save','/vendor/properties/availability/delete'] as $route){
 $start=strpos($routes,"\$router->post('".$route."'");
 if($start===false)throw new RuntimeException('Missing route '.$route);
 $block=substr($routes,$start,1100);
 if(!str_contains($block,'canManageVendorBookings')||!str_contains($block,'vendor_id=?'))throw new RuntimeException('Missing host ownership or approval guard: '.$route);
}
echo "Vendor calendar approval and pricing checks passed.\n";
