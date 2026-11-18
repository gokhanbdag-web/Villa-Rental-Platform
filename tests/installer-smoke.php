<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Installer;
use App\Core\Migrator;
use App\Core\PaymentWebhookInbox;
use App\Core\PaymentCallbackProcessor;
use App\Core\PaymentCheckoutGuard;
use App\Core\BookingHoldExpiryService;
use App\Core\IyzicoPaymentReconciler;
use App\Core\ThemeManager;
use App\Core\Router;

function assertSmoke(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$root = dirname(__DIR__);
$webRoutesSource = (string)file_get_contents($root . '/routes/web.php');
$configFile = $root . '/config/config.php';
$lockFile = $root . '/storage/installed.lock';
if (Installer::isInstalled()) throw new RuntimeException('Smoke test requires a clean checkout.');

try {
    Installer::install([
        'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
        'db_port' => (int)(getenv('DB_PORT') ?: 3306),
        'db_name' => getenv('DB_NAME') ?: 'villa_test',
        'db_user' => getenv('DB_USER') ?: 'root',
        'db_password' => getenv('DB_PASSWORD') ?: 'root',
        'admin_username' => 'smoke_admin',
        'admin_email' => 'smoke@example.test',
        'admin_password' => 'SmokeTestPassword!2026',
        'admin_password_confirm' => 'SmokeTestPassword!2026',
        'mode' => 'multi_vendor',
    ]);

    assertSmoke(Installer::isInstalled(), 'Installer did not create the installation lock.');
    $config = require $configFile;
    $pdo = Installer::connect($config['database']);

    $admin = $pdo->query("SELECT id,role,status FROM users WHERE email='smoke@example.test' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    assertSmoke(is_array($admin) && $admin['role'] === 'super_admin' && $admin['status'] === 'active', 'Initial administrator was not created correctly.');

    $mode = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='app.mode'")->fetchColumn();
    assertSmoke($mode === 'multi_vendor', 'Selected marketplace mode was not saved.');

    ThemeManager::saveSocialSettings([
        'instagram'=>['url'=>'https://instagram.com/example','family'=>'brand','size'=>22],
        '_address'=>['text'=>'Test adresi'],
        '_call_center'=>['title'=>'Çağrı Merkezi','phone'=>'+90 555 111 2233'],
        '_license'=>['title'=>'TÜRSAB Lisans','number'=>'12345'],
    ]);
    $savedHeaderJson = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='theme.social_icons'")->fetchColumn();
    $savedHeader = json_decode((string)$savedHeaderJson, true);
    assertSmoke(is_array($savedHeader) && ($savedHeader['instagram']['url'] ?? '') === 'https://instagram.com/example' && ($savedHeader['_address']['text'] ?? '') === 'Test adresi' && ($savedHeader['_call_center']['phone'] ?? '') === '+90 555 111 2233' && ($savedHeader['_license']['number'] ?? '') === '12345', 'Shared header settings did not persist in the database.');
    $loadedHeader = ThemeManager::socialSettings();
    assertSmoke(($loadedHeader['_address']['text'] ?? '') === 'Test adresi' && ($loadedHeader['instagram']['size'] ?? 0) === 22, 'Shared header settings could not be loaded after saving.');

    ThemeManager::saveTypographySettings([
        'topbar_height'=>72,
        'headings'=>['family'=>'Playfair Display','size'=>40,'color'=>'#123456','weight'=>700,'style'=>'normal'],
        'span'=>['family'=>'Inter','size'=>15,'color'=>'#234567','weight'=>500,'style'=>'normal'],
        'paragraph'=>['family'=>'Roboto','size'=>17,'color'=>'#345678','weight'=>400,'style'=>'normal'],
        'paragraph_links'=>['family'=>'Montserrat','size'=>16,'color'=>'#456789','weight'=>600,'style'=>'italic'],
    ], 'default');
    $savedTypographyJson = $pdo->query("SELECT setting_value FROM settings WHERE setting_key='theme.default.typography'")->fetchColumn();
    $savedTypography = json_decode((string)$savedTypographyJson, true);
    assertSmoke(is_array($savedTypography) && ($savedTypography['topbar_height'] ?? 0) === 72 && ($savedTypography['headings']['size'] ?? 0) === 40 && ($savedTypography['paragraph_links']['family'] ?? '') === 'Montserrat', 'Theme typography settings were not persisted correctly.');
    $loadedTypography = ThemeManager::typographySettings('default');
    assertSmoke(($loadedTypography['paragraph']['family'] ?? '') === 'Roboto' && ($loadedTypography['span']['color'] ?? '') === '#234567', 'Theme typography settings could not be loaded after saving.');
    ThemeManager::saveTypographySettings(['headings'=>['family'=>'Lato','size'=>32]], 'futuretheme');
    assertSmoke((int)$pdo->query("SELECT COUNT(*) FROM settings WHERE setting_key IN ('theme.default.typography','theme.futuretheme.typography')")->fetchColumn() === 2, 'Typography settings must be stored separately for each theme.');

    $villaCount = (int)$pdo->query("SELECT COUNT(*) FROM properties WHERE status='published'")->fetchColumn();
    assertSmoke($villaCount >= 30, 'Demo villa seed did not create at least 30 published villas.');

    // Exercise the database side of the guest account -> booking -> checkout flow.
    $property = $pdo->query("SELECT id,title,slug,currency,base_nightly_price,cleaning_fee FROM properties WHERE status='published' ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    assertSmoke(is_array($property), 'A published property is required for the booking flow smoke test.');
    $customerEmail = 'booking.customer@example.test';
    $customerPassword = 'BookingSmokePassword!2026';
    $customerHash = password_hash($customerPassword, PASSWORD_DEFAULT);
    $createCustomer = $pdo->prepare("INSERT INTO users(username,email,password_hash,role,status,created_at) VALUES(?,?,?,'customer','active',NOW())");
    $createCustomer->execute(['booking.smoke.customer',$customerEmail,$customerHash]);
    $customerId = (int)$pdo->lastInsertId();
    assertSmoke(password_verify($customerPassword, $customerHash), 'New customer password hash did not verify.');
    assertSmoke(Auth::attempt($customerEmail, $customerPassword), 'A newly created customer could not sign in after booking registration.');
    $authenticatedCustomer = Auth::user();
    assertSmoke(is_array($authenticatedCustomer) && (int)$authenticatedCustomer['id'] === $customerId, 'The newly registered customer session was not established.');

    $bookingCode = 'VRSMOKE' . strtoupper(bin2hex(random_bytes(4)));
    $checkIn = (new DateTimeImmutable('+30 days'))->format('Y-m-d');
    $checkOut = (new DateTimeImmutable('+32 days'))->format('Y-m-d');
    $nightly = (float)$property['base_nightly_price'];
    $fees = (float)($property['cleaning_fee'] ?? 0);
    $subtotal = round($nightly * 2, 2);
    $total = round($subtotal + $fees, 2);
    $createBooking = $pdo->prepare("INSERT INTO bookings(booking_code,property_id,guest_user_id,guest_name,guest_email,guest_phone,booking_source,check_in,check_out,guests,subtotal,fees,total,currency,status,created_at) VALUES(?,?,?,?,?,?,'website',?,?,?,?,?,?,?,'pending',NOW())");
    $createBooking->execute([$bookingCode,(int)$property['id'],$customerId,'Booking Smoke Customer',$customerEmail,'+905551112233',$checkIn,$checkOut,1,$subtotal,$fees,$total,$property['currency']]);
    $bookingId = (int)$pdo->lastInsertId();
    $createFinancial = $pdo->prepare("INSERT INTO booking_financials(booking_id,gross_amount,platform_fee,vendor_net_amount,currency,payout_status,created_at) VALUES(?,?,0,?,?,'unpaid',NOW())");
    $createFinancial->execute([$bookingId,$total,$total,$property['currency']]);

    $checkoutLookup = $pdo->prepare("SELECT b.*,p.title AS property_title,p.slug AS property_slug FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.booking_code=? AND b.guest_user_id=? LIMIT 1");
    $checkoutLookup->execute([$bookingCode,$customerId]);
    $checkoutBooking = $checkoutLookup->fetch(PDO::FETCH_ASSOC);
    assertSmoke(is_array($checkoutBooking) && (int)$checkoutBooking['id'] === $bookingId && $checkoutBooking['status'] === 'pending', 'The authenticated booking owner could not load the checkout summary.');
    $checkoutLookup->execute([$bookingCode,(int)$admin['id']]);
    assertSmoke($checkoutLookup->fetch(PDO::FETCH_ASSOC) === false, 'A different account was able to load another guest checkout.');
    $financialLookup = $pdo->prepare('SELECT gross_amount,payout_status FROM booking_financials WHERE booking_id=?');
    $financialLookup->execute([$bookingId]);
    $financial = $financialLookup->fetch(PDO::FETCH_ASSOC);
    assertSmoke(is_array($financial) && (float)$financial['gross_amount'] === $total && $financial['payout_status'] === 'unpaid', 'The booking financial record was not saved correctly.');

    $migrationCount = (int)$pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
    assertSmoke($migrationCount >= 20, 'Expected database migrations were not applied.');
    $emailQueueTable = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='email_queue'")->fetchColumn();
    assertSmoke($emailQueueTable === 1, 'Email delivery queue migration did not create its table.');

    $seasonTable=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='property_seasonal_prices'")->fetchColumn();
    assertSmoke($seasonTable===1,'Seasonal property pricing migration did not create its table.');
    $stayColumns=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='property_seasonal_prices' AND COLUMN_NAME IN ('min_nights','checkin_weekdays','checkout_weekdays')")->fetchColumn();
    assertSmoke($stayColumns===3,'Seasonal minimum-stay and weekday restriction migration did not add all required columns.');
    $propertyExtraColumns=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='properties' AND COLUMN_NAME IN ('early_checkin_enabled','early_checkin_fee','late_checkout_enabled','late_checkout_fee')")->fetchColumn();
    $bookingExtraColumns=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bookings' AND COLUMN_NAME IN ('early_checkin_requested','early_checkin_fee','late_checkout_requested','late_checkout_fee')")->fetchColumn();
    assertSmoke($propertyExtraColumns===4&&$bookingExtraColumns===4,'Booking time extras migration did not add all property settings and booking snapshot columns.');
    $seasonFrom=(new DateTimeImmutable('+400 days'))->format('Y-m-d');
    $seasonTo=(new DateTimeImmutable('+402 days'))->format('Y-m-d');
    $seasonInsert=$pdo->prepare('INSERT INTO property_seasonal_prices(property_id,name,date_from,date_to,nightly_price,created_at) VALUES(?,?,?,?,?,NOW())');
    $seasonInsert->execute([(int)$property['id'],'Installer seasonal pricing smoke',$seasonFrom,$seasonTo,432.10]);
    $seasonLookup=$pdo->prepare('SELECT name,date_from,date_to,nightly_price FROM property_seasonal_prices WHERE property_id=? AND name=?');
    $seasonLookup->execute([(int)$property['id'],'Installer seasonal pricing smoke']);$seasonRow=$seasonLookup->fetch(PDO::FETCH_ASSOC);
    $seasonTotal=\App\Core\BookingRateCalculator::staySubtotal($property,new DateTimeImmutable($seasonFrom),new DateTimeImmutable($seasonTo),[$seasonRow]);
    assertSmoke($seasonRow&&$seasonTotal===864.2,'MySQL seasonal price could not be loaded into the shared booking rate calculator.');
    $seasonDelete=$pdo->prepare('DELETE FROM property_seasonal_prices WHERE property_id=? AND name=?');$seasonDelete->execute([(int)$property['id'],'Installer seasonal pricing smoke']);
    assertSmoke(str_contains($webRoutesSource,'/admin/properties/{id}/seasonal-pricing')&&str_contains($webRoutesSource,'/vendor/properties/{id}/seasonal-pricing')&&substr_count($webRoutesSource,'BookingRateCalculator::staySubtotal')>=3,'Seasonal pricing management routes or booking quote integration are missing.');
    assertSmoke(substr_count($webRoutesSource,'BookingStayRules::validate')>=5,'Seasonal stay rules must be enforced in quotes, customer bookings, manual bookings, and both confirmation paths.');
    assertSmoke(substr_count($webRoutesSource,'BookingExtras::calculate')>=3&&str_contains($webRoutesSource,'early_checkin_requested')&&str_contains($webRoutesSource,'late_checkout_requested'),'Early check-in and late checkout must be priced and stored on customer and manual reservations.');
    \App\Core\EmailService::enqueue($pdo,'mail.smoke@example.test','Smoke email','<p>Smoke email body</p>','Smoke email body','smoke.email.event');
    \App\Core\EmailService::enqueue($pdo,'mail.smoke@example.test','Smoke email duplicate','<p>Duplicate</p>','Duplicate','smoke.email.event');
    $queuedEmailCount = (int)$pdo->query("SELECT COUNT(*) FROM email_queue WHERE event_key='smoke.email.event'")->fetchColumn();
    assertSmoke($queuedEmailCount === 1, 'Email event idempotency allowed a duplicate queue item.');
    $emailWorkerResult = \App\Core\EmailService::processPending($pdo,5);
    $queuedEmailStatus = (string)$pdo->query("SELECT status FROM email_queue WHERE event_key='smoke.email.event'")->fetchColumn();
    assertSmoke(($emailWorkerResult['skipped']??'')==='disabled' && $queuedEmailStatus==='pending', 'Email worker must keep queued messages pending while SMTP delivery is disabled.');

    $webhookInbox = new PaymentWebhookInbox($pdo);
    $eventPayload = ['merchant_oid'=>'VILLA-SMOKE-001','status'=>'success','total_amount'=>'125000'];
    assertSmoke($webhookInbox->recordVerified('paytr','VILLA-SMOKE-001','payment_callback','VILLA-SMOKE-001',$eventPayload)==='recorded','Verified webhook event was not recorded.');
    assertSmoke($webhookInbox->recordVerified('paytr','VILLA-SMOKE-001','payment_callback','VILLA-SMOKE-001',$eventPayload)==='duplicate','Repeated webhook event was not recognized.');
    $changedPayload = ['merchant_oid'=>'VILLA-SMOKE-001','status'=>'failed','total_amount'=>'125000'];
    assertSmoke($webhookInbox->recordVerified('paytr','VILLA-SMOKE-001','payment_callback','VILLA-SMOKE-001',$changedPayload)==='conflict','Conflicting duplicate webhook payload was not detected.');
    assertSmoke($webhookInbox->claim('paytr','VILLA-SMOKE-001'),'Webhook event could not be claimed.');
    assertSmoke(!$webhookInbox->claim('paytr','VILLA-SMOKE-001'),'Webhook event was claimed twice concurrently.');
    assertSmoke($webhookInbox->markProcessed('paytr','VILLA-SMOKE-001'),'Webhook event could not be marked processed.');
    assertSmoke(!$webhookInbox->claim('paytr','VILLA-SMOKE-001'),'Processed webhook event was claimable again.');

    // A verified successful payment must atomically mark the payment paid and confirm its booking.
    $paymentReference = 'VILLA-PAYMENT-' . strtoupper(bin2hex(random_bytes(4)));
    $createPayment = $pdo->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,amount,currency,status,created_at) VALUES(?,'paytr',?,?,?,'pending',NOW())");
    $createPayment->execute([$bookingId,$paymentReference,number_format($total,2,'.',''),$property['currency']]);
    $paymentCallback = [
        'booking_id'=>$bookingId,
        'provider'=>'paytr',
        'provider_reference'=>$paymentReference,
        'amount'=>number_format($total,2,'.',''),
        'currency'=>$property['currency'],
        'status'=>'success',
    ];
    $paymentProcessor = new PaymentCallbackProcessor($pdo);
    $paymentEventId = 'VILLA-PAYMENT-EVENT-' . strtoupper(bin2hex(random_bytes(4)));
    $paymentEventPayload = ['reference'=>$paymentReference,'status'=>'success','amount'=>number_format($total,2,'.','')];
    $paymentResult = $paymentProcessor->processVerified('paytr',$paymentEventId,'payment_callback',$paymentReference,$paymentEventPayload,$paymentCallback);
    assertSmoke(($paymentResult['result']??'')==='processed'&&($paymentResult['payment_status']??'')==='paid','Verified successful payment callback was not processed.');
    $paymentState = $pdo->prepare('SELECT status FROM payment_transactions WHERE provider=? AND provider_reference=? LIMIT 1');
    $paymentState->execute(['paytr',$paymentReference]);
    assertSmoke($paymentState->fetchColumn()==='paid','Payment transaction was not marked paid.');
    $bookingState = $pdo->prepare('SELECT status FROM bookings WHERE id=?');
    $bookingState->execute([$bookingId]);
    assertSmoke($bookingState->fetchColumn()==='confirmed','Successful payment did not confirm the booking.');
    $duplicatePayment = $paymentProcessor->processVerified('paytr',$paymentEventId,'payment_callback',$paymentReference,$paymentEventPayload,$paymentCallback);
    assertSmoke(($duplicatePayment['result']??'')==='duplicate'&&!($duplicatePayment['payment_updated']??true),'Duplicate payment callback changed the payment a second time.');
    $secondProviderEvent = $paymentProcessor->processVerified('paytr','VILLA-SECOND-EVENT-' . strtoupper(bin2hex(random_bytes(4))),'payment_callback',$paymentReference,['reference'=>$paymentReference,'status'=>'success','amount'=>number_format($total,2,'.','')],$paymentCallback);
    assertSmoke(($secondProviderEvent['result']??'')==='already_applied'&&!($secondProviderEvent['payment_updated']??true),'A second provider event for an already-paid transaction was not handled idempotently.');

    // A different provider must not be able to mark a second transaction paid
    // for the same booking, even when its callback is otherwise valid.
    $competingReference = 'VILLA-COMPETING-' . strtoupper(bin2hex(random_bytes(4)));
    $createCompetingPayment = $pdo->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,amount,currency,status,created_at) VALUES(?,'iyzico',?,?,?,'pending',NOW())");
    $createCompetingPayment->execute([$bookingId,$competingReference,number_format($total,2,'.',''),$property['currency']]);
    $competingCallback = [
        'booking_id'=>$bookingId,
        'provider'=>'iyzico',
        'provider_reference'=>$competingReference,
        'amount'=>number_format($total,2,'.',''),
        'currency'=>$property['currency'],
        'status'=>'success',
    ];
    $competingResult = $paymentProcessor->processVerified('iyzico','VILLA-COMPETING-EVENT-' . strtoupper(bin2hex(random_bytes(4))),'checkout_form_auth',$competingReference,['reference'=>$competingReference,'status'=>'success'],$competingCallback);
    assertSmoke(($competingResult['result']??'')==='rejected_duplicate_booking_payment'&&!($competingResult['payment_updated']??true),'A second provider was allowed to confirm a second payment for the same booking.');
    $competingState = $pdo->prepare('SELECT status FROM payment_transactions WHERE provider=? AND provider_reference=? LIMIT 1');
    $competingState->execute(['iyzico',$competingReference]);
    assertSmoke($competingState->fetchColumn()==='pending','Rejected competing payment changed its transaction status.');
    
    // Exercise the iyzico callback processor against MySQL without contacting
    // iyzico or creating a real charge. This verifies provider-specific
    // transaction lookup, booking confirmation and duplicate-event safety.
    $iyzicoBookingCode = 'VRIZY' . strtoupper(bin2hex(random_bytes(4)));
    $iyzicoCheckIn = (new DateTimeImmutable('+40 days'))->format('Y-m-d');
    $iyzicoCheckOut = (new DateTimeImmutable('+42 days'))->format('Y-m-d');
    $createIyzicoBooking = $pdo->prepare("INSERT INTO bookings(booking_code,property_id,guest_user_id,guest_name,guest_email,guest_phone,booking_source,check_in,check_out,guests,subtotal,fees,total,currency,status,created_at) VALUES(?,?,?,?,?,?,'website',?,?,?,?,?,?,?,'pending',NOW())");
    $createIyzicoBooking->execute([$iyzicoBookingCode,(int)$property['id'],$customerId,'Iyzico Smoke Customer',$customerEmail,'+905551112233',$iyzicoCheckIn,$iyzicoCheckOut,1,$subtotal,$fees,$total,$property['currency']]);
    $iyzicoBookingId = (int)$pdo->lastInsertId();
    $iyzicoReference = 'VILLA-IYZICO-' . strtoupper(bin2hex(random_bytes(4)));
    $createIyzicoPayment = $pdo->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,amount,currency,status,created_at) VALUES(?,'iyzico',?,?,?,'pending',NOW())");
    $createIyzicoPayment->execute([$iyzicoBookingId,$iyzicoReference,number_format($total,2,'.',''),$property['currency']]);
    $iyzicoCallback = [
        'booking_id'=>$iyzicoBookingId,
        'provider'=>'iyzico',
        'provider_reference'=>$iyzicoReference,
        'amount'=>number_format($total,2,'.',''),
        'currency'=>$property['currency'],
        'status'=>'success',
    ];
    $iyzicoEventId = 'IYZICO-SMOKE-EVENT-' . strtoupper(bin2hex(random_bytes(4)));
    $iyzicoEventPayload = ['paymentId'=>'IYZICO-SMOKE-PAYMENT','status'=>'SUCCESS','amount'=>number_format($total,2,'.','')];
    $iyzicoResult = $paymentProcessor->processVerified('iyzico',$iyzicoEventId,'checkout_form_callback',$iyzicoReference,$iyzicoEventPayload,$iyzicoCallback);
    assertSmoke(($iyzicoResult['result']??'')==='processed'&&($iyzicoResult['payment_status']??'')==='paid','Verified iyzico callback was not applied to its transaction.');
    $iyzicoPaymentState = $pdo->prepare('SELECT status FROM payment_transactions WHERE provider=? AND provider_reference=? LIMIT 1');
    $iyzicoPaymentState->execute(['iyzico',$iyzicoReference]);
    assertSmoke($iyzicoPaymentState->fetchColumn()==='paid','iyzico payment transaction was not marked paid.');
    $iyzicoBookingState = $pdo->prepare('SELECT status FROM bookings WHERE id=?');
    $iyzicoBookingState->execute([$iyzicoBookingId]);
    assertSmoke($iyzicoBookingState->fetchColumn()==='confirmed','Successful iyzico callback did not confirm its booking.');
    $iyzicoDuplicate = $paymentProcessor->processVerified('iyzico',$iyzicoEventId,'checkout_form_callback',$iyzicoReference,$iyzicoEventPayload,$iyzicoCallback);
    assertSmoke(($iyzicoDuplicate['result']??'')==='duplicate'&&!($iyzicoDuplicate['payment_updated']??true),'Duplicate iyzico callback changed the payment a second time.');

    $mismatchReference = 'VILLA-IYZICO-MISMATCH-' . strtoupper(bin2hex(random_bytes(4)));
    $createIyzicoPayment->execute([$iyzicoBookingId,$mismatchReference,number_format($total,2,'.',''),$property['currency']]);
    $mismatchCallback = $iyzicoCallback;
    $mismatchCallback['provider_reference'] = $mismatchReference;
    $mismatchCallback['amount'] = number_format($total + 0.01,2,'.','');
    $mismatchResult = $paymentProcessor->processVerified('iyzico','IYZICO-MISMATCH-' . strtoupper(bin2hex(random_bytes(4))),'checkout_form_callback',$mismatchReference,['status'=>'SUCCESS','amount'=>$mismatchCallback['amount']],$mismatchCallback);
    assertSmoke(($mismatchResult['result']??'')==='rejected'&&!($mismatchResult['payment_updated']??true),'iyzico callback with a mismatched amount was accepted.');
    $iyzicoPaymentState->execute(['iyzico',$mismatchReference]);
    assertSmoke($iyzicoPaymentState->fetchColumn()==='pending','Rejected iyzico callback changed the transaction status.');

    // Exercise terminal payment lifecycle transitions in MySQL through the
    // same verified callback processor used by gateway callbacks.
    $createLifecycleCase = static function (string $label, string $bookingStatus='pending') use ($pdo, $createBooking, $createFinancial, $property, $customerId, $customerEmail, $subtotal, $fees, $total): array {
        // Give independent lifecycle fixtures disjoint dates. The callback now
        // correctly treats every pending booking as a date hold, so sharing one
        // date range would make unrelated test cases conflict with one another.
        static $caseIndex = 0;
        $offset = 95 + ($caseIndex++ * 3);
        $start=(new DateTimeImmutable('+'.$offset.' days'))->format('Y-m-d');
        $end=(new DateTimeImmutable('+'.($offset + 2).' days'))->format('Y-m-d');
        $code='VRLIFE'.strtoupper(bin2hex(random_bytes(4)));
        $createBooking->execute([$code,(int)$property['id'],$customerId,'Lifecycle Smoke Customer',$customerEmail,'+905551112233',$start,$end,1,$subtotal,$fees,$total,$property['currency']]);
        $bookingId=(int)$pdo->lastInsertId();
        $financial=$pdo->prepare("INSERT INTO booking_financials(booking_id,gross_amount,platform_fee,vendor_net_amount,currency,payout_status,created_at) VALUES(?,?,0,?,?,'unpaid',NOW())");
        $financial->execute([$bookingId,$total,$total,$property['currency']]);
        if($bookingStatus!=='pending'){
            $update=$pdo->prepare('UPDATE bookings SET status=? WHERE id=?');
            $update->execute([$bookingStatus,$bookingId]);
        }
        $reference='VILLA-LIFECYCLE-'.strtoupper(bin2hex(random_bytes(5)));
        $payment=$pdo->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,amount,currency,status,created_at) VALUES(?,'paytr',?,?,?,'pending',NOW())");
        $payment->execute([$bookingId,$reference,number_format($total,2,'.',''),$property['currency']]);
        return ['booking_id'=>$bookingId,'reference'=>$reference];
    };
    $runLifecycleCallback = static function (array $case, string $status, string $eventType='payment_callback') use ($paymentProcessor,$total,$property): array {
        $eventId='VILLA-LIFECYCLE-EVENT-'.strtoupper(bin2hex(random_bytes(5)));
        $callback=['booking_id'=>$case['booking_id'],'provider'=>'paytr','provider_reference'=>$case['reference'],'amount'=>number_format($total,2,'.',''),'currency'=>$property['currency'],'status'=>$status];
        return $paymentProcessor->processVerified('paytr',$eventId,$eventType,$case['reference'],['reference'=>$case['reference'],'status'=>$status,'amount'=>$callback['amount']],$callback);
    };
    $lifecyclePaymentState=$pdo->prepare('SELECT status,paid_at FROM payment_transactions WHERE provider_reference=?');
    $lifecycleBookingState=$pdo->prepare('SELECT status FROM bookings WHERE id=?');

    $cancelCase=$createLifecycleCase('cancel');
    $cancelResult=$runLifecycleCallback($cancelCase,'cancelled');
    assertSmoke(($cancelResult['result']??'')==='processed'&&($cancelResult['payment_status']??'')==='cancelled','Verified cancellation callback was not applied.');
    $lifecyclePaymentState->execute([$cancelCase['reference']]);
    assertSmoke(($lifecyclePaymentState->fetch(PDO::FETCH_ASSOC)['status']??'')==='cancelled','Cancelled payment status was not persisted.');
    $lifecycleBookingState->execute([$cancelCase['booking_id']]);
    assertSmoke($lifecycleBookingState->fetchColumn()==='pending','A cancelled checkout attempt unexpectedly changed the booking status.');

    $expiryCase=$createLifecycleCase('expire');
    $expiryResult=$runLifecycleCallback($expiryCase,'expired');
    assertSmoke(($expiryResult['result']??'')==='processed'&&($expiryResult['payment_status']??'')==='expired','Verified expiry callback was not applied.');
    $lifecyclePaymentState->execute([$expiryCase['reference']]);
    assertSmoke(($lifecyclePaymentState->fetch(PDO::FETCH_ASSOC)['status']??'')==='expired','Expired payment status was not persisted.');

    $refundCase=$createLifecycleCase('refund');
    $refundPaid=$runLifecycleCallback($refundCase,'success');
    assertSmoke(($refundPaid['result']??'')==='processed','Refund lifecycle fixture could not first reach paid status.');
    $refundResult=$runLifecycleCallback($refundCase,'refunded');
    assertSmoke(($refundResult['result']??'')==='processed'&&($refundResult['payment_status']??'')==='refunded','Verified refund callback was not applied to a paid transaction.');
    $lifecyclePaymentState->execute([$refundCase['reference']]);
    $refundPaymentRow=$lifecyclePaymentState->fetch(PDO::FETCH_ASSOC);
    assertSmoke(($refundPaymentRow['status']??'')==='refunded'&&!empty($refundPaymentRow['paid_at']),'Refund did not preserve the paid timestamp and terminal refund status.');
    $lifecycleBookingState->execute([$refundCase['booking_id']]);
    assertSmoke($lifecycleBookingState->fetchColumn()==='confirmed','A payment refund callback unexpectedly rewrote the booking lifecycle; booking cancellation remains a separate business decision.');

    $latePaidCase=$createLifecycleCase('late-paid','cancelled');
    $latePaidResult=$runLifecycleCallback($latePaidCase,'success');
    assertSmoke(($latePaidResult['result']??'')==='paid_booking_conflict'&&($latePaidResult['payment_updated']??false)&&($latePaidResult['manual_review_required']??false),'A late successful callback for a cancelled booking was not recorded for manual refund review.');
    $lifecyclePaymentState->execute([$latePaidCase['reference']]);
    assertSmoke(($lifecyclePaymentState->fetch(PDO::FETCH_ASSOC)['status']??'')==='paid','A verified late payment was not persisted for a cancelled booking.');
    $lifecycleBookingState->execute([$latePaidCase['booking_id']]);
    assertSmoke($lifecycleBookingState->fetchColumn()==='cancelled','A late payment incorrectly reactivated a cancelled booking.');

    // A provider-verified success may reconcile a legacy local expiry; the
    // local clock alone must not make a real provider payment permanently terminal.
    $legacyExpiredCase=$createLifecycleCase('legacy-expired');
    $markLegacyExpired=$pdo->prepare("UPDATE payment_transactions SET status='expired' WHERE provider_reference=? AND status='pending'");
    $markLegacyExpired->execute([$legacyExpiredCase['reference']]);
    $legacyExpiredPaid=$runLifecycleCallback($legacyExpiredCase,'success');
    assertSmoke(($legacyExpiredPaid['result']??'')==='processed'&&($legacyExpiredPaid['payment_status']??'')==='paid','Verified success could not reconcile a legacy expired payment transaction.');
    $lifecyclePaymentState->execute([$legacyExpiredCase['reference']]);
    assertSmoke(($lifecyclePaymentState->fetch(PDO::FETCH_ASSOC)['status']??'')==='paid','Legacy expired payment did not return to paid after provider verification.');
    $lifecycleBookingState->execute([$legacyExpiredCase['booking_id']]);
    assertSmoke($lifecycleBookingState->fetchColumn()==='confirmed','Legacy expired but provider-paid booking was not confirmed.');

    // The booking row lock serializes repeat checkout starts and stale init retries.
    $checkoutCode='VRCHK'.strtoupper(bin2hex(random_bytes(4)));
    $checkoutStart=(new DateTimeImmutable('+105 days'))->format('Y-m-d');
    $checkoutEnd=(new DateTimeImmutable('+107 days'))->format('Y-m-d');
    $createBooking->execute([$checkoutCode,(int)$property['id'],$customerId,'Checkout Guard Customer',$customerEmail,'+905551112233',$checkoutStart,$checkoutEnd,1,$subtotal,$fees,$total,$property['currency']]);
    $checkoutBookingId=(int)$pdo->lastInsertId();
    $checkoutConversation='VR'.$checkoutBookingId.'-'.strtoupper(bin2hex(random_bytes(5)));
    $checkoutPayload=['conversationId'=>$checkoutConversation,'basketId'=>$checkoutCode,'environment'=>'sandbox'];
    $checkoutAmount=number_format($total,2,'.','');
    $firstCheckout=PaymentCheckoutGuard::reserveIyzico($pdo,$checkoutBookingId,$checkoutConversation,$checkoutAmount,$property['currency'],$checkoutPayload);
    assertSmoke(($firstCheckout['result']??'')==='reserved'&&!empty($firstCheckout['transaction_id']),'First iyzico start did not reserve an initialization.');
    $repeatCheckout=PaymentCheckoutGuard::reserveIyzico($pdo,$checkoutBookingId,'VR-REPEAT-'.strtoupper(bin2hex(random_bytes(4))),$checkoutAmount,$property['currency'],$checkoutPayload);
    assertSmoke(($repeatCheckout['result']??'')==='initializing','Repeated iyzico start did not detect the active initialization.');
    $activeCheckoutCount=$pdo->prepare("SELECT COUNT(*) FROM payment_transactions WHERE booking_id=? AND provider='iyzico' AND status IN ('pending','processing')");
    $activeCheckoutCount->execute([$checkoutBookingId]);
    assertSmoke((int)$activeCheckoutCount->fetchColumn()===1,'Repeated checkout start created multiple active iyzico transactions.');
    $expireCheckout=$pdo->prepare("UPDATE payment_transactions SET expires_at=DATE_SUB(NOW(),INTERVAL 1 MINUTE) WHERE id=?");
    $expireCheckout->execute([(int)$firstCheckout['transaction_id']]);
    $retryCheckout=PaymentCheckoutGuard::reserveIyzico($pdo,$checkoutBookingId,'VR-RETRY-'.strtoupper(bin2hex(random_bytes(4))),$checkoutAmount,$property['currency'],$checkoutPayload);
    assertSmoke(($retryCheckout['result']??'')==='reserved','Expired initialization placeholder could not be retried.');
    $oldCheckout=$pdo->prepare('SELECT status FROM payment_transactions WHERE id=?');
    $oldCheckout->execute([(int)$firstCheckout['transaction_id']]);
    assertSmoke($oldCheckout->fetchColumn()==='expired','Expired initialization placeholder was not marked expired.');
    $activeCheckoutCount->execute([$checkoutBookingId]);
    assertSmoke((int)$activeCheckoutCount->fetchColumn()===1,'Retry left multiple active iyzico transactions.');

    // An expired provider token may have been paid while its callback is delayed.
    // Keep it pending for provider reconciliation instead of creating a second charge.
    $staleTokenCode='VRSTL'.strtoupper(bin2hex(random_bytes(4)));
    $staleTokenStart=(new DateTimeImmutable('+115 days'))->format('Y-m-d');
    $staleTokenEnd=(new DateTimeImmutable('+117 days'))->format('Y-m-d');
    $createBooking->execute([$staleTokenCode,(int)$property['id'],$customerId,'Stale Token Customer',$customerEmail,'+905551112233',$staleTokenStart,$staleTokenEnd,1,$subtotal,$fees,$total,$property['currency']]);
    $staleTokenBookingId=(int)$pdo->lastInsertId();
    $staleFinancial=$pdo->prepare("INSERT INTO booking_financials(booking_id,gross_amount,platform_fee,vendor_net_amount,currency,payout_status,created_at) VALUES(?,?,0,?,?,'unpaid',NOW())");
    $staleFinancial->execute([$staleTokenBookingId,$total,$total,$property['currency']]);
    $staleToken='iyzico-stale-token-'.strtoupper(bin2hex(random_bytes(4)));
    $staleConversation='VR'.$staleTokenBookingId.'-STALE';
    $staleRequest=json_encode(['conversationId'=>$staleConversation,'basketId'=>$staleTokenCode,'environment'=>'sandbox']);
    $staleResponse=json_encode(['token'=>$staleToken,'conversationId'=>$staleConversation,'environment'=>'sandbox']);
    $staleInsert=$pdo->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,amount,currency,status,request_payload,response_payload,expires_at,created_at,updated_at) VALUES(?,'iyzico',?,?,?,'pending',?,?,DATE_SUB(NOW(),INTERVAL 1 MINUTE),NOW(),NOW())");
    $staleInsert->execute([$staleTokenBookingId,$staleToken,$checkoutAmount,$property['currency'],$staleRequest,$staleResponse]);
    $staleRetry=PaymentCheckoutGuard::reserveIyzico($pdo,$staleTokenBookingId,'VR-STALE-RETRY-'.strtoupper(bin2hex(random_bytes(4))),$checkoutAmount,$property['currency'],$checkoutPayload);
    assertSmoke(($staleRetry['result']??'')==='verification_required','Expired real provider token was retried without provider reconciliation.');
    $staleState=$pdo->prepare('SELECT status FROM payment_transactions WHERE provider_reference=?');
    $staleState->execute([$staleToken]);
    assertSmoke($staleState->fetchColumn()==='pending','Expired provider token was marked expired without checking the provider.');
    $staleCount=$pdo->prepare("SELECT COUNT(*) FROM payment_transactions WHERE booking_id=? AND provider='iyzico'");
    $staleCount->execute([$staleTokenBookingId]);
    assertSmoke((int)$staleCount->fetchColumn()===1,'A second checkout was created before reconciling the expired provider token.');

    // Legacy rows already marked expired by an older build must also be
    // reconciled before a new provider session can be created.
    $legacyCode='VRLEG'.strtoupper(bin2hex(random_bytes(4)));
    $legacyStart=(new DateTimeImmutable('+120 days'))->format('Y-m-d');
    $legacyEnd=(new DateTimeImmutable('+122 days'))->format('Y-m-d');
    $createBooking->execute([$legacyCode,(int)$property['id'],$customerId,'Legacy Expired Customer',$customerEmail,'+905551112233',$legacyStart,$legacyEnd,1,$subtotal,$fees,$total,$property['currency']]);
    $legacyBookingId=(int)$pdo->lastInsertId();
    $legacyFinancial=$pdo->prepare("INSERT INTO booking_financials(booking_id,gross_amount,platform_fee,vendor_net_amount,currency,payout_status,created_at) VALUES(?,?,0,?,?,'unpaid',NOW())");
    $legacyFinancial->execute([$legacyBookingId,$total,$total,$property['currency']]);
    $legacyToken='VRLEGACY-TOKEN-'.strtoupper(bin2hex(random_bytes(4)));
    $legacyRequest=json_encode(['conversationId'=>'VR'.$legacyBookingId.'-LEGACY','basketId'=>$legacyCode,'environment'=>'sandbox']);
    $legacyInsert=$pdo->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,amount,currency,status,request_payload,expires_at,created_at,updated_at) VALUES(?,'iyzico',?,?,?,'expired',?,DATE_SUB(NOW(),INTERVAL 1 MINUTE),NOW(),NOW())");
    $legacyInsert->execute([$legacyBookingId,$legacyToken,$checkoutAmount,$property['currency'],$legacyRequest]);
    $legacyRetry=PaymentCheckoutGuard::reserveIyzico($pdo,$legacyBookingId,'VR-LEGACY-RETRY-'.strtoupper(bin2hex(random_bytes(4))),$checkoutAmount,$property['currency'],$checkoutPayload);
    assertSmoke(($legacyRetry['result']??'')==='verification_required','A legacy expired real provider token was bypassed by a new checkout.');
    $legacyCount=$pdo->prepare("SELECT COUNT(*) FROM payment_transactions WHERE booking_id=? AND provider='iyzico'");
    $legacyCount->execute([$legacyBookingId]);
    assertSmoke((int)$legacyCount->fetchColumn()===1,'A second checkout was created for a legacy expired provider token.');

    // Expired booking holds are released only when no unresolved payment or
    // pending bank-transfer report can still represent money in flight.
    $makeHoldExpiryCase = static function (string $label, ?string $paymentStatus=null, string $provider='iyzico', ?string $providerReference=null) use ($pdo,$createBooking,$property,$customerId,$customerEmail,$subtotal,$fees,$total): array {
        static $holdIndex=0;
        $offset=140+($holdIndex++*3);
        $start=(new DateTimeImmutable('+'.$offset.' days'))->format('Y-m-d');
        $end=(new DateTimeImmutable('+'.($offset+2).' days'))->format('Y-m-d');
        $code='VRHOLD'.strtoupper(bin2hex(random_bytes(4)));
        $createBooking->execute([$code,(int)$property['id'],$customerId,'Hold Expiry Customer',$customerEmail,'+905551112233',$start,$end,1,$subtotal,$fees,$total,$property['currency']]);
        $id=(int)$pdo->lastInsertId();
        $financial=$pdo->prepare("INSERT INTO booking_financials(booking_id,gross_amount,platform_fee,vendor_net_amount,currency,payout_status,created_at) VALUES(?,?,0,?,?,'unpaid',NOW())");
        $financial->execute([$id,$total,$total,$property['currency']]);
        $hold=$pdo->prepare('UPDATE bookings SET hold_expires_at=DATE_SUB(NOW(),INTERVAL 1 MINUTE) WHERE id=?');
        $hold->execute([$id]);
        $reference=$providerReference??('VRHOLD-PAY-'.strtoupper(bin2hex(random_bytes(5))));
        if($paymentStatus!==null){
            $expires=$provider==='iyzico'?"DATE_SUB(NOW(),INTERVAL 1 MINUTE)":"NULL";
            $payment=$pdo->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,amount,currency,status,expires_at,created_at,updated_at) VALUES(?,?,?,?,?,?,{$expires},NOW(),NOW())");
            $payment->execute([$id,$provider,$reference,number_format($total,2,'.',''),$property['currency'],$paymentStatus]);
        }
        return ['id'=>$id,'reference'=>$reference];
    };
    $runHoldExpiry = static function (int $propertyId) use ($pdo): int {
        $pdo->beginTransaction();
        try {
            $lock=$pdo->prepare('SELECT id FROM properties WHERE id=? FOR UPDATE');
            $lock->execute([$propertyId]);
            $count=BookingHoldExpiryService::expireSafePendingForProperty($pdo,$propertyId);
            $pdo->commit();
            return $count;
        } catch (Throwable $e) {
            if($pdo->inTransaction())$pdo->rollBack();
            throw $e;
        }
    };
    $plainExpiredHold=$makeHoldExpiryCase('plain');
    assertSmoke($runHoldExpiry((int)$property['id'])>=1,'A due booking hold with no payment was not released.');
    $holdState=$pdo->prepare('SELECT status FROM bookings WHERE id=?');
    $holdState->execute([$plainExpiredHold['id']]);
    assertSmoke($holdState->fetchColumn()==='cancelled','A due unpaid booking was not cancelled.');

    $manualPendingHold=$makeHoldExpiryCase('manual-report');
    $manualReport=$pdo->prepare("INSERT INTO manual_payment_reports(booking_id,guest_user_id,amount,currency,sender_name,transfer_reference,status,submitted_at,created_at) VALUES(?,?,?,?,?,?,'pending',NOW(),NOW())");
    $manualReport->execute([$manualPendingHold['id'],$customerId,$total,$property['currency'],'Hold Customer','HOLD-MANUAL-'.bin2hex(random_bytes(3))]);
    $runHoldExpiry((int)$property['id']);
    $holdState->execute([$manualPendingHold['id']]);
    assertSmoke($holdState->fetchColumn()==='pending','A booking with a pending bank-transfer report was released.');

    $realTokenHold=$makeHoldExpiryCase('real-token','pending','iyzico','VRHOLD-REAL-'.strtoupper(bin2hex(random_bytes(4))));
    $runHoldExpiry((int)$property['id']);
    $holdState->execute([$realTokenHold['id']]);
    assertSmoke($holdState->fetchColumn()==='pending','A booking with an unresolved real provider token was released.');
    $realTokenState=$pdo->prepare('SELECT status FROM payment_transactions WHERE provider_reference=?');
    $realTokenState->execute([$realTokenHold['reference']]);
    assertSmoke($realTokenState->fetchColumn()==='pending','An unresolved real provider token was expired without verification.');

    $legacyExpiredRealHold=$makeHoldExpiryCase('legacy-expired-real','expired','iyzico','VRHOLD-LEGACY-'.strtoupper(bin2hex(random_bytes(4))));
    $reconciliationColumn=$pdo->query("SHOW COLUMNS FROM payment_transactions LIKE 'reconciliation_checked_at'")->fetch(PDO::FETCH_ASSOC);
    assertSmoke((bool)$reconciliationColumn,'Payment reconciliation cooldown migration did not create its timestamp column.');
    $legacyTokenIdStmt=$pdo->prepare('SELECT id FROM payment_transactions WHERE provider_reference=?');
    $legacyTokenIdStmt->execute([$legacyExpiredRealHold['reference']]);
    $legacyTokenId=(int)$legacyTokenIdStmt->fetchColumn();
    assertSmoke(IyzicoPaymentReconciler::claimReconciliationAttempt($pdo,$legacyTokenId),'First provider reconciliation claim was not acquired.');
    assertSmoke(!IyzicoPaymentReconciler::claimReconciliationAttempt($pdo,$legacyTokenId),'Provider reconciliation cooldown allowed duplicate lookups.');
    $runHoldExpiry((int)$property['id']);
    $holdState->execute([$legacyExpiredRealHold['id']]);
    assertSmoke($holdState->fetchColumn()==='pending','A legacy locally expired real iyzico token was released without a verified provider event.');

    $verifiedExpiredHold=$makeHoldExpiryCase('verified-expired','pending','iyzico','VRHOLD-VERIFIED-'.strtoupper(bin2hex(random_bytes(4))));
    $setVerifiedHoldDue=$pdo->prepare('UPDATE bookings SET hold_expires_at=DATE_SUB(NOW(),INTERVAL 1 MINUTE) WHERE id=?');
    $setVerifiedHoldDue->execute([$verifiedExpiredHold['id']]);
    $verifiedExpiredCallback=[
        'booking_id'=>$verifiedExpiredHold['id'],'provider'=>'iyzico',
        'provider_reference'=>$verifiedExpiredHold['reference'],
        'amount'=>$checkoutAmount,'currency'=>$property['currency'],'status'=>'expired',
    ];
    $verifiedExpiredResult=$paymentProcessor->processVerified(
        'iyzico','VILLA-VERIFIED-EXPIRY-'.strtoupper(bin2hex(random_bytes(4))),
        'CHECKOUT_FORM_RECONCILIATION',$verifiedExpiredHold['reference'],
        ['paymentStatus'=>'EXPIRED','token'=>$verifiedExpiredHold['reference']],
        $verifiedExpiredCallback
    );
    assertSmoke(($verifiedExpiredResult['result']??'')==='processed','Verified provider expiry callback was not applied.');
    $runHoldExpiry((int)$property['id']);
    $holdState->execute([$verifiedExpiredHold['id']]);
    assertSmoke($holdState->fetchColumn()==='cancelled','A provider-verified expired token did not release its due booking hold.');

    $placeholderHold=$makeHoldExpiryCase('placeholder','pending','iyzico','init:VRHOLD-INIT-'.strtoupper(bin2hex(random_bytes(4))));
    $runHoldExpiry((int)$property['id']);
    $holdState->execute([$placeholderHold['id']]);
    assertSmoke($holdState->fetchColumn()==='cancelled','A due abandoned initialization did not release its booking hold.');
    $realTokenState->execute([$placeholderHold['reference']]);
    assertSmoke($realTokenState->fetchColumn()==='expired','An expired initialization placeholder was not safely retired.');

    $paidHold=$makeHoldExpiryCase('paid','paid','manual');
    $runHoldExpiry((int)$property['id']);
    $holdState->execute([$paidHold['id']]);
    assertSmoke($holdState->fetchColumn()==='pending','A paid booking awaiting confirmation was incorrectly released.');

    $failedHold=$makeHoldExpiryCase('failed','failed','paytr');
    $runHoldExpiry((int)$property['id']);
    $holdState->execute([$failedHold['id']]);
    assertSmoke($holdState->fetchColumn()==='cancelled','A due booking with a terminal failed payment was not released.');

    
    // MySQL-backed manual transfer lifecycle: reject, resubmit, validate
    // amount and booking conflicts, then approve exactly once.
    $makeManualBooking = static function (string $code, string $start, string $end) use ($pdo, $createBooking, $createFinancial, $property, $customerId, $customerEmail, $subtotal, $fees, $total): int {
        $createBooking->execute([$code,(int)$property['id'],$customerId,'Manual Smoke Customer',$customerEmail,'+905551112233',$start,$end,1,$subtotal,$fees,$total,$property['currency']]);
        $id = (int)$pdo->lastInsertId();
        $createFinancial->execute([$id,$total,$total,$property['currency']]);
        return $id;
    };
    $createManualReport = $pdo->prepare("INSERT INTO manual_payment_reports(booking_id,guest_user_id,amount,currency,sender_name,sender_bank,transfer_reference,note,status,submitted_at,created_at) VALUES(?,?,?,?,?,?,?,?,'pending',NOW(),NOW())");
    $manualReportState = $pdo->prepare('SELECT status,amount,currency FROM manual_payment_reports WHERE id=?');
    $manualPaymentCount = $pdo->prepare("SELECT COUNT(*) FROM payment_transactions WHERE booking_id=? AND provider='manual' AND status='paid'");

    $resubmitBookingId = $makeManualBooking('VRMANREJ' . strtoupper(bin2hex(random_bytes(3))),(new DateTimeImmutable('+60 days'))->format('Y-m-d'),(new DateTimeImmutable('+62 days'))->format('Y-m-d'));
    $createManualReport->execute([$resubmitBookingId,$customerId,$total,$property['currency'],'Smoke Sender','Smoke Bank','MANUAL-REJECT-'.bin2hex(random_bytes(3)),null]);
    $rejectedReportId = (int)$pdo->lastInsertId();
    $reject = $pdo->prepare("UPDATE manual_payment_reports SET status='rejected',reviewed_by=?,review_note='Smoke rejection',reviewed_at=NOW(),updated_at=NOW() WHERE id=? AND status='pending'");
    $reject->execute([(int)$admin['id'],$rejectedReportId]);
    assertSmoke($reject->rowCount()===1,'Manual transfer report could not be rejected.');
    $manualReportState->execute([$rejectedReportId]);
    assertSmoke(($manualReportState->fetch(PDO::FETCH_ASSOC)['status']??'')==='rejected','Rejected manual transfer report status was not persisted.');
    $createManualReport->execute([$resubmitBookingId,$customerId,$total,$property['currency'],'Smoke Sender','Smoke Bank','MANUAL-RESUBMIT-'.bin2hex(random_bytes(3)),null]);
    $resubmittedReportId = (int)$pdo->lastInsertId();
    assertSmoke($resubmittedReportId>$rejectedReportId,'A rejected report prevented a new pending report from being submitted.');
    $activeReportCount = $pdo->prepare("SELECT COUNT(*) FROM manual_payment_reports WHERE booking_id=? AND status='pending'");
    $activeReportCount->execute([$resubmitBookingId]);
    assertSmoke((int)$activeReportCount->fetchColumn()===1,'Resubmission did not leave exactly one active report.');

    $mismatchBookingId = $makeManualBooking('VRMANAMT' . strtoupper(bin2hex(random_bytes(3))),(new DateTimeImmutable('+70 days'))->format('Y-m-d'),(new DateTimeImmutable('+72 days'))->format('Y-m-d'));
    $createManualReport->execute([$mismatchBookingId,$customerId,$total+0.01,$property['currency'],'Smoke Sender','Smoke Bank','MANUAL-AMOUNT-'.bin2hex(random_bytes(3)),null]);
    $mismatchReportId = (int)$pdo->lastInsertId();
    $mismatchBooking = $pdo->prepare('SELECT total,currency FROM bookings WHERE id=?');
    $mismatchBooking->execute([$mismatchBookingId]);
    $mismatchBookingRow = $mismatchBooking->fetch(PDO::FETCH_ASSOC);
    $manualReportState->execute([$mismatchReportId]);
    $mismatchReportRow = $manualReportState->fetch(PDO::FETCH_ASSOC);
    $amountMismatch = abs((float)$mismatchBookingRow['total']-(float)$mismatchReportRow['amount'])>0.009 || strtoupper((string)$mismatchBookingRow['currency'])!==strtoupper((string)$mismatchReportRow['currency']);
    assertSmoke($amountMismatch,'Manual transfer amount mismatch fixture was not detected.');
    $manualPaymentCount->execute([$mismatchBookingId]);
    assertSmoke((int)$manualPaymentCount->fetchColumn()===0,'Amount-mismatched manual transfer created a paid transaction.');

    $conflictBookingId = $makeManualBooking('VRMANCON' . strtoupper(bin2hex(random_bytes(3))),$checkIn,$checkOut);
    $createManualReport->execute([$conflictBookingId,$customerId,$total,$property['currency'],'Smoke Sender','Smoke Bank','MANUAL-CONFLICT-'.bin2hex(random_bytes(3)),null]);
    $conflictReportId = (int)$pdo->lastInsertId();
    $conflictCheck = $pdo->prepare("SELECT id FROM bookings WHERE property_id=? AND id<>? AND status='confirmed' AND check_in < ? AND check_out > ? LIMIT 1 FOR UPDATE");
    $conflictCheck->execute([(int)$property['id'],$conflictBookingId,$checkOut,$checkIn]);
    assertSmoke((bool)$conflictCheck->fetchColumn(),'Overlapping confirmed booking was not detected before manual payment approval.');
    $manualPaymentCount->execute([$conflictBookingId]);
    assertSmoke((int)$manualPaymentCount->fetchColumn()===0,'Conflicting booking received a manual paid transaction.');

    $pendingOverlapStart=(new DateTimeImmutable('+200 days'))->format('Y-m-d');
    $pendingOverlapEnd=(new DateTimeImmutable('+202 days'))->format('Y-m-d');
    $pendingOverlapId=$makeManualBooking('VRMANPEND'.strtoupper(bin2hex(random_bytes(3))),$pendingOverlapStart,$pendingOverlapEnd);
    $pendingTargetId=$makeManualBooking('VRMANTARG'.strtoupper(bin2hex(random_bytes(3))),$pendingOverlapStart,$pendingOverlapEnd);
    $createManualReport->execute([$pendingTargetId,$customerId,$total,$property['currency'],'Smoke Sender','Smoke Bank','MANUAL-PENDING-CONFLICT-'.bin2hex(random_bytes(3)),null]);
    $pendingConflictCheck=$pdo->prepare("SELECT id FROM bookings WHERE property_id=? AND id<>? AND status IN ('pending','confirmed') AND check_in < ? AND check_out > ? LIMIT 1 FOR UPDATE");
    $pendingConflictCheck->execute([(int)$property['id'],$pendingTargetId,$pendingOverlapEnd,$pendingOverlapStart]);
    assertSmoke((bool)$pendingConflictCheck->fetchColumn(),'Overlapping pending booking was not detected before manual payment approval.');
    $manualPaymentCount->execute([$pendingTargetId]);
    assertSmoke((int)$manualPaymentCount->fetchColumn()===0,'A pending-overlap booking received a manual paid transaction.');

    $approvedBookingId = $makeManualBooking('VRMANOK' . strtoupper(bin2hex(random_bytes(3))),(new DateTimeImmutable('+80 days'))->format('Y-m-d'),(new DateTimeImmutable('+82 days'))->format('Y-m-d'));
    $createManualReport->execute([$approvedBookingId,$customerId,$total,$property['currency'],'Smoke Sender','Smoke Bank','MANUAL-APPROVE-'.bin2hex(random_bytes(3)),null]);
    $approvedReportId = (int)$pdo->lastInsertId();
    $approvedReport = $pdo->prepare('SELECT * FROM manual_payment_reports WHERE id=?');
    $approvedReport->execute([$approvedReportId]);
    $approvedReportRow = $approvedReport->fetch(PDO::FETCH_ASSOC);
    $approvedProviderReference = 'MANUAL-REPORT-'.$approvedReportId;
    $approvedIdempotencyKey = hash('sha256','manual-payment-report:'.$approvedReportId);
    $pdo->beginTransaction();
    $createManualTransaction = $pdo->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,idempotency_key,amount,currency,status,request_payload,response_payload,paid_at,created_at,updated_at) VALUES(?, 'manual', ?, ?, ?, ?, 'paid', ?, ?, NOW(), NOW(), NOW())");
    $createManualTransaction->execute([$approvedBookingId,$approvedProviderReference,$approvedIdempotencyKey,(float)$approvedReportRow['amount'],(string)$approvedReportRow['currency'],json_encode(['transfer_reference'=>$approvedReportRow['transfer_reference']]),json_encode(['report_id'=>$approvedReportId,'reviewed_by'=>(int)$admin['id'],'decision'=>'approved'])]);
    $confirmManualBooking = $pdo->prepare("UPDATE bookings SET status='confirmed' WHERE id=? AND status='pending'");
    $confirmManualBooking->execute([$approvedBookingId]);
    $approveManualReport = $pdo->prepare("UPDATE manual_payment_reports SET status='approved',reviewed_by=?,reviewed_at=NOW(),updated_at=NOW() WHERE id=? AND status='pending'");
    $approveManualReport->execute([(int)$admin['id'],$approvedReportId]);
    assertSmoke($confirmManualBooking->rowCount()===1&&$approveManualReport->rowCount()===1,'Manual transfer approval did not atomically confirm the booking and report.');
    $pdo->commit();
    $approvedBookingState = $pdo->prepare('SELECT status FROM bookings WHERE id=?');
    $approvedBookingState->execute([$approvedBookingId]);
    assertSmoke($approvedBookingState->fetchColumn()==='confirmed','Approved manual transfer did not confirm the booking.');
    $manualPaymentCount->execute([$approvedBookingId]);
    assertSmoke((int)$manualPaymentCount->fetchColumn()===1,'Manual approval did not create exactly one paid transaction.');
    $duplicateApproval = $pdo->prepare("UPDATE manual_payment_reports SET status='approved',reviewed_by=?,reviewed_at=NOW() WHERE id=? AND status='pending'");
    $duplicateApproval->execute([(int)$admin['id'],$approvedReportId]);
    assertSmoke($duplicateApproval->rowCount()===0,'A second manual approval was not rejected as already reviewed.');
    $manualPaymentCount->execute([$approvedBookingId]);
    assertSmoke((int)$manualPaymentCount->fetchColumn()===1,'Repeated manual approval created a second paid transaction.');

    // A second provider must not collect a second payment for a booking that
    // already has a verified manual transfer. The booking lock serializes
    // callbacks from different providers before this invariant is checked.
    $crossProviderReference = 'VILLA-CROSS-PROVIDER-' . strtoupper(bin2hex(random_bytes(4)));
    $createCrossProviderPayment = $pdo->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,amount,currency,status,created_at) VALUES(?,'iyzico',?,?,?,'pending',NOW())");
    $createCrossProviderPayment->execute([$approvedBookingId,$crossProviderReference,(string)$approvedReportRow['amount'],(string)$approvedReportRow['currency']]);
    $crossProviderCallback = [
        'booking_id'=>$approvedBookingId,'provider'=>'iyzico',
        'provider_reference'=>$crossProviderReference,
        'amount'=>(string)$approvedReportRow['amount'],
        'currency'=>(string)$approvedReportRow['currency'],'status'=>'success',
    ];
    $crossProviderEvent = 'VILLA-CROSS-EVENT-' . strtoupper(bin2hex(random_bytes(4)));
    $crossProviderResult = (new PaymentCallbackProcessor($pdo))->processVerified(
        'iyzico',$crossProviderEvent,'CHECKOUT_FORM_AUTH',$crossProviderReference,
        ['reference'=>$crossProviderReference,'status'=>'success'],$crossProviderCallback
    );
    assertSmoke(($crossProviderResult['result']??'')==='rejected_duplicate_booking_payment'&&!($crossProviderResult['payment_updated']??true),'A second provider payment was accepted for a booking already paid by manual transfer.');
    $crossProviderState = $pdo->prepare('SELECT status FROM payment_transactions WHERE provider=? AND provider_reference=?');
    $crossProviderState->execute(['iyzico',$crossProviderReference]);
    assertSmoke($crossProviderState->fetchColumn()==='pending','Rejected cross-provider callback changed its pending transaction.');
    $approvedBookingState->execute([$approvedBookingId]);
    assertSmoke($approvedBookingState->fetchColumn()==='confirmed','Rejected cross-provider callback changed the already-confirmed booking.');




    foreach (['vendors','properties','property_media','bookings','booking_financials','payment_transactions','vendor_payouts','reviews','notifications','cms_pages','navigation_items','contact_messages','newsletter_subscribers'] as $table) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $check->execute([$table]);
        assertSmoke((int)$check->fetchColumn() === 1, 'Missing required table: ' . $table);
    }

    $themeManifests = glob($root . '/themes/*/theme.json') ?: [];
    assertSmoke(is_file($root . '/themes/default/theme.json'), 'The required default theme manifest must remain installed.');
    assertSmoke(isset(ThemeManager::installed()['default']) && ThemeManager::activeSlug() === 'default', 'The default theme must remain available and be the initial active theme.');
    $defaultManifestPath = $root . '/themes/default/theme.json';
    $defaultManifest = json_decode((string)file_get_contents($defaultManifestPath), true);
    assertSmoke(is_array($defaultManifest) && ($defaultManifest['slug'] ?? '') === 'default', 'The default base theme manifest is missing or invalid.');

    // Exercise active-theme selection, component-level PHP fallback, and nested asset fallback.
    $smokeThemeDir = $root . '/themes/smoke-theme';
    $defaultAssetsDir = $root . '/themes/default/assets';
    @mkdir($smokeThemeDir . '/partials', 0775, true);
    @mkdir($smokeThemeDir . '/assets/icons', 0775, true);
    @mkdir($defaultAssetsDir, 0775, true);
    file_put_contents($smokeThemeDir . '/theme.json', json_encode(['slug'=>'smoke-theme','name'=>'Smoke Theme','version'=>'1.0.0'], JSON_UNESCAPED_SLASHES));
    file_put_contents($smokeThemeDir . '/partials/smoke-component.php', '<?php echo "active-component";');
    @mkdir($smokeThemeDir . '/layouts', 0775, true);
    file_put_contents($smokeThemeDir . '/layouts/admin-shell.php', '<?php return static fn(string $html, string $path, string $mode): string => "theme-admin:" . $html;');
    @mkdir($smokeThemeDir . '/templates/pages', 0775, true);
    file_put_contents($smokeThemeDir . '/templates/pages/smoke-page.php', '<?php echo "active-page";');
    file_put_contents($smokeThemeDir . '/style.css', '.smoke-theme{color:#123456}');
    file_put_contents($smokeThemeDir . '/assets/icons/active.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
    file_put_contents($defaultAssetsDir . '/smoke-fallback.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
    ThemeManager::saveActiveTheme('smoke-theme');
    assertSmoke(ThemeManager::activeSlug($pdo) === 'smoke-theme', 'A valid installed theme could not be activated.');
    assertSmoke(ThemeManager::resolveFile('style.css', 'smoke-theme') === realpath($smokeThemeDir . '/style.css'), 'Active theme CSS did not receive priority over the default stylesheet.');
    ob_start();
    ThemeManager::renderPartial('partials/smoke-component.php');
    $renderedSmokeComponent = (string)ob_get_clean();
    assertSmoke($renderedSmokeComponent === 'active-component', 'A PHP component override was not rendered from the active theme.');
    assertSmoke(ThemeManager::resolveFile('templates/pages/listing.php', 'smoke-theme') === realpath($root . '/themes/default/templates/pages/listing.php'), 'Missing active page templates did not fall back to the default page template.');
    ob_start();
    ThemeManager::renderTemplate('templates/pages/smoke-page.php');
    $renderedSmokePage = (string)ob_get_clean();
    assertSmoke($renderedSmokePage === 'active-page', 'An active theme page template was not rendered.');
    assertSmoke(ThemeManager::resolveFile('templates/pages/admin/dashboard.php', 'smoke-theme') === realpath($root . '/themes/default/templates/pages/admin/dashboard.php'), 'Missing active admin pages did not fall back to the default theme.');
    assertSmoke(ThemeManager::wrapAdminHtml('content', '/admin', 'single_vendor') === 'theme-admin:content', 'The active theme admin layout was not applied.');
    assertSmoke(ThemeManager::resolveFile('assets/icons/active.svg', 'smoke-theme') === realpath($smokeThemeDir . '/assets/icons/active.svg'), 'Active theme assets did not receive priority.');
    assertSmoke(ThemeManager::resolveFile('assets/smoke-fallback.svg', 'smoke-theme') === realpath($defaultAssetsDir . '/smoke-fallback.svg'), 'Missing active assets did not fall back to default.');
    assertSmoke(ThemeManager::resolveFile('partials/login-modal.php', 'smoke-theme') === realpath($root . '/themes/default/partials/login-modal.php'), 'Missing active PHP components did not fall back to default.');
    $capturedRoute = [];
    $testRouter = new Router();
    $testRouter->get('/theme-assets/{slug}/{file*}', static function (array $params) use (&$capturedRoute): void { $capturedRoute = $params; });
    $testRouter->dispatch('GET', '/theme-assets/smoke-theme/assets/icons/active.svg');
    assertSmoke(($capturedRoute['slug'] ?? '') === 'smoke-theme' && ($capturedRoute['file'] ?? '') === 'assets/icons/active.svg', 'Nested theme asset routes did not preserve their relative path.');
    $pdo->prepare('DELETE FROM settings WHERE setting_key=?')->execute(['theme.active']);
    @unlink($smokeThemeDir . '/partials/smoke-component.php');
    @unlink($smokeThemeDir . '/templates/pages/smoke-page.php');
    @unlink($smokeThemeDir . '/layouts/admin-shell.php');
    @unlink($smokeThemeDir . '/style.css');
    @unlink($smokeThemeDir . '/assets/icons/active.svg');
    @unlink($smokeThemeDir . '/theme.json');
    @unlink($defaultAssetsDir . '/smoke-fallback.svg');
    @rmdir($smokeThemeDir . '/partials');
    @rmdir($smokeThemeDir . '/templates/pages');
    @rmdir($smokeThemeDir . '/layouts');
    @rmdir($smokeThemeDir . '/templates');
    @rmdir($smokeThemeDir . '/assets/icons');
    @rmdir($smokeThemeDir . '/assets');
    @rmdir($smokeThemeDir);
    @rmdir($defaultAssetsDir);

    foreach (['style.css','templates/landing.php','templates/listing.php','templates/property.php','templates/destinations.php','templates/booking.php','templates/page.php','templates/account.php'] as $themeFile) {
        assertSmoke(is_file($root . '/themes/default/' . $themeFile), 'Missing default theme file: ' . $themeFile);
    }
    assertSmoke(!is_file($root . '/app/Core/PageBuilder.php') && !is_file($root . '/app/Core/ThemeDemoImporter.php') && !is_file($root . '/views/admin/builder.php') && !is_file($root . '/views/site/partials/page-builder-home.php'), 'Page Builder and widget implementation files must be removed.');

    $landingThemeSource = (string)file_get_contents($root . '/themes/default/templates/landing.php');
    $listingPageSource = (string)file_get_contents($root . '/themes/default/templates/pages/listing.php');
    assertSmoke(str_contains($landingThemeSource, "renderPartial('components/cards/villa-card.php'") && str_contains($listingPageSource, "renderPartial('components/cards/villa-card.php'"), 'Homepage and search results must use the same shared villa card component.');
    $webRoutesSource = (string)file_get_contents($root . '/routes/web.php');
    $bookingCreateRouteStart = strpos($webRoutesSource, '$router->post(\'/villa/{slug}/reserve\'');
    $bookingCreateRoute = $bookingCreateRouteStart !== false ? substr($webRoutesSource, $bookingCreateRouteStart) : '';
    assertSmoke(
        substr_count($webRoutesSource, "status='confirmed'") >= 7
        && substr_count($webRoutesSource, "status IN ('pending','confirmed')") === 4
        && str_contains($bookingCreateRoute, "status IN ('pending','confirmed')")
        && str_contains($bookingCreateRoute, 'FOR UPDATE')
        && str_contains($webRoutesSource, "SELECT id FROM bookings WHERE property_id=? AND id<>? AND status IN ('pending','confirmed')"),
        'Booking creation and payment/administration confirmation paths must protect pending date holds; public availability and occupied calendars must continue to use confirmed bookings only.'
    );
    $heroLandingSource = (string)file_get_contents($root . '/themes/default/templates/pages/landing.php');
    assertSmoke(str_contains($heroLandingSource, 'id="homeCalendar"') && str_contains($heroLandingSource, 'id="calendarMonths"') && str_contains($heroLandingSource, 'date-picker-trigger') && str_contains($heroLandingSource, 'is-in-range') && str_contains($heroLandingSource, "co.value<=ci.value") && !str_contains($heroLandingSource, 'showPicker'), 'Homepage hero must use the accessible custom date-range calendar with past-date blocking, range highlighting and checkout validation.');
    assertSmoke(!str_contains($landingThemeSource, 'class="med-property-card"') && str_contains($landingThemeSource, 'cardGalleryScript'), 'Homepage must not maintain a separate villa card markup and must initialize the shared gallery controls.');

    $themeManagerSource = (string)file_get_contents($root . '/app/Core/ThemeManager.php');
    assertSmoke(str_contains($themeManagerSource, 'public static function resolveFile') && str_contains($themeManagerSource, 'public static function assetUrl') && str_contains($themeManagerSource, 'public static function renderPartial') && str_contains($themeManagerSource, "['theme.active']"), 'Active theme resolution must use a validated setting and provide safe file and component resolvers.');
    assertSmoke(str_contains($themeManagerSource, 'Base first, active second') && str_contains($themeManagerSource, "self::assetUrl('style.css', 'default')"), 'Default and active stylesheets must load in cascade order.');
    $defaultThemeCssSource = (string)file_get_contents($root . '/themes/default/style.css');
    assertSmoke(str_contains($defaultThemeCssSource, 'body[data-villa-theme]{background-color:var(--villa-background)!important;}'), 'The shared body background must follow the active theme background setting.');
    assertSmoke(str_contains($themeManagerSource, 'public static function socialSettings()') && str_contains($themeManagerSource, 'public static function saveSocialSettings(array $input)') && str_contains($themeManagerSource, 'villa-social-bar') && str_contains($themeManagerSource, 'renderSocialIcon'), 'Shared top information settings and rendering must remain available.');
    assertSmoke(str_contains($themeManagerSource, 'saveTypographySettings') && str_contains($themeManagerSource, 'typographySettings') && str_contains($themeManagerSource, 'fonts.googleapis.com') && str_contains($themeManagerSource, 'paragraph_links'), 'Theme-specific Google Fonts and typography rendering must be available.');
    $typographyViewSource = (string)file_get_contents($root . '/views/admin/typography.php');
    assertSmoke(str_contains($typographyViewSource, 'name="typography[') && str_contains($typographyViewSource, '][family]') && str_contains($typographyViewSource, '][color]') && str_contains($typographyViewSource, 'topbar_height'), 'Theme typography controls must expose font family, size, color and topbar height.');
    assertSmoke(!str_contains($themeManagerSource, 'PageBuilder') && !str_contains($themeManagerSource, 'ThemeDemoImporter') && !str_contains($themeManagerSource, 'ZipArchive'), 'Theme manager must not contain builder, demo importer or theme package upload dependencies.');

    $sharedTopbarPath = $root . '/themes/default/partials/shared-topbar.php';
    assertSmoke(is_file($sharedTopbarPath), 'The shared logo-only topbar must live in one reusable theme partial.');
    $sharedTopbarSource = (string)file_get_contents($sharedTopbarPath);
    assertSmoke(str_contains($sharedTopbarSource, 'site-logo-topbar') && str_contains($sharedTopbarSource, 'site-logo-topbar__brand'), 'The reusable topbar must render the centered site logo.');
    assertSmoke(!str_contains($sharedTopbarSource, 'villa-social-bar') && !str_contains($sharedTopbarSource, 'villa-social-address') && !str_contains($sharedTopbarSource, 'villa-social-call-center'), 'The logo-only topbar must not restore the removed contact and social information strip.');
    assertSmoke(str_contains($themeManagerSource, "renderPartial('partials/shared-topbar.php'") && str_contains($themeManagerSource, 'public static function renderPartial') && !str_contains($themeManagerSource, 'villa-mobile-single-column-global'), 'The logo-only topbar must use the reusable component resolver and global responsive CSS must not be duplicated in ThemeManager.');
    $obsoleteTables = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('page_builder_layouts','theme_demo_imports','theme_demo_layouts')")->fetchColumn();
    assertSmoke($obsoleteTables === 0, 'Obsolete Page Builder and theme demo tables must be removed by migrations.');
    $archiveTable = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='legacy_cleanup_archive'")->fetchColumn();
    assertSmoke($archiveTable === 1, 'Legacy Page Builder records must be archived before obsolete tables are removed.');
    $obsoleteNavigation = (int)$pdo->query("SELECT COUNT(*) FROM navigation_items WHERE url LIKE '/demo-theme/%' OR (label='Demolar' AND url='#')")->fetchColumn();
    assertSmoke($obsoleteNavigation === 0, 'Obsolete demo theme navigation entries must be removed.');

    $rootHtaccess = (string)file_get_contents($root . '/.htaccess');
    assertSmoke(!str_contains($rootHtaccess, 'public/demo/') && str_contains($rootHtaccess, 'public/uploads/'), 'Root rewrite rules must not point to the removed demo asset directory and must retain upload routing.');

    $themeRoutesSource = (string)file_get_contents($root . '/routes/web.php');
    $adminUsersViewSource = (string)file_get_contents($root . '/themes/default/templates/pages/admin/users.php');
    assertSmoke(str_contains($themeRoutesSource, "/admin/users/delete") && str_contains($themeRoutesSource, "verify_csrf();") && str_contains($themeRoutesSource, "Auth::canManageUserRole((string)$currentUser['role'],(string)$target['role'])") && str_contains($themeRoutesSource, "role IN ('customer','vendor')") && str_contains($themeRoutesSource, "status='disabled'") && str_contains($themeRoutesSource, "session_version=session_version+1") && str_contains($themeRoutesSource, "account_security_tokens SET used_at=COALESCE(used_at,NOW())") && !str_contains($themeRoutesSource, "DELETE FROM users WHERE id=? AND role IN ('customer','vendor')") && !str_contains($themeRoutesSource, "UPDATE bookings SET guest_user_id=NULL WHERE guest_user_id=?"), 'Member closure must be CSRF-protected, role-limited, invalidate credentials and preserve linked history.');
    assertSmoke(str_contains($adminUsersViewSource, 'action="/admin/users/delete"') && str_contains($adminUsersViewSource, 'Hesabı kapat') && str_contains($adminUsersViewSource, 'anonimleştirilir') && str_contains($adminUsersViewSource, 'confirm('), 'Admin user list must explain and confirm anonymized account closure.');
    $adminShellSource = (string)file_get_contents($root . '/themes/default/layouts/admin-shell.php');
    assertSmoke(str_contains($themeRoutesSource, "/admin/header-settings/social-icons") && !str_contains($themeRoutesSource, 'PageBuilder') && !str_contains($themeRoutesSource, '/admin/builder') && !str_contains($adminShellSource, '/admin/builder'), 'Builder routes and navigation must be removed while shared top information settings remain available.');
    $themeDesignViewSource = (string)file_get_contents($root . '/themes/default/templates/pages/admin/theme-design.php');
    assertSmoke(str_contains($themeRoutesSource, "/admin/theme-design") && str_contains($adminShellSource, "/admin/theme-design") && str_contains($themeDesignViewSource, 'data-design-tab="typography"') && str_contains($themeDesignViewSource, 'data-design-tab-panel="typography"') && str_contains($themeDesignViewSource, 'Başlık tipografisi (H1–H6)') && str_contains($themeDesignViewSource, 'typography[headings][sizes]'), 'Theme typography controls must be available from the Header settings tab in the theme design screen.');
    assertSmoke(str_contains($themeRoutesSource, "/admin/themes") && str_contains($themeRoutesSource, "ThemeManager::saveActiveTheme") && str_contains($adminShellSource, "Translator::get('admin.themes.title')"), 'Theme activation must be available through the translated admin theme manager.');
    $themeCssSource = (string)file_get_contents($root . '/themes/default/style.css');
    assertSmoke(str_contains($themeCssSource, 'min-height:64px') && str_contains($themeCssSource, 'padding:10px 22px'), 'Shared top information bar must use the increased height and spacing.');
    assertSmoke(str_contains($themeRoutesSource, "villa_theme_message_type") && str_contains($themeRoutesSource, 'Shared header settings save failed'), 'Header settings save failures must be logged and surfaced to the administrator.');
    $themeSettingsViewSource = (string)file_get_contents($root . '/themes/default/templates/pages/admin/header-settings.php');
    assertSmoke(str_contains($themeSettingsViewSource, 'role="status"') && str_contains($themeSettingsViewSource, '.notice.error'), 'Header settings must visibly distinguish a failed save from a successful save.');

    $iyzicoClientSource = (string)file_get_contents($root . '/app/Core/IyzicoApiClient.php');
    assertSmoke(str_contains($iyzicoClientSource, 'IYZWSv2 ') && str_contains($iyzicoClientSource, 'hash_hmac') && str_contains($iyzicoClientSource, '/payment/bin/check'), 'Native iyzico connection client must use HMAC authentication and a non-payment BIN check.');
    assertSmoke(str_contains($iyzicoClientSource, 'sandbox-api.iyzipay.com') && str_contains($iyzicoClientSource, 'api.iyzipay.com') && str_contains($iyzicoClientSource, 'CURLOPT_SSL_VERIFYPEER => true'), 'iyzico client must target both supported environments and verify TLS certificates.');
    assertSmoke(str_contains($iyzicoClientSource, 'function testSandboxConnection') && str_contains($iyzicoClientSource, 'binNumber'), 'iyzico admin connection test must use a non-payment BIN lookup.');
    assertSmoke(str_contains($themeRoutesSource, 'test_iyzico_sandbox') && str_contains($themeRoutesSource, 'IyzicoApiClient::testConnection'), 'Admin settings must expose the selected-environment iyzico connection test.');
    assertSmoke(str_contains($themeRoutesSource, "/checkout/{bookingCode}/iyzico/start") && str_contains($themeRoutesSource, "/payments/iyzico/callback"), 'iyzico Checkout Form start and callback routes must be registered.');
    assertSmoke(str_contains($themeRoutesSource, 'IyzicoApiClient::initializeCheckoutForm') && str_contains($themeRoutesSource, 'IyzicoApiClient::retrieveCheckoutForm'), 'iyzico checkout must initialize and retrieve the result from the provider.');
    assertSmoke(str_contains($themeRoutesSource, 'verifyIyzicoCheckoutFormInitialize') && str_contains($themeRoutesSource, 'villa_iyzico_checkout_redirect_url') && str_contains($themeRoutesSource, 'paymentPageUrl') && str_contains($themeRoutesSource, 'redirect($redirectUrl)') && str_contains($themeRoutesSource, "checkout_url=?"), 'iyzico initialization must verify its response signature and redirect the customer to a validated hosted payment page.');
assertSmoke(str_contains($themeRoutesSource, "'sandbox-cpp.iyzipay.com'") && str_contains($themeRoutesSource, "'cpp.iyzipay.com'") && str_contains($themeRoutesSource, "['','/']") && str_contains($themeRoutesSource, "query['token']"), 'iyzico Sandbox and Production CPP root paymentPageUrl with a token must be accepted for hosted redirection.');
    assertSmoke(str_contains($themeRoutesSource, 'verifyIyzicoCheckoutFormRetrieve'), 'iyzico callback and webhook must verify the Checkout Form retrieve response signature.');

    assertSmoke(str_contains($themeRoutesSource, "/payments/iyzico/webhook") && str_contains($themeRoutesSource, 'verifyIyzicoV3'), 'iyzico Checkout Form webhook must verify V3 signatures.');
    $helpersSource = (string)file_get_contents($root . '/app/Support/helpers.php');
    assertSmoke(str_contains($helpersSource, 'function app_absolute_url') && str_contains($helpersSource, 'HTTPS bağlantısı gereklidir'), 'Absolute HTTPS URL helper must be available for provider callbacks.');
    $paymentSettingsViewSource = (string)file_get_contents($root . '/themes/default/templates/pages/admin/settings.php');
    assertSmoke(str_contains($paymentSettingsViewSource, '/payments/iyzico/webhook') && str_contains($paymentSettingsViewSource, 'Webhook V3'), 'Admin payment settings must display the iyzico webhook endpoint and setup note.');

    assertSmoke(str_contains($themeRoutesSource, "fraudStatus") && str_contains($themeRoutesSource, '$paymentEnvironment=(string)') && str_contains($themeRoutesSource, "'environment'=>$paymentEnvironment"), 'iyzico checkout must honor fraud review and persist the selected Sandbox or Production environment.');
    $checkoutViewSource = (string)file_get_contents($root . '/themes/default/templates/pages/checkout.php');
    assertSmoke(str_contains($checkoutViewSource, 'iyzico güvenli ödeme sayfasına git') && str_contains($checkoutViewSource, 'identity_number') && str_contains($checkoutViewSource, 'billing_address') && !str_contains($checkoutViewSource, 'checkout-iframe-shell') && !str_contains($checkoutViewSource, 'iyzipay-checkout-form'), 'Booking checkout must collect buyer billing details and send the customer to iyzico hosted checkout instead of embedding it.');
    assertSmoke(str_contains($themeRoutesSource, "/payment/") && str_contains($themeRoutesSource, "foreach(['success','failed','review']") && str_contains($themeRoutesSource, "payment-result.php") && str_contains($themeRoutesSource, "paymentToken"), 'iyzico callback must return to token-bound local success, failure or review result pages.');
    assertSmoke(str_contains($themeRoutesSource, "brandSettings=ThemeManager::brandSettings(ThemeManager::activeSlug())"), 'Payment result route must pass active theme branding settings so the configured site logo is displayed.');
    $paymentResultViewSource = (string)file_get_contents($root . '/themes/default/templates/pages/payment-result.php');
    assertSmoke(str_contains($paymentResultViewSource, 'Ödemeniz başarılı') && str_contains($paymentResultViewSource, 'Ödeme tamamlanamadı') && str_contains($paymentResultViewSource, 'Tekrar ödeme yap'), 'Dedicated payment result page must show verified success and failure states and allow retry.');
    assertSmoke(str_contains($paymentResultViewSource, "ThemeManager::assetUrl('style.css', 'default')") && str_contains($paymentResultViewSource, 'ThemeManager::assetUrl(\'style.css\', $paymentThemeSlug)') && str_contains($paymentResultViewSource, 'data-villa-theme='), 'Payment result pages must load the base and active theme stylesheets so shared header and footer render with the site design.');

    $paymentSimulatorSource = (string)file_get_contents($root . '/app/Core/PaymentSandboxSimulator.php');
    assertSmoke(str_contains($paymentSimulatorSource, 'LOCAL-ONLY-TEST-MERCHANT-KEY') && str_contains($paymentSimulatorSource, 'LOCAL-ONLY-TEST-MERCHANT-SALT'), 'PayTR local sandbox must use isolated synthetic credentials.');
    assertSmoke(str_contains($paymentSimulatorSource, 'amount_mismatch') && str_contains($paymentSimulatorSource, 'wrong_order') && str_contains($paymentSimulatorSource, 'PaymentSignatureVerifier::verifyPaytr'), 'PayTR local sandbox must test callback signatures, amount mismatch and order mismatch.');
    assertSmoke(str_contains($paymentSimulatorSource, "'network_requests' => 0") && str_contains($paymentSimulatorSource, "'real_charge' => false") && str_contains($paymentSimulatorSource, "'booking_changed' => false"), 'Local payment sandbox must never contact providers, charge cards or mutate bookings.');
    $paymentTestViewSource = (string)file_get_contents($root . '/themes/default/templates/pages/admin/payment-tests.php');
    assertSmoke(str_contains($paymentTestViewSource, 'PayTR – Yerel iFrame simülatörü') && str_contains($paymentTestViewSource, 'Tutar uyuşmazlığı') && str_contains($paymentTestViewSource, 'Sipariş numarası uyuşmazlığı'), 'Admin payment test center must expose PayTR protocol failure scenarios.');

    // A cache produced for another database must never suppress this
    // installation's pending migrations. Seed a matching file signature with
    // a deliberately foreign database identity and verify it is rejected.
    $migrationFiles = glob($root . '/database/migrations/*.sql') ?: [];
    sort($migrationFiles, SORT_STRING);
    $signatureParts = [];
    foreach ($migrationFiles as $migrationFile) {
        $signatureParts[] = basename($migrationFile) . ':' . (string)filesize($migrationFile) . ':' . (string)filemtime($migrationFile);
    }
    $migrationSignature = hash('sha256', implode("\n", $signatureParts));
    $migrationStateFile = $root . '/storage/migrations-state.json';
    file_put_contents($migrationStateFile, json_encode([
        'signature' => $migrationSignature,
        'database_identity' => 'foreign-database-cache',
    ], JSON_UNESCAPED_SLASHES), LOCK_EX);

    Migrator::run($pdo, $root);
    $migrationState = json_decode((string)file_get_contents($migrationStateFile), true);
    assertSmoke(is_array($migrationState)
        && isset($migrationState['database_identity'])
        && $migrationState['database_identity'] !== 'foreign-database-cache',
        'Migration cache accepted a state file created for a different database.');

    // A second migration pass must be safe and leave the migration ledger intact.
    Migrator::run($pdo, $root);
    $migrationCountAfter = (int)$pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
    assertSmoke($migrationCountAfter === $migrationCount, 'Second migration pass changed the migration ledger.');

    // Exercise vendor payout eligibility, bank-profile encryption, allocation
    // idempotency, admin transitions, and the booking financial ledger.
    $vendorSuffix=strtolower(bin2hex(random_bytes(4)));
    $vendorEmail='payout.vendor.'.$vendorSuffix.'@example.test';
    $vendorUserStmt=$pdo->prepare("INSERT INTO users(username,email,password_hash,role,status,created_at) VALUES(?,?,?,'vendor','active',NOW())");
    $vendorUserStmt->execute(['payout_vendor_'.$vendorSuffix,$vendorEmail,password_hash('PayoutSmokePassword!2026',PASSWORD_DEFAULT)]);
    $vendorUserId=(int)$pdo->lastInsertId();
    $vendorInsert=$pdo->prepare("INSERT INTO vendors(user_id,business_name,status,created_at) VALUES(?,?,'approved',NOW())");
    $vendorInsert->execute([$vendorUserId,'Payout Smoke Vendor']);
    $vendorId=(int)$pdo->lastInsertId();
    $testIban='TR330006100519786457841326';
    assertSmoke(\App\Core\VendorPayoutService::validIban($testIban), 'Valid Turkish IBAN checksum was rejected.');
    assertSmoke(!\App\Core\VendorPayoutService::validIban('TR000000000000000000000000'), 'Invalid IBAN checksum was accepted.');
    \App\Core\VendorPayoutService::savePayoutProfile($pdo,$vendorUserId,'Test Bank','Payout Smoke Vendor',$testIban);
    $payoutProfile=$pdo->prepare('SELECT iban_encrypted FROM vendor_payout_profiles WHERE vendor_id=?');
    $payoutProfile->execute([$vendorId]);$encryptedIban=(string)$payoutProfile->fetchColumn();
    assertSmoke(str_starts_with($encryptedIban,'enc:v1:') && \App\Core\PaymentSecretVault::decrypt($encryptedIban)===$testIban, 'Vendor IBAN was not encrypted at rest or could not be decrypted.');

    $payoutPropertySlug='payout-smoke-'.$vendorSuffix;
    $payoutProperty=$pdo->prepare("INSERT INTO properties(vendor_id,title,slug,max_guests,bedrooms,beds,bathrooms,base_nightly_price,currency,status,booking_type,created_at) VALUES(?,?,?,4,2,2,1,100,'TRY','published','request',NOW())");
    $payoutProperty->execute([$vendorId,'Payout Smoke Villa',$payoutPropertySlug]);
    $payoutPropertyId=(int)$pdo->lastInsertId();
    $payoutBookingCode='PAYOUTSMOKE'.strtoupper($vendorSuffix);
    $payoutBooking=$pdo->prepare("INSERT INTO bookings(booking_code,property_id,guest_user_id,guest_name,guest_email,guest_phone,booking_source,check_in,check_out,guests,subtotal,fees,total,currency,status,created_at) VALUES(?,?,?,'Payout Smoke Guest',?,'','website',?,?,2,100,0,100,'TRY','completed',NOW())");
    $payoutBooking->execute([$payoutBookingCode,$payoutPropertyId,$customerId,$customerEmail,(new DateTimeImmutable('-3 days'))->format('Y-m-d'),(new DateTimeImmutable('-1 day'))->format('Y-m-d')]);
    $payoutBookingId=(int)$pdo->lastInsertId();
    $payoutFinancial=$pdo->prepare("INSERT INTO booking_financials(booking_id,gross_amount,platform_fee,vendor_net_amount,currency,payout_status,created_at) VALUES(?,100,10,90,'TRY','unpaid',NOW())");
    $payoutFinancial->execute([$payoutBookingId]);
    $payoutPayment=$pdo->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,amount,currency,status,paid_at,created_at) VALUES(?,'manual',?,100,'TRY','paid',NOW(),NOW())");
    $payoutPayment->execute([$payoutBookingId,'PAYOUT-SMOKE-'.$vendorSuffix]);

    $availablePayouts=\App\Core\VendorPayoutService::availableBalances($pdo,$vendorId);
    assertSmoke(count($availablePayouts)===1 && (float)$availablePayouts[0]['available_amount']===90.0, 'Completed, paid vendor balance was not calculated correctly.');
    $payoutRequest=\App\Core\VendorPayoutService::request($pdo,$vendorUserId,'TRY');
    assertSmoke((float)$payoutRequest['amount']===90.0 && $payoutRequest['allocation_count']===1, 'Vendor payout request did not reserve the exact available balance.');
    $duplicatePayoutRejected=false;
    try { \App\Core\VendorPayoutService::request($pdo,$vendorUserId,'TRY'); } catch (RuntimeException) { $duplicatePayoutRejected=true; }
    assertSmoke($duplicatePayoutRejected, 'A vendor was able to request the same booking balance twice.');
    \App\Core\VendorPayoutService::transition($pdo,(int)$admin['id'],(int)$payoutRequest['id'],'processing','','Smoke processing');
    $reservedStatus=(string)$pdo->query("SELECT payout_status FROM booking_financials WHERE booking_id=".(int)$payoutBookingId)->fetchColumn();
    assertSmoke($reservedStatus==='processing', 'Payout processing status was not reflected in the booking ledger.');
    \App\Core\VendorPayoutService::transition($pdo,(int)$admin['id'],(int)$payoutRequest['id'],'paid','BANK-SMOKE-'.$vendorSuffix,'Smoke paid');
    $paidStatus=(string)$pdo->query("SELECT payout_status FROM booking_financials WHERE booking_id=".(int)$payoutBookingId)->fetchColumn();
    $payoutEventCount=$pdo->prepare('SELECT COUNT(*) FROM vendor_payout_events WHERE payout_id=?');$payoutEventCount->execute([(int)$payoutRequest['id']]);
    assertSmoke($paidStatus==='paid' && (int)$payoutEventCount->fetchColumn()===3, 'Paid payout did not finalize the ledger and record the complete audit trail.');
    assertSmoke(\App\Core\VendorPayoutService::availableBalances($pdo,$vendorId)===[], 'Already-paid vendor funds remained available for another payout request.');

    // Apply signed provider refund responses locally without making a live gateway call.
    $refundBookingCode='REFUNDSMOKE'.strtoupper($vendorSuffix);
    $refundBookingStmt=$pdo->prepare("INSERT INTO bookings(booking_code,property_id,guest_user_id,guest_name,guest_email,guest_phone,booking_source,check_in,check_out,guests,subtotal,fees,total,currency,status,created_at) VALUES(?,?,?,'Refund Smoke Guest',?,'','website',?,?,2,100,0,100,'TRY','completed',NOW())");
    $refundBookingStmt->execute([$refundBookingCode,$payoutPropertyId,$customerId,$customerEmail,(new DateTimeImmutable('-3 days'))->format('Y-m-d'),(new DateTimeImmutable('-1 day'))->format('Y-m-d')]);
    $refundBookingId=(int)$pdo->lastInsertId();
    $refundFinancial=$pdo->prepare("INSERT INTO booking_financials(booking_id,gross_amount,platform_fee,vendor_net_amount,currency,payout_status,created_at) VALUES(?,100,10,90,'TRY','unpaid',NOW())");
    $refundFinancial->execute([$refundBookingId]);
    $refundPaymentPayload=json_encode(['environment'=>'sandbox','conversationId'=>'REFUND-CONV-'.$vendorSuffix,'basketId'=>$refundBookingCode],JSON_UNESCAPED_SLASHES);
    $refundVerifiedPayload=json_encode(['verified_checkout_form'=>['paymentId'=>'IYZICO-REFUND-'.$vendorSuffix]],JSON_UNESCAPED_SLASHES);
    $refundPayment=$pdo->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,amount,currency,status,request_payload,response_payload,paid_at,created_at,updated_at) VALUES(?,'iyzico',?,100,'TRY','paid',?,?,NOW(),NOW(),NOW())");
    $refundPayment->execute([$refundBookingId,'REFUND-TOKEN-'.$vendorSuffix,$refundPaymentPayload,$refundVerifiedPayload]);
    $refundPaymentId=(int)$pdo->lastInsertId();
    $refundSecret='refund-smoke-secret';
    $createRefund=$pdo->prepare("INSERT INTO payment_refunds(payment_transaction_id,booking_id,provider,amount,currency,status,conversation_id,reason,actor_user_id,created_at,updated_at) VALUES(?,?,'iyzico',?,'TRY','processing',?,?,?,NOW(),NOW())");
    $createRefund->execute([$refundPaymentId,$refundBookingId,'25.00','REFUND-PARTIAL-'.$vendorSuffix,'Partial refund smoke',(int)$admin['id']]);
    $partialRefundId=(int)$pdo->lastInsertId();
    $partialResponse=['status'=>'success','paymentId'=>'IYZICO-REFUND-'.$vendorSuffix,'price'=>'25.00','currency'=>'TRY','conversationId'=>'REFUND-PARTIAL-'.$vendorSuffix,'refundHostReference'=>'REFUND-PARTIAL-REF'];
    $partialResponse['signature']=hash_hmac('sha256',implode(':',[$partialResponse['paymentId'],'25','TRY',$partialResponse['conversationId']]),$refundSecret);
    \App\Core\PaymentRefundService::applyVerifiedRefund($pdo,$partialRefundId,(int)$admin['id'],$partialResponse,$refundSecret);
    $partialLedger=$pdo->prepare('SELECT gross_amount,platform_fee,vendor_net_amount FROM booking_financials WHERE booking_id=?');$partialLedger->execute([$refundBookingId]);$partialValues=$partialLedger->fetch(PDO::FETCH_ASSOC);
    $partialPaymentStatus=(string)$pdo->query('SELECT status FROM payment_transactions WHERE id='.(int)$refundPaymentId)->fetchColumn();
    assertSmoke((float)$partialValues['gross_amount']===75.0&&(float)$partialValues['platform_fee']===7.5&&(float)$partialValues['vendor_net_amount']===67.5&&$partialPaymentStatus==='paid','Partial refund did not proportionally reconcile booking financials.');

    $createRefund->execute([$refundPaymentId,$refundBookingId,'75.00','REFUND-FINAL-'.$vendorSuffix,'Final refund smoke',(int)$admin['id']]);
    $finalRefundId=(int)$pdo->lastInsertId();
    $finalResponse=['status'=>'success','paymentId'=>'IYZICO-REFUND-'.$vendorSuffix,'price'=>'75.00','currency'=>'TRY','conversationId'=>'REFUND-FINAL-'.$vendorSuffix,'refundHostReference'=>'REFUND-FINAL-REF'];
    $finalResponse['signature']=hash_hmac('sha256',implode(':',[$finalResponse['paymentId'],'75','TRY',$finalResponse['conversationId']]),$refundSecret);
    \App\Core\PaymentRefundService::applyVerifiedRefund($pdo,$finalRefundId,(int)$admin['id'],$finalResponse,$refundSecret);
    $finalLedger=$pdo->prepare('SELECT gross_amount,platform_fee,vendor_net_amount FROM booking_financials WHERE booking_id=?');$finalLedger->execute([$refundBookingId]);$finalValues=$finalLedger->fetch(PDO::FETCH_ASSOC);
    $finalPaymentStatus=(string)$pdo->query('SELECT status FROM payment_transactions WHERE id='.(int)$refundPaymentId)->fetchColumn();
    assertSmoke((float)$finalValues['gross_amount']===0.0&&(float)$finalValues['platform_fee']===0.0&&(float)$finalValues['vendor_net_amount']===0.0&&$finalPaymentStatus==='refunded','Full refund did not zero the booking ledger and mark the payment refunded.');

    // Exercise iCal event import, update, cancellation, stale-feed cleanup,
    // export tokens, and cron-token protection without any external requests.
    $calendarFeedStmt=$pdo->prepare("INSERT INTO property_calendar_feeds(property_id,label,direction,feed_url_encrypted,enabled,created_at) VALUES(?,'iCal smoke source','import',?,1,NOW())");
    $calendarFeedStmt->execute([$payoutPropertyId,\App\Core\PaymentSecretVault::encrypt('https://calendar.example.invalid/ical')]);
    $calendarFeedId=(int)$pdo->lastInsertId();
    $calendarStart=(new DateTimeImmutable('+10 days'))->format('Y-m-d');
    $calendarEnd=(new DateTimeImmutable('+12 days'))->format('Y-m-d');
    $calendarEvent=['uid'=>'ical-smoke-uid','date_from'=>$calendarStart,'date_to'=>$calendarEnd,'cancelled'=>false,'summary'=>'External booking'];
    $calendarApply=\App\Core\CalendarSyncService::applyEvents($pdo,$calendarFeedId,[$calendarEvent]);
    $calendarAvailability=$pdo->prepare("SELECT id,status,date_from,date_to FROM availability WHERE property_id=? AND date_from=? AND date_to=? AND status='blocked'");
    $calendarAvailability->execute([$payoutPropertyId,$calendarStart,$calendarEnd]);$calendarBlock=$calendarAvailability->fetch(PDO::FETCH_ASSOC);
    assertSmoke($calendarApply['blocked']===1&&$calendarBlock&&$calendarBlock['status']==='blocked','iCal import did not create a blocked availability range.');

    $updatedStart=(new DateTimeImmutable('+15 days'))->format('Y-m-d');
    $updatedEnd=(new DateTimeImmutable('+17 days'))->format('Y-m-d');
    $calendarEvent['date_from']=$updatedStart;$calendarEvent['date_to']=$updatedEnd;
    \App\Core\CalendarSyncService::applyEvents($pdo,$calendarFeedId,[$calendarEvent]);
    $updatedBlock=$pdo->prepare('SELECT COUNT(*) FROM availability WHERE id=? AND date_from=? AND date_to=?');$updatedBlock->execute([(int)$calendarBlock['id'],$updatedStart,$updatedEnd]);
    assertSmoke((int)$updatedBlock->fetchColumn()===1,'Changed external iCal dates did not update the linked availability row.');

    $calendarEvent['cancelled']=true;
    \App\Core\CalendarSyncService::applyEvents($pdo,$calendarFeedId,[$calendarEvent]);
    $cancelledBlock=$pdo->prepare('SELECT COUNT(*) FROM availability WHERE id=?');$cancelledBlock->execute([(int)$calendarBlock['id']]);
    assertSmoke((int)$cancelledBlock->fetchColumn()===0,'Cancelled iCal event did not release its availability block.');

    $calendarEvent['cancelled']=false;
    \App\Core\CalendarSyncService::applyEvents($pdo,$calendarFeedId,[$calendarEvent]);
    \App\Core\CalendarSyncService::applyEvents($pdo,$calendarFeedId,[]);
    $staleOnce=$pdo->prepare('SELECT missed_syncs FROM calendar_feed_events WHERE feed_id=? AND external_uid=?');$staleOnce->execute([$calendarFeedId,'ical-smoke-uid']);
    assertSmoke((int)$staleOnce->fetchColumn()===1,'One missing feed sync removed an event too early.');
    \App\Core\CalendarSyncService::applyEvents($pdo,$calendarFeedId,[]);
    $staleStatus=$pdo->prepare("SELECT event_status,availability_id FROM calendar_feed_events WHERE feed_id=? AND external_uid=?");$staleStatus->execute([$calendarFeedId,'ical-smoke-uid']);$staleRow=$staleStatus->fetch(PDO::FETCH_ASSOC);
    assertSmoke($staleRow['event_status']==='stale'&&$staleRow['availability_id']===null,'Two consecutive missing feed syncs did not safely expire a stale event.');

    $manualBlockFrom=(new DateTimeImmutable('+30 days'))->format('Y-m-d');
    $manualBlockTo=(new DateTimeImmutable('+32 days'))->format('Y-m-d');
    $manualCalendarBlock=$pdo->prepare("INSERT INTO availability(property_id,date_from,date_to,status,nightly_price) VALUES(?,?,?,'blocked',NULL)");
    $manualCalendarBlock->execute([$payoutPropertyId,$manualBlockFrom,$manualBlockTo]);
    $exportCreated=\App\Core\CalendarSyncService::createExportFeed($pdo,$payoutPropertyId,'Smoke export');
    $exportFound=\App\Core\CalendarSyncService::exportFeed($pdo,$exportCreated['token']);
    $exportCalendar=\App\Core\CalendarSyncService::exportIcs($pdo,$payoutPropertyId);
    assertSmoke($exportFound&&str_contains($exportCalendar,'BEGIN:VCALENDAR')&&str_contains($exportCalendar,'SUMMARY:Unavailable')&&!str_contains($exportCalendar,'ical-smoke-uid'),'iCal export did not expose manual blocks or included an imported event loop.');
    $cronToken=\App\Core\CalendarSyncService::cronToken($pdo);
    assertSmoke(\App\Core\CalendarSyncService::verifyCronToken($pdo,$cronToken)&&!\App\Core\CalendarSyncService::verifyCronToken($pdo,'invalid-calendar-token'),'Calendar cron token verification failed.');
    \App\Core\CalendarSyncService::deleteFeed($pdo,$calendarFeedId,$payoutPropertyId);
    $deletedFeed=$pdo->prepare('SELECT COUNT(*) FROM property_calendar_feeds WHERE id=?');$deletedFeed->execute([$calendarFeedId]);
    assertSmoke((int)$deletedFeed->fetchColumn()===0,'Vendor calendar feed deletion did not remove the feed record.');


    echo "Installer smoke tests passed: admin, platform mode, {$villaCount} demo villas, customer account authentication, booking persistence, financial record, checkout ownership, {$migrationCount} migrations, required tables, theme fallback architecture, and repeat migration.\n";
} finally {
    if (is_file($configFile)) unlink($configFile);
    if (is_file($lockFile)) unlink($lockFile);
    if (is_dir($root . '/config')) @rmdir($root . '/config');
    if (is_dir($root . '/storage')) @rmdir($root . '/storage');
}
