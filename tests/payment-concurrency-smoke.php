<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Installer;
use App\Core\PaymentCallbackProcessor;
use App\Core\PaymentCheckoutGuard;
use App\Core\PaymentRefundService;
use App\Core\IyzicoPaymentReconciler;
use App\Core\PaymentWebhookInbox;

function concurrencyAssert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$configFile = $root . '/config/config.php';
$lockFile = $root . '/storage/installed.lock';
if (Installer::isInstalled()) {
    throw new RuntimeException('Concurrency smoke test requires installer-smoke cleanup.');
}

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = (int)(getenv('DB_PORT') ?: 3306);
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root';
$database = 'villa_concurrency_' . bin2hex(random_bytes(4));
$server = new PDO(
    sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
    $user,
    $password,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$server->exec('CREATE DATABASE `' . $database . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

try {
    $db = ['host' => $host, 'port' => $port, 'name' => $database, 'user' => $user, 'password' => $password];
    Installer::install([
        'db_host' => $host,
        'db_port' => $port,
        'db_name' => $database,
        'db_user' => $user,
        'db_password' => $password,
        'admin_username' => 'concurrency_admin',
        'admin_email' => 'concurrency-admin@example.test',
        'admin_password' => 'ConcurrencySmokePassword!2026',
        'admin_password_confirm' => 'ConcurrencySmokePassword!2026',
        'mode' => 'multi_vendor',
    ]);

    $pdoA = Installer::connect($db);
    $pdoB = Installer::connect($db);
    $pdoB->exec('SET SESSION innodb_lock_wait_timeout=1');
    $property = $pdoA->query("SELECT id,currency FROM properties WHERE status='published' ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    concurrencyAssert(is_array($property), 'Concurrency test requires a published demo property.');

    $email = 'concurrency-' . bin2hex(random_bytes(4)) . '@example.test';
    $createUser = $pdoA->prepare("INSERT INTO users(username,email,password_hash,role,status,created_at) VALUES(?,?,?,'customer','active',NOW())");
    $createUser->execute(['concurrency_' . bin2hex(random_bytes(4)), $email, password_hash('ConcurrencyCustomer!2026', PASSWORD_DEFAULT)]);
    $customerId = (int)$pdoA->lastInsertId();

    $createBooking = $pdoA->prepare("INSERT INTO bookings(booking_code,property_id,guest_user_id,guest_name,guest_email,guest_phone,booking_source,check_in,check_out,guests,subtotal,fees,total,currency,status,created_at) VALUES(?,?,?,?,?,?,'website',?,?,?,?,?,?,?,'pending',NOW())");
    $createPayment = $pdoA->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,amount,currency,status,created_at) VALUES(?,'paytr',?,?,?,'pending',NOW())");
    $baseStart = (new DateTimeImmutable('+300 days'))->format('Y-m-d');
    $baseEnd = (new DateTimeImmutable('+302 days'))->format('Y-m-d');
    $currency = (string)$property['currency'];

    $newBooking = static function (string $label, string $start, string $end) use ($createBooking, $createPayment, $property, $customerId, $email, $currency, $pdoA): array {
        $code = 'VRC' . strtoupper(bin2hex(random_bytes(5)));
        $createBooking->execute([$code, (int)$property['id'], $customerId, 'Concurrency Guest', $email, '', $start, $end, 1, 100, 0, 100, $currency]);
        $bookingId = (int)$pdoA->lastInsertId();
        return ['id' => $bookingId, 'code' => $code, 'label' => $label];
    };

    // The first callback confirms a booking. A later overlapping pending
    // request must be paid-but-unconfirmed and routed to manual review.
    $first = $newBooking('first', $baseStart, $baseEnd);
    $firstReference = 'VRC-PAY-' . strtoupper(bin2hex(random_bytes(5)));
    $createPayment->execute([$first['id'], $firstReference, '100.00', $currency]);
    $processorA = new PaymentCallbackProcessor($pdoA);
    $firstCallback = [
        'booking_id' => $first['id'], 'provider' => 'paytr', 'provider_reference' => $firstReference,
        'amount' => '100.00', 'currency' => $currency, 'status' => 'success',
    ];
    $firstResult = $processorA->processVerified('paytr', 'VRC-EVENT-' . bin2hex(random_bytes(5)), 'payment_callback', $firstReference, ['status' => 'success'], $firstCallback);
    concurrencyAssert(($firstResult['result'] ?? '') === 'processed', 'First booking payment did not confirm.');

    $second = $newBooking('overlap', $baseStart, $baseEnd);
    $secondReference = 'VRC-PAY-' . strtoupper(bin2hex(random_bytes(5)));
    $createPayment->execute([$second['id'], $secondReference, '100.00', $currency]);
    $secondEvent = 'VRC-EVENT-' . strtoupper(bin2hex(random_bytes(5)));
    $secondCallback = [
        'booking_id' => $second['id'], 'provider' => 'paytr', 'provider_reference' => $secondReference,
        'amount' => '100.00', 'currency' => $currency, 'status' => 'success',
    ];

    // Hold the property lock on connection A while connection B tries to
    // apply the competing callback. The second transaction must time out and
    // roll back its webhook inbox row instead of partially changing payment.
    $pdoA->beginTransaction();
    $propertyLock = $pdoA->prepare('SELECT id FROM properties WHERE id=? FOR UPDATE');
    $propertyLock->execute([(int)$property['id']]);
    $lockTimedOut = false;
    try {
        (new PaymentCallbackProcessor($pdoB))->processVerified(
            'paytr', $secondEvent, 'payment_callback', $secondReference, ['status' => 'success'], $secondCallback
        );
    } catch (PDOException $error) {
        $lockTimedOut = (int)($error->errorInfo[1] ?? 0) === 1205;
        if ($pdoB->inTransaction()) {
            $pdoB->rollBack();
        }
    } finally {
        if ($pdoA->inTransaction()) {
            $pdoA->commit();
        }
    }
    concurrencyAssert($lockTimedOut, 'Competing callback did not wait for the property lock as expected.');

    $paymentState = $pdoA->prepare('SELECT status FROM payment_transactions WHERE provider_reference=?');
    $paymentState->execute([$secondReference]);
    concurrencyAssert($paymentState->fetchColumn() === 'pending', 'Lock-timeout callback partially changed the payment.');
    $eventState = $pdoA->prepare('SELECT COUNT(*) FROM payment_webhook_events WHERE provider=? AND provider_event_id=?');
    $eventState->execute(['paytr', $secondEvent]);
    concurrencyAssert((int)$eventState->fetchColumn() === 0, 'Lock-timeout callback left a partially committed webhook event.');

    $secondResult = (new PaymentCallbackProcessor($pdoB))->processVerified(
        'paytr', $secondEvent, 'payment_callback', $secondReference, ['status' => 'success'], $secondCallback
    );
    concurrencyAssert(($secondResult['result'] ?? '') === 'paid_booking_conflict'
        && ($secondResult['manual_review_required'] ?? false) === true,
        'Retry did not detect the already-confirmed overlapping booking and require manual review.');
    $bookingState = $pdoA->prepare('SELECT status FROM bookings WHERE id=?');
    $bookingState->execute([$second['id']]);
    concurrencyAssert($bookingState->fetchColumn() === 'pending', 'Overlapping paid booking was incorrectly confirmed.');
    $paymentState->execute([$secondReference]);
    concurrencyAssert($paymentState->fetchColumn() === 'paid', 'Verified provider payment was not retained for manual review.');

    // Checkout reservation must also serialize on the booking row.
    $checkout = $newBooking('checkout-lock', (new DateTimeImmutable('+310 days'))->format('Y-m-d'), (new DateTimeImmutable('+312 days'))->format('Y-m-d'));
    $pdoA->beginTransaction();
    $bookingLock = $pdoA->prepare('SELECT id FROM bookings WHERE id=? FOR UPDATE');
    $bookingLock->execute([$checkout['id']]);
    $checkoutTimedOut = false;
    try {
        PaymentCheckoutGuard::reserveIyzico($pdoB, $checkout['id'], 'VRC-CONV-' . bin2hex(random_bytes(5)), '100.00', $currency, ['environment' => 'sandbox']);
    } catch (PDOException $error) {
        $checkoutTimedOut = (int)($error->errorInfo[1] ?? 0) === 1205;
        if ($pdoB->inTransaction()) {
            $pdoB->rollBack();
        }
    } finally {
        if ($pdoA->inTransaction()) {
            $pdoA->commit();
        }
    }
    concurrencyAssert($checkoutTimedOut, 'Concurrent checkout reservation did not wait for the booking lock.');
    $checkoutCount = $pdoA->prepare('SELECT COUNT(*) FROM payment_transactions WHERE booking_id=?');
    $checkoutCount->execute([$checkout['id']]);
    concurrencyAssert((int)$checkoutCount->fetchColumn() === 0, 'Lock-timeout checkout left a partial payment reservation.');

    $checkoutResult = PaymentCheckoutGuard::reserveIyzico(
        $pdoB, $checkout['id'], 'VRC-CONV-' . bin2hex(random_bytes(5)), '100.00', $currency, ['environment' => 'sandbox']
    );
    concurrencyAssert(($checkoutResult['result'] ?? '') === 'reserved', 'Checkout could not reserve after the booking lock was released.');

    // A verified payment arriving after an administrator/customer cancellation
    // must be recorded as paid for reconciliation, but must never reopen the booking.
    $cancelled = $newBooking('cancelled-late-payment', (new DateTimeImmutable('+320 days'))->format('Y-m-d'), (new DateTimeImmutable('+322 days'))->format('Y-m-d'));
    $cancelBooking = $pdoA->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND status='pending'");
    $cancelBooking->execute([$cancelled['id']]);
    concurrencyAssert($cancelBooking->rowCount() === 1, 'Could not prepare cancelled-booking callback scenario.');
    $lateReference = 'VRC-PAY-' . strtoupper(bin2hex(random_bytes(5)));
    $createPayment->execute([$cancelled['id'], $lateReference, '100.00', $currency]);
    $lateEvent = 'VRC-EVENT-' . strtoupper(bin2hex(random_bytes(5)));
    $lateCallback = [
        'booking_id' => $cancelled['id'], 'provider' => 'paytr', 'provider_reference' => $lateReference,
        'amount' => '100.00', 'currency' => $currency, 'status' => 'success',
    ];
    $lateProcessor = new PaymentCallbackProcessor($pdoA);
    $latePayload = ['merchant_oid' => $lateReference, 'status' => 'success', 'total_amount' => '10000'];
    $lateResult = $lateProcessor->processVerified(
        'paytr', $lateEvent, 'payment_callback', $lateReference, $latePayload, $lateCallback
    );
    concurrencyAssert(($lateResult['result'] ?? '') === 'paid_booking_conflict'
        && ($lateResult['manual_review_required'] ?? false) === true,
        'Late payment for a cancelled booking was not flagged for manual review.');
    $bookingState->execute([$cancelled['id']]);
    concurrencyAssert($bookingState->fetchColumn() === 'cancelled', 'Late payment incorrectly reopened a cancelled booking.');
    $paymentState->execute([$lateReference]);
    concurrencyAssert($paymentState->fetchColumn() === 'paid', 'Verified late payment was not retained for reconciliation.');

    // Exact event replay and a second event ID for the same paid transaction
    // must both remain idempotent and must not create another payment record.
    $replay = $lateProcessor->processVerified(
        'paytr', $lateEvent, 'payment_callback', $lateReference, $latePayload, $lateCallback
    );
    concurrencyAssert(($replay['result'] ?? '') === 'duplicate' && ($replay['payment_updated'] ?? true) === false,
        'Exact provider event replay was not treated as a no-op duplicate.');
    $secondEvent = 'VRC-EVENT-' . strtoupper(bin2hex(random_bytes(5)));
    $secondReplay = $lateProcessor->processVerified(
        'paytr', $secondEvent, 'payment_callback', $lateReference, $latePayload, $lateCallback
    );
    concurrencyAssert(($secondReplay['result'] ?? '') === 'paid_booking_conflict'
        && ($secondReplay['payment_updated'] ?? true) === false,
        'A second event ID for an already-paid transaction was not idempotent.');
    $paymentCount = $pdoA->prepare('SELECT COUNT(*) FROM payment_transactions WHERE booking_id=?');
    $paymentCount->execute([$cancelled['id']]);
    concurrencyAssert((int)$paymentCount->fetchColumn() === 1, 'Duplicate callbacks created an extra payment transaction.');
    $eventCount = $pdoA->prepare("SELECT COUNT(*) FROM payment_webhook_events WHERE provider='paytr' AND transaction_reference=? AND processing_status='processed'");
    $eventCount->execute([$lateReference]);
    concurrencyAssert((int)$eventCount->fetchColumn() === 2, 'Distinct verified callback events were not recorded exactly once.');

    // Refund reservations and settlement must serialize on the booking/payment rows.
    // Reflection exercises the real reservation transaction without sending any
    // external iyzico request or issuing a real refund.
    $refundBooking = $newBooking('refund-concurrency', (new DateTimeImmutable('+330 days'))->format('Y-m-d'), (new DateTimeImmutable('+332 days'))->format('Y-m-d'));
    $iyzicoPaymentReference = 'VRC-IYZ-' . strtoupper(bin2hex(random_bytes(5)));
    $paymentId = 'VRC-IYZ-PAY-' . strtoupper(bin2hex(random_bytes(5)));
    $paymentPayload = json_encode(['verified_checkout_form' => ['paymentId' => $paymentId]], JSON_THROW_ON_ERROR);
    $requestPayload = json_encode(['environment' => 'sandbox'], JSON_THROW_ON_ERROR);
    $createIyzicoPayment = $pdoA->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,amount,currency,status,request_payload,response_payload,created_at) VALUES(?,'iyzico',?, '100.00',?,'paid',?,?,NOW())");
    $createIyzicoPayment->execute([$refundBooking['id'], $iyzicoPaymentReference, $currency, $requestPayload, $paymentPayload]);
    $refundPaymentId = (int)$pdoA->lastInsertId();
    $createFinancial = $pdoA->prepare("INSERT INTO booking_financials(booking_id,gross_amount,platform_fee,vendor_net_amount,currency,payout_status,created_at) VALUES(?, '100.00','12.00','88.00',?,'unpaid',NOW())");
    $createFinancial->execute([$refundBooking['id'], $currency]);

    $reserveRefund = new ReflectionMethod(PaymentRefundService::class, 'reserve');
    $pdoA->beginTransaction();
    $refundBookingLock = $pdoA->prepare('SELECT id FROM bookings WHERE id=? FOR UPDATE');
    $refundBookingLock->execute([$refundBooking['id']]);
    $refundReserveTimedOut = false;
    try {
        $reserveRefund->invoke(null, $pdoB, $customerId, $refundPaymentId, 6000, 'Concurrency test');
    } catch (PDOException $error) {
        $refundReserveTimedOut = (int)($error->errorInfo[1] ?? 0) === 1205;
        if ($pdoB->inTransaction()) $pdoB->rollBack();
    } finally {
        if ($pdoA->inTransaction()) $pdoA->commit();
    }
    concurrencyAssert($refundReserveTimedOut, 'Concurrent refund reservation did not wait for the booking lock.');
    $refundCount = $pdoA->prepare('SELECT COUNT(*) FROM payment_refunds WHERE payment_transaction_id=?');
    $refundCount->execute([$refundPaymentId]);
    concurrencyAssert((int)$refundCount->fetchColumn() === 0, 'Timed-out refund reservation left a partial refund row.');

    $reservedRefund = $reserveRefund->invoke(null, $pdoA, $customerId, $refundPaymentId, 6000, 'First partial refund');
    concurrencyAssert((int)$reservedRefund['id'] > 0, 'First partial refund was not reserved.');
    $pendingRefundBlocked = false;
    try {
        $reserveRefund->invoke(null, $pdoB, $customerId, $refundPaymentId, 4000, 'Competing refund');
    } catch (RuntimeException $error) {
        $pendingRefundBlocked = str_contains($error->getMessage(), 'başka bir iade talebi');
    }
    concurrencyAssert($pendingRefundBlocked, 'A second refund was not blocked while the first refund outcome was pending.');

    $refundSecret = 'refund-concurrency-secret';
    $refundResponse = [
        'status' => 'success',
        'paymentId' => $paymentId,
        'price' => '60.00',
        'currency' => $currency,
        'conversationId' => $reservedRefund['conversation_id'],
        'refundHostReference' => 'VRC-REFUND-' . bin2hex(random_bytes(4)),
    ];
    $refundSignatureMessage = implode(':', [$refundResponse['paymentId'], '60', $refundResponse['currency'], $refundResponse['conversationId']]);
    $refundResponse['signature'] = hash_hmac('sha256', $refundSignatureMessage, $refundSecret);

    // Settlement must wait for the booking lock and must not partially
    // decrement financial balances when the lock cannot be acquired.
    $pdoA->beginTransaction();
    $refundBookingLock->execute([$refundBooking['id']]);
    $refundApplyTimedOut = false;
    try {
        PaymentRefundService::applyVerifiedRefund($pdoB, (int)$reservedRefund['id'], $customerId, $refundResponse, $refundSecret);
    } catch (PDOException $error) {
        $refundApplyTimedOut = (int)($error->errorInfo[1] ?? 0) === 1205;
        if ($pdoB->inTransaction()) $pdoB->rollBack();
    } finally {
        if ($pdoA->inTransaction()) $pdoA->commit();
    }
    concurrencyAssert($refundApplyTimedOut, 'Concurrent refund settlement did not wait for the booking lock.');
    $financialState = $pdoA->prepare('SELECT gross_amount FROM booking_financials WHERE booking_id=?');
    $financialState->execute([$refundBooking['id']]);
    concurrencyAssert((string)$financialState->fetchColumn() === '100.00', 'Timed-out refund settlement partially changed booking financials.');

    $appliedRefund = PaymentRefundService::applyVerifiedRefund($pdoB, (int)$reservedRefund['id'], $customerId, $refundResponse, $refundSecret);
    concurrencyAssert(($appliedRefund['status'] ?? '') === 'succeeded', 'Verified refund could not be applied after releasing the booking lock.');
    $financialState->execute([$refundBooking['id']]);
    concurrencyAssert((string)$financialState->fetchColumn() === '40.00', 'Refund settlement did not decrement the remaining gross amount exactly once.');
    $refundStatus = $pdoA->prepare('SELECT status FROM payment_refunds WHERE id=?');
    $refundStatus->execute([(int)$reservedRefund['id']]);
    concurrencyAssert($refundStatus->fetchColumn() === 'succeeded', 'Refund was not marked succeeded after verified settlement.');

    $duplicateSettlementBlocked = false;
    try {
        PaymentRefundService::applyVerifiedRefund($pdoA, (int)$reservedRefund['id'], $customerId, $refundResponse, $refundSecret);
    } catch (RuntimeException $error) {
        $duplicateSettlementBlocked = str_contains($error->getMessage(), 'artık işlenebilir durumda değil');
    }
    concurrencyAssert($duplicateSettlementBlocked, 'A replayed successful refund settlement was not rejected.');

    $overRefundBlocked = false;
    try {
        $reserveRefund->invoke(null, $pdoA, $customerId, $refundPaymentId, 5000, 'Exceeds remaining balance');
    } catch (RuntimeException $error) {
        $overRefundBlocked = str_contains($error->getMessage(), 'kalan tahsilat tutarını aşıyor');
    }
    concurrencyAssert($overRefundBlocked, 'Refund reservation exceeded the remaining booking/payment balance.');
    $secondPartialRefund = $reserveRefund->invoke(null, $pdoA, $customerId, $refundPaymentId, 4000, 'Remaining partial refund');
    concurrencyAssert((int)$secondPartialRefund['id'] > 0, 'A valid refund within the remaining balance was rejected.');

    // Expired iyzico provider sessions must have a single atomic
    // reconciliation claimant and a one-minute retry cooldown.
    $reconcileBooking = $newBooking('iyzico-reconciliation', (new DateTimeImmutable('+340 days'))->format('Y-m-d'), (new DateTimeImmutable('+342 days'))->format('Y-m-d'));
    $reconcileReference = 'VRC-IYZ-TOKEN-' . strtoupper(bin2hex(random_bytes(5)));
    $reconcileRequest = json_encode([
        'conversationId' => 'VRC-CONV-' . bin2hex(random_bytes(5)),
        'basketId' => $reconcileBooking['code'],
        'environment' => 'sandbox',
    ], JSON_THROW_ON_ERROR);
    $createReconcilePayment = $pdoA->prepare("INSERT INTO payment_transactions(booking_id,provider,provider_reference,amount,currency,status,request_payload,expires_at,created_at,updated_at) VALUES(?,'iyzico',?,'100.00',?,'pending',?,DATE_SUB(NOW(),INTERVAL 2 MINUTE),NOW(),NOW())");
    $createReconcilePayment->execute([$reconcileBooking['id'], $reconcileReference, $currency, $reconcileRequest]);
    $reconcilePaymentId = (int)$pdoA->lastInsertId();

    $pdoA->beginTransaction();
    $reconcileRowLock = $pdoA->prepare('SELECT id FROM payment_transactions WHERE id=? FOR UPDATE');
    $reconcileRowLock->execute([$reconcilePaymentId]);
    $reconcileClaimTimedOut = false;
    try {
        IyzicoPaymentReconciler::claimReconciliationAttempt($pdoB, $reconcilePaymentId);
    } catch (PDOException $error) {
        $reconcileClaimTimedOut = (int)($error->errorInfo[1] ?? 0) === 1205;
        if ($pdoB->inTransaction()) $pdoB->rollBack();
    } finally {
        if ($pdoA->inTransaction()) $pdoA->commit();
    }
    concurrencyAssert($reconcileClaimTimedOut, 'Concurrent iyzico reconciliation claim did not wait for the payment-row lock.');

    concurrencyAssert(IyzicoPaymentReconciler::claimReconciliationAttempt($pdoA, $reconcilePaymentId), 'First iyzico reconciliation claim was not acquired.');
    concurrencyAssert(!IyzicoPaymentReconciler::claimReconciliationAttempt($pdoB, $reconcilePaymentId), 'A second iyzico reconciliation claim bypassed the one-minute cooldown.');
    $pdoA->prepare('UPDATE payment_transactions SET reconciliation_checked_at=DATE_SUB(NOW(),INTERVAL 61 SECOND) WHERE id=?')->execute([$reconcilePaymentId]);
    concurrencyAssert(IyzicoPaymentReconciler::claimReconciliationAttempt($pdoB, $reconcilePaymentId), 'Iyzico reconciliation could not be retried after the cooldown elapsed.');
    $pdoA->prepare("UPDATE payment_transactions SET status='paid' WHERE id=?")->execute([$reconcilePaymentId]);
    concurrencyAssert(!IyzicoPaymentReconciler::claimReconciliationAttempt($pdoA, $reconcilePaymentId), 'A paid iyzico transaction was incorrectly eligible for reconciliation.');

    // The webhook inbox must allow exactly one concurrent claimant,
    // recover failed events, and keep processed events closed to replay.
    $webhookEventId = 'VRC-WEBHOOK-' . strtoupper(bin2hex(random_bytes(5)));
    $webhookReference = 'VRC-PAY-' . strtoupper(bin2hex(random_bytes(5)));
    $inboxA = new PaymentWebhookInbox($pdoA);
    $inboxB = new PaymentWebhookInbox($pdoB);
    $webhookPayload = ['status' => 'success', 'reference' => $webhookReference];
    concurrencyAssert(
        $inboxA->recordVerified('paytr', $webhookEventId, 'payment_callback', $webhookReference, $webhookPayload) === 'recorded',
        'A verified webhook event was not recorded.'
    );

    // Duplicate identity is safe only when payload, type and transaction reference match.
    concurrencyAssert($inboxB->recordVerified('paytr', $webhookEventId, 'payment_callback', $webhookReference, $webhookPayload) === 'duplicate', 'Identical webhook replay was not recognized.');
    concurrencyAssert($inboxB->recordVerified('paytr', $webhookEventId, 'refund_callback', $webhookReference, $webhookPayload) === 'conflict', 'Same event ID with a different event type was accepted.');
    concurrencyAssert($inboxB->recordVerified('paytr', $webhookEventId, 'payment_callback', $webhookReference . '-OTHER', $webhookPayload) === 'conflict', 'Same event ID with a different transaction reference was accepted.');
    concurrencyAssert($inboxB->recordVerified('paytr', $webhookEventId, 'payment_callback', $webhookReference, ['status' => 'failed']) === 'conflict', 'Same event ID with different payload was accepted.');
    $webhookIdentity = $pdoA->prepare('SELECT event_type,transaction_reference,processing_status FROM payment_webhook_events WHERE provider=? AND provider_event_id=?');
    $webhookIdentity->execute(['paytr', $webhookEventId]);
    $webhookOriginal = $webhookIdentity->fetch(PDO::FETCH_ASSOC);
    concurrencyAssert($webhookOriginal['event_type'] === 'payment_callback' && $webhookOriginal['transaction_reference'] === $webhookReference && $webhookOriginal['processing_status'] === 'received', 'Conflicting webhook replay mutated the original event.');

    $pdoA->beginTransaction();
    $webhookRowLock = $pdoA->prepare('SELECT id FROM payment_webhook_events WHERE provider=? AND provider_event_id=? FOR UPDATE');
    $webhookRowLock->execute(['paytr', $webhookEventId]);
    $webhookClaimTimedOut = false;
    try {
        $inboxB->claim('paytr', $webhookEventId);
    } catch (PDOException $error) {
        $webhookClaimTimedOut = (int)($error->errorInfo[1] ?? 0) === 1205;
        if ($pdoB->inTransaction()) $pdoB->rollBack();
    } finally {
        if ($pdoA->inTransaction()) $pdoA->commit();
    }
    concurrencyAssert($webhookClaimTimedOut, 'Concurrent webhook claim did not wait for the event-row lock.');

    concurrencyAssert($inboxA->claim('paytr', $webhookEventId), 'First webhook claim was not acquired.');
    concurrencyAssert(!$inboxB->claim('paytr', $webhookEventId), 'A second processor claimed an event already in processing.');
    concurrencyAssert($inboxA->markFailed('paytr', $webhookEventId, 'Synthetic retryable failure'), 'Webhook failure state was not recorded.');
    concurrencyAssert($inboxB->claim('paytr', $webhookEventId), 'A failed webhook could not be safely reclaimed for retry.');
    concurrencyAssert($inboxB->markProcessed('paytr', $webhookEventId), 'Reclaimed webhook could not be marked processed.');
    concurrencyAssert(!$inboxA->claim('paytr', $webhookEventId), 'A processed webhook was claimed again.');
    concurrencyAssert(!$inboxA->markProcessed('paytr', $webhookEventId), 'A processed webhook accepted a second completion transition.');

    echo "Payment concurrency smoke tests passed: payment callback serialization/idempotency, checkout locking, refund reservation locking, pending-refund exclusion, verified settlement locking, duplicate settlement rejection, remaining-balance enforcement, atomic iyzico reconciliation claims with cooldown, and exclusive webhook claiming with failed-event retry.\n";
} finally {
    if (isset($pdoA) && $pdoA instanceof PDO && $pdoA->inTransaction()) {
        $pdoA->rollBack();
    }
    if (is_file($configFile)) {
        unlink($configFile);
    }
    if (is_file($lockFile)) {
        unlink($lockFile);
    }
    if (is_file($root . '/storage/migrations-state.json')) {
        unlink($root . '/storage/migrations-state.json');
    }
    if (is_dir($root . '/config')) {
        @rmdir($root . '/config');
    }
    if (is_dir($root . '/storage')) {
        @rmdir($root . '/storage');
    }
    $server->exec('DROP DATABASE IF EXISTS `' . $database . '`');
}
