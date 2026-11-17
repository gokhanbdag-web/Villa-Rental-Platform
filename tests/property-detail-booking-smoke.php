<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$property = file_get_contents($root.'/themes/default/templates/pages/property.php');
$routes = file_get_contents($root.'/routes/web.php');
$checkout = file_get_contents($root.'/themes/default/templates/pages/checkout.php');

$bookingRouteStart = strpos($routes, '$router->post(\'/villa/{slug}/reserve\'');
$bookingRoute = $bookingRouteStart !== false
    ? substr($routes, $bookingRouteStart)
    : '';
$checks = [
    'booking creation rejects overlapping pending and confirmed reservations under the property lock' => str_contains($bookingRoute, "status IN ('pending','confirmed')") && str_contains($bookingRoute, 'FOR UPDATE') && str_contains($bookingRoute, 'SELECT id,base_nightly_price') ,

    'villa detail uses a dedicated 1/3 + 2/3 hero layout' => str_contains($property, 'property-hero-layout') && str_contains($property, 'property-hero-gallery'),
    'feature list renders every amenity in a single responsive grid' => str_contains($property, '<ul class="property-feature-groups">') && str_contains($property, 'foreach($featureItems as $feature)') && str_contains($property, '$featureIconSvg'),
    'mobile villa gallery uses a desktop-style two-column collage instead of a swipe rail' => str_contains($property, 'grid-template-columns:minmax(0,1.55fr) minmax(0,1fr)!important') && str_contains($property, '.gallery.gallery-has-photos .gallery-tile:first-child{grid-row:1 / span 2!important}') && str_contains($property, '.gallery.gallery-has-photos .gallery-tile:nth-child(n+4){display:none!important}') && str_contains($property, '.gallery.gallery-has-photos .gallery-mobile-counter{display:none!important}') && !str_contains($property, 'scroll-snap-type:x mandatory!important'),
    'villa features show labels in two balanced columns on mobile' => str_contains($property, '.property-feature-groups{grid-template-columns:repeat(2,minmax(0,1fr))') && str_contains($property, '.property-feature-item .property-feature-label{font-size:12px') && str_contains($property, 'class="property-feature-label"'),
    'second booking month forces white unselected days and primary-color text including focus' => str_contains($property, '#villaCalendarGridNext button.calendar-day:not(.selected):not(.in-range):not(.is-selected):not(.booked):not(.blocked)') && str_contains($property, 'background:#fff!important;background-color:#fff!important;background-image:none!important;color:var(--villa-primary)!important'),
    'mobile feature list does not use tooltip buttons or hidden labels' => !str_contains($property, 'property-feature-tip') && !str_contains($property, 'is-tooltip-open') && !str_contains($property, 'data-tooltip'),
    'location map sits in the adjacent information column' => str_contains($property, 'property-info-layout') && str_contains($property, 'property-location-section'),
    'booking form is full-width rather than a sidebar' => str_contains($property, '<section class="booking property-booking"') && !str_contains($property, '<aside class="booking property-booking"'),
    'availability calendar renders two months' => str_contains($property, 'villaCalendarGridNext') && str_contains($property, 'calendarNextMonth') && str_contains($property, 'İki aylık müsaitlik'),
    'date selection displays total and continues to step two' => str_contains($property, 'calendarTotalAmount') && str_contains($property, 'calendarReserve') && str_contains($property, 'bookingStepDetails'),
    'booking collects iyzico billing details' => str_contains($property, 'name="identity_number"') && str_contains($property, 'name="billing_city"') && str_contains($property, 'name="billing_address"'),
    'reservation stores one-time iyzico auto-start data in session' => str_contains($routes, 'villa_iyzico_autostart') && str_contains($routes, '?start_iyzico=1'),
    'checkout auto-submits the iyzico payment form' => str_contains($checkout, 'autoStartIyzicoPayment') && str_contains($checkout, 'paymentForm.submit()'),
    'guest booking offers enabled early check-in and late checkout options' => str_contains($property, 'bookingEarlyCheckin') && str_contains($property, 'bookingLateCheckout'),
    'guest booking refreshes quoted total when time extras change' => str_contains($property, 'early_checkin:early&&early.checked') && str_contains($property, 'late_checkout:late&&late.checked'),
    'admin and vendor can manage booking time extras per property' => str_contains($routes, '/admin/properties/{id}/booking-time-options') && str_contains($routes, '/vendor/properties/{id}/booking-time-options'),
];

$failed = [];
foreach ($checks as $label => $passed) {
    echo ($passed ? '[PASS] ' : '[FAIL] ').$label.PHP_EOL;
    if (!$passed) $failed[] = $label;
}
if ($failed) {
    fwrite(STDERR, count($failed).' property detail booking smoke check(s) failed.'.PHP_EOL);
    exit(1);
}
echo 'All property detail booking smoke checks passed.'.PHP_EOL;
