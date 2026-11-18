<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';

function netgsmAssert(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
netgsmAssert(\App\Core\NetgsmApiClient::normalizePhone('05301234567')==='5301234567','Turkish leading-zero phone normalization failed.');
netgsmAssert(\App\Core\NetgsmApiClient::normalizePhone('+905301234567')==='5301234567','Turkish +90 phone normalization failed.');
netgsmAssert(\App\Core\NetgsmApiClient::normalizePhone('905301234567')==='5301234567','Turkish country-code phone normalization failed.');
netgsmAssert(\App\Core\NetgsmApiClient::normalizePhone('+421123456789')==='00421123456789','International phone normalization failed.');
$invalid=false;try{\App\Core\NetgsmApiClient::normalizePhone('123');}catch(RuntimeException){$invalid=true;}
netgsmAssert($invalid,'Invalid SMS phone number was accepted.');

$client=(string)file_get_contents(dirname(__DIR__).'/app/Core/NetgsmApiClient.php');
$service=(string)file_get_contents(dirname(__DIR__).'/app/Core/SmsService.php');
$routes=(string)file_get_contents(dirname(__DIR__).'/routes/web.php');
$settings=(string)file_get_contents(dirname(__DIR__).'/themes/default/templates/pages/admin/settings.php');
$migration=(string)file_get_contents(dirname(__DIR__).'/database/migrations/054_netgsm_sms_notifications.sql');
$workflow=(string)file_get_contents(dirname(__DIR__).'/.github/workflows/php-lint.yml');

netgsmAssert(str_contains($client,'https://api.netgsm.com.tr/sms/rest/v2/send'),'Official Netgsm REST v2 endpoint is missing.');
netgsmAssert(str_contains($client,'CURLOPT_HTTPAUTH=>CURLAUTH_BASIC')&&str_contains($client,'CURLOPT_USERPWD=>'),'Netgsm HTTP Basic Authentication is missing.');
netgsmAssert(str_contains($client,'CURLOPT_SSL_VERIFYPEER=>true')&&str_contains($client,'CURLOPT_FOLLOWLOCATION=>false'),'Netgsm HTTPS transport protections are missing.');
netgsmAssert(str_contains($client,"'msgheader'=>")&&str_contains($client,"'messages'=>")&&str_contains($client,"'iysfilter'=>"),'Netgsm JSON payload or IYS filter is missing.');
netgsmAssert(str_contains($client,"code")&&str_contains($client,"jobid"),'Netgsm provider response validation is missing.');
netgsmAssert(str_contains($service,'INSERT IGNORE INTO sms_queue')&&str_contains($service,'event_key')&&str_contains($service,'attempts>=5'),'SMS queue idempotency or retry policy is missing.');
netgsmAssert(str_contains($service,'PaymentSecretVault::decrypt'),'SMS credentials are not read from encrypted secret storage.');
netgsmAssert(str_contains($routes,"/tasks/sms/process")&&str_contains($routes,'SmsService::sendTest')&&str_contains($routes,'SmsService::enqueueBookingStatus'),'SMS cron, test send or booking notifications are missing.');
netgsmAssert(str_contains($routes,'sms.netgsm.password')&&str_contains($routes,'sms.cron_token'),'SMS settings are not persisted securely.');
netgsmAssert(str_contains($settings,'Gerçek test SMS')&&str_contains($settings,'sms_netgsm_sender')&&str_contains($settings,'smsCronUrl'),'SMS settings and explicit paid test-send UI are missing.');
netgsmAssert(str_contains($migration,'CREATE TABLE IF NOT EXISTS sms_queue')&&str_contains($migration,"('sms.enabled','0',1)")&&str_contains($migration,"'sms.booking.confirmed'"),'SMS queue schema, safe default or localized booking templates are missing.');
netgsmAssert(str_contains($workflow,'tests/netgsm-sms-smoke.php'),'Netgsm regression smoke test is not registered in CI.');
echo "Netgsm SMS adapter, phone normalization, secure settings and queue checks passed. No real SMS was sent.\n";
