<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/Auth.php';
require dirname(__DIR__) . '/app/Core/BookingStatusPolicy.php';


$cases = [
    ['super_admin', 'admin', true],
    ['super_admin', 'customer', true],
    ['super_admin', 'vendor', true],
    ['super_admin', 'super_admin', false],
    ['super_admin', 'editor', true],
    ['super_admin', 'support', true],
    ['admin', 'editor', false],
    ['admin', 'support', false],
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
    $actual = \App\Core\Auth::canManageVendorBookings($vendorStatus);
    if ($actual !== $expected) {
        throw new RuntimeException('Unexpected vendor booking access for status ' . $vendorStatus);
    }
}

$vendorTransitionCases = [
    ['pending','confirmed',true],
    ['pending','cancelled',true],
    ['pending','completed',false],
    ['confirmed','cancelled',true],
    ['confirmed','completed',true],
    ['confirmed','pending',false],
    ['cancelled','confirmed',false],
    ['completed','confirmed',false],
    ['cancelled','cancelled',true],
];
foreach ($vendorTransitionCases as [$current,$target,$expected]) {
    $actual = \App\Core\BookingStatusPolicy::canVendorTransition($current,$target);
    if ($actual !== $expected) {
        throw new RuntimeException('Unexpected vendor booking transition '.$current.' -> '.$target);
    }
}

$routesSource = file_get_contents(dirname(__DIR__) . '/routes/web.php');
$vendorRouteStart = strpos($routesSource, "\$router->post('/vendor/bookings/status'");
$vendorRouteEnd = $vendorRouteStart !== false ? strpos($routesSource, "\$router->get('/vendor/properties'", $vendorRouteStart) : false;
$vendorRoute = $vendorRouteStart !== false && $vendorRouteEnd !== false ? substr($routesSource,$vendorRouteStart,$vendorRouteEnd-$vendorRouteStart) : '';
if ($vendorRoute === '' || strpos($vendorRoute, 'BookingStatusPolicy::canVendorTransition') === false
    || strpos($vendorRoute, "status IN ('pending','confirmed')") === false
    || strpos($vendorRoute, 'BookingHoldExpiryService::expireSafePendingForProperty') === false
    || strpos($vendorRoute, 'SELECT id FROM properties WHERE id=? AND vendor_id=? LIMIT 1 FOR UPDATE') === false) {
    throw new RuntimeException('Vendor booking route does not enforce ownership, safe lock order, pending overlap and transition rules.');
}

$permissionCases = [
    ['super_admin', 'admin.pages.edit', true],
    ['admin', 'admin.bookings.view', true],
    ['editor', 'admin.pages.view', true],
    ['editor', 'admin.pages.edit', true],
    ['editor', 'admin.blog.edit', true],
    ['editor', 'admin.bookings.view', false],
    ['editor', 'admin.users.manage', false],
    ['support', 'admin.bookings.view', true],
    ['support', 'admin.bookings.edit', false],
    ['support', 'admin.pages.view', false],
    ['customer', 'admin.bookings.view', false],
    ['vendor', 'admin.blog.edit', false],
];
foreach ($permissionCases as [$role, $permission, $expected]) {
    if (\App\Core\Auth::roleCan($role, $permission) !== $expected) {
        throw new RuntimeException('Unexpected role permission: ' . $role . ' / ' . $permission);
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

$calendarServiceSource = file_get_contents(dirname(__DIR__) . '/app/Core/CalendarSyncService.php');
if (strpos($routesSource, 'syncFeed($pdo,$feedId,$pid)') === false
    || strpos($calendarServiceSource, 'public static function syncFeed(PDO $pdo,int $feedId,?int $expectedPropertyId=null)') === false
    || strpos($calendarServiceSource, 'AND property_id=?') === false) {
    throw new RuntimeException('Vendor calendar sync must bind the feed ID to the vendor-owned property ID.');
}

fwrite(STDOUT, "User role boundary smoke test passed.\n");
