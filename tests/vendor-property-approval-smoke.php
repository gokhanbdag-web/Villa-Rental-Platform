<?php
declare(strict_types=1);
$routes=(string)file_get_contents(dirname(__DIR__).'/routes/web.php');
foreach(["Villa ekleme ve düzenleme için ev sahibi hesabınızın onaylanması gerekiyor.","/vendor/properties/new","$"+"v->fetchColumn()!=='approved'"] as $needle){
 if(!str_contains($routes,$needle))throw new RuntimeException('Missing host approval guard: '.$needle);
}
echo "Vendor property approval checks passed.\n";
