<?php
declare(strict_types=1);

require_once dirname(__DIR__).'/app/bootstrap.php';

function iyzicoProductionAssert(bool $ok,string $message):void { if(!$ok)throw new RuntimeException($message); }
$routes=(string)file_get_contents(dirname(__DIR__).'/routes/web.php');
$client=(string)file_get_contents(dirname(__DIR__).'/app/Core/IyzicoApiClient.php');
$settings=(string)file_get_contents(dirname(__DIR__).'/themes/default/templates/pages/admin/settings.php');
iyzicoProductionAssert(str_contains($routes,"'api.iyzipay.com','cpp.iyzipay.com'"),'Production hosted payment hosts are not allow-listed.');
iyzicoProductionAssert(str_contains($routes,"in_array((\$iyzicoConfig['environment']??''),['sandbox','production'],true)"),'Checkout availability is not environment-aware.');
iyzicoProductionAssert(str_contains($routes,"'environment'=>\$paymentEnvironment")&&str_contains($routes,'PaymentCheckoutGuard::reserveIyzico'),'New transactions do not persist their selected iyzico environment through the atomic checkout reservation.');
iyzicoProductionAssert(str_contains($routes,"\$config['environment']=\$storedEnvironment"),'Callbacks do not retrieve payments from their original environment.');
iyzicoProductionAssert(!str_contains($routes,"\$config['environment']='sandbox'"),'A callback forcibly switches all transactions to Sandbox.');
iyzicoProductionAssert(str_contains($client,"return self::post(\$config, '/payment/bin/check'")&&str_contains($client,"], \$environment);"),'Connection test does not use the selected environment.');
iyzicoProductionAssert(str_contains($settings,'Production API')&&str_contains($settings,'Test the selected iyzico environment'),'Admin settings do not explain the Production connection test.');
iyzicoProductionAssert(method_exists(\App\Core\IyzicoApiClient::class,'testConnection'),'Environment-aware connection test method missing.');
echo "iyzico environment-aware checkout smoke tests passed. No payment or live charge was created.\n";
