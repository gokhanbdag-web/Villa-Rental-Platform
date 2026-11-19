<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
use App\Core\PaymentSandboxSimulator;
foreach (['iyzico', 'paytr'] as $provider) {
    foreach (['success', 'failure', 'tampered', 'amount_mismatch', 'wrong_order'] as $scenario) {
        $result = PaymentSandboxSimulator::run($provider, $scenario);
        if (($result['provider'] ?? null) !== $provider || ($result['scenario'] ?? null) !== $scenario) throw new RuntimeException("Scenario mismatch: $provider/$scenario");
        if (($result['network_requests'] ?? -1) !== 0 || ($result['real_charge'] ?? true) !== false || ($result['booking_changed'] ?? true) !== false) throw new RuntimeException("Unexpected side effect: $provider/$scenario");
        if (($result['payment_success'] ?? null) !== ($scenario === 'success')) throw new RuntimeException("Incorrect payment decision: $provider/$scenario");
        if ($scenario === 'success' && !($result['callback_accepted'] ?? false)) throw new RuntimeException("Successful callback rejected: $provider");
        echo "[PASS] $provider/$scenario\n";
    }
}
echo "Offline provider sandbox scenario matrix passed; no real sandbox transaction was performed.\n";
