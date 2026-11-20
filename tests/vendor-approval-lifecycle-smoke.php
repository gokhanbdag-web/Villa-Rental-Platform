<?php
declare(strict_types=1);
$r=(string)file_get_contents(dirname(__DIR__).'/routes/web.php');
$v=(string)file_get_contents(dirname(__DIR__).'/themes/default/templates/pages/admin/vendors.php');
foreach(["/admin/vendors/reject","status='rejected'","status='draft'","status IN ('pending','rejected')","FOR UPDATE"] as $needle){
 if(!str_contains($r,$needle))throw new RuntimeException('Missing vendor approval lifecycle guard: '.$needle);
}
if(!str_contains($v,'action="/admin/vendors/reject"'))throw new RuntimeException('Missing vendor rejection action');
echo "Vendor approval lifecycle checks passed.\n";
