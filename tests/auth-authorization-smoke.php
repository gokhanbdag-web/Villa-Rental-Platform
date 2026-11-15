<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/Auth.php';


$cases = [
    ['super_admin', 'admin', true],
    ['super_admin', 'customer', true],
    ['super_admin', 'vendor', true],
    ['super_admin', 'super_admin', false],
    ['admin', 'customer', true],
    ['admin', 'vendor', true],
    ['admin', 'admin', false],
    ['admin', 'super_admin', false],
    ['vendor', 'customer', false],
    ['customer', 'vendor', false],
];

$vendorBookingCases = [
    ['approved', true],
    ['pending', false],
    ['rejected', false],
    ['', false],
];
foreach ($vendorBookingCases as [$vendorStatus, $expected]) {
    $actual = \\App\\Core\\Auth::canManageVendorBookings($vendorStatus);
    if ($actual !== $expected) {
        throw new RuntimeException('Unexpected vendor booking access for status ' . $vendorStatus);
    }
}

foreach ($cases as [$actorRole, $targetRole, $expected]) {
    $actual = \App\Core\Auth::canManageUserRole($actorRole, $targetRole);
    if ($actual !== $expected) {
        throw new RuntimeException(sprintf(
            'Unexpected permission for actor %s managing %s: expected %s, got %s.',
            $actorRole,
            $targetRole,
            $expected ? 'allow' : 'deny',
            $actual ? 'allow' : 'deny'
        ));
    }
}

fwrite(STDOUT, "User role boundary smoke test passed.\n");
