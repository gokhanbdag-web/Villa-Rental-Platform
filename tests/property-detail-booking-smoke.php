<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$property = file_get_contents($root.'/themes/default/templates/pages/property.php');
$routes = file_get_contents($root.'/routes/web.php');
$checkout = file_get_contents($root.'/themes/default/templates/pages/checkout.php');

$checks = [
    'villa detail uses a dedicated 1/3 + 2/3 hero layout' => str_contains($property, 'property-hero-layout') && str_contains($property, 'property-hero-gallery'),
    'feature area is grouped into three icon columns' => str_contains($property, 'property-feature-groups') && str_contains($property, 'for($groupIndex=0;$groupIndex<3;$groupIndex++)') && str_contains($property, '$featureIconSvg'),
    'villa features stay in three columns on mobile widths' => str_contains($property, '@media(max-width:700px)') && str_contains($property, '.property-feature-groups{grid-template-columns:repeat(3,minmax(0,1fr))') && str_contains($property, '@media(max-width:420px){.property-feature-groups{grid-template-columns:repeat(3,minmax(0,1fr))'),
    'location map sits in the adjacent information column' => str_contains($property, 'property-info-layout') && str_contains($property, 'property-location-section'),
    'booking form is full-width rather than a sidebar' => str_contains($property, '<section class="booking property-booking"') && !str_contains($property, '<aside class="booking property-booking"'),
    'availability calendar renders two months' => str_contains($property, 'villaCalendarGridNext') && str_contains($property, 'calendarNextMonth') && str_contains($property, 'İki aylık müsaitlik'),
    'date selection displays total and continues to step two' => str_contains($property, 'calendarTotalAmount') && str_contains($property, 'calendarReserve') && str_contains($property, 'bookingStepDetails'),
    'booking collects iyzico billing details' => str_contains($property, 'name="identity_number"') && str_contains($property, 'name="billing_city"') && str_contains($property, 'name="billing_address"'),
    'reservation stores one-time iyzico auto-start data in session' => str_contains($routes, 'villa_iyzico_autostart') && str_contains($routes, '?start_iyzico=1'),
    'checkout auto-submits the iyzico payment form' => str_contains($checkout, 'autoStartIyzicoPayment') && str_contains($checkout, 'paymentForm.submit()'),
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
