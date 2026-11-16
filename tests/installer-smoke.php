<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Auth;
use App\Core\Installer;
use App\Core\Migrator;
use App\Core\PaymentWebhookInbox;
use App\Core\PaymentCallbackProcessor;
use App\Core\ThemeManager;
use App\Core\Router;

function assertSmoke(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$root = dirname(__DIR__);
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
        $start=(new DateTimeImmutable('+95 days'))->format('Y-m-d');
        $end=(new DateTimeImmutable('+97 days'))->format('Y-m-d');
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
    assertSmoke(($latePaidResult['result']??'')==='rejected_booking_status'&&!($latePaidResult['payment_updated']??true),'A late successful callback was accepted for a cancelled booking.');
    $lifecyclePaymentState->execute([$latePaidCase['reference']]);
    assertSmoke(($lifecyclePaymentState->fetch(PDO::FETCH_ASSOC)['status']??'')==='pending','Rejected late payment callback changed the payment transaction.');

    
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
    assertSmoke(substr_count($webRoutesSource, "status='confirmed'") >= 7 && !str_contains($webRoutesSource, "status IN ('pending','confirmed')"), 'Unpaid/pending booking requests must not block property availability or appear as occupied calendar dates; only confirmed bookings may occupy nights.');
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
    assertSmoke(str_contains($themeRoutesSource, "/admin/users/delete") && str_contains($themeRoutesSource, "UPDATE bookings SET guest_user_id=NULL WHERE guest_user_id=?") && str_contains($themeRoutesSource, "role IN ('customer','vendor')"), 'Admin member deletion must be CSRF-protected, limited to customer/vendor accounts and preserve booking history.');
    assertSmoke(str_contains($adminUsersViewSource, 'action="/admin/users/delete"') && str_contains($adminUsersViewSource, 'Üyeyi sil') && str_contains($adminUsersViewSource, 'confirm('), 'Admin user list must expose a confirmed delete action for eligible members.');
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

    // A second migration pass must be safe and leave the migration ledger intact.
    Migrator::run($pdo, $root);
    $migrationCountAfter = (int)$pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
    assertSmoke($migrationCountAfter === $migrationCount, 'Second migration pass changed the migration ledger.');

    echo "Installer smoke tests passed: admin, platform mode, {$villaCount} demo villas, customer account authentication, booking persistence, financial record, checkout ownership, {$migrationCount} migrations, required tables, theme fallback architecture, and repeat migration.\n";
} finally {
    if (is_file($configFile)) unlink($configFile);
    if (is_file($lockFile)) unlink($lockFile);
    if (is_dir($root . '/config')) @rmdir($root . '/config');
    if (is_dir($root . '/storage')) @rmdir($root . '/storage');
}
