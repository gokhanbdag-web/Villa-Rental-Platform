<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Installer;
use App\Core\PaymentCallbackProcessor;
use App\Core\PaymentCheckoutGuard;

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

    echo "Payment concurrency smoke tests passed: property-lock serialization, callback rollback/retry, overlapping paid-booking manual review, and checkout reservation locking.\n";
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
